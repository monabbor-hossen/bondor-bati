<?php
/**
 * Daily Closing Job — End-of-Day Automation
 * 
 * Intended to be run via cron at the end of each business day:
 *   0 23 * * * /opt/lampp/bin/php /opt/lampp/htdocs/bondor-bati/scripts/daily_closing_job.php
 * 
 * Functions:
 *   1. generateDailySummary()  — Produces a WhatsApp-ready plain-text summary.
 *   2. backupDatabase()        — Dumps the full DB to a timestamped .sql file.
 *   3. run()                   — Executes both and logs the outcome.
 */

require_once __DIR__ . '/../config/database.php';

// ═══════════════════════════════════════════════════════════════════════
//  1. DAILY SUMMARY
// ═══════════════════════════════════════════════════════════════════════

/**
 * Fetch key financial metrics for today and format them into a clean,
 * plain-text string suitable for sending via WhatsApp or SMS.
 *
 * @param  string|null $date  Override date (Y-m-d). Defaults to today.
 * @return array              ['success' => bool, 'summary' => string, 'data' => array]
 */
function generateDailySummary($date = null) {
    $database = new Database();
    $db = $database->getConnection();
    $date = $date ?? date('Y-m-d');

    try {
        // ── Total Cash Sales ─────────────────────────────────────────
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(total_sales_amount), 0) AS total 
             FROM daily_stocks WHERE log_date = :date"
        );
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $cash_sales = (float) $stmt->fetch()['total'];

        // ── Total Due Sales ──────────────────────────────────────────
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(due_amount), 0) AS total 
             FROM customer_dues WHERE log_date = :date"
        );
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $due_sales = (float) $stmt->fetch()['total'];

        // ── Total Bazaar Spent ───────────────────────────────────────
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(total_spent), 0) AS total 
             FROM bazaar_ledgers WHERE ledger_date = :date"
        );
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $bazaar_cost = (float) $stmt->fetch()['total'];

        // ── Wastage Cost (wastage_qty × cost_price) ──────────────────
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(ds.wastage_qty * i.cost_price), 0) AS total
             FROM daily_stocks ds
             JOIN items i ON ds.item_id = i.id
             WHERE ds.log_date = :date"
        );
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $wastage_cost = (float) $stmt->fetch()['total'];

        // ── Complimentary Loss ───────────────────────────────────────
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(ds.complimentary_qty * i.cost_price), 0) AS total
             FROM daily_stocks ds
             JOIN items i ON ds.item_id = i.id
             WHERE ds.log_date = :date"
        );
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $comp_loss = (float) $stmt->fetch()['total'];

        // ── Daily Expenses (non-spread) ──────────────────────────────
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(total_amount), 0) AS total
             FROM expenses
             WHERE expense_date = :date AND is_spread = 0"
        );
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $daily_expenses = (float) $stmt->fetch()['total'];

        // ── Active Gas Spread ────────────────────────────────────────
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(daily_amount), 0) AS total
             FROM expenses
             WHERE is_spread = 1 AND category = 'Gas' AND remaining_balance > 0"
        );
        $stmt->execute();
        $gas_daily = (float) $stmt->fetch()['total'];

        // ── Prorated Salary ──────────────────────────────────────────
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(daily_rate), 0) AS total
             FROM staff_salaries
             WHERE start_date <= :date_start AND (end_date IS NULL OR end_date >= :date_end)"
        );
        $stmt->bindParam(':date_start', $date);
        $stmt->bindParam(':date_end', $date);
        $stmt->execute();
        $salary = (float) $stmt->fetch()['total'];

        // ── Pending Advance Orders for Tomorrow ──────────────────────
        $tomorrow = date('Y-m-d', strtotime($date . ' +1 day'));
        $stmt = $db->prepare(
            "SELECT COUNT(*) AS count FROM advance_orders
             WHERE delivery_date = :tomorrow AND status = 'Pending'"
        );
        $stmt->bindParam(':tomorrow', $tomorrow);
        $stmt->execute();
        $pending_orders = (int) $stmt->fetch()['count'];

        // ── Calculate Totals ─────────────────────────────────────────
        $total_revenue  = $cash_sales + $due_sales;
        $total_expenses = $bazaar_cost + $daily_expenses + $gas_daily + $salary + $wastage_cost + $comp_loss;
        $net_profit     = round($total_revenue - $total_expenses, 2);
        $profit_icon    = $net_profit >= 0 ? '📈' : '📉';
        $formatted_date = date('d M Y (l)', strtotime($date));

        // ── Format WhatsApp Message ──────────────────────────────────
        $summary = "━━━━━━━━━━━━━━━━━━━━\n";
        $summary .= "🍽️ *Bondor Bati — Daily Summary*\n";
        $summary .= "📅 {$formatted_date}\n";
        $summary .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $summary .= "💰 *REVENUE*\n";
        $summary .= "  Cash Sales:    ৳" . number_format($cash_sales, 2) . "\n";
        $summary .= "  Due Sales:     ৳" . number_format($due_sales, 2) . "\n";
        $summary .= "  *Total:        ৳" . number_format($total_revenue, 2) . "*\n\n";

        $summary .= "📤 *EXPENSES*\n";
        $summary .= "  Bazaar:        ৳" . number_format($bazaar_cost, 2) . "\n";
        $summary .= "  Fixed/Other:   ৳" . number_format($daily_expenses, 2) . "\n";
        $summary .= "  Gas (daily):   ৳" . number_format($gas_daily, 2) . "\n";
        $summary .= "  Salaries:      ৳" . number_format($salary, 2) . "\n";
        $summary .= "  Wastage:       ৳" . number_format($wastage_cost, 2) . "\n";
        $summary .= "  Complimentary: ৳" . number_format($comp_loss, 2) . "\n";
        $summary .= "  *Total:        ৳" . number_format($total_expenses, 2) . "*\n\n";

        $summary .= "━━━━━━━━━━━━━━━━━━━━\n";
        $summary .= "{$profit_icon} *NET " . ($net_profit >= 0 ? "PROFIT" : "LOSS") . ": ৳" . number_format(abs($net_profit), 2) . "*\n";
        $summary .= "━━━━━━━━━━━━━━━━━━━━\n";

        if ($pending_orders > 0) {
            $summary .= "\n⚠️ *{$pending_orders} advance order(s)* pending for tomorrow!\n";
        }

        return [
            'success' => true,
            'summary' => $summary,
            'data'    => [
                'date'            => $date,
                'cash_sales'      => $cash_sales,
                'due_sales'       => $due_sales,
                'total_revenue'   => $total_revenue,
                'bazaar_cost'     => $bazaar_cost,
                'daily_expenses'  => $daily_expenses,
                'gas_daily'       => $gas_daily,
                'salary'          => $salary,
                'wastage_cost'    => $wastage_cost,
                'comp_loss'       => $comp_loss,
                'total_expenses'  => $total_expenses,
                'net_profit'      => $net_profit,
                'pending_orders'  => $pending_orders,
            ]
        ];

    } catch (PDOException $e) {
        error_log("generateDailySummary Error: " . $e->getMessage());
        return [
            'success' => false,
            'summary' => 'Failed to generate daily summary.',
            'data'    => []
        ];
    }
}


