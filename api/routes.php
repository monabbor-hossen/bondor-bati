<?php
/**
 * API Routes
 * 
 * Simple REST-style router for the Bondor Bati POS.
 * All requests go through this file.
 * 
 * Usage: /api/routes.php?action=<action_name>
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/POSController.php';
require_once __DIR__ . '/../controllers/InventoryController.php';
require_once __DIR__ . '/../controllers/ExpenseController.php';
require_once __DIR__ . '/../controllers/ReportController.php';
require_once __DIR__ . '/../controllers/BazaarController.php';
require_once __DIR__ . '/../controllers/AdvanceOrderController.php';

// Get the requested action
$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Helper: send JSON response
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper: get POST JSON body
function getPostData() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    // Fallback to form-encoded POST data
    if (!$data) {
        $data = $_POST;
    }
    return $data;
}

// ─── ROUTE DISPATCHER ────────────────────────────────────────────────

switch ($action) {

    // ═══════════════════════════════════════════
    //  AUTH
    // ═══════════════════════════════════════════
    case 'login':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $data = getPostData();
        $auth = new AuthController();
        $result = $auth->login($data['username'] ?? '', $data['password'] ?? '');
        jsonResponse($result, $result['success'] ? 200 : 401);
        break;

    case 'login_token':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $data = getPostData();
        $auth = new AuthController();
        $result = $auth->loginWithToken($data['token'] ?? '');
        jsonResponse($result, $result['success'] ? 200 : 401);
        break;

    case 'logout':
        $auth = new AuthController();
        jsonResponse($auth->logout());
        break;

    // ═══════════════════════════════════════════
    //  POS — Daily Closing
    // ═══════════════════════════════════════════
    case 'submit_closing':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $data = getPostData();
        $pos = new POSController();
        $results = [];

        // Expects: items[item_id][closing_qty] and items[item_id][wastage_qty]
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item_id => $values) {
                $closing_qty = (int) ($values['closing_qty'] ?? 0);
                $wastage_qty = (int) ($values['wastage_qty'] ?? 0);
                $result = $pos->closeDailyStock((int) $item_id, $closing_qty, $wastage_qty);
                $results[] = [
                    'item_id' => (int) $item_id,
                    'result'  => $result
                ];
            }

            // Could aggregate success/failure, but let's assume partial success is okay
            jsonResponse([
                'success' => true,
                'message' => 'Closing data processed',
                'details' => $results
            ]);
        } else {
            jsonResponse(['error' => 'Invalid data format'], 400);
        }
        break;

    case 'log_complimentary':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $data = getPostData();
        $pos = new POSController();
        $result = $pos->logComplimentaryFood((int) ($data['item_id'] ?? 0), (int) ($data['qty'] ?? 0));
        jsonResponse($result);
        break;

    case 'record_customer_due':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $data = getPostData();
        $pos = new POSController();
        $result = $pos->recordCustomerDue(
            $data['customer_name'] ?? '',
            $data['phone']         ?? '',
            (float) ($data['due_amount'] ?? 0),
            (int)   ($data['item_id']    ?? 0),
            (int)   ($data['qty']        ?? 0)
        );
        jsonResponse($result);
        break;

    // ═══════════════════════════════════════════
    //  INVENTORY
    // ═══════════════════════════════════════════
    case 'receive_stock':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $data = getPostData();
        $inv = new InventoryController();
        $result = $inv->receiveFromSupplier(
            $data['item_name']   ?? '',
            (float) ($data['qty']        ?? 0),
            (float) ($data['unit_price'] ?? 0),
            (int)   ($data['supplier_id'] ?? 0)
        );
        jsonResponse(['success' => $result]);
        break;

    case 'transfer_to_daily':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $data = getPostData();
        $inv = new InventoryController();
        $result = $inv->transferToDailyStock(
            (int)   ($data['item_id']       ?? 0),
            $data['raw_item_name']          ?? '',
            (float) ($data['processed_qty'] ?? 0),
            (int)   ($data['carry_forward'] ?? 0)
        );
        jsonResponse($result);
        break;

    case 'carry_forward':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $data = getPostData();
        $inv = new InventoryController();
        $result = $inv->carryForwardFromYesterday((int) ($data['item_id'] ?? 0));
        jsonResponse($result);
        break;

    case 'get_raw_inventory':
        $inv = new InventoryController();
        jsonResponse(['success' => true, 'data' => $inv->getRawInventory()]);
        break;

    case 'process_raw_to_shop':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $data = getPostData();
        $inv = new InventoryController();
        $result = $inv->processRawToShop(
            $data['raw_item_name'] ?? '',
            (int) ($data['shop_item_id'] ?? 0),
            (float) ($data['qty_processed'] ?? 0)
        );
        jsonResponse($result);
        break;

    case 'get_daily_stock':
        $inv = new InventoryController();
        jsonResponse(['success' => true, 'data' => $inv->getTodaysDailyStock()]);
        break;

    // ═══════════════════════════════════════════
    //  EXPENSES
    // ═══════════════════════════════════════════
    case 'log_expense':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $data = getPostData();
        $exp = new ExpenseController();
        $result = $exp->logExpense(
            $data['category']     ?? 'Fixed',
            $data['name']         ?? '',
            (float) ($data['total_amount'] ?? 0)
        );
        jsonResponse($result);
        break;

    case 'log_gas':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $data = getPostData();
        $exp = new ExpenseController();
        $result = $exp->logGasExpense(
            $data['name']          ?? '',
            (float) ($data['total_amount'] ?? 0),
            (int)   ($data['spread_days']  ?? 1)
        );
        jsonResponse($result);
        break;

    case 'process_spreads':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $exp = new ExpenseController();
        jsonResponse($exp->processSpreadDeductions());
        break;

    // ═══════════════════════════════════════════
    //  REPORTS
    // ═══════════════════════════════════════════
    case 'daily_profit':
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $report = new ReportController();
        $result = $report->calculateDailyNetProfit($date);
        jsonResponse($result);
        break;

    // ═══════════════════════════════════════════
    //  BAZAAR
    // ═══════════════════════════════════════════
    case 'create_ledger':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $data = getPostData();
        $baz = new BazaarController();
        $result = $baz->createLedger($data['date'] ?? date('Y-m-d'), $data['notes'] ?? null);
        jsonResponse($result);
        break;

    case 'submit_bazaar':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $data = getPostData();
        $baz = new BazaarController();
        $result = $baz->submitDailyBazaar((int)($data['ledger_id'] ?? 0), $data['items'] ?? []);
        jsonResponse($result);
        break;

    case 'get_bazaar_items':
        $baz = new BazaarController();
        $ledger_id = (int)($_GET['ledger_id'] ?? 0);
        jsonResponse(['success' => true, 'data' => $baz->getLedgerItems($ledger_id)]);
        break;

    // ═══════════════════════════════════════════
    //  ADVANCE ORDERS
    // ═══════════════════════════════════════════
    case 'create_advance_order':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $data = getPostData();
        $aoc = new AdvanceOrderController();
        $result = $aoc->createAdvanceOrder(
            $data['delivery_date'] ?? '',
            [
                'name'  => $data['customer_name']  ?? '',
                'phone' => $data['customer_phone'] ?? '',
                'notes' => $data['notes']           ?? null
            ],
            (float) ($data['total_bill']    ?? 0),
            (float) ($data['advance_paid']  ?? 0),
            $data['items'] ?? []
        );
        jsonResponse($result);
        break;

    case 'get_pending_orders':
        $date = $_GET['date'] ?? date('Y-m-d');
        $aoc = new AdvanceOrderController();
        $result = $aoc->getPendingOrdersForDate($date);
        jsonResponse(['success' => true, 'data' => $result]);
        break;

    case 'update_order_status':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $data = getPostData();
        $aoc = new AdvanceOrderController();
        $result = $aoc->updateOrderStatus(
            (int) ($data['order_id'] ?? 0),
            $data['status'] ?? ''
        );
        jsonResponse($result);
        break;

    // ═══════════════════════════════════════════
    //  DEFAULT
    // ═══════════════════════════════════════════
    default:
        jsonResponse([
            'success' => false,
            'message' => 'Unknown action. Available: login, login_token, logout, submit_closing, log_complimentary, record_customer_due, receive_stock, transfer_to_daily, carry_forward, get_raw_inventory, process_raw_to_shop, get_daily_stock, log_expense, log_gas, process_spreads, daily_profit, create_ledger, submit_bazaar, get_bazaar_items, create_advance_order, get_pending_orders, update_order_status'
        ], 400);
        break;
}
?>
