<?php
/**
 * Admin Setup — Create the initial admin account
 * Run once: http://localhost/bondor-bati/setup_admin.php
 */

$host     = '127.0.0.1';
$username = 'root';
$password = '';
$dbname   = 'bondor_bati';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if admin already exists
    $check = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($check > 0) {
        echo "<p style='color:orange;'>Admin already exists. Delete manually to re-create.</p>";
        exit;
    }

    // Create admin
    $stmt = $pdo->prepare("
        INSERT INTO users (name, name_bn, username, password, role, is_active)
        VALUES (:name, :name_bn, :username, :password, 'admin', 1)
    ");
    $stmt->execute([
        ':name'    => 'Admin',
        ':name_bn' => 'অ্যাডমিন',
        ':username'=> 'admin',
        ':password'=> password_hash('admin123', PASSWORD_DEFAULT),
    ]);

    $adminId = $pdo->lastInsertId();

    // Set admin salary
    $pdo->prepare("
        INSERT INTO staff_salaries (user_id, monthly_salary, daily_rate, start_date)
        VALUES (:uid, 35000, 1166.67, CURDATE())
    ")->execute([':uid' => $adminId]);

    // Create a staff member
    $stmt2 = $pdo->prepare("
        INSERT INTO users (name, name_bn, username, password, role, is_active)
        VALUES (:name, :name_bn, NULL, NULL, 'staff', 1)
    ");
    $stmt2->execute([
        ':name'    => 'Karim',
        ':name_bn' => 'করিম',
    ]);

    $staffId = $pdo->lastInsertId();

    // Staff salary
    $pdo->prepare("
        INSERT INTO staff_salaries (user_id, monthly_salary, daily_rate, start_date)
        VALUES (:uid, 15000, 500, CURDATE())
    ")->execute([':uid' => $staffId]);

    echo "<div style='background:#0a0a0f;color:#e2e8f0;font-family:monospace;padding:2rem;min-height:100vh;'>";
    echo "<h2 style='color:#10b981;'>✅ Admin Created</h2>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<br>";
    echo "<p style='color:#64748b;'>Staff user 'Karim' created (magic link only, no password).</p>";
    echo "<br>";
    echo "<a href='?url=auth/login' style='color:#f43f5e;font-weight:bold;'>→ Go to Login</a>";
    echo "</div>";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
