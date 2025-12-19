<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action == 'add_year') {
        $year_name = trim($_POST['year_name']);
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : NULL;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;

        $stmt = mysqli_prepare($conn, "INSERT INTO academic_years (year_name, start_date, end_date) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $year_name, $start_date, $end_date);
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Year added successfully.';
        } else {
            $response['message'] = 'Error adding year.';
        }

    } elseif ($action == 'add_term') {
        $term_name = trim($_POST['term_name']);
        $year_id = $_POST['year_id'];
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : NULL;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;

        $stmt = mysqli_prepare($conn, "INSERT INTO terms (term_name, academic_year_id, start_date, end_date) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "siss", $term_name, $year_id, $start_date, $end_date);
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Term added successfully.';
        } else {
            $response['message'] = 'Error adding term.';
        }

    } elseif ($action == 'edit_year') {
        $year_id = $_POST['year_id'];
        $year_name = trim($_POST['year_name']);
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : NULL;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;

        $stmt = mysqli_prepare($conn, "UPDATE academic_years SET year_name=?, start_date=?, end_date=? WHERE year_id=?");
        mysqli_stmt_bind_param($stmt, "sssi", $year_name, $start_date, $end_date, $year_id);
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Year updated successfully.';
        } else {
            $response['message'] = 'Error updating year.';
        }

    } elseif ($action == 'delete_year') {
        $year_id = $_POST['year_id'];
        $password = $_POST['password'];

        // Verify password
        $user_id = $_SESSION['user_id'];
        $check_sql = "SELECT password_hash FROM users WHERE user_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $user_id);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && $password == $user['password_hash']) {
            // Check if year has terms
            $term_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM terms WHERE academic_year_id = $year_id");
            $term_count = mysqli_fetch_assoc($term_check)['count'];

            if ($term_count > 0) {
                $response['message'] = 'Cannot delete year with existing terms. Delete terms first.';
            } else {
                $stmt = mysqli_prepare($conn, "DELETE FROM academic_years WHERE year_id = ?");
                mysqli_stmt_bind_param($stmt, "i", $year_id);
                if (mysqli_stmt_execute($stmt)) {
                    $response['success'] = true;
                    $response['message'] = 'Year deleted successfully.';
                } else {
                    $response['message'] = 'Error deleting year.';
                }
            }
        } else {
            $response['message'] = 'Incorrect password.';
        }

    } elseif ($action == 'edit_term') {
        $term_id = $_POST['term_id'];
        $term_name = trim($_POST['term_name']);
        $year_id = $_POST['year_id'];
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : NULL;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;

        $stmt = mysqli_prepare($conn, "UPDATE terms SET term_name=?, academic_year_id=?, start_date=?, end_date=? WHERE term_id=?");
        mysqli_stmt_bind_param($stmt, "sissi", $term_name, $year_id, $start_date, $end_date, $term_id);
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Term updated successfully.';
        } else {
            $response['message'] = 'Error updating term.';
        }

    } elseif ($action == 'delete_term') {
        $term_id = $_POST['term_id'];
        $password = $_POST['password'];

        // Verify password
        $user_id = $_SESSION['user_id'];
        $check_sql = "SELECT password_hash FROM users WHERE user_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $user_id);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && $password == $user['password_hash']) {
            $stmt = mysqli_prepare($conn, "DELETE FROM terms WHERE term_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $term_id);
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Term deleted successfully.';
            } else {
                $response['message'] = 'Error deleting term.';
            }
        } else {
            $response['message'] = 'Incorrect password.';
        }
    } elseif ($action == 'get_events') {
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
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = $row;
        }
        $response['success'] = true;
        $response['events'] = $events;

    } elseif ($action == 'add_event') {
        $title = trim($_POST['title']);
        $date = $_POST['date'];
        $type = $_POST['type'];
        $description = $_POST['description'] ?? '';

        $stmt = mysqli_prepare($conn, "INSERT INTO calendar_events (title, start_date, event_type, description) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $title, $date, $type, $description);

        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Event added successfully.';
        } else {
            $response['message'] = 'Error adding event.';
        }

    } elseif ($action == 'delete_event') {
        $event_id = $_POST['event_id'];

        $stmt = mysqli_prepare($conn, "DELETE FROM calendar_events WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $event_id);
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Event deleted successfully.';
        } else {
            $response['message'] = 'Error deleting event.';
        }

    } elseif ($action == 'add_message') {
        $message = trim($_POST['message']);

        $stmt = mysqli_prepare($conn, "INSERT INTO calendar_messages (message) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $message);

        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Message added successfully.';
        } else {
            $response['message'] = 'Error adding message.';
        }

    } elseif ($action == 'get_messages') {
        $result = mysqli_query($conn, "SELECT * FROM calendar_messages ORDER BY created_at DESC");
        $messages = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $messages[] = $row;
        }
        $response['success'] = true;
        $response['messages'] = $messages;

    } elseif ($action == 'delete_message') {
        $message_id = $_POST['message_id'];

        $stmt = mysqli_prepare($conn, "DELETE FROM calendar_messages WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $message_id);

        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Message deleted successfully.';
        } else {
            $response['message'] = 'Error deleting message.';
        }
    }
}

echo json_encode($response);
?>