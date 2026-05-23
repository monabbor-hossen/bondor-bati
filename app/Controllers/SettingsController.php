<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;

class SettingsController extends Controller {
    public function index() {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->redirect('?url=bazaar');
        }

        $this->view('settings/index', [
            'pageTitle' => __('settings_title'),
            'activeNav' => 'settings'
        ]);
    }

    public function priceCalculator() {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->redirect('?url=bazaar');
        }

        $db = (new Database())->getConnection();

        // Safe migration — create table if not exists
        $db->exec("CREATE TABLE IF NOT EXISTS daily_price_logs (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            item_name   VARCHAR(100) NOT NULL,
            category    VARCHAR(50),
            raw_price   DECIMAL(10,2) DEFAULT 0.00,
            final_price DECIMAL(10,2) DEFAULT 0.00,
            log_date    DATE NOT NULL,
            UNIQUE KEY unique_item_date (item_name, log_date)
        )");

        $rawItems = $db->query("SELECT id, item_name, avg_unit_price, unit FROM raw_inventory ORDER BY item_name")->fetchAll();
        $logs     = $db->query("SELECT * FROM daily_price_logs ORDER BY log_date DESC, id DESC LIMIT 50")->fetchAll();

        $this->view('settings/calculator', [
            'pageTitle' => __('price_calculator'),
            'activeNav' => 'settings',
            'rawItems'  => $rawItems,
            'logs'      => $logs,
        ]);
    }

    public function savePriceLog() {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->json(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $data      = json_decode(file_get_contents('php://input'), true);
        $itemName  = trim($data['item_name'] ?? '');
        $category  = trim($data['category']  ?? '');
        $rawPrice  = (float)($data['raw_price']   ?? 0);
        $finalPrice= (float)($data['final_price']  ?? 0);

        if (empty($itemName) || $finalPrice <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid data']);
            return;
        }

        $db      = (new Database())->getConnection();
        $logDate = date('Y-m-d'); // always use server business date

        $stmt = $db->prepare("
            INSERT INTO daily_price_logs (item_name, category, raw_price, final_price, log_date)
            VALUES (:item_name, :category, :raw_price, :final_price, :log_date)
            ON DUPLICATE KEY UPDATE
                category    = VALUES(category),
                raw_price   = VALUES(raw_price),
                final_price = VALUES(final_price)
        ");
        $stmt->execute([
            ':item_name'   => $itemName,
            ':category'    => $category,
            ':raw_price'   => $rawPrice,
            ':final_price' => $finalPrice,
            ':log_date'    => $logDate,
        ]);

        $this->json(['success' => true]);
    }
}
