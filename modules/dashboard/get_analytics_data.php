<?php
// Disable error displaying to prevent HTML injection into JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    // Use __DIR__ for robust path resolution
    $auth_path = __DIR__ . '/../../includes/auth_functions.php';
    if (!file_exists($auth_path)) {
        throw new Exception("Auth functions not found at $auth_path");
    }
    require_once $auth_path;

    // Ensure only authorized users can access
    // This might fail if session access fails, caught by catch block
    check_auth();

    // Check role safely - allow accountants to view financial data
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'accountant'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    $db_path = __DIR__ . '/../../config/database.php';
    if (!file_exists($db_path)) {
        throw new Exception("DB connection not found at $db_path");
    }
    require_once $db_path;

    $action = $_GET['action'] ?? '';
    $response = [];

    switch ($action) {
        case 'get_financials':
            $months = [];
            $income = [];

            for ($i = 5; $i >= 0; $i--) {
                $month_start = date('Y-m-01', strtotime("-$i months"));
                $month_end = date('Y-m-t', strtotime("-$i months"));
                $month_label = date('M', strtotime("-$i months"));

                // Ensure table exists or handle error
                $sql = "SELECT SUM(amount) as total FROM payments WHERE payment_date BETWEEN '$month_start' AND '$month_end'";
                $res = mysqli_query($conn, $sql);
                if (!$res) {
                    // Table might not exist or other DB error
                    // Log it but don't break JSON
                    $income[] = 0;
                } else {
                    $row = mysqli_fetch_assoc($res);
                    $income[] = $row['total'] ?? 0;
                }

                $months[] = $month_label;
            }

            $response = [
                'labels' => $months,
                'income' => $income
            ];
            break;

        case 'get_enrollment':
            $years = [];
            $counts = [];
            $current_year = date('Y');

            for ($i = 4; $i >= 0; $i--) {
                $year = $current_year - $i;
                $sql = "SELECT COUNT(*) as total FROM students WHERE YEAR(enrollment_date) <= '$year'";
                $res = mysqli_query($conn, $sql);
                $total = 0;
                if ($res) {
                    $row = mysqli_fetch_assoc($res);
                    $total = $row['total'];
                }

                $years[] = $year;
                $counts[] = $total;
            }

            $response = [
                'labels' => $years,
                'data' => $counts
            ];
            break;

        case 'get_debtors':
            $sql = "SELECT 
                        s.student_id,
                        s.first_name,
                        s.last_name,
                        c.class_name,
                        (COALESCE(SUM(sf.total_amount), 0) - COALESCE(SUM(sf.paid_amount), 0)) as balance
                    FROM students s
                    JOIN student_fees sf ON s.student_id = sf.student_id
                    LEFT JOIN classes c ON s.current_class_id = c.class_id
                    GROUP BY s.student_id
                    HAVING balance > 0
                    ORDER BY balance DESC
                    LIMIT 5";

            $res = mysqli_query($conn, $sql);
            $debtors = [];

            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $debtors[] = $row;
                }
            }

            $response = $debtors;
            break;

        case 'get_kpi':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-t');
            $revenue_mtd = 0;
            $collection_rate = 0;
            $unpaid_count = 0;
            $partial_count = 0;

            // Revenue
            $sql_rev = "SELECT SUM(amount) as total FROM payments WHERE payment_date BETWEEN '$start_date' AND '$end_date'";
            $res_rev = mysqli_query($conn, $sql_rev);
            if ($res_rev) {
                $revenue_mtd = mysqli_fetch_assoc($res_rev)['total'] ?? 0;
            }

            // Collection Rate
            $sql_fee = "SELECT SUM(total_amount) as invoiced, SUM(paid_amount) as paid FROM student_fees";
            $res_fee = mysqli_query($conn, $sql_fee);
            if ($res_fee) {
                $fee_data = mysqli_fetch_assoc($res_fee);
                $collection_rate = $fee_data['invoiced'] > 0 ? round(($fee_data['paid'] / $fee_data['invoiced']) * 100, 1) : 0;
            }

            // Count students with unpaid invoices (status = 'Unpaid')
            $sql_unpaid = "SELECT COUNT(DISTINCT sf.student_id) as count 
                          FROM student_fees sf 
                          WHERE sf.status = 'Unpaid'";
            $res_unpaid = mysqli_query($conn, $sql_unpaid);
            if ($res_unpaid) {
                $unpaid_count = mysqli_fetch_assoc($res_unpaid)['count'] ?? 0;
            }

            // Count students with partial payments (status = 'Partial')
            $sql_partial = "SELECT COUNT(DISTINCT sf.student_id) as count 
                           FROM student_fees sf 
                           WHERE sf.status = 'Partial'";
            $res_partial = mysqli_query($conn, $sql_partial);
            if ($res_partial) {
                $partial_count = mysqli_fetch_assoc($res_partial)['count'] ?? 0;
            }

            $response = [
                'revenue_mtd' => $revenue_mtd,
                'collection_rate' => $collection_rate,
                'unpaid_count' => $unpaid_count,
                'partial_count' => $partial_count
            ];
            break;

        default:
            $response = ['error' => 'Invalid action'];
    }

} catch (Exception $e) {
    http_response_code(500);
    $response = ['error' => 'Server Error: ' . $e->getMessage()];
}

echo json_encode($response);
?>