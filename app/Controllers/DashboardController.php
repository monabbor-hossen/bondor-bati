<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

/**
 * Dashboard Controller
 * Phase 4: Smart Dashboard with forecasting SQL queries.
 */
class DashboardController extends Controller {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Dashboard index — collects all widget data and renders view
     * Route: ?url=dashboard
     */
    public function index() {
        $this->view('dashboard/index', [
            'pageTitle'          => 'Smart Dashboard',
            'activeNav'          => 'home',
            'userName'           => $_SESSION['user_name'] ?? 'Staff',
            'gasInfo'            => $this->getGasDepletionDate(),
            'bazaarSuggestions'  => $this->getTomorrowBazaarSuggestions(),
            'supplierDues'       => $this->getSupplierDues(),
            'pendingOrders'      => $this->getPendingOrdersToday(),
        ]);
    }

    // ----------------------------------------------------------------
    //  WIDGET 1 — Gas Depletion Forecast
    // ----------------------------------------------------------------

    /**
     * Predict the next gas refill date using remaining balance and daily burn rate.
     * Formula: Refill Date = TODAY + FLOOR(remaining_balance / daily_amount)
     *
     * @return array ['days_remaining' => int, 'refill_date' => string, 'status' => string]
     */
    public function getGasDepletionDate() {
        // Fetch the most recent active Gas spread expense
        $stmt = $this->db->prepare("
            SELECT remaining_balance, daily_amount, expense_date
            FROM   expenses
            WHERE  is_spread = 1
              AND  LOWER(category) = 'gas'
              AND  remaining_balance > 0
            ORDER  BY expense_date DESC
            LIMIT  1
        ");
        $stmt->execute();
        $gasExpense = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$gasExpense || (float)$gasExpense['daily_amount'] <= 0) {
            return ['days_remaining' => null, 'refill_date' => 'N/A', 'status' => 'no_data'];
        }

        $remaining   = (float) $gasExpense['remaining_balance'];
        $dailyBurn   = (float) $gasExpense['daily_amount'];
        $daysLeft    = (int) floor($remaining / $dailyBurn);
        $refillDate  = date('D, d M Y', strtotime("+{$daysLeft} days"));

        // Determine urgency status for UI color coding
        $status = 'ok';
        if ($daysLeft <= 3)  $status = 'critical';
        elseif ($daysLeft <= 7)  $status = 'warning';

        return [
            'days_remaining' => $daysLeft,
            'refill_date'    => $refillDate,
            'remaining_bdt'  => $remaining,
            'status'         => $status,
        ];
    }

    // ----------------------------------------------------------------
    //  WIDGET 2 — Smart Bazaar Suggestions (7-day moving avg × event multiplier)
    // ----------------------------------------------------------------

    /**
     * Suggest raw purchase quantities for tomorrow's bazaar.
     * Formula: Suggested Qty = ROUND(7-day AVG sold_qty * tomorrow's impact_multiplier, 1)
     *
     * Uses a LEFT JOIN on calendar_events so neutral days (multiplier=1) still return results.
     *
     * @return array
     */
    public function getTomorrowBazaarSuggestions() {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $stmt = $this->db->prepare("
            SELECT
                i.item_name,
                ROUND(AVG(ds.sold_qty), 2)                                       AS avg_sold_7d,
                COALESCE(ce.impact_multiplier, 1.00)                             AS impact_multiplier,
                ROUND(AVG(ds.sold_qty) * COALESCE(ce.impact_multiplier, 1.00), 1) AS suggested_qty
            FROM daily_stocks ds
            JOIN items i
                ON ds.item_id = i.id
            LEFT JOIN calendar_events ce
                ON ce.event_date = :tomorrow
            WHERE ds.log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              AND ds.log_date < CURDATE()
            GROUP BY i.id, i.item_name, ce.impact_multiplier
            ORDER BY suggested_qty DESC
        ");
        $stmt->execute([':tomorrow' => $tomorrow]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ----------------------------------------------------------------
    //  WIDGET 3 — Pending Supplier Dues
    // ----------------------------------------------------------------

    /**
     * Return all suppliers with outstanding dues, highest first.
     *
     * @return array
     */
    public function getSupplierDues() {
        $stmt = $this->db->query("
            SELECT name, contact, total_due
            FROM   suppliers
            WHERE  total_due > 0
            ORDER  BY total_due DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ----------------------------------------------------------------
    //  HELPER — Pending Orders Today (used in header stat chip)
    // ----------------------------------------------------------------

    /**
     * Count today's pending advance orders
     *
     * @return int
     */
    private function getPendingOrdersToday() {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM advance_orders
            WHERE delivery_date = :today AND status = 'pending'
        ");
        $stmt->execute([':today' => date('Y-m-d')]);
        return (int) $stmt->fetchColumn();
    }
}
