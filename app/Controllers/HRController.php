<?php
namespace App\Controllers;

use App\Core\Controller;
use Config\Database;
use PDO;

class HRController extends Controller {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function hrPayroll() {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->redirect('?url=home');
            return;
        }

        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

        $staffList = $this->getActiveStaff();
        $payrollData = $this->calculateMonthlyPayroll($month, $year);

        $this->view('admin/hr_payroll', [
            'pageTitle' => 'Staff Payroll',
            'activeNav' => 'hr',
            'staffList' => $staffList,
            'payrollData' => $payrollData,
            'currentMonth' => $month,
            'currentYear' => $year,
        ]);
    }

    public function logAbsence() {
        header('Content-Type: application/json');

        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Admin only']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $userId = (int) ($data['user_id'] ?? 0);
        $absentDate = $data['absent_date'] ?? date('Y-m-d');

        if (!$userId || !$absentDate) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }

        try {
            $salaryStmt = $this->db->prepare("SELECT daily_rate FROM staff_salaries WHERE user_id = :user_id AND (end_date IS NULL OR end_date >= :absent_date)");
            $salaryStmt->execute([':user_id' => $userId, ':absent_date' => $absentDate]);
            $salary = $salaryStmt->fetch(PDO::FETCH_ASSOC);

            $deduction = $salary ? $salary['daily_rate'] : 1;

            $stmt = $this->db->prepare("INSERT INTO attendance_logs (user_id, absent_date, deduct_salary) VALUES (:user_id, :absent_date, :deduction)");
            $stmt->execute([
                ':user_id' => $userId,
                ':absent_date' => $absentDate,
                ':deduction' => $deduction
            ]);

            echo json_encode(['success' => true, 'message' => 'Absence logged']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function getActiveStaff() {
        $stmt = $this->db->query("
            SELECT u.id, u.name, u.username, ss.monthly_salary, ss.daily_rate
            FROM users u
            JOIN staff_salaries ss ON u.id = ss.user_id
            WHERE u.is_active = 1 AND (ss.end_date IS NULL OR ss.end_date >= CURDATE())
            ORDER BY u.name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function calculateMonthlyPayroll($month, $year) {
        $staff = $this->getActiveStaff();
        $result = [];

        foreach ($staff as $member) {
            $absentStmt = $this->db->prepare("
                SELECT COUNT(*) AS total_absences, SUM(deduct_salary) AS total_deduction
                FROM attendance_logs
                WHERE user_id = :user_id
                  AND MONTH(absent_date) = :month
                  AND YEAR(absent_date) = :year
            ");
            $absentStmt->execute([
                ':user_id' => $member['id'],
                ':month' => $month,
                ':year' => $year
            ]);
            $attendance = $absentStmt->fetch(PDO::FETCH_ASSOC);

            $absentDays = (int) ($attendance['total_absences'] ?? 0);
            $deduction = (float) ($attendance['total_deduction'] ?? 0);
            $finalPay = (float) $member['monthly_salary'] - $deduction;

            $result[] = [
                'id' => $member['id'],
                'name' => $member['name'],
                'monthly_salary' => $member['monthly_salary'],
                'daily_rate' => $member['daily_rate'],
                'absent_days' => $absentDays,
                'deduction' => $deduction,
                'final_pay' => max(0, $finalPay)
            ];
        }

        return $result;
    }
}