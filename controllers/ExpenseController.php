<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Expense.php';

class ExpenseController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // ─── LOG A ONE-TIME EXPENSE (Fixed, Asset, Utility) ──────────────
    public function logExpense($category, $name, $total_amount) {
        $expense = new Expense($this->db);
        $expense->category          = $category;
        $expense->name              = $name;
        $expense->total_amount      = $total_amount;
        $expense->is_spread         = 0;
        $expense->daily_amount      = 0;
        $expense->remaining_balance = 0;
        $expense->expense_date      = date('Y-m-d');

        if ($expense->create()) {
            return ['success' => true, 'message' => 'Expense logged successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to log expense.'];
    }

    // ─── GAS SPREAD LOGIC ────────────────────────────────────────────
    // Records a gas purchase and spreads the cost evenly over N days
    public function logGasExpense($name, $total_amount, $spread_days) {
        if ($spread_days <= 0) {
            return ['success' => false, 'message' => 'Spread days must be greater than zero.'];
        }

        $daily_amount = round($total_amount / $spread_days, 2);

        $expense = new Expense($this->db);
        $expense->category          = 'Gas';
        $expense->name              = $name;
        $expense->total_amount      = $total_amount;
        $expense->is_spread         = 1;
        $expense->daily_amount      = $daily_amount;
        $expense->remaining_balance = $total_amount; // Full amount remaining on day 1
        $expense->expense_date      = date('Y-m-d');

        if ($expense->create()) {
            return [
                'success'      => true,
                'message'      => 'Gas expense spread over ' . $spread_days . ' days.',
                'daily_amount' => $daily_amount
            ];
        }
        return ['success' => false, 'message' => 'Failed to log gas expense.'];
    }

    // ─── DAILY DEDUCTION: Run once per day to consume spread expenses ─
    // Called by a cron job or at the start of each business day
    public function processSpreadDeductions() {
        // Fetch all active spread expenses with remaining balance > 0
        $query = "SELECT * FROM expenses WHERE is_spread = 1 AND remaining_balance > 0";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $spreads = $stmt->fetchAll();

        $processed_count = 0;

        foreach ($spreads as $spread) {
            $deduction = min($spread['daily_amount'], $spread['remaining_balance']);
            $new_balance = $spread['remaining_balance'] - $deduction;

            $update = "UPDATE expenses SET remaining_balance = :balance WHERE id = :id";
            $stmt_update = $this->db->prepare($update);
            $stmt_update->bindParam(':balance', $new_balance);
            $stmt_update->bindParam(':id', $spread['id']);
            $stmt_update->execute();

            $processed_count++;
        }

        return [
            'success'   => true,
            'message'   => $processed_count . ' spread expense(s) deducted for today.',
            'processed' => $processed_count
        ];
    }

    // ─── DAILY BAZAAR: Get all non-spread expenses for a given date ──
    public function getDailyBazaar($date) {
        $query = "SELECT * FROM expenses WHERE expense_date = :date AND is_spread = 0 ORDER BY id ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ─── DAILY BAZAAR TOTAL ──────────────────────────────────────────
    public function getDailyBazaarTotal($date) {
        $query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM expenses WHERE expense_date = :date AND is_spread = 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['total'];
    }

    // ─── ACTIVE GAS COST: Current daily gas burden ───────────────────
    public function getActiveDailyGasCost() {
        $query = "SELECT COALESCE(SUM(daily_amount), 0) as total_gas 
                  FROM expenses 
                  WHERE is_spread = 1 AND category = 'Gas' AND remaining_balance > 0";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['total_gas'];
    }

    // ─── VIEW: Get all expenses ──────────────────────────────────────
    public function getAllExpenses() {
        $expense = new Expense($this->db);
        return $expense->read();
    }
}
?>
