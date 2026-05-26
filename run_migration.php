<?php
$db = new PDO("mysql:host=localhost;dbname=bondor_bati;charset=utf8", "root", "");
try { 
    $db->exec("ALTER TABLE items ADD COLUMN raw_usage_unit VARCHAR(20) DEFAULT 'kg' AFTER raw_usage"); 
    echo "Migration Success\n"; 
} catch (\Exception $e) { 
    echo "Migration Error: " . $e->getMessage() . "\n"; 
}
