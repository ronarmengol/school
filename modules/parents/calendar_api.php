<?php
require_once '../../includes/auth_functions.php';
check_auth(); // Any authenticated user can access

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'get_events') {
        $month = $_POST['month'];
        $year = $_POST['year'];
        
        $start_date = "$year-$month-01";
        $end_date = date("Y-m-t", strtotime($start_date));
        
        $sql = "SELECT * FROM calendar_events WHERE start_date BETWEEN ? AND ? ORDER BY start_date ASC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $events = [];
        while($row = mysqli_fetch_assoc($result)) {
            $events[] = $row;
        }
        $response['success'] = true;
        $response['events'] = $events;
        
    } elseif ($action == 'get_messages') {
        $result = mysqli_query($conn, "SELECT * FROM calendar_messages ORDER BY created_at DESC LIMIT 10");
        $messages = [];
        while($row = mysqli_fetch_assoc($result)) {
            $messages[] = $row;
        }
        $response['success'] = true;
        $response['messages'] = $messages;
    }
}

echo json_encode($response);
?>
