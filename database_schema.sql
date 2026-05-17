-- 1. AUTH & HR
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    access_token VARCHAR(100) UNIQUE,
    role ENUM('ADMIN', 'STAFF') DEFAULT 'STAFF',
    is_active BOOLEAN DEFAULT 1
);

CREATE TABLE staff_salaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    monthly_salary DECIMAL(10,2),
    daily_rate DECIMAL(10,2),
    start_date DATE,
    end_date DATE NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 2. STAKEHOLDERS
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    contact VARCHAR(20),
    total_due DECIMAL(10,2) DEFAULT 0.00
);

CREATE TABLE customer_dues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100),
    phone VARCHAR(20),
    due_amount DECIMAL(10,2),
    log_date DATE,
    status ENUM('Unpaid', 'Paid') DEFAULT 'Unpaid'
);

-- 3. INVENTORY ENGINE
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    selling_price DECIMAL(10,2),
    cost_price DECIMAL(10,2)
);

CREATE TABLE raw_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100),
    current_qty DECIMAL(10,2) DEFAULT 0,
    avg_unit_price DECIMAL(10,2) DEFAULT 0
);

CREATE TABLE daily_stocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT,
    log_date DATE,
    carry_forward_qty INT DEFAULT 0,
    wastage_qty INT DEFAULT 0,
    complimentary_qty INT DEFAULT 0,
    fresh_processed_qty INT DEFAULT 0,
    opening_qty INT GENERATED ALWAYS AS ((carry_forward_qty - wastage_qty) + fresh_processed_qty) STORED,
    closing_qty INT DEFAULT 0,
    sold_qty INT GENERATED ALWAYS AS (opening_qty - closing_qty - complimentary_qty) STORED,
    total_sales_amount DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (item_id) REFERENCES items(id)
);

-- 4. BAZAAR & EXPENSES
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('Gas', 'Fixed', 'Asset', 'Utility'),
    name VARCHAR(100),
    total_amount DECIMAL(10,2),
    is_spread BOOLEAN DEFAULT 0,
    daily_amount DECIMAL(10,2) DEFAULT 0,
    remaining_balance DECIMAL(10,2) DEFAULT 0,
    expense_date DATE
);

-- 5. BAZAAR LEDGER (Daily Market Purchases)
CREATE TABLE bazaar_ledgers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ledger_date DATE NOT NULL,
    total_spent DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bazaar_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ledger_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    bought_qty DECIMAL(10,2) DEFAULT 0,
    total_price DECIMAL(10,2) DEFAULT 0,
    supplier_id INT NULL,
    FOREIGN KEY (ledger_id) REFERENCES bazaar_ledgers(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- 6. FORECASTING & ORDERS
CREATE TABLE calendar_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_date DATE,
    event_name VARCHAR(100),
    impact_multiplier DECIMAL(3,2) DEFAULT 1.00
);

-- 7. ADVANCE ORDERS
CREATE TABLE advance_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_date DATE NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20),
    total_bill DECIMAL(10,2) DEFAULT 0.00,
    advance_paid DECIMAL(10,2) DEFAULT 0.00,
    remaining_due DECIMAL(10,2) GENERATED ALWAYS AS (total_bill - advance_paid) STORED,
    status ENUM('Pending', 'Preparing', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE advance_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES advance_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id)
);

-- Insert Sample Baseline Data
INSERT INTO items (item_name, selling_price, cost_price) VALUES ('BBQ Telapia', 150.00, 80.00);
INSERT INTO suppliers (name, contact, total_due) VALUES ('Bagabarir Ghee Supplier', '01700000000', 0.00);
