<?php
/**
 * Bondor Bati POS v2.0 — Front Controller
 */

// ── PHP Hardening ─────────────────────────────────────────────────────
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// ── Secure Session Configuration (must be before session_start) ───────
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,           // until browser closes
    'path'     => '/',
    'secure'   => $isHttps,    // HTTPS-only in production
    'httponly' => true,        // JS cannot read session cookie
    'samesite' => 'Lax',
]);
session_name('bb_sess');
session_start();

// ── Security Headers ──────────────────────────────────────────────────
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('X-Powered-By: ', true);
header("Content-Security-Policy: default-src 'self'; "
    . "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; "
    . "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; "
    . "img-src 'self' data:; "
    . "connect-src 'self'; "
    . "frame-ancestors 'none';");

define('ROOT_PATH', __DIR__);
define('LOG_PATH', ROOT_PATH . '/logs');

// ── Global Exception Handler ──────────────────────────────────────────
set_exception_handler(function ($e) {
    $logFile = LOG_PATH . '/error_' . date('Y-m-d') . '.log';
    $msg     = date('H:i:s') . " | {$e->getMessage()} | {$e->getFile()}:{$e->getLine()}\n";
    if (!is_dir(LOG_PATH)) mkdir(LOG_PATH, 0755, true);
    file_put_contents($logFile, $msg, FILE_APPEND);
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Error</title></head>'
       . '<body style="background:#0a0a0f;color:#e2e8f0;font-family:Inter,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;">'
       . '<div style="text-align:center;padding:2rem;"><h2 style="color:#f43f5e;">Something went wrong.</h2>'
       . '<a href="?url=dashboard" style="color:#f43f5e;font-weight:600;">← Go Back</a></div></body></html>';
    exit;
});

// ── Autoloader ────────────────────────────────────────────────────────
spl_autoload_register(function ($class) {
    $file = ROOT_PATH . '/' . str_replace(['App\\', 'Config\\', '\\'], ['app/', 'config/', '/'], $class) . '.php';
    if (file_exists($file)) require_once $file;
});

// ── i18n Bootstrap ────────────────────────────────────────────────────
require_once ROOT_PATH . '/config/i18n.php';

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'bn'])) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('bb_lang', $_GET['lang'], time() + 86400 * 365, '/');
    $params = $_GET;
    unset($params['lang']);
    $qs = http_build_query($params);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . ($qs ? '?' . $qs : ''));
    exit;
}
loadTranslations();

// ── Router ────────────────────────────────────────────────────────────
$url   = rtrim(filter_var($_GET['url'] ?? 'dashboard', FILTER_SANITIZE_URL), '/');
$parts = explode('/', $url);

$controllerName = ucfirst($parts[0] ?? 'dashboard') . 'Controller';
$methodName     = preg_replace('/[^a-zA-Z0-9_]/', '', $parts[1] ?? 'index');
$params         = array_slice($parts, 2);

// ── Auth Guard ────────────────────────────────────────────────────────
$publicRoutes = ['auth/login', 'auth/logout', 'auth/verifytoken', 'auth/checkstatus'];
$isPublic     = in_array(strtolower($url), $publicRoutes);

if (!$isPublic && empty($_SESSION['user_id']) && empty($_COOKIE['bb_token'])) {
    header('Location: ?url=auth/login');
    exit;
}

// ── Dispatch ──────────────────────────────────────────────────────────
$controllerClass = '\\App\\Controllers\\' . $controllerName;

if (!class_exists($controllerClass)) {
    http_response_code(404);
    exit('404 Not Found');
}

$instance = new $controllerClass();
if (!method_exists($instance, $methodName)) {
    http_response_code(404);
    exit('404 Not Found');
}

call_user_func_array([$instance, $methodName], $params);
