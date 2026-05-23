<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

/**
 * Inventory Controller — Morning Prep + Day Ledger System
 */
class InventoryController extends Controller {
public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // ══════════════════════════════════════════════════════════════
    //  MORNING PREP
    // ══════════════════════════════════════════════════════════════

    /**
     * Display Morning Prep form
     * Route: ?url=inventory/dailyPrep
     */
    public function dailyPrep() {
        $date  = $_GET['date'] ?? date('Y-m-d');
        $items = $this->db->query("SELECT * FROM items WHERE is_active = 1 ORDER BY sort_order, item_name")->fetchAll();

        $prepData = [];
        foreach ($items as $item) {
            // Get yesterday's closing (last shift of yesterday = carry forward)
            $cfStmt = $this->db->prepare("
                SELECT closing_qty FROM shift_closings
                WHERE item_id = :id AND log_date < :date
                ORDER BY log_date DESC, FIELD(shift,'night','evening','morning') ASC
                LIMIT 1
            ");
            $cfStmt->execute([':id' => $item['id'], ':date' => $date]);
            $prevClosing = $cfStmt->fetchColumn();

            if ($prevClosing !== false) {
                // Use yesterday's closing stock as carry-forward
                $carryForward = (float)$prevClosing;
            } else {
                // No prior closing — seed opening from raw_inventory.current_qty
                $rawStmt = $this->db->prepare("
                    SELECT current_qty FROM raw_inventory
                    WHERE LOWER(item_name) = LOWER(:name)
                    LIMIT 1
                ");
                $rawStmt->execute([':name' => $item['item_name']]);
                $rawQty = $rawStmt->fetchColumn();
                $carryForward = $rawQty !== false ? (float)$rawQty : 0;
            }

            // Get existing prep data for today
            $existStmt = $this->db->prepare("
                SELECT * FROM daily_stocks WHERE item_id = :id AND log_date = :date
            ");
            $existStmt->execute([':id' => $item['id'], ':date' => $date]);
            $existing = $existStmt->fetch();

            $prepData[] = [
                'item_id'          => $item['id'],
                'item_name'        => $item['item_name'],
                'item_name_bn'     => $item['item_name_bn'] ?? $item['item_name'],
                'carry_forward'    => $existing ? (float)$existing['carry_forward_qty'] : $carryForward,
                'wastage'          => $existing ? (float)$existing['wastage_qty'] : 0,
                'fresh_processed'  => $existing ? (float)$existing['fresh_processed_qty'] : 0,
                'opening_qty'      => $existing ? (float)$existing['opening_qty'] : 0,
                'is_saved'         => (bool)$existing,
            ];
        }

        $rawItems = $this->db->query("SELECT item_name FROM raw_inventory ORDER BY item_name")->fetchAll(\PDO::FETCH_COLUMN);
        
        $conStmt = $this->db->prepare("SELECT * FROM daily_consumable_logs WHERE log_date = :date");
        $conStmt->execute([':date' => $date]);
        $todayConsumables = $conStmt->fetchAll();

        $this->view('inventory/daily_prep', [
            'pageTitle'        => __('morning_prep'),
            'activeNav'        => 'stock',
            'prepData'         => $prepData,
            'logDate'          => $date,
            'rawItems'         => $rawItems,
            'todayConsumables' => $todayConsumables,
        ]);
    }

    /**
     * Save Morning Prep (AJAX)
     * Route: ?url=inventory/saveDailyPrep
     */
    public function saveDailyPrep() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required']);
        }

        $data    = json_decode(file_get_contents('php://input'), true);
        $logDate = $data['log_date'] ?? date('Y-m-d');
        $items   = $data['items'] ?? [];

        if (empty($items)) {
            $this->json(['success' => false, 'error' => 'No items provided']);
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO daily_stocks (item_id, log_date, carry_forward_qty, wastage_qty, fresh_processed_qty, opening_qty)
                VALUES (:item_id, :log_date, :cf, :wastage, :fresh, :opening)
                ON DUPLICATE KEY UPDATE
                    carry_forward_qty = VALUES(carry_forward_qty),
                    wastage_qty = VALUES(wastage_qty),
                    fresh_processed_qty = VALUES(fresh_processed_qty),
                    opening_qty = VALUES(opening_qty)
            ");

            foreach ($items as $item) {
                $cf      = (float)($item['carry_forward'] ?? 0);
                $wastage = (float)($item['wastage'] ?? 0);
                $fresh   = (float)($item['fresh_processed'] ?? 0);
                $opening = ($cf - $wastage) + $fresh;

                $stmt->execute([
                    ':item_id'  => (int)$item['item_id'],
                    ':log_date' => $logDate,
                    ':cf'       => $cf,
                    ':wastage'  => $wastage,
                    ':fresh'    => $fresh,
                    ':opening'  => max(0, $opening),
                ]);
            }

            $this->db->commit();
            $this->json(['success' => true, 'message' => __('success')]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  DAY LEDGER (unified closing)
    // ══════════════════════════════════════════════════════════════

    /**
     * Display Day Ledger
     * Route: ?url=inventory/closeDayView
     */
    public function closeDayView() {
        $date  = $this->getBusinessDate();
        $shift = $this->getCurrentShift();

        // All active menu items for the "Add to Today" dropdown
        // LEFT JOIN raw_inventory to get current raw stock for auto-fill
        $menuItems = $this->db->query("
            SELECT i.id, i.item_name, i.item_name_bn, i.selling_price, i.cost_price,
                   COALESCE(r.current_qty, 0) AS raw_qty
            FROM items i
            LEFT JOIN raw_inventory r ON LOWER(r.item_name) = LOWER(i.item_name)
            WHERE i.is_active = 1
            ORDER BY i.sort_order, i.item_name
        ")->fetchAll();

        // Items already tracked today (from daily_stocks joined with shift_closings)
        $todayStmt = $this->db->prepare("
            SELECT ds.item_id, ds.opening_qty,
                   i.item_name, i.item_name_bn, i.selling_price, i.cost_price,
                   COALESCE(sc.closing_qty, '') AS closing_qty,
                   COALESCE(sc.complimentary_qty, 0) AS complimentary_qty,
                   COALESCE(sc.sold_qty, 0) AS sold_qty,
                   COALESCE(sc.total_sales_amount, 0) AS total_sales_amount
            FROM daily_stocks ds
            JOIN items i ON i.id = ds.item_id
            LEFT JOIN shift_closings sc ON sc.item_id = ds.item_id AND sc.log_date = ds.log_date AND sc.shift = :shift
            WHERE ds.log_date = :date
            ORDER BY i.sort_order, i.item_name
        ");
        $todayStmt->execute([':date' => $date, ':shift' => $shift]);
        $todayItems = $todayStmt->fetchAll();

        // Today's customer dues
        $duesStmt = $this->db->prepare("SELECT id, customer_name, phone, due_amount, status FROM customer_dues WHERE log_date = :date ORDER BY id DESC");
        $duesStmt->execute([':date' => $date]);
        $todayDues = $duesStmt->fetchAll();

        $this->view('inventory/close_day', [
            'pageTitle'     => __('day_ledger'),
            'activeNav'     => 'close',
            'menuItems'     => $menuItems,
            'todayItems'    => $todayItems,
            'todayDues'     => $todayDues,
            'businessDate'  => $date,
            'currentShift'  => $shift,
        ]);
    }

    /**
     * Upsert a day item (AJAX)
     * Route: ?url=inventory/upsertDayItem
     */
    public function upsertDayItem() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required']);
        }

        $data       = json_decode(file_get_contents('php://input'), true);
        $logDate    = $this->getBusinessDate();
        $shift      = $this->getCurrentShift();
        $itemId     = (int)($data['item_id'] ?? 0);
        $openingQty = (float)($data['opening_qty'] ?? 0);
        $closingQty = (float)($data['closing_qty'] ?? 0);
        $compQty    = (float)($data['complimentary_qty'] ?? 0);

        if ($itemId <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid item']);
        }

        try {
            $this->db->beginTransaction();

            // Check for an existing daily_stocks record (to know if this is an insert or update)
            $existStmt = $this->db->prepare("
                SELECT opening_qty FROM daily_stocks WHERE item_id = :item_id AND log_date = :date
            ");
            $existStmt->execute([':item_id' => $itemId, ':date' => $logDate]);
            $existingOpening = $existStmt->fetchColumn(); // false = no record

            // Upsert into daily_stocks (opening)
            $dsStmt = $this->db->prepare("
                INSERT INTO daily_stocks (item_id, log_date, opening_qty)
                VALUES (:item_id, :date, :opening)
                ON DUPLICATE KEY UPDATE
                    opening_qty = VALUES(opening_qty)
            ");
            $dsStmt->execute([
                ':item_id' => $itemId,
                ':date'    => $logDate,
                ':opening' => $openingQty,
            ]);

            // Deduct from raw_inventory (matched by item_name)
            // On first add: deduct full openingQty
            // On edit:      deduct only the difference (new - old)
            $itemNameStmt = $this->db->prepare("SELECT item_name FROM items WHERE id = :id");
            $itemNameStmt->execute([':id' => $itemId]);
            $itemName = $itemNameStmt->fetchColumn();

            if ($itemName) {
                if ($existingOpening === false) {
                    // New entry — deduct full opening qty
                    $deduct = $openingQty;
                } else {
                    // Update — deduct only the delta
                    $deduct = $openingQty - (float)$existingOpening;
                }

                if ($deduct != 0) {
                    $this->db->prepare("
                        UPDATE raw_inventory
                        SET current_qty = GREATEST(0, current_qty - :deduct)
                        WHERE LOWER(item_name) = LOWER(:name)
                    ")->execute([':deduct' => $deduct, ':name' => $itemName]);
                }
            }

            // Get selling price for revenue calc
            $priceStmt = $this->db->prepare("SELECT selling_price FROM items WHERE id = :id");
            $priceStmt->execute([':id' => $itemId]);
            $sellingPrice = (float)$priceStmt->fetchColumn();

            // Deductive sales
            $soldQty = max(0, $openingQty - $closingQty - $compQty);
            $salesAmount = $soldQty * $sellingPrice;

            // Upsert into shift_closings
            $scStmt = $this->db->prepare("
                INSERT INTO shift_closings
                    (item_id, log_date, shift, user_id, closing_qty, complimentary_qty, due_qty, sold_qty, total_sales_amount)
                VALUES
                    (:item_id, :date, :shift, :user_id, :closing, :comp, 0, :sold, :sales)
                ON DUPLICATE KEY UPDATE
                    closing_qty = VALUES(closing_qty),
                    complimentary_qty = VALUES(complimentary_qty),
                    sold_qty = VALUES(sold_qty),
                    total_sales_amount = VALUES(total_sales_amount),
                    user_id = VALUES(user_id)
            ");
            $scStmt->execute([
                ':item_id' => $itemId,
                ':date'    => $logDate,
                ':shift'   => $shift,
                ':user_id' => $_SESSION['user_id'] ?? null,
                ':closing' => $closingQty,
                ':comp'    => $compQty,
                ':sold'    => $soldQty,
                ':sales'   => $salesAmount,
            ]);

            $this->db->commit();
            $this->json([
                'success'   => true,
                'sold_qty'  => $soldQty,
                'sales'     => $salesAmount,
            ]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Remove item from today's ledger (AJAX)
     * Route: ?url=inventory/removeDayItem
     */
    public function removeDayItem() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required']);
        }

        $data    = json_decode(file_get_contents('php://input'), true);
        $logDate = $this->getBusinessDate();
        $itemId  = (int)($data['item_id'] ?? 0);

        if ($itemId <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid item']);
        }

        try {
            $this->db->beginTransaction();

            // Remove from daily_stocks
            $this->db->prepare("DELETE FROM daily_stocks WHERE item_id = :id AND log_date = :date")
                ->execute([':id' => $itemId, ':date' => $logDate]);

            // Remove from shift_closings (all shifts for this day)
            $this->db->prepare("DELETE FROM shift_closings WHERE item_id = :id AND log_date = :date")
                ->execute([':id' => $itemId, ':date' => $logDate]);

            $this->db->commit();
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Add a customer due (AJAX)
     * Route: ?url=inventory/addCustomerDue
     */
    public function addCustomerDue() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required']);
        }

        $data         = json_decode(file_get_contents('php://input'), true);
        $logDate      = $this->getBusinessDate();
        $shift        = $this->getCurrentShift();
        $customerName = trim($data['customer_name'] ?? '');
        $dueAmount    = (float)($data['due_amount'] ?? 0);
        $phone        = trim($data['phone'] ?? '');

        $itemId       = (int)($data['item_id'] ?? 0);
        $qty          = (float)($data['qty'] ?? 0);

        if (empty($customerName) || $dueAmount <= 0) {
            $this->json(['success' => false, 'error' => 'Name and amount required']);
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO customer_dues (customer_name, phone, due_amount, log_date, shift, item_id, qty)
                VALUES (:name, :phone, :amount, :date, :shift, :item_id, :qty)
            ");
            $stmt->execute([
                ':name'   => $customerName,
                ':phone'  => $phone ?: null,
                ':amount' => $dueAmount,
                ':date'   => $logDate,
                ':shift'  => $shift,
                ':item_id'=> $itemId > 0 ? $itemId : null,
                ':qty'    => $qty,
            ]);
            
            $newId = (int)$this->db->lastInsertId();
            $this->db->commit();

            $this->json([
                'success' => true,
                'id'      => $newId,
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  LEGACY: Save Shift Closing (kept for backwards compat)
    // ══════════════════════════════════════════════════════════════

    /**
     * Save Shift Closing (AJAX)
     * Route: ?url=inventory/saveShiftClose
     */
    public function saveShiftClose() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required']);
        }

        $data    = json_decode(file_get_contents('php://input'), true);
        $logDate = $this->getBusinessDate();
        $shift   = $this->getCurrentShift();
        $items   = $data['items'] ?? [];
        $dues    = $data['dues'] ?? [];

        if (empty($items)) {
            $this->json(['success' => false, 'error' => 'No items']);
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO shift_closings
                    (item_id, log_date, shift, user_id, closing_qty, complimentary_qty, due_qty, sold_qty, total_sales_amount)
                VALUES
                    (:item_id, :log_date, :shift, :user_id, :closing, :comp, :due, :sold, :sales)
                ON DUPLICATE KEY UPDATE
                    closing_qty = VALUES(closing_qty),
                    complimentary_qty = VALUES(complimentary_qty),
                    due_qty = VALUES(due_qty),
                    sold_qty = VALUES(sold_qty),
                    total_sales_amount = VALUES(total_sales_amount),
                    user_id = VALUES(user_id)
            ");

            $totalSold = 0;
            $totalRevenue = 0;

            foreach ($items as $item) {
                $effectiveOpening = (float)($item['effective_opening'] ?? 0);
                $closingQty       = (float)($item['closing_qty'] ?? 0);
                $compQty          = (float)($item['complimentary_qty'] ?? 0);
                $dueQty           = (float)($item['due_qty'] ?? 0);
                $sellingPrice     = (float)($item['selling_price'] ?? 0);

                // Deductive Sales: Opening - Closing - Complimentary - Due = Sold
                $soldQty = $effectiveOpening - $closingQty - $compQty - $dueQty;
                if ($soldQty < 0) $soldQty = 0;

                $salesAmount = $soldQty * $sellingPrice;
                $totalSold    += $soldQty;
                $totalRevenue += $salesAmount;

                $stmt->execute([
                    ':item_id'  => (int)$item['item_id'],
                    ':log_date' => $logDate,
                    ':shift'    => $shift,
                    ':user_id'  => $_SESSION['user_id'] ?? null,
                    ':closing'  => $closingQty,
                    ':comp'     => $compQty,
                    ':due'      => $dueQty,
                    ':sold'     => $soldQty,
                    ':sales'    => $salesAmount,
                ]);
            }

            // Save customer dues (baki)
            if (!empty($dues)) {
                $dueStmt = $this->db->prepare("
                    INSERT INTO customer_dues (customer_name, phone, due_amount, log_date, shift, item_id, qty, notes)
                    VALUES (:name, :phone, :amount, :log_date, :shift, :item_id, :qty, :notes)
                ");

                foreach ($dues as $due) {
                    $dueStmt->execute([
                        ':name'     => $due['customer_name'] ?? 'Unknown',
                        ':phone'    => $due['phone'] ?? null,
                        ':amount'   => (float)($due['amount'] ?? 0),
                        ':log_date' => $logDate,
                        ':shift'    => $shift,
                        ':item_id'  => $due['item_id'] ?? null,
                        ':qty'      => (float)($due['qty'] ?? 0),
                        ':notes'    => $due['notes'] ?? null,
                    ]);
                }
            }

            // Deduct daily spread costs
            $this->db->query("UPDATE expenses SET remaining_balance = GREATEST(0, remaining_balance - daily_amount) WHERE is_spread = 1 AND remaining_balance > 0 AND is_active = 1");

            $this->db->commit();

            // Calculate financial results
            $finance    = new FinanceController();
            $cashDrawer = $finance->calculateCashInDrawer($logDate);
            $netProfit  = $finance->calculateNetProfit($logDate);

            $this->json([
                'success'       => true,
                'total_sold'    => $totalSold,
                'total_revenue' => $totalRevenue,
                'cash_in_drawer'=> $cashDrawer,
                'net_profit'    => $netProfit,
                'shift'         => $shift,
            ]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    /**
     * Save Daily Consumable Log (AJAX)
     * Route: ?url=inventory/saveConsumableLog
     */
    public function saveConsumableLog() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required']);
        }

        $data    = json_decode(file_get_contents('php://input'), true);
        $itemName = trim($data['item_name'] ?? '');
        $usedQty  = (float)($data['used_qty'] ?? 0);
        $logDate  = $data['log_date'] ?? $this->getBusinessDate();

        if (empty($itemName) || $usedQty <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid data provided']);
        }

        try {
            $this->db->beginTransaction();

            // Fetch the current avg_unit_price from raw_inventory
            $costStmt = $this->db->prepare("SELECT avg_unit_price FROM raw_inventory WHERE LOWER(item_name) = LOWER(:name)");
            $costStmt->execute([':name' => $itemName]);
            $unitCost = (float)$costStmt->fetchColumn();

            // Ensure schema exists (silent migration)
            try {
                $this->db->exec("CREATE TABLE IF NOT EXISTS daily_consumable_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    item_name VARCHAR(100),
                    used_qty DECIMAL(10,2),
                    unit_cost DECIMAL(10,2),
                    log_date DATE,
                    UNIQUE KEY (item_name, log_date)
                )");
            } catch (\Exception $e) {}

            $stmt = $this->db->prepare("
                INSERT INTO daily_consumable_logs (item_name, used_qty, unit_cost, log_date) 
                VALUES (:name, :qty, :cost, :date) 
                ON DUPLICATE KEY UPDATE used_qty = VALUES(used_qty), unit_cost = VALUES(unit_cost)
            ");
            $stmt->execute([
                ':name' => $itemName,
                ':qty'  => $usedQty,
                ':cost' => $unitCost,
                ':date' => $logDate
            ]);

            $this->db->commit();
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
