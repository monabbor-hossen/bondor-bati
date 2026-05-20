<?php
namespace Config;

use PDO;
use PDOException;

/**
 * Database Connection Singleton
 */
class Database {
    private static $instance = null;
    private $conn;

    private $host    = '127.0.0.1';
    private $db_name = 'bondor_bati';
    private $user    = 'root';
    private $pass    = '';

    public function __construct() {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
                $this->conn = new PDO($dsn, $this->user, $this->pass);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$instance = $this->conn;
            } catch (PDOException $e) {
                die("DB Error: " . $e->getMessage());
            }
        }
        $this->conn = self::$instance;
    }

    public function getConnection(): PDO {
        return $this->conn;
    }
}
