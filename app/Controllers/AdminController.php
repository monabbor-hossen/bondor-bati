<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

class AdminController extends Controller {
public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function settings() {
        $this->requireAdmin();

        $items = $this->db->query("SELECT * FROM items ORDER BY sort_order, item_name")->fetchAll();
        $rawInventory = $this->db->query("SELECT * FROM raw_inventory ORDER BY item_name")->fetchAll();
        
        $users = $this->db->query("
            SELECT u.*, s.monthly_salary, s.daily_rate 
            FROM users u 
            LEFT JOIN staff_salaries s ON u.id = s.user_id 
            ORDER BY u.role, u.name
        ")->fetchAll();

        $this->view('admin/settings', [
            'pageTitle'    => 'Settings',
            'activeNav'    => 'settings',
            'items'        => $items,
            'rawInventory' => $rawInventory,
            'users'        => $users
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
                    ':name' => $name,
                    ':name_bn' => $nameBn,
                    ':username' => $phone,
                    ':password' => password_hash('123456', PASSWORD_DEFAULT),
                    ':role' => $role
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
}
