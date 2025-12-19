<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action == 'add_subject') {
        $subject_name = trim($_POST['subject_name']);
        $subject_code = trim($_POST['subject_code']);

        if (!empty($subject_name)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO subjects (subject_name, subject_code) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ss", $subject_name, $subject_code);
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Subject added successfully.';
            } else {
                $response['message'] = 'Error adding subject: ' . mysqli_error($conn);
            }
        } else {
            $response['message'] = 'Subject name is required.';
        }

    } elseif ($action == 'edit_subject') {
        $subject_id = $_POST['subject_id'];
        $subject_name = trim($_POST['subject_name']);
        $subject_code = trim($_POST['subject_code']);

        if (!empty($subject_name)) {
            $stmt = mysqli_prepare($conn, "UPDATE subjects SET subject_name = ?, subject_code = ? WHERE subject_id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $subject_name, $subject_code, $subject_id);
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Subject updated successfully.';
            } else {
                $response['message'] = 'Error updating subject.';
            }
        } else {
            $response['message'] = 'Subject name is required.';
        }

    } elseif ($action == 'delete_subject') {
        $subject_id = $_POST['subject_id'];

        $stmt = mysqli_prepare($conn, "DELETE FROM subjects WHERE subject_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $subject_id);
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Subject deleted successfully.';
        } else {
            $response['message'] = 'Error deleting subject.';
        }
    } elseif ($action == 'get_subject') {
        $subject_id = $_POST['subject_id'];
        $stmt = mysqli_prepare($conn, "SELECT * FROM subjects WHERE subject_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $subject_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($data = mysqli_fetch_assoc($result)) {
            $response['success'] = true;
            $response['data'] = $data;
        } else {
            $response['message'] = 'Subject not found.';
        }
    }
}

echo json_encode($response);
?>
