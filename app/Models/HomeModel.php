<?php
namespace App\Models;

use Config\Database;

/**
 * Home Model
 * Handles data logic for the Home Controller.
 */
class HomeModel {
    private $db;

    public function __construct() {
        // To use the database, uncomment the following lines:
        // $database = new Database();
        // $this->db = $database->getConnection();
    }

    /**
     * Return some dummy data for demonstration purposes
     * 
     * @return array
     */
    public function getDummyData() {
        return [
            ['id' => 1, 'name' => 'Model-View-Controller Architecture'],
            ['id' => 2, 'name' => 'Built-in Mobile Responsiveness'],
            ['id' => 3, 'name' => 'PDO Database Connectivity ready']
        ];
    }
}
