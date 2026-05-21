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
            SELECT id, name, name_bn, username, role, is_active 
            FROM users 
            ORDER BY role, name
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
            $stmt = $this->db->prepare("UPDATE users SET is_active = :is_active WHERE id = :id");
            $stmt->execute([':is_active' => $isActive, ':id' => $userId]);
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

    /**
     * Delete an entity by ID (AJAX)
     * Route: ?url=admin/deleteEntity
     */
    public function deleteEntity() {
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'POST required']);
        }

        $data   = json_decode(file_get_contents('php://input'), true);
        $entity = $data['entity'] ?? '';
        $id     = (int)($data['id'] ?? 0);

        $allowed = ['items', 'raw_inventory'];
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
}
