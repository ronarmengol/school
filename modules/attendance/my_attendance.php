<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();

// Allow multiple roles but we will verify relationship below
check_role(['super_admin', 'admin', 'teacher', 'parent', 'student']);

$student_id = $_GET['student_id'] ?? 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (!$student_id) {
    if ($role == 'student') {
        // Find their student_id
        $s = mysqli_fetch_assoc(mysqli_query($conn, "SELECT student_id FROM students WHERE user_id = $user_id"));
        if ($s) {
            $student_id = $s['student_id'];
        } else {
            die("Student record not found.");
        }
    } elseif ($role == 'parent') {
        // Get first child for this parent
        $s = mysqli_fetch_assoc(mysqli_query($conn, "SELECT student_id FROM students WHERE parent_id = $user_id LIMIT 1"));
        if ($s) {
            $student_id = $s['student_id'];
        } else {
            die("No student records found for this parent.");
        }
    } else {
        die("Student ID required.");
    }
}

// Security / Relationship Check
$authorized = false;
if (in_array($role, ['super_admin', 'admin', 'teacher', 'accountant'])) {
    $authorized = true;
} elseif ($role == 'parent') {
    // Check if this student belongs to this parent
    $stmt = mysqli_prepare($conn, "SELECT student_id FROM students WHERE student_id = ? AND parent_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $user_id);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_fetch($stmt)) {
        $authorized = true;
    }
    mysqli_stmt_close($stmt);
} elseif ($role == 'student') {
    // Check if looking at own record
    $stmt = mysqli_prepare($conn, "SELECT student_id FROM students WHERE student_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $user_id);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_fetch($stmt)) {
        $authorized = true;
    }
    mysqli_stmt_close($stmt);
}

if (!$authorized) {
    die("Unauthorized access to this student's records.");
}

// Fetch Student Info
$sql = "SELECT s.*, c.class_name, c.section_name 
        FROM students s 
        LEFT JOIN classes c ON s.current_class_id = c.class_id 
        WHERE s.student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get attendance summary
$sql_attendance = "SELECT 
    COUNT(*) as total_days,
    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
    SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_days,
    SUM(CASE WHEN status = 'Excused' THEN 1 ELSE 0 END) as excused_days
    FROM attendance 
    WHERE student_id = ?";
$stmt_att = mysqli_prepare($conn, $sql_attendance);
mysqli_stmt_bind_param($stmt_att, "i", $student_id);
mysqli_stmt_execute($stmt_att);
$attendance = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_att));

// Get detailed attendance history (Last 60 days)
$sql_history = "SELECT attendance_date, status 
                FROM attendance 
                WHERE student_id = ? 
                ORDER BY attendance_date DESC
                LIMIT 60";
$stmt_hist = mysqli_prepare($conn, $sql_history);
mysqli_stmt_bind_param($stmt_hist, "i", $student_id);
mysqli_stmt_execute($stmt_hist);
$history_res = mysqli_stmt_get_result($stmt_hist);

// Prepare heatmap data (Monthly view)
$current_month = $_GET['month'] ?? date('Y-m');
$month_start = $current_month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

// Calculate previous and next months
$prev_month = date('Y-m', strtotime($month_start . ' -1 month'));
$next_month = date('Y-m', strtotime($month_start . ' +1 month'));
$current_month_name = date('F Y', strtotime($month_start));

// Fetch attendance for the selected month
$heatmap_data = [];
$sql_heatmap = "SELECT attendance_date, status 
                FROM attendance 
                WHERE student_id = ? 
                AND attendance_date >= ? 
                AND attendance_date <= ?";
$stmt_hm = mysqli_prepare($conn, $sql_heatmap);
mysqli_stmt_bind_param($stmt_hm, "iss", $student_id, $month_start, $month_end);
mysqli_stmt_execute($stmt_hm);
$hm_res = mysqli_stmt_get_result($stmt_hm);
while ($row = mysqli_fetch_assoc($hm_res)) {
    $heatmap_data[$row['attendance_date']] = $row['status'];
}


