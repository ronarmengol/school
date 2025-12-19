<?php
require_once '../../includes/auth_functions.php';
require_once '../../config/database.php';

check_auth();
check_role(['super_admin', 'admin', 'teacher']);

header('Content-Type: application/json');

$class_id = $_GET['class_id'] ?? '';
$month = $_GET['month'] ?? '';
$date = $_GET['date'] ?? '';

if (!$class_id) {
  echo json_encode(['success' => false, 'message' => 'Class ID is required']);
  exit;
}

// Handle month-based query (for View Attendance tab)
if ($month) {
  list($year, $monthNum) = explode('-', $month);

  // Get number of days in the month
  $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthNum, $year);

  // First, get all students in the class
  $studentsSql = "SELECT student_id, first_name, last_name, admission_number
                  FROM students
                  WHERE current_class_id = $class_id AND status = 'Active'
                  ORDER BY first_name ASC, last_name ASC";

  $studentsResult = mysqli_query($conn, $studentsSql);

  $students = [];
  $counts = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Excused' => 0];

  while ($student = mysqli_fetch_assoc($studentsResult)) {
    // Get attendance records for this student for the month
    $attendanceSql = "SELECT DAY(attendance_date) as day, status
                      FROM attendance
                      WHERE student_id = {$student['student_id']}
                        AND YEAR(attendance_date) = $year
                        AND MONTH(attendance_date) = $monthNum";

    $attendanceResult = mysqli_query($conn, $attendanceSql);

    $attendance = [];
    while ($record = mysqli_fetch_assoc($attendanceResult)) {
      $day = (int) $record['day'];
      $attendance[$day] = $record['status'];

      if (isset($counts[$record['status']])) {
        $counts[$record['status']]++;
      }
    }

    $students[] = [
      'student_id' => $student['student_id'],
      'first_name' => $student['first_name'],
      'last_name' => $student['last_name'],
      'admission_number' => $student['admission_number'],
      'attendance' => $attendance
    ];
  }

  // Fetch term date ranges
  $termsRes = mysqli_query($conn, "SELECT start_date, end_date FROM terms WHERE start_date IS NOT NULL AND end_date IS NOT NULL");
  $termRanges = [];
  while ($t = mysqli_fetch_assoc($termsRes)) {
    $termRanges[] = $t;
  }

  // Fetch class-level notes (not specific to any student)
  $classNotesSql = "SELECT 
                    DATE(created_at) as note_date,
                    COUNT(*) as note_count,
                    MAX(CASE WHEN priority = 'Urgent' THEN 1 ELSE 0 END) as has_urgent,
                    MAX(CASE WHEN priority = 'High' THEN 1 ELSE 0 END) as has_high,
                    GROUP_CONCAT(CONCAT(title, '|', priority, '|', category, '|', COALESCE(note_content, '')) SEPARATOR ';;;') as notes_data
                  FROM school_notes
                  WHERE (related_class_id = $class_id AND (related_student_id IS NULL OR related_student_id = 0))
                    AND YEAR(created_at) = $year
                    AND MONTH(created_at) = $monthNum
                    AND status != 'Closed'
                  GROUP BY DATE(created_at)";

  $classNotesRes = mysqli_query($conn, $classNotesSql);
  $classNotesByDate = [];
  while ($note = mysqli_fetch_assoc($classNotesRes)) {
    $day = (int) date('d', strtotime($note['note_date']));
    $classNotesByDate[$day] = [
      'count' => (int) $note['note_count'],
      'has_urgent' => (bool) $note['has_urgent'],
      'has_high' => (bool) $note['has_high'],
      'notes_data' => $note['notes_data']
    ];
  }

  // Fetch student-specific notes (include student name)
  $studentNotesSql = "SELECT 
                        n.related_student_id,
                        DATE(n.created_at) as note_date,
                        COUNT(*) as note_count,
                        MAX(CASE WHEN n.priority = 'Urgent' THEN 1 ELSE 0 END) as has_urgent,
                        MAX(CASE WHEN n.priority = 'High' THEN 1 ELSE 0 END) as has_high,
                        GROUP_CONCAT(CONCAT(n.title, '|', n.priority, '|', n.category, '|', COALESCE(n.note_content, ''), '|', s.first_name, ' ', s.last_name) SEPARATOR ';;;') as notes_data
                      FROM school_notes n
                      JOIN students s ON n.related_student_id = s.student_id
                      WHERE n.related_student_id > 0
                        AND n.related_class_id = $class_id
                        AND YEAR(n.created_at) = $year
                        AND MONTH(n.created_at) = $monthNum
                        AND n.status != 'Closed'
                      GROUP BY n.related_student_id, DATE(n.created_at)";

  $studentNotesRes = mysqli_query($conn, $studentNotesSql);
  $studentNotesByDate = [];
  while ($note = mysqli_fetch_assoc($studentNotesRes)) {
    $day = (int) date('d', strtotime($note['note_date']));
    $sid = (int) $note['related_student_id'];
    if (!isset($studentNotesByDate[$sid]))
      $studentNotesByDate[$sid] = [];
    $studentNotesByDate[$sid][$day] = [
      'count' => (int) $note['note_count'],
      'has_urgent' => (bool) $note['has_urgent'],
      'has_high' => (bool) $note['has_high'],
      'notes_data' => $note['notes_data']
    ];
  }

  // Also provide a flat list of all notes for the month for the summary section
  $allNotesSql = "SELECT n.*, s.first_name, s.last_name, u.full_name as created_by_name
                  FROM school_notes n
                  LEFT JOIN students s ON n.related_student_id = s.student_id
                  LEFT JOIN users u ON n.created_by = u.user_id
                  WHERE n.related_class_id = $class_id
                    AND YEAR(n.created_at) = $year
                    AND MONTH(n.created_at) = $monthNum
                    AND n.status != 'Closed'
                  ORDER BY n.created_at DESC";
  $allNotesRes = mysqli_query($conn, $allNotesSql);
  $allNotes = [];
  while ($note = mysqli_fetch_assoc($allNotesRes)) {
    $allNotes[] = $note;
  }

  echo json_encode([
    'success' => true,
    'students' => $students,
    'counts' => $counts,
    'daysInMonth' => $daysInMonth,
    'year' => (int) $year,
    'month' => (int) $monthNum,
    'termRanges' => $termRanges,
    'classNotesByDate' => $classNotesByDate,
    'studentNotesByDate' => $studentNotesByDate,
    'allNotes' => $allNotes
  ]);
  exit;
}

