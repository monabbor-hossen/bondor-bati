<?php

class AdvanceOrder {
    private $conn;
    private $table_name = "calendar_events"; // Maps to forecasting & advance events

    public $id;
    public $event_date;
    public $event_name;
    public $impact_multiplier;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new advance order / calendar event
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (event_date, event_name, impact_multiplier) 
                  VALUES (:event_date, :event_name, :impact_multiplier)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_date", $this->event_date);
        $stmt->bindParam(":event_name", $this->event_name);
        $stmt->bindParam(":impact_multiplier", $this->impact_multiplier);

        return $stmt->execute();
    }

    // Read all events, upcoming first
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY event_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Read single event by ID
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Get upcoming events from today onwards (for forecasting)
    public function readUpcoming() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE event_date >= CURDATE() ORDER BY event_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Update an event
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET event_date = :event_date, event_name = :event_name, impact_multiplier = :impact_multiplier
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_date", $this->event_date);
        $stmt->bindParam(":event_name", $this->event_name);
        $stmt->bindParam(":impact_multiplier", $this->impact_multiplier);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Delete an event
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }
}
?>
