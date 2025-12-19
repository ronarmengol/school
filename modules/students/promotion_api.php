<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']);

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    switch($action) {
        case 'count_students':
            countStudents();
            break;
        
        case 'get_all_students':
            getAllStudents();
            break;
        
        case 'preview':
            previewPromotion($input);
            break;
        
        case 'execute':
            executePromotion($input);
            break;
        
        case 'rollback':
            rollbackPromotion($input);
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function countStudents() {
    global $conn;
    $class_id = $_GET['class_id'] ?? 0;
    $year_id = $_GET['year_id'] ?? 0;
    
    $sql = "SELECT COUNT(*) as count FROM students 
            WHERE current_class_id = ? AND status = 'Active'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $class_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    echo json_encode(['count' => $row['count']]);
}

function getAllStudents() {
    global $conn;
    $year_id = $_GET['year_id'] ?? 0;
    
    $sql = "SELECT s.student_id, s.first_name, s.last_name, s.admission_number, 
                   s.current_class_id, c.class_name, c.section_name
            FROM students s
            LEFT JOIN classes c ON s.current_class_id = c.class_id
            WHERE s.status = 'Active'
            ORDER BY c.class_name, c.section_name, s.last_name, s.first_name";
    
    $result = mysqli_query($conn, $sql);
    $students = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = [
            'student_id' => $row['student_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'admission_number' => $row['admission_number'],
            'class_id' => $row['current_class_id'],
            'class_name' => $row['class_name'],
            'section_name' => $row['section_name']
        ];
    }
    
    echo json_encode(['success' => true, 'students' => $students]);
}

function previewPromotion($input) {
    global $conn;
    $from_year = $input['from_year'];
    $mappings = $input['mappings'];
    $retained_students = $input['retained_students'] ?? [];
    
    $students = [];
    
    foreach ($mappings as $current_class_id => $next_action) {
        if (empty($next_action)) continue;
        
        // Fetch students in this class
        $sql = "SELECT s.*, c.class_name, c.section_name 
                FROM students s
                LEFT JOIN classes c ON s.current_class_id = c.class_id
                WHERE s.current_class_id = ? AND s.status = 'Active'";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $current_class_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($student = mysqli_fetch_assoc($result)) {
            $student_id_str = (string)$student['student_id'];
            $is_retained = in_array($student_id_str, $retained_students);
            
            $action_text = '';
            
            // Check if student is individually marked for retention
            if ($is_retained) {
                $action_text = '🔄 Retain in ' . $student['class_name'] . ' ' . ($student['section_name'] ?? '');
            } elseif ($next_action === 'alumni') {
                $action_text = '🎓 Mark as Alumni';
            } elseif ($next_action === 'retain') {
                $action_text = '🔄 Retain in ' . $student['class_name'];
            } else {
                // Get next class name
                $class_sql = "SELECT class_name, section_name FROM classes WHERE class_id = ?";
                $class_stmt = mysqli_prepare($conn, $class_sql);
                mysqli_stmt_bind_param($class_stmt, "i", $next_action);
                mysqli_stmt_execute($class_stmt);
                $class_result = mysqli_stmt_get_result($class_stmt);
                $next_class = mysqli_fetch_assoc($class_result);
                $action_text = '⬆️ Promote to ' . $next_class['class_name'] . ' ' . ($next_class['section_name'] ?? '');
            }
            
            $students[] = [
                'student_id' => $student['student_id'],
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'admission_number' => $student['admission_number'],
                'current_class' => $student['class_name'] . ' ' . ($student['section_name'] ?? ''),
                'action' => $action_text
            ];
        }
    }
    
    echo json_encode(['success' => true, 'students' => $students]);
}

