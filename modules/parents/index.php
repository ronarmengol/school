<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['parent']); // Only parents

$parent_id = $_SESSION['user_id'];
$parent_name = $_SESSION['full_name'];
$school_name = get_setting('school_name', 'School Management System');

// Fetch children linked to this parent
$children_sql = "
    SELECT s.*, c.class_name, c.section_name
    FROM students s
    LEFT JOIN classes c ON s.current_class_id = c.class_id
    WHERE s.parent_id = ?
";
$stmt = mysqli_prepare($conn, $children_sql);
mysqli_stmt_bind_param($stmt, "i", $parent_id);
mysqli_stmt_execute($stmt);
$children_result = mysqli_stmt_get_result($stmt);
$children = mysqli_fetch_all($children_result, MYSQLI_ASSOC);

// Get selected child (default to first child)
$selected_child_id = $_GET['child_id'] ?? ($children[0]['student_id'] ?? null);
$selected_child = null;
foreach ($children as $child) {
    if ($child['student_id'] == $selected_child_id) {
        $selected_child = $child;
        break;
    }
}

// If we have a selected child, fetch their data
$attendance_summary = null;
$recent_grades = [];

if ($selected_child) {
    // Fetch attendance summary (current month)
    $att_sql = "SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_days
        FROM attendance 
        WHERE student_id = ? 
        AND MONTH(attendance_date) = MONTH(CURDATE())
        AND YEAR(attendance_date) = YEAR(CURDATE())";
    $att_stmt = mysqli_prepare($conn, $att_sql);
    mysqli_stmt_bind_param($att_stmt, "i", $selected_child_id);
    mysqli_stmt_execute($att_stmt);
    $attendance_summary = mysqli_fetch_assoc(mysqli_stmt_get_result($att_stmt));

    // Fetch school notes for this child (General class notes OR specifically for this child)
    $notes_sql = "
        SELECT n.*, u.full_name as created_by_name
        FROM school_notes n
        LEFT JOIN users u ON n.created_by = u.user_id
        WHERE n.related_class_id = ? 
        AND (n.related_student_id IS NULL OR n.related_student_id = 0 OR n.related_student_id = ?)
        AND n.status != 'Closed'
        ORDER BY FIELD(n.priority, 'Urgent', 'High', 'Medium', 'Low'), n.created_at DESC
        LIMIT 5
    ";
    $notes_stmt = mysqli_prepare($conn, $notes_sql);
    $child_class_id = $selected_child['current_class_id'];
    mysqli_stmt_bind_param($notes_stmt, "ii", $child_class_id, $selected_child_id);
    mysqli_stmt_execute($notes_stmt);
    $school_notes = mysqli_fetch_all(mysqli_stmt_get_result($notes_stmt), MYSQLI_ASSOC);
}

// Check for default password
$password_warning = false;
$chk_sql = "SELECT password_hash FROM users WHERE user_id = ?";
$chk_stmt = mysqli_prepare($conn, $chk_sql);
mysqli_stmt_bind_param($chk_stmt, "i", $parent_id);
mysqli_stmt_execute($chk_stmt);
$chk_res = mysqli_stmt_get_result($chk_stmt);
$chk_row = mysqli_fetch_assoc($chk_res);
if ($chk_row && $chk_row['password_hash'] === '123') {
    $password_warning = true;
}

$page_title = "Parent Portal";
include '../../includes/header.php';
?>

