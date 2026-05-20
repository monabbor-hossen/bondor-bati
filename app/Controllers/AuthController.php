<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

/**
 * Auth Controller — Admin login + Magic Link staff authentication
 */
class AuthController extends Controller {
    private $db;

    public function __construct() {
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
                    $this->redirect('?url=dashboard');
                } else {
                    $error = 'Invalid credentials.';
                }
            }
        }

        $this->view('auth/login', ['error' => $error], false);
    }

    /**
     * Magic Link Authentication (staff)
     * Route: ?url=auth/magic&token=XXXXX
     */
    public function magic() {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $this->view('auth/magic_error', ['message' => 'No access token provided.'], false);
            return;
        }

        // Find valid, unused, non-expired magic link
        $stmt = $this->db->prepare("
            SELECT ml.*, u.name, u.name_bn, u.role, u.is_active, u.permissions
            FROM magic_links ml
            JOIN users u ON ml.user_id = u.id
            WHERE ml.token = :token
              AND ml.used_at IS NULL
              AND ml.expires_at > NOW()
              AND u.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $link = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$link) {
            $this->view('auth/magic_error', ['message' => 'Link expired or already used.'], false);
            return;
        }

        // Immediately invalidate the link (one-time use)
        $upd = $this->db->prepare("UPDATE magic_links SET used_at = NOW() WHERE id = :id");
        $upd->execute([':id' => $link['id']]);

        // Establish session
        session_regenerate_id(true);
        $_SESSION['user_id']      = $link['user_id'];
        $_SESSION['user_name']    = $link['name'];
        $_SESSION['user_name_bn'] = $link['name_bn'] ?? $link['name'];
        $_SESSION['role']         = $link['role'];
        $_SESSION['permissions']  = json_decode($link['permissions'] ?? '{}', true);

        $this->redirect('?url=dashboard');
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
        $hours  = (int) ($data['hours'] ?? 72);

        if (!$userId) {
            $this->json(['success' => false, 'error' => 'User ID required']);
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));

        $stmt = $this->db->prepare("
            INSERT INTO magic_links (user_id, token, expires_at)
            VALUES (:user_id, :token, :expires_at)
        ");
        $stmt->execute([
            ':user_id'    => $userId,
            ':token'      => $token,
            ':expires_at' => $expiresAt,
        ]);

        // Build the full magic link URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'];
        $baseUrl  = "{$protocol}://{$host}/bondor-bati";
        $magicUrl = "{$baseUrl}/?url=auth/magic&token={$token}";

        $this->json([
            'success'    => true,
            'magic_link' => $magicUrl,
            'expires_at' => $expiresAt,
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
}