// ═══════════════════════════════════════════════════════════════════════
//  2. DATABASE BACKUP
// ═══════════════════════════════════════════════════════════════════════

/**
 * Run mysqldump to create a timestamped backup of the full database.
 * Saves to /backups directory relative to the project root.
 *
 * @return array  ['success' => bool, 'message' => string, 'file' => string|null]
 */
function backupDatabase() {
    $db_host = 'localhost';
    $db_name = 'bondor_bati_db';
    $db_user = 'root';
    $db_pass = '';  // empty for default LAMPP

    // Build backup directory path
    $backup_dir = __DIR__ . '/../backups';

    // Create directory if it doesn't exist
    if (!is_dir($backup_dir)) {
        if (!mkdir($backup_dir, 0755, true)) {
            return [
                'success' => false,
                'message' => 'Failed to create backups directory.',
                'file'    => null
            ];
        }
    }

    // Build filename with date
    $date_stamp = date('Y-m-d');
    $filename   = "backup_{$date_stamp}.sql";
    $filepath   = $backup_dir . '/' . $filename;

    // Build mysqldump command
    // Using the LAMPP-bundled mysqldump binary
    $mysqldump_path = '/opt/lampp/bin/mysqldump';

    // Fallback to system mysqldump if LAMPP binary doesn't exist
    if (!file_exists($mysqldump_path)) {
        $mysqldump_path = 'mysqldump';
    }

    $cmd = sprintf(
        '%s --host=%s --user=%s %s %s > %s 2>&1',
        escapeshellarg($mysqldump_path),
        escapeshellarg($db_host),
        escapeshellarg($db_user),
        $db_pass ? '--password=' . escapeshellarg($db_pass) : '',
        escapeshellarg($db_name),
        escapeshellarg($filepath)
    );

    // Execute
    $output = [];
    $return_code = 0;
    exec($cmd, $output, $return_code);

    if ($return_code !== 0) {
        $error_msg = implode("\n", $output);
        error_log("backupDatabase Error: " . $error_msg);
        return [
            'success' => false,
            'message' => 'mysqldump failed (exit code: ' . $return_code . '). ' . $error_msg,
            'file'    => null
        ];
    }

    // Verify the file was actually created and isn't empty
    if (!file_exists($filepath) || filesize($filepath) === 0) {
        return [
            'success' => false,
            'message' => 'Backup file was not created or is empty.',
            'file'    => null
        ];
    }

    $size_kb = round(filesize($filepath) / 1024, 1);

    return [
        'success' => true,
        'message' => "Backup saved: {$filename} ({$size_kb} KB)",
        'file'    => $filepath
    ];
}


