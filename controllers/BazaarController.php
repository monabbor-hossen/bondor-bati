<?php
/**
 * BazaarController
 * 
 * Handles the daily bazaar (market purchase) workflow.
 * Each day a ledger is created, and individual items are logged against it.
 * Items bought on credit from a named supplier update that supplier's due.
 */

require_once __DIR__ . '/../config/database.php';

class BazaarController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Create a new daily bazaar ledger.
     * Call this once at the start of the day before adding items.
     *
     * @param  string      $date   The ledger date (Y-m-d).
     * @param  string|null $notes  Optional note for the day.
     * @return array               Result with the new ledger_id.
     */
    public function createLedger($date, $notes = null) {
        $query = "INSERT INTO bazaar_ledgers (ledger_date, notes) VALUES (:date, :notes)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':notes', $notes);

        if ($stmt->execute()) {
            return [
                'success'   => true,
                'ledger_id' => (int) $this->db->lastInsertId(),
                'message'   => 'Bazaar ledger created for ' . $date . '.'
            ];
        }

        return ['success' => false, 'message' => 'Failed to create ledger.'];
    }

    /**
     * Submit the full daily bazaar purchase.
     *
     * Runs inside a single database transaction so that either ALL items
     * are recorded (and supplier dues updated) or NONE are.
     *
     * @param  int   $ledger_id    The bazaar_ledgers.id to attach items to.
     * @param  array $items_array  Array of items, each with keys:
     *                             - item_name   (string, required)
     *                             - bought_qty  (float,  required)
     *                             - total_price (float,  required)
     *                             - supplier_id (int|null, optional)
     * @return array               Result with grand total and item count.
     */
    public function submitDailyBazaar($ledger_id, $items_array) {
        // ── Validate inputs ──────────────────────────────────────────
        if (empty($ledger_id) || !is_numeric($ledger_id)) {
            return ['success' => false, 'message' => 'Invalid ledger ID.'];
        }

        if (empty($items_array) || !is_array($items_array)) {
            return ['success' => false, 'message' => 'Items array is empty or invalid.'];
        }

        // Verify ledger exists
        $check = $this->db->prepare("SELECT id FROM bazaar_ledgers WHERE id = :id LIMIT 1");
        $check->bindParam(':id', $ledger_id, PDO::PARAM_INT);
        $check->execute();
        if (!$check->fetch()) {
            return ['success' => false, 'message' => 'Ledger not found. Create one first.'];
        }

        // ── Begin Transaction ────────────────────────────────────────
        try {
            $this->db->beginTransaction();

            $grand_total = 0;
            $items_inserted = 0;

            // Prepare reusable statements
            $stmt_insert = $this->db->prepare(
                "INSERT INTO bazaar_items (ledger_id, item_name, bought_qty, total_price, supplier_id)
                 VALUES (:ledger_id, :item_name, :bought_qty, :total_price, :supplier_id)"
            );

            $stmt_supplier = $this->db->prepare(
                "UPDATE suppliers SET total_due = total_due + :amount WHERE id = :supplier_id"
            );

            // ── Step 1 & 2: Loop and insert each item ────────────────
            foreach ($items_array as $item) {
                $item_name   = trim($item['item_name'] ?? '');
                $bought_qty  = (float) ($item['bought_qty']  ?? 0);
                $total_price = (float) ($item['total_price'] ?? 0);
                $supplier_id = isset($item['supplier_id']) && $item['supplier_id'] !== ''
                               ? (int) $item['supplier_id']
                               : null;

                // Skip empty rows
                if (empty($item_name) || $total_price <= 0) {
                    continue;
                }

                // Insert into bazaar_items
                $stmt_insert->bindParam(':ledger_id', $ledger_id, PDO::PARAM_INT);
                $stmt_insert->bindParam(':item_name', $item_name);
                $stmt_insert->bindParam(':bought_qty', $bought_qty);
                $stmt_insert->bindParam(':total_price', $total_price);
                $stmt_insert->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
                $stmt_insert->execute();

                // ── Step 3: Supplier credit logic ────────────────────
                if ($supplier_id !== null) {
                    // Bought on credit → add to supplier's total_due
                    $stmt_supplier->bindParam(':amount', $total_price);
                    $stmt_supplier->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
                    $stmt_supplier->execute();
                }
                // If supplier_id is NULL → cash purchase, do nothing

                $grand_total += $total_price;
                $items_inserted++;
            }

            // ── Step 4: Update the ledger's grand total ──────────────
            $stmt_ledger = $this->db->prepare(
                "UPDATE bazaar_ledgers SET total_spent = :total WHERE id = :id"
            );
            $stmt_ledger->bindParam(':total', $grand_total);
            $stmt_ledger->bindParam(':id', $ledger_id, PDO::PARAM_INT);
            $stmt_ledger->execute();

            // ── Commit ───────────────────────────────────────────────
            $this->db->commit();

            return [
                'success'        => true,
                'message'        => $items_inserted . ' item(s) recorded. Grand total: ৳' . number_format($grand_total, 2),
                'items_inserted' => $items_inserted,
                'grand_total'    => $grand_total
            ];

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("BazaarController::submitDailyBazaar Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Transaction failed. No data was saved.'
            ];
        }
    }

    /**
     * Get all items for a specific bazaar ledger.
     *
     * @param  int   $ledger_id
     * @return array
     */
    public function getLedgerItems($ledger_id) {
        $query = "SELECT bi.*, s.name as supplier_name
                  FROM bazaar_items bi
                  LEFT JOIN suppliers s ON bi.supplier_id = s.id
                  WHERE bi.ledger_id = :ledger_id
                  ORDER BY bi.id ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':ledger_id', $ledger_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get today's bazaar ledger (or null if none exists yet).
     *
     * @return array|false
     */
    public function getTodaysLedger() {
        $today = date('Y-m-d');
        $query = "SELECT * FROM bazaar_ledgers WHERE ledger_date = :today LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>
