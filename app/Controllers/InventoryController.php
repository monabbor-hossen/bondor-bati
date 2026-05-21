<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

/**
 * Inventory Controller — Morning Prep + 3-Shift Closing System
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
            $carryForward = (float)($cfStmt->fetchColumn() ?: 0);

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

        $this->view('inventory/daily_prep', [
            'pageTitle'   => __('morning_prep'),
            'activeNav'   => 'stock',
            'prepData'    => $prepData,
            'logDate'     => $date,
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
    //  SHIFT CLOSING (3-SHIFT SYSTEM)
    // ══════════════════════════════════════════════════════════════

    /**
     * Display Shift Closing form
     * Route: ?url=inventory/closeDayView
     */
    public function closeDayView() {
        $date  = $this->getBusinessDate();
        $shift = $this->getCurrentShift();

        $items = $this->db->query("SELECT * FROM items WHERE is_active = 1 ORDER BY sort_order, item_name")->fetchAll();

        $closeData = [];
        foreach ($items as $item) {
            // Get opening qty from daily_stocks
            $dsStmt = $this->db->prepare("SELECT opening_qty FROM daily_stocks WHERE item_id = :id AND log_date = :date");
            $dsStmt->execute([':id' => $item['id'], ':date' => $date]);
            $openingQty = (float)($dsStmt->fetchColumn() ?: 0);

            // Calculate what's already been closed in previous shifts today
            $prevStmt = $this->db->prepare("
                SELECT SUM(sold_qty) AS prev_sold, SUM(complimentary_qty) AS prev_comp, SUM(due_qty) AS prev_due
                FROM shift_closings
                WHERE item_id = :id AND log_date = :date AND shift != :shift
            ");
            $prevStmt->execute([':id' => $item['id'], ':date' => $date, ':shift' => $shift]);
            $prev = $prevStmt->fetch();

            // Effective opening for THIS shift = total opening - already sold/comp/due
            $prevSold = (float)($prev['prev_sold'] ?? 0);
            $prevComp = (float)($prev['prev_comp'] ?? 0);
            $prevDue  = (float)($prev['prev_due'] ?? 0);
            $effectiveOpening = $openingQty - $prevSold - $prevComp - $prevDue;

            // Check if this shift already has data
            $existStmt = $this->db->prepare("
                SELECT * FROM shift_closings
                WHERE item_id = :id AND log_date = :date AND shift = :shift
            ");
            $existStmt->execute([':id' => $item['id'], ':date' => $date, ':shift' => $shift]);
            $existing = $existStmt->fetch();

            $closeData[] = [
                'item_id'            => $item['id'],
                'item_name'          => $item['item_name'],
                'item_name_bn'       => $item['item_name_bn'] ?? $item['item_name'],
                'selling_price'      => (float)$item['selling_price'],
                'cost_price'         => (float)$item['cost_price'],
                'effective_opening'  => max(0, $effectiveOpening),
                'closing_qty'        => $existing ? (float)$existing['closing_qty'] : '',
                'complimentary_qty'  => $existing ? (float)$existing['complimentary_qty'] : 0,
                'due_qty'            => $existing ? (float)$existing['due_qty'] : 0,
                'is_saved'           => (bool)$existing,
            ];
        }

        // Get list of which shifts are already closed
        $closedShifts = $this->db->prepare("
            SELECT DISTINCT shift FROM shift_closings WHERE log_date = :date
        ");
        $closedShifts->execute([':date' => $date]);
        $closed = $closedShifts->fetchAll(PDO::FETCH_COLUMN);

        $this->view('inventory/close_day', [
            'pageTitle'     => __('shift_closing'),
            'activeNav'     => 'close',
            'closeData'     => $closeData,
            'logDate'       => $date,
            'currentShift'  => $shift,
            'closedShifts'  => $closed,
            'businessDate'  => $date,
        ]);
    }

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

    // Removed old detectCurrentShift helper as it's now in Core\Controller
}
