<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $action = $_POST['action'] ?? '';

  if ($action == 'save_lesson_plan') {
    $plan_id = !empty($_POST['plan_id']) ? (int) $_POST['plan_id'] : null;
    $teacher_id = $_SESSION['user_id'];
    $class_id = (int) $_POST['class_id'];
    $subject_id = (int) $_POST['subject_id'];
    $lesson_date = $_POST['lesson_date'];
    $duration = $_POST['duration'];
    $topic = trim($_POST['topic']);
    $sub_topic = trim($_POST['sub_topic']);
    $objective = trim($_POST['objective']);
    $intro_minutes = (int) $_POST['intro_minutes'];
    $teaching_minutes = (int) $_POST['teaching_minutes'];
    $practice_minutes = (int) $_POST['practice_minutes'];
    $assessment_minutes = (int) $_POST['assessment_minutes'];
    $total_minutes = (int) $_POST['total_minutes'];
    $status = $_POST['status'] ?? 'submitted';

    // Encode arrays to JSON
    $teaching_methods = json_encode($_POST['teaching_methods'] ?? []);
    $learner_activities = json_encode($_POST['learner_activities'] ?? []);
    $teaching_aids = json_encode($_POST['teaching_aids'] ?? []);
    $remarks = trim($_POST['remarks']);

    if ($plan_id) {
      // Update existing
      $sql = "UPDATE lesson_plans SET 
                    class_id = ?, subject_id = ?, lesson_date = ?, duration = ?, 
                    topic = ?, sub_topic = ?, objective = ?, 
                    intro_minutes = ?, teaching_minutes = ?, practice_minutes = ?, assessment_minutes = ?, 
                    total_minutes = ?, teaching_methods = ?, learner_activities = ?, 
                    teaching_aids = ?, remarks = ?, status = ?
                    WHERE plan_id = ? AND (teacher_id = ? OR ? IN (SELECT role_name FROM roles r JOIN users u ON u.role_id = r.role_id WHERE u.user_id = ? AND r.role_name IN ('admin', 'super_admin')))";

      // Simplified check for admin/super_admin or owner
      $is_admin = in_array($_SESSION['role'], ['admin', 'super_admin']);
      $sql = "UPDATE lesson_plans SET 
                    class_id = ?, subject_id = ?, lesson_date = ?, duration = ?, 
                    topic = ?, sub_topic = ?, objective = ?, 
                    intro_minutes = ?, teaching_minutes = ?, practice_minutes = ?, assessment_minutes = ?, 
                    total_minutes = ?, teaching_methods = ?, learner_activities = ?, 
                    teaching_aids = ?, remarks = ?, status = ?
                    WHERE plan_id = ?";

      if (!$is_admin) {
        $sql .= " AND teacher_id = $teacher_id";
      }

      $stmt = mysqli_prepare($conn, $sql);
      mysqli_stmt_bind_param(
        $stmt,
        "iisssssiiiiisssssi",
        $class_id,
        $subject_id,
        $lesson_date,
        $duration,
        $topic,
        $sub_topic,
        $objective,
        $intro_minutes,
        $teaching_minutes,
        $practice_minutes,
        $assessment_minutes,
        $total_minutes,
        $teaching_methods,
        $learner_activities,
        $teaching_aids,
        $remarks,
        $status,
        $plan_id
      );
    } else {
      // Insert new
      $sql = "INSERT INTO lesson_plans (
                    teacher_id, class_id, subject_id, lesson_date, duration, 
                    topic, sub_topic, objective, 
                    intro_minutes, teaching_minutes, practice_minutes, assessment_minutes, 
                    total_minutes, teaching_methods, learner_activities, 
                    teaching_aids, remarks, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

      $stmt = mysqli_prepare($conn, $sql);
      mysqli_stmt_bind_param(
        $stmt,
        "iiisssssiiiiisssss",
        $teacher_id,
        $class_id,
        $subject_id,
        $lesson_date,
        $duration,
        $topic,
        $sub_topic,
        $objective,
        $intro_minutes,
        $teaching_minutes,
        $practice_minutes,
        $assessment_minutes,
        $total_minutes,
        $teaching_methods,
        $learner_activities,
        $teaching_aids,
        $remarks,
        $status
      );
    }

    if (mysqli_stmt_execute($stmt)) {
      $response['success'] = true;
      $response['message'] = $plan_id ? 'Lesson plan updated successfully.' : 'Lesson plan saved successfully.';
    } else {
      $response['message'] = 'Database error: ' . mysqli_error($conn);
    }
  } elseif ($action == 'delete_lesson_plan') {
    $plan_id = (int) $_POST['plan_id'];
    $teacher_id = $_SESSION['user_id'];
    $is_admin = in_array($_SESSION['role'], ['admin', 'super_admin']);

    $sql = "DELETE FROM lesson_plans WHERE plan_id = ?";
    if (!$is_admin) {
      $sql .= " AND teacher_id = $teacher_id";
    }

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $plan_id);

    if (mysqli_stmt_execute($stmt)) {
      $response['success'] = true;
      $response['message'] = 'Lesson plan deleted successfully.';
    } else {
      $response['message'] = 'Error deleting lesson plan.';
    }
  }
}

echo json_encode($response);
?>