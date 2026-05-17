<?php
/**
 * Front Controller (index.php)
 * Phase 4 Update: Added session_start() and global auth guard.
 * Phase 9 Update: Added global exception handler for error logging.
 */

// Start session at the very top — must be before any output
session_start();

define('ROOT_PATH', __DIR__);
define('LOG_PATH', ROOT_PATH . '/logs');

// Global exception handler - catches all uncaught errors
set_exception_handler(function($exception) {
    $date = date('Y-m-d');
    $logFile = LOG_PATH . "/error_{$date}.log";
    $message = date('Y-m-d H:i:s') . " | " . $exception->getMessage() . " | File: " . $exception->getFile() . " Line: " . $exception->getLine() . "\n";

    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    file_put_contents($logFile, $message, FILE_APPEND);

    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body style="background:#0f0f1a;color:#eaeaea;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;"><div style="text-align:center;padding:2rem;"><h2 style="color:#e94560;">Whoops!</h2><p>Something went wrong. Please try that again.</p><a href="?url=home" style="color:#e94560;">← Back to Home</a></div></body></html>';
    exit;
});

// Autoloader for App\ and Config\ namespaces
spl_autoload_register(function ($class) {
    $classPath = str_replace(['App\\', 'Config\\', '\\'], ['app/', 'config/', '/'], $class);
    $file = ROOT_PATH . '/' . $classPath . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// ── Router ────────────────────────────────────────────────────────────
$url      = isset($_GET['url']) ? rtrim($_GET['url'], '/') : 'dashboard';
$url      = filter_var($url, FILTER_SANITIZE_URL);
$parts    = explode('/', $url);

$controllerName = !empty($parts[0]) ? ucfirst($parts[0]) . 'Controller' : 'DashboardController';
$methodName     = !empty($parts[1]) ? $parts[1] : 'index';
unset($parts[0], $parts[1]);
$params = array_values($parts);

// ── Global Auth Guard ─────────────────────────────────────────────────
// Public routes that don't require authentication
$publicRoutes = ['auth/login', 'auth/logout'];
$currentRoute = strtolower($parts[0] ?? $url);  // normalized route string
$isPublic     = in_array(strtolower(rtrim($url, '/')), $publicRoutes);

if (!$isPublic && empty($_SESSION['user_id'])) {
    // Not logged in and not on a public route — redirect to login
    header('Location: ?url=auth/login');
    exit;
}

// ── Dispatch ──────────────────────────────────────────────────────────
$controllerClass = '\\App\\Controllers\\' . $controllerName;

if (class_exists($controllerClass)) {
    $instance = new $controllerClass();
    if (method_exists($instance, $methodName)) {
        call_user_func_array([$instance, $methodName], $params);
    } else {
        http_response_code(404);
        echo "404: Method '{$methodName}' not found in '{$controllerName}'.";
    }
} else {
    http_response_code(404);
    echo "404: Controller '{$controllerName}' does not exist.";
}