// Handle single date query (for backward compatibility)
if (!$date) {
  $date = date('Y-m-d');
}

// Fetch students and their attendance status for a specific date
$sql = "SELECT s.student_id, s.first_name, s.last_name, s.admission_number, COALESCE(a.status, 'Not Marked') as status
        FROM students s
        LEFT JOIN attendance a ON s.student_id = a.student_id AND a.attendance_date = '$date'
        WHERE s.current_class_id = $class_id AND s.status = 'Active'
        ORDER BY s.first_name ASC";

$result = mysqli_query($conn, $sql);

$students = [];
$counts = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Excused' => 0, 'Not Marked' => 0];

while ($row = mysqli_fetch_assoc($result)) {
  $students[] = $row;
  if (isset($counts[$row['status']])) {
    $counts[$row['status']]++;
  } else {
    $counts['Not Marked']++;
  }
}

// Fetch notes for this specific date and class
$notesSql = "SELECT n.*, s.first_name, s.last_name, u.full_name as created_by_name
             FROM school_notes n
             LEFT JOIN students s ON n.related_student_id = s.student_id
             LEFT JOIN users u ON n.created_by = u.user_id
             WHERE n.related_class_id = $class_id
               AND DATE(n.created_at) = '$date'
               AND n.status != 'Closed'
             ORDER BY n.created_at DESC";
$notesRes = mysqli_query($conn, $notesSql);
$dayNotes = [];
while ($note = mysqli_fetch_assoc($notesRes)) {
  $dayNotes[] = $note;
}

echo json_encode([
  'success' => true,
  'students' => $students,
  'counts' => $counts,
  'dayNotes' => $dayNotes
]);
?>