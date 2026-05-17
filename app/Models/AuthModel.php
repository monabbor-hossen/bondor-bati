<?php
namespace App\Models;

use Config\Database;
use PDO;

/**
 * AuthModel
 * Handles authentication and human resource data fetching.
 */
class AuthModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Authenticate a user based on username and password
     *
     * @param string $username
     * @param string $password
     * @return array|false Returns user data array on success, false on failure
     */
    public function authenticate($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username AND is_active = 1 LIMIT 1");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password hash
        if ($user && password_verify($password, $user['password'])) {
            // Do not return the password hash
            unset($user['password']);
            return $user;
        }
        return false;
    }
}
