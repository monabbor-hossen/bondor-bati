<?php
/**
 * Auth Middleware
 * - Admin: session-based login (username + password)
 * - Staff: magic link via access_token URL
 * - Permissions: admin toggles page access for each staff
 */

// All controllable pages with display labels & icons
define('ALL_PAGES', [
    'dashboard' => ['label' => 'Dashboard',        'icon' => 'fa-chart-pie'],
    'morning'   => ['label' => 'Morning & Prep',   'icon' => 'fa-sun'],
    'service'   => ['label' => 'Service & Sales',   'icon' => 'fa-utensils'],
    'closing'   => ['label' => 'Night Closing',     'icon' => 'fa-moon'],
    'forecast'  => ['label' => 'Forecasting',       'icon' => 'fa-chart-line'],
    'staff'     => ['label' => 'Staff & Dues',      'icon' => 'fa-users'],
    'items'     => ['label' => 'Menu Items',         'icon' => 'fa-list'],
    'suppliers' => ['label' => 'Suppliers',          'icon' => 'fa-truck'],
]);

function checkAuth() {
    // Check for staff magic link token in URL FIRST
    // This ensures if they click the link again, their session and permissions are forcefully refreshed.
    if (!empty($_GET['token'])) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE access_token = :token AND is_active = 1 LIMIT 1");
        $stmt->execute(['token' => $_GET['token']]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_token'] = $user['access_token'];

            // Load and cache permissions for staff
            if ($user['role'] === 'STAFF') {
                $stmtP = $db->prepare("SELECT page_slug FROM staff_permissions WHERE user_id = :uid");
                $stmtP->execute(['uid' => $user['id']]);
                $_SESSION['permissions'] = $stmtP->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $_SESSION['permissions'] = array_keys(ALL_PAGES); // Admin gets all
            }

            return true;
        }
    }

    // Already authenticated via session
    if (!empty($_SESSION['user_id'])) {
        // Dynamically reload permissions on every page load to ensure real-time admin control
        if ($_SESSION['user_role'] === 'STAFF') {
            $db = Database::getInstance()->getConnection();
            $stmtP = $db->prepare("SELECT page_slug FROM staff_permissions WHERE user_id = :uid");
            $stmtP->execute(['uid' => $_SESSION['user_id']]);
            $_SESSION['permissions'] = $stmtP->fetchAll(PDO::FETCH_COLUMN);
        }
        return true;
    }

    return false;
}

/**
 * Load permissions into session (call after login or when permissions change)
 */
function loadPermissions($userId, $role) {
    $db = Database::getInstance()->getConnection();
    if ($role === 'ADMIN') {
        $_SESSION['permissions'] = array_keys(ALL_PAGES);
    } else {
        $stmt = $db->prepare("SELECT page_slug FROM staff_permissions WHERE user_id = :uid");
        $stmt->execute(['uid' => $userId]);
        $_SESSION['permissions'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

function currentUser() {
    return [
        'id'    => $_SESSION['user_id'] ?? null,
        'name'  => $_SESSION['user_name'] ?? 'Guest',
        'role'  => $_SESSION['user_role'] ?? 'GUEST',
    ];
}

function isAdmin() {
    return ($_SESSION['user_role'] ?? '') === 'ADMIN';
}

/**
 * Check if the current user can access a given page slug.
 */
function canAccess($pageSlug) {
    if (isAdmin()) return true;
    $perms = $_SESSION['permissions'] ?? [];
    return in_array($pageSlug, $perms);
}

/**
 * Get the list of page slugs the current user is allowed to see.
 */
function allowedPages() {
    if (isAdmin()) return array_keys(ALL_PAGES);
    return $_SESSION['permissions'] ?? [];
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ?page=dashboard');
        exit;
    }
}

function generateAccessToken() {
    return bin2hex(random_bytes(32));
}
