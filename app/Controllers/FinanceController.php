<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

/**
 * Finance Controller — Cash Drawer, Net Profit, Daily P&L formulas
 */
class FinanceController extends Controller {
public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Cash In Drawer = (Total Cash Sales) + Advance Received - Bazaar Spent - Direct Expenses
     * (Due sales are excluded from cash since they aren't collected yet)
     */
    public function calculateCashInDrawer(?string $date = null): float {
        $date = $date ?: date('Y-m-d');

        // Total sales from shift closings
        $s = $this->db->prepare("SELECT COALESCE(SUM(total_sales_amount), 0) FROM shift_closings WHERE log_date = :d");
        $s->execute([':d' => $date]);
        $totalSales = (float)$s->fetchColumn();

        // Due sales (Baki) — not in the drawer
        $d = $this->db->prepare("SELECT COALESCE(SUM(due_amount), 0) FROM customer_dues WHERE log_date = :d AND status = 'pending'");
        $d->execute([':d' => $date]);
        $dueSales = (float)$d->fetchColumn();

        // Advance payments received today
        $a = $this->db->prepare("SELECT COALESCE(SUM(advance_paid), 0) FROM advance_orders WHERE delivery_date = :d");
        $a->execute([':d' => $date]);
        $advanceReceived = (float)$a->fetchColumn();

        // Cash bazaar spent
        $b = $this->db->prepare("SELECT COALESCE(SUM(total_spent), 0) FROM bazaar_ledgers WHERE log_date = :d");
        $b->execute([':d' => $date]);
        $bazaarSpent = (float)$b->fetchColumn();

        // Direct (non-spread) expenses
        $e = $this->db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM expenses WHERE expense_date = :d AND is_spread = 0");
        $e->execute([':d' => $date]);
        $directExpenses = (float)$e->fetchColumn();

        return ($totalSales - $dueSales) + $advanceReceived - $bazaarSpent - $directExpenses;
    }

    /**
     * True Net Profit = (Cash Sales + Due Sales) - (Bazaar + Fixed Daily + Spread Gas + Prorated Salary + Wastage Cost + Comp Cost)
     */
    public function calculateNetProfit(?string $date = null): float {
        $date = $date ?: date('Y-m-d');

        // 1. Total Revenue (cash + due)
        $s = $this->db->prepare("SELECT COALESCE(SUM(total_sales_amount), 0) FROM shift_closings WHERE log_date = :d");
        $s->execute([':d' => $date]);
        $totalRevenue = (float)$s->fetchColumn();

        // Add due sales as revenue (they are still owed)
        $d = $this->db->prepare("SELECT COALESCE(SUM(due_amount), 0) FROM customer_dues WHERE log_date = :d");
        $d->execute([':d' => $date]);
        $totalRevenue += (float)$d->fetchColumn();

        // 2. Bazaar cost
        $b = $this->db->prepare("SELECT COALESCE(SUM(total_spent), 0) FROM bazaar_ledgers WHERE log_date = :d");
        $b->execute([':d' => $date]);
        $bazaarCost = (float)$b->fetchColumn();

        // 3. Fixed daily costs (rent, etc.)
        $f = $this->db->query("SELECT COALESCE(SUM(daily_amount), 0) FROM fixed_daily_costs WHERE is_active = 1");
        $fixedCosts = (float)$f->fetchColumn();

        // 4. Spread costs (gas daily deduction)
        $sp = $this->db->prepare("
            SELECT COALESCE(SUM(daily_amount), 0) FROM expenses
            WHERE is_spread = 1 AND remaining_balance > 0 AND is_active = 1
        ");
        $sp->execute();
        $spreadCosts = (float)$sp->fetchColumn();

        // 5. Prorated salary
        $sal = $this->db->prepare("
            SELECT COALESCE(SUM(daily_rate), 0) FROM staff_salaries
            WHERE start_date <= :d AND (end_date IS NULL OR end_date >= :d)
        ");
        $sal->execute([':d' => $date]);
        $salaryCost = (float)$sal->fetchColumn();

        // 6. Wastage + Complimentary cost (at cost price)
        $gc = $this->db->prepare("
            SELECT
                COALESCE(SUM(ds.wastage_qty * i.cost_price), 0) AS wastage_cost
            FROM daily_stocks ds
            JOIN items i ON ds.item_id = i.id
            WHERE ds.log_date = :d
        ");
        $gc->execute([':d' => $date]);
        $wastageCost = (float)$gc->fetch()['wastage_cost'];

        // Complimentary cost from shift closings
        $cc = $this->db->prepare("
            SELECT COALESCE(SUM(sc.complimentary_qty * i.cost_price), 0) AS comp_cost
            FROM shift_closings sc
            JOIN items i ON sc.item_id = i.id
            WHERE sc.log_date = :d
        ");
        $cc->execute([':d' => $date]);
        $compCost = (float)$cc->fetch()['comp_cost'];

        $totalCosts = $bazaarCost + $fixedCosts + $spreadCosts + $salaryCost + $wastageCost + $compCost;

        return $totalRevenue - $totalCosts;
    }

    // ══════════════════════════════════════════════════════════════
    //  SPREAD COSTS & FIXED COSTS
    // ══════════════════════════════════════════════════════════════

    public function spreadCosts() {
        $fixedCosts = $this->db->query("SELECT * FROM fixed_daily_costs ORDER BY name")->fetchAll();
        $spreadCosts = $this->db->query("SELECT * FROM expenses WHERE is_spread = 1 AND is_active = 1 ORDER BY expense_date DESC")->fetchAll();

        $this->view('finance/spread_costs', [
            'pageTitle' => __('link_spread_costs'),
            'activeNav' => 'settings',
            'fixedCosts' => $fixedCosts,
            'spreadCosts' => $spreadCosts
        ]);
    }

    public function addFixedCost() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = (int)($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $amount = (float)($data['amount'] ?? 0);
        
        if (empty($name) || $amount <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid data']);
        }

        if ($id > 0) {
            $stmt = $this->db->prepare("UPDATE fixed_daily_costs SET name = :name, daily_amount = :amount WHERE id = :id");
            $stmt->execute([':name' => $name, ':amount' => $amount, ':id' => $id]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO fixed_daily_costs (name, daily_amount, is_active) VALUES (:name, :amount, 1)");
            $stmt->execute([':name' => $name, ':amount' => $amount]);
        }
        
        $this->json(['success' => true]);
    }

    public function addSpreadCost() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = (int)($data['id'] ?? 0);
        $name = trim($data['asset_name'] ?? '');
        $total = (float)($data['total_cost'] ?? 0);
        $days = (int)($data['spread_days'] ?? 0);
        
        if (empty($name) || $total <= 0 || $days <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid data']);
        }
        
        $daily = round($total / $days, 2);
        $date = date('Y-m-d');

        if ($id > 0) {
            $stmt = $this->db->prepare("UPDATE expenses SET name = :name, total_amount = :total, remaining_balance = :total, daily_amount = :daily WHERE id = :id AND is_spread = 1");
            $stmt->execute([
                ':name' => $name,
                ':total' => $total,
                ':daily' => $daily,
                ':id' => $id
            ]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO expenses (category, name, total_amount, is_spread, daily_amount, remaining_balance, expense_date, is_active) VALUES ('Capital', :name, :total, 1, :daily, :total, :date, 1)");
            $stmt->execute([
                ':name' => $name,
                ':total' => $total,
                ':daily' => $daily,
                ':date' => $date
            ]);
        }
        
        $this->json(['success' => true]);
    }

    public function finishSpreadCost() {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        
        if ($id <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid ID']);
        }

        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("SELECT name, remaining_balance FROM expenses WHERE id = :id AND is_spread = 1 AND is_active = 1");
            $stmt->execute([':id' => $id]);
            $expense = $stmt->fetch();

            if ($expense && (float)$expense['remaining_balance'] > 0) {
                $rem = (float)$expense['remaining_balance'];
                $newName = $expense['name'] . ' (Early Finish)';
                $today = date('Y-m-d');
                
                $ins = $this->db->prepare("INSERT INTO expenses (category, name, total_amount, is_spread, expense_date) VALUES ('True-Up', :name, :rem, 0, :today)");
                $ins->execute([
                    ':name' => $newName,
                    ':rem' => $rem,
                    ':today' => $today
                ]);
            }

            $upd = $this->db->prepare("UPDATE expenses SET remaining_balance = 0, is_active = 0 WHERE id = :id");
            $upd->execute([':id' => $id]);

            $this->db->commit();
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
