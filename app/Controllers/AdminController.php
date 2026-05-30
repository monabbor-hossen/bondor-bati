<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

class AdminController extends Controller {
public function __construct() {
        parent::__construct();
        $this->db = (new Database())->getConnection();
    }

    public function settings() {
        $this->requireAdmin();

        $items = $this->db->query("
            SELECT i.*, r.avg_unit_price as raw_price, r.unit as raw_unit
            FROM items i 
            LEFT JOIN raw_inventory r ON i.linked_raw_item = r.item_name 
            ORDER BY i.sort_order, i.item_name
        ")->fetchAll();
        $rawInventory = $this->db->query("SELECT * FROM raw_inventory ORDER BY item_name")->fetchAll();
        
        $users = $this->db->query("
            SELECT u.*, s.monthly_salary, s.daily_rate 
            FROM users u 
            LEFT JOIN staff_salaries s ON u.id = s.user_id 
            ORDER BY u.role, u.name
        ")->fetchAll();

        // Fetch Online Addons
        $addonsJson = $this->db->query("SELECT setting_value FROM app_settings WHERE setting_key = 'online_addons'")->fetchColumn();
        if (!$addonsJson) {
            $addonsJson = '[{"name":"+ Mayonnaise","price":20},{"name":"+ Sauce","price":15},{"name":"+ Box","price":10}]';
            $this->db->prepare("INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES ('online_addons', ?)")->execute([$addonsJson]);
        }
        $onlineAddons = json_decode($addonsJson, true) ?: [];

        $this->view('admin/settings', [
            'pageTitle'    => 'Configuration & Permissions',
            'activeNav'    => 'admin_settings',
            'items'        => $items,
            'rawInventory' => $rawInventory,
            'users'        => $users,
            'onlineAddons' => $onlineAddons
        ]);
    }

    public function users() {
        $this->requireAdmin();

        $users = $this->db->query("
            SELECT u.id, u.name, u.name_bn, u.username, u.role, u.is_active, 
                   s.monthly_salary, s.daily_rate,
                   (SELECT COALESCE(SUM(deduct_salary), 0) FROM attendance_logs 
                    WHERE user_id = u.id 
                      AND MONTH(absent_date) = MONTH(CURRENT_DATE()) 
                      AND YEAR(absent_date) = YEAR(CURRENT_DATE())
                   ) as month_deductions
            FROM users u 
            LEFT JOIN staff_salaries s ON u.id = s.user_id 
            ORDER BY u.role, u.name
        ")->fetchAll();

        $this->view('admin/users', [
            'pageTitle' => __('staff_management'),
            'activeNav' => 'settings',
            'users'     => $users
        ]);
    }

    public function saveEntity() {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required']);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $entity = $data['entity'] ?? '';
        $id = (int)($data['id'] ?? 0);
        $fields = $data['fields'] ?? [];
        
        $allowed = ['items', 'raw_inventory', 'users', 'fixed_daily_costs', 'spread_costs'];
        if (!in_array($entity, $allowed) || empty($fields)) {
            $this->json(['success' => false, 'error' => 'Invalid entity or payload']);
        }
        
        try {
            if ($entity === 'items') {
                try {
                    $this->db->exec("ALTER TABLE items ADD COLUMN linked_raw_item VARCHAR(100) NULL AFTER item_name");
                } catch (\Exception $e) {} // Ignore if column exists
                try {
                    $this->db->exec("ALTER TABLE items ADD COLUMN additional_cost DECIMAL(10,2) DEFAULT 0 AFTER cost_price");
                } catch (\Exception $e) {} // Ignore if column exists
                try {
                    $this->db->exec("ALTER TABLE items ADD COLUMN online_price DECIMAL(10,2) DEFAULT 0 AFTER selling_price");
                } catch (\Exception $e) {} // Ignore if column exists
            }

            if ($id > 0) {
                // Update
                $setSql = [];
                $params = ['id' => $id];
                foreach ($fields as $key => $val) {
                    $setSql[] = "`$key` = :$key";
                    $params[$key] = $val;
                }
                $sql = "UPDATE `$entity` SET " . implode(', ', $setSql) . " WHERE id = :id";
                $this->db->prepare($sql)->execute($params);
            } else {
                // Insert
                $cols = array_keys($fields);
                $placeholders = array_map(fn($c) => ":$c", $cols);
                $params = [];
                foreach ($fields as $key => $val) {
                    $params[$key] = $val;
                }
                $sql = "INSERT INTO `$entity` (`" . implode('`, `', $cols) . "`) VALUES (" . implode(', ', $placeholders) . ")";
                $this->db->prepare($sql)->execute($params);
                $id = $this->db->lastInsertId();
            }
            
            // Auto-sync linked menu items if raw inventory price changes
            if ($entity === 'raw_inventory' && $id > 0) {
                try {
                    $updatedRaw = $this->db->query("SELECT item_name, avg_unit_price, unit FROM raw_inventory WHERE id = " . (int)$id)->fetch();
                    if ($updatedRaw) {
                        $linkedItems = $this->db->query("SELECT id, raw_usage, raw_usage_unit, additional_cost FROM items WHERE linked_raw_item = " . $this->db->quote($updatedRaw['item_name']))->fetchAll();
                        foreach ($linkedItems as $li) {
                            $rUsage = (float)$li['raw_usage'];
                            $rUnit = strtolower($li['raw_usage_unit'] ?: 'kg');
                            $rawUnit = strtolower($updatedRaw['unit']);
                            
                            $norm = $rUsage;
                            if ($rawUnit === 'kg' && ($rUnit === 'g' || $rUnit === 'gm')) $norm = $rUsage / 1000.0;
                            if ($rawUnit === 'l' && $rUnit === 'ml') $norm = $rUsage / 1000.0;
                            
                            $newCost = (float)$li['additional_cost'] + ($updatedRaw['avg_unit_price'] * $norm);
                            $this->db->prepare("UPDATE items SET cost_price = :cost WHERE id = :id")->execute([':cost' => $newCost, ':id' => $li['id']]);
                        }
                    }
                } catch (\Exception $e) {}
            }

            $this->json(['success' => true, 'id' => $id]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function toggleUserStatus() {
        $this->requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = (int)($data['user_id'] ?? 0);
        $isActive = (int)($data['is_active'] ?? 0);
        
        if ($userId) {
            // 1. Toggle App Access
            $stmt = $this->db->prepare("UPDATE users SET is_active = :is_active WHERE id = :id");
            $stmt->execute([':is_active' => $isActive, ':id' => $userId]);
            
            // 2. Toggle Payroll Clock
            if ($isActive === 0) {
                // Staff left or was fired -> Stop their pay as of today
                $sal = $this->db->prepare("UPDATE staff_salaries SET end_date = CURDATE() WHERE user_id = :id AND end_date IS NULL");
                $sal->execute([':id' => $userId]);
            } else {
                // Staff came back -> Resume their pay
                $sal = $this->db->prepare("UPDATE staff_salaries SET end_date = NULL WHERE user_id = :id ORDER BY id DESC LIMIT 1");
                $sal->execute([':id' => $userId]);
            }

            $this->json(['success' => true]);
        }
        $this->json(['success' => false, 'error' => 'Invalid user']);
    }

    public function deleteUser() {
        $this->requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = (int)($data['user_id'] ?? 0);
        
        if ($userId) {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $this->json(['success' => true]);
        }
        $this->json(['success' => false, 'error' => 'Invalid user']);
    }

    public function deleteEntity() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required']);
        }

        $data   = json_decode(file_get_contents('php://input'), true);
        $entity = $data['entity'] ?? '';
        $id     = (int)($data['id'] ?? 0);

        $allowed = ['items', 'raw_inventory', 'expenses', 'fixed_daily_costs', 'spread_costs'];
        if (!in_array($entity, $allowed) || $id <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid entity or ID']);
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM `$entity` WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function saveStaff() {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required']);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // STRICT INTEGER CASTING
        $userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
        $name = trim($data['name'] ?? '');
        $nameBn = trim($data['name_bn'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $role = trim($data['role'] ?? 'staff');
        $monthlySalary = (float)($data['monthly_salary'] ?? 0);
        $dailyRate = round($monthlySalary / 30, 2);
        
        if (empty($name) || empty($phone)) {
            $this->json(['success' => false, 'error' => 'Name and phone are required.']);
        }
        
        try {
            $this->db->beginTransaction();
            
            if ($userId > 0) {
                // UPDATE EXISTING USER
                $stmt = $this->db->prepare("UPDATE users SET name = :name, name_bn = :name_bn, username = :username, role = :role WHERE id = :id");
                $stmt->execute([':name' => $name, ':name_bn' => $nameBn, ':username' => $phone, ':role' => $role, ':id' => $userId]);
                
                $salStmt = $this->db->prepare("
                    INSERT INTO staff_salaries (user_id, monthly_salary, daily_rate, start_date) 
                    VALUES (:uid, :ms, :dr, CURDATE())
                    ON DUPLICATE KEY UPDATE 
                    monthly_salary = VALUES(monthly_salary),
                    daily_rate = VALUES(daily_rate)
                ");
                $salStmt->execute([
                    ':uid' => $userId,
                    ':ms' => $monthlySalary,
                    ':dr' => $dailyRate
                ]);
            } else {
                // INSERT BRAND NEW USER
                $chk = $this->db->prepare("SELECT id FROM users WHERE username = :phone");
                $chk->execute([':phone' => $phone]);
                if ($chk->fetch()) {
                    $this->db->rollBack();
                    $this->json(['success' => false, 'error' => 'Phone number already exists.']);
                }

                $stmt = $this->db->prepare("INSERT INTO users (name, name_bn, username, password, role, is_active) VALUES (:name, :name_bn, :username, :password, :role, 1)");
                $stmt->execute([
                    ':name'     => $name,
                    ':name_bn'  => $nameBn,
                    ':username' => $phone,
                    ':password' => password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT), // random secure password — staff use magic links, not passwords
                    ':role'     => $role
                ]);
                $userId = $this->db->lastInsertId();
                
                $salStmt = $this->db->prepare("
                    INSERT INTO staff_salaries (user_id, monthly_salary, daily_rate, start_date) 
                    VALUES (:uid, :ms, :dr, CURDATE())
                ");
                $salStmt->execute([
                    ':uid' => $userId,
                    ':ms' => $monthlySalary,
                    ':dr' => $dailyRate
                ]);
            }
            
            $this->db->commit();
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function logAbsence() {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required']);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $userId = (int)($data['user_id'] ?? 0);
        
        $startDateStr = $data['absent_date'] ?? date('Y-m-d');
        $endDateStr   = !empty($data['end_date']) ? $data['end_date'] : $startDateStr;
        
        $isDeducted = !empty($data['is_deducted']);
        $note = trim($data['note'] ?? '');

        if ($userId <= 0 || empty($startDateStr)) {
            $this->json(['success' => false, 'error' => 'Invalid data']);
        }

        try {
            $sal = $this->db->prepare("SELECT daily_rate FROM staff_salaries WHERE user_id = :uid ORDER BY start_date DESC LIMIT 1");
            $sal->execute([':uid' => $userId]);
            $dailyRate = (float)$sal->fetchColumn();

            $deduction = $isDeducted ? $dailyRate : 0.00;
            
            $begin = new \DateTime($startDateStr);
            $end   = new \DateTime($endDateStr);
            $end->modify('+1 day'); // Make inclusive for DatePeriod
            
            $period = new \DatePeriod($begin, new \DateInterval('P1D'), $end);

            $stmt = $this->db->prepare("
                INSERT INTO attendance_logs (user_id, absent_date, deduct_salary, note)
                VALUES (:uid, :d, :deduct, :note)
                ON DUPLICATE KEY UPDATE 
                deduct_salary = VALUES(deduct_salary),
                note = VALUES(note)
            ");
            
            foreach ($period as $dt) {
                $stmt->execute([
                    ':uid'    => $userId,
                    ':d'      => $dt->format('Y-m-d'),
                    ':deduct' => $deduction,
                    ':note'   => $note
                ]);
            }

            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function saveOnlineAddons() {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required']);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $addons = $data['addons'] ?? [];
        
        $cleanAddons = [];
        foreach ($addons as $a) {
            $name = trim($a['name'] ?? '');
            $price = (float)($a['price'] ?? 0);
            $rawItem = trim($a['raw_item'] ?? '');
            $gram = (float)($a['gram'] ?? 0);
            if (!empty($name) && $price >= 0) {
                $cleanAddons[] = ['name' => $name, 'price' => $price, 'raw_item' => $rawItem, 'gram' => $gram];
            }
        }

        $json = json_encode($cleanAddons, JSON_UNESCAPED_UNICODE);
        $this->db->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('online_addons', :val) ON DUPLICATE KEY UPDATE setting_value = :val")
                 ->execute([':val' => $json]);
                 
        $this->json(['success' => true]);
    }
}
