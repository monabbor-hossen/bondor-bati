<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Item.php';

class InventoryController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // ─── RAW INVENTORY: Purchase from supplier ───────────────────────
    // Adds quantity to raw_inventory and recalculates weighted average price
    public function receiveFromSupplier($item_name, $qty, $unit_price, $supplier_id) {
        // Fetch current raw inventory for this item
        $query = "SELECT * FROM raw_inventory WHERE item_name = :item_name LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':item_name', $item_name);
        $stmt->execute();
        $existing = $stmt->fetch();

        if ($existing) {
            // Weighted average: ((old_qty * old_price) + (new_qty * new_price)) / (old_qty + new_qty)
            $old_qty   = $existing['current_qty'];
            $old_price = $existing['avg_unit_price'];
            $new_total_qty = $old_qty + $qty;
            $new_avg_price = (($old_qty * $old_price) + ($qty * $unit_price)) / $new_total_qty;

            $update = "UPDATE raw_inventory 
                       SET current_qty = :qty, avg_unit_price = :avg_price 
                       WHERE id = :id";
            $stmt_update = $this->db->prepare($update);
            $stmt_update->bindParam(':qty', $new_total_qty);
            $stmt_update->bindParam(':avg_price', $new_avg_price);
            $stmt_update->bindParam(':id', $existing['id']);
            $stmt_update->execute();
        } else {
            // First time receiving this item — insert new row
            $insert = "INSERT INTO raw_inventory (item_name, current_qty, avg_unit_price) 
                       VALUES (:item_name, :qty, :unit_price)";
            $stmt_insert = $this->db->prepare($insert);
            $stmt_insert->bindParam(':item_name', $item_name);
            $stmt_insert->bindParam(':qty', $qty);
            $stmt_insert->bindParam(':unit_price', $unit_price);
            $stmt_insert->execute();
        }

        // Update supplier due
        $total_cost = $qty * $unit_price;
        $update_supplier = "UPDATE suppliers SET total_due = total_due + :cost WHERE id = :supplier_id";
        $stmt_supplier = $this->db->prepare($update_supplier);
        $stmt_supplier->bindParam(':cost', $total_cost);
        $stmt_supplier->bindParam(':supplier_id', $supplier_id);
        $stmt_supplier->execute();

        return true;
    }

    // ─── RAW → DAILY: Transfer processed items to daily stock ────────
    // Deducts from raw_inventory and creates today's daily_stocks entry
    public function transferToDailyStock($item_id, $raw_item_name, $processed_qty, $carry_forward_qty = 0) {
        $date = date('Y-m-d');

        // 1. Deduct from raw inventory
        $deduct = "UPDATE raw_inventory 
                   SET current_qty = current_qty - :qty 
                   WHERE item_name = :item_name AND current_qty >= :qty";
        $stmt_deduct = $this->db->prepare($deduct);
        $stmt_deduct->bindParam(':qty', $processed_qty);
        $stmt_deduct->bindParam(':item_name', $raw_item_name);
        $stmt_deduct->execute();

        if ($stmt_deduct->rowCount() === 0) {
            return [
                'success' => false,
                'message' => 'Insufficient raw inventory for ' . $raw_item_name
            ];
        }

        // 2. Check if a daily_stocks row already exists for today
        $check = "SELECT id FROM daily_stocks WHERE item_id = :item_id AND log_date = :log_date LIMIT 1";
        $stmt_check = $this->db->prepare($check);
        $stmt_check->bindParam(':item_id', $item_id);
        $stmt_check->bindParam(':log_date', $date);
        $stmt_check->execute();
        $existing = $stmt_check->fetch();

        if ($existing) {
            // Add to existing fresh_processed_qty
            $update = "UPDATE daily_stocks 
                       SET fresh_processed_qty = fresh_processed_qty + :qty 
                       WHERE id = :id";
            $stmt_update = $this->db->prepare($update);
            $stmt_update->bindParam(':qty', $processed_qty);
            $stmt_update->bindParam(':id', $existing['id']);
            $stmt_update->execute();
        } else {
            // Create new daily stock entry
            $insert = "INSERT INTO daily_stocks 
                       (item_id, log_date, carry_forward_qty, fresh_processed_qty) 
                       VALUES (:item_id, :log_date, :carry_forward, :fresh_qty)";
            $stmt_insert = $this->db->prepare($insert);
            $stmt_insert->bindParam(':item_id', $item_id);
            $stmt_insert->bindParam(':log_date', $date);
            $stmt_insert->bindParam(':carry_forward', $carry_forward_qty);
            $stmt_insert->bindParam(':fresh_qty', $processed_qty);
            $stmt_insert->execute();
        }

        return [
            'success' => true,
            'message' => $processed_qty . ' units transferred to daily stock.'
        ];
    }

    // ─── CARRY FORWARD: Roll yesterday's closing into today's opening ─
    public function carryForwardFromYesterday($item_id) {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today     = date('Y-m-d');

        // Fetch yesterday's closing_qty
        $query = "SELECT closing_qty FROM daily_stocks 
                  WHERE item_id = :item_id AND log_date = :yesterday LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->bindParam(':yesterday', $yesterday);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            return [
                'success' => false,
                'message' => 'No closing data found for yesterday.'
            ];
        }

        $carry_qty = $row['closing_qty'];

        // Check if today's record already exists
        $check = "SELECT id FROM daily_stocks WHERE item_id = :item_id AND log_date = :today LIMIT 1";
        $stmt_check = $this->db->prepare($check);
        $stmt_check->bindParam(':item_id', $item_id);
        $stmt_check->bindParam(':today', $today);
        $stmt_check->execute();
        $existing = $stmt_check->fetch();

        if ($existing) {
            $update = "UPDATE daily_stocks SET carry_forward_qty = :carry WHERE id = :id";
            $stmt_update = $this->db->prepare($update);
            $stmt_update->bindParam(':carry', $carry_qty);
            $stmt_update->bindParam(':id', $existing['id']);
            $stmt_update->execute();
        } else {
            $insert = "INSERT INTO daily_stocks (item_id, log_date, carry_forward_qty) 
                       VALUES (:item_id, :today, :carry)";
            $stmt_insert = $this->db->prepare($insert);
            $stmt_insert->bindParam(':item_id', $item_id);
            $stmt_insert->bindParam(':today', $today);
            $stmt_insert->bindParam(':carry', $carry_qty);
            $stmt_insert->execute();
        }

        return [
            'success' => true,
            'message' => $carry_qty . ' units carried forward from yesterday.'
        ];
    }

    // ─── RAW → SHOP: Process raw material into sellable food ─────────
    /**
     * Convert raw inventory into today's sellable daily stock.
     *
     * Runs inside a strict database transaction:
     * 1. Fetch current_qty from raw_inventory by name.
     * 2. Validate sufficient stock; deduct qty_processed.
     * 3. Upsert daily_stocks for today (UPDATE if exists, INSERT if not).
     *
     * @param  string $raw_item_name  Name matching raw_inventory.item_name
     * @param  int    $shop_item_id   The items.id for the sellable product
     * @param  float  $qty_processed  How many units were processed
     * @return array                  Result with success status and details
     */
    public function processRawToShop($raw_item_name, $shop_item_id, $qty_processed) {
        // Input validation
        if (empty($raw_item_name)) {
            return ['success' => false, 'message' => 'Raw item name is required.'];
        }
        if (!is_numeric($shop_item_id) || $shop_item_id <= 0) {
            return ['success' => false, 'message' => 'Invalid shop item ID.'];
        }
        if (!is_numeric($qty_processed) || $qty_processed <= 0) {
            return ['success' => false, 'message' => 'Quantity must be greater than zero.'];
        }

        $today = date('Y-m-d');

        try {
            $this->db->beginTransaction();

            // ── Step 1: Fetch current_qty from raw_inventory ─────────
            $query_raw = "SELECT id, current_qty 
                          FROM raw_inventory 
                          WHERE item_name = :name 
                          LIMIT 1 
                          FOR UPDATE";
            $stmt_raw = $this->db->prepare($query_raw);
            $stmt_raw->bindParam(':name', $raw_item_name);
            $stmt_raw->execute();
            $raw = $stmt_raw->fetch();

            if (!$raw) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Raw item "' . $raw_item_name . '" not found in inventory.'
                ];
            }

            $current_qty = (float) $raw['current_qty'];

            // ── Step 2: Ensure sufficient stock & deduct ─────────────
            if ($current_qty < $qty_processed) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Insufficient raw stock. Available: ' . $current_qty . ', Requested: ' . $qty_processed
                ];
            }

            $new_qty = $current_qty - $qty_processed;
            $stmt_deduct = $this->db->prepare(
                "UPDATE raw_inventory SET current_qty = :new_qty WHERE id = :id"
            );
            $stmt_deduct->bindParam(':new_qty', $new_qty);
            $stmt_deduct->bindParam(':id', $raw['id'], PDO::PARAM_INT);
            $stmt_deduct->execute();

            if ($stmt_deduct->rowCount() === 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to deduct from raw inventory.'];
            }

            // ── Step 3: Upsert today's daily_stocks entry ────────────
            $stmt_check = $this->db->prepare(
                "SELECT id, fresh_processed_qty 
                 FROM daily_stocks 
                 WHERE item_id = :item_id AND log_date = :today 
                 LIMIT 1 
                 FOR UPDATE"
            );
            $stmt_check->bindParam(':item_id', $shop_item_id, PDO::PARAM_INT);
            $stmt_check->bindParam(':today', $today);
            $stmt_check->execute();
            $existing = $stmt_check->fetch();

            if ($existing) {
                // Row exists → UPDATE: add to fresh_processed_qty
                $stmt_update = $this->db->prepare(
                    "UPDATE daily_stocks 
                     SET fresh_processed_qty = fresh_processed_qty + :qty 
                     WHERE id = :id"
                );
                $stmt_update->bindParam(':qty', $qty_processed);
                $stmt_update->bindParam(':id', $existing['id'], PDO::PARAM_INT);
                $stmt_update->execute();

                if ($stmt_update->rowCount() === 0) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Failed to update daily stock.'];
                }
            } else {
                // No row → INSERT new entry for today
                $stmt_insert = $this->db->prepare(
                    "INSERT INTO daily_stocks (item_id, log_date, fresh_processed_qty) 
                     VALUES (:item_id, :today, :qty)"
                );
                $stmt_insert->bindParam(':item_id', $shop_item_id, PDO::PARAM_INT);
                $stmt_insert->bindParam(':today', $today);
                $stmt_insert->bindParam(':qty', $qty_processed);
                $stmt_insert->execute();

                if ($stmt_insert->rowCount() === 0) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Failed to insert daily stock.'];
                }
            }

            // ── Commit ───────────────────────────────────────────────
            $this->db->commit();

            return [
                'success'          => true,
                'message'          => $qty_processed . ' units of "' . $raw_item_name . '" processed into shop stock.',
                'raw_remaining'    => $new_qty,
                'qty_processed'    => $qty_processed,
                'daily_stock_mode' => $existing ? 'updated' : 'inserted'
            ];

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("InventoryController::processRawToShop Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Transaction failed. All changes have been rolled back.'
            ];
        }
    }

    // ─── VIEW: Get current raw inventory snapshot ────────────────────
    public function getRawInventory() {
        $query = "SELECT * FROM raw_inventory ORDER BY item_name ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ─── VIEW: Get today's daily stock snapshot ──────────────────────
    public function getTodaysDailyStock() {
        $today = date('Y-m-d');
        $query = "SELECT ds.*, i.item_name, i.selling_price, i.cost_price 
                  FROM daily_stocks ds 
                  JOIN items i ON ds.item_id = i.id 
                  WHERE ds.log_date = :today 
                  ORDER BY i.item_name ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
