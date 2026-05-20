<?php
/**
 * Bondor Bati POS v2.0 — Front Controller
 * Routes all requests, handles auth guards, and bootstraps i18n.
 */
session_start();

define('ROOT_PATH', __DIR__);
define('LOG_PATH', ROOT_PATH . '/logs');

// ── Global Exception Handler ──────────────────────────────────────────
set_exception_handler(function ($e) {
    $date    = date('Y-m-d');
    $logFile = LOG_PATH . "/error_{$date}.log";
    $msg     = date('H:i:s') . " | {$e->getMessage()} | {$e->getFile()}:{$e->getLine()}\n";

    if (!is_dir(LOG_PATH)) mkdir(LOG_PATH, 0755, true);
    file_put_contents($logFile, $msg, FILE_APPEND);

    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Error</title></head>'
       . '<body style="background:#0a0a0f;color:#e2e8f0;font-family:Inter,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;">'
       . '<div style="text-align:center;padding:2rem;"><h2 style="color:#f43f5e;">Whoops!</h2><p>Something went wrong.</p>'
       . '<a href="?url=dashboard" style="color:#f43f5e;font-weight:600;">← Back</a></div></body></html>';
    exit;
});

// ── Autoloader ────────────────────────────────────────────────────────
spl_autoload_register(function ($class) {
    $classPath = str_replace(['App\\', 'Config\\', '\\'], ['app/', 'config/', '/'], $class);
    $file = ROOT_PATH . '/' . $classPath . '.php';
    if (file_exists($file)) require_once $file;
});

// ── i18n Bootstrap ────────────────────────────────────────────────────
require_once ROOT_PATH . '/config/i18n.php';

// Handle language toggle via GET parameter
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'bn'])) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('bb_lang', $_GET['lang'], time() + 86400 * 365, '/');
    // Redirect back without the lang param
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    $params   = $_GET;
    unset($params['lang']);
    $qs = http_build_query($params);
    header('Location: ' . $redirect . ($qs ? '?' . $qs : ''));
    exit;
}

loadTranslations();

// ── Router ────────────────────────────────────────────────────────────
$url   = isset($_GET['url']) ? rtrim($_GET['url'], '/') : 'dashboard';
$url   = filter_var($url, FILTER_SANITIZE_URL);
$parts = explode('/', $url);

$controllerName = !empty($parts[0]) ? ucfirst($parts[0]) . 'Controller' : 'DashboardController';
$methodName     = !empty($parts[1]) ? $parts[1] : 'index';
unset($parts[0], $parts[1]);
$params = array_values($parts);

// ── Auth Guard & Persistent Login ─────────────────────────────────────
$publicRoutes = ['auth/login', 'auth/logout', 'auth/verifytoken', 'api/sync'];
$isPublic     = in_array(strtolower(rtrim($url, '/')), $publicRoutes);

// If there's a token and no session, we let the Controller constructor handle it
if (!$isPublic && empty($_SESSION['user_id']) && empty($_COOKIE['bb_token'])) {
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
        echo "404: Method '{$methodName}' not found.";
    }
} else {
    http_response_code(404);
    echo "404: Controller '{$controllerName}' not found.";
}
