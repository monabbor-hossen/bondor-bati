<?php

require_once __DIR__ . '/../core/Model.php';

class DashboardModel extends BaseModel {
    
    /**
     * Logic 2: Cash in Drawer
     * Cash = (Total Sales - Due Sales) + Advance Received from Orders - Cash Bazaar - Cash Expenses.
     */
    /**
     * Logic 2: Cash in Drawer
     * Cash = (Total Sales - Due Sales) + Advance Received from Orders - Cash Bazaar - Cash Expenses.
     */
    public function getCashInDrawer($date, $shift = null) {
        // 1. Total Sales
        if ($shift) {
            $stmtSales = $this->db->prepare("SELECT SUM(total_sales_amount) as total_sales FROM daily_stocks WHERE log_date = :date AND shift = :shift");
            $stmtSales->execute(['date' => $date, 'shift' => $shift]);
        } else {
            $stmtSales = $this->db->prepare("SELECT SUM(total_sales_amount) as total_sales FROM daily_stocks WHERE log_date = :date");
            $stmtSales->execute(['date' => $date]);
        }
        $totalSales = $stmtSales->fetchColumn() ?: 0;
        
        // 2. Due Sales
        if ($shift) {
            $stmtDue = $this->db->prepare("SELECT SUM(due_amount) as total_due FROM customer_dues WHERE log_date = :date AND shift = :shift");
            $stmtDue->execute(['date' => $date, 'shift' => $shift]);
        } else {
            $stmtDue = $this->db->prepare("SELECT SUM(due_amount) as total_due FROM customer_dues WHERE log_date = :date");
            $stmtDue->execute(['date' => $date]);
        }
        $dueSales = $stmtDue->fetchColumn() ?: 0;
        
        // 3. Advance Received from Orders (Typically EOD or general, let's keep it in Morning shift or divide it if shift is provided)
        $stmtAdvance = $this->db->prepare("SELECT SUM(advance_paid) as total_advance FROM advance_orders WHERE created_at LIKE :date");
        $stmtAdvance->execute(['date' => $date . '%']);
        $advanceReceived = $stmtAdvance->fetchColumn() ?: 0;
        if ($shift && $shift !== 'Morning') {
            $advanceReceived = 0; // Count advance received only in Morning shift
        }
        
        // 4. Cash Bazaar (Money that physically left the drawer = advance_cash - return_cash)
        $stmtBazaar = $this->db->prepare("SELECT (advance_cash - return_cash) as cash_out FROM bazaar_ledgers WHERE log_date = :date");
        $stmtBazaar->execute(['date' => $date]);
        $cashBazaar = $stmtBazaar->fetchColumn() ?: 0;
        if ($shift && $shift !== 'Morning') {
            $cashBazaar = 0; // Count bazaar cash out only in Morning shift
        }
        
        // 5. Cash Expenses
        $stmtExp = $this->db->prepare("SELECT SUM(total_amount) as total_exp FROM expenses WHERE expense_date = :date");
        $stmtExp->execute(['date' => $date]);
        $cashExpenses = $stmtExp->fetchColumn() ?: 0;
        if ($shift) {
            $cashExpenses = $cashExpenses / 3; // Prorate fixed expenses per shift
        }
        
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
    public function getNetProfit($date, $shift = null) {
        // Total Sales (Cash + Due is basically Total Sales from daily_stocks)
        if ($shift) {
            $stmtSales = $this->db->prepare("SELECT SUM(total_sales_amount) FROM daily_stocks WHERE log_date = :date AND shift = :shift");
            $stmtSales->execute(['date' => $date, 'shift' => $shift]);
        } else {
            $stmtSales = $this->db->prepare("SELECT SUM(total_sales_amount) FROM daily_stocks WHERE log_date = :date");
            $stmtSales->execute(['date' => $date]);
        }
        $totalSales = $stmtSales->fetchColumn() ?: 0;
        
        // Bazaar Cost (Assume Morning shift handles bazaar cost)
        $bazaarCost = 0;
        if (!$shift || $shift === 'Morning') {
            $stmtBazaar = $this->db->prepare("SELECT total_spent FROM bazaar_ledgers WHERE log_date = :date");
            $stmtBazaar->execute(['date' => $date]);
            $bazaarCost = $stmtBazaar->fetchColumn() ?: 0;
        }
        
        // Daily Fixed & Spread Exp
        $stmtExp = $this->db->prepare("
            SELECT COALESCE(SUM(
                CASE 
                    WHEN is_spread = 0 AND expense_date = :d1 THEN total_amount
                    WHEN is_spread = 1 AND :d2 >= expense_date AND :d3 < DATE_ADD(expense_date, INTERVAL (total_amount / daily_amount) DAY) THEN daily_amount
                    ELSE 0
                END
            ), 0)
            FROM expenses
        ");
        $stmtExp->execute(['d1' => $date, 'd2' => $date, 'd3' => $date]);
        $dailyExp = $stmtExp->fetchColumn() ?: 0;
        if ($shift) {
            $dailyExp = $dailyExp / 3;
        }
        
        // Prorated Salary
        $stmtSalary = $this->db->prepare("SELECT SUM(daily_rate) FROM staff_salaries WHERE :date BETWEEN start_date AND IFNULL(end_date, CURRENT_DATE)");
        $stmtSalary->execute(['date' => $date]);
        $proratedSalary = $stmtSalary->fetchColumn() ?: 0;
        if ($shift) {
            $proratedSalary = $proratedSalary / 3;
        }
        
        // Losses from Wastage & Complimentary
        if ($shift) {
            $stmtLoss = $this->db->prepare("
                SELECT SUM((ds.wastage_qty + ds.complimentary_qty) * i.cost_price) as loss
                FROM daily_stocks ds
                JOIN items i ON ds.item_id = i.id
                WHERE ds.log_date = :date AND ds.shift = :shift
            ");
            $stmtLoss->execute(['date' => $date, 'shift' => $shift]);
        } else {
            $stmtLoss = $this->db->prepare("
                SELECT SUM((ds.wastage_qty + ds.complimentary_qty) * i.cost_price) as loss
                FROM daily_stocks ds
                JOIN items i ON ds.item_id = i.id
                WHERE ds.log_date = :date
            ");
            $stmtLoss->execute(['date' => $date]);
        }
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

    /**
     * Logic 9: Dynamic Custom Range Reporting
     */
    public function getRangeReport($fromDate, $toDate) {
        // 1. Total Sales
        $stmtSales = $this->db->prepare("SELECT SUM(total_sales_amount) FROM daily_stocks WHERE log_date BETWEEN :from AND :to");
        $stmtSales->execute(['from' => $fromDate, 'to' => $toDate]);
        $totalSales = $stmtSales->fetchColumn() ?: 0;

        // 2. Total Dues
        $stmtDue = $this->db->prepare("SELECT SUM(due_amount) FROM customer_dues WHERE log_date BETWEEN :from AND :to");
        $stmtDue->execute(['from' => $fromDate, 'to' => $toDate]);
        $totalDues = $stmtDue->fetchColumn() ?: 0;

        // 3. Total Bazaar Cost
        $stmtBazaar = $this->db->prepare("SELECT SUM(total_spent) FROM bazaar_ledgers WHERE log_date BETWEEN :from AND :to");
        $stmtBazaar->execute(['from' => $fromDate, 'to' => $toDate]);
        $bazaarCost = $stmtBazaar->fetchColumn() ?: 0;

        // 4. Overlapping Spread & Fixed Expenses
        $stmtExp = $this->db->prepare("
            SELECT COALESCE(SUM(
                CASE 
                    WHEN is_spread = 0 THEN 
                        CASE WHEN expense_date BETWEEN :f1 AND :t1 THEN total_amount ELSE 0 END
                    ELSE 
                        CASE 
                            WHEN expense_date <= :t2 AND DATE_ADD(expense_date, INTERVAL (total_amount / daily_amount) - 1 DAY) >= :f2 THEN
                                daily_amount * (DATEDIFF(
                                    LEAST(:t3, DATE_ADD(expense_date, INTERVAL (total_amount / daily_amount) - 1 DAY)),
                                    GREATEST(:f3, expense_date)
                                    ) + 1)
                            ELSE 0
                        END
                END
            ), 0) FROM expenses
        ");
        $stmtExp->execute(['f1' => $fromDate, 't1' => $toDate, 'f2' => $fromDate, 't2' => $toDate, 'f3' => $fromDate, 't3' => $toDate]);
        $totalExpenses = $stmtExp->fetchColumn() ?: 0;

        // 5. Overlapping Salary Cost
        $stmtSal = $this->db->prepare("
            SELECT COALESCE(SUM(
                daily_rate * (DATEDIFF(
                    LEAST(:t1, IFNULL(end_date, CURRENT_DATE)),
                    GREATEST(:f1, start_date)
                ) + 1)
            ), 0) FROM staff_salaries
            WHERE start_date <= :t2 AND (end_date IS NULL OR end_date >= :f2)
        ");
        $stmtSal->execute(['f1' => $fromDate, 't1' => $toDate, 'f2' => $fromDate, 't2' => $toDate]);
        $salaryCost = $stmtSal->fetchColumn() ?: 0;

        // 6. Wastage & Complimentary Loss (Cost Price)
        $stmtLoss = $this->db->prepare("
            SELECT 
                SUM(ds.wastage_qty * i.cost_price) as wastage_loss,
                SUM(ds.complimentary_qty * i.cost_price) as complimentary_loss
            FROM daily_stocks ds
            JOIN items i ON ds.item_id = i.id
            WHERE ds.log_date BETWEEN :from AND :to
        ");
        $stmtLoss->execute(['from' => $fromDate, 'to' => $toDate]);
        $lossData = $stmtLoss->fetch(PDO::FETCH_ASSOC);
        $wastageLoss = $lossData['wastage_loss'] ?? 0;
        $complimentaryLoss = $lossData['complimentary_loss'] ?? 0;

        $netProfit = $totalSales - ($bazaarCost + $totalExpenses + $salaryCost + $wastageLoss + $complimentaryLoss);

        // 7. Top Selling Items
        $stmtTop = $this->db->prepare("
            SELECT i.item_name, SUM(ds.sold_qty) as total_sold, SUM(ds.total_sales_amount) as total_revenue
            FROM daily_stocks ds
            JOIN items i ON ds.item_id = i.id
            WHERE ds.log_date BETWEEN :from AND :to
            GROUP BY ds.item_id
            ORDER BY total_sold DESC
            LIMIT 5
        ");
        $stmtTop->execute(['from' => $fromDate, 'to' => $toDate]);
        $topSelling = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

        // 8. High Wastage Items
        $stmtWaste = $this->db->prepare("
            SELECT i.item_name, SUM(ds.wastage_qty) as total_wasted, SUM(ds.wastage_qty * i.cost_price) as total_wasted_cost
            FROM daily_stocks ds
            JOIN items i ON ds.item_id = i.id
            WHERE ds.log_date BETWEEN :from AND :to
            GROUP BY ds.item_id
            ORDER BY total_wasted DESC
            LIMIT 5
        ");
        $stmtWaste->execute(['from' => $fromDate, 'to' => $toDate]);
        $highWastage = $stmtWaste->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_sales' => $totalSales,
            'total_dues' => $totalDues,
            'bazaar_cost' => $bazaarCost,
            'total_expenses' => $totalExpenses,
            'salary_cost' => $salaryCost,
            'wastage_loss' => $wastageLoss,
            'complimentary_loss' => $complimentaryLoss,
            'net_profit' => $netProfit,
            'top_selling' => $topSelling,
            'high_wastage' => $highWastage
        ];
    }
}
