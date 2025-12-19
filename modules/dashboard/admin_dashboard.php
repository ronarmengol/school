<?php
// Admin Stats

// Count Students
$sql_students = "SELECT COUNT(*) as count FROM students WHERE status='Active'";
$res_students = mysqli_query($conn, $sql_students);
$count_students = mysqli_fetch_assoc($res_students)['count'];

// Count Teachers
$sql_teachers = "SELECT COUNT(*) as count FROM teachers";
$res_teachers = mysqli_query($conn, $sql_teachers);
$count_teachers = mysqli_fetch_assoc($res_teachers)['count'];

// Count Classes
$sql_classes = "SELECT COUNT(*) as count FROM classes";
$res_classes = mysqli_query($conn, $sql_classes);
$count_classes = mysqli_fetch_assoc($res_classes)['count'];

// Calculate Attendance Percentage (Today)
$today = date('Y-m-d');
$sql_attendance = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present
    FROM attendance 
    WHERE attendance_date = '$today'";
$res_attendance = mysqli_query($conn, $sql_attendance);
$att_data = mysqli_fetch_assoc($res_attendance);
$attendance_percent = $att_data['total'] > 0 ? round(($att_data['present'] / $att_data['total']) * 100) : 0;

// Get Recent Activities (last 5)
$sql_activity = "SELECT 
    'Student Admission' as activity,
    CONCAT(first_name, ' ', last_name) as user_name,
    enrollment_date as date,
    'Completed' as status
    FROM students 
    ORDER BY student_id DESC 
    LIMIT 5";
$res_activity = mysqli_query($conn, $sql_activity);

$currency_symbol = get_setting('currency_symbol', '$');
?>

