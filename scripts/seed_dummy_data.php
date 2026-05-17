<?php
/**
 * Database Seeder for Forecasting & Reporting
 * Generates 30 days of realistic historical data.
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Disable foreign key checks to allow truncation
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // 1. Truncate operational tables
    $tables_to_truncate = [
        'daily_stocks',
        'bazaar_ledgers',
        'bazaar_items',
        'expenses',
        'customer_dues'
    ];
    
    foreach ($tables_to_truncate as $table) {
        $db->exec("TRUNCATE TABLE $table");
        echo "Truncated table: $table\n";
    }
    
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');

    // Fetch available items
    $stmt_items = $db->query("SELECT id, item_name, selling_price FROM items");
    $items = $stmt_items->fetchAll();

    if (empty($items)) {
        die("No items found in the database. Please insert items first.\n");
    }

    $start_date = new DateTime('-30 days');
    $end_date   = new DateTime('-1 day');
    
    // Track gas expenses
    $gas_days = [
        (new DateTime('-28 days'))->format('Y-m-d'),
        (new DateTime('-18 days'))->format('Y-m-d'),
        (new DateTime('-8 days'))->format('Y-m-d')
    ];

    echo "\nSeeding 30 days of data...\n";

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start_date, $interval, $end_date->modify('+1 day'));

    foreach ($period as $date_obj) {
        $current_date = $date_obj->format('Y-m-d');
        
        // ── Seed Daily Stocks ──────────────────────────────────────────
        foreach ($items as $item) {
            // Randomize input to generate reasonable opening_qty and sold_qty
            $fresh_processed = rand(20, 50);
            $closing_qty     = rand(0, 5);
            $complimentary   = rand(0, 1);
            $wastage         = rand(0, 1);
            $carry_forward   = 0; // Keeping simple for seeded data
            
            // opening_qty = (carry_forward_qty - wastage_qty) + fresh_processed_qty
            // Make sure opening > closing
            $opening_qty = ($carry_forward - $wastage) + $fresh_processed;
            if ($closing_qty + $complimentary > $opening_qty) {
                $closing_qty = 0;
                $complimentary = 0;
            }
            
            $sold_qty = $opening_qty - $closing_qty - $complimentary;
            $total_sales_amount = $sold_qty * $item['selling_price'];

            $stmt_stock = $db->prepare("
                INSERT INTO daily_stocks 
                (item_id, log_date, carry_forward_qty, wastage_qty, complimentary_qty, fresh_processed_qty, closing_qty, total_sales_amount)
                VALUES (:item_id, :log_date, :carry_forward_qty, :wastage_qty, :complimentary_qty, :fresh_processed_qty, :closing_qty, :total_sales_amount)
            ");
            $stmt_stock->execute([
                ':item_id'             => $item['id'],
                ':log_date'            => $current_date,
                ':carry_forward_qty'   => $carry_forward,
                ':wastage_qty'         => $wastage,
                ':complimentary_qty'   => $complimentary,
                ':fresh_processed_qty' => $fresh_processed,
                ':closing_qty'         => $closing_qty,
                ':total_sales_amount'  => $total_sales_amount
            ]);
        }

        // ── Seed Bazaar Ledgers ────────────────────────────────────────
        $total_spent = rand(500, 2000);
        $stmt_bazaar = $db->prepare("
            INSERT INTO bazaar_ledgers (ledger_date, total_spent)
            VALUES (:ledger_date, :total_spent)
        ");
        $stmt_bazaar->execute([
            ':ledger_date' => $current_date,
            ':total_spent' => $total_spent
        ]);
        
        // ── Seed Expenses ──────────────────────────────────────────────
        // Daily Fixed Expense
        $daily_exp = rand(100, 300);
        $stmt_exp = $db->prepare("
            INSERT INTO expenses (category, name, total_amount, expense_date, is_spread)
            VALUES ('Fixed', 'Daily Tea & Snacks', :amount, :date, 0)
        ");
        $stmt_exp->execute([
            ':amount' => $daily_exp,
            ':date'   => $current_date
        ]);

        // Gas Expense Injection
        if (in_array($current_date, $gas_days)) {
            $stmt_gas = $db->prepare("
                INSERT INTO expenses (category, name, total_amount, expense_date, is_spread, remaining_balance, daily_amount)
                VALUES ('Gas', 'New Cylinder (12KG)', 1500.00, :date, 1, 1500.00, 150.00)
            ");
            $stmt_gas->execute([
                ':date' => $current_date
            ]);
            echo "🔥 Inserted Gas expense on $current_date\n";
        }
        
        // Randomly simulate gas usage by reducing remaining_balance of active gas
        $stmt_update_gas = $db->query("
            UPDATE expenses 
            SET remaining_balance = GREATEST(remaining_balance - daily_amount, 0)
            WHERE is_spread = 1 AND category = 'Gas' AND remaining_balance > 0 AND expense_date <= '$current_date'
        ");

        // ── Seed Customer Dues ─────────────────────────────────────────
        if (rand(1, 10) > 7) { // 30% chance of a due sale
            $due_amount = rand(100, 500);
            $stmt_due = $db->prepare("
                INSERT INTO customer_dues (customer_name, phone, due_amount, log_date, status)
                VALUES ('Regular Customer', '01700000000', :amount, :date, 'Unpaid')
            ");
            $stmt_due->execute([
                ':amount' => $due_amount,
                ':date'   => $current_date
            ]);
        }
    }

    echo "\n✅ Successfully seeded 30 days of dummy data!\n";

} catch (PDOException $e) {
    die("\n❌ Database Error: " . $e->getMessage() . "\n");
}
?>
