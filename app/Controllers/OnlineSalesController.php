<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;

/**
 * OnlineSalesController — Ledger for Foodpanda, Pathao, Foodi
 * Tracks gross sales, platform commissions, net receivables, and payouts.
 */
class OnlineSalesController extends Controller {

    public function __construct() {
        $this->db = (new Database())->getConnection();

        // Silently create tables if they don't exist
        $this->db->exec("CREATE TABLE IF NOT EXISTS online_sales_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            platform VARCHAR(50),
            log_date DATE,
            gross_amount DECIMAL(10,2) DEFAULT 0,
            commission_amount DECIMAL(10,2) DEFAULT 0,
            net_amount DECIMAL(10,2) DEFAULT 0,
            UNIQUE KEY uq_platform_date (platform, log_date)
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS online_payouts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            platform VARCHAR(50),
            amount DECIMAL(10,2) DEFAULT 0,
            payout_date DATE
        )");
    }

    /**
     * Main ledger dashboard — balances, recent logs
     */
    public function index(): void {
        // Running balance per platform (net earned minus payouts received)
        $balStmt = $this->db->query("
            SELECT
                p.platform,
                (
                    COALESCE((SELECT SUM(net_amount)  FROM online_sales_logs WHERE platform = p.platform), 0)
                  - COALESCE((SELECT SUM(amount)      FROM online_payouts      WHERE platform = p.platform), 0)
                ) AS balance
            FROM (
                SELECT 'Foodpanda' AS platform
                UNION SELECT 'Pathao'
                UNION SELECT 'Foodi'
            ) p
        ");
        $balances = $balStmt->fetchAll();

        // Recent 30 sales entries
        $salesStmt = $this->db->query("
            SELECT 'sale' AS entry_type, platform, log_date AS entry_date,
                   gross_amount, commission_amount, net_amount, NULL AS payout_amount
            FROM online_sales_logs
            ORDER BY log_date DESC, id DESC
            LIMIT 30
        ");
        $salesLogs = $salesStmt->fetchAll();

        // Recent 20 payouts
        $payStmt = $this->db->query("
            SELECT 'payout' AS entry_type, platform, payout_date AS entry_date,
                   NULL AS gross_amount, NULL AS commission_amount, NULL AS net_amount, amount AS payout_amount
            FROM online_payouts
            ORDER BY payout_date DESC, id DESC
            LIMIT 20
        ");
        $payoutLogs = $payStmt->fetchAll();

        // Merge and sort by date desc
        $logs = array_merge($salesLogs, $payoutLogs);
        usort($logs, fn($a, $b) => strcmp($b['entry_date'], $a['entry_date']));
        $logs = array_slice($logs, 0, 40);

        $this->view('finance/online_sales', [
            'pageTitle'  => __('online_platforms'),
            'activeNav'  => 'online',
            'balances'   => $balances,
            'logs'       => $logs,
        ]);
    }

    /**
     * POST: Log or update a daily platform sale
     * INSERT … ON DUPLICATE KEY UPDATE
     */
    public function logDailySale(): void {
        $data       = json_decode(file_get_contents('php://input'), true);
        $platform   = trim($data['platform']   ?? '');
        $logDate    = trim($data['log_date']   ?? '');
        $gross      = (float)($data['gross_amount']      ?? 0);
        $commission = (float)($data['commission_amount'] ?? 0);
        $net        = round($gross - $commission, 2);

        $allowed = ['Foodpanda', 'Pathao', 'Foodi'];
        if (!in_array($platform, $allowed, true) || empty($logDate) || $gross < 0) {
            $this->json(['success' => false, 'error' => 'Invalid data']);
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO online_sales_logs (platform, log_date, gross_amount, commission_amount, net_amount)
            VALUES (:platform, :log_date, :gross, :commission, :net)
            ON DUPLICATE KEY UPDATE
                gross_amount      = VALUES(gross_amount),
                commission_amount = VALUES(commission_amount),
                net_amount        = VALUES(net_amount)
        ");
        $stmt->execute([
            ':platform'   => $platform,
            ':log_date'   => $logDate,
            ':gross'      => $gross,
            ':commission' => $commission,
            ':net'        => $net,
        ]);

        $this->json(['success' => true, 'net' => $net]);
    }

    /**
     * POST: Log a cash payout received from a platform
     */
    public function logPayout(): void {
        $data       = json_decode(file_get_contents('php://input'), true);
        $platform   = trim($data['platform']     ?? '');
        $payDate    = trim($data['payout_date']  ?? '');
        $amount     = (float)($data['amount']    ?? 0);

        $allowed = ['Foodpanda', 'Pathao', 'Foodi'];
        if (!in_array($platform, $allowed, true) || empty($payDate) || $amount <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid data']);
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO online_payouts (platform, amount, payout_date)
            VALUES (:platform, :amount, :payout_date)
        ");
        $stmt->execute([
            ':platform'    => $platform,
            ':amount'      => $amount,
            ':payout_date' => $payDate,
        ]);

        $this->json(['success' => true]);
    }
}
