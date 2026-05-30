<?php
// ── Setup Guard ── Remove this file after initial deploy ──────────
if (!defined('SETUP_ALLOWED') && php_uname('n') !== 'localhost') {
    http_response_code(403); die('403 Forbidden — Remove setup files after deploy.');
}
/**
 * Bondor Bati POS v2.0 — Database Setup
 * Run once via browser: http://localhost/bondor-bati/setup_database.php
 * Supports: 3-shift system, magic links, spread costs, low-stock alerts, bilingual i18n
 */

$host     = '127.0.0.1';
$username = 'root';
$password = '';
$dbname   = 'bondor_bati';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database '$dbname' ready.<br>";

    $pdo->exec("USE `$dbname`");

    $tables = [
        // ── AUTH & USERS ──────────────────────────────────────────
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            name_bn VARCHAR(100) DEFAULT NULL,
            username VARCHAR(50) DEFAULT NULL UNIQUE,
            password VARCHAR(255) DEFAULT NULL,
            role ENUM('admin','staff') DEFAULT 'staff',
            is_active TINYINT(1) DEFAULT 1,
            permissions JSON DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        "CREATE TABLE IF NOT EXISTS magic_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",

        // ── HR & PAYROLL ──────────────────────────────────────────
        "CREATE TABLE IF NOT EXISTS staff_salaries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            monthly_salary DECIMAL(10,2) NOT NULL,
            daily_rate DECIMAL(10,2) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS attendance_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            absent_date DATE NOT NULL,
            deduct_salary DECIMAL(10,2) DEFAULT 0.00,
            note VARCHAR(255) DEFAULT NULL,
            UNIQUE KEY unique_user_date (user_id, absent_date),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",

        // ── SELLABLE ITEMS (MENU) ─────────────────────────────────
        "CREATE TABLE IF NOT EXISTS items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_name VARCHAR(100) NOT NULL,
            item_name_bn VARCHAR(100) DEFAULT NULL,
            linked_raw_item VARCHAR(100) DEFAULT NULL,
            selling_price DECIMAL(10,2) NOT NULL,
            online_price DECIMAL(10,2) DEFAULT 0,
            cost_price DECIMAL(10,2) NOT NULL,
            additional_cost DECIMAL(10,2) DEFAULT 0,
            unit VARCHAR(30) DEFAULT 'plate',
            min_stock_threshold DECIMAL(10,2) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0
        )",

        // ── RAW MATERIALS INVENTORY ───────────────────────────────
        "CREATE TABLE IF NOT EXISTS raw_inventory (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_name VARCHAR(100) NOT NULL,
            item_name_bn VARCHAR(100) DEFAULT NULL,
            current_qty DECIMAL(10,2) DEFAULT 0.00,
            unit VARCHAR(30) DEFAULT 'kg',
            avg_unit_price DECIMAL(10,2) DEFAULT 0.00,
            min_stock_threshold DECIMAL(10,2) DEFAULT 0
        )",

        // ── DAILY STOCK TRACKING (MORNING PREP) ──────────────────
        "CREATE TABLE IF NOT EXISTS daily_stocks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            log_date DATE NOT NULL,
            carry_forward_qty DECIMAL(10,2) DEFAULT 0.00,
            wastage_qty DECIMAL(10,2) DEFAULT 0.00,
            fresh_processed_qty DECIMAL(10,2) DEFAULT 0.00,
            opening_qty DECIMAL(10,2) DEFAULT 0.00,
            UNIQUE KEY unique_item_date (item_id, log_date),
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
        )",

        // ── 3-SHIFT CLOSING SYSTEM ───────────────────────────────
        "CREATE TABLE IF NOT EXISTS shift_closings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            log_date DATE NOT NULL,
            shift ENUM('morning','evening','night') NOT NULL,
            user_id INT DEFAULT NULL,
            closing_qty DECIMAL(10,2) DEFAULT 0.00,
            complimentary_qty DECIMAL(10,2) DEFAULT 0.00,
            due_qty DECIMAL(10,2) DEFAULT 0.00,
            sold_qty DECIMAL(10,2) DEFAULT 0.00,
            total_sales_amount DECIMAL(12,2) DEFAULT 0.00,
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_shift (item_id, log_date, shift),
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )",

        // ── CUSTOMER DUES (BAKI) ─────────────────────────────────
        "CREATE TABLE IF NOT EXISTS customer_dues (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            due_amount DECIMAL(12,2) NOT NULL,
            log_date DATE NOT NULL,
            shift ENUM('morning','evening','night') DEFAULT NULL,
            item_id INT DEFAULT NULL,
            qty DECIMAL(10,2) DEFAULT 0,
            status ENUM('pending','paid') DEFAULT 'pending',
            paid_date DATE DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL
        )",

        // ── SUPPLIERS ─────────────────────────────────────────────
        "CREATE TABLE IF NOT EXISTS suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            name_bn VARCHAR(100) DEFAULT NULL,
            contact VARCHAR(100) DEFAULT NULL,
            total_due DECIMAL(12,2) DEFAULT 0.00
        )",

        // ── BAZAAR LEDGER ─────────────────────────────────────────
        "CREATE TABLE IF NOT EXISTS bazaar_ledgers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            log_date DATE NOT NULL UNIQUE,
            advance_cash DECIMAL(12,2) DEFAULT 0.00,
            total_spent DECIMAL(12,2) DEFAULT 0.00,
            returned_cash DECIMAL(12,2) DEFAULT 0.00,
            carry_forward DECIMAL(12,2) DEFAULT 0.00,
            staff_due DECIMAL(12,2) DEFAULT 0.00,
            status ENUM('open','closed') DEFAULT 'open'
        )",

        "CREATE TABLE IF NOT EXISTS bazaar_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ledger_id INT NOT NULL,
            item_name VARCHAR(100) NOT NULL,
            bought_qty DECIMAL(10,2) DEFAULT 0.00,
            unit VARCHAR(30) DEFAULT 'kg',
            unit_price DECIMAL(10,2) DEFAULT 0.00,
            total_price DECIMAL(12,2) DEFAULT 0.00,
            FOREIGN KEY (ledger_id) REFERENCES bazaar_ledgers(id) ON DELETE CASCADE
        )",

        // ── EXPENSES & SPREAD COSTS ──────────────────────────────
        "CREATE TABLE IF NOT EXISTS expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            name_bn VARCHAR(100) DEFAULT NULL,
            total_amount DECIMAL(12,2) NOT NULL,
            is_spread TINYINT(1) DEFAULT 0,
            daily_amount DECIMAL(10,2) DEFAULT 0.00,
            remaining_balance DECIMAL(12,2) DEFAULT 0.00,
            expense_date DATE NOT NULL,
            is_active TINYINT(1) DEFAULT 1
        )",

        "CREATE TABLE IF NOT EXISTS daily_expense_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            expense_id INT NOT NULL,
            log_date DATE NOT NULL,
            deducted_amount DECIMAL(10,2) NOT NULL,
            remaining_after DECIMAL(12,2) NOT NULL,
            UNIQUE KEY unique_exp_date (expense_id, log_date),
            FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE
        )",

        // ── FIXED DAILY COSTS (RENT, ETC.) ────────────────────────
        "CREATE TABLE IF NOT EXISTS fixed_daily_costs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            name_bn VARCHAR(100) DEFAULT NULL,
            daily_amount DECIMAL(10,2) NOT NULL,
            is_active TINYINT(1) DEFAULT 1
        )",

        // ── CALENDAR EVENTS & FORECASTING ─────────────────────────
        "CREATE TABLE IF NOT EXISTS calendar_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_date DATE NOT NULL,
            event_name VARCHAR(100) NOT NULL,
            event_name_bn VARCHAR(100) DEFAULT NULL,
            impact_multiplier DECIMAL(5,2) DEFAULT 1.00,
            notes TEXT DEFAULT NULL
        )",

        // ── ADVANCE ORDERS ────────────────────────────────────────
        "CREATE TABLE IF NOT EXISTS advance_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            delivery_date DATE NOT NULL,
            customer_info TEXT,
            phone VARCHAR(20) DEFAULT NULL,
            total_bill DECIMAL(12,2) NOT NULL,
            advance_paid DECIMAL(12,2) DEFAULT 0.00,
            status ENUM('pending','delivered','cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        "CREATE TABLE IF NOT EXISTS advance_order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            item_id INT NOT NULL,
            qty DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES advance_orders(id) ON DELETE CASCADE,
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
        )",

        // ── OFFLINE SYNC QUEUE ────────────────────────────────────
        "CREATE TABLE IF NOT EXISTS sync_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action_type VARCHAR(50) NOT NULL,
            payload JSON NOT NULL,
            synced_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        // ── APP SETTINGS ──────────────────────────────────────────
        "CREATE TABLE IF NOT EXISTS app_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
    ];

    foreach ($tables as $i => $sql) {
        $pdo->exec($sql);
        echo "✅ Table block " . ($i + 1) . " created.<br>";
    }

    // Insert default settings
    $defaults = [
        ['business_name', 'Bondor Bati'],
        ['business_name_bn', 'বন্দর বাটি'],
        ['currency', '৳'],
        ['default_lang', 'bn'],
        ['shifts', 'morning,evening,night'],
    ];

    $settingStmt = $pdo->prepare("INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaults as $d) {
        $settingStmt->execute($d);
    }

    // Insert default fixed daily cost (rent)
    $pdo->exec("INSERT IGNORE INTO fixed_daily_costs (id, name, name_bn, daily_amount) VALUES (1, 'Shop Rent', 'দোকান ভাড়া', 500.00)");

    echo "<br><strong style='color:lime;'>🎉 Database setup completed!</strong>";

} catch (PDOException $e) {
    die("<strong style='color:red;'>❌ Setup failed:</strong> " . $e->getMessage());
}
