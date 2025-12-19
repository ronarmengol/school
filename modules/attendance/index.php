<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

$page_title = "Attendance";
include '../../includes/header.php';

$class_id = $_GET['class_id'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

// Handle Attendance Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_attendance'])) {
    $class_id_post = $_POST['class_id'];
    $date_post = $_POST['date'];
    $recorded_by = $_SESSION['user_id'];

    foreach ($_POST['status'] as $student_id => $status) {
        // Check if attendance already exists for this student on this date
        $check = mysqli_query($conn, "SELECT attendance_id FROM attendance WHERE student_id = $student_id AND attendance_date = '$date_post'");

        if (mysqli_num_rows($check) > 0) {
            // Update
            mysqli_query($conn, "UPDATE attendance SET status = '$status', recorded_by = $recorded_by WHERE student_id = $student_id AND attendance_date = '$date_post'");
        } else {
            // Insert
            mysqli_query($conn, "INSERT INTO attendance (student_id, class_id, attendance_date, status, recorded_by) VALUES ($student_id, $class_id_post, '$date_post', '$status', $recorded_by)");
        }
    }

    $class_id = $class_id_post;
    $date = $date_post;
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            showToastSuccess('Attendance saved successfully!');
        });
    </script>
    <?php
}

// Get Classes
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
?>

