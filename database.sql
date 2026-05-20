-- Restaurant Management System Database Schema
-- Phase 1: Master Database

-- 1. User, Staff & Access (Auth & HR)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255) NOT NULL,
    access_token VARCHAR(255),
    role ENUM('ADMIN', 'STAFF') DEFAULT 'STAFF',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS staff_salaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    monthly_salary DECIMAL(10,2) NOT NULL,
    daily_rate DECIMAL(10,2) GENERATED ALWAYS AS (monthly_salary / 30) STORED,
    start_date DATE NOT NULL,
    end_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS staff_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    page_slug VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_page (user_id, page_slug)
);

CREATE TABLE IF NOT EXISTS attendance_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    absent_date DATE NOT NULL,
    deduct_salary TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. Suppliers & Customer Dues (Stakeholders & Dues)
CREATE TABLE IF NOT EXISTS suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(50),
    total_due DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customer_dues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    due_amount DECIMAL(10,2) NOT NULL,
    log_date DATE NOT NULL,
    shift ENUM('Morning', 'Evening', 'Night') NOT NULL DEFAULT 'Morning',
    status ENUM('Unpaid', 'Paid') DEFAULT 'Unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Inventory, Wastage & Daily Stock (Inventory Engine)
CREATE TABLE IF NOT EXISTS items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_name VARCHAR(100) NOT NULL,
    selling_price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2) NOT NULL,
    min_threshold DECIMAL(10,2) DEFAULT 10.00,
    unit VARCHAR(20) DEFAULT 'pcs',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS raw_inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_name VARCHAR(100) NOT NULL,
    current_qty DECIMAL(10,2) DEFAULT 0,
    avg_unit_price DECIMAL(10,2) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS daily_stocks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    log_date DATE NOT NULL,
    shift ENUM('Morning', 'Evening', 'Night') NOT NULL DEFAULT 'Morning',
    user_id INT NULL,
    carry_forward_qty DECIMAL(10,2) DEFAULT 0,
    wastage_qty DECIMAL(10,2) DEFAULT 0,
    complimentary_qty DECIMAL(10,2) DEFAULT 0,
    fresh_processed_qty DECIMAL(10,2) DEFAULT 0,
    opening_qty DECIMAL(10,2) DEFAULT 0,
    closing_qty DECIMAL(10,2) DEFAULT 0,
    sold_qty DECIMAL(10,2) DEFAULT 0,
    total_sales_amount DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_item_date_shift (item_id, log_date, shift)
);

-- 4. Bazaar, Expenses & Gas (Ledger & Spread Costs)
CREATE TABLE IF NOT EXISTS bazaar_ledgers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    log_date DATE NOT NULL,
    advance_cash DECIMAL(10,2) DEFAULT 0,
    total_spent DECIMAL(10,2) DEFAULT 0,
    return_cash DECIMAL(10,2) DEFAULT 0,
    carry_forward_cash DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (log_date)
);

CREATE TABLE IF NOT EXISTS bazaar_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ledger_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    bought_qty DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) DEFAULT 'pcs',
    total_price DECIMAL(10,2) NOT NULL,
    supplier_id INT,
    FOREIGN KEY (ledger_id) REFERENCES bazaar_ledgers(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category ENUM('Gas', 'Fixed', 'Asset') NOT NULL,
    name VARCHAR(100) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    is_spread TINYINT(1) DEFAULT 0,
    daily_amount DECIMAL(10,2) DEFAULT 0,
    remaining_balance DECIMAL(10,2) DEFAULT 0,
    expense_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Forecasting & Advance Orders (Smart Features)
CREATE TABLE IF NOT EXISTS calendar_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_date DATE NOT NULL,
    event_name VARCHAR(100) NOT NULL,
    impact_multiplier DECIMAL(3,2) DEFAULT 1.00,
    UNIQUE KEY unique_event_date (event_date)
);

CREATE TABLE IF NOT EXISTS advance_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    delivery_date DATE NOT NULL,
    customer_info VARCHAR(200) NOT NULL,
    total_bill DECIMAL(10,2) NOT NULL,
    advance_paid DECIMAL(10,2) DEFAULT 0,
    status ENUM('Pending', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS advance_order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    qty INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES advance_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);