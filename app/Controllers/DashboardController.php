<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

/**
 * Dashboard Controller — Smart dashboard with forecasting widgets
 */
class DashboardController extends Controller {
public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function index() {
        $today = date('Y-m-d');

        $this->view('dashboard/index', [
            'pageTitle'         => __('dashboard'),
            'activeNav'         => 'home',
            'userName'          => currentLang() === 'bn'
                ? ($_SESSION['user_name_bn'] ?? $_SESSION['user_name'] ?? 'Staff')
                : ($_SESSION['user_name'] ?? 'Staff'),
            'role'              => $_SESSION['role'] ?? 'staff',
            'gasInfo'           => $this->getGasDepletion(),
            'bazaarSuggestions' => $this->getBazaarSuggestions(),
            'lowStockItems'     => $this->getLowStockItems(),
            'todayStats'        => $this->getTodayStats($today),
            'pendingOrders'     => $this->getPendingOrders(),
            'upcomingEvent'     => $this->getUpcomingEvent(),
        ]);
    }

    // ── Gas Depletion Forecast ─────────────────────────────────
    private function getGasDepletion(): array {
        $stmt = $this->db->prepare("
            SELECT remaining_balance, daily_amount, expense_date, name
            FROM expenses
            WHERE is_spread = 1 AND LOWER(category) = 'gas'
              AND remaining_balance > 0 AND is_active = 1
            ORDER BY expense_date DESC LIMIT 1
        ");
        $stmt->execute();
        $gas = $stmt->fetch();

        if (!$gas || (float)$gas['daily_amount'] <= 0) {
            return ['days' => null, 'date' => 'N/A', 'status' => 'no_data'];
        }

        $remaining = (float)$gas['remaining_balance'];
        $daily     = (float)$gas['daily_amount'];
        $days      = (int)floor($remaining / $daily);
        $refill    = date('D, d M Y', strtotime("+{$days} days"));

        return [
            'days'      => $days,
            'date'      => $refill,
            'remaining' => $remaining,
            'daily'     => $daily,
            'status'    => $days <= 3 ? 'critical' : ($days <= 7 ? 'warning' : 'ok'),
        ];
    }

    // ── Bazaar Suggestions (7-day avg × event multiplier) ──────
    private function getBazaarSuggestions(): array {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $stmt = $this->db->prepare("
            SELECT i.item_name, i.item_name_bn,
                   ROUND(AVG(sc.sold_qty), 1) AS avg_sold,
                   COALESCE(ce.impact_multiplier, 1.00) AS multiplier,
                   ROUND(AVG(sc.sold_qty) * COALESCE(ce.impact_multiplier, 1.00), 1) AS suggested
            FROM shift_closings sc
            JOIN items i ON sc.item_id = i.id
            LEFT JOIN calendar_events ce ON ce.event_date = :tomorrow
            WHERE sc.log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              AND sc.log_date < CURDATE()
            GROUP BY i.id, i.item_name, i.item_name_bn, ce.impact_multiplier
            ORDER BY suggested DESC
        ");
        $stmt->execute([':tomorrow' => $tomorrow]);
        return $stmt->fetchAll();
    }

    // ── Low Stock Alerts ──────────────────────────────────────
    private function getLowStockItems(): array {
        // Check sellable items
        $sellable = $this->db->query("
            SELECT i.item_name, i.item_name_bn, i.min_stock_threshold,
                   COALESCE(ds.opening_qty, 0) AS current_qty, 'sellable' AS type
            FROM items i
            LEFT JOIN daily_stocks ds ON i.id = ds.item_id AND ds.log_date = CURDATE()
            WHERE i.min_stock_threshold > 0
              AND COALESCE(ds.opening_qty, 0) < i.min_stock_threshold
              AND i.is_active = 1
        ")->fetchAll();

        // Check raw materials
        $raw = $this->db->query("
            SELECT item_name, item_name_bn, min_stock_threshold,
                   current_qty, 'raw' AS type
            FROM raw_inventory
            WHERE min_stock_threshold > 0
              AND current_qty < min_stock_threshold
        ")->fetchAll();

        return array_merge($sellable, $raw);
    }

    // ── Today's Sales Stats ───────────────────────────────────
    private function getTodayStats(string $date): array {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(sc.sold_qty), 0) AS total_sold,
                COALESCE(SUM(sc.total_sales_amount), 0) AS total_revenue,
                COALESCE(SUM(sc.complimentary_qty), 0) AS total_comp,
                COALESCE(SUM(sc.due_qty), 0) AS total_due
            FROM shift_closings sc
            WHERE sc.log_date = :date
        ");
        $stmt->execute([':date' => $date]);
        $stats = $stmt->fetch();

        // Check which shifts are closed
        $shifts = $this->db->prepare("
            SELECT DISTINCT shift FROM shift_closings WHERE log_date = :date
        ");
        $shifts->execute([':date' => $date]);
        $closedShifts = $shifts->fetchAll(PDO::FETCH_COLUMN);

        return array_merge($stats ?: [], ['closed_shifts' => $closedShifts]);
    }

    // ── Pending Advance Orders ────────────────────────────────
    private function getPendingOrders(): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM advance_orders
            WHERE delivery_date = :today AND status = 'pending'
        ");
        $stmt->execute([':today' => date('Y-m-d')]);
        return (int)$stmt->fetchColumn();
    }

    // ── Upcoming Calendar Event ───────────────────────────────
    private function getUpcomingEvent(): ?array {
        $stmt = $this->db->prepare("
            SELECT event_name, event_name_bn, event_date, impact_multiplier
            FROM calendar_events
            WHERE event_date >= CURDATE()
            ORDER BY event_date ASC LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }
}
