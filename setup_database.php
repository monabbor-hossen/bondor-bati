<?php
/**
 * Standalone Database Setup Script
 * Run this once via browser or CLI to provision the database and tables.
 */

$host = '127.0.0.1';
$username = 'root';
$password = '';
$dbname = 'bondor_bati';

try {
    // 1. Connect to MySQL without a specific database to create it
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Create the database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '$dbname' verified/created.<br>";
    
    // 3. Connect to the new database
    $pdo->exec("USE `$dbname`");

    // 4. Define table schemas
    $tables = [
        // --- Auth & HR ---
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            access_token VARCHAR(255) DEFAULT NULL,
            role VARCHAR(50) DEFAULT 'staff',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
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
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        // --- Stakeholders & Dues ---
        "CREATE TABLE IF NOT EXISTS suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            contact VARCHAR(100) DEFAULT NULL,
            total_due DECIMAL(12,2) DEFAULT 0.00
        )",
        
        "CREATE TABLE IF NOT EXISTS customer_dues (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            due_amount DECIMAL(12,2) NOT NULL,
            log_date DATE NOT NULL,
            status VARCHAR(50) DEFAULT 'pending'
        )",
        
        // --- Inventory Engine ---
        "CREATE TABLE IF NOT EXISTS items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_name VARCHAR(100) NOT NULL,
            selling_price DECIMAL(10,2) NOT NULL,
            cost_price DECIMAL(10,2) NOT NULL
        )",
        
        "CREATE TABLE IF NOT EXISTS raw_inventory (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_name VARCHAR(100) NOT NULL,
            current_qty DECIMAL(10,2) DEFAULT 0.00,
            avg_unit_price DECIMAL(10,2) DEFAULT 0.00
        )",
        
        "CREATE TABLE IF NOT EXISTS daily_stocks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            log_date DATE NOT NULL,
            carry_forward_qty DECIMAL(10,2) DEFAULT 0.00,
            wastage_qty DECIMAL(10,2) DEFAULT 0.00,
            complimentary_qty DECIMAL(10,2) DEFAULT 0.00,
            fresh_processed_qty DECIMAL(10,2) DEFAULT 0.00,
            opening_qty DECIMAL(10,2) DEFAULT 0.00,
            closing_qty DECIMAL(10,2) DEFAULT 0.00,
            sold_qty DECIMAL(10,2) DEFAULT 0.00,
            total_sales_amount DECIMAL(12,2) DEFAULT 0.00,
            UNIQUE KEY unique_item_date (item_id, log_date),
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
        )",

        // --- Ledger & Spread Costs ---
        "CREATE TABLE IF NOT EXISTS bazaar_ledgers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            log_date DATE NOT NULL,
            advance_cash DECIMAL(12,2) DEFAULT 0.00,
            total_spent DECIMAL(12,2) DEFAULT 0.00
        )",

        "CREATE TABLE IF NOT EXISTS bazaar_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ledger_id INT NOT NULL,
            item_name VARCHAR(100) NOT NULL,
            bought_qty DECIMAL(10,2) DEFAULT 0.00,
            total_price DECIMAL(12,2) DEFAULT 0.00,
            supplier_id INT DEFAULT NULL,
            FOREIGN KEY (ledger_id) REFERENCES bazaar_ledgers(id) ON DELETE CASCADE,
            FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
        )",
        
        "CREATE TABLE IF NOT EXISTS expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            total_amount DECIMAL(12,2) NOT NULL,
            is_spread TINYINT(1) DEFAULT 0,
            daily_amount DECIMAL(10,2) DEFAULT 0.00,
            remaining_balance DECIMAL(12,2) DEFAULT 0.00,
            expense_date DATE NOT NULL
        )",

        // --- Forecasting & Advance Orders ---
        "CREATE TABLE IF NOT EXISTS calendar_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_date DATE NOT NULL,
            event_name VARCHAR(100) NOT NULL,
            impact_multiplier DECIMAL(5,2) DEFAULT 1.00
        )",
        
        "CREATE TABLE IF NOT EXISTS advance_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            delivery_date DATE NOT NULL,
            customer_info TEXT,
            total_bill DECIMAL(12,2) NOT NULL,
            advance_paid DECIMAL(12,2) DEFAULT 0.00,
            status VARCHAR(50) DEFAULT 'pending'
        )",
        
        "CREATE TABLE IF NOT EXISTS advance_order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            item_id INT NOT NULL,
            qty DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES advance_orders(id) ON DELETE CASCADE,
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
        )"
    ];

    // 5. Execute table creation
    foreach ($tables as $index => $sql) {
        $pdo->exec($sql);
        echo "Table block " . ($index + 1) . " created/verified.<br>";
    }

    echo "<br><b>Database setup completed successfully!</b>";

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
