<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\InventoryModel;
use App\Controllers\FinanceController;
use Config\Database;
use PDO;

/**
 * Inventory Controller
 * Handles Phase 2 Business Logic: Daily stock reconciliation and night closing prep.
 */
class InventoryController extends Controller {
    private $db;
    private $inventoryModel;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->inventoryModel = new InventoryModel();
    }

    // ----------------------------------------------------------------
    //  MORNING PREP
    // ----------------------------------------------------------------

    /**
     * Display the Morning Prep form
     * Route: ?url=inventory/dailyPrep
     */
    public function dailyPrep() {
        $rawInventory = $this->inventoryModel->getRawInventory();

        $this->view('inventory/daily_prep', [
            'pageTitle'    => 'Morning Prep',
            'activeNav'    => 'stock',
            'rawInventory' => $rawInventory,
        ]);
    }

    /**
     * Handle POST submission from the Morning Prep form
     * Route: ?url=inventory/saveDailyPrep
     */
    public function saveDailyPrep() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?url=inventory/dailyPrep');
        }

        $logDate = $_POST['log_date'] ?? date('Y-m-d');
        $items   = $_POST['items'] ?? [];

        foreach ($items as $itemId => $values) {
            $wastageQty      = (float) ($values['wastage_qty']      ?? 0);
            $freshProcessed  = (float) ($values['fresh_processed_qty'] ?? 0);

            // Calculate the opening qty from the formula:
            // Opening = (Carry Forward - Wastage) + Fresh Processed
            // Carry forward is yesterday's closing_qty for this item
            $stmtCF = $this->db->prepare("
                SELECT closing_qty FROM daily_stocks
                WHERE item_id = :id AND log_date < :date
                ORDER BY log_date DESC LIMIT 1
            ");
            $stmtCF->execute([':id' => $itemId, ':date' => $logDate]);
            $carryForward = (float) ($stmtCF->fetchColumn() ?? 0);

            $openingQty = ($carryForward - $wastageQty) + $freshProcessed;

            // Upsert the daily_stocks record for morning data
            $this->inventoryModel->saveDailyStock([
                ':item_id'            => (int) $itemId,
                ':log_date'           => $logDate,
                ':carry_forward_qty'  => $carryForward,
                ':wastage_qty'        => $wastageQty,
                ':complimentary_qty'  => 0,
                ':fresh_processed_qty'=> $freshProcessed,
                ':opening_qty'        => $openingQty,
                ':closing_qty'        => 0,
                ':sold_qty'           => 0,
                ':total_sales_amount' => 0,
            ]);
        }

        // Re-render the form with a success message
        $this->view('inventory/daily_prep', [
            'pageTitle'      => 'Morning Prep',
            'activeNav'      => 'stock',
            'rawInventory'   => $this->inventoryModel->getRawInventory(),
            'successMessage' => 'Morning prep saved successfully!',
        ]);
    }

    // ----------------------------------------------------------------
    //  NIGHT CLOSING
    // ----------------------------------------------------------------

    /**
     * Display the Night Closing form with pre-calculated opening quantities
     * Route: ?url=inventory/closeDayView
     */
    public function closeDayView() {
        $date = date('Y-m-d');

        // Fetch all active sellable items
        $stmt = $this->db->query("SELECT * FROM items ORDER BY item_name ASC");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $closeDayData = [];
        foreach ($items as $item) {
            // Get today's existing stock row (if morning prep was done)
            $stockStmt = $this->db->prepare("SELECT * FROM daily_stocks WHERE item_id = :id AND log_date = :date");
            $stockStmt->execute([':id' => $item['id'], ':date' => $date]);
            $dailyStock = $stockStmt->fetch(PDO::FETCH_ASSOC);

            // Use saved opening_qty OR dynamically calculate it
            $openingQty = ($dailyStock && (float)$dailyStock['opening_qty'] > 0)
                ? (float) $dailyStock['opening_qty']
                : $this->calculateMorningStock($item['id'], $date);

            $closeDayData[] = [
                'item_id'          => $item['id'],
                'item_name'        => $item['item_name'],
                'opening_qty'      => $openingQty,
                'closing_qty'      => $dailyStock['closing_qty'] ?? '',
                'complimentary_qty'=> $dailyStock['complimentary_qty'] ?? '',
                'wastage_qty'      => $dailyStock['wastage_qty'] ?? '',
            ];
        }

        $this->view('inventory/close_day', [
            'pageTitle' => 'Night Closing',
            'activeNav' => 'close',
            'itemsData' => $closeDayData,
            'submitted' => false,
        ]);
    }

    /**
     * Handle POST from the Night Closing form, save data, then display results
     * Route: ?url=inventory/saveCloseDay
     */
    public function saveCloseDay() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?url=inventory/closeDayView');
        }

        $logDate = $_POST['log_date'] ?? date('Y-m-d');
        $items   = $_POST['items'] ?? [];

        foreach ($items as $itemId => $values) {
            $openingQty      = (float) ($values['opening_qty']       ?? 0);
            $closingQty      = (float) ($values['closing_qty']       ?? 0);
            $complimentaryQty= (float) ($values['complimentary_qty'] ?? 0);

            // Actual sold = Opening - Closing - Complimentary
            $soldQty = $openingQty - $closingQty - $complimentaryQty;
            if ($soldQty < 0) $soldQty = 0;

            // Fetch selling_price for this item to calculate total_sales_amount
            $priceStmt = $this->db->prepare("SELECT selling_price FROM items WHERE id = :id");
            $priceStmt->execute([':id' => $itemId]);
            $sellingPrice = (float) ($priceStmt->fetchColumn() ?? 0);
            $totalSalesAmount = $soldQty * $sellingPrice;

            // Upsert the closing data into daily_stocks
            $this->inventoryModel->saveDailyStock([
                ':item_id'            => (int) $itemId,
                ':log_date'           => $logDate,
                ':carry_forward_qty'  => 0, // Preserved via ON DUPLICATE KEY UPDATE
                ':wastage_qty'        => 0,
                ':complimentary_qty'  => $complimentaryQty,
                ':fresh_processed_qty'=> 0,
                ':opening_qty'        => $openingQty,
                ':closing_qty'        => $closingQty,
                ':sold_qty'           => $soldQty,
                ':total_sales_amount' => $totalSalesAmount,
            ]);
        }

        // Calculate financial results using the FinanceController formulas
        $financeController = new FinanceController();
        $cashInDrawer = $financeController->calculateCashInDrawer($logDate);
        $netProfit    = $financeController->calculateNetProfit($logDate);

        // Render the same view with submitted=true to show the results panel
        $this->view('inventory/close_day', [
            'pageTitle'    => 'Night Closing — Results',
            'activeNav'    => 'close',
            'itemsData'    => [],
            'submitted'    => true,
            'cashInDrawer' => $cashInDrawer,
            'netProfit'    => $netProfit,
        ]);
    }

    // ----------------------------------------------------------------
    //  PRIVATE HELPERS
    // ----------------------------------------------------------------

    /**
     * Calculate Morning Stock for an item
     * Formula: (Carry Forward - Wastage) + Fresh Processed
     */
    private function calculateMorningStock($itemId, $date) {
        $stmt = $this->db->prepare("
            SELECT carry_forward_qty, wastage_qty, fresh_processed_qty
            FROM daily_stocks WHERE item_id = :id AND log_date = :date
        ");
        $stmt->execute([':id' => $itemId, ':date' => $date]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            return ((float)$record['carry_forward_qty'] - (float)$record['wastage_qty'])
                 + (float)$record['fresh_processed_qty'];
        }
        return 0.00;
    }
}
