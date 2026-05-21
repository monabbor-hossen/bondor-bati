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
        $this->db = (new Database())->getConnection();
    }

    /**
     * Display Bazaar Ledger form
     * Route: ?url=bazaar
     */
    public function index() {
        $date = $_GET['date'] ?? date('Y-m-d');

        $s = $this->db->query("SELECT setting_value FROM app_settings WHERE setting_key = 'default_bazaar_staff_id'");
        $defaultStaff = (int)($s->fetchColumn() ?: 0);

        // Get today's ledger if exists
        $stmt = $this->db->prepare("SELECT * FROM bazaar_ledgers WHERE log_date = :d");
        $stmt->execute([':d' => $date]);
        $ledger = $stmt->fetch();
        
        $assignedStaffId = $ledger ? (int)$ledger['assigned_staff_id'] : $defaultStaff;
        
        $staffList = $this->db->query("SELECT id, name FROM users WHERE role = 'staff' AND is_active = 1")->fetchAll();

        $bazaarItems = [];
        if ($ledger) {
            $itemStmt = $this->db->prepare("SELECT * FROM bazaar_items WHERE ledger_id = :id ORDER BY id");
            $itemStmt->execute([':id' => $ledger['id']]);
            $bazaarItems = $itemStmt->fetchAll();
        }

        // Get carry forward from yesterday
        $cfStmt = $this->db->prepare("
            SELECT carry_forward FROM bazaar_ledgers
            WHERE log_date < :d ORDER BY log_date DESC LIMIT 1
        ");
        $cfStmt->execute([':d' => $date]);
        $yesterdayCF = (float)($cfStmt->fetchColumn() ?: 0);

        $this->view('bazaar/index', [
            'pageTitle'     => __('bazaar'),
            'activeNav'     => 'bazaar',
            'logDate'       => $date,
            'ledger'        => $ledger,
            'bazaarItems'   => $bazaarItems,
            'yesterdayCF'   => $yesterdayCF,
            'assignedStaffId' => $assignedStaffId,
            'staffList'     => $staffList
        ]);
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
        $logDate     = $data['log_date'] ?? date('Y-m-d');
        $advanceCash = (float)($data['advance_cash'] ?? 0);
        $assignedStaffId = (int)($data['assigned_staff_id'] ?? 0);
        $items       = $data['items'] ?? [];
        $returnedCash = (float)($data['returned_cash'] ?? 0);

        if (($_SESSION['role'] ?? '') !== 'admin') {
            $existing = $this->db->prepare("SELECT advance_cash, assigned_staff_id FROM bazaar_ledgers WHERE log_date = :d");
            $existing->execute([':d' => $logDate]);
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

            // Upsert ledger
            $stmt = $this->db->prepare("
                INSERT INTO bazaar_ledgers (log_date, advance_cash, assigned_staff_id, total_spent, returned_cash, carry_forward, staff_due, status)
                VALUES (:log_date, :advance, :assigned, :spent, :returned, :cf, :due, 'closed')
                ON DUPLICATE KEY UPDATE
                    advance_cash = VALUES(advance_cash),
                    assigned_staff_id = VALUES(assigned_staff_id),
                    total_spent = VALUES(total_spent),
                    returned_cash = VALUES(returned_cash),
                    carry_forward = VALUES(carry_forward),
                    staff_due = VALUES(staff_due),
                    status = 'closed'
            ");
            $stmt->execute([
                ':log_date'  => $logDate,
                ':advance'   => $advanceCash,
                ':assigned'  => $assignedStaffId,
                ':spent'     => $totalSpent,
                ':returned'  => $returnedCash,
                ':cf'        => max(0, $carryForward),
                ':due'       => $staffDue,
            ]);

            // Get ledger ID
            $ledgerIdStmt = $this->db->prepare("SELECT id FROM bazaar_ledgers WHERE log_date = :d");
            $ledgerIdStmt->execute([':d' => $logDate]);
            $ledgerId = (int)$ledgerIdStmt->fetchColumn();

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
}
