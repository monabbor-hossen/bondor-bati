<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

/**
 * Home Controller
 * Renders the main dashboard with live stats pulled from the database.
 */
class HomeController extends Controller {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Dashboard index — pulls today's live stats
     */
    public function index() {
        $today = date('Y-m-d');

        // Count advance orders due for delivery today
        $stmtOrders = $this->db->prepare("SELECT COUNT(*) FROM advance_orders WHERE delivery_date = :today AND status = 'pending'");
        $stmtOrders->execute([':today' => $today]);
        $pendingOrders = (int) $stmtOrders->fetchColumn();

        // Count total active sellable items
        $stmtItems = $this->db->query("SELECT COUNT(*) FROM items");
        $totalItemsActive = (int) $stmtItems->fetchColumn();

        $this->view('home', [
            'pageTitle'        => 'Dashboard — Bondor Bati',
            'activeNav'        => 'home',
            'pendingOrders'    => $pendingOrders,
            'totalItemsActive' => $totalItemsActive,
        ]);
    }
}
