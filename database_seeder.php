<?php
/**
 * Database Seeder - Phase 5
 * Populates the database with initial real-world test data.
 * Run this script once via browser to seed data.
 */

$host = '127.0.0.1';
$username = 'root';
$password = '';
$dbname = 'bondor_bati';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database.<br><br>";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "<br><br>Please run setup_database.php first.");
}

echo "<strong>Starting Seeding...</strong><br><br>";

// 1. AUTH - Users
echo "1. Inserting users...<br>";
$users = [
    ['name' => 'System Admin', 'username' => 'admin', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'admin'],
    ['name' => 'Shop Staff', 'username' => 'staff', 'password' => password_hash('staff123', PASSWORD_DEFAULT), 'role' => 'staff'],
];

$stmt = $pdo->prepare("INSERT INTO users (name, username, password, role, is_active) VALUES (:name, :username, :password, :role, 1)");
foreach ($users as $user) {
    $stmt->execute($user);
    echo "  - Created user: {$user['username']} ({$user['role']})<br>";
}

// 1b. STAFF SALARIES
echo "<br>1b. Inserting staff salaries...<br>";
$salaries = [
    ['user_id' => 1, 'monthly_salary' => 35000.00, 'daily_rate' => 1166.67],
    ['user_id' => 2, 'monthly_salary' => 18000.00, 'daily_rate' => 600.00],
];
$stmt = $pdo->prepare("INSERT INTO staff_salaries (user_id, monthly_salary, daily_rate, start_date) VALUES (:user_id, :monthly_salary, :daily_rate, CURDATE())");
foreach ($salaries as $sal) {
    $stmt->execute($sal);
    echo "  - Salary set for user_id {$sal['user_id']}<br>";
}

// 2. SUPPLIERS
echo "<br>2. Inserting suppliers...<br>";
$suppliers = [
    ['name' => 'Bagabarir Ghee Supplier', 'contact' => '01712345678'],
    ['name' => 'Regular Milkman', 'contact' => '01987654321'],
];

$stmt = $pdo->prepare("INSERT INTO suppliers (name, contact, total_due) VALUES (:name, :contact, 0)");
foreach ($suppliers as $supplier) {
    $stmt->execute($supplier);
    echo "  - Added supplier: {$supplier['name']}<br>";
}

// 3. INVENTORY ENGINE - Items (Menu)
echo "<br>3. Inserting menu items...<br>";
$items = [
    ['item_name' => 'BBQ Tilapia', 'selling_price' => 180.00, 'cost_price' => 110.00],
    ['item_name' => 'Chicken Sandwich', 'selling_price' => 150.00, 'cost_price' => 85.00],
];

$stmt = $pdo->prepare("INSERT INTO items (item_name, selling_price, cost_price) VALUES (:item_name, :selling_price, :cost_price)");
foreach ($items as $item) {
    $stmt->execute($item);
    echo "  - Added menu item: {$item['item_name']}<br>";
}

// 4. RAW INVENTORY
echo "<br>4. Inserting raw inventory...<br>";
$rawItems = [
    ['item_name' => 'Raw Tilapia', 'current_qty' => 15.00, 'avg_unit_price' => 120.00],
    ['item_name' => 'Marination Spice', 'current_qty' => 5.00, 'avg_unit_price' => 450.00],
    ['item_name' => 'Ghee', 'current_qty' => 3.00, 'avg_unit_price' => 800.00],
];

$stmt = $pdo->prepare("INSERT INTO raw_inventory (item_name, current_qty, avg_unit_price) VALUES (:item_name, :current_qty, :avg_unit_price)");
foreach ($rawItems as $raw) {
    $stmt->execute($raw);
    echo "  - Added raw item: {$raw['item_name']}<br>";
}

// 5. EXPENSES - Fixed Costs
echo "<br>5. Inserting standard expenses...<br>";
$expenses = [
    ['category' => 'Gas', 'name' => 'Daily Gas', 'total_amount' => 1800.00, 'is_spread' => 1, 'daily_amount' => 60.00, 'remaining_balance' => 1500.00],
    ['category' => 'Rent', 'name' => 'Cart Rent', 'total_amount' => 30000.00, 'is_spread' => 1, 'daily_amount' => 1000.00, 'remaining_balance' => 25000.00],
    ['category' => 'Utilities', 'name' => 'Electricity Bill', 'total_amount' => 4500.00, 'is_spread' => 0, 'daily_amount' => 0, 'remaining_balance' => 0],
];

$stmt = $pdo->prepare("INSERT INTO expenses (category, name, total_amount, is_spread, daily_amount, remaining_balance, expense_date) VALUES (:category, :name, :total_amount, :is_spread, :daily_amount, :remaining_balance, CURDATE())");
foreach ($expenses as $exp) {
    $stmt->execute($exp);
    echo "  - Added expense: {$exp['name']}<br>";
}

// 6. FORECASTING - Calendar Events
echo "<br>6. Inserting calendar event...<br>";
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$stmt = $pdo->prepare("INSERT INTO calendar_events (event_date, event_name, impact_multiplier) VALUES (:event_date, :event_name, :impact_multiplier)");
$stmt->execute([
    'event_date' => $tomorrow,
    'event_name' => 'Agargaon Local Fair',
    'impact_multiplier' => 1.5
]);
echo "  - Added event for $tomorrow: Agargaon Local Fair (×1.5)<br>";

echo "<br><hr><br>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Seeder</title>
    <style>
        body { font-family: -apple-system, sans-serif; background: #0f0f1a; color: #eaeaea; padding: 2rem; }
        .success { background: rgba(46,204,113,0.2); border: 1px solid #2ecc71; padding: 1.5rem; border-radius: 12px; text-align: center; }
        a { color: #e94560; font-weight: bold; }
    </style>
</head>
<body>
    <div class="success">
        <h2>✅ Database Successfully Seeded!</h2>
        <p>Go to <a href="index.php">Login</a></p>
        <p style="font-size: 0.85rem; color: #8899aa; margin-top: 1rem;">
            Default Admin: admin / password123<br>
            Default Staff: staff / staff123
        </p>
    </div>
</body>
</html>