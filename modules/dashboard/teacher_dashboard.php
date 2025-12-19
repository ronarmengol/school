<?php
// Teacher Stats
$teacher_user_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['full_name'] ?? $_SESSION['username'];

// Get Teacher ID and Info
$sql_teacher = "SELECT t.teacher_id, t.specialization, u.full_name 
                FROM teachers t 
                JOIN users u ON t.user_id = u.user_id 
                WHERE t.user_id = ?";
$stmt = mysqli_prepare($conn, $sql_teacher);
mysqli_stmt_bind_param($stmt, "i", $teacher_user_id);
mysqli_stmt_execute($stmt);
$teacher_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$teacher_id = $teacher_data['teacher_id'] ?? 0;
$specialization = $teacher_data['specialization'] ?? 'General';

// Count All Classes (since there's no teacher-class relationship)
$count_classes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM classes"))['count'] ?? 0;

// Count All Active Students
$count_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM students WHERE status = 'Active'"))['count'] ?? 0;

// Today's Attendance (all students)
$today = date('Y-m-d');
$sql_attendance = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present
    FROM attendance 
    WHERE attendance_date = ?";
$stmt_att = mysqli_prepare($conn, $sql_attendance);
mysqli_stmt_bind_param($stmt_att, "s", $today);
mysqli_stmt_execute($stmt_att);
$att_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_att));
$attendance_percent = $att_data['total'] > 0 ? round(($att_data['present'] / $att_data['total']) * 100) : 0;
$attendance_marked = $att_data['total'] ?? 0;

// Pending Marks (placeholder - marks table doesn't exist yet)
$pending_marks = 0;

// All Classes List (showing all classes since no teacher assignment)
$sql_all_classes = "SELECT c.class_id, c.class_name, c.section_name, 
                   COUNT(s.student_id) as student_count
                   FROM classes c
                   LEFT JOIN students s ON c.class_id = s.current_class_id AND s.status = 'Active'
                   GROUP BY c.class_id
                   ORDER BY c.class_name, c.section_name
                   LIMIT 5";
$my_classes_result = mysqli_query($conn, $sql_all_classes);

// Recent Activity (last 5 attendance records)
$sql_activity = "SELECT 
    a.attendance_date,
    c.class_name,
    c.section_name,
    COUNT(*) as students_marked
    FROM attendance a
    JOIN students s ON a.student_id = s.student_id
    JOIN classes c ON s.current_class_id = c.class_id
    GROUP BY a.attendance_date, c.class_id
    ORDER BY a.attendance_date DESC
    LIMIT 5";
$activity_result = mysqli_query($conn, $sql_activity);
?>

