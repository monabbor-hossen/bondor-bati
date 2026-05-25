<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;

/**
 * OnlineSalesController — Ledger for Foodpanda, Pathao, Foodi
 * Supports multiple orders per day per platform, with full edit/delete.
 */
class OnlineSalesController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->db = (new Database())->getConnection();

        $this->db->exec("CREATE TABLE IF NOT EXISTS online_sales_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            platform VARCHAR(50),
            log_date DATE,
            gross_amount DECIMAL(10,2) DEFAULT 0,
            commission_amount DECIMAL(10,2) DEFAULT 0,
            discount_amount DECIMAL(10,2) DEFAULT 0,
            net_amount DECIMAL(10,2) DEFAULT 0
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS online_payouts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            platform VARCHAR(50),
            amount DECIMAL(10,2) DEFAULT 0,
            payout_date DATE
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS online_sales_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            log_id INT NOT NULL,
            item_name VARCHAR(100) NOT NULL,
            qty DECIMAL(8,2) DEFAULT 0,
            unit_price DECIMAL(10,2) DEFAULT 0,
            total_price DECIMAL(10,2) DEFAULT 0,
            FOREIGN KEY (log_id) REFERENCES online_sales_logs(id) ON DELETE CASCADE
        )");

        // Drop the old UNIQUE key if it still exists (allows multiple orders per day)
        try {
            $this->db->exec("ALTER TABLE online_sales_logs DROP INDEX uq_platform_date");
        } catch (\Exception $e) { /* already dropped or never existed */ }
    }

    /**
     * Main ledger dashboard
     */
    public function index(): void {
        // Running balance per platform
        $balStmt = $this->db->query("
            SELECT p.platform,
                (
                    COALESCE((SELECT SUM(net_amount) FROM online_sales_logs WHERE platform = p.platform), 0)
                  - COALESCE((SELECT SUM(amount)     FROM online_payouts      WHERE platform = p.platform), 0)
                ) AS balance,
                (SELECT MAX(payout_date) FROM online_payouts WHERE platform = p.platform) AS last_payout_date
            FROM (SELECT 'Foodpanda' AS platform UNION SELECT 'Pathao' UNION SELECT 'Foodi') p
        ");
        $balances = $balStmt->fetchAll();

        // Recent 40 sale logs
        $salesStmt = $this->db->query("
            SELECT 'sale' AS entry_type, id AS log_id, platform, log_date AS entry_date,
                   gross_amount, commission_amount, discount_amount, net_amount, NULL AS payout_amount, NULL AS payout_id
            FROM online_sales_logs
            ORDER BY log_date DESC, id DESC
            LIMIT 40
        ");
        $salesLogs = $salesStmt->fetchAll();

        // Attach items to each sale log
        foreach ($salesLogs as &$sl) {
            $iStmt = $this->db->prepare(
                "SELECT item_name, qty, unit_price, total_price FROM online_sales_items WHERE log_id = :lid ORDER BY id"
            );
            $iStmt->execute([':lid' => $sl['log_id']]);
            $sl['items'] = $iStmt->fetchAll();
        }
        unset($sl);

        // Recent 20 payouts
        $payStmt = $this->db->query("
            SELECT 'payout' AS entry_type, NULL AS log_id, platform, payout_date AS entry_date,
                   NULL AS gross_amount, NULL AS commission_amount, NULL AS discount_amount, NULL AS net_amount,
                   amount AS payout_amount, id AS payout_id
            FROM online_payouts
            ORDER BY payout_date DESC, id DESC
            LIMIT 20
        ");
        $payoutLogs = $payStmt->fetchAll();

        $menuItems = $this->db->query(
            "SELECT id, item_name AS name, selling_price AS price, online_price FROM items WHERE is_active = 1 ORDER BY item_name"
        )->fetchAll();

        // Today's opening stock from close/prep page (daily_stocks)
        $today = date('Y-m-d');
        $stockStmt = $this->db->prepare("
            SELECT i.item_name, COALESCE(ds.opening_qty, 0) AS opening_qty
            FROM items i
            LEFT JOIN daily_stocks ds ON ds.item_id = i.id AND ds.log_date = :today
            WHERE i.is_active = 1
        ");
        $stockStmt->execute([':today' => $today]);
        $stockRows = $stockStmt->fetchAll();

        // Build stockMap: { "Item Name": opening_qty }
        $stockMap = [];
        foreach ($stockRows as $sr) {
            $stockMap[$sr['item_name']] = (float)$sr['opening_qty'];
        }

        $this->view('finance/online_sales', [
            'pageTitle'  => __('online_platforms'),
            'activeNav'  => 'online',
            'balances'   => $balances,
            'salesLogs'  => $salesLogs,
            'payoutLogs' => $payoutLogs,
            'menuItems'  => $menuItems,
            'stockMap'   => $stockMap,
        ]);
    }

    /**
     * POST: Insert a new sale OR update an existing one by log_id.
     * Gross is always derived from items sum — never trusted from client.
     */
    public function logDailySale(): void {
        $data       = json_decode(file_get_contents('php://input'), true);
        $logId      = (int)($data['log_id']          ?? 0);   // 0 = new, >0 = edit
        $platform   = trim($data['platform']          ?? '');
        $logDate    = trim($data['log_date']          ?? '');
        $commission = (float)($data['commission_amount'] ?? 0);
        $discount   = (float)($data['discount_amount']   ?? 0);
        $items      = $data['items'] ?? [];

        $allowed = ['Foodpanda', 'Pathao', 'Foodi'];
        if (!in_array($platform, $allowed, true) || empty($logDate) || !is_array($items) || empty($items)) {
            $this->json(['success' => false, 'error' => 'Invalid data: platform, date, and at least one item required.']);
            return;
        }

        // Validate + calculate gross from items
        $gross      = 0;
        $cleanItems = [];
        foreach ($items as $row) {
            $name  = trim($row['item_name'] ?? '');
            $qty   = (float)($row['qty']       ?? 0);
            $price = (float)($row['unit_price'] ?? 0);
            if (empty($name) || $qty <= 0 || $price < 0) continue;
            $total        = round($qty * $price, 2);
            $gross       += $total;
            $cleanItems[] = ['name' => $name, 'qty' => $qty, 'price' => $price, 'total' => $total];
        }

        if (empty($cleanItems)) {
            $this->json(['success' => false, 'error' => 'No valid items provided.']);
            return;
        }

        $gross = round($gross, 2);
        $net   = round($gross - $commission - $discount, 2);

        try {
            $this->db->beginTransaction();

            if ($logId > 0) {
                // ── EDIT: Update existing log header ─────────────────
                $upd = $this->db->prepare("
                    UPDATE online_sales_logs
                    SET platform = :platform, log_date = :log_date,
                        gross_amount = :gross, commission_amount = :commission, discount_amount = :discount, net_amount = :net
                    WHERE id = :id
                ");
                $upd->execute([
                    ':platform'   => $platform,
                    ':log_date'   => $logDate,
                    ':gross'      => $gross,
                    ':commission' => $commission,
                    ':discount'   => $discount,
                    ':net'        => $net,
                    ':id'         => $logId,
                ]);
            } else {
                // ── NEW: Plain INSERT (allows multiple per day) ───────
                $ins = $this->db->prepare("
                    INSERT INTO online_sales_logs (platform, log_date, gross_amount, commission_amount, discount_amount, net_amount)
                    VALUES (:platform, :log_date, :gross, :commission, :discount, :net)
                ");
                $ins->execute([
                    ':platform'   => $platform,
                    ':log_date'   => $logDate,
                    ':gross'      => $gross,
                    ':commission' => $commission,
                    ':discount'   => $discount,
                    ':net'        => $net,
                ]);
                $logId = (int)$this->db->lastInsertId();
            }

            // Delete existing items then re-insert (clean edit or fresh insert)
            $this->db->prepare("DELETE FROM online_sales_items WHERE log_id = :lid")->execute([':lid' => $logId]);

            $insItem = $this->db->prepare("
                INSERT INTO online_sales_items (log_id, item_name, qty, unit_price, total_price)
                VALUES (:log_id, :name, :qty, :price, :total)
            ");
            foreach ($cleanItems as $ci) {
                $insItem->execute([
                    ':log_id' => $logId,
                    ':name'   => $ci['name'],
                    ':qty'    => $ci['qty'],
                    ':price'  => $ci['price'],
                    ':total'  => $ci['total'],
                ]);
            }

            $this->db->commit();
            $this->json(['success' => true, 'log_id' => $logId, 'gross' => $gross, 'net' => $net]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * POST: Delete a sale log (items cascade automatically)
     */
    public function deleteSaleLog(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($data['id'] ?? 0);
        if ($id <= 0) { $this->json(['success' => false, 'error' => 'Invalid ID']); return; }
        $this->db->prepare("DELETE FROM online_sales_logs WHERE id = :id")->execute([':id' => $id]);
        $this->json(['success' => true]);
    }

    /**
     * POST: Insert or update a payout
     */
    public function logPayout(): void {
        $data      = json_decode(file_get_contents('php://input'), true);
        $payoutId  = (int)($data['payout_id']   ?? 0);
        $platform  = trim($data['platform']      ?? '');
        $payDate   = trim($data['payout_date']   ?? '');
        $amount    = (float)($data['amount']     ?? 0);

        $allowed = ['Foodpanda', 'Pathao', 'Foodi'];
        if (!in_array($platform, $allowed, true) || empty($payDate) || $amount <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid data']); return;
        }

        if ($payoutId > 0) {
            $stmt = $this->db->prepare(
                "UPDATE online_payouts SET platform = :platform, amount = :amount, payout_date = :payout_date WHERE id = :id"
            );
            $stmt->execute([':platform' => $platform, ':amount' => $amount, ':payout_date' => $payDate, ':id' => $payoutId]);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO online_payouts (platform, amount, payout_date) VALUES (:platform, :amount, :payout_date)"
            );
            $stmt->execute([':platform' => $platform, ':amount' => $amount, ':payout_date' => $payDate]);
        }

        $this->json(['success' => true]);
    }

    /**
     * POST: Delete a payout entry
     */
    public function deletePayout(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($data['id'] ?? 0);
        if ($id <= 0) { $this->json(['success' => false, 'error' => 'Invalid ID']); return; }
        $this->db->prepare("DELETE FROM online_payouts WHERE id = :id")->execute([':id' => $id]);
        $this->json(['success' => true]);
    }
}
