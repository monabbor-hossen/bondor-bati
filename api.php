<?php
/**
 * Bondor Bati POS — AJAX API Handler
 * Handles all form submissions and CRUD operations via fetch().
 */
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/config/Database.php';
$db = Database::getInstance()->getConnection();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action.'];

try {
    switch ($action) {

        // ═══════════════════════════════════════════════════════
        // ITEMS (Menu) CRUD
        // ═══════════════════════════════════════════════════════
        case 'add_item':
            $stmt = $db->prepare("INSERT INTO items (item_name, selling_price, cost_price) VALUES (:n, :sp, :cp)");
            $stmt->execute([
                'n'  => trim($_POST['item_name']),
                'sp' => (float)$_POST['selling_price'],
                'cp' => (float)$_POST['cost_price']
            ]);
            $response = ['success' => true, 'message' => 'Item added.'];
            break;

        case 'update_item':
            $stmt = $db->prepare("UPDATE items SET item_name = :n, selling_price = :sp, cost_price = :cp WHERE id = :id");
            $stmt->execute([
                'n'  => trim($_POST['item_name']),
                'sp' => (float)$_POST['selling_price'],
                'cp' => (float)$_POST['cost_price'],
                'id' => (int)$_POST['id']
            ]);
            $response = ['success' => true, 'message' => 'Item updated.'];
            break;

        case 'delete_item':
            $stmt = $db->prepare("DELETE FROM items WHERE id = :id");
            $stmt->execute(['id' => (int)$_POST['id']]);
            $response = ['success' => true, 'message' => 'Item deleted.'];
            break;

        // ═══════════════════════════════════════════════════════
        // SUPPLIERS CRUD
        // ═══════════════════════════════════════════════════════
        case 'add_supplier':
            $stmt = $db->prepare("INSERT INTO suppliers (name, contact, total_due) VALUES (:n, :c, :d)");
            $stmt->execute([
                'n' => trim($_POST['name']),
                'c' => trim($_POST['contact']),
                'd' => (float)($_POST['total_due'] ?? 0)
            ]);
            $response = ['success' => true, 'message' => 'Supplier added.'];
            break;

        case 'update_supplier':
            $stmt = $db->prepare("UPDATE suppliers SET name = :n, contact = :c, total_due = :d WHERE id = :id");
            $stmt->execute([
                'n'  => trim($_POST['name']),
                'c'  => trim($_POST['contact']),
                'd'  => (float)$_POST['total_due'],
                'id' => (int)$_POST['id']
            ]);
            $response = ['success' => true, 'message' => 'Supplier updated.'];
            break;

        case 'delete_supplier':
            $stmt = $db->prepare("DELETE FROM suppliers WHERE id = :id");
            $stmt->execute(['id' => (int)$_POST['id']]);
            $response = ['success' => true, 'message' => 'Supplier deleted.'];
            break;

        // ═══════════════════════════════════════════════════════
        // BAZAAR LEDGER & ITEMS
        // ═══════════════════════════════════════════════════════
        case 'save_bazaar':
            $logDate = $_POST['log_date'] ?? date('Y-m-d');
            $advanceCash = (float)($_POST['advance_cash'] ?? 0);

            // Upsert ledger
            $stmtCheck = $db->prepare("SELECT id FROM bazaar_ledgers WHERE log_date = :d");
            $stmtCheck->execute(['d' => $logDate]);
            $existing = $stmtCheck->fetch();

            if ($existing) {
                $ledgerId = $existing['id'];
                $db->prepare("UPDATE bazaar_ledgers SET advance_cash = :ac WHERE id = :id")->execute(['ac' => $advanceCash, 'id' => $ledgerId]);
                $db->prepare("DELETE FROM bazaar_items WHERE ledger_id = :lid")->execute(['lid' => $ledgerId]);
            } else {
                $db->prepare("INSERT INTO bazaar_ledgers (log_date, advance_cash, total_spent) VALUES (:d, :ac, 0)")->execute(['d' => $logDate, 'ac' => $advanceCash]);
                $ledgerId = $db->lastInsertId();
            }

            // Insert bazaar items
            $totalSpent = 0;
            $names = $_POST['bi_name'] ?? [];
            $qtys = $_POST['bi_qty'] ?? [];
            $prices = $_POST['bi_price'] ?? [];
            $sids = $_POST['bi_supplier'] ?? [];

            for ($i = 0; $i < count($names); $i++) {
                if (empty(trim($names[$i]))) continue;
                $suppId = !empty($sids[$i]) ? (int)$sids[$i] : null;
                $price = (float)$prices[$i];
                $totalSpent += $price;

                $db->prepare("INSERT INTO bazaar_items (ledger_id, item_name, bought_qty, total_price, supplier_id) VALUES (:lid, :n, :q, :p, :s)")
                   ->execute(['lid' => $ledgerId, 'n' => trim($names[$i]), 'q' => (float)$qtys[$i], 'p' => $price, 's' => $suppId]);

                // Update supplier due if applicable
                if ($suppId) {
                    $db->prepare("UPDATE suppliers SET total_due = total_due + :p WHERE id = :id")->execute(['p' => $price, 'id' => $suppId]);
                }
            }

            $db->prepare("UPDATE bazaar_ledgers SET total_spent = :ts WHERE id = :id")->execute(['ts' => $totalSpent, 'id' => $ledgerId]);
            $response = ['success' => true, 'message' => 'Bazaar ledger saved. Total: ৳' . number_format($totalSpent, 0)];
            break;

        // ═══════════════════════════════════════════════════════
        // DAILY STOCK — Morning Prep
        // ═══════════════════════════════════════════════════════
        case 'save_morning_stock':
            $logDate = $_POST['log_date'] ?? date('Y-m-d');
            $itemIds = $_POST['stock_item_id'] ?? [];
            $cfs = $_POST['carry_forward'] ?? [];
            $wastes = $_POST['wastage'] ?? [];
            $freshes = $_POST['fresh_processed'] ?? [];

            for ($i = 0; $i < count($itemIds); $i++) {
                $itemId = (int)$itemIds[$i];
                $cf = (float)($cfs[$i] ?? 0);
                $w = (float)($wastes[$i] ?? 0);
                $fp = (float)($freshes[$i] ?? 0);
                $opening = ($cf - $w) + $fp;

                $sql = "INSERT INTO daily_stocks (item_id, log_date, carry_forward_qty, wastage_qty, fresh_processed_qty, opening_qty)
                        VALUES (:item, :d, :cf, :w, :fp, :op)
                        ON DUPLICATE KEY UPDATE
                        carry_forward_qty = VALUES(carry_forward_qty),
                        wastage_qty = VALUES(wastage_qty),
                        fresh_processed_qty = VALUES(fresh_processed_qty),
                        opening_qty = VALUES(opening_qty)";
                $db->prepare($sql)->execute([
                    'item' => $itemId, 'd' => $logDate,
                    'cf' => $cf, 'w' => $w, 'fp' => $fp, 'op' => $opening
                ]);
            }
            $response = ['success' => true, 'message' => 'Morning stock saved.'];
            break;

        // ═══════════════════════════════════════════════════════
        // NIGHT CLOSING
        // ═══════════════════════════════════════════════════════
        case 'save_closing':
            $logDate = $_POST['log_date'] ?? date('Y-m-d');
            $itemIds = $_POST['close_item_id'] ?? [];
            $closings = $_POST['closing_qty'] ?? [];
            $comps = $_POST['complimentary_qty'] ?? [];

            for ($i = 0; $i < count($itemIds); $i++) {
                $itemId = (int)$itemIds[$i];
                $closeQty = (float)($closings[$i] ?? 0);
                $compQty = (float)($comps[$i] ?? 0);

                // Fetch opening qty & selling price
                $stmtFetch = $db->prepare("SELECT ds.opening_qty, i.selling_price FROM daily_stocks ds JOIN items i ON ds.item_id = i.id WHERE ds.item_id = :item AND ds.log_date = :d");
                $stmtFetch->execute(['item' => $itemId, 'd' => $logDate]);
                $row = $stmtFetch->fetch();

                if ($row) {
                    $soldQty = $row['opening_qty'] - $closeQty - $compQty;
                    if ($soldQty < 0) $soldQty = 0;
                    $totalSales = $soldQty * $row['selling_price'];

                    $db->prepare("UPDATE daily_stocks SET closing_qty = :cq, complimentary_qty = :comp, sold_qty = :sq, total_sales_amount = :tsa WHERE item_id = :item AND log_date = :d")
                       ->execute(['cq' => $closeQty, 'comp' => $compQty, 'sq' => $soldQty, 'tsa' => $totalSales, 'item' => $itemId, 'd' => $logDate]);
                }
            }
            $response = ['success' => true, 'message' => 'Night closing saved. Profit/loss calculated.'];
            break;

        // ═══════════════════════════════════════════════════════
        // CUSTOMER DUES
        // ═══════════════════════════════════════════════════════
        case 'add_due':
            $db->prepare("INSERT INTO customer_dues (customer_name, phone, due_amount, log_date) VALUES (:n, :p, :a, :d)")
               ->execute([
                   'n' => trim($_POST['customer_name']),
                   'p' => trim($_POST['phone'] ?? ''),
                   'a' => (float)$_POST['due_amount'],
                   'd' => $_POST['log_date'] ?? date('Y-m-d')
               ]);
            $response = ['success' => true, 'message' => 'Customer due logged.'];
            break;

        case 'mark_due_paid':
            $db->prepare("UPDATE customer_dues SET status = 'Paid' WHERE id = :id")->execute(['id' => (int)$_POST['id']]);
            $response = ['success' => true, 'message' => 'Due marked as paid.'];
            break;

        case 'delete_due':
            $db->prepare("DELETE FROM customer_dues WHERE id = :id")->execute(['id' => (int)$_POST['id']]);
            $response = ['success' => true, 'message' => 'Due deleted.'];
            break;

        // ═══════════════════════════════════════════════════════
        // EXPENSES
        // ═══════════════════════════════════════════════════════
        case 'add_expense':
            $totalAmt = (float)$_POST['total_amount'];
            $isSpread = (int)($_POST['is_spread'] ?? 0);
            $dailyAmt = $isSpread ? (float)$_POST['daily_amount'] : $totalAmt;
            $remaining = $isSpread ? $totalAmt : 0;

            $db->prepare("INSERT INTO expenses (category, name, total_amount, is_spread, daily_amount, remaining_balance, expense_date) VALUES (:cat, :n, :ta, :is, :da, :rb, :ed)")
               ->execute([
                   'cat' => $_POST['category'],
                   'n'   => trim($_POST['name']),
                   'ta'  => $totalAmt,
                   'is'  => $isSpread,
                   'da'  => $dailyAmt,
                   'rb'  => $remaining,
                   'ed'  => $_POST['expense_date'] ?? date('Y-m-d')
               ]);
            $response = ['success' => true, 'message' => 'Expense recorded.'];
            break;

        case 'delete_expense':
            $db->prepare("DELETE FROM expenses WHERE id = :id")->execute(['id' => (int)$_POST['id']]);
            $response = ['success' => true, 'message' => 'Expense deleted.'];
            break;

        // ═══════════════════════════════════════════════════════
        // ADVANCE ORDERS
        // ═══════════════════════════════════════════════════════
        case 'add_order':
            $db->prepare("INSERT INTO advance_orders (delivery_date, customer_info, total_bill, advance_paid) VALUES (:dd, :ci, :tb, :ap)")
               ->execute([
                   'dd' => $_POST['delivery_date'],
                   'ci' => trim($_POST['customer_info']),
                   'tb' => (float)$_POST['total_bill'],
                   'ap' => (float)$_POST['advance_paid']
               ]);
            $orderId = $db->lastInsertId();

            // Order items
            $oiItems = $_POST['oi_item'] ?? [];
            $oiQtys = $_POST['oi_qty'] ?? [];
            for ($i = 0; $i < count($oiItems); $i++) {
                if (empty($oiItems[$i])) continue;
                $db->prepare("INSERT INTO advance_order_items (order_id, item_id, qty) VALUES (:oid, :iid, :q)")
                   ->execute(['oid' => $orderId, 'iid' => (int)$oiItems[$i], 'q' => (int)$oiQtys[$i]]);
            }
            $response = ['success' => true, 'message' => 'Advance order placed.'];
            break;

        case 'update_order_status':
            $db->prepare("UPDATE advance_orders SET status = :s WHERE id = :id")->execute(['s' => $_POST['status'], 'id' => (int)$_POST['id']]);
            $response = ['success' => true, 'message' => 'Order status updated.'];
            break;

        // ═══════════════════════════════════════════════════════
        // CALENDAR EVENTS
        // ═══════════════════════════════════════════════════════
        case 'add_event':
            $db->prepare("INSERT INTO calendar_events (event_date, event_name, impact_multiplier) VALUES (:ed, :en, :im) ON DUPLICATE KEY UPDATE event_name = VALUES(event_name), impact_multiplier = VALUES(impact_multiplier)")
               ->execute([
                   'ed' => $_POST['event_date'],
                   'en' => trim($_POST['event_name']),
                   'im' => (float)$_POST['impact_multiplier']
               ]);
            $response = ['success' => true, 'message' => 'Calendar event saved.'];
            break;

        case 'delete_event':
            $db->prepare("DELETE FROM calendar_events WHERE id = :id")->execute(['id' => (int)$_POST['id']]);
            $response = ['success' => true, 'message' => 'Event deleted.'];
            break;

        // ═══════════════════════════════════════════════════════
        // STAFF & ATTENDANCE
        // ═══════════════════════════════════════════════════════
        case 'add_staff':
            $hashedPass = password_hash($_POST['password'] ?? 'staff123', PASSWORD_DEFAULT);
            $accessToken = bin2hex(random_bytes(32));
            $db->prepare("INSERT INTO users (name, username, password, access_token, role) VALUES (:n, :u, :p, :t, :r)")
               ->execute([
                   'n' => trim($_POST['name']),
                   'u' => trim($_POST['username'] ?? ''),
                   'p' => $hashedPass,
                   't' => $accessToken,
                   'r' => $_POST['role'] ?? 'STAFF'
               ]);
            $userId = $db->lastInsertId();

            if (!empty($_POST['monthly_salary'])) {
                $db->prepare("INSERT INTO staff_salaries (user_id, monthly_salary, start_date) VALUES (:uid, :ms, :sd)")
                   ->execute([
                       'uid' => $userId,
                       'ms'  => (float)$_POST['monthly_salary'],
                       'sd'  => $_POST['start_date'] ?? date('Y-m-d')
                   ]);
            }
            $response = ['success' => true, 'message' => 'Staff added! Access Key: ' . $accessToken];
            break;

        case 'toggle_staff':
            $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = :id")->execute(['id' => (int)$_POST['id']]);
            $response = ['success' => true, 'message' => 'Staff status toggled.'];
            break;

        case 'log_absence':
            $db->prepare("INSERT INTO attendance_logs (user_id, absent_date, deduct_salary) VALUES (:uid, :d, :ds)")
               ->execute([
                   'uid' => (int)$_POST['user_id'],
                   'd'   => $_POST['absent_date'] ?? date('Y-m-d'),
                   'ds'  => (int)($_POST['deduct_salary'] ?? 1)
               ]);
            $response = ['success' => true, 'message' => 'Absence logged.'];
            break;

        // ═══════════════════════════════════════════════════════
        // RAW INVENTORY
        // ═══════════════════════════════════════════════════════
        case 'update_raw_inventory':
            $db->prepare("INSERT INTO raw_inventory (item_name, current_qty, avg_unit_price) VALUES (:n, :q, :p)
                          ON DUPLICATE KEY UPDATE current_qty = :q2, avg_unit_price = :p2")
               ->execute([
                   'n' => trim($_POST['item_name']),
                   'q' => (float)$_POST['current_qty'], 'q2' => (float)$_POST['current_qty'],
                   'p' => (float)$_POST['avg_unit_price'], 'p2' => (float)$_POST['avg_unit_price']
               ]);
            $response = ['success' => true, 'message' => 'Raw inventory updated.'];
            break;

        // ═══════════════════════════════════════════════════════
        // STAFF PERMISSIONS
        // ═══════════════════════════════════════════════════════
        case 'save_permissions':
            $userId = (int)$_POST['perm_user_id'];
            $pages = $_POST['pages'] ?? [];

            // Clear existing permissions for this user
            $db->prepare("DELETE FROM staff_permissions WHERE user_id = :uid")->execute(['uid' => $userId]);

            // Insert new permissions
            $stmtIns = $db->prepare("INSERT INTO staff_permissions (user_id, page_slug) VALUES (:uid, :slug)");
            foreach ($pages as $slug) {
                $stmtIns->execute(['uid' => $userId, 'slug' => trim($slug)]);
            }

            $response = ['success' => true, 'message' => 'Permissions updated for staff. Changes take effect on their next login.'];
            break;
    }

} catch (PDOException $e) {
    $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
}

echo json_encode($response);
