<?php

require_once __DIR__ . '/../config/database.php';

class ReportController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Calculate the Daily Net Profit using the full formula:
     *
     * Net Profit = (Total Cash Sales + Total Due Sales)
     *            - (Bazaar Cost + Daily Fixed Expenses + Daily Spread Gas Expense
     *               + Prorated Salary + Wastage Loss + Complimentary Loss)
     *
     * Where:
     *   - Total Cash Sales    = SUM(total_sales_amount) from daily_stocks
     *   - Total Due Sales     = SUM(due_amount) from customer_dues for that date
     *   - Bazaar Cost         = SUM(total_amount) from expenses WHERE is_spread=0 AND category NOT IN ('Fixed')
     *   - Daily Fixed Exp     = SUM(total_amount) from expenses WHERE is_spread=0 AND category='Fixed'
     *   - Daily Spread Gas    = SUM(daily_amount) from expenses WHERE is_spread=1 AND category='Gas' AND remaining_balance > 0
     *   - Prorated Salary     = SUM(daily_rate) from staff_salaries active on that date
     *   - Wastage Loss        = SUM(wastage_qty * cost_price) across all items for that date
     *   - Complimentary Loss  = SUM(complimentary_qty * cost_price) across all items for that date
     *
     * @param  string $date  The date to calculate for (Y-m-d format).
     * @return array         Breakdown of every variable plus the final net profit.
     */
    public function calculateDailyNetProfit($date) {
        try {
            // ── 1. Total Cash Sales ──────────────────────────────────────
            $query_cash = "SELECT COALESCE(SUM(total_sales_amount), 0) AS total 
                           FROM daily_stocks 
                           WHERE log_date = :date";
            $stmt = $this->db->prepare($query_cash);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            $total_cash_sales = (float) $stmt->fetch()['total'];

            // ── 2. Total Due Sales ───────────────────────────────────────
            $query_dues = "SELECT COALESCE(SUM(due_amount), 0) AS total 
                           FROM customer_dues 
                           WHERE log_date = :date";
            $stmt = $this->db->prepare($query_dues);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            $total_due_sales = (float) $stmt->fetch()['total'];

            // ── 3. Bazaar Cost (non-spread, non-fixed daily market purchases) ─
            $query_bazaar = "SELECT COALESCE(SUM(total_amount), 0) AS total 
                             FROM expenses 
                             WHERE expense_date = :date 
                               AND is_spread = 0 
                               AND category NOT IN ('Fixed')";
            $stmt = $this->db->prepare($query_bazaar);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            $bazaar_cost = (float) $stmt->fetch()['total'];

            // ── 4. Daily Fixed Expenses (non-spread, category = Fixed) ───
            $query_fixed = "SELECT COALESCE(SUM(total_amount), 0) AS total 
                            FROM expenses 
                            WHERE expense_date = :date 
                              AND is_spread = 0 
                              AND category = 'Fixed'";
            $stmt = $this->db->prepare($query_fixed);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            $daily_fixed_exp = (float) $stmt->fetch()['total'];

            // ── 5. Daily Spread Gas Expense (active gas spreads) ─────────
            $query_gas = "SELECT COALESCE(SUM(daily_amount), 0) AS total 
                          FROM expenses 
                          WHERE is_spread = 1 
                            AND category = 'Gas' 
                            AND remaining_balance > 0";
            $stmt = $this->db->prepare($query_gas);
            $stmt->execute();
            $daily_gas_exp = (float) $stmt->fetch()['total'];

            // ── 6. Prorated Staff Salary ─────────────────────────────────
            $query_salary = "SELECT COALESCE(SUM(daily_rate), 0) AS total 
                             FROM staff_salaries 
                             WHERE start_date <= :date 
                               AND (end_date IS NULL OR end_date >= :date)";
            $stmt = $this->db->prepare($query_salary);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            $prorated_salary = (float) $stmt->fetch()['total'];

            // ── 7. Wastage Loss = SUM(wastage_qty * cost_price) ──────────
            $query_wastage = "SELECT COALESCE(SUM(ds.wastage_qty * i.cost_price), 0) AS total 
                              FROM daily_stocks ds 
                              JOIN items i ON ds.item_id = i.id 
                              WHERE ds.log_date = :date";
            $stmt = $this->db->prepare($query_wastage);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            $wastage_loss = (float) $stmt->fetch()['total'];

            // ── 8. Complimentary Loss = SUM(complimentary_qty * cost_price)
            $query_comp = "SELECT COALESCE(SUM(ds.complimentary_qty * i.cost_price), 0) AS total 
                           FROM daily_stocks ds 
                           JOIN items i ON ds.item_id = i.id 
                           WHERE ds.log_date = :date";
            $stmt = $this->db->prepare($query_comp);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            $complimentary_loss = (float) $stmt->fetch()['total'];

            // ── FINAL CALCULATION ────────────────────────────────────────
            $total_revenue  = $total_cash_sales + $total_due_sales;
            $total_expenses = $bazaar_cost + $daily_fixed_exp + $daily_gas_exp + $prorated_salary + $wastage_loss + $complimentary_loss;
            $net_profit     = round($total_revenue - $total_expenses, 2);

            return [
                'success'            => true,
                'date'               => $date,
                'breakdown' => [
                    'total_cash_sales'   => $total_cash_sales,
                    'total_due_sales'    => $total_due_sales,
                    'total_revenue'      => $total_revenue,
                    'bazaar_cost'        => $bazaar_cost,
                    'daily_fixed_exp'    => $daily_fixed_exp,
                    'daily_gas_exp'      => $daily_gas_exp,
                    'prorated_salary'    => $prorated_salary,
                    'wastage_loss'       => $wastage_loss,
                    'complimentary_loss' => $complimentary_loss,
                    'total_expenses'     => $total_expenses,
                ],
                'net_profit' => $net_profit
            ];

        } catch (PDOException $e) {
            error_log("ReportController::calculateDailyNetProfit Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to calculate daily net profit.'
            ];
        }
    }
}
?>
