<?php

require_once __DIR__ . '/../config/Database.php';

class BaseModel {
    protected $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
}