<style>
    /* Premium Dashboard Styling */

    /* Dashboard Container */
    .dashboard-container {
        max-width: 1600px;
        margin: 0 auto;
    }

    /* Welcome Header */
    .dashboard-welcome {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        padding: 32px;
        border-radius: 16px;
        margin-bottom: 32px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
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
        opacity: 0.9;
        font-weight: 500;
    }

    /* Stats Grid - Premium */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
        background: linear-gradient(90deg, var(--card-color, #3b82f6) 0%, var(--card-color-light, #60a5fa) 100%);
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

    .stat-info .stat-change {
        font-size: 13px;
        margin-top: 8px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .stat-change.positive {
        color: #10b981;
    }

    .stat-change.negative {
        color: #ef4444;
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

    .icon-blue {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #3b82f6;
        --card-color: #3b82f6;
        --card-color-light: #60a5fa;
    }

    .icon-green {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        color: #10b981;
        --card-color: #10b981;
        --card-color-light: #34d399;
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
        background: linear-gradient(180deg, #3498db 0%, #2c3e50 100%);
        border-radius: 2px;
    }

    /* Premium Tables */
    .activity-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .activity-table thead th {
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

    .activity-table thead th:first-child {
        border-top-left-radius: 10px;
    }

    .activity-table thead th:last-child {
        border-top-right-radius: 10px;
    }

    .activity-table tbody tr {
        transition: all 0.2s ease;
        border-bottom: 1px solid #f1f5f9;
    }

    .activity-table tbody tr:hover {
        background: #f8fafc;
    }

    .activity-table tbody tr:last-child {
        border-bottom: none;
    }

    .activity-table tbody td {
        padding: 16px;
        font-size: 14px;
        color: #475569;
        font-weight: 500;
        vertical-align: middle;
    }

    .activity-table tbody td:first-child {
        font-weight: 600;
        color: #1e293b;
    }

    /* Status Badges - Refined */
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: -0.01em;
    }

    .status-completed {
        background: #d1fae5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }

    .status-pending {
        background: #fef3c7;
        color: #d97706;
        border: 1px solid #fde68a;
    }

    /* Chart Container */
    .chart-container {
        position: relative;
        height: 280px;
        margin-top: 20px;
    }

    /* Two Column Layout */
    .dashboard-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 24px;
        margin-bottom: 24px;
    }

    /* Action Buttons - Polished */
    .btn-sm {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 12px;
        border: 1.5px solid;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-primary {
        background: transparent;
        color: #3b82f6;
        border-color: #3b82f6;
    }

    .btn-primary:hover {
        background: #3b82f6;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
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

    /* Loading State */
    .loading-shimmer {
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 8px;
        height: 20px;
    }

    @keyframes shimmer {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
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

        .activity-table thead th,
        .activity-table tbody td {
            padding: 12px;
            font-size: 13px;
        }
    }
</style>

<div class="dashboard-container">
    <!-- Welcome Header -->
    <div class="dashboard-welcome">
        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</h1>
        <p><?php echo date('l, F j, Y'); ?> • School Management Dashboard</p>
    </div>

    <!-- Primary Stats Grid -->
    <div class="dashboard-grid">
        <!-- Students Card -->
        <div class="stat-card">
            <div class="stat-info">
                <h3>Active Students</h3>
                <p class="stat-number"><?php echo number_format($count_students); ?></p>
            </div>
            <div class="stat-icon icon-blue">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
        </div>

        <!-- Teachers Card -->
        <div class="stat-card">
            <div class="stat-info">
                <h3>Teaching Staff</h3>
                <p class="stat-number"><?php echo number_format($count_teachers); ?></p>
            </div>
            <div class="stat-icon icon-green">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
        </div>

        <!-- Classes Card -->
        <div class="stat-card">
            <div class="stat-info">
                <h3>Total Classes</h3>
                <p class="stat-number"><?php echo number_format($count_classes); ?></p>
            </div>
            <div class="stat-icon icon-purple">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
        </div>

        <!-- Attendance Card -->
        <div class="stat-card">
            <div class="stat-info">
                <h3>Today's Attendance</h3>
                <p class="stat-number"
                    style="color: <?php echo $attendance_percent >= 90 ? '#10b981' : ($attendance_percent >= 75 ? '#f59e0b' : '#ef4444'); ?>;">
                    <?php echo $attendance_percent; ?>%
                </p>
            </div>
            <div class="stat-icon icon-orange">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Financial Stats Grid -->
    <div class="dashboard-grid">
        <!-- Revenue MTD Card -->
        <div class="stat-card">
            <div class="stat-info">
                <h3>Revenue (MTD)</h3>
                <p class="stat-number" id="revenue-mtd">
                    <span class="loading-shimmer" style="display: inline-block; width: 120px;"></span>
                </p>
            </div>
            <div class="stat-icon icon-blue">
                <span
                    style="font-size: 32px; font-weight: 700;"><?php echo htmlspecialchars($currency_symbol); ?></span>
            </div>
        </div>

        <!-- Fee Collection Rate Card -->
        <div class="stat-card">
            <div class="stat-info">
                <h3>Collection Rate</h3>
                <p class="stat-number" id="collection-rate">
                    <span class="loading-shimmer" style="display: inline-block; width: 80px;"></span>
                </p>
            </div>
            <div class="stat-icon icon-green">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Payment Status Grid -->
    <div class="dashboard-grid">
        <!-- Unpaid Invoices Card -->
        <div class="stat-card">
            <div class="stat-info">
                <h3>Unpaid Invoices</h3>
                <p class="stat-number" id="unpaid-count" style="color: #ef4444;">
                    <span class="loading-shimmer" style="display: inline-block; width: 60px;"></span>
                </p>
                <div class="stat-change" style="color: #64748b; font-size: 12px; margin-top: 8px;">
                    Students with zero payments
                </div>
            </div>
            <div class="stat-icon"
                style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #ef4444;">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Partial Payments Card -->
        <div class="stat-card">
            <div class="stat-info">
                <h3>Partial Payments</h3>
                <p class="stat-number" id="partial-count" style="color: #f59e0b;">
                    <span class="loading-shimmer" style="display: inline-block; width: 60px;"></span>
                </p>
                <div class="stat-change" style="color: #64748b; font-size: 12px; margin-top: 8px;">
                    Students with incomplete payments
                </div>
            </div>
            <div class="stat-icon"
                style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #f59e0b;">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- School Notes Section -->
    <div style="margin-bottom: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin: 0;">School Notes</h2>
                <p style="color: #64748b; margin: 4px 0 0 0; font-size: 14px;">Recent notes from teachers and staff</p>
            </div>
            <select id="notes-class-filter" class="form-control" style="width: 250px;" onchange="loadNotes()">
                <option value="">All Classes</option>
                <?php
                $classes_sql = "SELECT class_id, class_name, section_name FROM classes ORDER BY class_name";
                $classes_result = mysqli_query($conn, $classes_sql);
                while ($class = mysqli_fetch_assoc($classes_result)) {
                    echo "<option value='{$class['class_id']}'>{$class['class_name']} {$class['section_name']}</option>";
                }
                ?>
            </select>
        </div>

        <!-- Notes Stats Grid -->
        <div class="dashboard-grid" style="margin-bottom: 24px;">
            <!-- Urgent Notes Card -->
            <div class="stat-card" style="cursor: pointer;"
                onclick="openNotesPopover('priority', 'Urgent', 'Urgent Notes')">
                <div class="stat-info">
                    <h3>Urgent Notes</h3>
                    <p class="stat-number" id="urgent-notes-count" style="color: #ef4444;">
                        <span class="loading-shimmer" style="display: inline-block; width: 40px;"></span>
                    </p>
                    <div class="stat-change" style="color: #64748b; font-size: 12px; margin-top: 8px;">
                        Requires immediate attention
                    </div>
                </div>
                <div class="stat-icon"
                    style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #ef4444;">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>

            <!-- High Priority Notes Card -->
            <div class="stat-card" style="cursor: pointer;"
                onclick="openNotesPopover('priority', 'High', 'High Priority Notes')">
                <div class="stat-info">
                    <h3>High Priority</h3>
                    <p class="stat-number" id="high-notes-count" style="color: #f59e0b;">
                        <span class="loading-shimmer" style="display: inline-block; width: 40px;"></span>
                    </p>
                    <div class="stat-change" style="color: #64748b; font-size: 12px; margin-top: 8px;">
                        Important issues
                    </div>
                </div>
                <div class="stat-icon"
                    style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #f59e0b;">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <!-- Open Notes Card -->
            <div class="stat-card" style="cursor: pointer;" onclick="openNotesPopover('status', 'Open', 'Open Notes')">
                <div class="stat-info">
                    <h3>Open Notes</h3>
                    <p class="stat-number" id="open-notes-count" style="color: #3b82f6;">
                        <span class="loading-shimmer" style="display: inline-block; width: 40px;"></span>
                    </p>
                    <div class="stat-change" style="color: #64748b; font-size: 12px; margin-top: 8px;">
                        Pending action
                    </div>
                </div>
                <div class="stat-icon"
                    style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #3b82f6;">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>

            <!-- Total Notes Card -->
            <div class="stat-card" style="cursor: pointer;" onclick="openNotesPopover('all', '', 'All Active Notes')">
                <div class="stat-info">
                    <h3>Total Notes</h3>
                    <p class="stat-number" id="total-notes-count" style="color: #6366f1;">
                        <span class="loading-shimmer" style="display: inline-block; width: 40px;"></span>
                    </p>
                    <div class="stat-change" style="color: #64748b; font-size: 12px; margin-top: 8px;">
                        All active notes
                    </div>
                </div>
                <div class="stat-icon"
                    style="background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #6366f1;">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
            </div>

            <!-- Archived Notes Card -->
            <div class="stat-card" style="cursor: pointer;"
                onclick="window.location.href='../attendance/archived_notes.php'">
                <div class="stat-info">
                    <h3>Archived Notes</h3>
                    <p class="stat-number" id="archived-notes-count" style="color: #64748b;">
                        <span class="loading-shimmer" style="display: inline-block; width: 40px;"></span>
                    </p>
                    <div class="stat-change" style="color: #64748b; font-size: 12px; margin-top: 8px;">
                        View history
                    </div>
                </div>
                <div class="stat-icon"
                    style="background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); color: #64748b;">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Notes List -->
        <div class="dashboard-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Recent Notes</h3>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn-sm btn-primary" onclick="loadNotes()" title="Refresh">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0118.8-4.3M22 12.5a10 10 0 01-18.8 4.2" />
                        </svg>
                    </button>
                </div>
            </div>
            <div id="notes-list-container">
                <div style="text-align: center; padding: 40px; color: #94a3b8;">
                    <div class="loading-shimmer" style="width: 100%; height: 20px; margin-bottom: 12px;"></div>
                    <div class="loading-shimmer" style="width: 80%; height: 20px; margin: 0 auto;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="dashboard-row">
        <!-- Income Chart -->
        <div class="dashboard-card">
            <h3>Income Trends (Last 6 Months)</h3>
            <div class="chart-container">
                <canvas id="financeChart"></canvas>
            </div>
        </div>

        <!-- Enrollment Trends Chart -->
        <div class="dashboard-card">
            <h3>Enrollment Growth (Last 5 Years)</h3>
            <div class="chart-container">
                <canvas id="enrollmentChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Data Tables Row -->
    <div class="dashboard-row">
        <!-- Top Debtors -->
        <div class="dashboard-card">
            <h3>Top 5 Outstanding Balances</h3>
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Class</th>
                        <th style="text-align: right;">Balance</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody id="debtors-list">
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 32px;">
                            <div class="loading-shimmer" style="width: 100%; height: 20px; margin-bottom: 12px;"></div>
                            <div class="loading-shimmer" style="width: 80%; height: 20px; margin: 0 auto;"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Recent Activity -->
        <div class="dashboard-card">
            <h3>Recent Activity</h3>
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>Activity</th>
                        <th>User</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res_activity) > 0): ?>
                        <?php while ($act = mysqli_fetch_assoc($res_activity)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($act['activity']); ?></td>
                                <td><?php echo htmlspecialchars($act['user_name']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($act['date'])); ?></td>
                                <td><span class="status-badge status-completed"><?php echo $act['status']; ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <div class="empty-state-title">No Recent Activity</div>
                                <div class="empty-state-text">Activity will appear here as it happens</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Notes Detail Modal -->
<div id="notes-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title">Notes</h2>
            <button class="close-modal" onclick="closeNotesModal()">&times;</button>
        </div>
        <div id="modal-body" class="modal-body">
            <!-- Notes will be loaded here -->
        </div>
    </div>
</div>

<style>
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(8px);
        padding: 20px;
    }

    .modal-content {
        background: white;
        width: 100%;
        max-width: 850px;
        max-height: 90vh;
        border-radius: 20px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        animation: modalSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        overflow: hidden;
    }

    @keyframes modalSlideUp {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.98);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .modal-header {
        padding: 24px 32px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fff;
    }

    .modal-header h2 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    .close-modal {
        background: #f1f5f9;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        font-size: 24px;
        color: #64748b;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .close-modal:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .modal-body {
        padding: 32px;
        overflow-y: auto;
        background: #f8fafc;
        flex: 1;
    }

    .modal-note-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        position: relative;
    }

    .modal-note-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .modal-note-card:last-child {
        margin-bottom: 0;
    }

    .btn-archive {
        background: #fee2e2;
        color: #ef4444;
        border: 1px solid #fecaca;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .btn-archive:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
    }

    .note-content-full {
        color: #334155;
        line-height: 1.6;
        font-size: 15px;
        margin: 16px 0;
        white-space: pre-wrap;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Brand Colors
        const colors = {
            primary: '#3b82f6',
            secondary: '#3498db',
            success: '#10b981',
            danger: '#ef4444',
            warning: '#f59e0b',
            info: '#6366f1',
            dark: '#2c3e50'
        };

        // Currency from PHP
        const currencySymbol = "<?php echo $currency_symbol; ?>";

        // Chart.js Global Configuration
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.font.size = 13;
        Chart.defaults.color = '#64748b';

        // 1. Financial Chart
        fetch('get_analytics_data.php?action=get_financials')
            .then(response => response.json())
            .then(data => {
                if (data.labels) {
                    const ctx = document.getElementById('financeChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Income',
                                data: data.income,
                                backgroundColor: colors.success,
                                borderRadius: 8,
                                borderSkipped: false
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    padding: 12,
                                    borderRadius: 8,
                                    titleFont: { size: 13, weight: 600 },
                                    bodyFont: { size: 14 },
                                    callbacks: {
                                        label: function (context) {
                                            return 'Income: ' + currencySymbol + context.parsed.y.toLocaleString();
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: '#f1f5f9',
                                        drawBorder: false
                                    },
                                    ticks: {
                                        callback: function (value) {
                                            return currencySymbol + value.toLocaleString();
                                        }
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false,
                                        drawBorder: false
                                    }
                                }
                            }
                        }
                    });
                }
            });

        // 2. Enrollment Chart
        fetch('get_analytics_data.php?action=get_enrollment')
            .then(response => response.json())
            .then(data => {
                if (data.labels) {
                    const ctx = document.getElementById('enrollmentChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Total Students',
                                data: data.data,
                                borderColor: colors.primary,
                                backgroundColor: 'rgba(59, 130, 246, 0.08)',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 3,
                                pointRadius: 5,
                                pointBackgroundColor: colors.primary,
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointHoverRadius: 7
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    padding: 12,
                                    borderRadius: 8,
                                    titleFont: { size: 13, weight: 600 },
                                    bodyFont: { size: 14 }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: '#f1f5f9',
                                        drawBorder: false
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false,
                                        drawBorder: false
                                    }
                                }
                            }
                        }
                    });
                }
            });

        // 3. Debtors Table
        fetch('get_analytics_data.php?action=get_debtors')
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('debtors-list');
                tbody.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(student => {
                        const row = `
                        <tr>
                            <td>${student.first_name} ${student.last_name}</td>
                            <td>${student.class_name || 'N/A'}</td>
                            <td style="text-align: right; color: #ef4444; font-weight: 700; font-variant-numeric: tabular-nums;">${currencySymbol}${parseFloat(student.balance).toFixed(2)}</td>
                            <td style="text-align: center;">
                                <a href="../finance/payments.php?search=${encodeURIComponent(student.first_name)}" class="btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                    `;
                        tbody.innerHTML += row;
                    });
                } else {
                    tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="empty-state">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="empty-state-title">All Clear!</div>
                            <div class="empty-state-text">No outstanding balances found</div>
                        </td>
                    </tr>
                `;
                }
            });

        // 4. KPI Cards
        fetch('get_analytics_data.php?action=get_kpi')
            .then(response => response.json())
            .then(data => {
                if (data.revenue_mtd !== undefined) {
                    // Format revenue in thousands (K)
                    const revenueInK = (parseFloat(data.revenue_mtd) / 1000).toFixed(1);
                    document.getElementById('revenue-mtd').textContent = revenueInK + 'K';
                    document.getElementById('collection-rate').textContent = data.collection_rate + '%';

                    // Add color based on rate
                    const rateEl = document.getElementById('collection-rate');
                    if (data.collection_rate >= 90) rateEl.style.color = '#10b981';
                    else if (data.collection_rate >= 70) rateEl.style.color = '#f59e0b';
                    else rateEl.style.color = '#ef4444';

                    // Update payment status counts
                    if (data.unpaid_count !== undefined) {
                        document.getElementById('unpaid-count').textContent = data.unpaid_count;
                    }
                    if (data.partial_count !== undefined) {
                        document.getElementById('partial-count').textContent = data.partial_count;
                    }
                }
            });

        // 5. Load School Notes
        loadNotes();
    });

    function openNotesPopover(type, value, title) {
        const modal = document.getElementById('notes-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalBody = document.getElementById('modal-body');

        modalTitle.textContent = title;
        modalBody.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="loading-shimmer" style="width: 100%; height: 20px; margin-bottom: 12px;"></div>
                <div class="loading-shimmer" style="width: 80%; height: 20px; margin: 0 auto;"></div>
            </div>
        `;
        modal.style.display = 'flex';

        const params = new URLSearchParams();
        if (type === 'priority') params.append('priority', value);
        if (type === 'status') params.append('status', value);
        if (type === 'all') params.append('status', 'all');
        if (type === 'id') params.append('note_id', value);

        const classId = document.getElementById('notes-class-filter').value;
        if (classId) params.append('class_id', classId);

        fetch(`../attendance/notes_api.php?action=get_notes&${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.notes.length > 0) {
                    let html = '';
                    data.notes.forEach(note => {
                        const priorityColors = {
                            'Urgent': { bg: '#fee2e2', text: '#dc2626', border: '#fecaca' },
                            'High': { bg: '#fef3c7', text: '#d97706', border: '#fde68a' },
                            'Medium': { bg: '#dbeafe', text: '#2563eb', border: '#bfdbfe' },
                            'Low': { bg: '#f3f4f6', text: '#6b7280', border: '#e5e7eb' }
                        };
                        const pColor = priorityColors[note.priority] || priorityColors['Medium'];

                        html += `
                            <div class="modal-note-card" id="modal-note-${note.note_id}">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                            <span style="background: ${pColor.bg}; color: ${pColor.text}; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; border: 1px solid ${pColor.border};">
                                                ${note.priority}
                                            </span>
                                            <span style="background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600;">
                                                ${note.category}
                                            </span>
                                        </div>
                                        <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">${note.title}</h3>
                                    </div>
                                    <button class="btn-archive" onclick="archiveNote(${note.note_id})">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                        Archive
                                    </button>
                                </div>
                                <div class="note-content-full">${note.note_content}</div>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #f1f5f9; font-size: 13px;">
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <span style="color: #94a3b8; font-weight: 600; text-transform: uppercase; font-size: 11px;">Created By</span>
                                        <span style="color: #475569; font-weight: 600;">${note.created_by_name || 'System'}</span>
                                        <span style="color: #94a3b8;">${new Date(note.created_at).toLocaleString('en-GB')}</span>
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <span style="color: #94a3b8; font-weight: 600; text-transform: uppercase; font-size: 11px;">Related To</span>
                                        <span style="color: #475569; font-weight: 600;">${note.student_first_name ? note.student_first_name + ' ' + note.student_last_name : (note.class_name || 'N/A')}</span>
                                        <span style="color: #94a3b8;">${note.student_first_name ? (note.class_name || '') : 'General Class Note'}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    modalBody.innerHTML = html;
                } else {
                    modalBody.innerHTML = `
                        <div style="text-align: center; padding: 60px 20px; color: #94a3b8;">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 16px; opacity: 0.5;">
                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <div style="font-size: 16px; font-weight: 600; color: #64748b;">No matching notes found</div>
                        </div>
                    `;
                }
            });
    }

    function closeNotesModal() {
        document.getElementById('notes-modal').style.display = 'none';
        loadNotes(); // Refresh main list
    }

    function archiveNote(noteId) {
        if (!confirm('Are you sure you want to archive this note? It will be marked as Closed.')) return;

        fetch('../attendance/notes_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update_status&note_id=${noteId}&status=Closed`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const card = document.getElementById(`modal-note-${noteId}`);
                    if (card) {
                        card.style.opacity = '0.5';
                        card.style.pointerEvents = 'none';
                        card.querySelector('.btn-archive').innerHTML = 'Archived';
                        card.querySelector('.btn-archive').style.background = '#f1f5f9';
                        card.querySelector('.btn-archive').style.color = '#94a3b8';
                    }
                    loadNotes(); // Refresh stats in background
                } else {
                    alert('Error: ' + data.message);
                }
            });
    }

    // Close modal on outside click
    window.onclick = function (event) {
        const modal = document.getElementById('notes-modal');
        if (event.target == modal) {
            closeNotesModal();
        }
    };

    function renderNotesList(notes) {
        const container = document.getElementById('notes-list-container');

        if (!notes || notes.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 60px 20px; color: #94a3b8;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 16px; opacity: 0.5;">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <div style="font-size: 16px; font-weight: 600; color: #64748b; margin-bottom: 8px;">No Notes Found</div>
                    <div style="font-size: 14px;">No notes match the current filters</div>
                </div>
            `;
            return;
        }

        let html = '<div style="display: flex; flex-direction: column; gap: 12px;">';

        notes.forEach(note => {
            const priorityColors = {
                'Urgent': { bg: '#fee2e2', text: '#dc2626', border: '#fecaca' },
                'High': { bg: '#fef3c7', text: '#d97706', border: '#fde68a' },
                'Medium': { bg: '#dbeafe', text: '#2563eb', border: '#bfdbfe' },
                'Low': { bg: '#f3f4f6', text: '#6b7280', border: '#e5e7eb' }
            };

            const statusColors = {
                'Open': { bg: '#dbeafe', text: '#2563eb' },
                'In Progress': { bg: '#fef3c7', text: '#d97706' },
                'Resolved': { bg: '#d1fae5', text: '#059669' },
                'Closed': { bg: '#f3f4f6', text: '#6b7280' }
            };

            const pColor = priorityColors[note.priority] || priorityColors['Medium'];
            const sColor = statusColors[note.status] || statusColors['Open'];

            const studentInfo = note.student_first_name ?
                `<span style="color: #64748b;">• Student: ${note.student_first_name} ${note.student_last_name}</span>` : '';
            const classInfo = note.class_name ?
                `<span style="color: #64748b;">• Class: ${note.class_name} ${note.section_name || ''}</span>` : '';

            html += `
                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; transition: all 0.2s; cursor: pointer;" 
                     onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'" 
                     onmouseout="this.style.boxShadow='none'"
                     onclick="openNotesPopover('id', ${note.note_id}, 'Note Detail')">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                <span style="background: ${pColor.bg}; color: ${pColor.text}; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; border: 1px solid ${pColor.border};">
                                    ${note.priority}
                                </span>
                                <span style="background: ${sColor.bg}; color: ${sColor.text}; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600;">
                                    ${note.status}
                                </span>
                                <span style="background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600;">
                                    ${note.category}
                                </span>
                            </div>
                            <h4 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: #1e293b;">${note.title}</h4>
                            <p style="margin: 0 0 8px 0; color: #64748b; font-size: 14px; line-height: 1.5;">${note.note_content.substring(0, 150)}${note.note_content.length > 150 ? '...' : ''}</p>
                            <div style="display: flex; gap: 16px; font-size: 12px; color: #94a3b8;">
                                <span>By: ${note.created_by_name || 'Unknown'}</span>
                                <span>• ${new Date(note.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</span>
                                ${studentInfo}
                                ${classInfo}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;
    }

    function filterNotesByPriority(priority) {
        currentNotesFilter.priority = currentNotesFilter.priority === priority ? '' : priority;
        currentNotesFilter.status = ''; // Clear status filter
        loadNotes();
    }

    function filterNotesByStatus(status) {
        currentNotesFilter.status = status;
        currentNotesFilter.priority = ''; // Clear priority filter
        loadNotes();
    }

    // Notes filtering state
    let currentNotesFilter = {
        class_id: '',
        priority: '',
        status: 'Open'
    };

    function loadNotes() {
        const classId = document.getElementById('notes-class-filter').value;
        currentNotesFilter.class_id = classId;

        // Fetch counts for stats
        fetch(`../attendance/notes_api.php?action=get_counts&class_id=${classId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotesStats(data.counts);
                }
            });

        const params = new URLSearchParams();
        if (currentNotesFilter.class_id) params.append('class_id', currentNotesFilter.class_id);
        if (currentNotesFilter.priority) params.append('priority', currentNotesFilter.priority);
        if (currentNotesFilter.status) params.append('status', currentNotesFilter.status);

        fetch(`../attendance/notes_api.php?action=get_notes&${params.toString()}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get_notes&limit=5&${params.toString()}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderNotesList(data.notes);
                }
            })
            .catch(error => console.error('Error loading notes:', error));
    }

    function updateNotesStats(counts) {
        document.getElementById('urgent-notes-count').textContent = counts.urgent || 0;
        document.getElementById('high-notes-count').textContent = counts.high || 0;
        document.getElementById('open-notes-count').textContent = counts.open || 0;
        document.getElementById('total-notes-count').textContent = counts.total || 0;
        document.getElementById('archived-notes-count').textContent = counts.archived || 0;
    }
</script>