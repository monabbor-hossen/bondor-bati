<?php
namespace Config;

use PDO;
use PDOException;

/**
 * Database Connection Singleton
 * Reads credentials from environment variables with safe fallbacks.
 * Set these in Apache's VirtualHost or a .env loader — never hardcode in production.
 */
class Database {
    private static $instance = null;
    private $conn;

    public function __construct() {
        if (self::$instance === null) {
            $host   = getenv('DB_HOST')   ?: '127.0.0.1';
            $dbname = getenv('DB_NAME')   ?: 'bondor_bati';
            $user   = getenv('DB_USER')   ?: 'root';
            $pass   = getenv('DB_PASS')   ?: '';

            try {
                $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // real prepared statements
                self::$instance = $pdo;
            } catch (PDOException $e) {
                // Never expose DB details in production
                error_log('DB connection failed: ' . $e->getMessage());
                http_response_code(503);
                die('Service temporarily unavailable. Please try again later.');
            }
        }
        $this->conn = self::$instance;
    }

    public function getConnection(): PDO {
        return $this->conn;
    }
}
