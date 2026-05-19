<?php

require_once __DIR__ . '/../core/Model.php';

class ForecastingModel extends BaseModel {

    /**
     * Phase 4 - Query 1: Gas Depletion Prediction (Moving Average)
     */
    public function getNextGasRefillDate() {
        $sql = "
            SELECT DATE_ADD(MAX(expense_date), INTERVAL (
                SELECT AVG(DATEDIFF(e2.expense_date, e1.expense_date)) 
                FROM expenses e1 JOIN expenses e2 ON e1.id < e2.id 
                WHERE e1.name = 'Gas'
            ) DAY) AS next_gas_refill_date
            FROM expenses WHERE name = 'Gas'
        ";
        
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return $result ? $result['next_gas_refill_date'] : null;
    }
    
    /**
     * Phase 4 - Query 2: Smart Bazaar Suggestions for Tomorrow (Event Impact)
     */
    public function getSmartBazaarSuggestions() {
        $sql = "
            SELECT ds.item_id, i.item_name,
                   (AVG(ds.sold_qty) * IFNULL((
                       SELECT impact_multiplier 
                       FROM calendar_events 
                       WHERE event_date = CURDATE() + INTERVAL 1 DAY
                   ), 1)) AS suggested_prep_qty
            FROM daily_stocks ds
            JOIN items i ON ds.item_id = i.id
            WHERE ds.log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY ds.item_id
        ";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Phase 4 - Query 3: Supplier Due Balance Check
     */
    public function getSupplierDues() {
        $sql = "SELECT name, total_due, contact FROM suppliers WHERE total_due > 0";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