<style>
    /* Premium Parent Portal Styles */
    .parent-portal {
        background: #f8fafc;
        min-height: 100vh;
        padding-bottom: 40px;
    }

    .portal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .portal-header-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .welcome-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .welcome-text h1 {
        font-size: 32px;
        font-weight: 800;
        margin: 0 0 8px 0;
        letter-spacing: -0.5px;
    }

    .welcome-text p {
        font-size: 16px;
        margin: 0;
        opacity: 0.95;
    }

    .child-selector {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 12px 20px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        min-width: 250px;
    }

    .child-selector label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        opacity: 0.9;
    }

    .child-selector select {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: none;
        background: white;
        color: #1e293b;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
    }

    .portal-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .security-alert {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .security-alert-content {
        flex: 1;
    }

    .security-alert-title {
        font-weight: 700;
        color: #92400e;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .security-alert-text {
        color: #78350f;
        font-size: 14px;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
        border: 1px solid #e2e8f0;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
    }

    .stat-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stat-icon.attendance {
        background: #dbeafe;
        color: #1e40af;
    }

    .stat-icon.academic {
        background: #fef3c7;
        color: #b45309;
    }

    .stat-icon.fees {
        background: #dcfce7;
        color: #15803d;
    }

    .stat-icon.communication {
        background: #f3e8ff;
        color: #6b21a8;
    }

    .stat-title {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-value {
        font-size: 36px;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 8px;
        line-height: 1;
    }

    .stat-label {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 16px;
    }

    .stat-progress {
        height: 8px;
        background: #e2e8f0;
        border-radius: 99px;
        overflow: hidden;
        margin-bottom: 8px;
    }

    .stat-progress-bar {
        height: 100%;
        border-radius: 99px;
        transition: width 0.3s ease;
    }

    .stat-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 16px;
        border-top: 1px solid #f1f5f9;
    }

    .stat-footer-text {
        font-size: 13px;
        color: #94a3b8;
    }

    .content-section {
        background: white;
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #f1f5f9;
    }

    .section-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .child-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 16px;
        border: 2px solid #e2e8f0;
        transition: all 0.2s ease;
    }

    .child-card:hover {
        border-color: #667eea;
        background: white;
    }

    .child-avatar {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .child-info {
        flex: 1;
    }

    .child-name {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 4px 0;
    }

    .child-details {
        font-size: 14px;
        color: #64748b;
        margin: 0;
    }

    .child-actions {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 2px solid;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-primary-action {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .btn-primary-action:hover {
        background: #5568d3;
        border-color: #5568d3;
        transform: translateY(-2px);
    }

    .btn-secondary-action {
        background: white;
        color: #667eea;
        border-color: #e2e8f0;
    }

    .btn-secondary-action:hover {
        border-color: #667eea;
        background: #f8fafc;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }

    .empty-state-icon {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .empty-state-title {
        font-size: 18px;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 8px;
    }

    .empty-state-text {
        font-size: 14px;
        color: #94a3b8;
    }

    @media (max-width: 768px) {
        .portal-header {
            padding: 24px 0;
        }

        .welcome-section {
            flex-direction: column;
            align-items: flex-start;
        }

        .welcome-text h1 {
            font-size: 24px;
        }

        .child-selector {
            width: 100%;
        }

        .dashboard-grid {
            grid-template-columns: 1fr;
        }

        .child-card {
            flex-direction: column;
            text-align: center;
        }

        .child-actions {
            width: 100%;
            flex-direction: column;
        }

        .btn-action {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="parent-portal">
    <!-- Scrolling Marquee Banner for Special Messages -->
    <div id="marqueeContainer"
        style="display: none; background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%); backdrop-filter: blur(10px); color: #1e293b; padding: 12px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; position: fixed; top: 80px; left: 0; right: 0; z-index: 1000; transition: opacity 0.5s ease-in-out; border-bottom: 2px solid rgba(102, 126, 234, 0.3);">
        <div style="display: flex; align-items: center; padding: 0 20px; position: relative; height: 30px;">
            <div
                style="position: absolute; left: 20px; z-index: 2; background: white; padding: 6px; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#667eea" stroke-width="2"
                    style="animation: ring 2s ease-in-out infinite;">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
            </div>
            <div style="position: absolute; left: 0; right: 0; overflow: hidden;">
                <div id="marqueeContent"
                    style="display: inline-block; white-space: nowrap; animation: scroll 30s linear infinite; padding-left: 100%; font-weight: 600; font-size: 14px;">
                    <!-- Messages will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Spacer for fixed marquee -->
    <div id="marqueeSpacer" style="height: 0; transition: height 0.3s ease;"></div>

    <!-- Portal Header -->
    <div class="portal-header">
        <div class="portal-header-content">
            <div class="welcome-section">
                <div class="welcome-text">
                    <h1>Welcome, <?php echo htmlspecialchars(explode(' ', $parent_name)[0]); ?>! <span
                            style="font-weight: 400; opacity: 0.7; font-size: 0.7em; margin-left: 5px;"><?php if ($selected_child)
                                echo "(" . htmlspecialchars($selected_child['first_name'] . ' ' . $selected_child['last_name']) . ")"; ?></span>
                    </h1>
                    <p>Stay connected with your child's academic journey at
                        <?php echo htmlspecialchars($school_name); ?>
                    </p>
                </div>

                <?php if (count($children) > 1): ?>
                    <div class="child-selector">
                        <label>Select Child</label>
                        <select onchange="window.location.href='?child_id=' + this.value">
                            <?php foreach ($children as $child): ?>
                                <option value="<?php echo $child['student_id']; ?>" <?php echo ($child['student_id'] == $selected_child_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="portal-container">
        <!-- Security Alert -->
        <?php if ($password_warning): ?>
            <div class="security-alert">
                <div class="security-alert-content">
                    <div class="security-alert-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path
                                d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                            </path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        Security Action Required
                    </div>
                    <div class="security-alert-text">
                        You are using the default password. Please change it immediately to secure your account.
                    </div>
                </div>
                <a href="change_password.php" class="btn btn-warning" style="white-space: nowrap;">Change Password</a>
            </div>
        <?php endif; ?>

        <?php if ($selected_child): ?>
            <!-- Dashboard Overview Cards -->
            <div class="dashboard-grid">
                <!-- Attendance Card -->
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon attendance">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="stat-title">Attendance</div>
                    </div>
                    <div class="stat-value"><?php echo $attendance_summary['present_days'] ?? 0; ?></div>
                    <div class="stat-label">Days present this month</div>
                    <?php
                    $total = $attendance_summary['total_days'] ?? 0;
                    $present = $attendance_summary['present_days'] ?? 0;
                    $percentage = $total > 0 ? ($present / $total) * 100 : 0;
                    ?>
                    <div class="stat-progress">
                        <div class="stat-progress-bar" style="width: <?php echo $percentage; ?>%; background: #3b82f6;">
                        </div>
                    </div>
                    <div class="stat-footer">
                        <span class="stat-footer-text"><?php echo round($percentage); ?>% attendance rate</span>
                        <a href="../attendance/my_attendance.php?student_id=<?php echo $selected_child_id; ?>"
                            class="btn-action btn-secondary-action" style="font-size: 12px; padding: 4px 10px;">
                            View Details →
                        </a>
                    </div>
                </div>

                <!-- Academic Performance Card -->
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon academic">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                        </div>
                        <div class="stat-title">Academic</div>
                    </div>
                    <div class="stat-value"><?php echo htmlspecialchars($selected_child['class_name'] ?? 'N/A'); ?></div>
                    <div class="stat-label">Current class</div>
                    <div class="stat-footer">
                        <span class="stat-footer-text">View grades & reports</span>
                        <a href="../students/view.php?id=<?php echo $selected_child_id; ?>"
                            class="btn-action btn-secondary-action" style="font-size: 12px; padding: 4px 10px;">
                            View Records →
                        </a>
                    </div>
                </div>

                <!-- Communication Card -->
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon communication">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </div>
                        <div class="stat-title">Updates</div>
                    </div>
                    <div class="stat-value">0</div>
                    <div class="stat-label">New announcements</div>
                    <div class="stat-footer">
                        <span class="stat-footer-text">School notices</span>
                        <a href="#school-notes" class="btn-action btn-secondary-action"
                            style="font-size: 12px; padding: 4px 10px;">
                            View All →
                        </a>
                    </div>
                </div>
            </div>

            <!-- School Notes Section (Personalized) -->
            <div class="content-section" id="school-notes">
                <div class="section-header">
                    <div class="section-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <h2 class="section-title">Latest School Notes for
                        <?php echo htmlspecialchars($selected_child['first_name']); ?>
                    </h2>
                </div>

                <?php if (!empty($school_notes)): ?>
                    <div class="notes-list">
                        <?php foreach ($school_notes as $note): ?>
                            <?php
                            $priority_color = '#6366f1';
                            if ($note['priority'] == 'Urgent')
                                $priority_color = '#ef4444';
                            elseif ($note['priority'] == 'High')
                                $priority_color = '#f59e0b';
                            ?>
                            <div
                                style="padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 15px; position: relative;">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                    <div>
                                        <span
                                            style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: <?php echo $priority_color; ?>; padding: 2px 8px; border-radius: 4px; border: 1px solid <?php echo $priority_color; ?>; margin-bottom: 8px; display: inline-block;">
                                            <?php echo $note['priority']; ?>
                                        </span>
                                        <h3 style="margin: 0; font-size: 16px; color: #1e293b;">
                                            <?php echo htmlspecialchars($note['title']); ?>
                                        </h3>
                                    </div>
                                    <div style="font-size: 12px; color: #94a3b8; text-align: right;">
                                        <?php echo date('M d, Y', strtotime($note['created_at'])); ?><br>
                                        <span style="font-size: 11px;"><?php echo htmlspecialchars($note['category']); ?></span>
                                    </div>
                                </div>
                                <p style="margin: 0; font-size: 14px; color: #475569; line-height: 1.5;">
                                    <?php echo nl2br(htmlspecialchars($note['note_content'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 30px; color: #94a3b8;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                            style="margin-bottom: 12px; opacity: 0.5;">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <p style="font-size: 14px; margin: 0;">No active school notes for this student.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Children Section -->
            <div class="content-section">
                <div class="section-header">
                    <div class="section-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h2 class="section-title">My Children</h2>
                </div>

                <?php foreach ($children as $child): ?>
                    <div class="child-card">
                        <div class="child-avatar">
                            <?php
                            $initials = strtoupper(substr($child['first_name'], 0, 1) . substr($child['last_name'], 0, 1));
                            echo $initials;
                            ?>
                        </div>
                        <div class="child-info">
                            <h3 class="child-name">
                                <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                            </h3>
                            <p class="child-details">
                                <strong>Class:</strong> <?php echo htmlspecialchars($child['class_name'] ?? 'Not Assigned'); ?>
                                <span style="margin: 0 8px;">•</span>
                                <strong>Admission No:</strong> <?php echo htmlspecialchars($child['admission_number']); ?>
                            </p>
                        </div>
                        <div class="child-actions">
                            <a href="../students/view.php?id=<?php echo $child['student_id']; ?>"
                                class="btn-action btn-primary-action">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                View Profile
                            </a>
                            <a href="../attendance/my_attendance.php?student_id=<?php echo $child['student_id']; ?>"
                                class="btn-action btn-secondary-action">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                Attendance
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- School Announcements Section -->
            <div class="content-section" id="announcements">
                <div class="section-header">
                    <div class="section-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                    </div>
                    <h2 class="section-title">School Announcements & Reminders</h2>
                </div>

                <div id="announcementsList">
                    <div style="text-align: center; padding: 40px 20px; color: #94a3b8;">
                        <div
                            style="width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #667eea; border-radius: 50%; margin: 0 auto 16px; animation: spin 1s linear infinite;">
                        </div>
                        <div style="font-size: 14px;">Loading announcements...</div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- No Children State -->
            <div class="content-section">
                <div class="empty-state">
                    <div class="empty-state-icon">👨‍👩‍👧‍👦</div>
                    <div class="empty-state-title">No Children Records Found</div>
                    <div class="empty-state-text">
                        No student records are currently linked to your account.<br>
                        Please contact the school administration if this is an error.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    @keyframes ring {

        0%,
        100% {
            transform: rotate(0deg);
        }

        10%,
        30% {
            transform: rotate(-10deg);
        }

        20%,
        40% {
            transform: rotate(10deg);
        }

        50% {
            transform: rotate(0deg);
        }
    }

    @keyframes scroll {
        0% {
            transform: translateX(0);
        }

        100% {
            transform: translateX(-200%);
        }
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .announcement-item {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s ease;
    }

    .announcement-item:last-child {
        border-bottom: none;
    }

    .announcement-item:hover {
        background: #f8fafc;
    }

    .announcement-text {
        font-size: 15px;
        color: #334155;
        line-height: 1.6;
        margin-bottom: 8px;
    }

    .announcement-date {
        font-size: 12px;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 6px;
    }
</style>

<script>
    // Load school announcements
    function loadAnnouncements() {
        const formData = new FormData();
        formData.append('action', 'get_messages');

        fetch('calendar_api.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('announcementsList');

                if (data.success && data.messages && data.messages.length > 0) {
                    container.innerHTML = '';

                    data.messages.forEach(msg => {
                        const item = document.createElement('div');
                        item.className = 'announcement-item';
                        item.innerHTML = `
                    <div class="announcement-text">${msg.message}</div>
                    <div class="announcement-date">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        ${new Date(msg.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                    </div>
                `;
                        container.appendChild(item);
                    });

                    // Update marquee banner
                    updateMarquee(data.messages);
                } else {
                    container.innerHTML = `
                <div style="text-align: center; padding: 60px 20px; color: #94a3b8;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 16px; opacity: 0.5;">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                    <div style="font-size: 16px; font-weight: 600; color: #64748b; margin-bottom: 8px;">No Announcements</div>
                    <div style="font-size: 14px;">There are no school announcements at this time.</div>
                </div>
            `;
                    // Hide marquee if no messages
                    document.getElementById('marqueeContainer').style.display = 'none';
                }
            })
            .catch(err => {
                console.error('Error loading announcements:', err);
                document.getElementById('announcementsList').innerHTML = `
            <div style="text-align: center; padding: 40px 20px; color: #ef4444;">
                <div style="font-size: 14px;">Failed to load announcements.</div>
            </div>
        `;
            });
    }

    // Update marquee banner
    function updateMarquee(messages) {
        const marqueeContent = document.getElementById('marqueeContent');
        const marqueeContainer = document.getElementById('marqueeContainer');
        const marqueeSpacer = document.getElementById('marqueeSpacer');

        if (messages && messages.length > 0) {
            let currentIndex = 0;

            function showMessage() {
                const message = messages[currentIndex].message;
                marqueeContent.textContent = message;

                // Calculate animation duration
                const animationDuration = 25; // seconds
                marqueeContent.style.animation = `scroll ${animationDuration}s linear`;

                marqueeContainer.style.display = 'block';
                marqueeContainer.style.opacity = '1';
                marqueeSpacer.style.height = '54px'; // Make space for marquee

                // Hide after 12 seconds
                setTimeout(() => {
                    marqueeContainer.style.opacity = '0';
                    setTimeout(() => {
                        marqueeContainer.style.display = 'none';
                        marqueeSpacer.style.height = '0';
                    }, 500);
                }, 11500);
            }

            // Show first message immediately
            showMessage();

            // Show marquee every 1 minute (60000ms)
            setInterval(() => {
                // Rotate to next message if multiple exist
                if (messages.length > 1) {
                    currentIndex = (currentIndex + 1) % messages.length;
                }
                showMessage();
            }, 60000);
        } else {
            marqueeContainer.style.display = 'none';
            marqueeSpacer.style.height = '0';
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', () => {
        loadAnnouncements();
    });
</script>

<?php include '../../includes/footer.php'; ?>