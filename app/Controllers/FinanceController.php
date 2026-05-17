<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\LedgerModel;
use Config\Database;
use PDO;

/**
 * Finance Controller
 * Handles Phase 2 Business Logic: Deep financial mathematics, cash handling, and profit formulas.
 */
class FinanceController extends Controller {
    private $db;
    private $ledgerModel;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->ledgerModel = new LedgerModel();
    }

    /**
     * Calculate Cash In Drawer (Physical Cash Expectation)
     * Formula: (Total Sales - Due Sales) + Advance Received from Orders - Cash Bazaar - Cash Expenses
     *
     * @param string|null $date (Defaults to today)
     * @return float
     */
    public function calculateCashInDrawer($date = null) {
        if (!$date) $date = date('Y-m-d');

        // 1. Total Sales (from Inventory/Daily Stocks)
        $stmtSales = $this->db->prepare("SELECT SUM(total_sales_amount) FROM daily_stocks WHERE log_date = :date");
        $stmtSales->execute([':date' => $date]);
        $totalSales = (float) $stmtSales->fetchColumn();

        // 2. Due Sales (from Customer Dues)
        $stmtDues = $this->db->prepare("SELECT SUM(due_amount) FROM customer_dues WHERE log_date = :date");
        $stmtDues->execute([':date' => $date]);
        $dueSales = (float) $stmtDues->fetchColumn();

        // 3. Advance Received from Orders
        $stmtAdvance = $this->db->prepare("SELECT SUM(advance_paid) FROM advance_orders WHERE delivery_date = :date");
        $stmtAdvance->execute([':date' => $date]);
        $advanceReceived = (float) $stmtAdvance->fetchColumn();

        // 4. Cash Bazaar (Money taken out for raw inventory shopping)
        $stmtBazaar = $this->db->prepare("SELECT SUM(total_spent) FROM bazaar_ledgers WHERE log_date = :date");
        $stmtBazaar->execute([':date' => $date]);
        $cashBazaar = (float) $stmtBazaar->fetchColumn();

        // 5. Cash Expenses (Direct daily expenses, ignoring spread balance for cash-drawer math)
        $stmtExp = $this->db->prepare("SELECT SUM(total_amount) FROM expenses WHERE expense_date = :date AND is_spread = 0");
        $stmtExp->execute([':date' => $date]);
        $cashExpenses = (float) $stmtExp->fetchColumn();

        // Phase 2 Formula Applied Here:
        $cashInDrawer = ($totalSales - $dueSales) + $advanceReceived - $cashBazaar - $cashExpenses;

        return $cashInDrawer;
    }

    /**
     * Calculate True Net Profit
     * Formula: (Total Cash Sales + Total Due Sales) - 
     * (Bazaar Cost + Daily Fixed Exp + Daily Spread Gas Exp + Prorated Salary + [Wastage Cost] + [Comp Cost])
     *
     * @param string|null $date (Defaults to today)
     * @return float
     */
    public function calculateNetProfit($date = null) {
        if (!$date) $date = date('Y-m-d');

        // 1. Total Sales (Cash + Due are already combined in total_sales_amount technically, 
        // but this ensures we capture the total generated revenue for the day).
        $stmtSales = $this->db->prepare("SELECT SUM(total_sales_amount) FROM daily_stocks WHERE log_date = :date");
        $stmtSales->execute([':date' => $date]);
        $totalSales = (float) $stmtSales->fetchColumn();

        // 2. Bazaar Cost
        $stmtBazaar = $this->db->prepare("SELECT SUM(total_spent) FROM bazaar_ledgers WHERE log_date = :date");
        $stmtBazaar->execute([':date' => $date]);
        $bazaarCost = (float) $stmtBazaar->fetchColumn();

        // 3. Daily Expenses (Handles both fixed total_amount and spread daily_amount seamlessly)
        $stmtExp = $this->db->prepare("
            SELECT SUM(CASE WHEN is_spread = 1 THEN daily_amount ELSE total_amount END) 
            FROM expenses WHERE expense_date = :date
        ");
        $stmtExp->execute([':date' => $date]);
        $dailyExpenses = (float) $stmtExp->fetchColumn();

        // 4. Prorated Salary (Cost of active staff for this specific day)
        $stmtSalary = $this->db->prepare("
            SELECT SUM(daily_rate) FROM staff_salaries 
            WHERE start_date <= :date AND (end_date IS NULL OR end_date >= :date)
        ");
        $stmtSalary->execute([':date' => $date]);
        $proratedSalary = (float) $stmtSalary->fetchColumn();

        // 5. Cost of Goods (Wastage + Complimentary)
        // We cross-reference daily_stocks with the items table to get the true monetary loss
        $stmtGoodsCost = $this->db->prepare("
            SELECT 
                SUM(ds.wastage_qty * i.cost_price) as total_wastage_cost,
                SUM(ds.complimentary_qty * i.cost_price) as total_comp_cost
            FROM daily_stocks ds
            JOIN items i ON ds.item_id = i.id
            WHERE ds.log_date = :date
        ");
        $stmtGoodsCost->execute([':date' => $date]);
        $goodsCosts = $stmtGoodsCost->fetch(PDO::FETCH_ASSOC);
        
        $wastageCost = (float) ($goodsCosts['total_wastage_cost'] ?? 0);
        $complimentaryCost = (float) ($goodsCosts['total_comp_cost'] ?? 0);

        // Phase 2 Formula Applied Here:
        $totalCosts = $bazaarCost + $dailyExpenses + $proratedSalary + $wastageCost + $complimentaryCost;
        $netProfit = $totalSales - $totalCosts;

        return $netProfit;
    }
}
