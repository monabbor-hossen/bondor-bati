<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

/**
 * Analytics Controller — Reports, Top Sellers, Wastage, Custom Ranges
 */
class AnalyticsController extends Controller {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function index() {
        $this->requireAdmin();

        $range = $_GET['range'] ?? 'today';
        $from  = $_GET['from'] ?? date('Y-m-d');
        $to    = $_GET['to'] ?? date('Y-m-d');

        switch ($range) {
            case 'today':    $from = $to = date('Y-m-d'); break;
            case 'yesterday': $from = $to = date('Y-m-d', strtotime('-1 day')); break;
            case 'month':    $from = date('Y-m-01'); $to = date('Y-m-d'); break;
            case 'custom':   break; // use provided from/to
        }

        $this->view('admin/analytics', [
            'pageTitle'      => __('analytics'),
            'activeNav'      => 'analytics',
            'range'          => $range,
            'from'           => $from,
            'to'             => $to,
            'salesSummary'   => $this->getSalesSummary($from, $to),
            'expenseSummary' => $this->getExpenseSummary($from, $to),
            'topSelling'     => $this->getTopSelling($from, $to),
            'highWastage'    => $this->getHighWastage($from, $to),
            'shiftBreakdown' => $this->getShiftBreakdown($from, $to),
            'customerDues'   => $this->getCustomerDues(),
            'supplierDues'   => $this->getSupplierDues(),
        ]);
    }

    private function getSalesSummary(string $from, string $to): array {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(total_sales_amount), 0) AS total_revenue,
                COALESCE(SUM(sold_qty), 0) AS total_sold,
                COALESCE(SUM(complimentary_qty), 0) AS total_comp,
                COALESCE(SUM(due_qty), 0) AS total_due
            FROM shift_closings
            WHERE log_date BETWEEN :from AND :to
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetch() ?: [];
    }

    private function getExpenseSummary(string $from, string $to): array {
        // Bazaar
        $b = $this->db->prepare("SELECT COALESCE(SUM(total_spent), 0) FROM bazaar_ledgers WHERE log_date BETWEEN :f AND :t");
        $b->execute([':f' => $from, ':t' => $to]);
        $bazaar = (float)$b->fetchColumn();

        // Days in range for fixed costs
        $days = max(1, (strtotime($to) - strtotime($from)) / 86400 + 1);
        $fc = $this->db->query("SELECT COALESCE(SUM(daily_amount), 0) FROM fixed_daily_costs WHERE is_active = 1");
        $fixedTotal = (float)$fc->fetchColumn() * $days;

        // Spread costs
        $sp = $this->db->query("SELECT COALESCE(SUM(daily_amount), 0) FROM expenses WHERE is_spread = 1 AND remaining_balance > 0 AND is_active = 1");
        $spreadTotal = (float)$sp->fetchColumn() * $days;

        // Salary
        $sal = $this->db->prepare("SELECT COALESCE(SUM(daily_rate), 0) FROM staff_salaries WHERE start_date <= :t AND (end_date IS NULL OR end_date >= :f)");
        $sal->execute([':f' => $from, ':t' => $to]);
        $salaryTotal = (float)$sal->fetchColumn() * $days;

        // Wastage cost
        $w = $this->db->prepare("
            SELECT COALESCE(SUM(ds.wastage_qty * i.cost_price), 0)
            FROM daily_stocks ds JOIN items i ON ds.item_id = i.id
            WHERE ds.log_date BETWEEN :f AND :t
        ");
        $w->execute([':f' => $from, ':t' => $to]);
        $wastageCost = (float)$w->fetchColumn();

        return [
            'bazaar'   => $bazaar,
            'fixed'    => $fixedTotal,
            'spread'   => $spreadTotal,
            'salary'   => $salaryTotal,
            'wastage'  => $wastageCost,
            'total'    => $bazaar + $fixedTotal + $spreadTotal + $salaryTotal + $wastageCost,
        ];
    }

    private function getTopSelling(string $from, string $to): array {
        $stmt = $this->db->prepare("
            SELECT i.item_name, i.item_name_bn, SUM(sc.sold_qty) AS total_sold,
                   SUM(sc.total_sales_amount) AS total_revenue
            FROM shift_closings sc
            JOIN items i ON sc.item_id = i.id
            WHERE sc.log_date BETWEEN :f AND :t
            GROUP BY i.id, i.item_name, i.item_name_bn
            ORDER BY total_sold DESC LIMIT 5
        ");
        $stmt->execute([':f' => $from, ':t' => $to]);
        return $stmt->fetchAll();
    }

    private function getHighWastage(string $from, string $to): array {
        $stmt = $this->db->prepare("
            SELECT i.item_name, i.item_name_bn,
                   SUM(ds.wastage_qty) AS total_wastage,
                   SUM(ds.wastage_qty * i.cost_price) AS wastage_cost
            FROM daily_stocks ds
            JOIN items i ON ds.item_id = i.id
            WHERE ds.log_date BETWEEN :f AND :t AND ds.wastage_qty > 0
            GROUP BY i.id, i.item_name, i.item_name_bn
            ORDER BY total_wastage DESC LIMIT 5
        ");
        $stmt->execute([':f' => $from, ':t' => $to]);
        return $stmt->fetchAll();
    }

    private function getShiftBreakdown(string $from, string $to): array {
        $stmt = $this->db->prepare("
            SELECT shift, SUM(sold_qty) AS total_sold, SUM(total_sales_amount) AS revenue
            FROM shift_closings
            WHERE log_date BETWEEN :f AND :t
            GROUP BY shift
            ORDER BY FIELD(shift, 'morning', 'evening', 'night')
        ");
        $stmt->execute([':f' => $from, ':t' => $to]);
        return $stmt->fetchAll();
    }

    private function getCustomerDues(): array {
        return $this->db->query("
            SELECT customer_name, phone, due_amount, log_date
            FROM customer_dues WHERE status = 'pending'
            ORDER BY due_amount DESC LIMIT 10
        ")->fetchAll();
    }

    private function getSupplierDues(): array {
        return $this->db->query("
            SELECT name, name_bn, contact, total_due
            FROM suppliers WHERE total_due > 0
            ORDER BY total_due DESC
        ")->fetchAll();
    }
}
