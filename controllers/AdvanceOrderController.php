<?php
/**
 * AdvanceOrderController
 *
 * Handles pre-orders / advance bookings where customers order food
 * ahead of time for a specific delivery date (e.g., events, parties).
 */

require_once __DIR__ . '/../config/database.php';

class AdvanceOrderController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Create a new advance order with line items.
     *
     * Transaction steps:
     * 1. Insert main order details into advance_orders, get the order ID.
     * 2. Loop through $items_array and insert each into advance_order_items.
     * 3. Commit — or rollback if anything fails.
     *
     * @param  string $delivery_date  The date the order should be ready (Y-m-d).
     * @param  array  $customer_info  ['name' => string, 'phone' => string, 'notes' => string|null]
     * @param  float  $total_bill     Full bill amount for the order.
     * @param  float  $advance_paid   Amount paid upfront.
     * @param  array  $items_array    Array of items: [['item_id' => int, 'qty' => int], ...]
     * @return array                  Result with order_id and summary.
     */
    public function createAdvanceOrder($delivery_date, $customer_info, $total_bill, $advance_paid, $items_array) {
        // ── Input validation ─────────────────────────────────────────
        if (empty($delivery_date)) {
            return ['success' => false, 'message' => 'Delivery date is required.'];
        }
        if (empty($customer_info['name'])) {
            return ['success' => false, 'message' => 'Customer name is required.'];
        }
        if (!is_numeric($total_bill) || $total_bill <= 0) {
            return ['success' => false, 'message' => 'Total bill must be greater than zero.'];
        }
        if (empty($items_array) || !is_array($items_array)) {
            return ['success' => false, 'message' => 'At least one item is required.'];
        }

        try {
            $this->db->beginTransaction();

            // ── Step 1: Insert main order ────────────────────────────
            $stmt_order = $this->db->prepare(
                "INSERT INTO advance_orders 
                 (delivery_date, customer_name, customer_phone, total_bill, advance_paid, notes)
                 VALUES (:delivery_date, :name, :phone, :total_bill, :advance_paid, :notes)"
            );

            $customer_name  = trim($customer_info['name']);
            $customer_phone = trim($customer_info['phone'] ?? '');
            $notes          = $customer_info['notes'] ?? null;
            $advance_paid   = (float) $advance_paid;

            $stmt_order->bindParam(':delivery_date', $delivery_date);
            $stmt_order->bindParam(':name', $customer_name);
            $stmt_order->bindParam(':phone', $customer_phone);
            $stmt_order->bindParam(':total_bill', $total_bill);
            $stmt_order->bindParam(':advance_paid', $advance_paid);
            $stmt_order->bindParam(':notes', $notes);
            $stmt_order->execute();

            $order_id = (int) $this->db->lastInsertId();

            if ($order_id <= 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to create order record.'];
            }

            // ── Step 2: Insert order line items ──────────────────────
            $stmt_item = $this->db->prepare(
                "INSERT INTO advance_order_items (order_id, item_id, qty)
                 VALUES (:order_id, :item_id, :qty)"
            );

            $items_inserted = 0;

            foreach ($items_array as $item) {
                $item_id = (int) ($item['item_id'] ?? 0);
                $qty     = (int) ($item['qty'] ?? 0);

                if ($item_id <= 0 || $qty <= 0) {
                    continue; // skip invalid entries
                }

                $stmt_item->bindParam(':order_id', $order_id, PDO::PARAM_INT);
                $stmt_item->bindParam(':item_id', $item_id, PDO::PARAM_INT);
                $stmt_item->bindParam(':qty', $qty, PDO::PARAM_INT);
                $stmt_item->execute();

                $items_inserted++;
            }

            if ($items_inserted === 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'No valid items were provided.'];
            }

            // ── Step 3: Commit ───────────────────────────────────────
            $this->db->commit();

            return [
                'success'        => true,
                'message'        => 'Advance order #' . $order_id . ' created for ' . $customer_name . '.',
                'order_id'       => $order_id,
                'delivery_date'  => $delivery_date,
                'total_bill'     => $total_bill,
                'advance_paid'   => $advance_paid,
                'remaining_due'  => round($total_bill - $advance_paid, 2),
                'items_count'    => $items_inserted
            ];

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("AdvanceOrderController::createAdvanceOrder Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Transaction failed. No data was saved.'];
        }
    }

    /**
     * Get all pending order items for a specific delivery date.
     *
     * Joins advance_orders → advance_order_items → items to return
     * a prep-ready list of what needs to be cooked, grouped by item.
     *
     * @param  string $date  The delivery date to query (Y-m-d).
     * @return array         List of items with quantities and customer details.
     */
    public function getPendingOrdersForDate($date) {
        if (empty($date)) {
            return [];
        }

        // Detailed view: every line item with its parent order info
        $query = "SELECT 
                      ao.id AS order_id,
                      ao.customer_name,
                      ao.customer_phone,
                      ao.total_bill,
                      ao.advance_paid,
                      ao.remaining_due,
                      ao.notes,
                      aoi.qty,
                      i.id AS item_id,
                      i.item_name,
                      i.selling_price
                  FROM advance_orders ao
                  JOIN advance_order_items aoi ON ao.id = aoi.order_id
                  JOIN items i ON aoi.item_id = i.id
                  WHERE ao.delivery_date = :date
                    AND ao.status = 'Pending'
                  ORDER BY ao.id ASC, i.item_name ASC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $details = $stmt->fetchAll();

        // Aggregated summary: total qty per item across all orders
        $query_summary = "SELECT 
                              i.id AS item_id,
                              i.item_name,
                              SUM(aoi.qty) AS total_qty
                          FROM advance_orders ao
                          JOIN advance_order_items aoi ON ao.id = aoi.order_id
                          JOIN items i ON aoi.item_id = i.id
                          WHERE ao.delivery_date = :date
                            AND ao.status = 'Pending'
                          GROUP BY i.id, i.item_name
                          ORDER BY total_qty DESC";

        $stmt_summary = $this->db->prepare($query_summary);
        $stmt_summary->bindParam(':date', $date);
        $stmt_summary->execute();
        $prep_summary = $stmt_summary->fetchAll();

        return [
            'date'         => $date,
            'prep_summary' => $prep_summary,  // "Prepare 40 BBQ Tilapia, 20 Grilled Chicken"
            'order_details' => $details        // Full breakdown per order
        ];
    }

    /**
     * Update an order's status (e.g., Pending → Preparing → Delivered).
     *
     * @param  int    $order_id   The advance_orders.id.
     * @param  string $status     New status: Pending|Preparing|Delivered|Cancelled.
     * @return array
     */
    public function updateOrderStatus($order_id, $status) {
        $valid = ['Pending', 'Preparing', 'Delivered', 'Cancelled'];
        if (!in_array($status, $valid)) {
            return ['success' => false, 'message' => 'Invalid status. Use: ' . implode(', ', $valid)];
        }

        $stmt = $this->db->prepare(
            "UPDATE advance_orders SET status = :status WHERE id = :id"
        );
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $order_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Order not found.'];
        }

        return ['success' => true, 'message' => 'Order #' . $order_id . ' updated to ' . $status . '.'];
    }
}
?>