$page_title = "My Attendance";
include '../../includes/header.php';
?>

<style>
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        height: 100%;
    }

    .stat-val {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #64748b;
        font-size: 14px;
    }

    .card-plain {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }

    .card-header-styled {
        padding: 15px 20px;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 600;
        color: #1e293b;
    }

    .card-body-styled {
        padding: 20px;
    }

    .history-item {
        display: flex;
        justify-content: space-between;
        padding: 12px 20px;
        border-bottom: 1px solid #f1f5f9;
        align-items: center;
    }

    .history-item:last-child {
        border-bottom: none;
    }
</style>

<!-- Header Section -->
<div
    style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0 0 5px 0; font-size: 24px;">Attendance Record</h2>
        <p style="color: #64748b; margin: 0;">
            Student: <strong
                style="color: #1e293b;"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
            (<?php echo htmlspecialchars($student['admission_number']); ?>)
        </p>
    </div>
    <?php if ($role == 'parent'): ?>
        <a href="../parents/index.php" class="btn btn-secondary">Back to Portal</a>
    <?php endif; ?>
</div>

<!-- Stats Grid -->
<div
    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-val" style="color: #3b82f6;"><?php echo $attendance['total_days'] ?? 0; ?></div>
        <div class="stat-label">Total School Days</div>
    </div>
    <div class="stat-card">
        <div class="stat-val" style="color: #10b981;"><?php echo $attendance['present_days'] ?? 0; ?></div>
        <div class="stat-label">Days Present</div>
    </div>
    <div class="stat-card">
        <div class="stat-val" style="color: #ef4444;"><?php echo $attendance['absent_days'] ?? 0; ?></div>
        <div class="stat-label">Days Absent</div>
    </div>
    <div class="stat-card">
        <div class="stat-val" style="color: #f59e0b;"><?php echo $attendance['late_days'] ?? 0; ?></div>
        <div class="stat-label">Days Late</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
    <!-- Visualization / Monthly Calendar -->
    <div class="card-plain">
        <div class="card-header-styled" style="display: flex; justify-content: space-between; align-items: center;">
            <span>Attendance Overview</span>
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="?student_id=<?php echo $student_id; ?>&month=<?php echo $prev_month; ?>" 
                   class="btn btn-sm btn-outline-secondary" 
                   style="padding: 6px 12px; border-radius: 6px; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    Prev
                </a>
                <span style="font-weight: 700; color: #1e293b; min-width: 150px; text-align: center;">
                    <?php echo $current_month_name; ?>
                </span>
                <a href="?student_id=<?php echo $student_id; ?>&month=<?php echo $next_month; ?>" 
                   class="btn btn-sm btn-outline-secondary" 
                   style="padding: 6px 12px; border-radius: 6px; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                    Next
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            </div>
        </div>
        <div class="card-body-styled">
            <!-- Calendar Grid -->
            <div style="margin-bottom: 15px;">
                <!-- Day headers -->
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-bottom: 8px;">
                    <?php
                    $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    foreach ($days as $day) {
                        echo "<div style='text-align: center; font-size: 12px; font-weight: 700; color: #64748b; padding: 8px 0;'>$day</div>";
                    }
                    ?>
                </div>
                
                <!-- Calendar days -->
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px;">
                    <?php
                    // Get first day of month and total days
                    $first_day = date('w', strtotime($month_start)); // 0 (Sunday) to 6 (Saturday)
                    $total_days = date('t', strtotime($month_start));
                    
                    // Add empty cells for days before month starts
                    for ($i = 0; $i < $first_day; $i++) {
                        echo "<div style='aspect-ratio: 1; background: #f8fafc; border-radius: 8px;'></div>";
                    }
                    
                    // Add days of the month
                    for ($day = 1; $day <= $total_days; $day++) {
                        $date = sprintf('%s-%02d', $current_month, $day);
                        $status = isset($heatmap_data[$date]) ? $heatmap_data[$date] : 'None';
                        
                        // Determine colors based on status
                        $bg_color = '#f8fafc';
                        $text_color = '#94a3b8';
                        $border_color = '#e2e8f0';
                        $tooltip = 'No record';
                        
                        if ($status == 'Present') {
                            $bg_color = '#d1fae5';
                            $text_color = '#059669';
                            $border_color = '#6ee7b7';
                            $tooltip = 'Present';
                        } elseif ($status == 'Absent') {
                            $bg_color = '#fee2e2';
                            $text_color = '#dc2626';
                            $border_color = '#fca5a5';
                            $tooltip = 'Absent';
                        } elseif ($status == 'Late') {
                            $bg_color = '#fef3c7';
                            $text_color = '#d97706';
                            $border_color = '#fde68a';
                            $tooltip = 'Late';
                        } elseif ($status == 'Excused') {
                            $bg_color = '#e0e7ff';
                            $text_color = '#4f46e5';
                            $border_color = '#c7d2fe';
                            $tooltip = 'Excused';
                        }
                        
                        // Check if it's today
                        $is_today = ($date == date('Y-m-d'));
                        $today_style = $is_today ? "border: 2px solid #6366f1; font-weight: 800;" : "border: 1px solid $border_color;";
                        
                        echo "
                        <div style='aspect-ratio: 1; background: $bg_color; color: $text_color; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; $today_style cursor: pointer; transition: transform 0.2s;' 
                             title='$date: $tooltip'
                             onmouseover='this.style.transform=\"scale(1.05)\"'
                             onmouseout='this.style.transform=\"scale(1)\"'>
                            $day
                        </div>";
                    }
                    ?>
                </div>
            </div>
            
            <!-- Legend -->
            <div style="margin-top: 20px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; font-size: 12px; color: #64748b;">
                <span style="display: flex; align-items: center; gap: 5px;"><span style="width: 12px; height: 12px; background: #d1fae5; border: 1px solid #6ee7b7; border-radius: 3px;"></span> Present</span>
                <span style="display: flex; align-items: center; gap: 5px;"><span style="width: 12px; height: 12px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 3px;"></span> Absent</span>
                <span style="display: flex; align-items: center; gap: 5px;"><span style="width: 12px; height: 12px; background: #fef3c7; border: 1px solid #fde68a; border-radius: 3px;"></span> Late</span>
                <span style="display: flex; align-items: center; gap: 5px;"><span style="width: 12px; height: 12px; background: #e0e7ff; border: 1px solid #c7d2fe; border-radius: 3px;"></span> Excused</span>
                <span style="display: flex; align-items: center; gap: 5px;"><span style="width: 12px; height: 12px; background: white; border: 2px solid #6366f1; border-radius: 3px;"></span> Today</span>
            </div>
        </div>
    </div>

    <!-- Recent History List -->
    <div class="card-plain">
        <div class="card-header-styled">
            Recent History
        </div>
        <div style="max-height: 400px; overflow-y: auto;">
            <?php if (mysqli_num_rows($history_res) > 0): ?>
                <?php while ($h = mysqli_fetch_assoc($history_res)): ?>
                    <?php
                    $badge_bg = '#f1f5f9';
                    $badge_col = '#64748b';
                    if ($h['status'] == 'Present') {
                        $badge_bg = '#d1fae5';
                        $badge_col = '#059669';
                    } elseif ($h['status'] == 'Absent') {
                        $badge_bg = '#fee2e2';
                        $badge_col = '#dc2626';
                    } elseif ($h['status'] == 'Late') {
                        $badge_bg = '#fef3c7';
                        $badge_col = '#d97706';
                    } elseif ($h['status'] == 'Excused') {
                        $badge_bg = '#e0e7ff';
                        $badge_col = '#4f46e5';
                    }
                    ?>
                    <div class="history-item">
                        <span
                            style="font-weight: 500; color: #334155;"><?php echo date('M j, Y', strtotime($h['attendance_date'])); ?></span>
                        <span
                            style="background: <?php echo $badge_bg; ?>; color: <?php echo $badge_col; ?>; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                            <?php echo $h['status']; ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="padding: 20px; text-align: center; color: #94a3b8; font-style: italic;">
                    No attendance records found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>