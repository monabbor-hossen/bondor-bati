<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

/**
 * Auth Controller
 * Handles user login and logout with session management.
 */
class AuthController extends Controller {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Login — GET renders form, POST processes credentials
     * Route: ?url=auth/login
     */
    public function login() {
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($username) || empty($password)) {
                $error = 'Please enter both username and password.';
            } else {
                // Fetch user by username — only active users
                $stmt = $this->db->prepare("
                    SELECT * FROM users WHERE username = :username AND is_active = 1 LIMIT 1
                ");
                $stmt->execute([':username' => $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    // Regenerate session ID to prevent session fixation attacks
                    session_regenerate_id(true);

                    // Store minimal, safe user info in session
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['role']      = $user['role'];

                    // Redirect to the smart dashboard after login
                    $this->redirect('?url=dashboard');
                } else {
                    $error = 'Invalid username or password. Please try again.';
                }
            }
        }

        // Render login page WITHOUT the layout wrapper (standalone page)
        $this->view('auth/login', ['error' => $error], false);
    }

    /**
     * Logout — destroys session and redirects to login
     * Route: ?url=auth/logout
     */
    public function logout() {
        // Unset all session variables
        $_SESSION = [];

        // Destroy the session cookie if it exists
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        // Destroy the session on the server
        session_destroy();

        $this->redirect('?url=auth/login');
    }
}
