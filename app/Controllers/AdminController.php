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
        
        $fixedCosts = $this->db->query("SELECT * FROM fixed_daily_costs ORDER BY name")->fetchAll();

        $this->view('admin/settings', [
            'pageTitle'    => 'Settings',
            'activeNav'    => 'settings',
            'items'        => $items,
            'rawInventory' => $rawInventory,
            'users'        => $users,
            'fixedCosts'   => $fixedCosts
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
        
        $allowed = ['items', 'raw_inventory', 'users', 'fixed_daily_costs'];
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
}
