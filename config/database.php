<?php
/**
 * Database Configuration & Connection Wrapper
 */
class Database
{
    private $host = "localhost";
    private $db_name = "bondor_bati_pos";
    private $username = "root"; // Change if your MySQL has a different username
    private $password = "";     // Change if your MySQL has a password
    public $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            // Set PDO to throw exceptions on error
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Return rows as associative arrays by default
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            die("Database Connection Error: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
?>