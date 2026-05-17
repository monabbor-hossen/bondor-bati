<?php
namespace App\Models;

use Config\Database;
use PDO;

/**
 * InventoryModel
 * Handles the logic for retrieving and updating daily stock and raw inventory.
 */
class InventoryModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Get all raw inventory items
     *
     * @return array
     */
    public function getRawInventory() {
        $stmt = $this->db->query("SELECT * FROM raw_inventory ORDER BY item_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update raw inventory qty and average unit price
     *
     * @param int $itemId
     * @param float $currentQty
     * @param float $avgUnitPrice
     * @return bool
     */
    public function updateRawInventory($itemId, $currentQty, $avgUnitPrice) {
        $stmt = $this->db->prepare("UPDATE raw_inventory SET current_qty = :qty, avg_unit_price = :price WHERE id = :id");
        $stmt->bindParam(':qty', $currentQty);
        $stmt->bindParam(':price', $avgUnitPrice);
        $stmt->bindParam(':id', $itemId);
        return $stmt->execute();
    }

    /**
     * Retrieve daily stocks for a given date
     *
     * @param string $date (YYYY-MM-DD)
     * @return array
     */
    public function getDailyStocks($date) {
        $stmt = $this->db->prepare("SELECT * FROM daily_stocks WHERE log_date = :date");
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Save or update a daily stock record.
     * Uses ON DUPLICATE KEY UPDATE via the unique constraint (item_id, log_date).
     *
     * @param array $data Associative array matching table columns
     * @return bool
     */
    public function saveDailyStock($data) {
        $stmt = $this->db->prepare("
            INSERT INTO daily_stocks (
                item_id, log_date, carry_forward_qty, wastage_qty,
                complimentary_qty, fresh_processed_qty, opening_qty,
                closing_qty, sold_qty, total_sales_amount
            ) VALUES (
                :item_id, :log_date, :carry_forward_qty, :wastage_qty,
                :complimentary_qty, :fresh_processed_qty, :opening_qty,
                :closing_qty, :sold_qty, :total_sales_amount
            )
            ON DUPLICATE KEY UPDATE
                carry_forward_qty  = VALUES(carry_forward_qty),
                wastage_qty        = VALUES(wastage_qty),
                complimentary_qty  = VALUES(complimentary_qty),
                fresh_processed_qty= VALUES(fresh_processed_qty),
                opening_qty        = VALUES(opening_qty),
                closing_qty        = VALUES(closing_qty),
                sold_qty           = VALUES(sold_qty),
                total_sales_amount = VALUES(total_sales_amount)
        ");

        // PDO execute() array keys must NOT have a leading colon.
        // Strip leading colons from keys if they were accidentally passed with them.
        $clean = [];
        foreach ($data as $key => $value) {
            $clean[ltrim($key, ':')] = $value;
        }

        return $stmt->execute($clean);
    }
}
