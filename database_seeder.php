<?php
/**
 * Database Seeder — Populates test data for development
 * Run once: http://localhost/bondor-bati/database_seeder.php
 */

$host = '127.0.0.1';
$username = 'root';
$password = '';
$dbname = 'bondor_bati';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div style='background:#0a0a0f;color:#e2e8f0;font-family:monospace;padding:2rem;min-height:100vh;'>";
    echo "<h2 style='color:#10b981;'>🌱 Seeding Database...</h2><br>";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// 1. Menu Items (Sellable)
echo "<strong>1. Menu Items</strong><br>";
$items = [
    ['BBQ Tilapia',       'বিবিকিউ তেলাপিয়া',      180, 110, 'plate', 5],
    ['Chicken Sandwich',  'চিকেন স্যান্ডউইচ',      150,  85, 'plate', 5],
    ['Beef Burger',       'বিফ বার্গার',             200, 120, 'plate', 3],
    ['Fish Fry',          'মাছ ভাজা',                120,  70, 'plate', 8],
    ['Chicken Wings',     'চিকেন উইংস',             180, 100, 'plate', 5],
    ['Egg Roll',          'ডিম রোল',                  80,  40, 'plate', 10],
];

$stmt = $pdo->prepare("
    INSERT IGNORE INTO items (item_name, item_name_bn, selling_price, cost_price, unit, min_stock_threshold, sort_order)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
foreach ($items as $i => $item) {
    $stmt->execute([$item[0], $item[1], $item[2], $item[3], $item[4], $item[5], $i + 1]);
    echo "  ✅ {$item[0]}<br>";
}

// 2. Raw Inventory
echo "<br><strong>2. Raw Inventory</strong><br>";
$raw = [
    ['Raw Tilapia',     'কাঁচা তেলাপিয়া',   15, 'kg', 120, 5],
    ['Chicken Breast',  'চিকেন ব্রেস্ট',    10, 'kg', 350, 3],
    ['Beef Mince',      'বিফ কিমা',           8, 'kg', 600, 2],
    ['Cooking Oil',     'রান্নার তেল',        5, 'ltr', 180, 2],
    ['Bread Buns',      'ব্রেড বান',          50, 'pcs', 15, 20],
    ['Eggs',            'ডিম',                30, 'pcs', 12, 15],
    ['Spice Mix',       'মশলা মিক্স',         3, 'kg', 450, 1],
];

$rawStmt = $pdo->prepare("
    INSERT IGNORE INTO raw_inventory (item_name, item_name_bn, current_qty, unit, avg_unit_price, min_stock_threshold)
    VALUES (?, ?, ?, ?, ?, ?)
");
foreach ($raw as $r) {
    $rawStmt->execute([$r[0], $r[1], $r[2], $r[3], $r[4], $r[5]]);
    echo "  ✅ {$r[0]}<br>";
}

// 3. Suppliers
echo "<br><strong>3. Suppliers</strong><br>";
$suppliers = [
    ['Kamal Fish Market', 'কামাল ফিশ মার্কেট', '01712345678', 500],
    ['Rahim Chicken',     'রহিম চিকেন',       '01987654321', 0],
    ['City Grocery',      'সিটি গ্রোসারি',    '01711223344', 200],
];

$supStmt = $pdo->prepare("INSERT IGNORE INTO suppliers (name, name_bn, contact, total_due) VALUES (?, ?, ?, ?)");
foreach ($suppliers as $s) {
    $supStmt->execute($s);
    echo "  ✅ {$s[0]}<br>";
}

// 4. Gas Spread Expense
echo "<br><strong>4. Gas Spread Expense</strong><br>";
$pdo->exec("
    INSERT IGNORE INTO expenses (id, category, name, name_bn, total_amount, is_spread, daily_amount, remaining_balance, expense_date, is_active)
    VALUES (1, 'gas', 'LPG Cylinder', 'এলপিজি সিলিন্ডার', 3500.00, 1, 175.00, 2800.00, '" . date('Y-m-d', strtotime('-4 days')) . "', 1)
");
echo "  ✅ Gas cylinder (৳3500, ৳175/day)<br>";

// 5. Calendar Events
echo "<br><strong>5. Calendar Events</strong><br>";
$events = [
    [date('Y-m-d', strtotime('next friday')),  'Jumu\'ah Friday', 'জুমু\'আ শুক্রবার', 1.30],
    [date('Y-m-d', strtotime('+14 days')),     'Pahela Baishakh',  'পহেলা বৈশাখ',     2.00],
];
$evtStmt = $pdo->prepare("INSERT IGNORE INTO calendar_events (event_date, event_name, event_name_bn, impact_multiplier) VALUES (?, ?, ?, ?)");
foreach ($events as $e) {
    $evtStmt->execute($e);
    echo "  ✅ {$e[1]} ({$e[3]}x)<br>";
}

// 6. Seed Historical Data (7 days)
echo "<br><strong>6. Historical Sales Data (7 days)</strong><br>";
$itemIds = $pdo->query("SELECT id FROM items ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
$shifts = ['morning', 'evening', 'night'];

for ($d = 7; $d >= 1; $d--) {
    $date = date('Y-m-d', strtotime("-{$d} days"));

    // Daily stocks
    foreach ($itemIds as $itemId) {
        $opening = rand(15, 30);
        $wastage = rand(0, 2);
        $fresh   = rand(10, 20);
        $openingQty = ($opening - $wastage) + $fresh;

        $pdo->prepare("
            INSERT IGNORE INTO daily_stocks (item_id, log_date, carry_forward_qty, wastage_qty, fresh_processed_qty, opening_qty)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$itemId, $date, $opening, $wastage, $fresh, $openingQty]);

        // Shift closings
        $remaining = $openingQty;
        foreach ($shifts as $shift) {
            $sold = rand(3, (int)($remaining * 0.4));
            $comp = rand(0, 1);
            $closing = max(0, $remaining - $sold - $comp);

            $priceStmt = $pdo->prepare("SELECT selling_price FROM items WHERE id = ?");
            $priceStmt->execute([$itemId]);
            $price = (float)$priceStmt->fetchColumn();

            $pdo->prepare("
                INSERT IGNORE INTO shift_closings (item_id, log_date, shift, closing_qty, complimentary_qty, sold_qty, total_sales_amount)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([$itemId, $date, $shift, $closing, $comp, $sold, $sold * $price]);

            $remaining = $closing;
        }
    }

    // Bazaar ledger
    $advance = rand(1500, 3000);
    $spent   = rand(1000, $advance + 500);
    $balance = $advance - $spent;

    $pdo->prepare("
        INSERT IGNORE INTO bazaar_ledgers (log_date, advance_cash, total_spent, returned_cash, carry_forward, staff_due, status)
        VALUES (?, ?, ?, 0, ?, ?, 'closed')
    ")->execute([$date, $advance, $spent, max(0, $balance), $balance < 0 ? abs($balance) : 0]);

    echo "  ✅ Day: {$date}<br>";
}

echo "<br><strong style='color:#10b981;'>🎉 Seeding completed!</strong>";
echo "<br><br><a href='?url=dashboard' style='color:#f43f5e;font-weight:bold;'>→ Go to Dashboard</a>";
echo "</div>";
