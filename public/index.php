<?php
/**
 * Bondor Bati POS — Front Controller / Router
 * 
 * All non-file requests are routed here via .htaccess.
 * Matches clean URL paths like /login, /staff/dashboard, /admin/dashboard.
 * 
 * Route structure:
 *   /                      → Home (redirect by role)
 *   /login                 → Login form + POST handler
 *   /login/token/{token}   → Magic link staff login
 *   /logout                → Destroy session
 *   /staff/closing         → Staff closing entry form   (requires STAFF)
 *   /staff/dashboard       → Staff dashboard            (requires STAFF)
 *   /admin/dashboard       → Admin dashboard            (requires ADMIN)
 *   /admin/magic-link      → Generate magic link        (requires ADMIN)
 *   /api/*                 → Handled by api/routes.php  (separate entry)
 */

// ─── SECURITY HEADERS ────────────────────────────────────────────────
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("Referrer-Policy: strict-origin-when-cross-origin");

// ─── BOOTSTRAP ───────────────────────────────────────────────────────
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../middleware/Auth.php';

$auth = new AuthController();

// ─── PARSE THE REQUEST PATH ──────────────────────────────────────────
// Strip the base path (/bondor-bati) and query string to get a clean route
$request_uri = $_SERVER['REQUEST_URI'];
// To match your actual folder name:
$base_path = '/bondor-bati/public'; // or just '/bondor_bati_pos' depending on your XAMPP/server setup
$path = parse_url($request_uri, PHP_URL_PATH);
$route = '/' . trim(str_replace($base_path, '', $path), '/');
$method = $_SERVER['REQUEST_METHOD'];

// ─── ROUTE DISPATCHER ────────────────────────────────────────────────

switch (true) {

    // ═════════════════════════════════════════════════════════════════
    //  HOME — / 
    // ═════════════════════════════════════════════════════════════════
    case $route === '/' || $route === '':
        if (isLoggedIn()) {
            if (currentRole() === 'ADMIN') {
                header('Location: ' . $base_path . '/admin/dashboard');
            } else {
                header('Location: ' . $base_path . '/staff/dashboard');
            }
            exit;
        }
        // Not logged in → show login
        header('Location: ' . $base_path . '/login');
        exit;
        break;

    // ═════════════════════════════════════════════════════════════════
    //  LOGIN — /login
    // ═════════════════════════════════════════════════════════════════
    case $route === '/login':
        // Already logged in? Redirect home.
        if (isLoggedIn()) {
            header('Location: ' . $base_path . '/');
            exit;
        }

        $login_error = null;

        // Handle POST login form submission
        if ($method === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            $result = $auth->loginAdmin($username, $password);

            if ($result['success']) {
                header('Location: ' . $base_path . '/');
                exit;
            } else {
                $login_error = $result['message'];
            }
        }

        include __DIR__ . '/../views/shared/login.php';
        break;

    // ═════════════════════════════════════════════════════════════════
    //  MAGIC LINK LOGIN — /login/token/{token}
    // ═════════════════════════════════════════════════════════════════
    case preg_match('#^/login/token/([a-f0-9]{64})$#', $route, $matches) === 1:
        $token = $matches[1];
        $result = $auth->loginStaffWithToken($token);

        if ($result['success']) {
            header('Location: ' . $base_path . '/staff/dashboard');
            exit;
        }

        // Token invalid — show login with error
        $login_error = $result['message'];
        include __DIR__ . '/../views/shared/login.php';
        break;

    // ═════════════════════════════════════════════════════════════════
    //  LOGOUT — /logout
    // ═════════════════════════════════════════════════════════════════
    case $route === '/logout':
        $auth->logout();
        header('Location: ' . $base_path . '/login');
        exit;
        break;

    // ═════════════════════════════════════════════════════════════════
    //  STAFF ROUTES — /staff/*  (requires STAFF or ADMIN role)
    // ═════════════════════════════════════════════════════════════════
    case $route === '/staff/dashboard':
        requireRole('STAFF');

        // Fetch today's stock summary for dashboard
        $database = new Database();
        $db = $database->getConnection();

        $query = "SELECT i.id, i.item_name, i.selling_price,
                         COALESCE(ds.opening_qty, 0) as opening_qty,
                         COALESCE(ds.closing_qty, 0) as closing_qty,
                         COALESCE(ds.sold_qty, 0) as sold_qty,
                         COALESCE(ds.total_sales_amount, 0) as total_sales_amount
                  FROM items i
                  LEFT JOIN daily_stocks ds ON i.id = ds.item_id AND ds.log_date = CURDATE()
                  ORDER BY i.item_name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $today_stocks = $stmt->fetchAll();

        include __DIR__ . '/../views/staff/dashboard.php';
        break;

    case $route === '/staff/closing':
        requireRole('STAFF');

        // Fetch today's menu items for the closing form
        $database = new Database();
        $db = $database->getConnection();

        $query = "SELECT i.id, i.item_name, 
                         COALESCE(ds.opening_qty, 0) as opening_qty
                  FROM items i
                  LEFT JOIN daily_stocks ds ON i.id = ds.item_id AND ds.log_date = CURDATE()
                  ORDER BY i.item_name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $menu_items = $stmt->fetchAll();

        include __DIR__ . '/../views/staff/closing_entry.php';
        break;

    // ═════════════════════════════════════════════════════════════════
    //  ADMIN ROUTES — /admin/*  (requires ADMIN role)
    // ═════════════════════════════════════════════════════════════════
    case $route === '/admin/dashboard':
        requireRole('ADMIN');
        include __DIR__ . '/../views/admin/dashboard.php';
        break;

    case $route === '/admin/magic-link':
        requireRole('ADMIN');

        $magic_result = null;

        // Handle POST: generate magic link for a staff member
        if ($method === 'POST') {
            $staff_id = (int) ($_POST['staff_id'] ?? 0);
            if ($staff_id > 0) {
                $magic_result = $auth->generateMagicLink($staff_id);
            } else {
                $magic_result = ['success' => false, 'message' => 'Please select a staff member.'];
            }
        }

        // Fetch all active staff for the dropdown
        $database = new Database();
        $db = $database->getConnection();
        $query = "SELECT id, name, username FROM users WHERE role = 'STAFF' AND is_active = 1 ORDER BY name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $staff_list = $stmt->fetchAll();

        include __DIR__ . '/../views/admin/magic_link.php';
        break;

    // ═════════════════════════════════════════════════════════════════
    //  404 — No matching route
    // ═════════════════════════════════════════════════════════════════
    default:
        http_response_code(404);
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>404 Not Found | Bondor Bati</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
        </head>
        <body class="bg-slate-100 font-[Inter] min-h-screen flex items-center justify-center p-4">
            <div class="text-center max-w-sm">
                <div class="text-7xl font-extrabold text-slate-200 mb-4">404</div>
                <h1 class="text-xl font-bold text-slate-800 mb-2">Page Not Found</h1>
                <p class="text-slate-500 text-sm mb-6">The page you\'re looking for doesn\'t exist or has been moved.</p>
                <a href="' . $base_path . '/" class="inline-block px-6 py-3 bg-slate-900 text-white font-semibold rounded-xl hover:bg-slate-800 transition-colors">
                    ← Go Home
                </a>
            </div>
        </body>
        </html>';
        break;
}
?>