<?php

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $username;
    public $password;
    public $access_token;
    public $role;
    public $is_active;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, username, password, access_token, role, is_active) 
                  VALUES (:name, :username, :password, :access_token, :role, :is_active)";

        $stmt = $this->conn->prepare($query);

        // Hash password before storing
        $hashed_password = password_hash($this->password, PASSWORD_BCRYPT);

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":access_token", $this->access_token);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":is_active", $this->is_active, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Read all users
    public function read() {
        $query = "SELECT id, name, username, role, is_active FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Read single user by ID
    public function readOne() {
        $query = "SELECT id, name, username, role, is_active FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Find user by username (for login)
    public function findByUsername() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username AND is_active = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Find user by magic link access token
    public function findByAccessToken() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE access_token = :access_token AND is_active = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":access_token", $this->access_token);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Update user details
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET name = :name, username = :username, role = :role, is_active = :is_active
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":is_active", $this->is_active, PDO::PARAM_INT);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Update password separately for security
    public function updatePassword() {
        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($this->password, PASSWORD_BCRYPT);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }

    // Delete a user
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }
}
?>
