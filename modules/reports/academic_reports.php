<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

$page_title = "Academic Reports";
include '../../includes/header.php';

// Filter inputs
$selected_exam = $_GET['exam_id'] ?? '';
$selected_class = $_GET['class_id'] ?? '';

// Fetch exams and classes for filters
$exams_res = mysqli_query($conn, "SELECT * FROM exams ORDER BY start_date DESC");
$classes_res = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");

// Get selected exam and class names
$exam_name = '';
$class_name = '';
$term_name = '';
$year_name = '';

if ($selected_exam) {
    $exam_res = mysqli_query($conn, "SELECT e.exam_name, t.term_name, ay.year_name 
                                      FROM exams e
                                      LEFT JOIN terms t ON e.term_id = t.term_id
                                      LEFT JOIN academic_years ay ON t.academic_year_id = ay.year_id
                                      WHERE e.exam_id = $selected_exam");
    if ($exam_row = mysqli_fetch_assoc($exam_res)) {
        $exam_name = $exam_row['exam_name'];
        $term_name = $exam_row['term_name'] ?? '';
        $year_name = $exam_row['year_name'] ?? '';
    }
}

if ($selected_class) {
    $class_res = mysqli_query($conn, "SELECT class_name, section_name FROM classes WHERE class_id = $selected_class");
    if ($class_row = mysqli_fetch_assoc($class_res)) {
        $class_name = $class_row['class_name'] . ' ' . $class_row['section_name'];
    }
}

$report_data = [];
$subjects = [];

if ($selected_exam && $selected_class) {
    // 1. Get Subjects for this class header
    $sub_sql = "SELECT subject_name FROM subjects ORDER BY subject_name";
    $sub_res = mysqli_query($conn, $sub_sql);
    while ($s = mysqli_fetch_assoc($sub_res)) {
        $subjects[] = $s['subject_name'];
    }

    // 2. Get Student Marks
    $sql_marks = "SELECT
        s.student_id,
        s.first_name,
        s.last_name,
        sub.subject_name,
        em.marks_obtained
    FROM students s
    CROSS JOIN subjects sub
    LEFT JOIN exam_results em ON s.student_id = em.student_id AND sub.subject_id = em.subject_id AND em.exam_id = $selected_exam
    WHERE s.current_class_id = $selected_class
    ORDER BY s.first_name, s.last_name, sub.subject_name";

    $res_marks = mysqli_query($conn, $sql_marks);

    // Process into pivots
    while ($row = mysqli_fetch_assoc($res_marks)) {
        $sid = $row['student_id'];
        if (!isset($report_data[$sid])) {
            $report_data[$sid] = [
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'marks' => [],
                'total' => 0,
                'count' => 0
            ];
        }
        $marks = is_numeric($row['marks_obtained']) ? floatval($row['marks_obtained']) : 0;
        $report_data[$sid]['marks'][$row['subject_name']] = $row['marks_obtained'];

        if (is_numeric($row['marks_obtained'])) {
            $report_data[$sid]['total'] += $marks;
            $report_data[$sid]['count']++;
        }
    }

    // Calculate Average and Rank
    foreach ($report_data as $sid => &$data) {
        $data['average'] = $data['count'] > 0 ? round($data['total'] / $data['count'], 1) : 0;
    }
    unset($data);

    // Sort by Total Descending
    uasort($report_data, function ($a, $b) {
        return $b['total'] <=> $a['total'];
    });
}
?>

<style>
    /* Premium Page Styles */
    .page-header {
        margin-bottom: 32px;
    }

    .page-title {
        font-size: 28px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 8px 0;
        letter-spacing: -0.02em;
    }

    .page-subtitle {
        font-size: 15px;
        color: #64748b;
        margin: 0;
        font-weight: 500;
    }

    /* Filter Card */
    .filter-card {
        background: white;
        border-radius: 12px;
        padding: 24px 28px;
        margin-bottom: 32px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
    }

    .filter-form {
        display: flex;
        gap: 20px;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .form-group-premium {
        flex: 1;
        min-width: 200px;
    }

    .form-label-premium {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 8px;
        letter-spacing: -0.01em;
    }

    .form-select-premium {
        width: 100%;
        padding: 10px 16px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        color: #0f172a;
        transition: all 0.2s ease;
        background: white;
        cursor: pointer;
    }

    .form-select-premium:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .btn-generate {
        padding: 10px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
        white-space: nowrap;
    }

    .btn-generate:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
        transform: translateY(-1px);
    }

    /* Report Card */
    .report-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .report-header {
        padding: 24px 28px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
    }

    .report-title-section {
        flex: 1;
    }

    .report-title {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 6px 0;
        letter-spacing: -0.01em;
    }

    .report-meta {
        font-size: 13px;
        color: #64748b;
        font-weight: 500;
    }

    .btn-print {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        border: 1.5px solid #e2e8f0;
        background: white;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-print:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }

    /* Premium Table */
    .table-container {
        overflow-x: auto;
        padding: 0;
    }

    .premium-report-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 800px;
    }

    .premium-report-table thead th {
        text-align: center;
        font-size: 11px;
        color: #64748b;
        font-weight: 700;
        padding: 14px 12px;
        border-bottom: 2px solid #e2e8f0;
        background: #f8fafc;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        white-space: nowrap;
    }

    .premium-report-table thead th.text-left {
        text-align: left;
        padding-left: 24px;
    }

    .premium-report-table thead th.text-left:first-child {
        border-top-left-radius: 0;
    }

    .premium-report-table tbody tr {
        transition: background 0.15s ease;
        border-bottom: 1px solid #f1f5f9;
    }

    .premium-report-table tbody tr:hover {
        background: #f8fafc;
    }

    .premium-report-table tbody tr:last-child {
        border-bottom: none;
    }

    .premium-report-table tbody td {
        padding: 16px 12px;
        font-size: 14px;
        color: #475569;
        font-weight: 500;
        text-align: center;
        vertical-align: middle;
        font-variant-numeric: tabular-nums;
    }

    .premium-report-table tbody td.text-left {
        text-align: left;
        padding-left: 24px;
    }

    .premium-report-table tbody td.rank-cell {
        font-weight: 700;
        color: #1e293b;
        width: 60px;
    }

    .premium-report-table tbody td.name-cell {
        font-weight: 600;
        color: #1e293b;
        min-width: 180px;
    }

    .premium-report-table tbody td.total-cell {
        font-weight: 700;
        color: #1e293b;
        background: #f8fafc;
    }

    .premium-report-table tbody td.avg-cell {
        font-weight: 700;
        color: #3b82f6;
        background: #eff6ff;
    }

    /* Rank Badges */
    .rank-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
    }

    .rank-1 {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: white;
    }

    .rank-2 {
        background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
        color: white;
    }

    .rank-3 {
        background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
        color: white;
    }

    .rank-other {
        background: #f1f5f9;
        color: #64748b;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 80px 24px;
    }

    .empty-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 24px;
        opacity: 0.2;
    }

    .empty-title {
        font-size: 20px;
        font-weight: 600;
        color: #64748b;
        margin: 0 0 8px 0;
    }

    .empty-text {
        font-size: 14px;
        color: #94a3b8;
        margin: 0;
    }

    /* Print Styles */
    @media print {
        .no-print {
            display: none !important;
        }

        .report-card {
            box-shadow: none !important;
            border: 1px solid #000;
        }

        .report-header {
            background: none !important;
            border-bottom: 2px solid #000;
        }

        .premium-report-table thead th {
            background: #f0f0f0 !important;
            border: 1px solid #000;
        }

        .premium-report-table tbody td {
            border: 1px solid #000;
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }

        .form-group-premium {
            width: 100%;
        }

        .btn-generate {
            width: 100%;
        }

        .report-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }

        .btn-print {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Academic Reports</h1>
    <p class="page-subtitle">Comprehensive performance analysis and student rankings</p>
</div>

<!-- Filter Card -->
<div class="filter-card">
    <div class="filter-form">
        <div class="form-group-premium">
            <label class="form-label-premium">Examination</label>
            <select id="exam-select" class="form-select-premium">
                <option value="">Select Exam</option>
                <?php
                mysqli_data_seek($exams_res, 0);
                while ($ex = mysqli_fetch_assoc($exams_res)):
                    ?>
                    <option value="<?php echo $ex['exam_id']; ?>" <?php echo $selected_exam == $ex['exam_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($ex['exam_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group-premium">
            <label class="form-label-premium">Class</label>
            <select id="class-select" class="form-select-premium">
                <option value="">Select Class</option>
                <?php
                mysqli_data_seek($classes_res, 0);
                while ($cl = mysqli_fetch_assoc($classes_res)):
                    ?>
                    <option value="<?php echo $cl['class_id']; ?>" <?php echo $selected_class == $cl['class_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cl['class_name'] . ' ' . $cl['section_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="button" id="generate-btn" class="btn-generate" onclick="generateReport()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                style="vertical-align: middle;">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            <span id="btn-text">Generate Report</span>
        </button>
    </div>
</div>

<!-- Loading Indicator -->
<div id="loading-indicator"
    style="display: none; text-align: center; padding: 60px; background: white; border-radius: 12px; margin-bottom: 32px; border: 1px solid #e2e8f0;">
    <div
        style="display: inline-block; width: 48px; height: 48px; border: 4px solid #e2e8f0; border-top-color: #3b82f6; border-radius: 50%; animation: spin 0.8s linear infinite;">
    </div>
    <p style="margin-top: 16px; color: #64748b; font-size: 14px; font-weight: 500;">Generating report...</p>
</div>

<style>
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<!-- Report Container -->
<div id="report-container">
    <?php if ($selected_exam && $selected_class): ?>
        <!-- Report Card -->
        <div class="report-card">
            <div class="report-header">
                <div class="report-title-section">
                    <h2 class="report-title">Performance Analysis</h2>
                    <div class="report-meta">
                        <?php if ($year_name): ?>
                            <?php echo htmlspecialchars($year_name); ?>
                            <?php if ($term_name): ?>
                                • <?php echo htmlspecialchars($term_name); ?>
                            <?php endif; ?>
                            •
                        <?php endif; ?>
                        <?php echo htmlspecialchars($exam_name); ?> • <?php echo htmlspecialchars($class_name); ?> •
                        <?php echo count($report_data); ?> Students
                    </div>
                </div>
                <button onclick="window.print()" class="btn-print no-print">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Print Report
                </button>
            </div>

            <div class="table-container">
                <?php if (!empty($report_data)): ?>
                    <table class="premium-report-table">
                        <thead>
                            <tr>
                                <th class="text-left">Rank</th>
                                <th class="text-left">Student Name</th>
                                <?php foreach ($subjects as $sub): ?>
                                    <th title="<?php echo htmlspecialchars($sub); ?>">
                                        <?php echo htmlspecialchars(substr($sub, 0, 3)); ?>
                                    </th>
                                <?php endforeach; ?>
                                <th>Total</th>
                                <th>Average</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rank = 1;
                            foreach ($report_data as $student):
                                $rank_class = 'rank-other';
                                if ($rank == 1)
                                    $rank_class = 'rank-1';
                                elseif ($rank == 2)
                                    $rank_class = 'rank-2';
                                elseif ($rank == 3)
                                    $rank_class = 'rank-3';
                                ?>
                                <tr>
                                    <td class="text-left rank-cell">
                                        <span class="rank-badge <?php echo $rank_class; ?>">
                                            <?php echo $rank; ?>
                                        </span>
                                    </td>
                                    <td class="text-left name-cell">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </td>
                                    <?php foreach ($subjects as $sub): ?>
                                        <td>
                                            <?php
                                            $m = $student['marks'][$sub] ?? '-';
                                            echo $m !== '-' ? $m : '<span style="color: #cbd5e1;">—</span>';
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="total-cell"><?php echo $student['total']; ?></td>
                                    <td class="avg-cell"><?php echo $student['average']; ?>%</td>
                                </tr>
                                <?php
                                $rank++;
                            endforeach;
                            ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                        <div class="empty-title">No Data Available</div>
                        <div class="empty-text">No marks have been entered for this exam and class combination</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    // On page load, try to load last selection from localStorage
    document.addEventListener('DOMContentLoaded', () => {
        const savedExamId = localStorage.getItem('last_report_exam_id');
        const savedClassId = localStorage.getItem('last_report_class_id');

        // Check if the URL already has parameters (priority over localStorage)
        const urlParams = new URLSearchParams(window.location.search);
        const urlExamId = urlParams.get('exam_id');
        const urlClassId = urlParams.get('class_id');

        let targetExamId = urlExamId || savedExamId;
        let targetClassId = urlClassId || savedClassId;

        if (targetExamId) {
            document.getElementById('exam-select').value = targetExamId;
        }
        if (targetClassId) {
            document.getElementById('class-select').value = targetClassId;
        }

        // Automatically trigger report generation if we have both values (and not already generated by PHP)
        if (!urlExamId && !urlClassId && targetExamId && targetClassId) {
            generateReport();
        }
    });

    // Auto-generate report when both filters are selected
    document.getElementById('exam-select').addEventListener('change', autoGenerateReport);
    document.getElementById('class-select').addEventListener('change', autoGenerateReport);

    function autoGenerateReport() {
        const examId = document.getElementById('exam-select').value;
        const classId = document.getElementById('class-select').value;

        if (examId && classId) {
            generateReport();
        }
    }

    function generateReport() {
        const examId = document.getElementById('exam-select').value;
        const classId = document.getElementById('class-select').value;

        if (!examId || !classId) {
            showErrorMessage('Please select both exam and class');
            return;
        }

        // Save to local storage for next time
        localStorage.setItem('last_report_exam_id', examId);
        localStorage.setItem('last_report_class_id', classId);

        const loadingIndicator = document.getElementById('loading-indicator');
        const reportContainer = document.getElementById('report-container');
        const generateBtn = document.getElementById('generate-btn');
        const btnText = document.getElementById('btn-text');

        // Show loading state
        loadingIndicator.style.display = 'block';
        reportContainer.style.opacity = '0.5';
        generateBtn.disabled = true;
        btnText.textContent = 'Generating...';

        // Fetch report data
        fetch(`get_academic_report.php?exam_id=${examId}&class_id=${classId}`)
            .then(response => response.text())
            .then(html => {
                reportContainer.innerHTML = html;
                reportContainer.style.opacity = '1';

                // Update URL without reload
                const newUrl = `${window.location.pathname}?exam_id=${examId}&class_id=${classId}`;
                window.history.pushState({}, '', newUrl);
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('Failed to generate report. Please try again.');
                reportContainer.style.opacity = '1';
            })
            .finally(() => {
                loadingIndicator.style.display = 'none';
                generateBtn.disabled = false;
                btnText.textContent = 'Generate Report';
            });
    }

    function showErrorMessage(message) {
        // Check for showToastError (common in this app) or falls back to alert
        if (typeof showToastError === 'function') {
            showToastError(message);
        } else {
            alert(message);
        }
    }
</script>

<?php include '../../includes/footer.php'; ?>