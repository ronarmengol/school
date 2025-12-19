<?php
require_once '../../includes/auth_functions.php';
check_auth();
// Teachers can view students too
check_role(['super_admin', 'admin', 'teacher', 'accountant']);

$page_title = "Manage Students";
include '../../includes/header.php';

// Check for success message
$success = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'updated') {
        $success = "Student updated successfully!";
    }
}

// Filter by Class
$class_filter = $_GET['class_id'] ?? '';

// Build Query
$sql = "SELECT s.*, c.class_name, c.section_name 
        FROM students s 
        LEFT JOIN classes c ON s.current_class_id = c.class_id 
        WHERE s.status != 'Deleted' ";

if ($class_filter) {
    $sql .= " AND s.current_class_id = " . intval($class_filter);
}

$sql .= " ORDER BY s.admission_number DESC";
$result = mysqli_query($conn, $sql);

// Get Classes for filter
$classes_res = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
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

    /* Header Actions */
    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .btn-primary-action {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        border: none;
        background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
        color: white;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(52, 152, 219, 0.25);
    }

    .btn-primary-action:hover {
        background: linear-gradient(135deg, #2980b9 0%, #1a252f 100%);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.35);
        transform: translateY(-1px);
        color: white;
    }

    /* Filter Card */
    .filter-card {
        background: white;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
    }

    .filter-group {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .filter-label {
        font-size: 14px;
        font-weight: 600;
        color: #475569;
        white-space: nowrap;
    }

    .filter-select {
        min-width: 250px;
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

    .filter-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .student-count {
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
        padding: 8px 16px;
        background: #f8fafc;
        border-radius: 6px;
    }

    /* Premium Table */
    .premium-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .premium-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .premium-table thead th {
        text-align: left;
        font-size: 12px;
        color: #64748b;
        font-weight: 700;
        padding: 16px 24px;
        border-bottom: 2px solid #f1f5f9;
        background: #f8fafc;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        cursor: pointer;
        user-select: none;
        transition: all 0.15s ease;
    }

    .premium-table thead th:hover {
        background: #f1f5f9;
        color: #475569;
    }

    .premium-table thead th.active-sort {
        background: #e0e7ff;
        color: #3730a3;
    }

    .premium-table thead th.no-sort {
        cursor: default;
    }

    .premium-table thead th.no-sort:hover {
        background: #f8fafc;
        color: #64748b;
    }

    .sort-icon {
        display: inline-block;
        margin-left: 4px;
        opacity: 0.3;
        transition: opacity 0.2s ease;
    }

    .sort-icon.active {
        opacity: 1;
    }

    .premium-table tbody tr {
        transition: background 0.15s ease;
        border-bottom: 1px solid #f1f5f9;
    }

    .premium-table tbody tr:hover {
        background: #f8fafc;
    }

    .premium-table tbody tr:last-child {
        border-bottom: none;
    }

    .premium-table tbody td {
        padding: 18px 24px;
        font-size: 14px;
        color: #475569;
        font-weight: 500;
        vertical-align: middle;
    }

    .premium-table tbody td:first-child {
        font-weight: 700;
        color: #1e293b;
        font-variant-numeric: tabular-nums;
    }

    .premium-table tbody td:nth-child(2) {
        font-weight: 600;
        color: #1e293b;
    }

    /* Status Badge */
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-active {
        background: #d1fae5;
        color: #065f46;
    }

    .status-inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    .status-suspended {
        background: #fef3c7;
        color: #92400e;
    }

    /* Action Links */
    .action-links {
        display: flex;
        gap: 12px;
    }

    .action-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        transition: all 0.2s ease;
        border: 1.5px solid;
        background: transparent;
        cursor: pointer;
        padding: 0;
    }

    .action-link svg {
        width: 18px;
        height: 18px;
    }

    .action-link-edit {
        color: #3b82f6;
        border-color: #3b82f6;
    }

    .action-link-edit:hover {
        background: #3b82f6;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
    }

    .action-link-view {
        color: #10b981;
        border-color: #10b981;
    }

    .action-link-view:hover {
        background: #10b981;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }

    .action-link-delete {
        color: #ef4444;
        border-color: #ef4444;
    }

    .action-link-delete:hover {
        background: #ef4444;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
    }

    /* Loading State */
    .loading-overlay {
        display: none;
        text-align: center;
        padding: 64px 24px;
    }

    .loading-spinner {
        display: inline-block;
        width: 48px;
        height: 48px;
        border: 4px solid #e2e8f0;
        border-top-color: #3b82f6;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    .loading-text {
        margin-top: 16px;
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 64px 24px;
        color: #94a3b8;
    }

    .empty-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 20px;
        opacity: 0.3;
    }

    .empty-title {
        font-size: 18px;
        font-weight: 600;
        color: #64748b;
        margin: 0 0 8px 0;
    }

    .empty-text {
        font-size: 14px;
        color: #94a3b8;
        margin: 0;
    }

    /* Animations */
    .fade-in {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Page Padding */
    .content-body-wrap {
        padding-bottom: 100px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .header-actions {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }

        .filter-group {
            flex-direction: column;
            align-items: flex-start;
            width: 100%;
        }

        .filter-select {
            width: 100%;
        }

        .premium-table thead th,
        .premium-table tbody td {
            padding: 12px 16px;
            font-size: 13px;
        }

        .action-links {
            flex-direction: column;
            gap: 8px;
        }

        .action-link {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<!-- Page Padding Wrapper -->
<div class="content-body-wrap">

    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">Students</h1>
        <p class="page-subtitle">Manage student records, admissions, and profiles</p>
    </div>

    <?php if ($success): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                showSuccess('<?php echo addslashes($success); ?>');
            });
        </script>
    <?php endif; ?>

    <!-- Header Actions -->
    <div class="header-actions">
        <div></div>
        <?php if (in_array($_SESSION['role'], ['super_admin', 'admin'])): ?>
            <a href="add.php" class="btn-primary-action">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
                Admit Student
            </a>
        <?php endif; ?>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <div class="filter-group">
            <label class="filter-label">Filter by Class:</label>
            <select id="class-filter" class="filter-select">
                <option value="">All Classes</option>
                <?php while ($c = mysqli_fetch_assoc($classes_res)): ?>
                    <option value="<?php echo $c['class_id']; ?>" <?php echo ($class_filter == $c['class_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <span id="student-count" class="student-count"></span>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="loading-indicator" class="loading-overlay">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading students...</p>
    </div>

    <!-- Students Table -->
    <div class="premium-card" id="students-table-container">
        <table class="premium-table">
            <thead>
                <tr>
                    <th onclick="sortBy('admission_number')" id="header-admission_number">
                        Admn No. <span id="sort-icon-admission_number" class="sort-icon">↕</span>
                    </th>
                    <th onclick="sortBy('first_name')" id="header-first_name">
                        Name <span id="sort-icon-first_name" class="sort-icon">↕</span>
                    </th>
                    <th onclick="sortBy('gender')" id="header-gender">
                        Gender <span id="sort-icon-gender" class="sort-icon">↕</span>
                    </th>
                    <th class="no-sort">Class</th>
                    <th class="no-sort">Status</th>
                    <?php if (in_array($_SESSION['role'], ['super_admin', 'admin'])): ?>
                        <th class="no-sort" style="text-align: right;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody id="students-tbody">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['admission_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['gender']); ?></td>
                            <td><?php echo htmlspecialchars($row['class_name'] . ' ' . $row['section_name']); ?></td>
                            <td>
                                <?php
                                $status_class = 'status-active';
                                if ($row['status'] == 'Inactive')
                                    $status_class = 'status-inactive';
                                if ($row['status'] == 'Suspended')
                                    $status_class = 'status-suspended';
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <?php if (in_array($_SESSION['role'], ['super_admin', 'admin'])): ?>
                                <td style="text-align: right;">
                                    <div class="action-links">
                                        <a href="view.php?id=<?php echo $row['student_id']; ?>" class="action-link action-link-view"
                                            title="View Profile">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </a>
                                        <a href="edit.php?id=<?php echo $row['student_id']; ?>" class="action-link action-link-edit"
                                            title="Edit Student">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </a>
                                        <button type="button"
                                            onclick="deleteStudent(<?php echo $row['student_id']; ?>, '<?php echo addslashes($row['first_name'] . ' ' . $row['last_name']); ?>')"
                                            class="action-link action-link-delete" title="Delete Student">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                </path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.5">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                <div class="empty-title">No Students Found</div>
                                <div class="empty-text">No students match the current filter</div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        const classFilter = document.getElementById('class-filter');
        const studentsTbody = document.getElementById('students-tbody');
        const loadingIndicator = document.getElementById('loading-indicator');
        const tableContainer = document.getElementById('students-table-container');
        const studentCount = document.getElementById('student-count');
        const canEdit = <?php echo in_array($_SESSION['role'], ['super_admin', 'admin']) ? 'true' : 'false'; ?>;

        let currentSort = 'admission_number';
        let currentOrder = 'DESC';

        // Load students on filter change
        classFilter.addEventListener('change', function () {
            loadStudents(this.value);
        });

        // Load initial count and state
        updateSortIcons();
        loadStudents(classFilter.value, false);

        function sortBy(column) {
            if (currentSort === column) {
                currentOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
            } else {
                currentSort = column;
                currentOrder = 'ASC';
            }
            updateSortIcons();
            loadStudents(classFilter.value);
        }

        function updateSortIcons() {
            ['admission_number', 'first_name', 'gender'].forEach(col => {
                const icon = document.getElementById(`sort-icon-${col}`);
                const header = document.getElementById(`header-${col}`);

                icon.innerText = '↕';
                icon.classList.remove('active');
                header.classList.remove('active-sort');
            });

            const icon = document.getElementById(`sort-icon-${currentSort}`);
            const header = document.getElementById(`header-${currentSort}`);

            if (icon) {
                icon.innerText = currentOrder === 'ASC' ? '↑' : '↓';
                icon.classList.add('active');
            }
            if (header) {
                header.classList.add('active-sort');
            }
        }

        function deleteStudent(studentId, studentName) {
            showModal({
                type: 'warning',
                title: 'Delete Student',
                message: `<p>Are you sure you want to delete student <strong>"${studentName}"</strong>?</p><p style="margin-top: 10px; color: #64748b; font-size: 14px;">This will remove them from the active list. This action can be undone by an administrator if needed.</p>`,
                confirmText: 'Delete Student',
                confirmType: 'danger',
                onConfirm: () => {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('student_id', studentId);

                    fetch('student_actions.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showToastSuccess(data.message);
                                loadStudents(classFilter.value);
                            } else {
                                showToastError(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToastError('An error occurred while deleting the student');
                        });
                }
            });
        }

        function loadStudents(classId, showLoading = true) {
            if (showLoading) {
                loadingIndicator.style.display = 'block';
                tableContainer.style.opacity = '0.5';
            }

            const url = `get_students.php?sort=${currentSort}&order=${currentOrder}${classId ? '&class_id=' + classId : ''}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderStudents(data.students);
                        updateCount(data.count, classId);
                    } else {
                        renderError('Error loading students');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    renderError('Failed to load students');
                })
                .finally(() => {
                    if (showLoading) {
                        loadingIndicator.style.display = 'none';
                        tableContainer.style.opacity = '1';
                    }
                });
        }

        function renderStudents(students) {
            if (students.length === 0) {
                studentsTbody.innerHTML = `
            <tr>
                <td colspan="6">
                    <div class="empty-state">
                        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <div class="empty-title">No Students Found</div>
                        <div class="empty-text">No students match the current filter</div>
                    </div>
                </td>
            </tr>
        `;
                return;
            }

            let html = '';
            students.forEach(student => {
                let statusClass = 'status-active';
                if (student.status === 'Inactive') statusClass = 'status-inactive';
                if (student.status === 'Suspended') statusClass = 'status-suspended';

                const className = ((student.class_name || '') + ' ' + (student.section_name || '')).trim();

                html += `
            <tr class="fade-in">
                <td>${escapeHtml(student.admission_number)}</td>
                <td>${escapeHtml(student.first_name + ' ' + student.last_name)}</td>
                <td>${escapeHtml(student.gender)}</td>
                <td>${escapeHtml(className)}</td>
                <td>
                    <span class="status-badge ${statusClass}">
                        ${escapeHtml(student.status)}
                    </span>
                </td>
                ${canEdit ? `
                <td style="text-align: right;">
                    <div class="action-links">
                        <a href="view.php?id=${student.student_id}" class="action-link action-link-view" title="View Profile">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </a>
                        <a href="edit.php?id=${student.student_id}" class="action-link action-link-edit" title="Edit Student">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        </a>
                        <button type="button" onclick="deleteStudent(${student.student_id}, '${student.first_name.replace(/'/g, "\\'")} ${student.last_name.replace(/'/g, "\\'")}')" class="action-link action-link-delete" title="Delete Student">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        </button>
                    </div>
                </td>
                ` : ''}
            </tr>
        `;
            });

            studentsTbody.innerHTML = html;
        }

        function renderError(message) {
            studentsTbody.innerHTML = `
        <tr>
            <td colspan="6">
                <div class="empty-state">
                    <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <div class="empty-title" style="color: #ef4444;">${message}</div>
                </div>
            </td>
        </tr>
    `;
        }

        function updateCount(count, classId) {
            if (classId) {
                studentCount.textContent = `${count} student${count !== 1 ? 's' : ''} in this class`;
            } else {
                studentCount.textContent = `${count} total student${count !== 1 ? 's' : ''}`;
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>

</div> <!-- End content-body-wrap -->

<?php
include '../../includes/modals.php';
include '../../includes/footer.php';
?>