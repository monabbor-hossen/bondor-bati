<?php
namespace App\Core;

/**
 * Base Controller — All controllers extend this.
 * Renders views inside the master layout with bilingual data injection.
 */
class Controller {
    protected $db;

    public function __construct() {
        $this->db = (new \Config\Database())->getConnection();

        // 1. If we have a persistent cookie but no session, try to log them in
        if (isset($_COOKIE['bb_token']) && empty($_SESSION['user_id'])) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE access_token = :token LIMIT 1");
            $stmt->execute([':token' => $_COOKIE['bb_token']]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user && $user['is_active'] == 1) {
                $_SESSION['user_id']      = $user['id'];
                $_SESSION['user_name']    = $user['name'];
                $_SESSION['user_name_bn'] = $user['name_bn'] ?? $user['name'];
                $_SESSION['role']         = $user['role'];
                $_SESSION['permissions']  = json_decode($user['permissions'] ?? '{}', true);
            } else {
                setcookie('bb_token', '', time() - 3600, '/');
                $this->redirect('?url=auth/login');
            }
        } 
        
        // 2. Kill switch: if a user has a session, ensure they are still active
        if (!empty($_SESSION['user_id'])) {
            $stmt = $this->db->prepare("SELECT is_active FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $isActive = $stmt->fetchColumn();

            if (!$isActive) {
                // Admin revoked access or user deleted -> kill session and cookie
                $_SESSION = [];
                session_destroy();
                setcookie('bb_token', '', time() - 3600, '/');

                $isAjax = (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || 
                          $_SERVER['REQUEST_METHOD'] === 'POST' && file_get_contents('php://input');

                if ($isAjax) {
                    $this->json(['error' => 'Session expired', 'redirect' => '?url=auth/login'], 401);
                } else {
                    $this->redirect('?url=auth/login');
                }
            }
        }

        // 3. Auto-Migrations for Deployment (Ensures schema consistency on new hosts)
        try { $this->db->exec("ALTER TABLE items ADD COLUMN linked_raw_item VARCHAR(100) NULL AFTER item_name"); } catch (\Exception $e) {}
        try { $this->db->exec("ALTER TABLE items ADD COLUMN additional_cost DECIMAL(10,2) DEFAULT 0 AFTER cost_price"); } catch (\Exception $e) {}
        try { $this->db->exec("ALTER TABLE items ADD COLUMN online_price DECIMAL(10,2) DEFAULT 0 AFTER selling_price"); } catch (\Exception $e) {}
        try { $this->db->exec("ALTER TABLE items ADD COLUMN raw_usage DECIMAL(8,3) DEFAULT 1.000 AFTER linked_raw_item"); } catch (\Exception $e) {}
        try { $this->db->exec("ALTER TABLE items ADD COLUMN raw_usage_unit VARCHAR(20) DEFAULT 'kg' AFTER raw_usage"); } catch (\Exception $e) {}
    }

    /**
     * Render a view, optionally wrapped in the master layout.
     */
    protected function view(string $viewName, array $data = [], bool $useLayout = true): void {
        extract($data);

        $viewFile = ROOT_PATH . '/app/Views/' . $viewName . '.php';
        if (!file_exists($viewFile)) {
            die("View '{$viewName}.php' not found.");
        }

        if ($useLayout) {
            $contentView = $viewName;
            $layoutFile  = ROOT_PATH . '/app/Views/layout/main.php';
            if (file_exists($layoutFile)) {
                require $layoutFile;
            } else {
                require $viewFile;
            }
        } else {
            require $viewFile;
        }
    }

    /**
     * Redirect helper
     */
    protected function redirect(string $url): void {
        header("Location: {$url}");
        exit;
    }

    /**
     * JSON response helper
     */
    protected function json(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Guard: require admin role
     */
    protected function requireAdmin(): void {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->redirect('?url=dashboard');
        }
    }

    /**
     * Guard: require authentication
     */
    protected function requireAuth(): void {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('?url=auth/login');
        }
    }

    /**
     * Get 4:00 AM to 4:00 AM Business Date
     */
    protected function getBusinessDate(): string {
        return (int)date('H') < 4 ? date('Y-m-d', strtotime('-1 day')) : date('Y-m-d');
    }

    /**
     * Get auto-detected Shift
     */
    protected function getCurrentShift(): string {
        $h = (int)date('H');
        if ($h >= 4 && $h < 12) return 'morning';
        if ($h >= 12 && $h < 20) return 'evening';
        return 'night';
    }
}
