<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();

// Allow access to multiple roles
check_role(['super_admin', 'admin', 'teacher', 'parent', 'student']);

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;

// Logic to determine which student's results to show
if ($role == 'student') {
    $stmt = mysqli_prepare($conn, "SELECT student_id FROM students WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $student_id = $row['student_id'];
    }
} elseif ($role == 'parent') {
    $children_sql = "SELECT student_id, first_name, last_name, admission_number FROM students WHERE parent_id = ?";
    $stmt = mysqli_prepare($conn, $children_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $children_result = mysqli_stmt_get_result($stmt);
    $children = [];
    while ($child = mysqli_fetch_assoc($children_result)) {
        $children[] = $child;
    }
    if (!$student_id && count($children) > 0) {
        $student_id = $children[0]['student_id'];
    }
    if ($student_id) {
        $owned = false;
        foreach ($children as $child) {
            if ($child['student_id'] == $student_id) {
                $owned = true;
                break;
            }
        }
        if (!$owned)
            die("Unauthorized access to student results.");
    }
}

$page_title = "Exam Results";
include '../../includes/header.php';

// Fetch Student Details
$student_info = null;
if ($student_id) {
    $stmt = mysqli_prepare($conn, "SELECT s.*, c.class_name, c.section_name FROM students s LEFT JOIN classes c ON s.current_class_id = c.class_id WHERE s.student_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $student_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}
?>

<div class="row" style="margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between;">
    <div>
        <h2 style="margin: 0; color: #1e293b; font-weight: 700;">Academic Performance</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">View detailed exam results and progress reports.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <?php if ($role == 'parent' && count($children) > 1): ?>
            <div class="card"
                style="padding: 5px 15px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 10px; flex-direction: row; background: white;">
                <label style="margin: 0; font-size: 14px; font-weight: 500; color: #64748b;">Switch Student:</label>
                <form method="GET" style="margin: 0;">
                    <select name="student_id" onchange="this.form.submit()"
                        style="border: none; font-weight: 600; color: #1e293b; outline: none; cursor: pointer;">
                        <?php foreach ($children as $child): ?>
                            <option value="<?php echo $child['student_id']; ?>" <?php echo ($child['student_id'] == $student_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        <?php endif; ?>
        <button onclick="window.print()" class="btn btn-secondary"
            style="display: flex; align-items: center; gap: 8px; border-radius: 10px; padding: 8px 20px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Print
        </button>
    </div>
</div>

<?php if ($student_info): ?>
    <!-- Student Profile Summary -->
    <div class="card card-premium"
        style="padding: 25px; margin-bottom: 30px; display: flex; align-items: center; gap: 20px; flex-direction: row; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
        <div
            style="width: 80px; height: 80px; background: #6366f1; color: white; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);">
            <?php echo strtoupper(substr($student_info['first_name'], 0, 1) . substr($student_info['last_name'], 0, 1)); ?>
        </div>
        <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                <h3 style="margin: 0; font-size: 24px; color: #1e293b;">
                    <?php echo htmlspecialchars($student_info['first_name'] . ' ' . $student_info['last_name']); ?></h3>
                <span
                    style="background: #e0e7ff; color: #4338ca; padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
                    <?php echo htmlspecialchars($student_info['admission_number']); ?>
                </span>
            </div>
            <div style="display: flex; gap: 15px; color: #64748b; font-size: 14px;">
                <span style="display: flex; align-items: center; gap: 5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <?php echo htmlspecialchars($student_info['class_name'] . ' ' . $student_info['section_name']); ?>
                </span>
                <span style="display: flex; align-items: center; gap: 5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Academic Year 2025/26
                </span>
            </div>
        </div>
        <div style="text-align: right;">
            <?php
            // Calculate overall performance summary if possible
            $overall_sql = "SELECT AVG(marks_obtained) as avg_marks FROM exam_results WHERE student_id = ?";
            $st_overall = mysqli_prepare($conn, $overall_sql);
            mysqli_stmt_bind_param($st_overall, "i", $student_id);
            mysqli_stmt_execute($st_overall);
            $overall_avg = mysqli_fetch_assoc(mysqli_stmt_get_result($st_overall))['avg_marks'] ?? 0;
            ?>
            <div
                style="font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 5px;">
                Overall Average</div>
            <div style="font-size: 32px; font-weight: 800; color: #6366f1;"><?php echo number_format($overall_avg, 1); ?>%
            </div>
        </div>
    </div>

    <!-- Exam Results Listing -->
    <?php
    $exams_sql = "SELECT DISTINCT e.exam_id, e.exam_name, t.term_name, y.year_name, e.start_date
                  FROM exam_results m
                  JOIN exams e ON m.exam_id = e.exam_id
                  JOIN terms t ON e.term_id = t.term_id
                  JOIN academic_years y ON t.academic_year_id = y.year_id
                  WHERE m.student_id = ?
                  ORDER BY e.start_date DESC";
    $stmt_exams = mysqli_prepare($conn, $exams_sql);
    mysqli_stmt_bind_param($stmt_exams, "i", $student_id);
    mysqli_stmt_execute($stmt_exams);
    $exams_res = mysqli_stmt_get_result($stmt_exams);

    if (mysqli_num_rows($exams_res) > 0):
        while ($exam = mysqli_fetch_assoc($exams_res)): ?>
            <div class="card card-premium" style="margin-bottom: 30px; border: none; overflow: hidden;">
                <div
                    style="padding: 20px 25px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 style="margin: 0; color: #1e293b; font-weight: 700;"><?php echo htmlspecialchars($exam['exam_name']); ?>
                        </h4>
                        <div style="font-size: 13px; color: #64748b; margin-top: 2px;">
                            <?php echo htmlspecialchars($exam['term_name'] . " - " . $exam['year_name']); ?> •
                            <?php echo date('M Y', strtotime($exam['start_date'])); ?>
                        </div>
                    </div>
                </div>

                <div style="padding: 0;">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: white; border-bottom: 2px solid #f1f5f9;">
                                <th
                                    style="padding: 15px 25px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                                    Subject</th>
                                <th
                                    style="padding: 15px 25px; text-align: center; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                                    Marks</th>
                                <th
                                    style="padding: 15px 25px; text-align: center; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                                    Grade</th>
                                <th
                                    style="padding: 15px 25px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                                    Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $marks_sql = "SELECT m.marks_obtained, m.grade, m.remarks, s.subject_name 
                                          FROM exam_results m 
                                          JOIN subjects s ON m.subject_id = s.subject_id 
                                          WHERE m.student_id = ? AND m.exam_id = ?";
                            $stmt_marks = mysqli_prepare($conn, $marks_sql);
                            mysqli_stmt_bind_param($stmt_marks, "ii", $student_id, $exam['exam_id']);
                            mysqli_stmt_execute($stmt_marks);
                            $marks_res = mysqli_stmt_get_result($stmt_marks);

                            $total_marks = 0;
                            $count_subjects = 0;

                            while ($mark = mysqli_fetch_assoc($marks_res)):
                                $total_marks += $mark['marks_obtained'];
                                $count_subjects++;
                                $mark_color = $mark['marks_obtained'] >= 50 ? '#10b981' : '#ef4444';
                                $bg_color = $mark['marks_obtained'] >= 50 ? '#f0fdf4' : '#fef2f2';
                                ?>
                                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;"
                                    onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                                    <td style="padding: 18px 25px; color: #1e293b; font-weight: 600;">
                                        <?php echo htmlspecialchars($mark['subject_name']); ?></td>
                                    <td style="padding: 18px 25px; text-align: center;">
                                        <span
                                            style="font-weight: 700; color: #334155;"><?php echo number_format($mark['marks_obtained'], 1); ?></span>
                                        <div
                                            style="height: 4px; width: 40px; background: #e2e8f0; border-radius: 99px; margin: 5px auto 0;">
                                            <div
                                                style="height: 100%; width: <?php echo $mark['marks_obtained']; ?>%; background: <?php echo $mark_color; ?>; border-radius: 99px;">
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 18px 25px; text-align: center;">
                                        <span
                                            style="display: inline-block; padding: 4px 12px; border-radius: 8px; font-weight: 700; background: <?php echo $bg_color; ?>; color: <?php echo $mark_color; ?>; border: 1px solid rgba(0,0,0,0.05);">
                                            <?php echo htmlspecialchars($mark['grade'] ?: '-'); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 18px 25px; color: #64748b; font-style: italic; font-size: 14px;">
                                        <?php echo htmlspecialchars($mark['remarks'] ?: 'No remarks provided.'); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <?php if ($count_subjects > 0):
                            $avg = $total_marks / $count_subjects;
                            $status = $avg >= 50 ? 'Passed' : 'Failed';
                            $status_color = $avg >= 50 ? '#10b981' : '#ef4444';
                            ?>
                            <tfoot style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                                <tr>
                                    <td style="padding: 20px 25px; font-weight: 700; color: #1e293b;">Final Assessment</td>
                                    <td style="padding: 20px 25px; text-align: center;">
                                        <div style="font-weight: 800; font-size: 18px; color: #1e293b;">
                                            <?php echo number_format($avg, 1); ?>%</div>
                                        <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase;">Average</div>
                                    </td>
                                    <td style="padding: 20px 25px; text-align: center;">
                                        <span
                                            style="font-weight: 700; color: <?php echo $status_color; ?>; text-transform: uppercase; font-size: 14px; letter-spacing: 0.1em;">
                                            <?php echo $status; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 20px 25px; text-align: right;">
                                        <button class="btn btn-primary btn-sm"
                                            style="font-size: 12px; padding: 6px 15px; border-radius: 8px;">View Detailed Sheet</button>
                                    </td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        <?php endwhile;
    else: ?>
        <div class="card card-premium" style="padding: 60px 20px; text-align: center; color: #94a3b8;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 20px; opacity: 0.5;">
                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="9" y1="13" x2="15" y2="13"></line>
                <line x1="9" y1="17" x2="15" y2="17"></line>
                <line x1="9" y1="9" x2="10" y2="9"></line>
            </svg>
            <h3 style="color: #64748b; margin-bottom: 5px;">No Exam Results</h3>
            <p>We couldn't find any academic records for the selected student.</p>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="card card-premium" style="padding: 40px; text-align: center;">
        <div
            style="background: #fffbeb; color: #f59e0b; padding: 20px; border-radius: 12px; display: inline-block; margin-bottom: 20px; border: 1px solid #fef3c7;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>
        <h3 style="color: #1e293b;">Access Warning</h3>
        <p style="color: #64748b;">Please select a student from the system to view their results.</p>
    </div>
<?php endif; ?>

<?php
include '../../includes/modals.php';
include '../../includes/footer.php';
?>