<style>
    <style>

    /* Premium Design Tokens */
    :root {
        --glass-bg: rgba(255, 255, 255, 0.8);
        --glass-border: rgba(255, 255, 255, 0.3);
        --stats-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
        --card-radius: 16px;
    }

    /* Tab Styles - Modern Pill Design */
    .tab-container {
        border-bottom: none;
        margin-bottom: 30px;
        display: flex;
        gap: 10px;
        background: #f1f5f9;
        padding: 6px;
        border-radius: 12px;
        width: fit-content;
    }

    .tab-button {
        padding: 10px 24px;
        cursor: pointer;
        font-weight: 600;
        color: #64748b;
        border-radius: 10px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        font-size: 14px;
    }

    .tab-button:hover {
        color: #1e293b;
        background: rgba(255, 255, 255, 0.5);
    }

    .tab-button.active {
        color: #3b82f6;
        background: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .tab-content {
        display: none;
        animation: slideUp 0.4s ease-out;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Modern Card Styles */
    .card-premium {
        background: white;
        border-radius: var(--card-radius);
        border: 1px solid #f1f5f9;
        box-shadow: var(--stats-shadow);
        padding: 24px;
        margin-bottom: 24px;
    }

    /* Stats Cards Redesign */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        padding: 24px;
        border-radius: 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-card::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 150px;
        height: 150px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        pointer-events: none;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        background: rgba(255, 255, 255, 0.2);
    }

    .stat-value {
        font-size: 32px;
        font-weight: 800;
        line-height: 1;
    }

    .stat-label {
        font-size: 14px;
        font-weight: 600;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-present {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .stat-absent {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .stat-late {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }

    .stat-excused {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: white;
    }

    /* Page bottom padding for floating button */
    body {
        padding-bottom: 200px;
    }
</style>

<div class="tab-container">
    <div class="tab-button" onclick="switchTab('view')">View Attendance</div>
    <div class="tab-button active" onclick="switchTab('update')">Update Attendance</div>
</div>

<!-- UPDATE ATTENDANCE TAB -->
<div id="tab-update" class="tab-content active">

    <div class="card" style="margin-bottom: 20px; padding: 15px;">
        <form method="GET">
            <input type="hidden" name="tab" value="update">
            <div style="display: flex; gap: 15px; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0; flex: 1;">
                    <label>Select Class</label>
                    <select name="class_id" class="form-control" required onchange="this.form.submit()">
                        <option value="">-- Select Class --</option>
                        <?php
                        mysqli_data_seek($classes, 0); // Reset pointer
                        while ($c = mysqli_fetch_assoc($classes)): ?>
                            <option value="<?php echo $c['class_id']; ?>" <?php echo $class_id == $c['class_id'] ? 'selected' : ''; ?>>
                                <?php echo $c['class_name'] . ' ' . $c['section_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0; flex: 1;">
                    <label>Date</label>
                    <input type="text" id="date-display" class="form-control" placeholder="DD/MM/YYYY"
                        value="<?php echo date('d/m/Y', strtotime($date)); ?>" required>
                    <input type="hidden" name="date" id="date-hidden" value="<?php echo $date; ?>">
                </div>

                <button type="submit" class="btn btn-primary">Load Register</button>
            </div>
        </form>
    </div>

    <?php
    if ($class_id) {
        // Fetch students with their attendance for the selected date
        $sql = "SELECT s.student_id, s.first_name, s.last_name, s.admission_number, a.status
            FROM students s
            LEFT JOIN attendance a ON s.student_id = a.student_id AND a.attendance_date = '$date'
            WHERE s.current_class_id = $class_id AND s.status = 'Active'
            ORDER BY s.first_name ASC";

        $result = mysqli_query($conn, $sql);

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
        $notes = [];
        while ($n = mysqli_fetch_assoc($notesRes))
            $notes[] = $n;

        if (mysqli_num_rows($result) > 0) {
            ?>
            <div class="card">
                <h3>Attendance Register - <?php echo date('l, F j, Y', strtotime($date)); ?></h3>

                <form method="POST">
                    <input type="hidden" name="save_attendance" value="1">
                    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                    <input type="hidden" name="date" value="<?php echo $date; ?>">

                    <style>
                        .attendance-table {
                            width: 100%;
                            border-collapse: collapse;
                        }

                        .attendance-table th {
                            background: #f8fafc;
                            padding: 12px;
                            text-align: left;
                            font-weight: 600;
                            color: #475569;
                            border-bottom: 2px solid #e2e8f0;
                        }

                        .attendance-table td {
                            padding: 12px;
                            border-bottom: 1px solid #f1f5f9;
                        }

                        .attendance-table tr:hover {
                            background: #f8fafc;
                        }

                        .checkbox-group {
                            display: flex;
                            gap: 20px;
                        }

                        .checkbox-label {
                            display: flex;
                            align-items: center;
                            gap: 6px;
                            cursor: pointer;
                            padding: 6px 12px;
                            border-radius: 6px;
                            transition: all 0.2s;
                        }

                        .checkbox-label:hover {
                            background: #f1f5f9;
                        }

                        .checkbox-label input[type="radio"] {
                            width: 18px;
                            height: 18px;
                            cursor: pointer;
                        }

                        .status-present {
                            color: #10b981;
                            font-weight: 500;
                        }

                        .status-absent {
                            color: #ef4444;
                            font-weight: 500;
                        }

                        .status-late {
                            color: #f59e0b;
                            font-weight: 500;
                        }

                        .status-excused {
                            color: #6366f1;
                            font-weight: 500;
                        }
                    </style>

                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Adm No</th>
                                <th style="width: 30%;">Student Name</th>
                                <th style="width: 55%;">Mark Attendance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['admission_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td>
                                        <div class="checkbox-group">
                                            <label class="checkbox-label">
                                                <input type="radio" name="status[<?php echo $row['student_id']; ?>]" value="Present"
                                                    <?php echo ($row['status'] == 'Present' || !$row['status']) ? 'checked' : ''; ?>>
                                                <span class="status-present">Present</span>
                                            </label>

                                            <label class="checkbox-label">
                                                <input type="radio" name="status[<?php echo $row['student_id']; ?>]" value="Absent"
                                                    <?php echo ($row['status'] == 'Absent') ? 'checked' : ''; ?>>
                                                <span class="status-absent">Absent</span>
                                            </label>

                                            <label class="checkbox-label">
                                                <input type="radio" name="status[<?php echo $row['student_id']; ?>]" value="Late"
                                                    <?php echo ($row['status'] == 'Late') ? 'checked' : ''; ?>>
                                                <span class="status-late">Late</span>
                                            </label>

                                            <label class="checkbox-label">
                                                <input type="radio" name="status[<?php echo $row['student_id']; ?>]" value="Excused"
                                                    <?php echo ($row['status'] == 'Excused') ? 'checked' : ''; ?>>
                                                <span class="status-excused">Excused</span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <div
                        style="margin-top: 20px; text-align: right; display: flex; justify-content: space-between; align-items: center;">
                        <div style="color: #64748b; font-size: 14px;">
                            <strong>Quick Tip:</strong> Click on any option to mark attendance
                        </div>
                        <button type="submit" class="btn btn-success" style="font-size: 16px; padding: 12px 30px;">
                            Save Attendance
                        </button>
                    </div>
                </form>

                <?php if (!empty($notes)): ?>
                    <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #f1f5f9;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#b45309" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">Notes for
                                <?php echo date('M d, Y', strtotime($date)); ?>
                            </h4>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
                            <?php foreach ($notes as $note):
                                $pCol = $note['priority'] == 'Urgent' ? '#ef4444' : ($note['priority'] == 'High' ? '#f59e0b' : '#6366f1');
                                ?>
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 15px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                        <span
                                            style="font-size: 10px; font-weight: 700; color: <?php echo $pCol; ?>; background: <?php echo $pCol; ?>15; padding: 2px 6px; border-radius: 4px; border: 1px solid <?php echo $pCol; ?>30;">
                                            <?php echo $note['priority']; ?>
                                        </span>
                                        <span style="font-size: 11px; color: #94a3b8;"><?php echo $note['category']; ?></span>
                                    </div>
                                    <h5 style="margin: 0 0 5px 0; font-size: 14px; font-weight: 700; color: #334155;">
                                        <?php echo htmlspecialchars($note['title']); ?>
                                    </h5>
                                    <?php if ($note['first_name']): ?>
                                        <div style="font-size: 12px; font-weight: 600; color: #4f46e5; margin-bottom: 8px;">
                                            Student: <?php echo htmlspecialchars($note['first_name'] . ' ' . $note['last_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.4;">
                                        <?php echo nl2br(htmlspecialchars($note['note_content'])); ?>
                                    </p>
                                    <div
                                        style="margin-top: 12px; padding-top: 10px; border-top: 1px dashed #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; gap: 8px;">
                                            <button type="button" onclick="editNote(<?php echo $note['note_id']; ?>)"
                                                style="background: none; border: none; color: #6366f1; cursor: pointer; padding: 4px; border-radius: 4px;"
                                                title="Edit Note">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <button type="button" onclick="deleteNote(<?php echo $note['note_id']; ?>)"
                                                style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 4px; border-radius: 4px;"
                                                title="Delete Note">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path
                                                        d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                    </path>
                                                </svg>
                                            </button>
                                        </div>
                                        <span style="font-size: 11px; color: #94a3b8;">—
                                            <?php echo htmlspecialchars($note['created_by_name']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Floating Add Note Button -->
            <?php if ($class_id): ?>
                <button type="button" onclick="openNoteModal()" class="floating-note-btn" title="Add Note">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                    <span>Add Note</span>
                </button>

                <style>
                    .floating-note-btn {
                        position: fixed;
                        bottom: 30px;
                        right: 30px;
                        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
                        color: white;
                        border: none;
                        border-radius: 50px;
                        padding: 14px 24px;
                        font-size: 15px;
                        font-weight: 600;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        cursor: pointer;
                        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        z-index: 1000;
                    }

                    .floating-note-btn:hover {
                        transform: translateY(-3px);
                        box-shadow: 0 12px 32px rgba(99, 102, 241, 0.5);
                    }

                    .floating-note-btn:active {
                        transform: translateY(-1px);
                    }
                </style>
            <?php endif; ?>

            <?php
        } else {
            echo "<div class='alert alert-warning'>No active students found in this class.</div>";
        }
    } else {
        echo "<div class='alert alert-info'>Please select a class and date to mark attendance.</div>";
    }
    ?>
</div>

<!-- VIEW ATTENDANCE TAB -->
<div id="tab-view" class="tab-content">

    <div class="card" style="margin-bottom: 20px; padding: 15px;">
        <div style="display: flex; gap: 15px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label>Select Class</label>
                <select id="view-class-id" class="form-control" onchange="loadMonthView()">
                    <option value="">-- Select Class --</option>
                    <?php
                    mysqli_data_seek($classes, 0);
                    while ($c = mysqli_fetch_assoc($classes)): ?>
                        <option value="<?php echo $c['class_id']; ?>">
                            <?php echo $c['class_name'] . ' ' . $c['section_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="button" class="btn btn-primary" onclick="loadMonthView()">View Report</button>
        </div>
    </div>

    <!-- Summary Stats -->
    <div id="view-stats" class="stats-grid" style="display: none;">
        <div class="stat-card stat-present">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div>
                <div class="stat-value" id="count-present">0</div>
                <div class="stat-label">Present</div>
            </div>
        </div>
        <div class="stat-card stat-absent">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
            <div>
                <div class="stat-value" id="count-absent">0</div>
                <div class="stat-label">Absent</div>
            </div>
        </div>
        <div class="stat-card stat-late">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div>
                <div class="stat-value" id="count-late">0</div>
                <div class="stat-label">Late</div>
            </div>
        </div>
        <div class="stat-card stat-excused">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
            </div>
            <div>
                <div class="stat-value" id="count-excused">0</div>
                <div class="stat-label">Excused</div>
            </div>
        </div>
    </div>



    <!-- Results Grid -->
    <div id="view-results" class="card-premium" style="display: none; padding-bottom: 60px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h4 id="grid-title" style="margin: 0; color: #1e293b; font-weight: 700; font-size: 20px;">Monthly
                    Attendance Grid</h4>
                <p style="color: #64748b; margin-top: 4px; font-size: 14px;">Detailed daily records for the selected
                    month</p>
            </div>

            <div
                style="display: flex; gap: 4px; align-items: center; background: #f1f5f9; padding: 4px; border-radius: 12px;">
                <button type="button" class="btn-nav" onclick="changeMonth(-1)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>

                <div id="month-display"
                    style="min-width: 140px; text-align: center; font-weight: 700; color: #1e293b; font-size: 14px;">
                    <?php echo date('F Y'); ?>
                </div>
                <input type="hidden" id="view-month" value="<?php echo date('Y-m'); ?>">

                <button type="button" class="btn-nav" onclick="changeMonth(1)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
        </div>

        <style>
            .btn-nav {
                background: white;
                border: none;
                width: 36px;
                height: 36px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                color: #64748b;
                transition: all 0.2s;
            }

            .btn-nav:hover {
                color: #3b82f6;
                transform: scale(1.05);
            }
        </style>

        <div id="view-legend-container">
            <!-- Legend will be populated here -->
        </div>
        <div id="view-grid-container">
            <div id="view-grid-body">
                <!-- Grid will be populated here -->
            </div>
        </div>

        <!-- Monthly Notes Summary Section -->
        <div id="view-notes-summary" style="margin-top: 40px; display: none;">
            <div
                style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 2px solid #f1f5f9;">
                <div
                    style="width: 40px; height: 40px; border-radius: 10px; background: #fef3c7; display: flex; align-items: center; justify-content: center; color: #b45309;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h4 style="margin: 0; color: #1e293b; font-weight: 700; font-size: 18px;">School Notes for the Month
                    </h4>
                    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 13px;">Overview of all communications and
                        incidents recorded this month</p>
                </div>
            </div>

            <div id="view-notes-list"
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
                <!-- Notes will be populated dynamically -->
            </div>
        </div>
    </div>

    <style>
        #view-grid-container {
            max-height: 600px;
            overflow: auto;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
            background: white;
        }

        .attendance-grid {
            display: table;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
            width: 100%;
        }

        .grid-header {
            display: table-row;
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .grid-header-cell {
            display: table-cell;
            padding: 16px 8px;
            overflow: visible;
            font-weight: 700;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            border-right: 1px solid #f1f5f9;
            text-align: center;
            white-space: nowrap;
            position: sticky;
            top: 0;
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .grid-header-cell.student-col {
            text-align: left;
            min-width: 220px;
            max-width: 220px;
            left: 0;
            z-index: 21;
            padding-left: 16px;
            border-right: 2px solid #e2e8f0;
        }

        .grid-row {
            display: table-row;
            transition: background 0.2s;
        }

        .grid-row:hover {
            background: #f1f5f9;
        }

        .grid-cell {
            display: table-cell;
            padding: 4px;
            border-bottom: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            text-align: center;
            vertical-align: middle;
            width: 32px;
            min-width: 32px;
            height: 38px;
        }

        .grid-cell.student-info {
            text-align: left;
            position: sticky;
            left: 0;
            background: white;
            border-right: 2px solid #e2e8f0;
            z-index: 10;
            min-width: 220px;
            max-width: 220px;
            width: auto;
            height: auto;
            padding: 10px 16px;
            box-shadow: 4px 0 8px rgba(0, 0, 0, 0.02);
        }

        .grid-row:hover .grid-cell.student-info {
            background: #f8fafc;
        }

        .student-info-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
            line-height: 1.2;
        }

        .student-info-adm {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
            margin-top: 2px;
        }

        .day-cell {
            width: 24px;
            height: 24px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            transition: all 0.2s;
            cursor: pointer;
        }

        .day-cell:hover {
            transform: scale(1.15);
            filter: brightness(0.95);
        }

        .day-cell.present {
            background: #dcfce7;
            color: #166534;
        }

        .day-cell.absent {
            background: #fee2e2;
            color: #991b1b;
        }

        .day-cell.late {
            background: #fef3c7;
            color: #92400e;
        }

        .day-cell.excused {
            background: #e0e7ff;
            color: #3730a3;
        }

        .day-cell.not-marked {
            background: #f8fafc;
            color: #cbd5e1;
            border: 1px dashed #e2e8f0;
        }

        .day-cell.weekend {
            background: #f1f5f9;
            color: #94a3b8;
            font-size: 10px;
        }

        .day-cell.out-of-term {
            background: #fff1f2;
            color: #e11d48;
        }

        /* Modern Legend */
        .grid-legend {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 12px;
            flex-wrap: wrap;
            border: 1px solid #f1f5f9;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }

        .legend-indicator {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 800;
        }

        /* Custom scrollbar */
        #view-grid-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        #view-grid-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        #view-grid-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        #view-grid-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .grid-empty {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .grid-empty svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* Note Indicators */
        .note-indicator {
            position: absolute;
            top: 2px;
            right: 0px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: 700;
            color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            cursor: help;
            z-index: 5;
        }

        .note-indicator:hover {
            transform: scale(1.2);
        }
    </style>
</div>

<script>


    // Current month state
    let currentMonth = new Date();

    function changeMonth(direction) {
        currentMonth.setMonth(currentMonth.getMonth() + direction);
        updateMonthDisplay();

        // Auto-reload if a class is selected
        const classId = document.getElementById('view-class-id').value;
        if (classId) {
            loadMonthView();
        }
    }

    function updateMonthDisplay() {
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        const display = monthNames[currentMonth.getMonth()] + ' ' + currentMonth.getFullYear();
        document.getElementById('month-display').innerText = display;

        const year = currentMonth.getFullYear();
        const month = String(currentMonth.getMonth() + 1).padStart(2, '0');
        document.getElementById('view-month').value = `${year}-${month}`;
    }

    function loadMonthView() {
        const classId = document.getElementById('view-class-id').value;
        const month = document.getElementById('view-month').value;

        if (!classId) {
            showToastError("Please select a class before viewing the report.");
            return;
        }

        fetch(`get_attendance_view.php?class_id=${classId}&month=${month}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Update Stats
                    document.getElementById('count-present').innerText = data.counts.Present || 0;
                    document.getElementById('count-absent').innerText = data.counts.Absent || 0;
                    document.getElementById('count-late').innerText = data.counts.Late || 0;
                    document.getElementById('count-excused').innerText = data.counts.Excused || 0;

                    // Build Grid
                    const gridBody = document.getElementById('view-grid-body');
                    const legendContainer = document.getElementById('view-legend-container');
                    gridBody.innerHTML = '';
                    legendContainer.innerHTML = '';

                    if (data.students && data.students.length > 0) {
                        const daysInMonth = data.daysInMonth;
                        const year = data.year;
                        const monthNum = data.month;

                        // Create legend
                        const legendHTML = `
                            <div class="grid-legend">
                                <div class="legend-item">
                                    <div class="legend-indicator present">P</div>
                                    <span>Present</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-indicator absent">A</div>
                                    <span>Absent</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-indicator late">L</div>
                                    <span>Late</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-indicator excused">E</div>
                                    <span>Excused</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-indicator not-marked">·</div>
                                    <span>Not Marked</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-indicator weekend">-</div>
                                    <span>Weekend</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-indicator out-of-term">×</div>
                                    <span>Holiday</span>
                                </div>
                                <div class="legend-item">
                                    <div class="note-indicator" style="background: #ef4444; position: static;">!</div>
                                    <span>Urgent Note</span>
                                </div>
                                <div class="legend-item">
                                    <div class="note-indicator" style="background: #f59e0b; position: static;">!</div>
                                    <span>High Priority Note</span>
                                </div>
                            </div>
                        `;
                        legendContainer.innerHTML = legendHTML;

                        // Create grid table
                        let gridHTML = '<div class="attendance-grid">';

                        // Header row
                        gridHTML += '<div class="grid-header">';
                        gridHTML += '<div class="grid-header-cell student-col">Student</div>';

                        for (let day = 1; day <= daysInMonth; day++) {
                            const date = new Date(year, monthNum - 1, day);
                            const dayOfWeek = date.getDay();
                            const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;

                            const checkDateStr = `${year}-${String(monthNum).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                            const isInsideTerm = data.termRanges.some(range => {
                                return checkDateStr >= range.start_date && checkDateStr <= range.end_date;
                            });

                            let cellClass = '';
                            if (!isInsideTerm) {
                                cellClass = 'out-of-term';
                            } else if (isWeekend) {
                                cellClass = 'weekend';
                            }

                            // Class-level notes for the header
                            const hasClassNotes = data.classNotesByDate && data.classNotesByDate[day];
                            let noteIndicator = '';
                            if (hasClassNotes) {
                                const noteColor = hasClassNotes.has_urgent ? '#ef4444' : (hasClassNotes.has_high ? '#f59e0b' : '#6366f1');
                                let noteTooltip = `Class Note(s):\n`;
                                if (hasClassNotes.notes_data) {
                                    hasClassNotes.notes_data.split(';;;').forEach(note => {
                                        const [noteTitle, priority] = note.split('|');
                                        noteTooltip += `• [${priority}] ${noteTitle}\n`;
                                    });
                                }
                                noteIndicator = `<div class="note-indicator" style="background: ${noteColor}; top: 2px; right: 0px;" title="${noteTooltip.trim()}">${hasClassNotes.count}</div>`;
                            }

                            gridHTML += `<div class="grid-header-cell ${cellClass}" style="position: relative;">${day}${noteIndicator}</div>`;
                        }
                        gridHTML += '</div>';

                        // Student rows
                        data.students.forEach(student => {
                            gridHTML += '<div class="grid-row">';

                            // Student info cell (sticky)
                            gridHTML += `
                                <div class="grid-cell student-info">
                                    <div class="student-info-name">${student.first_name} ${student.last_name}</div>
                                    <div class="student-info-adm">${student.admission_number}</div>
                                </div>
                            `;

                            // Day cells
                            for (let day = 1; day <= daysInMonth; day++) {
                                const status = student.attendance[day] || null;
                                const date = new Date(year, monthNum - 1, day);
                                const dayOfWeek = date.getDay();
                                const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;

                                // Check if this date falls within any term
                                const checkDateStr = `${year}-${String(monthNum).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                                const isInsideTerm = data.termRanges.some(range => {
                                    return checkDateStr >= range.start_date && checkDateStr <= range.end_date;
                                });

                                let cellClass = 'day-cell';
                                let cellContent = '';

                                if (!isInsideTerm) {
                                    cellClass += ' out-of-term';
                                    cellContent = '×';
                                } else if (isWeekend) {
                                    cellClass += ' weekend';
                                    cellContent = '-';
                                } else if (status) {
                                    cellClass += ` ${status.toLowerCase()}`;
                                    cellContent = status.charAt(0);
                                } else {
                                    cellClass += ' not-marked';
                                    cellContent = '·';
                                }

                                const title = !isInsideTerm ? 'Holiday/Out of Term' : (status || (isWeekend ? 'Weekend' : 'Not Marked'));

                                // Student-specific notes
                                let noteIndicator = '';
                                const hasStudentNotes = data.studentNotesByDate && data.studentNotesByDate[student.student_id] && data.studentNotesByDate[student.student_id][day];

                                if (hasStudentNotes) {
                                    const noteColor = hasStudentNotes.has_urgent ? '#ef4444' : (hasStudentNotes.has_high ? '#f59e0b' : '#6366f1');
                                    let noteTooltip = `Student Note(s):\n`;
                                    if (hasStudentNotes.notes_data) {
                                        hasStudentNotes.notes_data.split(';;;').forEach(note => {
                                            const [noteTitle, priority] = note.split('|');
                                            noteTooltip += `• [${priority}] ${noteTitle}\n`;
                                        });
                                    }
                                    noteIndicator = `<div class="note-indicator" style="background: ${noteColor};" title="${noteTooltip.trim()}">${hasStudentNotes.count}</div>`;
                                }

                                gridHTML += `<div class="grid-cell" style="position: relative;"><div class="${cellClass}" title="${title}">${cellContent}</div>${noteIndicator}</div>`;
                            }
                            gridHTML += '</div>';
                        });

                        gridHTML += '</div>';
                        gridBody.innerHTML = gridHTML;
                    } else {
                        gridBody.innerHTML = `
                            <div class="grid-empty">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <p style="font-size: 16px; font-weight: 500;">No students found</p>
                                <p style="font-size: 14px; margin-top: 8px;">Try selecting a different class</p>
                            </div>
                        `;
                    }

                    document.getElementById('view-stats').style.display = 'grid';
                    document.getElementById('view-results').style.display = 'block';

                    // Build Notes Summary
                    const notesSection = document.getElementById('view-notes-summary');
                    const notesList = document.getElementById('view-notes-list');
                    notesList.innerHTML = '';

                    if (data.allNotes && data.allNotes.length > 0) {
                        notesSection.style.display = 'block';
                        data.allNotes.forEach(note => {
                            const priorityColor = note.priority === 'Urgent' ? '#ef4444' : (note.priority === 'High' ? '#f59e0b' : '#6366f1');
                            const noteDate = new Date(note.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                            const studentName = note.first_name ? `${note.first_name} ${note.last_name}` : null;

                            const noteCard = `
                                <div style="background: white; border: 1px solid #f1f5f9; border-radius: 12px; padding: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                        <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: ${priorityColor}; background: ${priorityColor}15; padding: 4px 8px; border-radius: 6px; border: 1px solid ${priorityColor}30;">
                                            ${note.priority}
                                        </span>
                                        <span style="font-size: 11px; color: #94a3b8; font-weight: 500;">${noteDate}</span>
                                    </div>
                                    <h5 style="margin: 0 0 4px 0; color: #1e293b; font-size: 15px; font-weight: 700;">${note.title}</h5>
                                    <div style="font-size: 12px; color: #64748b; margin-bottom: 10px; display: flex; align-items: center; gap: 4px;">
                                        <span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">${note.category}</span>
                                        ${studentName ? `<span>•</span> <span style="font-weight: 600; color: #475569;">${studentName}</span>` : ''}
                                    </div>
                                    <p style="margin: 0; font-size: 13px; color: #475569; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;" title="${note.note_content}">
                                        ${note.note_content}
                                    </p>
                                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <button onclick="editNote(${note.note_id})" style="background: none; border: none; color: #6366f1; cursor: pointer; padding: 4px; border-radius: 4px; transition: background 0.2s;" title="Edit Note">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <button onclick="deleteNote(${note.note_id})" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 4px; border-radius: 4px; transition: background 0.2s;" title="Delete Note">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <span style="font-size: 11px; color: #64748b; font-weight: 500;">By ${note.created_by_name}</span>
                                    </div>
                                </div>
                            `;
                            notesList.innerHTML += noteCard;
                        });
                    } else {
                        notesSection.style.display = 'none';
                    }
                } else {
                    showToastError(data.message || 'Error fetching attendance data. Please try again.');
                }
            })
            .catch(err => {
                console.error(err);
                showToastError('Failed to load attendance data. Please check your connection and try again.');
            });
    }
</script>

<script>
    function switchTab(tabName) {
        // Save to localStorage
        localStorage.setItem('attendance_active_tab', tabName);

        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));

        // Show selected
        document.getElementById('tab-' + tabName).classList.add('active');

        // Update button state
        const buttons = document.querySelectorAll('.tab-button');
        if (tabName === 'view') {
            buttons[0].classList.add('active');
            // If we have a saved class for view tab, load it if it's not already selected
            const savedClass = localStorage.getItem('attendance_view_class');
            const viewSelect = document.getElementById('view-class-id');
            if (savedClass && viewSelect && !viewSelect.value) {
                viewSelect.value = savedClass;
                loadMonthView();
            }
        }
        if (tabName === 'update') {
            buttons[1].classList.add('active');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // 1. Handle Tab Memory
        const urlParams = new URLSearchParams(window.location.search);
        const urlTab = urlParams.get('tab');
        const savedTab = localStorage.getItem('attendance_active_tab');

        if (urlTab) {
            switchTab(urlTab);
        } else if (savedTab) {
            switchTab(savedTab);
        } else {
            switchTab('update'); // Default
        }

        // 2. Handle Update Class Memory
        const updateSelect = document.querySelector('select[name="class_id"]');
        if (updateSelect) {
            // Save when changed
            updateSelect.addEventListener('change', function () {
                localStorage.setItem('attendance_update_class', this.value);
            });

            // Load if not set in URL
            if (!urlParams.get('class_id')) {
                const savedUpdateClass = localStorage.getItem('attendance_update_class');
                if (savedUpdateClass && savedUpdateClass !== updateSelect.value) {
                    updateSelect.value = savedUpdateClass;
                    // Auto-submit to load the class
                    updateSelect.form.submit();
                }
            } else {
                // If set in URL, update localStorage
                localStorage.setItem('attendance_update_class', urlParams.get('class_id'));
            }
        }

        // 3. Handle View Class Memory
        const viewSelect = document.getElementById('view-class-id');
        if (viewSelect) {
            // Save when changed
            viewSelect.addEventListener('change', function () {
                localStorage.setItem('attendance_view_class', this.value);
            });

            // Loading is handled in switchTab('view') logic
        }
    });
</script>

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    // Initialize Date picker for UPDATE tab
    flatpickr("#date-display", {
        dateFormat: "d/m/Y",
        altInput: true,
        altFormat: "d/m/Y",
        allowInput: true,
        onChange: function (selectedDates, dateStr, instance) {
            if (selectedDates.length > 0) {
                const date = selectedDates[0];
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                document.getElementById('date-hidden').value = `${year}-${month}-${day}`;

                // Auto-submit if a class is already selected
                const classSelect = instance.element.closest('form').querySelector('select[name="class_id"]');
                if (classSelect && classSelect.value) {
                    instance.element.closest('form').submit();
                }
            }
        }
    });

    // Ensure hidden date is synchronized with display date on load if not already set
    if (!document.getElementById('date-hidden').value) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        document.getElementById('date-hidden').value = `${year}-${month}-${day}`;
    }
</script>

<!-- Note Creation Modal -->
<div id="noteModal" class="note-modal" style="display: none;">
    <div class="note-modal-overlay" onclick="closeNoteModal()"></div>
    <div class="note-modal-content">
        <div class="note-modal-header">
            <h3 id="modal-title">Create New Note</h3>
            <input type="hidden" id="edit-note-id" value="">
            <button type="button" onclick="closeNoteModal()" class="note-modal-close">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <form id="noteForm" onsubmit="submitNote(event)">
            <div class="note-form-grid">
                <div class="form-group">
                    <label class="form-label">Title <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="note-title" class="form-control"
                        placeholder="e.g., Internet connection issue" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Priority <span style="color: #ef4444;">*</span></label>
                    <select id="note-priority" class="form-control" required>
                        <option value="Low">Low - General note</option>
                        <option value="Medium" selected>Medium - Standard importance</option>
                        <option value="High">High - Important (shows on dashboard)</option>
                        <option value="Urgent">Urgent - Critical (shows on dashboard)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Category <span style="color: #ef4444;">*</span></label>
                    <select id="note-category" class="form-control" required onchange="toggleStudentSelect()">
                        <option value="General">General</option>
                        <option value="Student">Student Issue</option>
                        <option value="Classroom">Classroom Issue</option>
                        <option value="Facility">Facility Issue</option>
                    </select>
                </div>

                <div class="form-group" id="student-select-group" style="display: none;">
                    <label class="form-label">Related Student</label>
                    <select id="note-student" class="form-control">
                        <option value="">-- Select Student --</option>
                        <?php
                        if ($class_id) {
                            $students_sql = "SELECT student_id, first_name, last_name, admission_number 
                                           FROM students 
                                           WHERE current_class_id = $class_id AND status = 'Active' 
                                           ORDER BY first_name";
                            $students_result = mysqli_query($conn, $students_sql);
                            while ($student = mysqli_fetch_assoc($students_result)) {
                                echo "<option value='{$student['student_id']}'>{$student['first_name']} {$student['last_name']} ({$student['admission_number']})</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Note Details <span style="color: #ef4444;">*</span></label>
                <textarea id="note-content" class="form-control" rows="5"
                    placeholder="Describe the issue or note in detail..." required></textarea>
            </div>

            <div class="note-modal-footer">
                <button type="button" onclick="closeNoteModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Save Note
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .note-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.2s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .note-modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }

    .note-modal-content {
        position: relative;
        background: white;
        border-radius: 16px;
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .note-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 24px;
        border-bottom: 1px solid #e2e8f0;
    }

    .note-modal-header h3 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
    }

    .note-modal-close {
        background: none;
        border: none;
        cursor: pointer;
        color: #64748b;
        padding: 4px;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .note-modal-close:hover {
        background: #f1f5f9;
        color: #1e293b;
    }

    .note-modal-content form {
        padding: 24px;
    }

    .note-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }

    .note-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-secondary:hover {
        background: #e2e8f0;
    }

    .btn-primary {
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>

<script>
    const currentClassId = <?php echo $class_id ?: 'null'; ?>;

    function openNoteModal(isEdit = false) {
        const modal = document.getElementById('noteModal');
        const title = document.getElementById('modal-title');

        if (isEdit) {
            title.textContent = 'Edit School Note';
        } else {
            title.textContent = 'Create New Note';
            document.getElementById('edit-note-id').value = '';
            document.getElementById('noteForm').reset();
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeNoteModal() {
        document.getElementById('noteModal').style.display = 'none';
        document.body.style.overflow = 'auto';
        document.getElementById('noteForm').reset();
        document.getElementById('edit-note-id').value = '';
        document.getElementById('student-select-group').style.display = 'none';
    }

    function toggleStudentSelect() {
        const category = document.getElementById('note-category').value;
        const studentGroup = document.getElementById('student-select-group');

        if (category === 'Student') {
            studentGroup.style.display = 'block';
        } else {
            studentGroup.style.display = 'none';
            document.getElementById('note-student').value = '';
        }
    }

    function editNote(noteId) {
        fetch(`notes_api.php?action=get_notes&note_id=${noteId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.notes.length > 0) {
                    const note = data.notes[0];
                    document.getElementById('edit-note-id').value = note.note_id;
                    document.getElementById('note-title').value = note.title;
                    document.getElementById('note-content').value = note.note_content;
                    document.getElementById('note-priority').value = note.priority;
                    document.getElementById('note-category').value = note.category;

                    toggleStudentSelect();
                    if (note.category === 'Student') {
                        document.getElementById('note-student').value = note.related_student_id;
                    }

                    openNoteModal(true);
                }
            });
    }

    function deleteNote(noteId) {
        if (!confirm('Are you sure you want to delete this note?')) return;

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('note_id', noteId);

        fetch('notes_api.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToastSuccess(data.message);
                    location.reload(); // Quickest way to refresh both potential views
                } else {
                    showToastError(data.message);
                }
            });
    }

    function submitNote(event) {
        event.preventDefault();

        const editId = document.getElementById('edit-note-id').value;
        const formData = new FormData();
        formData.append('action', editId ? 'update' : 'create');
        if (editId) formData.append('note_id', editId);

        formData.append('title', document.getElementById('note-title').value);
        formData.append('content', document.getElementById('note-content').value);
        formData.append('priority', document.getElementById('note-priority').value);
        formData.append('category', document.getElementById('note-category').value);
        formData.append('class_id', currentClassId);

        const studentId = document.getElementById('note-student').value;
        if (studentId) {
            formData.append('student_id', studentId);
        }

        fetch('notes_api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToastSuccess(data.message);
                    closeNoteModal();
                    location.reload();
                } else {
                    showToastError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToastError('Failed to save note');
            });
    }
</script>

<?php
include '../../includes/modals.php';
include '../../includes/footer.php';
?>