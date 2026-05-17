<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

class BackupController extends Controller {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function downloadSQLBackup() {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo "Access denied. Admin only.";
            return;
        }

        $filename = 'bondorbati_backup_' . date('Y-m-d') . '.sql';
        $sql = $this->generateFullBackup();

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
        exit;
    }

    private function generateFullBackup() {
        $output = "-- Bondor Bati Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $create = $this->db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            $output .= "\n-- Table: $table\n";
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $output .= $create['Create Table'] . ";\n\n";

            $rows = $this->db->query("SELECT * FROM `$table`");
            while ($row = $row = $rows->fetch(PDO::FETCH_ASSOC)) {
                $values = [];
                foreach ($row as $value) {
                    $values[] = $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                }
                $output .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
            $output .= "\n";
        }

        $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        return $output;
    }

    public function exportMonthlySalesCSV() {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo "Access denied. Admin only.";
            return;
        }

        $filename = 'bondorbati_sales_' . date('Y-m') . '.csv';
        $csv = $this->generateMonthlySalesCSV();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $csv;
        exit;
    }

    private function generateMonthlySalesCSV() {
        $currentMonth = date('Y-m');
        $csv = "Date,Item,Selling Price,Cost Price,Sold Qty,Revenue,Profit\n";

        $stmt = $this->db->prepare("
            SELECT ds.log_date, i.item_name, i.selling_price, i.cost_price,
                   ds.sold_qty, ds.total_sales_amount,
                   (i.selling_price - i.cost_price) * ds.sold_qty AS profit
            FROM daily_stocks ds
            JOIN items i ON ds.item_id = i.id
            WHERE ds.log_date LIKE :month AND ds.sold_qty > 0
            ORDER BY ds.log_date ASC
        ");
        $stmt->execute([':month' => $currentMonth . '%']);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalRevenue = 0;
        $totalProfit = 0;

        foreach ($sales as $row) {
            $csv .= $row['log_date'] . ',';
            $csv .= '"' . $row['item_name'] . '",';
            $csv .= $row['selling_price'] . ',';
            $csv .= $row['cost_price'] . ',';
            $csv .= $row['sold_qty'] . ',';
            $csv .= $row['total_sales_amount'] . ',';
            $csv .= $row['profit'] . "\n";

            $totalRevenue += $row['total_sales_amount'];
            $totalProfit += $row['profit'];
        }

        $csv .= "\n";
        $csv .= ",,TOTALS,," . $totalRevenue . ',' . $totalProfit . "\n";

        $csv .= "\nMonthly Expenses Summary\n";
        $csv .= "Date,Category,Name,Amount\n";

        $expStmt = $this->db->prepare("
            SELECT expense_date, category, name, total_amount
            FROM expenses
            WHERE expense_date LIKE :month
            ORDER BY expense_date
        ");
        $expStmt->execute([':month' => $currentMonth . '%']);
        $expenses = $expStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($expenses as $exp) {
            $csv .= $exp['expense_date'] . ',';
            $csv .= $exp['category'] . ',';
            $csv .= '"' . $exp['name'] . '",';
            $csv .= $exp['total_amount'] . "\n";
        }

        return $csv;
    }
}