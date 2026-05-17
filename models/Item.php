<?php

class Item {
    private $conn;
    private $table_name = "items";

    public $id;
    public $item_name;
    public $selling_price;
    public $cost_price;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new item
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (item_name, selling_price, cost_price) 
                  VALUES (:item_name, :selling_price, :cost_price)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":item_name", $this->item_name);
        $stmt->bindParam(":selling_price", $this->selling_price);
        $stmt->bindParam(":cost_price", $this->cost_price);

        return $stmt->execute();
    }

    // Read all items
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY item_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Read a single item by ID
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Update an item
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET item_name = :item_name, selling_price = :selling_price, cost_price = :cost_price
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":item_name", $this->item_name);
        $stmt->bindParam(":selling_price", $this->selling_price);
        $stmt->bindParam(":cost_price", $this->cost_price);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Delete an item
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }
}
?>
