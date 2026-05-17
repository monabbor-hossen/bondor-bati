<?php
/**
 * AuthController
 * 
 * Handles all authentication workflows:
 * - Admin login via username + password
 * - Staff magic link generation
 * - Staff login via secure access token
 * - Session management and logout
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // ═══════════════════════════════════════════════════════════════════
    //  ADMIN LOGIN — username + password
    // ═══════════════════════════════════════════════════════════════════
    /**
     * Verify admin credentials against the users table and start a session.
     * 
     * @param  string $username  The admin's username.
     * @param  string $password  The plain-text password to verify.
     * @return array             Result array with success status, message, and user data.
     */
    public function loginAdmin($username, $password) {
        // Validate inputs
        if (empty($username) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Username and password are required.'
            ];
        }

        // Look up the user by username (must be active)
        $query = "SELECT id, name, username, password, role 
                  FROM users 
                  WHERE username = :username AND is_active = 1 
                  LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch();

        // Verify user exists and password matches the bcrypt hash
        if (!$user || !password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Invalid username or password.'
            ];
        }

        // Check that this user is an ADMIN
        if ($user['role'] !== 'ADMIN') {
            return [
                'success' => false,
                'message' => 'Access denied. Admin credentials required.'
            ];
        }

        // Set session variables
        $this->startSession();
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        return [
            'success' => true,
            'message' => 'Login successful.',
            'user'    => [
                'id'   => $user['id'],
                'name' => $user['name'],
                'role' => $user['role']
            ]
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  MAGIC LINK GENERATION — admin creates a link for a staff member
    // ═══════════════════════════════════════════════════════════════════
    /**
     * Generate a secure, random token for a staff member, save it to the
     * access_token column, and return a ready-to-share login URL.
     * 
     * @param  int   $staff_id  The user ID of the staff member.
     * @return array            Result with success status, the token, and the full URL.
     */
    public function generateMagicLink($staff_id) {
        // Verify the staff member exists and is active
        $query_check = "SELECT id, name, role FROM users WHERE id = :id AND is_active = 1 LIMIT 1";
        $stmt_check = $this->db->prepare($query_check);
        $stmt_check->bindParam(':id', $staff_id, PDO::PARAM_INT);
        $stmt_check->execute();
        $staff = $stmt_check->fetch();

        if (!$staff) {
            return [
                'success' => false,
                'message' => 'Staff member not found or inactive.'
            ];
        }

        // Generate a cryptographically secure random token (64 hex chars)
        $token = bin2hex(random_bytes(32));

        // Save the token to the database
        $query_update = "UPDATE users SET access_token = :token WHERE id = :id";
        $stmt_update = $this->db->prepare($query_update);
        $stmt_update->bindParam(':token', $token);
        $stmt_update->bindParam(':id', $staff_id, PDO::PARAM_INT);

        if ($stmt_update->execute()) {
            // Build the full magic link URL
            $base_url = $this->getBaseUrl();
            $magic_url = $base_url . '/login/token/' . $token;

            return [
                'success' => true,
                'message' => 'Magic link generated for ' . $staff['name'] . '.',
                'token'   => $token,
                'link'    => $magic_url
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to generate magic link. Database error.'
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  STAFF TOKEN LOGIN — authenticate via URL without a password
    // ═══════════════════════════════════════════════════════════════════
    /**
     * Authenticate a staff member using the access_token passed as a URL
     * parameter. This enables password-less login via magic links.
     * 
     * After successful authentication, the token is invalidated (set to NULL)
     * to prevent replay attacks. A new link must be generated for next login.
     * 
     * @param  string $token  The access token from the URL.
     * @return array          Result array with success status and user data.
     */
    public function loginStaffWithToken($token) {
        // Validate input
        if (empty($token) || strlen($token) !== 64) {
            return [
                'success' => false,
                'message' => 'Invalid or malformed access token.'
            ];
        }

        // Look up user by access_token
        $query = "SELECT id, name, username, role 
                  FROM users 
                  WHERE access_token = :token AND is_active = 1 
                  LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid or expired access token. Please request a new link.'
            ];
        }

        // Invalidate the token after use (single-use magic link)
        $query_invalidate = "UPDATE users SET access_token = NULL WHERE id = :id";
        $stmt_invalidate = $this->db->prepare($query_invalidate);
        $stmt_invalidate->bindParam(':id', $user['id'], PDO::PARAM_INT);
        $stmt_invalidate->execute();

        // Set session variables
        $this->startSession();
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        return [
            'success' => true,
            'message' => 'Welcome back, ' . $user['name'] . '!',
            'user'    => [
                'id'   => $user['id'],
                'name' => $user['name'],
                'role' => $user['role']
            ]
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  LOGOUT
    // ═══════════════════════════════════════════════════════════════════
    /**
     * Destroy the session and log the user out.
     * 
     * @return array  Result with success status.
     */
    public function logout() {
        $this->startSession();
        $_SESSION = [];

        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        return [
            'success' => true,
            'message' => 'Logged out successfully.'
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SESSION HELPERS
    // ═══════════════════════════════════════════════════════════════════

    /** Check if the user is currently authenticated. */
    public function isAuthenticated() {
        $this->startSession();
        return isset($_SESSION['user_id']);
    }

    /** Check if the current user is an ADMIN. */
    public function isAdmin() {
        $this->startSession();
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'ADMIN';
    }

    /** Start the session only if one isn't active yet. */
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /** Detect the application's base URL for building magic links. */
    private function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . '/bondor-bati';
    }
}
?>
