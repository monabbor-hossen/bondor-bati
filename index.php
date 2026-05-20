<?php
/**
 * Bondor Bati POS — Main Router
 * Routes to the correct page and loads required data.
 */
session_start();

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Translate.php';
require_once __DIR__ . '/models/DashboardModel.php';
require_once __DIR__ . '/models/ForecastingModel.php';
require_once __DIR__ . '/models/InventoryModel.php';

// Handle language toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle_lang') {
    $_SESSION['lang'] = ($_SESSION['lang'] ?? 'en') === 'en' ? 'bn' : 'en';
    $params = $_GET;
    unset($params['action']);
    $queryString = http_build_query($params);
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?') . ($queryString ? '?' . $queryString : ''));
    exit;
}

$db = Database::getInstance()->getConnection();

// ── Auth Guard ─────────────────────────────────────────────────
if (!checkAuth()) {
    header('Location: login.php');
    exit;
}

$authUser = currentUser();
$page = $_GET['page'] ?? 'dashboard';

// Permission check — redirect to dashboard if not allowed
if (!canAccess($page)) {
    // Try to find the first allowed page as fallback
    $allowed = allowedPages();
    $page = !empty($allowed) ? $allowed[0] : 'dashboard';
}

// Helper: format currency
function tk($amount) {
    return '৳ ' . number_format((float)$amount, 0);
}

