<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

$page_title = "Enter Marks";
include '../../includes/header.php';

$exam_id = $_GET['exam_id'] ?? '';
$class_id = $_GET['class_id'] ?? '';
$subject_id = $_GET['subject_id'] ?? '';

$success = "";
$error = "";

// Handle Save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_marks'])) {
    $exam_id_post = $_POST['exam_id'];
    $subject_id_post = $_POST['subject_id'];
    $valid_count = 0;
    
    foreach ($_POST['marks'] as $sid => $mark) {
        if ($mark === '') continue;
        if (!is_numeric($mark)) continue;
        
        $remarks = $_POST['remarks'][$sid] ?? '';
        
        $check_sql = "SELECT result_id FROM exam_results WHERE exam_id=? AND student_id=? AND subject_id=?";
        $stmt_check = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt_check, "iii", $exam_id_post, $sid, $subject_id_post);
        mysqli_stmt_execute($stmt_check);
        $res_check = mysqli_stmt_get_result($stmt_check);
        
        if ($row_exist = mysqli_fetch_assoc($res_check)) {
            $update = mysqli_prepare($conn, "UPDATE exam_results SET marks_obtained=?, remarks=? WHERE result_id=?");
            mysqli_stmt_bind_param($update, "dsi", $mark, $remarks, $row_exist['result_id']);
            mysqli_stmt_execute($update);
        } else {
            $insert = mysqli_prepare($conn, "INSERT INTO exam_results (exam_id, student_id, subject_id, marks_obtained, remarks) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($insert, "iiids", $exam_id_post, $sid, $subject_id_post, $mark, $remarks);
            mysqli_stmt_execute($insert);
        }
        $valid_count++;
    }
    $success = "Marks saved successfully for $valid_count students.";
    
    $exam_id = $exam_id_post;
    $class_id = $_POST['class_id_hidden'];
    $subject_id = $subject_id_post;
}

// Fetch Dropdown Data
$exams = mysqli_query($conn, "SELECT e.*, t.term_name FROM exams e JOIN terms t ON e.term_id = t.term_id ORDER BY e.start_date DESC");
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
$subjects = mysqli_query($conn, "SELECT * FROM subjects ORDER BY subject_name");
?>

<div style="margin-bottom: 30px;">
    <h2 style="margin: 0; color: #1e293b; font-weight: 700;">Marks Management</h2>
    <p style="color: #64748b; margin: 5px 0 0 0;">Enter and update student examination marks.</p>
</div>

<div class="card card-premium" style="margin-bottom: 30px; padding: 25px;">
    <form method="GET">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: flex-end;">
            <div class="form-group" style="margin: 0;">
                <label style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Exam</label>
                <select name="exam_id" class="form-control" required style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px;">
                    <option value="">-- Select Exam --</option>
                    <?php while($e = mysqli_fetch_assoc($exams)): ?>
                        <option value="<?php echo $e['exam_id']; ?>" <?php echo $exam_id == $e['exam_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($e['exam_name'] . ' (' . $e['term_name'] . ')'); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Class</label>
                <select name="class_id" class="form-control" required style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px;">
                    <option value="">-- Select Class --</option>
                    <?php while($c = mysqli_fetch_assoc($classes)): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $class_id == $c['class_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group" style="margin: 0;">
                <label style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Subject</label>
                <select name="subject_id" class="form-control" required style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px;">
                    <option value="">-- Select Subject --</option>
                    <?php while($s = mysqli_fetch_assoc($subjects)): ?>
                        <option value="<?php echo $s['subject_id']; ?>" <?php echo $subject_id == $s['subject_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['subject_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary" style="height: 45px; border-radius: 10px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                Load Marksheet
            </button>
        </div>
    </form>
</div>

<?php 
if ($exam_id && $class_id && $subject_id) {
    $sql_students = "SELECT s.student_id, s.first_name, s.last_name, s.admission_number, r.marks_obtained, r.remarks 
                     FROM students s 
                     LEFT JOIN exam_results r ON s.student_id = r.student_id AND r.exam_id = ? AND r.subject_id = ? 
                     WHERE s.current_class_id = ? AND s.status = 'Active' 
                     ORDER BY s.first_name ASC";
                     
    $stmt_sheet = mysqli_prepare($conn, $sql_students);
    mysqli_stmt_bind_param($stmt_sheet, "iii", $exam_id, $subject_id, $class_id);
    mysqli_stmt_execute($stmt_sheet);
    $res_sheet = mysqli_stmt_get_result($stmt_sheet);
    ?>
    
    <?php if($success): ?>
        <script>window.onload = () => showSuccess("<?php echo $success; ?>");</script>
    <?php endif; ?>

    <div class="card card-premium" style="overflow: hidden; border: none;">
        <div style="padding: 20px 25px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h4 style="margin: 0; color: #1e293b; font-weight: 700;">Student Marksheet</h4>
            <span style="font-size: 13px; color: #64748b;">Enter marks (0-100) and remarks.</span>
        </div>
        
        <form method="POST">
            <input type="hidden" name="save_marks" value="1">
            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
            <input type="hidden" name="class_id_hidden" value="<?php echo $class_id; ?>">
            <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
            
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: white; border-bottom: 2px solid #f1f5f9;">
                        <th style="padding: 15px 25px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">Adm No</th>
                        <th style="padding: 15px 25px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">Student Name</th>
                        <th style="padding: 15px 25px; text-align: center; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; width: 150px;">Marks</th>
                        <th style="padding: 15px 25px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res_sheet) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($res_sheet)): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                            <td style="padding: 15px 25px; color: #64748b; font-weight: 500;"><?php echo htmlspecialchars($row['admission_number']); ?></td>
                            <td style="padding: 15px 25px; color: #1e293b; font-weight: 600;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                            <td style="padding: 15px 25px; text-align: center;">
                                <input type="number" step="0.1" min="0" max="100" 
                                       name="marks[<?php echo $row['student_id']; ?>]" 
                                       value="<?php echo $row['marks_obtained'] !== null ? number_format($row['marks_obtained'], 1) : ''; ?>" 
                                       class="form-control text-center" style="width: 100px; margin: 0 auto; font-weight: 700; border-radius: 8px; border: 1px solid #e2e8f0;">
                            </td>
                            <td style="padding: 15px 25px;">
                                <input type="text" 
                                       name="remarks[<?php echo $row['student_id']; ?>]" 
                                       value="<?php echo htmlspecialchars($row['remarks'] ?? ''); ?>" 
                                       class="form-control" style="border-radius: 8px; border: 1px solid #e2e8f0;" placeholder="Add remarks...">
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="padding: 40px; text-align: center; color: #94a3b8;">No active students found in this class.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if (mysqli_num_rows($res_sheet) > 0): ?>
            <div style="padding: 25px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right;">
                <button type="submit" class="btn btn-success" style="padding: 12px 40px; border-radius: 12px; font-weight: 700; font-size: 16px; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2); display: inline-flex; align-items: center; gap: 10px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Save All Marks
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>

<?php 
} else { 
?>
    <div class="card card-premium" style="padding: 60px 20px; text-align: center;">
        <div style="background: #eff6ff; color: #3b82f6; padding: 20px; border-radius: 50%; display: inline-block; margin-bottom: 20px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <h3 style="color: #1e293b; margin-bottom: 5px;">Sheet Ready</h3>
        <p style="color: #64748b;">Please select an Exam, Class, and Subject above to load the marksheet.</p>
    </div>
<?php 
} 
include '../../includes/modals.php';
include '../../includes/footer.php'; 
?>
