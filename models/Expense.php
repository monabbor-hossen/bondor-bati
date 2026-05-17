<?php

class Expense {
    // Database connection and table name
    private $conn;
    private $table_name = "expenses";

    // Object properties
    public $id;
    public $category;
    public $name;
    public $total_amount;
    public $is_spread;
    public $daily_amount;
    public $remaining_balance;
    public $expense_date;

    // Constructor with $db as database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new expense record
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (category, name, total_amount, is_spread, daily_amount, remaining_balance, expense_date) 
                  VALUES (:category, :name, :total_amount, :is_spread, :daily_amount, :remaining_balance, :expense_date)";

        $stmt = $this->conn->prepare($query);

        // Bind parameters securely
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":total_amount", $this->total_amount);
        $stmt->bindParam(":is_spread", $this->is_spread, PDO::PARAM_INT);
        $stmt->bindParam(":daily_amount", $this->daily_amount);
        $stmt->bindParam(":remaining_balance", $this->remaining_balance);
        $stmt->bindParam(":expense_date", $this->expense_date);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Read all expense records
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY expense_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
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

    // Update an existing expense record
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET 
                      category = :category, 
                      name = :name, 
                      total_amount = :total_amount, 
                      is_spread = :is_spread, 
                      daily_amount = :daily_amount, 
                      remaining_balance = :remaining_balance, 
                      expense_date = :expense_date
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Bind parameters securely
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":total_amount", $this->total_amount);
        $stmt->bindParam(":is_spread", $this->is_spread, PDO::PARAM_INT);
        $stmt->bindParam(":daily_amount", $this->daily_amount);
        $stmt->bindParam(":remaining_balance", $this->remaining_balance);
        $stmt->bindParam(":expense_date", $this->expense_date);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete an expense record
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