// ═══════════════════════════════════════════════════════════════════════
//  3. MAIN RUNNER
// ═══════════════════════════════════════════════════════════════════════

/**
 * Execute the full end-of-day routine.
 * Can be called from cron or manually via CLI/browser.
 */
function run() {
    $log = [];
    $log[] = "[" . date('Y-m-d H:i:s') . "] Starting end-of-day job...";

    // Step 1: Generate summary
    $summary_result = generateDailySummary();
    if ($summary_result['success']) {
        $log[] = "[OK] Daily summary generated.";
        $log[] = "";
        $log[] = $summary_result['summary'];
    } else {
        $log[] = "[FAIL] " . $summary_result['summary'];
    }

    // Step 2: Backup database
    $backup_result = backupDatabase();
    if ($backup_result['success']) {
        $log[] = "[OK] " . $backup_result['message'];
    } else {
        $log[] = "[FAIL] " . $backup_result['message'];
    }

    // Step 3: Process gas spread deductions for the day
    require_once __DIR__ . '/../controllers/ExpenseController.php';
    $exp = new ExpenseController();
    $spread_result = $exp->processSpreadDeductions();
    $log[] = "[OK] " . $spread_result['message'];

    $log[] = "";
    $log[] = "[" . date('Y-m-d H:i:s') . "] End-of-day job complete.";

    $output = implode("\n", $log);

    // Log to file
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    file_put_contents($log_dir . '/closing_' . date('Y-m-d') . '.log', $output . "\n", FILE_APPEND);

    return [
        'summary'  => $summary_result,
        'backup'   => $backup_result,
        'spreads'  => $spread_result,
        'log'      => $output
    ];
}


// ═══════════════════════════════════════════════════════════════════════
//  AUTO-EXECUTE WHEN RUN DIRECTLY (CLI or browser)
// ═══════════════════════════════════════════════════════════════════════

// Detect if this script is being executed directly (not included/required)
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    $result = run();

    // Output format depends on context
    if (php_sapi_name() === 'cli') {
        // CLI: plain text
        echo $result['log'] . "\n";
    } else {
        // Browser: JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
?>
