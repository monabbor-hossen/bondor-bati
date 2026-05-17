<?php
namespace App\Models;

use Config\Database;
use PDO;

/**
 * LedgerModel
 * Manages financial ledgers, bazaar purchases, and spread expenses.
 */
class LedgerModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Create a new bazaar ledger entry
     *
     * @param string $logDate (YYYY-MM-DD)
     * @param float $advanceCash
     * @param float $totalSpent
     * @return int Inserted ID
     */
    public function createBazaarLedger($logDate, $advanceCash, $totalSpent) {
        $stmt = $this->db->prepare("INSERT INTO bazaar_ledgers (log_date, advance_cash, total_spent) VALUES (:log_date, :advance_cash, :total_spent)");
        $stmt->bindParam(':log_date', $logDate);
        $stmt->bindParam(':advance_cash', $advanceCash);
        $stmt->bindParam(':total_spent', $totalSpent);
        $stmt->execute();
        
        return $this->db->lastInsertId();
    }

    /**
     * Add a bazaar item to a ledger
     *
     * @param int $ledgerId
     * @param string $itemName
     * @param float $boughtQty
     * @param float $totalPrice
     * @param int|null $supplierId
     * @return bool
     */
    public function addBazaarItem($ledgerId, $itemName, $boughtQty, $totalPrice, $supplierId = null) {
        $stmt = $this->db->prepare("
            INSERT INTO bazaar_items (ledger_id, item_name, bought_qty, total_price, supplier_id) 
            VALUES (:ledger_id, :item_name, :bought_qty, :total_price, :supplier_id)
        ");
        $stmt->bindParam(':ledger_id', $ledgerId);
        $stmt->bindParam(':item_name', $itemName);
        $stmt->bindParam(':bought_qty', $boughtQty);
        $stmt->bindParam(':total_price', $totalPrice);
        
        // Handle null supplier ID appropriately for PDO
        if ($supplierId === null) {
            $stmt->bindValue(':supplier_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':supplier_id', $supplierId);
        }
        
        return $stmt->execute();
    }

    /**
     * Add an expense (handles daily and spread logic)
     *
     * @param array $data Associative array of expense data
     * @return bool
     */
    public function addExpense($data) {
        $stmt = $this->db->prepare("
            INSERT INTO expenses (category, name, total_amount, is_spread, daily_amount, remaining_balance, expense_date) 
            VALUES (:category, :name, :total_amount, :is_spread, :daily_amount, :remaining_balance, :expense_date)
        ");
        return $stmt->execute($data);
    }
}
