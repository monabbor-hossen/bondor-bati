<?php
namespace Config;

use PDO;
use PDOException;

/**
 * Database Configuration and Connection Class
 * Uses PDO for secure database interactions.
 */
class Database {
    private $host = '127.0.0.1'; // Database host
    private $db_name = 'bondor_bati'; // Database name (update as needed)
    private $username = 'root'; // Database user
    private $password = ''; // Database password
    public $conn;

    /**
     * Get the database connection
     *
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        try {
            // Establish PDO connection
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Set error mode to exception for better error handling
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Fetch as associative array by default
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            die("Database Connection Error: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
