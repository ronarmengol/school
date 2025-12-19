<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']);

$page_title = "Academic Years & Terms";
include '../../includes/header.php';

// Fetch Data
$years_res = mysqli_query($conn, "SELECT * FROM academic_years ORDER BY start_date DESC");
$years = [];
while ($r = mysqli_fetch_assoc($years_res))
    $years[] = $r;
?>

<style>
    /* Premium Academic Management Styling */

    /* Toast Notifications - Refined */
    .toast {
        position: fixed;
        top: 24px;
        right: 24px;
        background: white;
        color: #991b1b;
        padding: 18px 24px;
        border-radius: 12px;
        border-left: 4px solid #ef4444;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1), 0 4px 10px rgba(0, 0, 0, 0.05);
        z-index: 9999;
        animation: slideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        max-width: 420px;
        font-weight: 500;
        backdrop-filter: blur(10px);
    }

    .toast.success {
        background: white;
        color: #065f46;
        border-left-color: #10b981;
    }

    @keyframes slideIn {
        from {
            transform: translateX(450px) scale(0.9);
            opacity: 0;
        }

        to {
            transform: translateX(0) scale(1);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0) scale(1);
            opacity: 1;
        }

        to {
            transform: translateX(450px) scale(0.9);
            opacity: 0;
        }
    }

    /* Main Container - Premium Quality */
    .tab-container {
        background: white;
        padding: 32px;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
    }

    /* Tab Navigation - Refined */
    .tabs {
        display: flex;
        gap: 4px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 32px;
        padding-bottom: 0;
    }

    .tab-link {
        padding: 14px 24px;
        background: none;
        border: none;
        font-size: 15px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        margin-bottom: -1px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        letter-spacing: -0.01em;
    }

    .tab-link:hover {
        color: #1e293b;
        background: #f8fafc;
        border-radius: 8px 8px 0 0;
    }

    .tab-link.active {
        color: #2c3e50;
        border-bottom-color: #3498db;
        background: #f8fafc;
        border-radius: 8px 8px 0 0;
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Section Headers */
    .action-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 28px;
    }

    .action-bar h3 {
        font-size: 22px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        letter-spacing: -0.02em;
    }

    /* Form Cards - Premium */
    .add-form-card {
        background: #f8fafc;
        padding: 28px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        margin-bottom: 32px;
        transition: all 0.2s ease;
    }

    .add-form-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .add-form-card h4 {
        margin: 0 0 20px 0;
        font-size: 16px;
        font-weight: 700;
        color: #334155;
        letter-spacing: -0.01em;
    }

    /* Enhanced Form Inputs */
    .form-control {
        height: 48px;
        padding: 0 16px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 500;
        color: #1e293b;
        transition: all 0.2s ease;
        background: white;
    }

    .form-control:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    .form-control::placeholder {
        color: #94a3b8;
        font-weight: 500;
    }

    select.form-control {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1.5L6 6.5L11 1.5' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        padding-right: 44px;
    }

    textarea.form-control {
        height: auto;
        padding: 14px 16px;
        resize: vertical;
        min-height: 100px;
    }

    /* Premium Tables */
    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
    }

    .table thead th {
        background: #f8fafc;
        padding: 16px 20px;
        text-align: left;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        border-bottom: 2px solid #e2e8f0;
    }

    .table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.2s ease;
    }

    .table tbody tr:hover {
        background: #f8fafc;
    }

    .table tbody tr:last-child {
        border-bottom: none;
    }

    .table tbody td {
        padding: 18px 20px;
        color: #475569;
        font-size: 15px;
        font-weight: 500;
        vertical-align: middle;
    }

    .table tbody td:first-child {
        font-weight: 600;
        color: #1e293b;
    }

    /* Status Badges - Refined */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        letter-spacing: -0.01em;
    }

    .badge-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .badge-secondary {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    /* Action Buttons - Polished */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        border: 2px solid;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: -0.01em;
    }

    .btn:active {
        transform: scale(0.98);
    }

    .btn-primary {
        background: transparent;
        color: #3b82f6;
        border-color: #3b82f6;
    }

    .btn-primary:hover {
        background: #3b82f6;
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
    }

    .btn-outline-primary {
        background: transparent;
        color: #3b82f6;
        border-color: #3b82f6;
        padding: 8px 14px;
        font-size: 13px;
    }

    .btn-outline-primary:hover {
        background: #3b82f6;
        color: white;
    }

    .btn-outline-danger {
        background: transparent;
        color: #ef4444;
        border-color: #ef4444;
        padding: 8px 14px;
        font-size: 13px;
    }

    .btn-outline-danger:hover {
        background: #ef4444;
        color: white;
    }

    .btn-secondary {
        background: white;
        color: #64748b;
        border-color: #e2e8f0;
    }

    .btn-secondary:hover {
        background: #f8fafc;
        color: #1e293b;
        border-color: #cbd5e1;
    }

    .btn-outline-secondary {
        background: transparent;
        color: #64748b;
        border-color: #cbd5e1;
    }

    .btn-outline-secondary:hover {
        background: #f1f5f9;
        color: #1e293b;
    }

    /* Calendar Styles - Premium */
    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 28px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e2e8f0;
    }

    .calendar-header h3 {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        min-width: 220px;
        letter-spacing: -0.02em;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
        background: #e2e8f0;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .calendar-day-header {
        background: #f8fafc;
        padding: 14px;
        text-align: center;
        font-weight: 700;
        color: #64748b;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .calendar-day {
        background: white;
        min-height: 120px;
        padding: 12px;
        position: relative;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .calendar-day:hover {
        background: #f8fafc;
        box-shadow: inset 0 0 0 1px #cbd5e1;
    }

    .calendar-day.today {
        background: #eff6ff;
        border: 2px solid #3b82f6;
        z-index: 1;
    }

    .day-number {
        font-weight: 700;
        margin-bottom: 8px;
        color: #1e293b;
        font-size: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .day-add-btn {
        opacity: 0;
        color: #cbd5e1;
        font-size: 18px;
        line-height: 1;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }

    .calendar-day:hover .day-add-btn {
        opacity: 1;
        color: #3b82f6;
    }

    .day-add-btn:hover {
        background: #dbeafe;
    }

    .event-dot {
        display: block;
        margin-top: 4px;
        padding: 5px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        color: white;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 3px;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .event-dot:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }

    .event-dot.holiday {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    .event-dot.event {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }

    .event-dot.reminder {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .event-dot.exam {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    }

    .other-month {
        background: #fafbfc;
        color: #cbd5e1;
    }

    .other-month .day-number {
        color: #cbd5e1;
        font-weight: 600;
    }

    /* Calendar Messages Section */
    .calendar-messages {
        margin-top: 40px;
        border-top: 1px solid #e2e8f0;
        padding-top: 32px;
    }

    .calendar-messages h3 {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 24px 0;
        letter-spacing: -0.01em;
    }

    /* Modals - Executive Quality */
    #editModal,
    #deleteModal,
    #editTermModal,
    #deleteTermModal,
    #eventModal,
    #viewEventModal {
        backdrop-filter: blur(8px);
        animation: fadeIn 0.3s ease;
    }

    #editModal>div,
    #deleteModal>div,
    #editTermModal>div,
    #deleteTermModal>div,
    #eventModal>div,
    #viewEventModal>div {
        background: white;
        padding: 36px;
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15), 0 10px 25px rgba(0, 0, 0, 0.1);
        animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        border: 1px solid #e2e8f0;
    }

    @keyframes slideUp {
        from {
            transform: translateY(30px) scale(0.95);
            opacity: 0;
        }

        to {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }

    #editModal h3,
    #editTermModal h3,
    #eventModal h3,
    #viewEventModal h3 {
        font-size: 22px;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 24px 0;
        letter-spacing: -0.02em;
    }

    #deleteModal h3,
    #deleteTermModal h3 {
        font-size: 22px;
        font-weight: 700;
        color: #ef4444;
        margin: 0 0 16px 0;
        letter-spacing: -0.02em;
    }

    /* Form Groups in Modals */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 14px;
        color: #334155;
        letter-spacing: -0.01em;
    }

    /* Text Utilities */
    .text-center {
        text-align: center;
    }

    .text-muted {
        color: #94a3b8;
        font-style: italic;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .tab-container {
            padding: 20px;
            border-radius: 12px;
        }

        .tabs {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .tab-link {
            padding: 12px 18px;
            font-size: 14px;
            white-space: nowrap;
        }

        .add-form-card {
            padding: 20px;
        }

        .table thead th {
            padding: 12px 14px;
            font-size: 12px;
        }

        .table tbody td {
            padding: 14px;
            font-size: 14px;
        }

        .calendar-day {
            min-height: 100px;
            padding: 8px;
        }

        .btn {
            padding: 8px 14px;
            font-size: 13px;
        }
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="tab-container">
            <div class="tabs">
                <button class="tab-link active" onclick="openTab('years', event)" id="tab-btn-years">Academic
                    Years</button>
                <button class="tab-link" onclick="openTab('terms', event)" id="tab-btn-terms">Terms</button>
                <button class="tab-link" onclick="openTab('calendar', event)" id="tab-btn-calendar">Term
                    Calendar</button>
            </div>

            <!-- Years Tab -->
            <div id="years" class="tab-content active">
                <div class="action-bar">
                    <h3>Manage Academic Years</h3>
                </div>

                <div class="add-form-card">
                    <h4 style="margin-top: 0; margin-bottom: 15px; font-size: 16px; color: #334155;">Add New Academic
                        Year</h4>
                    <form id="addYearForm" onsubmit="return handleAddYear(event)">
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <div style="flex: 2; min-width: 200px;">
                                <input type="text" name="year_name" placeholder="Year Name (e.g. 2024)"
                                    class="form-control" required>
                            </div>
                            <div style="flex: 3; display: flex; gap: 10px; min-width: 300px;">
                                <input type="text" name="start_date" id="add_start_date"
                                    class="form-control date-picker" placeholder="Start Date" required>
                                <input type="text" name="end_date" id="add_end_date" class="form-control date-picker"
                                    placeholder="End Date" required>
                            </div>
                            <div style="flex: 1; min-width: 120px;">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Add Year</button>
                            </div>
                        </div>
                    </form>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Year Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($years as $y): ?>
                            <tr>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($y['year_name']); ?></td>
                                <td><?php echo $y['start_date'] ? date('d/m/Y', strtotime($y['start_date'])) : '<span class="text-muted">N/A</span>'; ?>
                                </td>
                                <td><?php echo $y['end_date'] ? date('d/m/Y', strtotime($y['end_date'])) : '<span class="text-muted">N/A</span>'; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $y['is_active'] ? 'badge-success' : 'badge-secondary'; ?>"
                                        style="padding: 6px 12px; border-radius: 20px; font-weight: 500; font-size: 12px;
                                             background: <?php echo $y['is_active'] ? '#dcfce7; color: #166534' : '#f1f5f9; color: #64748b'; ?>;">
                                        <?php echo ($y['is_active']) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <button
                                            onclick="editYear(<?php echo $y['year_id']; ?>, '<?php echo htmlspecialchars($y['year_name']); ?>', '<?php echo $y['start_date']; ?>', '<?php echo $y['end_date']; ?>')"
                                            class="btn btn-outline-primary" style="padding: 4px 10px; font-size: 13px;">
                                            <i class="feather icon-edit"></i> Edit
                                        </button>
                                        <button
                                            onclick="deleteYear(<?php echo $y['year_id']; ?>, '<?php echo htmlspecialchars($y['year_name']); ?>')"
                                            class="btn btn-outline-danger" style="padding: 4px 10px; font-size: 13px;">
                                            <i class="feather icon-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($years)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted" style="padding: 30px;">No academic years
                                    found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Terms Tab -->
            <div id="terms" class="tab-content">
                <div class="action-bar">
                    <h3>Manage Terms</h3>
                </div>

                <div class="add-form-card">
                    <h4 style="margin-top: 0; margin-bottom: 15px; font-size: 16px; color: #334155;">Add New Term</h4>
                    <form id="addTermForm" onsubmit="return handleAddTerm(event)">
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <div style="flex: 2; min-width: 250px;">
                                <select name="year_id" class="form-control" required>
                                    <option value="">Select Academic Year</option>
                                    <?php foreach ($years as $y): ?>
                                        <option value="<?php echo $y['year_id']; ?>"><?php echo $y['year_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="flex: 2; min-width: 200px;">
                                <input type="text" name="term_name" placeholder="Term Name (e.g. Term 1)"
                                    class="form-control" required>
                            </div>
                            <div style="flex: 3; display: flex; gap: 10px; min-width: 300px;">
                                <input type="text" name="start_date" class="form-control date-picker"
                                    placeholder="Start Date">
                                <input type="text" name="end_date" class="form-control date-picker"
                                    placeholder="End Date">
                            </div>
                            <div style="flex: 1; min-width: 120px;">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Add Term</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- List last 20 terms -->
                <?php
                $terms_res = mysqli_query($conn, "SELECT t.*, y.year_name FROM terms t JOIN academic_years y ON t.academic_year_id = y.year_id ORDER BY y.year_name DESC, t.term_name ASC LIMIT 20");
                ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Academic Year</th>
                            <th>Term Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($t = mysqli_fetch_assoc($terms_res)): ?>
                            <tr>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($t['year_name']); ?></td>
                                <td><?php echo htmlspecialchars($t['term_name']); ?></td>
                                <td><?php echo $t['start_date'] ? date('d/m/Y', strtotime($t['start_date'])) : '<span class="text-muted">N/A</span>'; ?>
                                </td>
                                <td><?php echo $t['end_date'] ? date('d/m/Y', strtotime($t['end_date'])) : '<span class="text-muted">N/A</span>'; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <button
                                            onclick="editTerm(<?php echo $t['term_id']; ?>, '<?php echo htmlspecialchars($t['term_name']); ?>', <?php echo $t['academic_year_id']; ?>, '<?php echo $t['start_date']; ?>', '<?php echo $t['end_date']; ?>')"
                                            class="btn btn-outline-primary" style="padding: 4px 10px; font-size: 13px;">
                                            <i class="feather icon-edit"></i> Edit
                                        </button>
                                        <button
                                            onclick="deleteTerm(<?php echo $t['term_id']; ?>, '<?php echo htmlspecialchars($t['term_name']); ?>')"
                                            class="btn btn-outline-danger" style="padding: 4px 10px; font-size: 13px;">
                                            <i class="feather icon-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($terms_res) == 0): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted" style="padding: 30px;">No terms found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Calendar Tab -->
            <div id="calendar" class="tab-content">
                <div class="calendar-header">
                    <h3 id="calendarMonthDisplay" style="margin: 0; min-width: 200px;">December 2025</h3>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-outline-secondary" onclick="changeMonth(-1)">
                            <i class="feather icon-chevron-left"></i> Previous
                        </button>
                        <button class="btn btn-outline-secondary" onclick="changeMonth(0)">Today</button>
                        <button class="btn btn-outline-secondary" onclick="changeMonth(1)">
                            Next <i class="feather icon-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div class="calendar-grid">
                    <!-- Week Headers -->
                    <div class="calendar-day-header">Sun</div>
                    <div class="calendar-day-header">Mon</div>
                    <div class="calendar-day-header">Tue</div>
                    <div class="calendar-day-header">Wed</div>
                    <div class="calendar-day-header">Thu</div>
                    <div class="calendar-day-header">Fri</div>
                    <div class="calendar-day-header">Sat</div>

                    <!-- Days will be rendered by JS -->
                    <div id="calendarDates" style="display: contents;"></div>
                </div>

                <div class="calendar-messages"
                    style="margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0; font-size: 18px; color: #334155;">Special Messages & School Reminders</h3>
                    </div>

                    <div class="add-form-card">
                        <form id="addMessageForm" onsubmit="return handleAddMessage(event)">
                            <div style="display: flex; gap: 15px;">
                                <input type="text" name="message" class="form-control"
                                    placeholder="Enter special message or reminder for parents..." required>
                                <button type="submit" class="btn btn-primary" style="white-space: nowrap;">Add
                                    Message</button>
                            </div>
                        </form>
                    </div>

                    <div id="messagesList">
                        <!-- Messages will be loaded here -->
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div
        style="background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 20px;">Edit Academic Year</h3>
        <form id="editYearForm" onsubmit="return handleEditYear(event)">
            <input type="hidden" name="year_id" id="edit_year_id">

            <div class="form-group">
                <label>Year Name</label>
                <input type="text" name="year_name" id="edit_year_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Start Date</label>
                <input type="text" name="start_date" id="edit_start_date" class="form-control date-picker">
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type="text" name="end_date" id="edit_end_date" class="form-control date-picker">
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px; justify-content: flex-end;">
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div
        style="background: white; padding: 30px; border-radius: 8px; max-width: 400px; width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 15px; color: #ef4444;">Delete Academic Year</h3>
        <p>Are you sure you want to delete <strong id="delete_year_name"></strong>?</p>
        <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">This action cannot be undone and might affect
            linked records.</p>

        <form id="deleteYearForm" onsubmit="return handleDeleteYear(event)">
            <input type="hidden" name="year_id" id="delete_year_id">

            <div class="form-group">
                <label style="font-size: 14px; margin-bottom: 5px;">Enter your password to confirm:</label>
                <input type="password" name="password" class="form-control" required
                    placeholder="Administrator Password">
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px; justify-content: flex-end;">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-outline-danger">Delete Year</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Term Modal -->
<div id="editTermModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div
        style="background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 20px;">Edit Term</h3>
        <form id="editTermForm" onsubmit="return handleEditTerm(event)">
            <input type="hidden" name="term_id" id="edit_term_id">

            <div class="form-group">
                <label>Academic Year</label>
                <select name="year_id" id="edit_term_year_id" class="form-control" required>
                    <option value="">Select Academic Year</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y['year_id']; ?>"><?php echo $y['year_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Term Name</label>
                <input type="text" name="term_name" id="edit_term_name" class="form-control" required>
            </div>
            <div class="form-group" style="display: flex; gap: 15px;">
                <div style="flex: 1;">
                    <label>Start Date</label>
                    <input type="text" name="start_date" id="edit_term_start_date" class="form-control date-picker"
                        placeholder="DD/MM/YYYY">
                </div>
                <div style="flex: 1;">
                    <label>End Date</label>
                    <input type="text" name="end_date" id="edit_term_end_date" class="form-control date-picker"
                        placeholder="DD/MM/YYYY">
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px; justify-content: flex-end;">
                <button type="button" onclick="closeEditTermModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Term</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Term Modal -->
<div id="deleteTermModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div
        style="background: white; padding: 30px; border-radius: 8px; max-width: 400px; width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 15px; color: #ef4444;">Delete Term</h3>
        <p>Are you sure you want to delete <strong id="delete_term_name"></strong>?</p>
        <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">This action cannot be undone.</p>

        <form id="deleteTermForm" onsubmit="return handleDeleteTerm(event)">
            <input type="hidden" name="term_id" id="delete_term_id">

            <div class="form-group">
                <label style="font-size: 14px; margin-bottom: 5px;">Enter your password to confirm:</label>
                <input type="password" name="password" class="form-control" required
                    placeholder="Administrator Password">
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px; justify-content: flex-end;">
                <button type="button" onclick="closeDeleteTermModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-outline-danger">Delete Term</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Event Modal -->
<div id="eventModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div
        style="background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 20px;">Add Calendar Event</h3>
        <form id="addEventForm" onsubmit="return handleAddEvent(event)">
            <div class="form-group">
                <label>Date</label>
                <input type="text" name="date" id="event_date" class="form-control date-picker" required>
            </div>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" placeholder="Event Title" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type" class="form-control" required>
                    <option value="event">Event</option>
                    <option value="holiday">Holiday</option>
                    <option value="reminder">Reminder</option>
                    <option value="exam">Exam</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description (Optional)</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px; justify-content: flex-end;">
                <button type="button" onclick="closeEventModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Event</button>
            </div>
        </form>
    </div>
</div>

<!-- View/Delete Event Modal -->
<div id="viewEventModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div
        style="background: white; padding: 30px; border-radius: 8px; max-width: 400px; width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 id="viewEventTitle" style="margin-bottom: 5px;">Event Title</h3>
        <span id="viewEventType" class="badge" style="margin-bottom: 15px; display: inline-block;">Type</span>

        <p id="viewEventDesc" style="color: #64748b; margin-bottom: 20px;">Description here.</p>
        <p id="viewEventDate" style="font-size: 14px; color: #94a3b8; margin-bottom: 30px;"></p>

        <div style="display: flex; gap: 10px; margin-top: 25px; justify-content: flex-end;">
            <button type="button" onclick="closeViewEventModal()" class="btn btn-secondary">Close</button>
            <button id="btnDeleteEvent" onclick="handleDeleteEvent()" class="btn btn-outline-danger">Delete
                Event</button>
        </div>
    </div>
</div>

<script>
    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth(); // 0-11
    let currentEvents = [];

    function openTab(tabName, event) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-link').forEach(link => link.classList.remove('active'));

        document.getElementById(tabName).classList.add('active');

        if (event) {
            event.currentTarget.classList.add('active');
        } else {
            const btn = document.getElementById('tab-btn-' + tabName);
            if (btn) btn.classList.add('active');
        }

        localStorage.setItem('activeAcademicTab', tabName);

        if (tabName === 'calendar') {
            renderCalendar();
            loadMessages();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const savedTab = localStorage.getItem('activeAcademicTab');
        if (savedTab && (savedTab === 'years' || savedTab === 'terms' || savedTab === 'calendar')) {
            openTab(savedTab, null);
        }
    });

    function showToast(message, type = 'error') {
        const toast = document.createElement('div');
        toast.className = 'toast' + (type === 'success' ? ' success' : '');
        toast.innerHTML = `<strong>${type === 'success' ? '✓' : '✗'}</strong> ${message}`;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 2000);
    }

    function sendAjaxRequest(formData, successCallback) {
        fetch('terms_api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.events) { // Handle get_events special case
                        successCallback(data);
                        return;
                    }
                    showToast(data.message, 'success');
                    if (successCallback) successCallback(data);

                    // Reload page if not calendar event fetch or message actions
                    const action = formData.get('action');
                    if (!action.includes('get_events') && !action.includes('_message')) {
                        if (data.message && data.message.includes('Event')) {
                            // Update calendar without reload
                            renderCalendar();
                        } else {
                            setTimeout(() => location.reload(), 1000);
                        }
                    }
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showToast('An error occurred. Please try again.', 'error');
                console.error('Error:', error);
            });
    }

    // --- Calendar Functions ---

    function changeMonth(delta) {
        if (delta === 0) {
            const now = new Date();
            currentYear = now.getFullYear();
            currentMonth = now.getMonth();
        } else {
            currentMonth += delta;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            } else if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
        }
        renderCalendar();
    }

    function fetchEvents(year, month, callback) {
        const formData = new FormData();
        formData.append('action', 'get_events');
        formData.append('year', year);
        formData.append('month', month + 1); // PHP expects 1-12

        sendAjaxRequest(formData, (data) => {
            currentEvents = data.events;
            callback();
        });
    }

    function renderCalendar() {
        fetchEvents(currentYear, currentMonth, () => {
            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            document.getElementById('calendarMonthDisplay').textContent = `${monthNames[currentMonth]} ${currentYear}`;

            const firstDay = new Date(currentYear, currentMonth, 1).getDay(); // 0 is Sunday
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

            const grid = document.getElementById('calendarDates');
            grid.innerHTML = '';

            let date = 1;
            // 6 rows max to cover all weeks
            for (let i = 0; i < 6; i++) {
                // 7 columns
                for (let j = 0; j < 7; j++) {
                    const cell = document.createElement('div');
                    cell.className = 'calendar-day';

                    if (i === 0 && j < firstDay) {
                        // Empty cells before first day
                        cell.classList.add('other-month'); // styling for empty
                    } else if (date > daysInMonth) {
                        // Empty cells after last day
                        cell.classList.add('other-month');
                    } else {
                        // Valid Date
                        const fullDateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;

                        const today = new Date();
                        if (date === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear()) {
                            cell.classList.add('today');
                        }

                        const dayNum = document.createElement('div');
                        dayNum.className = 'day-number';
                        dayNum.innerHTML = `${date} <span class="day-add-btn" onclick="openEventModal('${fullDateStr}'); event.stopPropagation();">+</span>`;
                        cell.appendChild(dayNum);

                        // Render events
                        const daysEvents = currentEvents.filter(e => e.start_date === fullDateStr);
                        daysEvents.forEach(evt => {
                            const dot = document.createElement('div');
                            dot.className = `event-dot ${evt.event_type}`;
                            dot.textContent = evt.title;
                            dot.onclick = (e) => {
                                e.stopPropagation();
                                openViewEventModal(evt);
                            }
                            cell.appendChild(dot);
                        });

                        // Click on cell to add event (optional, already have + button)
                        cell.onclick = () => openEventModal(fullDateStr);

                        date++;
                    }

                    grid.appendChild(cell);
                }
                if (date > daysInMonth) break;
            }
        });
    }

    function openEventModal(dateStr) {
        const field = document.getElementById('event_date');
        if (field._flatpickr) field._flatpickr.setDate(dateStr);
        else field.value = dateStr;
        document.getElementById('eventModal').style.display = 'flex';
    }

    function closeEventModal() {
        document.getElementById('eventModal').style.display = 'none';
    }

    function handleAddEvent(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'add_event');

        sendAjaxRequest(formData, () => {
            closeEventModal();
            form.reset();
            // renderCalendar already called by sendAjaxRequest success logic if properly handled, otherwise manual called there
        });

        return false;
    }

    let selectedEventId = null;
    function openViewEventModal(event) {
        selectedEventId = event.id;
        document.getElementById('viewEventTitle').textContent = event.title;
        document.getElementById('viewEventType').textContent = event.event_type.toUpperCase();
        document.getElementById('viewEventType').className = `badge badge-${event.event_type}`;
        // Simple badge mapping logic
        const badgeColors = { 'holiday': '#ef4444', 'event': '#3b82f6', 'reminder': '#f59e0b', 'exam': '#8b5cf6' };
        document.getElementById('viewEventType').style.backgroundColor = badgeColors[event.event_type] || '#64748b';
        document.getElementById('viewEventType').style.color = 'white';

        document.getElementById('viewEventDesc').textContent = event.description || 'No description.';
        const evtDate = new Date(event.start_date);
        document.getElementById('viewEventDate').textContent = `${String(evtDate.getDate()).padStart(2, '0')}/${String(evtDate.getMonth() + 1).padStart(2, '0')}/${evtDate.getFullYear()}`;

        document.getElementById('viewEventModal').style.display = 'flex';
    }

    function closeViewEventModal() {
        document.getElementById('viewEventModal').style.display = 'none';
        selectedEventId = null;
    }

    function handleDeleteEvent() {
        if (!selectedEventId) return;

        showModal({
            type: 'warning',
            title: 'Delete Event',
            message: '<p>Are you sure you want to delete this event?</p>',
            confirmText: 'Delete Event',
            confirmType: 'danger',
            onConfirm: () => {
                const formData = new FormData();
                formData.append('action', 'delete_event');
                formData.append('event_id', selectedEventId);

                sendAjaxRequest(formData, () => {
                    closeViewEventModal();
                });
            }
        });
    }

    // --- Message Functions ---

    function loadMessages() {
        const formData = new FormData();
        formData.append('action', 'get_messages');

        // We use sendAjaxRequest but we need to handle the success manually to populate list
        // Note: sendAjaxRequest allows a callback.

        // However, sendAjaxRequest expects 'success' in response.
        // Let's manually fetch here to avoid the toast spam if we wanted, but sendAjaxRequest is fine.
        // 'get_events' in sendAjaxRequest has special handling. Let's add 'get_messages' there too if needed or just handle it here.

        fetch('terms_api.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const list = document.getElementById('messagesList');
                    list.innerHTML = '';

                    if (data.messages && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            const item = document.createElement('div');
                            item.className = 'message-item';
                            item.style.cssText = 'background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);';
                            const msgDate = new Date(msg.created_at);
                            const formattedMsgDate = `${String(msgDate.getDate()).padStart(2, '0')}/${String(msgDate.getMonth() + 1).padStart(2, '0')}/${msgDate.getFullYear()}`;
                            item.innerHTML = `
                        <div style="flex: 1;">
                            <div style="font-weight: 500; color: #334155; font-size: 15px;">${msg.message}</div>
                            <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;"> <i class="feather icon-clock"></i> Posted on ${formattedMsgDate}</div>
                        </div>
                        <button onclick="deleteMessage(${msg.id})" class="btn btn-outline-danger" style="padding: 8px 16px; border-radius: 6px; flex-shrink: 0; font-size: 13px; color: #dc2626; border-color: #dc2626;">
                            Delete
                        </button>
                    `;
                            list.appendChild(item);
                        });
                    } else {
                        list.innerHTML = '<div style="text-align: center; padding: 20px; color: #94a3b8; background: #f8fafc; border-radius: 8px;">No special messages posted yet.</div>';
                    }
                }
            });
    }

    function handleAddMessage(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'add_message');

        sendAjaxRequest(formData, () => {
            form.reset();
            loadMessages();
        });
        return false;
    }

    function deleteMessage(id) {
        showModal({
            type: 'warning',
            title: 'Delete Message',
            message: '<p>Are you sure you want to delete this message?</p>',
            confirmText: 'Delete Message',
            confirmType: 'danger',
            onConfirm: () => {
                const formData = new FormData();
                formData.append('action', 'delete_message');
                formData.append('message_id', id);

                sendAjaxRequest(formData, () => {
                    loadMessages();
                });
            }
        });
    }

    // --- End Calendar Functions ---

    // Add Year Handler
    function handleAddYear(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'add_year');

        sendAjaxRequest(formData, () => {
            form.reset();
        });
        return false;
    }
    function handleAddTerm(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'add_term');
        sendAjaxRequest(formData, () => { form.reset(); });
        return false;
    }
    function handleEditYear(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'edit_year');
        sendAjaxRequest(formData, () => { closeEditModal(); });
        return false;
    }
    function handleDeleteYear(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'delete_year');
        sendAjaxRequest(formData, () => { closeDeleteModal(); });
        return false;
    }
    function editYear(id, name, startDate, endDate) {
        document.getElementById('edit_year_id').value = id;
        document.getElementById('edit_year_name').value = name;

        const startField = document.getElementById('edit_start_date');
        const endField = document.getElementById('edit_end_date');

        if (startField._flatpickr) startField._flatpickr.setDate(startDate || '');
        else startField.value = startDate || '';

        if (endField._flatpickr) endField._flatpickr.setDate(endDate || '');
        else endField.value = endDate || '';

        document.getElementById('editModal').style.display = 'flex';
    }
    function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }
    function deleteYear(id, name) {
        document.getElementById('delete_year_id').value = id;
        document.getElementById('delete_year_name').textContent = name;
        document.getElementById('deleteModal').style.display = 'flex';
    }
    function closeDeleteModal() { document.getElementById('deleteModal').style.display = 'none'; }
    function handleEditTerm(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'edit_term');
        sendAjaxRequest(formData, () => { closeEditTermModal(); });
        return false;
    }
    function handleDeleteTerm(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'delete_term');
        sendAjaxRequest(formData, () => { closeDeleteTermModal(); });
        return false;
    }
    function editTerm(id, name, yearId, startDate, endDate) {
        document.getElementById('edit_term_id').value = id;
        document.getElementById('edit_term_name').value = name;
        document.getElementById('edit_term_year_id').value = yearId;

        const startField = document.getElementById('edit_term_start_date');
        const endField = document.getElementById('edit_term_end_date');

        if (startField._flatpickr) startField._flatpickr.setDate(startDate || '');
        else startField.value = startDate || '';

        if (endField._flatpickr) endField._flatpickr.setDate(endDate || '');
        else endField.value = endDate || '';

        document.getElementById('editTermModal').style.display = 'flex';
    }
    function closeEditTermModal() { document.getElementById('editTermModal').style.display = 'none'; }
    function deleteTerm(id, name) {
        document.getElementById('delete_term_id').value = id;
        document.getElementById('delete_term_name').textContent = name;
        document.getElementById('deleteTermModal').style.display = 'flex';
    }
    function closeDeleteTermModal() { document.getElementById('deleteTermModal').style.display = 'none'; }
</script>

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Flatpickr for all date-picker class inputs
        flatpickr(".date-picker", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            allowInput: true
        });
    });
</script>

<?php
include '../../includes/modals.php';
include '../../includes/footer.php';
?>