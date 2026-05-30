<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

/**
 * Bazaar Controller — Requisition Ledger (Advance / Spend / Balance)
 */
class BazaarController extends Controller {
public function __construct() {
        parent::__construct();
        $this->db = (new Database())->getConnection();
    }

    /**
     * Display Bazaar Ledger form
     * Route: ?url=bazaar
     */
    public function index() {
        $date = $_GET['date'] ?? date('Y-m-d');

        try { $this->db->exec("ALTER TABLE bazaar_ledgers DROP INDEX log_date"); } catch (\Exception $e) {}

        $s = $this->db->query("SELECT setting_value FROM app_settings WHERE setting_key = 'default_bazaar_staff_id'");
        $defaultStaff = (int)($s->fetchColumn() ?: 0);

        // Fetch ALL ledgers for today
        $stmt = $this->db->prepare("SELECT * FROM bazaar_ledgers WHERE log_date = :d ORDER BY id ASC");
        $stmt->execute([':d' => $date]);
        $ledgers = $stmt->fetchAll();
        
        if (empty($ledgers)) {
            $ins = $this->db->prepare("INSERT INTO bazaar_ledgers (log_date, status) VALUES (:d, 'open')");
            $ins->execute([':d' => $date]);
            $stmt->execute([':d' => $date]);
            $ledgers = $stmt->fetchAll();
        }

        $activeLedgerId = (int)($_GET['ledger_id'] ?? $ledgers[0]['id']);
        
        $ledger = null;
        foreach ($ledgers as $l) {
            if ($l['id'] == $activeLedgerId) {
                $ledger = $l;
                break;
            }
        }
        if (!$ledger) {
            $ledger = $ledgers[0];
            $activeLedgerId = $ledger['id'];
        }

        $assignedStaffId = $ledger ? (int)$ledger['assigned_staff_id'] : $defaultStaff;

        $staffList = $this->db->query("SELECT id, name, role FROM users WHERE is_active = 1 ORDER BY role DESC, name ASC")->fetchAll();

        $bazaarItems = [];
        if ($ledger) {
            $itemStmt = $this->db->prepare("SELECT * FROM bazaar_items WHERE ledger_id = :id ORDER BY id");
            $itemStmt->execute([':id' => $ledger['id']]);
            $bazaarItems = $itemStmt->fetchAll();
        }

        // Calculate past carry forward up to this exact ledger
        $cf = $this->db->prepare("SELECT COALESCE(SUM(advance_cash) - SUM(total_spent) - SUM(returned_cash), 0) FROM bazaar_ledgers WHERE id < :activeId");
        $cf->execute([':activeId' => $activeLedgerId]);
        $pastCarryForward = (float)$cf->fetchColumn();

        // Fetch raw inventory names for datalist auto-suggest
        $inventoryNames = $this->db->query("SELECT item_name FROM raw_inventory ORDER BY item_name")->fetchAll(PDO::FETCH_COLUMN);

        $this->view('bazaar/index', [
            'pageTitle'        => __('bazaar'),
            'activeNav'        => 'bazaar',
            'logDate'          => $date,
            'ledgers'          => $ledgers,
            'activeLedgerId'   => $activeLedgerId,
            'ledger'           => $ledger,
            'bazaarItems'      => $bazaarItems,
            'pastCarryForward' => max(0, $pastCarryForward),
            'assignedStaffId'  => $assignedStaffId,
            'staffList'        => $staffList,
            'inventoryNames'   => $inventoryNames
        ]);
    }

