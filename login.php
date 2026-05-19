<?php
/**
 * Login Handler
 * Handles admin login (username + password) via POST.
 */
session_start();

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/core/Auth.php';

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['login_type'] ?? '') === 'admin') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :u AND is_active = 1 LIMIT 1");
        $stmt->execute(['u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_token'] = $user['access_token'];

            // Load page permissions into session
            loadPermissions($user['id'], $user['role']);

            header('Location: index.php');
            exit;
        } else {
            $loginError = 'Invalid username or password.';
        }
    } else {
        $loginError = 'Please enter both username and password.';
    }
}

// Show login page
include __DIR__ . '/views/auth/login.php';
