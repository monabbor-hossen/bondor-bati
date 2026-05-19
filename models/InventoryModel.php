<?php

require_once __DIR__ . '/../core/Model.php';

class InventoryModel extends BaseModel {
    
    /**
     * Calculates and logs morning sellable stock
     * Logic 1: Morning Sellable Stock: (Carry Forward - Wastage) + Fresh Processed.
     */
    public function logMorningStock($itemId, $date, $carryForward, $wastage, $freshProcessed) {
        $openingQty = ($carryForward - $wastage) + $freshProcessed;
        
        $sql = "INSERT INTO daily_stocks (item_id, log_date, carry_forward_qty, wastage_qty, fresh_processed_qty, opening_qty) 
                VALUES (:item_id, :log_date, :carry_forward_qty, :wastage_qty, :fresh_processed_qty, :opening_qty)
                ON DUPLICATE KEY UPDATE 
                carry_forward_qty = VALUES(carry_forward_qty),
                wastage_qty = VALUES(wastage_qty),
                fresh_processed_qty = VALUES(fresh_processed_qty),
                opening_qty = VALUES(opening_qty)";
                
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'item_id' => $itemId,
            'log_date' => $date,
            'carry_forward_qty' => $carryForward,
            'wastage_qty' => $wastage,
            'fresh_processed_qty' => $freshProcessed,
            'opening_qty' => $openingQty
        ]);
    }

    /**
     * Reconciles end of day stock
     * Logic 1: Actual Sold Qty at End of Day: Opening Stock - Closing Stock - Complimentary Qty.
     */
    public function reconcileEndOfDayStock($itemId, $date, $closingQty, $complimentaryQty) {
        // Fetch current opening stock
        $stmt = $this->db->prepare("SELECT opening_qty, items.selling_price 
                                    FROM daily_stocks 
                                    JOIN items ON items.id = daily_stocks.item_id 
                                    WHERE item_id = :item_id AND log_date = :log_date");
        $stmt->execute(['item_id' => $itemId, 'log_date' => $date]);
        $row = $stmt->fetch();
        
        if ($row) {
            $openingQty = $row['opening_qty'];
            $sellingPrice = $row['selling_price'];
            
            $soldQty = $openingQty - $closingQty - $complimentaryQty;
            $totalSalesAmount = $soldQty * $sellingPrice;
            
            $updateSql = "UPDATE daily_stocks SET 
                          closing_qty = :closing_qty,
                          complimentary_qty = :complimentary_qty,
                          sold_qty = :sold_qty,
                          total_sales_amount = :total_sales_amount
                          WHERE item_id = :item_id AND log_date = :log_date";
                          
            $updateStmt = $this->db->prepare($updateSql);
            return $updateStmt->execute([
                'closing_qty' => $closingQty,
                'complimentary_qty' => $complimentaryQty,
                'sold_qty' => $soldQty,
                'total_sales_amount' => $totalSalesAmount,
                'item_id' => $itemId,
                'log_date' => $date
            ]);
        }
        return false;
    }
}
