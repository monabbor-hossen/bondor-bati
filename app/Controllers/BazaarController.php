<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

/**
 * Bazaar Controller — Requisition Ledger (Advance / Spend / Balance)
 */
class BazaarController extends Controller {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Display Bazaar Ledger form
     * Route: ?url=bazaar
     */
    public function index() {
        $date = $_GET['date'] ?? date('Y-m-d');

        // Get today's ledger if exists
        $stmt = $this->db->prepare("SELECT * FROM bazaar_ledgers WHERE log_date = :d");
        $stmt->execute([':d' => $date]);
        $ledger = $stmt->fetch();

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
        $items       = $data['items'] ?? [];
        $returnedCash = (float)($data['returned_cash'] ?? 0);

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
                INSERT INTO bazaar_ledgers (log_date, advance_cash, total_spent, returned_cash, carry_forward, staff_due, status)
                VALUES (:log_date, :advance, :spent, :returned, :cf, :due, 'closed')
                ON DUPLICATE KEY UPDATE
                    advance_cash = VALUES(advance_cash),
                    total_spent = VALUES(total_spent),
                    returned_cash = VALUES(returned_cash),
                    carry_forward = VALUES(carry_forward),
                    staff_due = VALUES(staff_due),
                    status = 'closed'
            ");
            $stmt->execute([
                ':log_date'  => $logDate,
                ':advance'   => $advanceCash,
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
                $itemStmt->execute([
                    ':lid'  => $ledgerId,
                    ':name' => $item['item_name'],
                    ':qty'  => (float)($item['bought_qty'] ?? 0),
                    ':unit' => $item['unit'] ?? 'kg',
                    ':up'   => (float)($item['unit_price'] ?? 0),
                    ':tp'   => (float)($item['total_price'] ?? 0),
                ]);
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
}