// ── Page Data Loaders ──────────────────────────────────────────
switch ($page) {

    case 'dashboard':
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $dashModel = new DashboardModel();
        $forecastModel = new ForecastingModel();

        $cashData = $dashModel->getCashInDrawer($today);
        $profitData = $dashModel->getNetProfit($today);
        $profitYesterday = $dashModel->getNetProfit($yesterday);
        $profitTrend = $profitYesterday['net_profit'] != 0
            ? round(($profitData['net_profit'] - $profitYesterday['net_profit']) / abs($profitYesterday['net_profit']) * 100)
            : 0;

        // Custom Range Report Logic
        $range = $_GET['range'] ?? 'daily';
        $reportFrom = $today;
        $reportTo = $today;
        if ($range === 'monthly') {
            $reportFrom = date('Y-m-01');
            $reportTo = date('Y-m-t');
        } elseif ($range === 'lifetime') {
            $reportFrom = '2000-01-01';
            $reportTo = $today;
        } elseif ($range === 'custom') {
            $reportFrom = $_GET['from_date'] ?? $today;
            $reportTo = $_GET['to_date'] ?? $today;
        }
        $rangeReport = $dashModel->getRangeReport($reportFrom, $reportTo);

        $nextGasDate = $forecastModel->getNextGasRefillDate();
        $gasDaysLeft = $nextGasDate ? max(0, (int)((strtotime($nextGasDate) - time()) / 86400)) : null;
        $bazaarSuggestions = $forecastModel->getSmartBazaarSuggestions();
        $supplierDues = $forecastModel->getSupplierDues();

        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $stmtEv = $db->prepare("SELECT event_name, impact_multiplier FROM calendar_events WHERE event_date = :d LIMIT 1");
        $stmtEv->execute(['d' => $tomorrow]);
        $tomorrowEvent = $stmtEv->fetch();

        $stmtOrd = $db->prepare("SELECT * FROM advance_orders WHERE delivery_date = :d AND status = 'Pending'");
        $stmtOrd->execute(['d' => $today]);
        $pendingOrders = $stmtOrd->fetchAll();

        $stmtCd = $db->prepare("SELECT * FROM customer_dues WHERE status = 'Unpaid' ORDER BY log_date DESC LIMIT 5");
        $stmtCd->execute();
        $customerDues = $stmtCd->fetchAll();

        $totalItems = $db->query("SELECT COUNT(*) FROM items")->fetchColumn();
        $totalStaff = $db->query("SELECT COUNT(*) FROM users WHERE role='STAFF' AND is_active=1")->fetchColumn();

        $lowStockItems = $db->query("
            SELECT i.id, i.item_name, i.min_threshold,
                   (SELECT COALESCE(ds.closing_qty, ds.opening_qty, 0) 
                    FROM daily_stocks ds 
                    WHERE ds.item_id = i.id 
                    ORDER BY ds.log_date DESC, FIELD(ds.shift, 'Night', 'Evening', 'Morning') DESC 
                    LIMIT 1) as current_qty
            FROM items i
            HAVING current_qty < i.min_threshold
        ")->fetchAll();

        $contentView = __DIR__ . '/views/dashboard/index.php';
        break;

    case 'items':
        $items = $db->query("SELECT * FROM items ORDER BY id DESC")->fetchAll();
        $rawInventory = $db->query("SELECT * FROM raw_inventory ORDER BY item_name")->fetchAll();
        $contentView = __DIR__ . '/views/items/index.php';
        break;

    case 'suppliers':
        $suppliers = $db->query("SELECT * FROM suppliers ORDER BY id DESC")->fetchAll();
        $contentView = __DIR__ . '/views/suppliers/index.php';
        break;

    case 'morning':
        $today = date('Y-m-d');
        $shift = $_GET['shift'] ?? 'Morning';
        $items = $db->query("SELECT * FROM items ORDER BY item_name")->fetchAll();
        $suppliers = $db->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();

        // Today's bazaar ledger
        $stmtL = $db->prepare("SELECT * FROM bazaar_ledgers WHERE log_date = :d");
        $stmtL->execute(['d' => $today]);
        $todayLedger = $stmtL->fetch();

        // Yesterday's bazaar ledger (for carried forward cash)
        $stmtY = $db->prepare("SELECT carry_forward_cash FROM bazaar_ledgers WHERE log_date = :d");
        $stmtY->execute(['d' => date('Y-m-d', strtotime('-1 day'))]);
        $yesterdayLedger = $stmtY->fetch();
        $carriedAdvance = $yesterdayLedger ? (float)$yesterdayLedger['carry_forward_cash'] : 0;

        $bazaarItems = [];
        if ($todayLedger) {
            $stmtBI = $db->prepare("SELECT bi.*, s.name as supplier_name FROM bazaar_items bi LEFT JOIN suppliers s ON bi.supplier_id = s.id WHERE bi.ledger_id = :lid");
            $stmtBI->execute(['lid' => $todayLedger['id']]);
            $bazaarItems = $stmtBI->fetchAll();
        }

        // Today's stock entries for this shift
        $stmtDS = $db->prepare("SELECT ds.*, i.item_name, i.selling_price FROM daily_stocks ds JOIN items i ON ds.item_id = i.id WHERE ds.log_date = :d AND ds.shift = :shift");
        $stmtDS->execute(['d' => $today, 'shift' => $shift]);
        $todayStocks = $stmtDS->fetchAll();

        // Map existing stock records
        $todayStocksMap = [];
        foreach ($todayStocks as $ts) {
            $todayStocksMap[$ts['item_id']] = $ts;
        }

        // Fetch Carry Forward from previous shift
        $prevDate = $today;
        $prevShift = 'Night';
        if ($shift === 'Morning') {
            $prevDate = date('Y-m-d', strtotime('-1 day'));
            $prevShift = 'Night';
        } elseif ($shift === 'Evening') {
            $prevShift = 'Morning';
        } elseif ($shift === 'Night') {
            $prevShift = 'Evening';
        }

        $stmtPrev = $db->prepare("SELECT item_id, closing_qty FROM daily_stocks WHERE log_date = :d AND shift = :shift");
        $stmtPrev->execute(['d' => $prevDate, 'shift' => $prevShift]);
        $prevClosings = $stmtPrev->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        // Pending advance orders for today
        $stmtOrd = $db->prepare("SELECT * FROM advance_orders WHERE delivery_date = :d AND status = 'Pending'");
        $stmtOrd->execute(['d' => $today]);
        $pendingOrders = $stmtOrd->fetchAll();

        $contentView = __DIR__ . '/views/morning/index.php';
        break;

    case 'service':
        $today = date('Y-m-d');
        $shift = $_GET['shift'] ?? 'Morning';
        $items = $db->query("SELECT * FROM items ORDER BY item_name")->fetchAll();

        // Today's stocks for this shift (for complimentary logging)
        $stmtDS = $db->prepare("SELECT ds.*, i.item_name FROM daily_stocks ds JOIN items i ON ds.item_id = i.id WHERE ds.log_date = :d AND ds.shift = :shift");
        $stmtDS->execute(['d' => $today, 'shift' => $shift]);
        $todayStocks = $stmtDS->fetchAll();

        // Customer dues
        $customerDues = $db->query("SELECT * FROM customer_dues ORDER BY CASE WHEN status='Unpaid' THEN 0 ELSE 1 END, log_date DESC")->fetchAll();

        $contentView = __DIR__ . '/views/service/index.php';
        break;

    case 'closing':
        $today = date('Y-m-d');
        $shift = $_GET['shift'] ?? 'Morning';
        $dashModel = new DashboardModel();

        // Today's stocks for closing in this shift
        $stmtDS = $db->prepare("SELECT ds.*, i.item_name, i.selling_price, i.cost_price FROM daily_stocks ds JOIN items i ON ds.item_id = i.id WHERE ds.log_date = :d AND ds.shift = :shift");
        $stmtDS->execute(['d' => $today, 'shift' => $shift]);
        $todayStocks = $stmtDS->fetchAll();

        $cashData = $dashModel->getCashInDrawer($today, $shift);
        $profitData = $dashModel->getNetProfit($today, $shift);

        $contentView = __DIR__ . '/views/closing/index.php';
        break;

    case 'forecast':
        $forecastModel = new ForecastingModel();
        $nextGasDate = $forecastModel->getNextGasRefillDate();
        $gasDaysLeft = $nextGasDate ? max(0, (int)((strtotime($nextGasDate) - time()) / 86400)) : null;
        $bazaarSuggestions = $forecastModel->getSmartBazaarSuggestions();

        $events = $db->query("SELECT * FROM calendar_events ORDER BY event_date ASC")->fetchAll();
        $orders = $db->query("SELECT * FROM advance_orders ORDER BY delivery_date DESC")->fetchAll();
        $items = $db->query("SELECT * FROM items ORDER BY item_name")->fetchAll();
        $expenses = $db->query("SELECT * FROM expenses ORDER BY expense_date DESC")->fetchAll();

        $contentView = __DIR__ . '/views/forecast/index.php';
        break;

    case 'staff':
        $staff = $db->query("SELECT u.*, ss.monthly_salary, ss.daily_rate, ss.start_date, ss.id as salary_id FROM users u LEFT JOIN staff_salaries ss ON u.id = ss.user_id AND ss.end_date IS NULL ORDER BY u.id DESC")->fetchAll();
        $suppliers = $db->query("SELECT * FROM suppliers ORDER BY id DESC")->fetchAll();

        // Load permissions for each staff member
        $staffPermissions = [];
        $stmtPerms = $db->prepare("SELECT page_slug FROM staff_permissions WHERE user_id = :uid");
        foreach ($staff as $s) {
            $stmtPerms->execute(['uid' => $s['id']]);
            $staffPermissions[$s['id']] = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);
        }

        $today = date('Y-m-d');
        $month = date('Y-m');
        $stmtAtt = $db->prepare("SELECT al.*, u.name FROM attendance_logs al JOIN users u ON al.user_id = u.id WHERE al.absent_date LIKE :m ORDER BY al.absent_date DESC");
        $stmtAtt->execute(['m' => $month . '%']);
        $attendance = $stmtAtt->fetchAll();

        $contentView = __DIR__ . '/views/staff/index.php';
        break;

    default:
        $contentView = __DIR__ . '/views/dashboard/index.php';
        break;
}

$pageTitle = ucfirst($page);
include __DIR__ . '/views/layouts/main.php';
