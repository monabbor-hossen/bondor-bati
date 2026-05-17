<?php

require_once __DIR__ . '/../config/database.php';

class POSController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Close daily stock for a specific item.
     * 
     * Steps:
     * 1. Fetch the auto-calculated opening_qty for today from daily_stocks.
     * 2. Calculate sold_qty = opening_qty - closing_qty.
     * 3. Fetch the selling_price from the items table.
     * 4. Compute total_sales_amount = sold_qty * selling_price.
     * 5. Update the daily_stocks record with closing_qty and total_sales_amount.
     *
     * @param int $item_id     The ID of the item being closed.
     * @param int $closing_qty The physical count remaining at end of day.
     * @param int $wastage_qty Spoiled items count.
     * @return array           Result with success status and details.
     */
    public function closeDailyStock($item_id, $closing_qty, $wastage_qty = 0) {
        $today = date('Y-m-d');

        try {
            $this->db->beginTransaction();

            // ── Step 1: Fetch today's stock data ──
            $query_stock = "SELECT id, carry_forward_qty, fresh_processed_qty, complimentary_qty 
                            FROM daily_stocks 
                            WHERE item_id = :item_id AND log_date = :today 
                            LIMIT 1";
            $stmt_stock = $this->db->prepare($query_stock);
            $stmt_stock->bindParam(':item_id', $item_id, PDO::PARAM_INT);
            $stmt_stock->bindParam(':today', $today);
            $stmt_stock->execute();
            $stock_row = $stmt_stock->fetch();

            if (!$stock_row) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'No daily stock entry found for this item today. Open stock first.'
                ];
            }

            $stock_id            = $stock_row['id'];
            $carry_forward       = (int) $stock_row['carry_forward_qty'];
            $fresh_processed     = (int) $stock_row['fresh_processed_qty'];
            $complimentary_qty   = (int) $stock_row['complimentary_qty'];

            // ── Step 2: Calculate new opening_qty and sold_qty in PHP ────────
            // Match DB generated columns:
            // opening_qty = (carry_forward_qty - wastage_qty) + fresh_processed_qty
            // sold_qty    = opening_qty - closing_qty - complimentary_qty
            $opening_qty = ($carry_forward - $wastage_qty) + $fresh_processed;
            $sold_qty    = $opening_qty - $closing_qty - $complimentary_qty;

            // Guard against negative sold_qty (data entry error)
            if ($sold_qty < 0) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Closing qty + complimentary + wastage exceeds total available stock. Please recheck.'
                ];
            }

            // ── Step 3: Fetch selling_price from items table ─────────────────
            $query_item = "SELECT selling_price FROM items WHERE id = :item_id LIMIT 1";
            $stmt_item = $this->db->prepare($query_item);
            $stmt_item->bindParam(':item_id', $item_id, PDO::PARAM_INT);
            $stmt_item->execute();
            $item_row = $stmt_item->fetch();

            if (!$item_row) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Item not found in the items table.'
                ];
            }

            $selling_price = (float) $item_row['selling_price'];

            // ── Step 4: Compute total_sales_amount ───────────────────────────
            $total_sales_amount = round($sold_qty * $selling_price, 2);

            // ── Step 5: Update daily_stocks with closing_qty, wastage_qty & sales amount ──
            $query_update = "UPDATE daily_stocks 
                             SET closing_qty = :closing_qty, 
                                 wastage_qty = :wastage_qty,
                                 total_sales_amount = :total_sales_amount 
                             WHERE id = :id";
            $stmt_update = $this->db->prepare($query_update);
            $stmt_update->bindParam(':closing_qty', $closing_qty, PDO::PARAM_INT);
            $stmt_update->bindParam(':wastage_qty', $wastage_qty, PDO::PARAM_INT);
            $stmt_update->bindParam(':total_sales_amount', $total_sales_amount);
            $stmt_update->bindParam(':id', $stock_id, PDO::PARAM_INT);
            $stmt_update->execute();

            $this->db->commit();

            return [
                'success'            => true,
                'message'            => 'Daily stock closed successfully.',
                'opening_qty'        => $opening_qty,
                'closing_qty'        => $closing_qty,
                'wastage_qty'        => $wastage_qty,
                'complimentary_qty'  => $complimentary_qty,
                'sold_qty'           => $sold_qty,
                'selling_price'      => $selling_price,
                'total_sales_amount' => $total_sales_amount
            ];

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("POSController::closeDailyStock Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error while closing stock.'
            ];
        }
    }

    /**
     * Record a customer due (credit sale).
     *
     * Logic: The food IS sold and leaves the shop, so sold_qty should reflect it.
     * However, no cash enters the drawer. We log the revenue in customer_dues
     * (status='Unpaid') so the ReportController's net profit formula picks it up
     * via the "Total Due Sales" line, keeping the cash drawer balanced.
     *
     * Transaction steps:
     * 1. Validate that today's daily_stocks entry exists for the item.
     * 2. Fetch the selling_price to compute the due amount (or use provided amount).
     * 3. Insert into customer_dues with status='Unpaid'.
     *
     * @param  string $customer_name  Name of the customer.
     * @param  string $phone          Customer phone number.
     * @param  float  $due_amount     Amount owed (selling_price * qty if 0).
     * @param  int    $item_id        The item sold on credit.
     * @param  int    $qty            Number of units sold on credit.
     * @return array
     */
    public function recordCustomerDue($customer_name, $phone, $due_amount, $item_id, $qty) {
        $today = date('Y-m-d');

        // Input validation
        if (empty(trim($customer_name))) {
            return ['success' => false, 'message' => 'Customer name is required.'];
        }
        if (!is_numeric($item_id) || $item_id <= 0) {
            return ['success' => false, 'message' => 'Invalid item ID.'];
        }
        if (!is_numeric($qty) || $qty <= 0) {
            return ['success' => false, 'message' => 'Quantity must be greater than zero.'];
        }

        try {
            $this->db->beginTransaction();

            // ── Step 1: Verify daily stock entry exists for today ─────
            $stmt_stock = $this->db->prepare(
                "SELECT id, opening_qty, closing_qty, complimentary_qty 
                 FROM daily_stocks 
                 WHERE item_id = :item_id AND log_date = :today 
                 LIMIT 1 FOR UPDATE"
            );
            $stmt_stock->bindParam(':item_id', $item_id, PDO::PARAM_INT);
            $stmt_stock->bindParam(':today', $today);
            $stmt_stock->execute();
            $stock = $stmt_stock->fetch();

            if (!$stock) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'No daily stock entry found for this item today.'];
            }

            // ── Step 2: Fetch selling_price & compute due if not given ─
            $stmt_item = $this->db->prepare(
                "SELECT selling_price FROM items WHERE id = :item_id LIMIT 1"
            );
            $stmt_item->bindParam(':item_id', $item_id, PDO::PARAM_INT);
            $stmt_item->execute();
            $item = $stmt_item->fetch();

            if (!$item) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Item not found.'];
            }

            $selling_price = (float) $item['selling_price'];

            // If due_amount wasn't explicitly provided, calculate it
            if (empty($due_amount) || $due_amount <= 0) {
                $due_amount = round($selling_price * $qty, 2);
            }

            // ── Step 3: Insert into customer_dues ────────────────────
            $stmt_due = $this->db->prepare(
                "INSERT INTO customer_dues (customer_name, phone, due_amount, log_date, status) 
                 VALUES (:name, :phone, :amount, :log_date, 'Unpaid')"
            );
            $stmt_due->bindParam(':name', $customer_name);
            $stmt_due->bindParam(':phone', $phone);
            $stmt_due->bindParam(':amount', $due_amount);
            $stmt_due->bindParam(':log_date', $today);
            $stmt_due->execute();

            $due_id = (int) $this->db->lastInsertId();

            $this->db->commit();

            return [
                'success'       => true,
                'message'       => 'Due of ৳' . number_format($due_amount, 2) . ' recorded for ' . $customer_name . '.',
                'due_id'        => $due_id,
                'customer_name' => $customer_name,
                'due_amount'    => $due_amount,
                'item_id'       => $item_id,
                'qty'           => $qty
            ];

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("POSController::recordCustomerDue Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error while recording due.'];
        }
    }

    /**
     * Log complimentary (free) food given away.
     *
     * Updates daily_stocks.complimentary_qty for today. This quantity is
     * automatically excluded from sold_qty via the SQL generated column:
     *   sold_qty = opening_qty - closing_qty - complimentary_qty
     *
     * So the cash drawer won't show a shortage — the system knows these
     * items left the shop but were not sold.
     *
     * Also records the cost (cost_price * qty) as a promotional expense
     * so it appears in the daily profit breakdown.
     *
     * @param  int   $item_id  The item given away.
     * @param  int   $qty      Number of units given free.
     * @return array
     */
    public function logComplimentaryFood($item_id, $qty) {
        $today = date('Y-m-d');

        // Input validation
        if (!is_numeric($item_id) || $item_id <= 0) {
            return ['success' => false, 'message' => 'Invalid item ID.'];
        }
        if (!is_numeric($qty) || $qty <= 0) {
            return ['success' => false, 'message' => 'Quantity must be greater than zero.'];
        }

        try {
            $this->db->beginTransaction();

            // 1. Increment complimentary_qty in today's daily stock
            $stmt = $this->db->prepare(
                "UPDATE daily_stocks 
                 SET complimentary_qty = complimentary_qty + :qty 
                 WHERE item_id = :item_id AND log_date = :today"
            );
            $stmt->bindParam(':qty', $qty, PDO::PARAM_INT);
            $stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
            $stmt->bindParam(':today', $today);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'No stock entry found for this item today.'];
            }

            // 2. Fetch cost_price to calculate promotional loss
            $stmt_item = $this->db->prepare(
                "SELECT item_name, cost_price FROM items WHERE id = :item_id LIMIT 1"
            );
            $stmt_item->bindParam(':item_id', $item_id, PDO::PARAM_INT);
            $stmt_item->execute();
            $item = $stmt_item->fetch();

            if (!$item) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Item not found in items table.'];
            }

            // 3. Record the cost as a promotional expense
            $loss = round($item['cost_price'] * $qty, 2);
            $loss_name = 'Complimentary: ' . $item['item_name'] . ' x' . $qty;

            $stmt_expense = $this->db->prepare(
                "INSERT INTO expenses (category, name, total_amount, expense_date) 
                 VALUES ('Fixed', :name, :amount, :date)"
            );
            $stmt_expense->bindParam(':name', $loss_name);
            $stmt_expense->bindParam(':amount', $loss);
            $stmt_expense->bindParam(':date', $today);
            $stmt_expense->execute();

            $this->db->commit();

            return [
                'success'          => true,
                'message'          => $qty . ' complimentary ' . $item['item_name'] . ' logged (৳' . number_format($loss, 2) . ' cost).',
                'item_name'        => $item['item_name'],
                'qty'              => $qty,
                'promotional_loss' => $loss
            ];

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("POSController::logComplimentaryFood Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error while logging complimentary food.'];
        }
    }
}
?>
