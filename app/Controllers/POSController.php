<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

class POSController extends Controller {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function index() {
        $today = date('Y-m-d');

        $stmt = $this->db->prepare("
            SELECT i.id, i.item_name, i.selling_price, i.cost_price,
                   COALESCE(ds.closing_qty, ds.opening_qty, 0) AS available_qty
            FROM items i
            LEFT JOIN daily_stocks ds ON i.id = ds.item_id AND ds.log_date = :today
            WHERE i.id IS NOT NULL
            ORDER BY i.item_name ASC
        ");
        $stmt->execute([':today' => $today]);
        $menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('pos/index', [
            'pageTitle'  => 'Point of Sale',
            'activeNav' => 'pos',
            'menuItems' => $menuItems,
        ]);
    }

    public function checkout() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $cart = $input['cart'] ?? [];

        if (empty($cart)) {
            echo json_encode(['success' => false, 'error' => 'Cart is empty']);
            return;
        }

        $today = date('Y-m-d');
        $receiptId = 'BB' . date('ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $itemsSold = [];
        $totalBill = 0;

        try {
            $this->db->beginTransaction();

            foreach ($cart as $item) {
                $itemId = (int) $item['item_id'];
                $qty = (float) $item['qty'];

                if ($qty <= 0) continue;

                $stmt = $this->db->prepare("
                    SELECT id, opening_qty, closing_qty, sold_qty
                    FROM daily_stocks
                    WHERE item_id = :item_id AND log_date = :today
                ");
                $stmt->execute([':item_id' => $itemId, ':today' => $today]);
                $stock = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($stock) {
                    $newSoldQty = (float)$stock['sold_qty'] + $qty;
                    $newClosingQty = (float)$stock['closing_qty'] - $qty;

                    $upd = $this->db->prepare("
                        UPDATE daily_stocks
                        SET sold_qty = :sold_qty, closing_qty = :closing_qty,
                            total_sales_amount = (selling_price * :sold_qty)
                        WHERE id = :id
                    ");
                    $upd->execute([
                        ':sold_qty' => $newSoldQty,
                        ':closing_qty' => max(0, $newClosingQty),
                        ':id' => $stock['id']
                    ]);
                } else {
                    $itemInfoStmt = $this->db->prepare("SELECT selling_price FROM items WHERE id = :id");
                    $itemInfoStmt->execute([':id' => $itemId]);
                    $price = $itemInfoStmt->fetch(PDO::FETCH_ASSOC)['selling_price'] ?? 0;

                    $ins = $this->db->prepare("
                        INSERT INTO daily_stocks (item_id, log_date, opening_qty, closing_qty, sold_qty, total_sales_amount)
                        VALUES (:item_id, :log_date, 0, -:qty, :qty, :total)
                    ");
                    $ins->execute([
                        ':item_id' => $itemId,
                        ':log_date' => $today,
                        ':qty' => $qty,
                        ':total' => $price * $qty
                    ]);
                }

                $priceStmt = $this->db->prepare("SELECT item_name, selling_price FROM items WHERE id = :id");
                $priceStmt->execute([':id' => $itemId]);
                $itemInfo = $priceStmt->fetch(PDO::FETCH_ASSOC);

                $itemsSold[] = [
                    'item_name' => $itemInfo['item_name'],
                    'qty' => $qty,
                    'unit_price' => $itemInfo['selling_price'],
                    'subtotal' => $itemInfo['selling_price'] * $qty
                ];
                $totalBill += $itemInfo['selling_price'] * $qty;
            }

            $this->db->commit();

            $_SESSION['last_receipt'] = [
                'receipt_id' => $receiptId,
                'items' => $itemsSold,
                'total' => $totalBill,
                'date' => $today . ' ' . date('H:i:s')
            ];

            echo json_encode([
                'success' => true,
                'receipt_id' => $receiptId,
                'redirect' => '?url=pos/receipt'
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function receipt() {
        $receiptData = $_SESSION['last_receipt'] ?? null;

        if (!$receiptData) {
            $this->redirect('?url=pos');
            return;
        }

        $this->view('pos/receipt', [
            'pageTitle' => 'Receipt',
            'activeNav' => 'pos',
            'receipt' => $receiptData
        ], false);
    }
}