<style>
    /* Premium Teacher Dashboard Styling */

    /* Dashboard Container */
    .dashboard-container {
        max-width: 1600px;
        margin: 0 auto;
    }

    /* Welcome Header */
    .dashboard-welcome {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        padding: 32px;
        border-radius: 16px;
        margin-bottom: 32px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        color: white;
    }

    .dashboard-welcome h1 {
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 8px 0;
        letter-spacing: -0.02em;
    }

    .dashboard-welcome p {
        font-size: 15px;
        margin: 0;
        opacity: 0.95;
        font-weight: 500;
    }

    .welcome-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.2);
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        margin-top: 12px;
        backdrop-filter: blur(10px);
    }

    /* Stats Grid - Premium */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: white;
        padding: 28px;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--card-color, #10b981) 0%, var(--card-color-light, #34d399) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .stat-card:hover::before {
        opacity: 1;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1), 0 4px 8px rgba(0, 0, 0, 0.06);
        border-color: #cbd5e1;
    }

    .stat-info {
        flex: 1;
    }

    .stat-info h3 {
        font-size: 13px;
        color: #64748b;
        margin: 0 0 12px 0;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .stat-info .stat-number {
        font-size: 36px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        line-height: 1;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.02em;
    }

    .stat-icon {
        width: 64px;
        height: 64px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        flex-shrink: 0;
        transition: transform 0.3s ease;
    }

    .stat-card:hover .stat-icon {
        transform: scale(1.05);
    }

    .icon-green {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        color: #10b981;
        --card-color: #10b981;
        --card-color-light: #34d399;
    }

    .icon-blue {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #3b82f6;
        --card-color: #3b82f6;
        --card-color-light: #60a5fa;
    }

    .icon-purple {
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        color: #6366f1;
        --card-color: #6366f1;
        --card-color-light: #818cf8;
    }

    .icon-orange {
        background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%);
        color: #f97316;
        --card-color: #f97316;
        --card-color-light: #fb923c;
    }

    /* Dashboard Cards - Professional */
    .dashboard-card {
        background: white;
        padding: 32px;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }

    .dashboard-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .dashboard-card h3 {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 24px 0;
        letter-spacing: -0.01em;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .dashboard-card h3::before {
        content: '';
        width: 4px;
        height: 24px;
        background: linear-gradient(180deg, #10b981 0%, #059669 100%);
        border-radius: 2px;
    }

    /* Quick Actions Grid */
    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .action-card {
        background: white;
        padding: 24px;
        border-radius: 14px;
        border: 2px solid #e2e8f0;
        transition: all 0.2s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .action-card:hover {
        border-color: #10b981;
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.15);
        transform: translateY(-2px);
    }

    .action-icon {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .action-content h4 {
        margin: 0 0 4px 0;
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
    }

    .action-content p {
        margin: 0;
        font-size: 13px;
        color: #64748b;
    }

    /* Tables */
    .data-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .data-table thead th {
        text-align: left;
        font-size: 12px;
        color: #64748b;
        font-weight: 700;
        padding: 14px 16px;
        border-bottom: 2px solid #e2e8f0;
        background: #f8fafc;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .data-table thead th:first-child {
        border-top-left-radius: 10px;
    }

    .data-table thead th:last-child {
        border-top-right-radius: 10px;
    }

    .data-table tbody tr {
        transition: all 0.2s ease;
        border-bottom: 1px solid #f1f5f9;
    }

    .data-table tbody tr:hover {
        background: #f8fafc;
    }

    .data-table tbody tr:last-child {
        border-bottom: none;
    }

    .data-table tbody td {
        padding: 16px;
        font-size: 14px;
        color: #475569;
        font-weight: 500;
        vertical-align: middle;
    }

    .data-table tbody td:first-child {
        font-weight: 600;
        color: #1e293b;
    }

    /* Two Column Layout */
    .dashboard-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 24px;
        margin-bottom: 24px;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 48px 24px;
        color: #94a3b8;
    }

    .empty-state svg {
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .empty-state-title {
        font-size: 16px;
        font-weight: 600;
        color: #64748b;
        margin: 0 0 8px 0;
    }

    .empty-state-text {
        font-size: 14px;
        color: #94a3b8;
        margin: 0;
    }

    /* Badges */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-success {
        background: #d1fae5;
        color: #059669;
    }

    .badge-info {
        background: #dbeafe;
        color: #0284c7;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .dashboard-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .dashboard-welcome {
            padding: 24px;
        }

        .dashboard-welcome h1 {
            font-size: 22px;
        }

        .dashboard-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .quick-actions-grid {
            grid-template-columns: 1fr;
        }

        .stat-card {
            padding: 20px;
        }

        .stat-info .stat-number {
            font-size: 28px;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            font-size: 22px;
        }

        .dashboard-card {
            padding: 20px;
        }
    }
</style>

<div class="dashboard-container">
    <!-- Welcome Header -->
    <div class="dashboard-welcome">
        <h1>Welcome back, <?php echo htmlspecialchars($teacher_name); ?>!</h1>
        <p><?php echo date('l, F j, Y'); ?> • Teacher Dashboard</p>
        <span class="welcome-badge">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 14l9-5-9-5-9 5 9 5z"></path>
                <path
                    d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z">
                </path>
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222">
                </path>
            </svg>
            <?php echo htmlspecialchars($specialization); ?>
        </span>
    </div>

    <!-- Primary Stats Grid -->
    <div class="dashboard-grid">
        <!-- School Classes Card -->
        <div class="stat-card">
            <div class="stat-info">
                <h3>School Classes</h3>
                <p class="stat-number"><?php echo number_format($count_classes); ?></p>
            </div>
            <div class="stat-icon icon-green">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
        </div>

        <!-- School Students Card -->
        <div class="stat-card">
            <div class="stat-info">
                <h3>School Students</h3>
                <p class="stat-number"><?php echo number_format($count_students); ?></p>
            </div>
            <div class="stat-icon icon-blue">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
        </div>

        <!-- Today's Attendance Card -->
        <div class="stat-card">
            <div class="stat-info">
                <h3>Today's Attendance</h3>
                <p class="stat-number"
                    style="color: <?php echo $attendance_percent >= 90 ? '#10b981' : ($attendance_percent >= 75 ? '#f59e0b' : '#ef4444'); ?>;">
                    <?php echo $attendance_percent; ?>%
                </p>
            </div>
            <div class="stat-icon icon-purple">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
        </div>

        <!-- Pending Marks Card -->
        <div class="stat-card">
            <div class="stat-info">
                <h3>Pending Marks</h3>
                <p class="stat-number" style="color: <?php echo $pending_marks > 0 ? '#f59e0b' : '#10b981'; ?>;">
                    <?php echo number_format($pending_marks); ?>
                </p>
            </div>
            <div class="stat-icon icon-orange">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions-grid">
        <a href="<?php echo BASE_URL; ?>modules/attendance/index.php" class="action-card">
            <div class="action-icon"
                style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #10b981;">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
            <div class="action-content">
                <h4>Mark Attendance</h4>
                <p>Record student attendance for today</p>
            </div>
        </a>

        <a href="<?php echo BASE_URL; ?>modules/exams/enter_marks.php" class="action-card">
            <div class="action-icon"
                style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #3b82f6;">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div class="action-content">
                <h4>Enter Marks</h4>
                <p>Input exam scores and grades</p>
            </div>
        </a>

        <a href="<?php echo BASE_URL; ?>modules/students/index.php" class="action-card">
            <div class="action-icon"
                style="background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #6366f1;">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div class="action-content">
                <h4>View Students</h4>
                <p>Browse students in your classes</p>
            </div>
        </a>
    </div>

    <!-- Data Tables Row -->
    <div class="dashboard-row">
        <!-- School Classes List -->
        <div class="dashboard-card">
            <h3>School Classes</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Section</th>
                        <th style="text-align: right;">Students</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($my_classes_result) > 0): ?>
                        <?php while ($class = mysqli_fetch_assoc($my_classes_result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($class['section_name'] ?? 'N/A'); ?></td>
                                <td style="text-align: right; font-weight: 700; color: #3b82f6;">
                                    <?php echo number_format($class['student_count']); ?>
                                </td>
                                <td style="text-align: center;">
                                    <a href="<?php echo BASE_URL; ?>modules/students/index.php?class_id=<?php echo $class['class_id']; ?>"
                                        style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 12px; border: 1.5px solid #10b981; background: transparent; color: #10b981; text-decoration: none; transition: all 0.2s ease;">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <div class="empty-state-title">No Classes Assigned</div>
                                <div class="empty-state-text">Contact admin to assign classes to you</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Activity -->
        <div class="dashboard-card">
            <h3>Recent Activity</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Class</th>
                        <th style="text-align: right;">Students Marked</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($activity_result) > 0): ?>
                        <?php while ($activity = mysqli_fetch_assoc($activity_result)): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($activity['attendance_date'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($activity['class_name'] . ' ' . $activity['section_name']); ?>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #10b981;">
                                    <?php echo number_format($activity['students_marked']); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="empty-state">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                                <div class="empty-state-title">No Recent Activity</div>
                                <div class="empty-state-text">Your attendance records will appear here</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>