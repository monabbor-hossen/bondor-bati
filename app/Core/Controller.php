<?php
namespace App\Core;

/**
 * Base Controller — All controllers extend this.
 * Renders views inside the master layout with bilingual data injection.
 */
class Controller {

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
}
