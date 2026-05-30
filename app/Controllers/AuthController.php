<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

/**
 * Auth Controller — Admin login + Magic Link staff authentication
 * Security: CSRF tokens, login rate-limiting, HttpOnly+SameSite cookies
 */
class AuthController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->db = (new Database())->getConnection();
    }

    // ─── CSRF ─────────────────────────────────────────────────────────
    private function generateCsrf(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function verifyCsrf(): void {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            die('Invalid request token. Please refresh and try again.');
        }
        // Rotate after use
        unset($_SESSION['csrf_token']);
    }

    // ─── Rate Limiting (login attempts) ──────────────────────────────
    private function checkRateLimit(string $ip): bool {
        $key     = 'login_fail_' . md5($ip);
        $max     = 5;
        $window  = 300; // 5 minutes

        $attempts = $_SESSION[$key] ?? ['count' => 0, 'until' => 0];

        if ($attempts['until'] > time()) {
            return false; // still locked out
        }
        if ($attempts['count'] >= $max) {
            $_SESSION[$key] = ['count' => $max, 'until' => time() + $window];
            return false;
        }
        return true;
    }

    private function recordFailedLogin(string $ip): void {
        $key = 'login_fail_' . md5($ip);
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'until' => 0];
        $attempts['count']++;
        $_SESSION[$key] = $attempts;
    }

    private function clearRateLimit(string $ip): void {
        unset($_SESSION['login_fail_' . md5($ip)]);
    }

    private function remainingLockout(string $ip): int {
        $key = 'login_fail_' . md5($ip);
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'until' => 0];
        return max(0, $attempts['until'] - time());
    }

    // ─── Login ────────────────────────────────────────────────────────
    /**
     * Admin Login (username/password)
     * Route: ?url=auth/login
     */
    public function login(): void {
        $error    = null;
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $csrfToken = $this->generateCsrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. CSRF check
            $this->verifyCsrf();

            // 2. Rate limit check
            if (!$this->checkRateLimit($ip)) {
                $wait = $this->remainingLockout($ip);
                $error = "Too many failed attempts. Please wait {$wait} seconds.";
            } else {
                $username = trim($_POST['username'] ?? '');
                $password = trim($_POST['password'] ?? '');

                if (empty($username) || empty($password)) {
                    $error = 'Please enter both username and password.';
                } else {
                    $stmt = $this->db->prepare("
                        SELECT id, name, name_bn, password, role, is_active
                        FROM users
                        WHERE username = :username AND is_active = 1 AND role = 'admin'
                        LIMIT 1
                    ");
                    $stmt->execute([':username' => $username]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
                        $this->clearRateLimit($ip);
                        $this->startSecureSession($user);
                        $this->redirect('?url=dashboard');
                    } else {
                        $this->recordFailedLogin($ip);
                        // Generic message — no hint about which field was wrong
                        $error = 'Invalid credentials.';
                        $csrfToken = $this->generateCsrf(); // rotate token
                    }
                }
            }
        }

        $this->view('auth/login', ['error' => $error, 'csrf_token' => $csrfToken], false);
    }

    // ─── Session Hardening ────────────────────────────────────────────
    private function startSecureSession(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['user_name']    = $user['name'];
        $_SESSION['user_name_bn'] = $user['name_bn'] ?? $user['name'];
        $_SESSION['role']         = $user['role'];
        $_SESSION['login_ip']     = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['login_ua']     = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200);
    }

    // ─── Token Generation ─────────────────────────────────────────────
    public function generateToken(int $userId): string {
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $this->db->prepare("
            UPDATE users SET access_token = :token, token_expires_at = :expires WHERE id = :id
        ")->execute([':token' => $token, ':expires' => $expiresAt, ':id' => $userId]);

        return $token;
    }

    // ─── Magic Link Verify ────────────────────────────────────────────
    /**
     * Route: ?url=auth/verifyToken&token=XXXXX
     */
    public function verifyToken(): void {
        $token = $_GET['token'] ?? '';

        if (empty($token) || strlen($token) !== 64) {
            $this->view('auth/magic_error', ['message' => 'Invalid access token.'], false);
            return;
        }

        $stmt = $this->db->prepare("
            SELECT id, name, name_bn, role, is_active, permissions
            FROM users
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

        // Expire the one-time token, keep it for cookie re-auth
        $this->db->prepare("UPDATE users SET token_expires_at = NULL WHERE id = :id")
                 ->execute([':id' => $user['id']]);

        // Secure persistent cookie: HttpOnly + SameSite=Lax
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie('bb_token', $token, [
            'expires'  => time() + (86400 * 30),
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $this->startSecureSession($user);
        $_SESSION['permissions'] = json_decode($user['permissions'] ?? '{}', true);

        $this->redirect($user['role'] === 'staff' ? '?url=bazaar' : '?url=dashboard');
    }

    // ─── Generate Magic Link (Admin only) ────────────────────────────
    /**
     * Route: ?url=auth/generateLink (POST, AJAX)
     */
    public function generateLink(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required'], 405);
        }

        $data   = json_decode(file_get_contents('php://input'), true);
        $userId = (int)($data['user_id'] ?? 0);

        if (!$userId) {
            $this->json(['success' => false, 'error' => 'User ID required'], 400);
        }

        $token    = $this->generateToken($userId);
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'];
        $magicUrl = "{$protocol}://{$host}/bondor-bati/?url=auth/verifyToken&token={$token}";

        $this->json(['success' => true, 'magic_link' => $magicUrl]);
    }

    // ─── Add Staff (Admin only) ───────────────────────────────────────
    public function addStaff(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required'], 405);
        }

        $data  = json_decode(file_get_contents('php://input'), true);
        $name  = trim($data['name']  ?? '');
        $phone = trim($data['phone'] ?? '');
        $role  = in_array($data['role'] ?? 'staff', ['staff', 'admin']) ? $data['role'] : 'staff';

        if (empty($name) || empty($phone)) {
            $this->json(['success' => false, 'error' => 'Name and phone are required.'], 400);
        }

        $chk = $this->db->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $chk->execute([':username' => $phone]);
        if ($chk->fetch()) {
            $this->json(['success' => false, 'error' => 'Phone number already registered.'], 409);
        }

        $this->db->prepare("
            INSERT INTO users (name, username, role, is_active) VALUES (:name, :username, :role, 1)
        ")->execute([':name' => $name, ':username' => $phone, ':role' => $role]);

        $userId   = (int)$this->db->lastInsertId();
        $token    = $this->generateToken($userId);
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'];
        $magicUrl = "{$protocol}://{$host}/bondor-bati/?url=auth/verifyToken&token={$token}";

        $this->json(['success' => true, 'magic_link' => $magicUrl]);
    }

    // ─── Logout ───────────────────────────────────────────────────────
    public function logout(): void {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie('bb_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], true);
        }
        session_destroy();
        $this->redirect('?url=auth/login');
    }

    // ─── Kill Switch Status ────────────────────────────────────────────
    public function checkStatus(): void {
        if (empty($_SESSION['user_id'])) {
            $this->json(['active' => false]);
        }

        $stmt = $this->db->prepare("SELECT is_active FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $isActive = $stmt->fetchColumn();

        if (!$isActive) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            setcookie('bb_token', '', ['expires' => time() - 3600, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
            $_SESSION = [];
            session_destroy();
            $this->json(['active' => false]);
        }

        $this->json(['active' => true]);
    }
}
