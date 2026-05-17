<?php

class DailyStock {
    // Database connection and table name
    private $conn;
    private $table_name = "daily_stocks";

    // Object properties
    public $id;
    public $item_id;
    public $log_date;
    public $carry_forward_qty;
    public $wastage_qty;
    public $complimentary_qty;
    public $fresh_processed_qty;
    // Note: opening_qty is GENERATED ALWAYS so we don't insert/update it directly
    public $closing_qty;
    // Note: sold_qty is GENERATED ALWAYS so we don't insert/update it directly
    public $total_sales_amount;

    // Constructor with $db as database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new daily stock record
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (item_id, log_date, carry_forward_qty, wastage_qty, complimentary_qty, fresh_processed_qty, closing_qty, total_sales_amount) 
                  VALUES (:item_id, :log_date, :carry_forward_qty, :wastage_qty, :complimentary_qty, :fresh_processed_qty, :closing_qty, :total_sales_amount)";

        $stmt = $this->conn->prepare($query);

        // Bind parameters securely
        $stmt->bindParam(":item_id", $this->item_id);
        $stmt->bindParam(":log_date", $this->log_date);
        $stmt->bindParam(":carry_forward_qty", $this->carry_forward_qty);
        $stmt->bindParam(":wastage_qty", $this->wastage_qty);
        $stmt->bindParam(":complimentary_qty", $this->complimentary_qty);
        $stmt->bindParam(":fresh_processed_qty", $this->fresh_processed_qty);
        $stmt->bindParam(":closing_qty", $this->closing_qty);
        $stmt->bindParam(":total_sales_amount", $this->total_sales_amount);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Read all daily stock records
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY log_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        // Since default fetch mode is PDO::FETCH_ASSOC, we can just return the array
        return $stmt->fetchAll();
    }

    // Read a single record by ID
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Update an existing daily stock record
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET 
                      item_id = :item_id, 
                      log_date = :log_date, 
                      carry_forward_qty = :carry_forward_qty, 
                      wastage_qty = :wastage_qty, 
                      complimentary_qty = :complimentary_qty, 
                      fresh_processed_qty = :fresh_processed_qty, 
                      closing_qty = :closing_qty, 
                      total_sales_amount = :total_sales_amount
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Bind parameters securely
        $stmt->bindParam(":item_id", $this->item_id);
        $stmt->bindParam(":log_date", $this->log_date);
        $stmt->bindParam(":carry_forward_qty", $this->carry_forward_qty);
        $stmt->bindParam(":wastage_qty", $this->wastage_qty);
        $stmt->bindParam(":complimentary_qty", $this->complimentary_qty);
        $stmt->bindParam(":fresh_processed_qty", $this->fresh_processed_qty);
        $stmt->bindParam(":closing_qty", $this->closing_qty);
        $stmt->bindParam(":total_sales_amount", $this->total_sales_amount);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete a daily stock record
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>