    public function createNewLedger() {
        $this->requireAdmin();
        $date = date('Y-m-d');
        try {
            $ins = $this->db->prepare("INSERT INTO bazaar_ledgers (log_date, status) VALUES (:d, 'open')");
            $ins->execute([':d' => $date]);
            $newId = $this->db->lastInsertId();
            $this->json(['success' => true, 'ledger_id' => $newId]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Save Bazaar Ledger (AJAX)
     * Route: ?url=bazaar/save
     */
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required']);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $ledgerId    = (int)($data['ledger_id'] ?? 0);
        $logDate     = $data['log_date'] ?? date('Y-m-d');
        $advanceCash = (float)($data['advance_cash'] ?? 0);
        $assignedStaffId = (int)($data['assigned_staff_id'] ?? 0);
        $items       = $data['items'] ?? [];
        $returnedCash = (float)($data['returned_cash'] ?? 0);
        
        if ($ledgerId <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid ledger ID']);
        }

        if (($_SESSION['role'] ?? '') !== 'admin') {
            $existing = $this->db->prepare("SELECT advance_cash, assigned_staff_id FROM bazaar_ledgers WHERE id = :id");
            $existing->execute([':id' => $ledgerId]);
            $row = $existing->fetch();
            $advanceCash = $row ? (float)$row['advance_cash'] : 0;
            $assignedStaffId = $row ? (int)$row['assigned_staff_id'] : $assignedStaffId;
        }

        try {
            $this->db->beginTransaction();

            // Calculate totals
            $totalSpent = 0;
            foreach ($items as $item) {
                $totalSpent += (float)($item['total_price'] ?? 0);
            }

            // Balance logic:
            // If spent > advance → staff_due (staff is owed money)
            // If advance > spent → remaining can be returned or carried forward
            $balance      = $advanceCash - $totalSpent;
            $staffDue     = $balance < 0 ? abs($balance) : 0;
            $carryForward = $balance > 0 ? ($balance - $returnedCash) : 0;

            // Update ledger by ID
            $stmt = $this->db->prepare("
                UPDATE bazaar_ledgers SET
                    advance_cash = :advance,
                    assigned_staff_id = :assigned,
                    total_spent = :spent,
                    returned_cash = :returned,
                    carry_forward = :cf,
                    staff_due = :due,
                    status = 'closed'
                WHERE id = :id
            ");
            $stmt->execute([
                ':advance'   => $advanceCash,
                ':assigned'  => $assignedStaffId,
                ':spent'     => $totalSpent,
                ':returned'  => $returnedCash,
                ':cf'        => max(0, $carryForward),
                ':due'       => $staffDue,
                ':id'        => $ledgerId
            ]);

            // Revert old items from raw inventory to prevent double-counting on multiple saves
            $oldItemsStmt = $this->db->prepare("SELECT item_name, bought_qty, total_price FROM bazaar_items WHERE ledger_id = :id");
            $oldItemsStmt->execute([':id' => $ledgerId]);
            $oldItems = $oldItemsStmt->fetchAll();

            foreach ($oldItems as $old) {
                if ((float)$old['bought_qty'] > 0) {
                    $rawStmt = $this->db->prepare("SELECT current_qty, avg_unit_price FROM raw_inventory WHERE item_name = :name LIMIT 1");
                    $rawStmt->execute([':name' => $old['item_name']]);
                    $raw = $rawStmt->fetch();

                    if ($raw) {
                        $currQty = (float)$raw['current_qty'];
                        $avgPrice = (float)$raw['avg_unit_price'];
                        
                        $revertedQty = $currQty - (float)$old['bought_qty'];
                        if ($revertedQty > 0) {
                            $revertedTotalVal = ($currQty * $avgPrice) - (float)$old['total_price'];
                            $newAvgPrice = max(0, $revertedTotalVal) / $revertedQty;
                        } else {
                            $revertedQty = 0;
                            $newAvgPrice = 0;
                        }
                        
                        $updRaw = $this->db->prepare("UPDATE raw_inventory SET current_qty = :qty, avg_unit_price = :avg WHERE item_name = :name");
                        $updRaw->execute([
                            ':qty' => $revertedQty,
                            ':avg' => round($newAvgPrice, 2),
                            ':name' => $old['item_name']
                        ]);
                    }
                }
            }

            // Delete old items and re-insert
            $this->db->prepare("DELETE FROM bazaar_items WHERE ledger_id = :id")->execute([':id' => $ledgerId]);

            $itemStmt = $this->db->prepare("
                INSERT INTO bazaar_items (ledger_id, item_name, bought_qty, unit, unit_price, total_price)
                VALUES (:lid, :name, :qty, :unit, :up, :tp)
            ");

            foreach ($items as $item) {
                if (empty($item['item_name'])) continue;
                $boughtQty = (float)($item['bought_qty'] ?? 0);
                $unitPrice = (float)($item['unit_price'] ?? 0);
                $totalPrice = (float)($item['total_price'] ?? 0);
                $unit = $item['unit'] ?? 'kg';

                $itemStmt->execute([
                    ':lid'  => $ledgerId,
                    ':name' => $item['item_name'],
                    ':qty'  => $boughtQty,
                    ':unit' => $unit,
                    ':up'   => $unitPrice,
                    ':tp'   => $totalPrice,
                ]);

                if ($boughtQty > 0) {
                    $rawStmt = $this->db->prepare("SELECT current_qty, avg_unit_price FROM raw_inventory WHERE item_name = :name LIMIT 1");
                    $rawStmt->execute([':name' => $item['item_name']]);
                    $raw = $rawStmt->fetch();

                    if ($raw) {
                        $currQty = (float)$raw['current_qty'];
                        $avgPrice = (float)$raw['avg_unit_price'];
                        
                        $newQty = $currQty + $boughtQty;
                        $newAvgPrice = (($currQty * $avgPrice) + $totalPrice) / $newQty;
                        
                        $updRaw = $this->db->prepare("UPDATE raw_inventory SET current_qty = :qty, avg_unit_price = :avg WHERE item_name = :name");
                        $updRaw->execute([
                            ':qty' => $newQty,
                            ':avg' => round($newAvgPrice, 2),
                            ':name' => $item['item_name']
                        ]);
                    } else {
                        $insRaw = $this->db->prepare("INSERT INTO raw_inventory (item_name, item_name_bn, unit, current_qty, avg_unit_price) VALUES (:name, :name_bn, :unit, :qty, :avg)");
                        $insRaw->execute([
                            ':name' => $item['item_name'],
                            ':name_bn' => $item['item_name'],
                            ':unit' => $unit,
                            ':qty' => $boughtQty,
                            ':avg' => $unitPrice
                        ]);
                    }
                }
            }

            $this->db->commit();
            $this->json([
                'success'       => true,
                'total_spent'   => $totalSpent,
                'balance'       => $balance,
                'staff_due'     => $staffDue,
                'carry_forward' => max(0, $carryForward),
            ]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function setDefaultStaff() {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->json(['success' => false, 'error' => 'Unauthorized']);
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $staffId = (int)($data['staff_id'] ?? 0);
        
        if ($staffId > 0) {
            $stmt = $this->db->prepare("
                INSERT INTO app_settings (setting_key, setting_value) 
                VALUES ('default_bazaar_staff_id', :val)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->execute([':val' => (string)$staffId]);
            $this->json(['success' => true]);
        }
        $this->json(['success' => false, 'error' => 'Invalid staff ID']);
    }

    /**
     * Bazaar History Page — date-based purchase list + selling product list
     * Route: ?url=bazaar/history
     */
    public function history() {
        $this->requireAdmin();

        // Date filter — defaults to today
        $date = $_GET['date'] ?? date('Y-m-d');

        // --- Bazaar Purchase Lists for the selected date ---
        $ledgerStmt = $this->db->prepare("
            SELECT bl.*, u.name AS staff_name
            FROM bazaar_ledgers bl
            LEFT JOIN users u ON u.id = bl.assigned_staff_id
            WHERE bl.log_date = :d
            ORDER BY bl.id ASC
        ");
        $ledgerStmt->execute([':d' => $date]);
        $ledgers = $ledgerStmt->fetchAll();

        // Fetch items for each ledger
        foreach ($ledgers as &$l) {
            $itemStmt = $this->db->prepare("SELECT * FROM bazaar_items WHERE ledger_id = :id ORDER BY id");
            $itemStmt->execute([':id' => $l['id']]);
            $l['items'] = $itemStmt->fetchAll();
        }
        unset($l);

        // --- Selling Product List for the selected date ---
        // Aggregate sold_qty, sales & wastage across all shifts per item
        $salesStmt = $this->db->prepare("
            SELECT i.item_name, i.item_name_bn,
                   SUM(sc.sold_qty) AS total_sold,
                   SUM(sc.complimentary_qty) AS total_comp,
                   SUM(sc.total_sales_amount) AS total_sales,
                   i.selling_price,
                   COALESCE(ds.wastage_qty, 0) AS wastage_qty,
                   GROUP_CONCAT(CONCAT(sc.shift,':',sc.sold_qty) ORDER BY sc.shift SEPARATOR ', ') AS shift_breakdown
            FROM shift_closings sc
            JOIN items i ON i.id = sc.item_id
            LEFT JOIN daily_stocks ds ON ds.item_id = sc.item_id AND ds.log_date = :d2
            WHERE sc.log_date = :d
            GROUP BY sc.item_id, i.item_name, i.item_name_bn, i.selling_price, ds.wastage_qty
            ORDER BY total_sold DESC
        ");
        $salesStmt->execute([':d' => $date, ':d2' => $date]);
        $salesRows = $salesStmt->fetchAll();

        // Available dates for the date picker (last 60 days that have bazaar or sales data)
        $datesStmt = $this->db->query("
            SELECT DISTINCT log_date FROM bazaar_ledgers
            UNION
            SELECT DISTINCT log_date FROM shift_closings
            ORDER BY log_date DESC
            LIMIT 60
        ");
        $availableDates = $datesStmt->fetchAll(PDO::FETCH_COLUMN);

        $this->view('bazaar/history', [
            'pageTitle'      => 'Bazaar & Sales History',
            'activeNav'      => 'settings',
            'selectedDate'   => $date,
            'ledgers'        => $ledgers,
            'salesRows'      => $salesRows,
            'availableDates' => $availableDates,
        ]);
    }

    public function deleteLedger() {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->json(['success' => false, 'error' => 'Unauthorized']);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $ledgerId = (int)($data['ledger_id'] ?? 0);
        
        if ($ledgerId <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid ledger ID']);
        }
        
        try {
            $this->db->beginTransaction();

            // 1. Revert inventory
            $oldItemsStmt = $this->db->prepare("SELECT item_name, bought_qty, total_price FROM bazaar_items WHERE ledger_id = :id");
            $oldItemsStmt->execute([':id' => $ledgerId]);
            $oldItems = $oldItemsStmt->fetchAll();

            foreach ($oldItems as $old) {
                if ((float)$old['bought_qty'] > 0) {
                    $rawStmt = $this->db->prepare("SELECT current_qty, avg_unit_price FROM raw_inventory WHERE item_name = :name LIMIT 1");
                    $rawStmt->execute([':name' => $old['item_name']]);
                    $raw = $rawStmt->fetch();

                    if ($raw) {
                        $currQty = (float)$raw['current_qty'];
                        $avgPrice = (float)$raw['avg_unit_price'];
                        
                        $revertedQty = $currQty - (float)$old['bought_qty'];
                        if ($revertedQty > 0) {
                            $revertedTotalVal = ($currQty * $avgPrice) - (float)$old['total_price'];
                            $newAvgPrice = max(0, $revertedTotalVal) / $revertedQty;
                        } else {
                            $revertedQty = 0;
                            $newAvgPrice = 0;
                        }
                        
                        $updRaw = $this->db->prepare("UPDATE raw_inventory SET current_qty = :qty, avg_unit_price = :avg WHERE item_name = :name");
                        $updRaw->execute([
                            ':qty' => $revertedQty,
                            ':avg' => round($newAvgPrice, 2),
                            ':name' => $old['item_name']
                        ]);
                    }
                }
            }

            // 2. Delete items
            $this->db->prepare("DELETE FROM bazaar_items WHERE ledger_id = :id")->execute([':id' => $ledgerId]);
            
            // 3. Delete ledger
            $this->db->prepare("DELETE FROM bazaar_ledgers WHERE id = :id")->execute([':id' => $ledgerId]);

            $this->db->commit();
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
