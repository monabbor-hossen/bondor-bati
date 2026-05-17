<?php
/**
 * Front Controller (index.php)
 * Phase 4 Update: Added session_start() and global auth guard.
 */

// Start session at the very top — must be before any output
session_start();

define('ROOT_PATH', __DIR__);

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
