<?php
/**
 * Middleware — Route Protection
 * 
 * Provides guard functions to protect routes based on authentication
 * status and user role. Call these at the top of any route handler
 * before rendering the view.
 * 
 * Usage:
 *   requireAuth();              // Must be logged in (any role)
 *   requireRole('ADMIN');       // Must be logged in as ADMIN
 *   requireRole('STAFF');       // Must be logged in as STAFF or ADMIN
 */

/**
 * Ensure the session is started exactly once.
 */
function ensureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if the current user is authenticated.
 * 
 * @return bool
 */
function isLoggedIn() {
    ensureSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the current user's role from the session.
 * 
 * @return string|null  'ADMIN', 'STAFF', or null if not logged in.
 */
function currentRole() {
    ensureSession();
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get the current user's ID from the session.
 * 
 * @return int|null
 */
function currentUserId() {
    ensureSession();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the current user's display name from the session.
 * 
 * @return string|null
 */
function currentUserName() {
    ensureSession();
    return $_SESSION['user_name'] ?? null;
}

/**
 * Require the user to be authenticated (any role).
 * Redirects to /login if not logged in.
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: /bondor-bati/login');
        exit;
    }
}

/**
 * Require the user to have a specific role.
 * 
 * - requireRole('ADMIN')  → Only ADMIN can access.
 * - requireRole('STAFF')  → STAFF and ADMIN can both access.
 * 
 * If the user is not logged in, redirects to /login.
 * If the user lacks the required role, shows a 403 Forbidden page.
 * 
 * @param string $role  The minimum role required ('ADMIN' or 'STAFF').
 */
function requireRole($role) {
    // First, must be logged in
    requireAuth();

    $current = currentRole();

    // ADMIN has access to everything
    if ($current === 'ADMIN') {
        return;
    }

    // STAFF role: allow if the required role is STAFF
    if ($role === 'STAFF' && $current === 'STAFF') {
        return;
    }

    // If we reach here, the user doesn't have permission
    http_response_code(403);
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 Forbidden | Bondor Bati</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    </head>
    <body class="bg-slate-100 font-[Inter] min-h-screen flex items-center justify-center p-4">
        <div class="text-center max-w-sm">
            <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-red-100 flex items-center justify-center">
                <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 mb-2">Access Denied</h1>
            <p class="text-slate-500 mb-6">You don\'t have permission to access this page. This area requires <strong>' . htmlspecialchars($role) . '</strong> privileges.</p>
            <a href="/bondor-bati/" class="inline-block px-6 py-3 bg-slate-900 text-white font-semibold rounded-xl hover:bg-slate-800 transition-colors">
                ← Go Home
            </a>
        </div>
    </body>
    </html>';
    exit;
}
?>
