<?php
$role = $_SESSION['role'] ?? 'guest';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <?php
        $logo = get_setting('school_logo');
        $logo_position = get_setting('logo_position', 'above');
        $has_logo = $logo && file_exists(__DIR__ . '/../uploads/' . $logo);

        // Logo above name
        if ($has_logo && $logo_position == 'above'):
            ?>
            <img src="<?php echo BASE_URL . 'uploads/' . $logo; ?>" alt="School Logo"
                style="max-width: 60px; max-height: 60px; margin-bottom: 10px; border-radius: 8px;">
        <?php endif; ?>

        <h3><?php echo get_setting('school_name', APP_NAME); ?></h3>
        <?php if ($motto = get_setting('school_motto')): ?>
            <div style="font-size: 12px; color: rgba(255,255,255,0.6); margin-top: 4px; font-style: italic;">
                <?php echo htmlspecialchars($motto); ?>
            </div>
        <?php endif; ?>

        <?php
        // Logo below name
        if ($has_logo && $logo_position == 'below'):
            ?>
            <img src="<?php echo BASE_URL . 'uploads/' . $logo; ?>" alt="School Logo"
                style="max-width: 60px; max-height: 60px; margin-top: 10px; border-radius: 8px;">
        <?php endif; ?>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>modules/dashboard/index.php">Dashboard</a></li>

            <?php if (in_array($role, ['super_admin', 'admin', 'teacher'])): ?>
                <?php if (in_array($role, ['super_admin', 'admin'])): ?>
                    <li><a href="<?php echo BASE_URL; ?>modules/academics/terms.php">Academic Years</a></li>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL; ?>modules/classes/index.php">Classes</a></li>
                <li><a href="<?php echo BASE_URL; ?>modules/students/index.php">Students</a></li>
                <?php if (in_array($role, ['super_admin', 'admin'])): ?>
                    <li><a href="<?php echo BASE_URL; ?>modules/students/promotion.php">Student Promotion</a></li>
                <?php endif; ?>
                <li id="nav-attendance"
                    style="<?php echo get_setting('enable_attendance', '1') == '0' ? 'display: none;' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>modules/attendance/index.php">Attendance</a>
                </li>
                <li><a href="<?php echo BASE_URL; ?>modules/exams/index.php">Exams & Results</a></li>
                <li id="nav-lesson-plans"
                    style="<?php echo get_setting('enable_lesson_plans', '0') == '0' ? 'display: none;' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>modules/academics/lesson_plans_list.php">Lesson Plans</a>
                </li>
                <li id="nav-library"
                    style="<?php echo get_setting('enable_library', '0') == '0' ? 'display: none;' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>modules/library/index.php">Library</a>
                </li>
                <li id="nav-assets"
                    style="<?php echo get_setting('enable_asset_management', '0') == '0' ? 'display: none;' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>modules/assets/index.php">Asset Management</a>
                </li>
                <li id="nav-parent-password-admin"
                    style="<?php echo get_setting('enable_parent_password', '0') == '0' ? 'display: none;' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>modules/parents/password_management.php">Parent Passwords</a>
                </li>
            <?php endif; ?>

            <?php if (in_array($role, ['super_admin', 'admin', 'accountant'])): ?>
                <li><a href="<?php echo BASE_URL; ?>modules/finance/index.php">Fees & Finance</a></li>
            <?php endif; ?>

            <?php if (in_array($role, ['super_admin', 'admin'])): ?>
                <li><a href="<?php echo BASE_URL; ?>modules/staff/index.php">Staff Management</a></li>
                <li><a href="<?php echo BASE_URL; ?>modules/reports/index.php">Reports</a></li>
                <li><a href="<?php echo BASE_URL; ?>modules/settings/index.php">Settings</a></li>
            <?php endif; ?>

            <?php if (in_array($role, ['parent', 'student'])): ?>
                <li><a href="<?php echo BASE_URL; ?>modules/exams/results.php">My Results</a></li>
                <?php if (get_setting('enable_attendance', '1') == '1'): ?>
                    <li><a href="<?php echo BASE_URL; ?>modules/attendance/my_attendance.php">My Attendance</a></li>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL; ?>modules/finance/my_transactions.php">My Transactions</a></li>
                <?php if ($role == 'parent'): ?>
                    <li id="nav-parent-password"
                        style="<?php echo get_setting('enable_parent_password', '0') == '0' ? 'display: none;' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>modules/parents/change_password.php">Change Password</a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </nav>
</aside>