<?php

require_once __DIR__ . '/../core/Model.php';

class DashboardModel extends BaseModel {
    
    /**
     * Logic 2: Cash in Drawer
     * Cash = (Total Sales - Due Sales) + Advance Received from Orders - Cash Bazaar - Cash Expenses.
     */
    public function getCashInDrawer($date) {
        // 1. Total Sales
        $stmtSales = $this->db->prepare("SELECT SUM(total_sales_amount) as total_sales FROM daily_stocks WHERE log_date = :date");
        $stmtSales->execute(['date' => $date]);
        $totalSales = $stmtSales->fetchColumn() ?: 0;
        
        // 2. Due Sales
        $stmtDue = $this->db->prepare("SELECT SUM(due_amount) as total_due FROM customer_dues WHERE log_date = :date");
        $stmtDue->execute(['date' => $date]);
        $dueSales = $stmtDue->fetchColumn() ?: 0;
        
        // 3. Advance Received from Orders
        $stmtAdvance = $this->db->prepare("SELECT SUM(advance_paid) as total_advance FROM advance_orders WHERE created_at LIKE :date");
        $stmtAdvance->execute(['date' => $date . '%']);
        $advanceReceived = $stmtAdvance->fetchColumn() ?: 0;
        
        // 4. Cash Bazaar (Assuming bazaar ledgers track cash spent)
        $stmtBazaar = $this->db->prepare("SELECT total_spent FROM bazaar_ledgers WHERE log_date = :date");
        $stmtBazaar->execute(['date' => $date]);
        $cashBazaar = $stmtBazaar->fetchColumn() ?: 0;
        
        // 5. Cash Expenses
        $stmtExp = $this->db->prepare("SELECT SUM(total_amount) as total_exp FROM expenses WHERE expense_date = :date");
        $stmtExp->execute(['date' => $date]);
        $cashExpenses = $stmtExp->fetchColumn() ?: 0;
        
        $cashInDrawer = ($totalSales - $dueSales) + $advanceReceived - $cashBazaar - $cashExpenses;
        
        return [
            'total_sales' => $totalSales,
            'due_sales' => $dueSales,
            'advance_received' => $advanceReceived,
            'cash_bazaar' => $cashBazaar,
            'cash_expenses' => $cashExpenses,
            'cash_in_drawer' => $cashInDrawer
        ];
    }
    
    /**
     * Logic 3: True Net Profit
     * Net Profit = (Total Cash Sales + Total Due Sales) - (Bazaar Cost + Daily Fixed Exp + Daily Spread Gas Exp + Prorated Salary + [Wastage Qty * Cost Price] + [Complimentary Qty * Cost Price]).
     */
    public function getNetProfit($date) {
        // Total Sales (Cash + Due is basically Total Sales from daily_stocks)
        $stmtSales = $this->db->prepare("SELECT SUM(total_sales_amount) FROM daily_stocks WHERE log_date = :date");
        $stmtSales->execute(['date' => $date]);
        $totalSales = $stmtSales->fetchColumn() ?: 0;
        
        // Bazaar Cost
        $stmtBazaar = $this->db->prepare("SELECT total_spent FROM bazaar_ledgers WHERE log_date = :date");
        $stmtBazaar->execute(['date' => $date]);
        $bazaarCost = $stmtBazaar->fetchColumn() ?: 0;
        
        // Daily Fixed & Spread Exp (Fetch from expenses where we assume daily_amount is calculated)
        $stmtExp = $this->db->prepare("SELECT SUM(daily_amount) FROM expenses WHERE expense_date = :date");
        $stmtExp->execute(['date' => $date]);
        $dailyExp = $stmtExp->fetchColumn() ?: 0;
        
        // Prorated Salary
        $stmtSalary = $this->db->prepare("SELECT SUM(daily_rate) FROM staff_salaries WHERE :date BETWEEN start_date AND IFNULL(end_date, CURRENT_DATE)");
        $stmtSalary->execute(['date' => $date]);
        $proratedSalary = $stmtSalary->fetchColumn() ?: 0;
        
        // Losses from Wastage & Complimentary
        $stmtLoss = $this->db->prepare("
            SELECT SUM((ds.wastage_qty + ds.complimentary_qty) * i.cost_price) as loss
            FROM daily_stocks ds
            JOIN items i ON ds.item_id = i.id
            WHERE ds.log_date = :date
        ");
        $stmtLoss->execute(['date' => $date]);
        $wastageCompLoss = $stmtLoss->fetchColumn() ?: 0;
        
        $netProfit = $totalSales - ($bazaarCost + $dailyExp + $proratedSalary + $wastageCompLoss);
        
        return [
            'total_sales' => $totalSales,
            'bazaar_cost' => $bazaarCost,
            'daily_exp' => $dailyExp,
            'prorated_salary' => $proratedSalary,
            'wastage_comp_loss' => $wastageCompLoss,
            'net_profit' => $netProfit
        ];
    }
}
