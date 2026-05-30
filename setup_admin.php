<?php
// ── Setup Guard ── Remove this file after initial deploy ──────────
if (!defined('SETUP_ALLOWED') && php_uname('n') !== 'localhost') {
    http_response_code(403); die('403 Forbidden — Remove setup files after deploy.');
}
/**
 * Admin Setup — Creates the initial admin account with a RANDOMLY generated password.
 * Run ONCE via browser: http://localhost/bondor-bati/setup_admin.php
 * DELETE this file immediately after running.
 */

$host   = getenv('DB_HOST') ?: '127.0.0.1';
$dbname = getenv('DB_NAME') ?: 'bondor_bati';
$dbuser = getenv('DB_USER') ?: 'root';
$dbpass = getenv('DB_PASS') ?: '';

echo "<div style='background:#0a0a0f;color:#e2e8f0;font-family:monospace;padding:2rem;min-height:100vh;'>";
echo "<h2 style='color:#f43f5e;'>⚙️ Admin Account Setup</h2><br>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $check = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($check > 0) {
        echo "<p style='color:orange;'>⚠️  An admin account already exists. Delete it manually before running this again.</p>";
        exit;
    }

    // Read admin name from query string or use default
    $adminName = htmlspecialchars(trim($_GET['name'] ?? 'Admin'));
    $adminUser = htmlspecialchars(trim($_GET['username'] ?? 'admin'));

    // Generate a cryptographically random 16-char password
    $rawPassword = bin2hex(random_bytes(8)); // 16 hex chars

    $stmt = $pdo->prepare("
        INSERT INTO users (name, name_bn, username, password, role, is_active)
        VALUES (:name, :name_bn, :username, :password, 'admin', 1)
    ");
    $stmt->execute([
        ':name'     => $adminName,
        ':name_bn'  => 'অ্যাডমিন',
        ':username' => $adminUser,
        ':password' => password_hash($rawPassword, PASSWORD_DEFAULT),
    ]);

    echo "<p style='color:#10b981;'>✅ Admin created successfully.</p>";
    echo "<p><strong>Username:</strong> " . htmlspecialchars($adminUser) . "</p>";
    echo "<p><strong>Password:</strong> <code style='color:#f43f5e;font-size:1.2rem;background:#1a1a26;padding:4px 8px;border-radius:6px;'>{$rawPassword}</code></p>";
    echo "<br><p style='color:#f59e0b;'>⚠️  Save this password immediately — it will NOT be shown again.</p>";
    echo "<p style='color:#f59e0b;'>🔒 DELETE this file from the server immediately after noting your password.</p>";

} catch (PDOException $e) {
    error_log('setup_admin error: ' . $e->getMessage());
    echo "<p style='color:red;'>❌ Database error. Check server error logs.</p>";
}

echo "</div>";
