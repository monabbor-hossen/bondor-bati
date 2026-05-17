<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

class AdminController extends Controller {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function index() {
        $this->redirect('?url=admin/analytics');
    }

    public function settleSupplierDue() {
        header('Content-Type: application/json');

        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Admin only']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $supplierId = (int) ($data['supplier_id'] ?? 0);
        $amountPaid = (float) ($data['amount_paid'] ?? 0);

        if (!$supplierId || $amountPaid <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid input']);
            return;
        }

        try {
            $stmt = $this->db->prepare("SELECT total_due FROM suppliers WHERE id = :id");
            $stmt->execute([':id' => $supplierId]);
            $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$supplier) {
                echo json_encode(['success' => false, 'error' => 'Supplier not found']);
                return;
            }

            $newDue = max(0, $supplier['total_due'] - $amountPaid);

            $upd = $this->db->prepare("UPDATE suppliers SET total_due = :new_due WHERE id = :id");
            $upd->execute([':new_due' => $newDue, ':id' => $supplierId]);

            echo json_encode(['success' => true, 'message' => 'Payment recorded']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function settleCustomerDue() {
        header('Content-Type: application/json');

        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Admin only']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $customerDueId = (int) ($data['customer_due_id'] ?? 0);

        if (!$customerDueId) {
            echo json_encode(['success' => false, 'error' => 'Invalid input']);
            return;
        }

        try {
            $upd = $this->db->prepare("UPDATE customer_dues SET status = 'paid', paid_date = CURDATE() WHERE id = :id");
            $upd->execute([':id' => $customerDueId]);

            echo json_encode(['success' => true, 'message' => 'Customer due settled']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}