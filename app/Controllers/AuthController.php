<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

/**
 * Auth Controller — Admin login + Magic Link staff authentication
 */
class AuthController extends Controller {
public function __construct() {
        parent::__construct();
        $this->db = (new Database())->getConnection();
    }

    /**
     * Admin Login (username/password)
     * Route: ?url=auth/login
     */
    public function login() {
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($username) || empty($password)) {
                $error = __('error') . ' Please enter both fields.';
            } else {
                $stmt = $this->db->prepare("
                    SELECT * FROM users
                    WHERE username = :username AND is_active = 1 AND role = 'admin'
                    LIMIT 1
                ");
                $stmt->execute([':username' => $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_name_bn'] = $user['name_bn'] ?? $user['name'];
                    $_SESSION['role']      = $user['role'];
                    
                    if ($_SESSION['role'] === 'staff') {
                        $this->redirect('?url=bazaar');
                    } else {
                        $this->redirect('?url=dashboard');
                    }
                } else {
                    $error = 'Invalid credentials.';
                }
            }
        }

        $this->view('auth/login', ['error' => $error], false);
    }

    /**
     * Generate a new access token for a user
     */
    public function generateToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $this->db->prepare("
            UPDATE users 
            SET access_token = :token, token_expires_at = :expires_at 
            WHERE id = :id
        ");
        $stmt->execute([
            ':token' => $token,
            ':expires_at' => $expiresAt,
            ':id' => $userId
        ]);
        
        return $token;
    }

    /**
     * Verify Magic Link Token and Login
     * Route: ?url=auth/verifyToken&token=XXXXX
     */
    public function verifyToken() {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $this->view('auth/magic_error', ['message' => 'No access token provided.'], false);
            return;
        }

        $stmt = $this->db->prepare("
            SELECT * FROM users
            WHERE access_token = :token 
              AND token_expires_at > NOW() 
              AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $this->view('auth/magic_error', ['message' => __('token_expired')], false);
            return;
        }

        // Clear token expiration but KEEP the token for persistent cookie verification
        $upd = $this->db->prepare("UPDATE users SET token_expires_at = NULL WHERE id = :id");
        $upd->execute([':id' => $user['id']]);

        // Set persistent 30-day cookie
        setcookie('bb_token', $token, time() + (86400 * 30), '/');

        // Establish session
        session_regenerate_id(true);
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['user_name']    = $user['name'];
        $_SESSION['user_name_bn'] = $user['name_bn'] ?? $user['name'];
        $_SESSION['role']         = $user['role'];
        $_SESSION['permissions']  = json_decode($user['permissions'] ?? '{}', true);

        if ($_SESSION['role'] === 'staff') {
            $this->redirect('?url=bazaar');
        } else {
            $this->redirect('?url=dashboard');
        }
    }

    /**
     * Generate a new magic link for a staff member (Admin only)
     * Route: ?url=auth/generateLink (POST, AJAX)
     */
    public function generateLink() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required'], 400);
        }

        $data   = json_decode(file_get_contents('php://input'), true);
        $userId = (int) ($data['user_id'] ?? 0);

        if (!$userId) {
            $this->json(['success' => false, 'error' => 'User ID required']);
        }

        $token = $this->generateToken($userId);

        // Build the full magic link URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'];
        $baseUrl  = "{$protocol}://{$host}/bondor-bati";
        $magicUrl = "{$baseUrl}/?url=auth/verifyToken&token={$token}";

        $this->json([
            'success'    => true,
            'magic_link' => $magicUrl
        ]);
    }

    /**
     * Add Staff (Admin only)
     * Route: ?url=auth/addStaff
     */
    public function addStaff() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required'], 400);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? ''); 
        $role = trim($data['role'] ?? 'staff');

        if (empty($name) || empty($phone)) {
            $this->json(['success' => false, 'error' => 'Name and phone are required.']);
        }

        // Check if username (phone) exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $phone]);
        if ($stmt->fetch()) {
            $this->json(['success' => false, 'error' => 'User with this phone number already exists.']);
        }

        // Insert new user
        $ins = $this->db->prepare("
            INSERT INTO users (name, username, role, is_active)
            VALUES (:name, :username, :role, 1)
        ");
        $ins->execute([
            ':name' => $name,
            ':username' => $phone,
            ':role' => $role
        ]);
        
        $userId = $this->db->lastInsertId();
        
        // Generate Token
        $token = $this->generateToken($userId);
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'];
        $baseUrl  = "{$protocol}://{$host}/bondor-bati";
        $magicUrl = "{$baseUrl}/?url=auth/verifyToken&token={$token}";

        $this->json([
            'success' => true,
            'magic_link' => $magicUrl
        ]);
    }

    /**
     * Logout
     * Route: ?url=auth/logout
     */
    public function logout() {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        $this->redirect('?url=auth/login');
    }

    /**
     * Real-time Kill Switch Status Check
     * Route: ?url=auth/checkStatus
     */
    public function checkStatus() {
        if (empty($_SESSION['user_id'])) {
            $this->json(['active' => false]);
        }
        $stmt = $this->db->prepare("SELECT is_active FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $isActive = $stmt->fetchColumn();

        if (!$isActive) {
            $_SESSION = [];
            session_destroy();
            setcookie('bb_token', '', time() - 3600, '/');
            $this->json(['active' => false]);
        }
        $this->json(['active' => true]);
    }
}
