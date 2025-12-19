<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
  switch ($action) {
    case 'update':
      $note_id = intval($_POST['note_id'] ?? 0);
      $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
      $content = mysqli_real_escape_string($conn, $_POST['content'] ?? '');
      $priority = mysqli_real_escape_string($conn, $_POST['priority'] ?? 'Medium');
      $category = mysqli_real_escape_string($conn, $_POST['category'] ?? 'General');
      $student_id = !empty($_POST['student_id']) ? intval($_POST['student_id']) : null;
      $class_id = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;

      if ($note_id <= 0 || empty($title) || empty($content)) {
        throw new Exception('Invalid parameters or missing required fields');
      }

      // Permission check
      $check_sql = "SELECT created_by FROM school_notes WHERE note_id = ?";
      $stmt = mysqli_prepare($conn, $check_sql);
      mysqli_stmt_bind_param($stmt, "i", $note_id);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      $note = mysqli_fetch_assoc($result);

      if (!$note)
        throw new Exception('Note not found');
      if ($note['created_by'] != $_SESSION['user_id'] && !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
        throw new Exception('Permission denied');
      }

      $sql = "UPDATE school_notes SET title = ?, note_content = ?, priority = ?, category = ?, related_student_id = ?, related_class_id = ? WHERE note_id = ?";
      $stmt = mysqli_prepare($conn, $sql);
      mysqli_stmt_bind_param($stmt, "ssssiii", $title, $content, $priority, $category, $student_id, $class_id, $note_id);

      if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Note updated successfully']);
      } else {
        throw new Exception('Failed to update note');
      }
      break;

    case 'create':
      $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
      $content = mysqli_real_escape_string($conn, $_POST['content'] ?? '');
      $priority = mysqli_real_escape_string($conn, $_POST['priority'] ?? 'Medium');
      $category = mysqli_real_escape_string($conn, $_POST['category'] ?? 'General');
      $student_id = !empty($_POST['student_id']) ? intval($_POST['student_id']) : null;
      $class_id = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;
      $created_by = $_SESSION['user_id'];

      if (empty($title) || empty($content)) {
        throw new Exception('Title and content are required');
      }

      $sql = "INSERT INTO school_notes (title, note_content, priority, category, related_student_id, related_class_id, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
      $stmt = mysqli_prepare($conn, $sql);
      mysqli_stmt_bind_param($stmt, "ssssiis", $title, $content, $priority, $category, $student_id, $class_id, $created_by);

      if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
          'success' => true,
          'message' => 'Note created successfully',
          'note_id' => mysqli_insert_id($conn)
        ]);
      } else {
        throw new Exception('Failed to create note');
      }
      break;

    case 'update_status':
      $note_id = intval($_POST['note_id'] ?? 0);
      $status = mysqli_real_escape_string($conn, $_POST['status'] ?? '');
      $user_id = $_SESSION['user_id'];

      if ($note_id <= 0 || empty($status)) {
        throw new Exception('Invalid parameters');
      }

      $resolved_at = ($status === 'Resolved' || $status === 'Closed') ? 'NOW()' : 'NULL';
      $resolved_by = ($status === 'Resolved' || $status === 'Closed') ? $user_id : 'NULL';

      $sql = "UPDATE school_notes 
                    SET status = ?, resolved_at = $resolved_at, resolved_by = $resolved_by 
                    WHERE note_id = ?";
      $stmt = mysqli_prepare($conn, $sql);
      mysqli_stmt_bind_param($stmt, "si", $status, $note_id);

      if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
          'success' => true,
          'message' => 'Note status updated'
        ]);
      } else {
        throw new Exception('Failed to update status');
      }
      break;

    case 'get_notes':
      $priority_filter = $_POST['priority'] ?? $_GET['priority'] ?? '';
      $status_filter = $_POST['status'] ?? $_GET['status'] ?? 'Open';
      $class_filter = $_POST['class_id'] ?? $_GET['class_id'] ?? '';
      $note_id_filter = intval($_POST['note_id'] ?? $_GET['note_id'] ?? 0);
      $limit = intval($_POST['limit'] ?? $_GET['limit'] ?? 50);

      $sql = "SELECT n.*, 
                    u.full_name as created_by_name,
                    s.first_name as student_first_name,
                    s.last_name as student_last_name,
                    c.class_name,
                    c.section_name
                    FROM school_notes n
                    LEFT JOIN users u ON n.created_by = u.user_id
                    LEFT JOIN students s ON n.related_student_id = s.student_id
                    LEFT JOIN classes c ON n.related_class_id = c.class_id
                    WHERE 1=1";

      if ($note_id_filter > 0) {
        $sql .= " AND n.note_id = " . $note_id_filter;
      }
      if (!empty($priority_filter)) {
        $sql .= " AND n.priority = '" . mysqli_real_escape_string($conn, $priority_filter) . "'";
      }
      if ($status_filter !== 'all') {
        if (!empty($status_filter)) {
          $sql .= " AND n.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
        }
      }
      if (!empty($class_filter)) {
        $sql .= " AND n.related_class_id = " . intval($class_filter);
      }

      $order_by = "FIELD(n.priority, 'Urgent', 'High', 'Medium', 'Low'), n.created_at DESC";
      if ($status_filter === 'Closed') {
        $order_by = "n.created_at DESC";
      }

      $sql .= " ORDER BY $order_by LIMIT $limit";

      $result = mysqli_query($conn, $sql);
      $notes = [];

      while ($row = mysqli_fetch_assoc($result)) {
        $notes[] = $row;
      }

      echo json_encode([
        'success' => true,
        'notes' => $notes
      ]);
      break;

    case 'get_counts':
      $class_id = intval($_POST['class_id'] ?? $_GET['class_id'] ?? 0);
      $where = "WHERE 1=1";
      if ($class_id > 0) {
        $where .= " AND related_class_id = $class_id";
      }

      $sql = "SELECT 
                COUNT(CASE WHEN priority = 'Urgent' AND status != 'Closed' THEN 1 END) as urgent,
                COUNT(CASE WHEN priority = 'High' AND status != 'Closed' THEN 1 END) as high,
                COUNT(CASE WHEN status = 'Open' THEN 1 END) as open,
                COUNT(CASE WHEN status != 'Closed' THEN 1 END) as total,
                COUNT(CASE WHEN status = 'Closed' THEN 1 END) as archived
              FROM school_notes $where";

      $res = mysqli_query($conn, $sql);
      $counts = mysqli_fetch_assoc($res);

      echo json_encode([
        'success' => true,
        'counts' => $counts
      ]);
      break;

    case 'delete':
      $note_id = intval($_POST['note_id'] ?? 0);

      if ($note_id <= 0) {
        throw new Exception('Invalid note ID');
      }

      // Only allow deletion by creator or admin
      $check_sql = "SELECT created_by FROM school_notes WHERE note_id = ?";
      $stmt = mysqli_prepare($conn, $check_sql);
      mysqli_stmt_bind_param($stmt, "i", $note_id);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      $note = mysqli_fetch_assoc($result);

      if (!$note) {
        throw new Exception('Note not found');
      }

      if ($note['created_by'] != $_SESSION['user_id'] && !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
        throw new Exception('You do not have permission to delete this note');
      }

      $sql = "DELETE FROM school_notes WHERE note_id = ?";
      $stmt = mysqli_prepare($conn, $sql);
      mysqli_stmt_bind_param($stmt, "i", $note_id);

      if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
          'success' => true,
          'message' => 'Note deleted successfully'
        ]);
      } else {
        throw new Exception('Failed to delete note');
      }
      break;

    default:
      throw new Exception('Invalid action');
  }
} catch (Exception $e) {
  echo json_encode([
    'success' => false,
    'message' => $e->getMessage()
  ]);
}
?>