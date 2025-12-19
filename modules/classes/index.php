<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

$page_title = "Manage Classes";
include '../../includes/header.php';

// Check for success message
$success = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'updated') {
        $success = "Class updated successfully!";
    }
}

// Fetch classes
$role_check_sql = "SELECT * FROM classes ORDER BY class_name, section_name";
$result = mysqli_query($conn, $role_check_sql);

// Fetch subjects for the new tab
$subjects_res = mysqli_query($conn, "SELECT * FROM subjects ORDER BY subject_name");
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

    /* Premium Tab Navigation */
    .tab-navigation {
        border-bottom: 2px solid #f1f5f9;
        margin-bottom: 32px;
        display: flex;
        gap: 8px;
    }

    .tab-button {
        padding: 14px 24px;
        cursor: pointer;
        font-weight: 600;
        font-size: 15px;
        color: #64748b;
        border-bottom: 3px solid transparent;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        position: relative;
        letter-spacing: -0.01em;
    }

    .tab-button:hover {
        color: #3498db;
        background: #f8fafc;
    }

    .tab-button.active {
        color: #3498db;
        border-bottom-color: #3498db;
        background: transparent;
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-content.active {
        display: block;
    }

    /* Section Header */
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        letter-spacing: -0.01em;
    }

    /* Premium Card */
    .premium-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    /* Premium Table */
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
    }

    /* Action Buttons */
    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 14px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13px;
        border: 1.5px solid;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-action-edit {
        background: transparent;
        color: #3b82f6;
        border-color: #3b82f6;
    }

    .btn-action-edit:hover {
        background: #3b82f6;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
    }

    .btn-action-delete {
        background: transparent;
        color: #ef4444;
        border-color: #ef4444;
    }

    .btn-action-delete:hover {
        background: #ef4444;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.25);
    }

    /* Primary Button */
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

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 64px 24px;
        color: #94a3b8;
    }

    .empty-state-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 20px;
        opacity: 0.3;
    }

    .empty-state-title {
        font-size: 18px;
        font-weight: 600;
        color: #64748b;
        margin: 0 0 8px 0;
    }

    .empty-state-text {
        font-size: 14px;
        color: #94a3b8;
        margin: 0;
    }

    /* Premium Modal */
    .premium-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 20px;
        opacity: 0;
        transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .premium-modal.active {
        opacity: 1;
    }

    .modal-dialog {
        background: white;
        width: 100%;
        max-width: 480px;
        border-radius: 16px;
        box-shadow:
            0 20px 25px -5px rgba(0, 0, 0, 0.1),
            0 10px 10px -5px rgba(0, 0, 0, 0.04),
            0 0 0 1px rgba(0, 0, 0, 0.05);
        transform: scale(0.95) translateY(-20px);
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        max-height: 90vh;
        display: flex;
        flex-direction: column;
    }

    .premium-modal.active .modal-dialog {
        transform: scale(1) translateY(0);
    }

    .modal-header-premium {
        padding: 24px 28px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .modal-title-premium {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.01em;
    }

    .modal-close-btn {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        font-size: 24px;
        line-height: 1;
    }

    .modal-close-btn:hover {
        background: #f1f5f9;
        color: #475569;
    }

    .modal-body-premium {
        padding: 28px;
        overflow-y: auto;
        flex: 1;
    }

    .form-group-premium {
        margin-bottom: 20px;
    }

    .form-label-premium {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 8px;
        letter-spacing: -0.01em;
    }

    .form-input-premium {
        width: 100%;
        padding: 12px 16px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 500;
        color: #0f172a;
        transition: all 0.2s ease;
        background: white;
    }

    .form-input-premium:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-input-premium::placeholder {
        color: #cbd5e1;
        font-weight: 400;
    }

    .modal-footer-premium {
        padding: 20px 28px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background: #fafbfc;
    }

    .btn-modal-secondary {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        border: 1.5px solid #e2e8f0;
        background: white;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-modal-secondary:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .btn-modal-primary {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
        color: white;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(52, 152, 219, 0.25);
    }

    .btn-modal-primary:hover {
        background: linear-gradient(135deg, #2980b9 0%, #1a252f 100%);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.35);
    }

    .btn-modal-danger {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.25);
    }

    .btn-modal-danger:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35);
    }

    /* Toast Notifications */
    .toast-container-premium {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 10000;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .toast-premium {
        background: white;
        color: #0f172a;
        padding: 16px 20px;
        border-radius: 12px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideInRight 0.3s ease, fadeOut 0.5s 2.5s forwards;
        min-width: 300px;
        border: 1px solid #e2e8f0;
    }

    .toast-premium.success {
        border-left: 4px solid #10b981;
    }

    .toast-premium.error {
        border-left: 4px solid #ef4444;
    }

    .toast-icon {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    .toast-message {
        flex: 1;
        font-size: 14px;
        font-weight: 500;
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }

        to {
            opacity: 0;
            visibility: hidden;
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-title {
            font-size: 24px;
        }

        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }

        .premium-table thead th,
        .premium-table tbody td {
            padding: 12px 16px;
            font-size: 13px;
        }

        .tab-navigation {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .tab-button {
            white-space: nowrap;
        }
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Classes & Subjects</h1>
    <p class="page-subtitle">Manage academic classes, sections, and subject offerings</p>
</div>

<?php if ($success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            showToast('<?php echo addslashes($success); ?>', 'success');
        });
    </script>
<?php endif; ?>

<!-- Tab Navigation -->
<div class="tab-navigation">
    <button class="tab-button active" onclick="switchTab('classes')">Classes</button>
    <button class="tab-button" onclick="switchTab('subjects')">Subjects</button>
</div>

<!-- CLASSES TAB -->
<div id="tab-classes" class="tab-content active">
    <div class="section-header">
        <h2 class="section-title">Classes List</h2>
        <?php if (in_array($_SESSION['role'], ['super_admin', 'admin'])): ?>
            <a href="add.php" class="btn-primary-action">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add New Class
            </a>
        <?php endif; ?>
    </div>

    <div class="premium-card">
        <table class="premium-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Class Name</th>
                    <th>Section</th>
                    <?php if (in_array($_SESSION['role'], ['super_admin', 'admin'])): ?>
                        <th style="text-align: right;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['class_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['section_name']); ?></td>
                            <?php if (in_array($_SESSION['role'], ['super_admin', 'admin'])): ?>
                                <td style="text-align: right;">
                                    <a href="edit.php?id=<?php echo $row['class_id']; ?>" class="btn-action btn-action-edit">
                                        Edit
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <div class="empty-state-title">No Classes Found</div>
                                <div class="empty-state-text">Get started by adding your first class</div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- SUBJECTS TAB -->
<div id="tab-subjects" class="tab-content">
    <div class="section-header">
        <h2 class="section-title">Subjects List</h2>
        <button class="btn-primary-action" onclick="openAddModal()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add Subject
        </button>
    </div>

    <div class="premium-card">
        <table class="premium-table" id="subjectsTable">
            <thead>
                <tr>
                    <th>Subject Name</th>
                    <th>Code</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($subjects_res) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($subjects_res)): ?>
                        <tr id="row-<?php echo $row['subject_id']; ?>">
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                            <td style="text-align: right;">
                                <button class="btn-action btn-action-edit"
                                    onclick="openEditModal(<?php echo $row['subject_id']; ?>)">
                                    Edit
                                </button>
                                <button class="btn-action btn-action-delete"
                                    onclick="deleteSubject(<?php echo $row['subject_id']; ?>)">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">
                            <div class="empty-state">
                                <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <div class="empty-state-title">No Subjects Found</div>
                                <div class="empty-state-text">Add subjects to build your curriculum</div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Subject Modal -->
<div id="subjectModal" class="premium-modal">
    <div class="modal-dialog">
        <div class="modal-header-premium">
            <h3 class="modal-title-premium" id="modalTitle">Add Subject</h3>
            <button class="modal-close-btn" onclick="closeModal()" aria-label="Close">&times;</button>
        </div>
        <form id="subjectForm">
            <div class="modal-body-premium">
                <input type="hidden" name="action" id="formAction" value="add_subject">
                <input type="hidden" name="subject_id" id="subjectId">

                <div class="form-group-premium">
                    <label class="form-label-premium">Subject Name</label>
                    <input type="text" name="subject_name" id="subjectName" class="form-input-premium" required
                        placeholder="e.g. Mathematics">
                </div>

                <div class="form-group-premium">
                    <label class="form-label-premium">Subject Code (Optional)</label>
                    <input type="text" name="subject_code" id="subjectCode" class="form-input-premium"
                        placeholder="e.g. MATH101">
                </div>
            </div>
            <div class="modal-footer-premium">
                <button type="button" class="btn-modal-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" id="saveSubjectBtn" class="btn-modal-primary">Save Subject</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm Modal -->
<div id="confirmModal" class="premium-modal">
    <div class="modal-dialog" style="max-width: 420px;">
        <div class="modal-header-premium">
            <h3 class="modal-title-premium">Confirm Action</h3>
            <button class="modal-close-btn" onclick="closeConfirmModal()" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body-premium">
            <p id="confirmMessage" style="color: #475569; font-size: 15px; margin: 0; line-height: 1.6;">Are you sure
                you want to proceed?</p>
        </div>
        <div class="modal-footer-premium">
            <button type="button" class="btn-modal-secondary" onclick="closeConfirmModal()">Cancel</button>
            <button type="button" id="confirmBtn" class="btn-modal-danger">Confirm</button>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container-premium"></div>

<script>
    // Tab Switching
    function switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));

        document.getElementById('tab-' + tabName).classList.add('active');

        const buttons = document.querySelectorAll('.tab-button');
        if (tabName === 'classes') buttons[0].classList.add('active');
        if (tabName === 'subjects') buttons[1].classList.add('active');
    }

    // Check URL for tab param
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab') === 'subjects') {
        switchTab('subjects');
    }

    // Toast Notifications
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast-premium ${type}`;

        const icon = type === 'success'
            ? '<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>'
            : '<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';

        toast.innerHTML = icon + '<span class="toast-message">' + message + '</span>';
        container.appendChild(toast);
        setTimeout(() => { toast.remove(); }, 3000);
    }

    // Modal Functions
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.style.display = 'flex';
        requestAnimationFrame(() => {
            modal.classList.add('active');
        });
    }

    function closeModalById(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 250);
    }

    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Add Subject';
        document.getElementById('formAction').value = 'add_subject';
        document.getElementById('subjectId').value = '';
        document.getElementById('subjectName').value = '';
        document.getElementById('subjectCode').value = '';
        openModal('subjectModal');
        setTimeout(() => document.getElementById('subjectName').focus(), 300);
    }

    function openEditModal(id) {
        const formData = new FormData();
        formData.append('action', 'get_subject');
        formData.append('subject_id', id);

        fetch('../academics/subjects_api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalTitle').innerText = 'Edit Subject';
                    document.getElementById('formAction').value = 'edit_subject';
                    document.getElementById('subjectId').value = data.data.subject_id;
                    document.getElementById('subjectName').value = data.data.subject_name;
                    document.getElementById('subjectCode').value = data.data.subject_code;
                    openModal('subjectModal');
                    setTimeout(() => document.getElementById('subjectName').focus(), 300);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(err => showToast('Network error occurred.', 'error'));
    }

    function closeModal() {
        closeModalById('subjectModal');
    }

    function closeConfirmModal() {
        closeModalById('confirmModal');
    }

    // Delete Subject
    let deleteId = null;
    function deleteSubject(id) {
        deleteId = id;
        document.getElementById('confirmMessage').innerText = 'Are you sure you want to delete this subject? This action cannot be undone.';
        openModal('confirmModal');
    }

    document.getElementById('confirmBtn').onclick = function () {
        if (!deleteId) return;
        const formData = new FormData();
        formData.append('action', 'delete_subject');
        formData.append('subject_id', deleteId);

        fetch('../academics/subjects_api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                closeConfirmModal();
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.href = 'index.php?tab=subjects', 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(err => showToast('Delete failed.', 'error'));
    };

    // Form Submit
    document.getElementById('subjectForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('../academics/subjects_api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeModal();
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.href = 'index.php?tab=subjects', 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(err => showToast('Operation failed.', 'error'));
    });

    // Close modal on backdrop click
    document.querySelectorAll('.premium-modal').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('active');
                setTimeout(() => {
                    this.style.display = 'none';
                }, 250);
            }
        });
    });

    // ESC key to close modals
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.premium-modal.active').forEach(modal => {
                modal.classList.remove('active');
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 250);
            });
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>