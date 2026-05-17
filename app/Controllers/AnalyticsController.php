<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

class AnalyticsController extends Controller {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function analytics() {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->redirect('?url=home');
            return;
        }

        $topItem = $this->getTopSellingItem();
        $wastageCost = $this->getMonthlyWastageCost();

        $suppliersStmt = $this->db->query("SELECT id, name, contact, total_due FROM suppliers WHERE total_due > 0 ORDER BY total_due DESC");
        $suppliers = $suppliersStmt->fetchAll(PDO::FETCH_ASSOC);

        $customerDuesStmt = $this->db->query("SELECT id, customer_name, phone, due_amount, log_date FROM customer_dues WHERE status = 'pending' OR status IS NULL ORDER BY due_amount DESC");
        $customerDues = $customerDuesStmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/analytics', [
            'pageTitle' => 'Owner Analytics',
            'activeNav' => 'analytics',
            'topItem' => $topItem,
            'wastageCost' => $wastageCost,
            'suppliers' => $suppliers,
            'customerDues' => $customerDues,
        ]);
    }

    public function getTopSellingItem() {
        $stmt = $this->db->prepare("
            SELECT i.id, i.item_name, SUM(ds.sold_qty) AS total_sold
            FROM daily_stocks ds
            JOIN items i ON ds.item_id = i.id
            WHERE ds.log_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY i.id, i.item_name
            ORDER BY total_sold DESC
            LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getMonthlyWastageCost() {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(ds.wastage_qty * i.cost_price), 0) AS total_wastage_cost
            FROM daily_stocks ds
            JOIN items i ON ds.item_id = i.id
            WHERE MONTH(ds.log_date) = MONTH(CURDATE()) AND YEAR(ds.log_date) = YEAR(CURDATE())
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) $result['total_wastage_cost'];
    }
}