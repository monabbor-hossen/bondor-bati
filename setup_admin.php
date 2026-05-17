<?php
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

$username = 'admin';
$password = '123456789';
// This creates the secure scramble that AuthController is looking for
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // 1. Delete any old broken admin attempts
    $db->exec("DELETE FROM users WHERE username = 'admin'");

    // 2. Insert the perfect, secure admin
    $stmt = $db->prepare("INSERT INTO users (name, username, password, role, is_active) VALUES ('System Admin', :user, :pass, 'ADMIN', 1)");
    $stmt->execute([':user' => $username, ':pass' => $hashed_password]);

    echo "<h2 style='color:green;'>✅ Admin successfully created!</h2>";
    echo "<p>Username: <b>admin</b></p>";
    echo "<p>Password: <b>123456789</b></p>";
    echo "<a href='/bondor_bati_pos/public/login'>Go to Login Page</a>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>