<?php
require_once '../../includes/auth_functions.php';
check_auth();

$student_id = $_GET['id'] ?? 0;

// Fetch student data with class info
$sql = "SELECT s.*, c.class_name, c.section_name 
        FROM students s 
        LEFT JOIN classes c ON s.current_class_id = c.class_id 
        WHERE s.student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    header("Location: index.php");
    exit();
}

// Get attendance summary
$sql_attendance = "SELECT 
    COUNT(*) as total_days,
    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days
    FROM attendance 
    WHERE student_id = ?";
$stmt_att = mysqli_prepare($conn, $sql_attendance);
mysqli_stmt_bind_param($stmt_att, "i", $student_id);
mysqli_stmt_execute($stmt_att);
$attendance = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_att));

// Get fee summary
$sql_fees = "SELECT 
    SUM(total_amount) as total_fees,
    SUM(paid_amount) as total_paid
    FROM student_fees 
    WHERE student_id = ?";
$stmt_fees = mysqli_prepare($conn, $sql_fees);
mysqli_stmt_bind_param($stmt_fees, "i", $student_id);
mysqli_stmt_execute($stmt_fees);
$fees = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_fees));

// Get attendance heatmap data (last 30 days)
$sql_heatmap = "SELECT attendance_date, status 
                FROM attendance 
                WHERE student_id = ? 
                AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ORDER BY attendance_date ASC";
$stmt_heatmap = mysqli_prepare($conn, $sql_heatmap);
mysqli_stmt_bind_param($stmt_heatmap, "i", $student_id);
mysqli_stmt_execute($stmt_heatmap);
$heatmap_result = mysqli_stmt_get_result($stmt_heatmap);

// Build heatmap array
$heatmap_data = [];
while ($row = mysqli_fetch_assoc($heatmap_result)) {
    $heatmap_data[$row['attendance_date']] = $row['status'];
}

$page_title = "Student Profile";
include '../../includes/header.php';
?>

