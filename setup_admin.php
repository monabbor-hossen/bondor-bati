<?php
/**
 * Admin & Seed Data Setup Script
 * Run once via browser: http://localhost:8000/setup_admin.php
 * Seeds: admin user, sample items, and raw inventory.
 * DELETE this file after running in production.
 */

$host   = '127.0.0.1';
$dbname = 'bondor_bati';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ── 1. Create Admin User ───────────────────────────────────────────
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);

    $pdo->prepare("
        INSERT IGNORE INTO users (name, username, password, role, is_active)
        VALUES (:name, :username, :password, :role, 1)
    ")->execute([
        ':name'     => 'Admin',
        ':username' => 'admin',
        ':password' => $adminPassword,
        ':role'     => 'admin',
    ]);
    echo "✅ Admin user created (username: <b>admin</b> / password: <b>admin123</b>)<br>";

    // ── 2. Seed Sellable Items ─────────────────────────────────────────
    $items = [
        ['Roast Chicken (Full)',  450.00, 280.00],
        ['Roast Chicken (Half)',  250.00, 150.00],
        ['Chicken Biriyani',      180.00, 100.00],
        ['Beef Tehari',           200.00, 120.00],
        ['Special Mixed Platter', 380.00, 220.00],
    ];

    $stmtItem = $pdo->prepare("
        INSERT IGNORE INTO items (item_name, selling_price, cost_price)
        VALUES (:item_name, :selling_price, :cost_price)
    ");

    foreach ($items as [$name, $sell, $cost]) {
        $stmtItem->execute([':item_name' => $name, ':selling_price' => $sell, ':cost_price' => $cost]);
    }
    echo "✅ " . count($items) . " sellable items seeded.<br>";

    // ── 3. Seed Raw Inventory ──────────────────────────────────────────
    $rawItems = [
        ['Whole Chicken (kg)',  50.00, 185.00],
        ['Beef (kg)',           20.00, 650.00],
        ['Basmati Rice (kg)',   30.00, 120.00],
        ['Spice Mix (kg)',       5.00, 300.00],
        ['Cooking Oil (L)',     10.00,  95.00],
    ];

    $stmtRaw = $pdo->prepare("
        INSERT IGNORE INTO raw_inventory (item_name, current_qty, avg_unit_price)
        VALUES (:item_name, :current_qty, :avg_unit_price)
    ");

    foreach ($rawItems as [$name, $qty, $price]) {
        $stmtRaw->execute([':item_name' => $name, ':current_qty' => $qty, ':avg_unit_price' => $price]);
    }
    echo "✅ " . count($rawItems) . " raw inventory items seeded.<br>";

    // ── 4. Seed a Sample Supplier ──────────────────────────────────────
    $pdo->prepare("
        INSERT IGNORE INTO suppliers (name, contact, total_due)
        VALUES ('Dhaka Poultry Market', '01700-000000', 0.00)
    ")->execute();
    echo "✅ Sample supplier seeded.<br>";

    // ── 5. Seed a Sample Advance Order (due today) ─────────────────────
    $pdo->prepare("
        INSERT IGNORE INTO advance_orders (delivery_date, customer_info, total_bill, advance_paid, status)
        VALUES (:date, :info, :bill, :advance, 'pending')
    ")->execute([
        ':date'    => date('Y-m-d'),
        ':info'    => 'Rahul Ahmed — 01800-123456',
        ':bill'    => 1500.00,
        ':advance' => 500.00,
    ]);
    echo "✅ Sample pending advance order for today seeded.<br>";

    echo "<br><hr><b>🎉 All seed data inserted successfully!</b><br>";
    echo "<a href='?url=home'>→ Go to Dashboard</a>";

} catch (PDOException $e) {
    die("❌ Seed failed: " . $e->getMessage());
}