function executePromotion($input) {
    global $conn;
    $from_year = $input['from_year'];
    $to_year = $input['to_year'];
    $batch_name = $input['batch_name'];
    $mappings = $input['mappings'];
    $retained_students = $input['retained_students'] ?? [];
    $user_id = $_SESSION['user_id'];
    
    mysqli_begin_transaction($conn);
    
    try {
        // Create promotion batch record
        $batch_sql = "INSERT INTO promotion_batches (batch_name, from_year_id, to_year_id, promoted_by) 
                      VALUES (?, ?, ?, ?)";
        $batch_stmt = mysqli_prepare($conn, $batch_sql);
        mysqli_stmt_bind_param($batch_stmt, "siii", $batch_name, $from_year, $to_year, $user_id);
        mysqli_stmt_execute($batch_stmt);
        $batch_id = mysqli_insert_id($conn);
        
        $promoted_count = 0;
        
        foreach ($mappings as $current_class_id => $next_action) {
            if (empty($next_action)) continue;
            
            // Fetch students in this class
            $sql = "SELECT student_id, current_class_id FROM students 
                    WHERE current_class_id = ? AND status = 'Active'";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $current_class_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($student = mysqli_fetch_assoc($result)) {
                $student_id_str = (string)$student['student_id'];
                
                // Check if this student is in the retained list
                $is_retained = in_array($student_id_str, $retained_students);
                
                // Record in academic history
                $history_sql = "INSERT INTO student_academic_history 
                               (student_id, academic_year_id, class_id, promoted_by, final_status) 
                               VALUES (?, ?, ?, ?, ?)";
                $history_stmt = mysqli_prepare($conn, $history_sql);
                
                // If student is marked for retention, override the class mapping
                if ($is_retained) {
                    $final_status = 'Retained';
                    mysqli_stmt_bind_param($history_stmt, "iiiis", 
                        $student['student_id'], $from_year, $current_class_id, $user_id, $final_status);
                    mysqli_stmt_execute($history_stmt);
                    // Student stays in same class, no update needed
                    
                } elseif ($next_action === 'alumni') {
                    $final_status = 'Graduated';
                    mysqli_stmt_bind_param($history_stmt, "iiiis", 
                        $student['student_id'], $from_year, $current_class_id, $user_id, $final_status);
                    mysqli_stmt_execute($history_stmt);
                    
                    // Update student status to Alumni
                    $update_sql = "UPDATE students SET status = 'Alumni' WHERE student_id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_sql);
                    mysqli_stmt_bind_param($update_stmt, "i", $student['student_id']);
                    mysqli_stmt_execute($update_stmt);
                    
                } elseif ($next_action === 'retain') {
                    $final_status = 'Retained';
                    mysqli_stmt_bind_param($history_stmt, "iiiis", 
                        $student['student_id'], $from_year, $current_class_id, $user_id, $final_status);
                    mysqli_stmt_execute($history_stmt);
                    // Student stays in same class, no update needed
                    
                } else {
                    $final_status = 'Promoted';
                    mysqli_stmt_bind_param($history_stmt, "iiiis", 
                        $student['student_id'], $from_year, $current_class_id, $user_id, $final_status);
                    mysqli_stmt_execute($history_stmt);
                    
                    // Update student's current class
                    $update_sql = "UPDATE students SET current_class_id = ? WHERE student_id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_sql);
                    mysqli_stmt_bind_param($update_stmt, "ii", $next_action, $student['student_id']);
                    mysqli_stmt_execute($update_stmt);
                }
                
                $promoted_count++;
            }
        }
        
        // Update batch with count
        $update_batch_sql = "UPDATE promotion_batches SET students_promoted = ? WHERE batch_id = ?";
        $update_batch_stmt = mysqli_prepare($conn, $update_batch_sql);
        mysqli_stmt_bind_param($update_batch_stmt, "ii", $promoted_count, $batch_id);
        mysqli_stmt_execute($update_batch_stmt);
        
        mysqli_commit($conn);
        
        // Get year names for response
        $year_sql = "SELECT year_name FROM academic_years WHERE year_id = ?";
        $year_stmt = mysqli_prepare($conn, $year_sql);
        mysqli_stmt_bind_param($year_stmt, "i", $from_year);
        mysqli_stmt_execute($year_stmt);
        $from_year_name = mysqli_fetch_assoc(mysqli_stmt_get_result($year_stmt))['year_name'];
        
        mysqli_stmt_bind_param($year_stmt, "i", $to_year);
        mysqli_stmt_execute($year_stmt);
        $to_year_name = mysqli_fetch_assoc(mysqli_stmt_get_result($year_stmt))['year_name'];
        
        echo json_encode([
            'success' => true, 
            'count' => $promoted_count,
            'batch_id' => $batch_id,
            'from_year' => $from_year_name,
            'to_year' => $to_year_name
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }
}

function rollbackPromotion($input) {
    global $conn;
    $batch_id = $input['batch_id'];
    $user_id = $_SESSION['user_id'];
    
    mysqli_begin_transaction($conn);
    
    try {
        // Get batch details
        $batch_sql = "SELECT * FROM promotion_batches WHERE batch_id = ? AND is_rolled_back = 0";
        $batch_stmt = mysqli_prepare($conn, $batch_sql);
        mysqli_stmt_bind_param($batch_stmt, "i", $batch_id);
        mysqli_stmt_execute($batch_stmt);
        $batch = mysqli_fetch_assoc(mysqli_stmt_get_result($batch_stmt));
        
        if (!$batch) {
            throw new Exception('Batch not found or already rolled back');
        }
        
        // Get all history records for this batch (by date range)
        $history_sql = "SELECT sah.*, s.student_id, s.status
                       FROM student_academic_history sah
                       JOIN students s ON sah.student_id = s.student_id
                       WHERE sah.academic_year_id = ? 
                       AND sah.promoted_date >= ?
                       AND sah.promoted_date <= DATE_ADD(?, INTERVAL 1 MINUTE)";
        $history_stmt = mysqli_prepare($conn, $history_sql);
        mysqli_stmt_bind_param($history_stmt, "iss", 
            $batch['from_year_id'], 
            $batch['promotion_date'],
            $batch['promotion_date']
        );
        mysqli_stmt_execute($history_stmt);
        $history_result = mysqli_stmt_get_result($history_stmt);
        
        while ($record = mysqli_fetch_assoc($history_result)) {
            if ($record['final_status'] === 'Graduated') {
                // Revert alumni back to active and restore their class
                $revert_sql = "UPDATE students 
                              SET status = 'Active', current_class_id = ? 
                              WHERE student_id = ?";
                $revert_stmt = mysqli_prepare($conn, $revert_sql);
                mysqli_stmt_bind_param($revert_stmt, "ii", $record['class_id'], $record['student_id']);
                mysqli_stmt_execute($revert_stmt);
                
            } elseif ($record['final_status'] === 'Promoted') {
                // Revert to previous class
                $revert_sql = "UPDATE students SET current_class_id = ? WHERE student_id = ?";
                $revert_stmt = mysqli_prepare($conn, $revert_sql);
                mysqli_stmt_bind_param($revert_stmt, "ii", $record['class_id'], $record['student_id']);
                mysqli_stmt_execute($revert_stmt);
            }
            // For 'Retained', no action needed as they stayed in same class
            
            // Delete history record
            $delete_history_sql = "DELETE FROM student_academic_history WHERE history_id = ?";
            $delete_history_stmt = mysqli_prepare($conn, $delete_history_sql);
            mysqli_stmt_bind_param($delete_history_stmt, "i", $record['history_id']);
            mysqli_stmt_execute($delete_history_stmt);
        }
        
        // Mark batch as rolled back
        $update_batch_sql = "UPDATE promotion_batches 
                            SET is_rolled_back = 1, rollback_date = NOW() 
                            WHERE batch_id = ?";
        $update_batch_stmt = mysqli_prepare($conn, $update_batch_sql);
        mysqli_stmt_bind_param($update_batch_stmt, "i", $batch_id);
        mysqli_stmt_execute($update_batch_stmt);
        
        mysqli_commit($conn);
        
        echo json_encode(['success' => true, 'message' => 'Promotion rolled back successfully']);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }
}
?>