<style>
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 12px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 30px;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        font-weight: bold;
        color: #667eea;
    }

    .profile-info h2 {
        margin: 0 0 10px 0;
        font-size: 32px;
    }

    .profile-info p {
        margin: 5px 0;
        opacity: 0.9;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .info-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .info-card h4 {
        margin: 0 0 15px 0;
        color: #64748b;
        font-size: 14px;
        text-transform: uppercase;
        font-weight: 600;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        color: #64748b;
        font-weight: 500;
    }

    .info-value {
        color: #1e293b;
        font-weight: 600;
    }

    .status-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-active {
        background: #d1fae5;
        color: #059669;
    }

    .status-suspended {
        background: #fee2e2;
        color: #dc2626;
    }
</style>

<div style="max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>Student Profile</h3>
        <div style="display: flex; gap: 10px;">
            <?php if (in_array($_SESSION['role'], ['super_admin', 'admin'])): ?>
                <?php if (!$student['parent_id']): ?>
                    <button onclick="openParentModal()" class="btn btn-success">Create Parent Account</button>
                <?php else: ?>
                    <span class="btn btn-outline-success" style="cursor: default;">Parent Active ✓</span>
                <?php endif; ?>
                <a href="edit.php?id=<?php echo $student_id; ?>" class="btn btn-primary">Edit Student</a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-danger">Back to List</a>
        </div>
    </div>

    <!-- Create Parent Modal -->
    <div id="parent-modal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 25px; border-radius: 8px; width: 400px; max-width: 90%;">
            <h4 style="margin-top: 0;">Create Parent Account</h4>
            <p style="font-size: 14px; color: #666;">Create a login for the parent of
                <strong><?php echo htmlspecialchars($student['first_name']); ?></strong>.
            </p>

            <form id="create-parent-form">
                <input type="hidden" name="action" value="create_parent">
                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">

                <div class="form-group">
                    <label>Parent Name</label>
                    <input type="text" name="full_name" class="form-control"
                        value="Parent of <?php echo htmlspecialchars($student['first_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control"
                        value="P<?php echo htmlspecialchars($student['admission_number']); ?>" required>
                    <small style="color: #666;">Default: P + Admission Number</small>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="text" name="password" class="form-control" value="parent123" required>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="document.getElementById('parent-modal').style.display='none'"
                        class="btn btn-secondary" style="border: 1px solid #ccc;">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openParentModal() {
            document.getElementById('parent-modal').style.display = 'flex';
        }

        document.getElementById('create-parent-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.textContent;

            btn.disabled = true;
            btn.textContent = 'Creating...';

            fetch('parent_actions.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        UniversalModal.alert('Success', data.message, () => {
                            location.reload();
                        });
                    } else {
                        UniversalModal.alert('Error', data.message);
                        btn.disabled = false;
                        btn.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    UniversalModal.alert('Error', 'An error occurred while creating the parent account. Please try again.');
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
        });
    </script>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
        </div>
        <div class="profile-info">
            <h2><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
            <p><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_number']); ?></p>
            <p><strong>Class:</strong>
                <?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section_name']); ?></p>
            <span class="status-badge status-<?php echo strtolower($student['status']); ?>">
                <?php echo $student['status']; ?>
            </span>
        </div>
    </div>

    <!-- Info Grid -->
    <div class="info-grid">
        <!-- Personal Information -->
        <div class="info-card">
            <h4>Personal Information</h4>
            <div class="info-row">
                <span class="info-label">Gender</span>
                <span class="info-value"><?php echo htmlspecialchars($student['gender']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Date of Birth</span>
                <span
                    class="info-value"><?php echo $student['dob'] ? date('d/m/Y', strtotime($student['dob'])) : 'N/A'; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Enrollment Date</span>
                <span
                    class="info-value"><?php echo $student['enrollment_date'] ? date('d/m/Y', strtotime($student['enrollment_date'])) : 'N/A'; ?></span>
            </div>
        </div>

        <?php if (get_setting('enable_attendance', '1') == '1'): ?>
            <!-- Attendance Summary -->
            <div class="info-card">
                <h4>Attendance Summary</h4>
                <div class="info-row">
                    <span class="info-label">Total Days</span>
                    <span class="info-value"><?php echo $attendance['total_days'] ?? 0; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Present</span>
                    <span class="info-value" style="color: #10b981;"><?php echo $attendance['present_days'] ?? 0; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Absent</span>
                    <span class="info-value" style="color: #ef4444;"><?php echo $attendance['absent_days'] ?? 0; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Attendance Rate</span>
                    <span class="info-value">
                        <?php
                        $rate = $attendance['total_days'] > 0 ? round(($attendance['present_days'] / $attendance['total_days']) * 100) : 0;
                        echo $rate . '%';
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Fee Summary -->
        <div class="info-card">
            <h4>Fee Summary</h4>
            <div class="info-row">
                <span class="info-label">Total Fees</span>
                <span
                    class="info-value"><?php echo get_setting('currency_symbol', '$') . number_format($fees['total_fees'] ?? 0, 2); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Paid</span>
                <span class="info-value"
                    style="color: #10b981;"><?php echo get_setting('currency_symbol', '$') . number_format($fees['total_paid'] ?? 0, 2); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Balance</span>
                <span class="info-value" style="color: #ef4444;">
                    <?php echo get_setting('currency_symbol', '$') . number_format(($fees['total_fees'] ?? 0) - ($fees['total_paid'] ?? 0), 2); ?>
                </span>
            </div>
        </div>
    </div>

    <?php if (get_setting('enable_attendance', '1') == '1'): ?>
        <!-- Attendance Heatmap -->
        <div class="card" style="padding: 20px; margin-bottom: 20px;">
            <h4 style="margin-bottom: 15px;">Attendance Heatmap (Last 30 Days)</h4>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(30px, 1fr)); gap: 4px;">
                <?php
                // Generate last 30 days
                for ($i = 29; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $day_name = date('D', strtotime($date));
                    $day_num = date('j', strtotime($date));

                    // Get status for this date
                    $status = isset($heatmap_data[$date]) ? $heatmap_data[$date] : 'None';

                    // Color coding matching attendance page
                    $color = '#f1f5f9'; // Default (no record)
                    $text_color = '#94a3b8';
                    $title = 'No record';

                    if ($status == 'Present') {
                        $color = '#d1fae5';
                        $text_color = '#10b981';
                        $title = 'Present';
                    } elseif ($status == 'Absent') {
                        $color = '#fee2e2';
                        $text_color = '#ef4444';
                        $title = 'Absent';
                    } elseif ($status == 'Late') {
                        $color = '#fef3c7';
                        $text_color = '#f59e0b';
                        $title = 'Late';
                    } elseif ($status == 'Excused') {
                        $color = '#e0e7ff';
                        $text_color = '#6366f1';
                        $title = 'Excused';
                    }

                    echo "<div style='background: $color; color: $text_color; padding: 8px; border-radius: 4px; text-align: center; font-size: 11px; font-weight: 600; cursor: pointer;' title='$date - $title'>
                        <div style='font-size: 9px; opacity: 0.7;'>$day_name</div>
                        <div>$day_num</div>
                      </div>";
                }
                ?>
            </div>

            <!-- Legend -->
            <div
                style="margin-top: 15px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; font-size: 13px;">
                <div style="display: flex; align-items: center; gap: 5px;">
                    <div style="width: 20px; height: 20px; background: #d1fae5; border-radius: 3px;"></div>
                    <span>Present</span>
                </div>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <div style="width: 20px; height: 20px; background: #fee2e2; border-radius: 3px;"></div>
                    <span>Absent</span>
                </div>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <div style="width: 20px; height: 20px; background: #fef3c7; border-radius: 3px;"></div>
                    <span>Late</span>
                </div>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <div style="width: 20px; height: 20px; background: #e0e7ff; border-radius: 3px;"></div>
                    <span>Excused</span>
                </div>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <div style="width: 20px; height: 20px; background: #f1f5f9; border-radius: 3px;"></div>
                    <span>No Record</span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php
    // Get academic history
    $history_sql = "SELECT sah.*, ay.year_name, c.class_name, c.section_name, u.full_name as promoted_by_name
                    FROM student_academic_history sah
                    LEFT JOIN academic_years ay ON sah.academic_year_id = ay.year_id
                    LEFT JOIN classes c ON sah.class_id = c.class_id
                    LEFT JOIN users u ON sah.promoted_by = u.user_id
                    WHERE sah.student_id = ?
                    ORDER BY sah.promoted_date DESC";
    $history_stmt = mysqli_prepare($conn, $history_sql);
    mysqli_stmt_bind_param($history_stmt, "i", $student_id);
    mysqli_stmt_execute($history_stmt);
    $history_result = mysqli_stmt_get_result($history_stmt);
    $has_history = mysqli_num_rows($history_result) > 0;
    ?>

    <?php if ($has_history): ?>
        <!-- Academic History -->
        <div class="card" style="padding: 20px; margin-bottom: 20px;">
            <h4 style="margin-bottom: 20px;">📚 Academic History</h4>

            <div style="position: relative; padding-left: 30px;">
                <?php
                $first = true;
                while ($history = mysqli_fetch_assoc($history_result)):
                    $status_colors = [
                        'Promoted' => ['bg' => '#d1fae5', 'text' => '#059669', 'icon' => '⬆️'],
                        'Retained' => ['bg' => '#fef3c7', 'text' => '#d97706', 'icon' => '🔄'],
                        'Graduated' => ['bg' => '#ddd6fe', 'text' => '#7c3aed', 'icon' => '🎓'],
                        'Transferred' => ['bg' => '#e0e7ff', 'text' => '#4f46e5', 'icon' => '➡️']
                    ];
                    $status_info = $status_colors[$history['final_status']] ?? ['bg' => '#f1f5f9', 'text' => '#64748b', 'icon' => '•'];
                    ?>
                    <div
                        style="position: relative; margin-bottom: 20px; padding-bottom: 20px; <?php echo !$first ? 'border-left: 2px solid #e2e8f0;' : ''; ?>">
                        <!-- Timeline dot -->
                        <div
                            style="position: absolute; left: -36px; top: 0; width: 12px; height: 12px; background: <?php echo $status_info['text']; ?>; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px <?php echo $status_info['text']; ?>;">
                        </div>

                        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                <div>
                                    <h5 style="margin: 0 0 5px 0; color: #1e293b;">
                                        <?php echo htmlspecialchars($history['year_name']); ?>
                                    </h5>
                                    <p style="margin: 0; color: #64748b; font-size: 14px;">
                                        <?php echo htmlspecialchars($history['class_name'] . ' ' . ($history['section_name'] ?? '')); ?>
                                    </p>
                                </div>
                                <span
                                    style="background: <?php echo $status_info['bg']; ?>; color: <?php echo $status_info['text']; ?>; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; white-space: nowrap;">
                                    <?php echo $status_info['icon'] . ' ' . $history['final_status']; ?>
                                </span>
                            </div>

                            <div style="font-size: 12px; color: #94a3b8; margin-top: 10px;">
                                <?php echo date('d M Y', strtotime($history['promoted_date'])); ?>
                                <?php if ($history['promoted_by_name']): ?>
                                    • By: <?php echo htmlspecialchars($history['promoted_by_name']); ?>
                                <?php endif; ?>
                            </div>

                            <?php if ($history['remarks']): ?>
                                <div
                                    style="margin-top: 10px; padding: 10px; background: #f8fafc; border-radius: 6px; font-size: 13px; color: #475569;">
                                    <strong>Remarks:</strong> <?php echo htmlspecialchars($history['remarks']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    $first = false;
                endwhile;
                ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="card" style="padding: 20px;">
        <h4 style="margin-bottom: 15px;">Quick Actions</h4>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php if (get_setting('enable_attendance', '1') == '1'): ?>
                <a href="../attendance/index.php?class_id=<?php echo $student['current_class_id']; ?>"
                    class="btn btn-primary">View Attendance</a>
            <?php endif; ?>
            <a href="../exams/enter_marks.php?class_id=<?php echo $student['current_class_id']; ?>"
                class="btn btn-success">View Results</a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>