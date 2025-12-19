<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']);

$page_title = "Student Promotion";
include '../../includes/header.php';

// Fetch Academic Years
$years_res = mysqli_query($conn, "SELECT * FROM academic_years ORDER BY start_date DESC");
$years = mysqli_fetch_all($years_res, MYSQLI_ASSOC);

// Fetch Classes
$classes_res = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
$classes = mysqli_fetch_all($classes_res, MYSQLI_ASSOC);

// Fetch recent promotion batches
$batches_res = mysqli_query($conn, "
    SELECT pb.*, 
           y1.year_name as from_year, 
           y2.year_name as to_year,
           u.full_name as promoted_by_name
    FROM promotion_batches pb
    LEFT JOIN academic_years y1 ON pb.from_year_id = y1.year_id
    LEFT JOIN academic_years y2 ON pb.to_year_id = y2.year_id
    LEFT JOIN users u ON pb.promoted_by = u.user_id
    ORDER BY pb.promotion_date DESC
    LIMIT 10
");
$batches = mysqli_fetch_all($batches_res, MYSQLI_ASSOC);
?>

<style>
    .promotion-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .promotion-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e2e8f0;
    }

    .promotion-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
    }

    .wizard-steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
        position: relative;
    }

    .wizard-step {
        flex: 1;
        text-align: center;
        position: relative;
    }

    .wizard-step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-weight: bold;
        transition: all 0.3s;
    }

    .wizard-step.active .wizard-step-circle {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .wizard-step.completed .wizard-step-circle {
        background: #10b981;
        color: white;
    }

    .wizard-step-label {
        font-size: 14px;
        color: #64748b;
        font-weight: 500;
    }

    .wizard-step.active .wizard-step-label {
        color: #667eea;
        font-weight: 600;
    }

    .step-content {
        display: none;
    }

    .step-content.active {
        display: block;
    }

    .student-preview-table {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }

    .batch-item {
        padding: 15px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .batch-item.rolled-back {
        opacity: 0.6;
        background: #fee2e2;
    }

    .info-box {
        background: #eff6ff;
        border-left: 4px solid #3b82f6;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .warning-box {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .success-box {
        background: #d1fae5;
        border-left: 4px solid #10b981;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .btn-wizard {
        padding: 12px 24px;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-wizard-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-wizard-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .btn-wizard-secondary {
        background: #e2e8f0;
        color: #475569;
    }

    .btn-wizard-secondary:hover {
        background: #cbd5e1;
    }

    .class-mapping {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        gap: 15px;
        align-items: center;
        padding: 15px;
        background: #f8fafc;
        border-radius: 8px;
        margin-bottom: 10px;
    }

    .arrow-icon {
        color: #667eea;
        font-size: 24px;
    }

    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.2s ease-out;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-dialog {
        background: white;
        border-radius: 16px;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s ease-out;
        overflow: hidden;
    }

    .modal-header {
        padding: 24px 24px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .modal-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }

    .modal-icon.warning {
        background: #fef3c7;
        color: #f59e0b;
    }

    .modal-icon.error {
        background: #fee2e2;
        color: #ef4444;
    }

    .modal-icon.success {
        background: #d1fae5;
        color: #10b981;
    }

    .modal-icon.info {
        background: #dbeafe;
        color: #3b82f6;
    }

    .modal-title {
        font-size: 20px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .modal-body {
        padding: 0 24px 24px;
        color: #475569;
        line-height: 1.6;
    }

    .modal-footer {
        padding: 16px 24px;
        background: #f8fafc;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    .modal-btn {
        padding: 10px 20px;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 14px;
    }

    .modal-btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .modal-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .modal-btn-danger {
        background: #ef4444;
        color: white;
    }

    .modal-btn-danger:hover {
        background: #dc2626;
        transform: translateY(-1px);
    }

    .modal-btn-secondary {
        background: white;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    .modal-btn-secondary:hover {
        background: #f8fafc;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
</style>

<div style="max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="margin: 0;">🎓 Student Promotion System</h2>
    </div>

    <!-- Promotion Wizard -->
    <div class="promotion-card">
        <div class="promotion-header">
            <div class="promotion-icon">📊</div>
            <div>
                <h3 style="margin: 0;">Year-End Promotion Wizard</h3>
                <p style="margin: 5px 0 0; color: #64748b;">Promote students to the next academic year</p>
            </div>
        </div>

        <!-- Wizard Steps -->
        <div class="wizard-steps">
            <div class="wizard-step active" id="step-indicator-1">
                <div class="wizard-step-circle">1</div>
                <div class="wizard-step-label">Select Years</div>
            </div>
            <div class="wizard-step" id="step-indicator-2">
                <div class="wizard-step-circle">2</div>
                <div class="wizard-step-label">Class Mapping</div>
            </div>
            <div class="wizard-step" id="step-indicator-2.5">
                <div class="wizard-step-circle">2.5</div>
                <div class="wizard-step-label">Select Students</div>
            </div>
            <div class="wizard-step" id="step-indicator-3">
                <div class="wizard-step-circle">3</div>
                <div class="wizard-step-label">Review</div>
            </div>
            <div class="wizard-step" id="step-indicator-4">
                <div class="wizard-step-circle">4</div>
                <div class="wizard-step-label">Complete</div>
            </div>
        </div>

        <!-- Step 1: Select Years -->
        <div class="step-content active" id="step-1">
            <div class="info-box">
                <strong>ℹ️ Step 1:</strong> Select the current academic year and the next academic year for promotion.
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">From Academic Year (Current)</label>
                    <select id="from_year" class="form-control" required>
                        <option value="">Select Current Year</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo $year['year_id']; ?>" <?php echo $year['is_active'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year['year_name']); ?>
                                <?php echo $year['is_active'] ? ' (Active)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">To Academic Year (Next)</label>
                    <select id="to_year" class="form-control" required>
                        <option value="">Select Next Year</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo $year['year_id']; ?>">
                                <?php echo htmlspecialchars($year['year_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Batch Name (Optional)</label>
                <input type="text" id="batch_name" class="form-control" placeholder="e.g., 2024 Year-End Promotion">
            </div>

            <div class="action-buttons">
                <button class="btn-wizard btn-wizard-primary" onclick="goToStep(2)">Next: Class Mapping →</button>
            </div>
        </div>

        <!-- Step 2: Class Mapping -->
        <div class="step-content" id="step-2">
            <div class="info-box">
                <strong>ℹ️ Step 2:</strong> Map each current class to their next year's class. Students in graduating
                classes can be marked as Alumni.
            </div>

            <div id="class-mappings-container">
                <!-- Will be populated dynamically -->
            </div>

            <div class="action-buttons">
                <button class="btn-wizard btn-wizard-secondary" onclick="goToStep(1)">← Back</button>
                <button class="btn-wizard btn-wizard-primary" onclick="goToStep(2.5)">Next: Select Students →</button>
            </div>
        </div>

        <!-- Step 2.5: Select Individual Students -->
        <div class="step-content" id="step-2.5">
            <div class="info-box">
                <strong>ℹ️ Step 2.5:</strong> Select which students should be retained (repeat the year). Unchecked
                students will be promoted according to the class mapping.
            </div>

            <div id="student-selection-container">
                <!-- Will be populated dynamically -->
            </div>

            <div class="action-buttons">
                <button class="btn-wizard btn-wizard-secondary" onclick="goToStep(2)">← Back</button>
                <button class="btn-wizard btn-wizard-primary" onclick="goToStep(3)">Next: Review →</button>
            </div>
        </div>

        <!-- Step 3: Review & Confirm -->
        <div class="step-content" id="step-3">
            <div class="warning-box">
                <strong>⚠️ Review Carefully:</strong> This will update all selected students. Make sure the mappings are
                correct before proceeding.
            </div>

            <div id="review-summary">
                <!-- Will be populated dynamically -->
            </div>

            <div id="student-preview" class="student-preview-table" style="margin-top: 20px;">
                <!-- Will be populated dynamically -->
            </div>

            <div class="action-buttons">
                <button class="btn-wizard btn-wizard-secondary" onclick="goToStep(2)">← Back</button>
                <button class="btn-wizard btn-wizard-primary" onclick="executePromotion()">Execute Promotion ✓</button>
            </div>
        </div>

        <!-- Step 4: Complete -->
        <div class="step-content" id="step-4">
            <div class="success-box" id="completion-message">
                <strong>✓ Promotion Complete!</strong>
                <p id="completion-details" style="margin: 10px 0 0;"></p>
            </div>

            <div class="action-buttons">
                <button class="btn-wizard btn-wizard-primary" onclick="location.reload()">Start New Promotion</button>
                <a href="../students/index.php" class="btn-wizard btn-wizard-secondary"
                    style="text-decoration: none; display: inline-block;">View Students</a>
            </div>
        </div>
    </div>

    <!-- Recent Promotion Batches -->
    <div class="promotion-card">
        <h3>📜 Recent Promotion Batches</h3>
        <?php if (count($batches) > 0): ?>
            <?php foreach ($batches as $batch): ?>
                <div class="batch-item <?php echo $batch['is_rolled_back'] ? 'rolled-back' : ''; ?>">
                    <div>
                        <strong><?php echo htmlspecialchars($batch['batch_name'] ?? ''); ?></strong>
                        <div style="font-size: 14px; color: #64748b; margin-top: 5px;">
                            <?php echo htmlspecialchars($batch['from_year'] ?? ''); ?> →
                            <?php echo htmlspecialchars($batch['to_year'] ?? ''); ?>
                            | <?php echo $batch['students_promoted']; ?> students
                            | By: <?php echo htmlspecialchars($batch['promoted_by_name'] ?? 'Unknown'); ?>
                            | <?php echo date('d M Y, H:i', strtotime($batch['promotion_date'])); ?>
                        </div>
                        <?php if ($batch['is_rolled_back']): ?>
                            <span style="color: #ef4444; font-size: 12px;">⚠️ ROLLED BACK</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!$batch['is_rolled_back']): ?>
                        <button class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;"
                            onclick="rollbackPromotion(<?php echo $batch['batch_id']; ?>, '<?php echo htmlspecialchars($batch['batch_name'] ?? '', ENT_QUOTES); ?>')">
                            Rollback
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #64748b; text-align: center; padding: 20px;">No promotion batches yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Dialog -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-icon" id="modalIcon">⚠️</div>
            <h3 class="modal-title" id="modalTitle">Confirm Action</h3>
        </div>
        <div class="modal-body" id="modalBody">
            Are you sure you want to proceed?
        </div>
        <div class="modal-footer" id="modalFooter">
            <button class="modal-btn modal-btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="modal-btn modal-btn-primary" id="modalConfirmBtn">Confirm</button>
        </div>
    </div>
</div>

<script>
    let currentStep = 1;
    let classMappings = {};
    let retainedStudents = new Set(); // Track students who will be retained
    let allClasses = <?php echo json_encode($classes); ?>;
    let modalCallback = null;

    // Modal Functions
    function showModal(options) {
        const modal = document.getElementById('modalOverlay');
        const icon = document.getElementById('modalIcon');
        const title = document.getElementById('modalTitle');
        const body = document.getElementById('modalBody');
        const footer = document.getElementById('modalFooter');

        // Set icon
        icon.className = 'modal-icon ' + (options.type || 'info');
        icon.textContent = options.icon || '⚠️';

        // Set title and body
        title.textContent = options.title || 'Confirm';
        body.innerHTML = options.message || '';

        // Set buttons
        footer.innerHTML = '';

        if (options.buttons) {
            options.buttons.forEach(btn => {
                const button = document.createElement('button');
                button.className = 'modal-btn modal-btn-' + (btn.type || 'secondary');
                button.textContent = btn.text;
                button.onclick = () => {
                    if (btn.onClick) btn.onClick();
                    closeModal();
                };
                footer.appendChild(button);
            });
        } else {
            // Default buttons
            const cancelBtn = document.createElement('button');
            cancelBtn.className = 'modal-btn modal-btn-secondary';
            cancelBtn.textContent = 'Cancel';
            cancelBtn.onclick = closeModal;
            footer.appendChild(cancelBtn);

            const confirmBtn = document.createElement('button');
            confirmBtn.className = 'modal-btn modal-btn-' + (options.confirmType || 'primary');
            confirmBtn.textContent = options.confirmText || 'Confirm';
            confirmBtn.onclick = () => {
                if (options.onConfirm) options.onConfirm();
                closeModal();
            };
            footer.appendChild(confirmBtn);
        }

        modal.classList.add('active');
    }

    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('active');
    }

    function showError(message) {
        showModal({
            type: 'error',
            icon: '✗',
            title: 'Error',
            message: message,
            buttons: [{
                text: 'OK',
                type: 'primary',
                onClick: () => { }
            }]
        });
    }

    function showSuccess(message, onClose) {
        showModal({
            type: 'success',
            icon: '✓',
            title: 'Success',
            message: message,
            buttons: [{
                text: 'OK',
                type: 'primary',
                onClick: onClose || (() => { })
            }]
        });
    }

    function showInfo(message) {
        showModal({
            type: 'info',
            icon: 'ℹ️',
            title: 'Information',
            message: message,
            buttons: [{
                text: 'OK',
                type: 'primary',
                onClick: () => { }
            }]
        });
    }

    function showConfirm(options) {
        showModal({
            type: options.type || 'warning',
            icon: options.icon || '⚠️',
            title: options.title || 'Confirm',
            message: options.message,
            confirmText: options.confirmText || 'Confirm',
            confirmType: options.confirmType || 'primary',
            onConfirm: options.onConfirm
        });
    }

    // Close modal on overlay click
    document.addEventListener('click', function (e) {
        if (e.target.id === 'modalOverlay') {
            closeModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    function goToStep(step) {
        // Validation
        if (step === 2 && currentStep === 1) {
            const fromYear = document.getElementById('from_year').value;
            const toYear = document.getElementById('to_year').value;

            if (!fromYear || !toYear) {
                showError('Please select both academic years before proceeding.');
                return;
            }

            if (fromYear === toYear) {
                showError('Please select different years for promotion.');
                return;
            }

            generateClassMappings();
        }

        if (step === 2.5 && currentStep === 2) {
            generateStudentSelection();
        }

        if (step === 3 && currentStep === 2.5) {
            generateReviewSummary();
        }

        // Hide all steps
        const stepIds = [1, 2, 2.5, 3, 4];
        stepIds.forEach(s => {
            const stepEl = document.getElementById('step-' + s);
            const indicatorEl = document.getElementById('step-indicator-' + s);
            if (stepEl) stepEl.classList.remove('active');
            if (indicatorEl) indicatorEl.classList.remove('active', 'completed');
        });

        // Show current step
        document.getElementById('step-' + step).classList.add('active');
        document.getElementById('step-indicator-' + step).classList.add('active');

        // Mark previous steps as completed
        stepIds.forEach(s => {
            if (s < step) {
                const indicatorEl = document.getElementById('step-indicator-' + s);
                if (indicatorEl) indicatorEl.classList.add('completed');
            }
        });

        currentStep = step;
    }

    function generateClassMappings() {
        const container = document.getElementById('class-mappings-container');
        container.innerHTML = '';

        allClasses.forEach(cls => {
            const mapping = document.createElement('div');
            mapping.className = 'class-mapping';
            mapping.innerHTML = `
            <div>
                <strong>${cls.class_name} ${cls.section_name || ''}</strong>
                <div style="font-size: 12px; color: #64748b;" id="count-${cls.class_id}">Loading...</div>
            </div>
            <div class="arrow-icon">→</div>
            <div>
                <select class="form-control" id="mapping-${cls.class_id}" onchange="updateMapping(${cls.class_id})">
                    <option value="">Select Next Class</option>
                    ${allClasses.map(c => `<option value="${c.class_id}">${c.class_name} ${c.section_name || ''}</option>`).join('')}
                    <option value="alumni">Mark as Alumni (Graduating)</option>
                    <option value="retain">Retain in Same Class</option>
                </select>
            </div>
        `;
            container.appendChild(mapping);

            // Fetch student count for this class
            fetchStudentCount(cls.class_id);
        });
    }

    function fetchStudentCount(classId) {
        const fromYear = document.getElementById('from_year').value;
        fetch(`promotion_api.php?action=count_students&class_id=${classId}&year_id=${fromYear}`)
            .then(r => r.json())
            .then(data => {
                document.getElementById('count-' + classId).textContent = `${data.count} students`;
            });
    }

    function updateMapping(classId) {
        const select = document.getElementById('mapping-' + classId);
        classMappings[classId] = select.value;
    }

    function generateStudentSelection() {
        const container = document.getElementById('student-selection-container');
        container.innerHTML = '<p style="text-align: center; color: #64748b;">Loading students...</p>';

        const fromYear = document.getElementById('from_year').value;

        // Fetch all students for the selected year
        fetch(`promotion_api.php?action=get_all_students&year_id=${fromYear}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.students || data.students.length === 0) {
                    container.innerHTML = '<p style="text-align: center; padding: 20px; color: #64748b;">No students found</p>';
                    return;
                }

                // Group students by class
                const studentsByClass = {};
                data.students.forEach(student => {
                    const classKey = student.class_id;
                    if (!studentsByClass[classKey]) {
                        studentsByClass[classKey] = {
                            className: student.class_name + ' ' + (student.section_name || ''),
                            students: []
                        };
                    }
                    studentsByClass[classKey].students.push(student);
                });

                // Build HTML
                let html = '';

                Object.keys(studentsByClass).forEach(classId => {
                    const classData = studentsByClass[classId];
                    const mapping = classMappings[classId];

                    // Skip classes with no mapping or alumni/retain mapping
                    if (!mapping || mapping === 'alumni') {
                        return; // These don't need individual selection
                    }

                    html += `
                    <div style="margin-bottom: 30px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px;">
                            <h5 style="margin: 0; font-size: 16px;">${classData.className}</h5>
                            <p style="margin: 5px 0 0; font-size: 14px; opacity: 0.9;">
                                ${classData.students.length} students • 
                                <span id="retain-count-${classId}">0</span> will be retained
                            </p>
                        </div>
                        <div style="padding: 15px;">
                            <div style="margin-bottom: 10px; display: flex; gap: 10px;">
                                <button class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;" onclick="selectAllStudents(${classId}, true)">Select All</button>
                                <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="selectAllStudents(${classId}, false)">Deselect All</button>
                            </div>
                            <div style="background: #fef3c7; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px;">
                                <strong>Note:</strong> Check the box for students who should <strong>repeat this class</strong>. Unchecked students will be promoted.
                            </div>
                            <table class="table" style="margin: 0;">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">Retain?</th>
                                        <th>Student Name</th>
                                        <th>Admission No.</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;

                    classData.students.forEach(student => {
                        const studentKey = `${student.student_id}`;
                        const isChecked = retainedStudents.has(studentKey) ? 'checked' : '';
                        html += `
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" 
                                       id="retain-${student.student_id}" 
                                       data-class-id="${classId}"
                                       data-student-id="${student.student_id}"
                                       ${isChecked}
                                       onchange="toggleStudentRetention(${student.student_id}, ${classId})"
                                       style="width: 18px; height: 18px; cursor: pointer;">
                            </td>
                            <td>${student.first_name} ${student.last_name}</td>
                            <td style="color: #64748b;">${student.admission_number}</td>
                        </tr>
                    `;
                    });

                    html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                });

                if (html === '') {
                    container.innerHTML = '<div class="info-box"><strong>ℹ️ No Selection Needed:</strong> All classes are either graduating or being retained as a group.</div>';
                } else {
                    container.innerHTML = html;
                    // Update counts
                    Object.keys(studentsByClass).forEach(classId => {
                        updateRetainCount(classId);
                    });
                }
            })
            .catch(err => {
                container.innerHTML = '<p style="text-align: center; padding: 20px; color: #ef4444;">Error loading students</p>';
                console.error(err);
            });
    }

    function toggleStudentRetention(studentId, classId) {
        const checkbox = document.getElementById('retain-' + studentId);
        const studentKey = `${studentId}`;

        if (checkbox.checked) {
            retainedStudents.add(studentKey);
        } else {
            retainedStudents.delete(studentKey);
        }

        updateRetainCount(classId);
    }

    function selectAllStudents(classId, select) {
        const checkboxes = document.querySelectorAll(`input[data-class-id="${classId}"]`);
        checkboxes.forEach(checkbox => {
            checkbox.checked = select;
            const studentId = checkbox.getAttribute('data-student-id');
            const studentKey = `${studentId}`;

            if (select) {
                retainedStudents.add(studentKey);
            } else {
                retainedStudents.delete(studentKey);
            }
        });

        updateRetainCount(classId);
    }

    function updateRetainCount(classId) {
        const checkboxes = document.querySelectorAll(`input[data-class-id="${classId}"]:checked`);
        const countEl = document.getElementById('retain-count-' + classId);
        if (countEl) {
            countEl.textContent = checkboxes.length;
        }
    }

    function generateReviewSummary() {
        const fromYear = document.getElementById('from_year').options[document.getElementById('from_year').selectedIndex].text;
        const toYear = document.getElementById('to_year').options[document.getElementById('to_year').selectedIndex].text;

        const summary = document.getElementById('review-summary');
        summary.innerHTML = `
        <h4>Promotion Summary</h4>
        <p><strong>From:</strong> ${fromYear} <strong>To:</strong> ${toYear}</p>
        <div id="stats-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
            <!-- Stats will be populated here -->
        </div>
    `;

        // Fetch preview data
        const fromYearId = document.getElementById('from_year').value;
        fetch('promotion_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'preview',
                from_year: fromYearId,
                mappings: classMappings,
                retained_students: Array.from(retainedStudents)
            })
        })
            .then(r => r.json())
            .then(data => {
                const preview = document.getElementById('student-preview');
                if (data.students && data.students.length > 0) {
                    // Group students by action type
                    const promoted = [];
                    const retained = [];
                    const graduated = [];

                    data.students.forEach(s => {
                        if (s.action.includes('Alumni') || s.action.includes('🎓')) {
                            graduated.push(s);
                        } else if (s.action.includes('Retain') || s.action.includes('🔄')) {
                            retained.push(s);
                        } else {
                            promoted.push(s);
                        }
                    });

                    // Show statistics
                    const statsContainer = document.getElementById('stats-container');
                    statsContainer.innerHTML = `
                <div style="background: #d1fae5; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981;">
                    <div style="font-size: 24px; font-weight: bold; color: #059669;">${promoted.length}</div>
                    <div style="color: #047857; font-size: 14px;">⬆️ Students Promoted</div>
                </div>
                <div style="background: #fef3c7; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                    <div style="font-size: 24px; font-weight: bold; color: #d97706;">${retained.length}</div>
                    <div style="color: #b45309; font-size: 14px;">🔄 Students Retained</div>
                </div>
                <div style="background: #ddd6fe; padding: 15px; border-radius: 8px; border-left: 4px solid #7c3aed;">
                    <div style="font-size: 24px; font-weight: bold; color: #6d28d9;">${graduated.length}</div>
                    <div style="color: #5b21b6; font-size: 14px;">🎓 Students Graduating</div>
                </div>
                <div style="background: #e0e7ff; padding: 15px; border-radius: 8px; border-left: 4px solid #4f46e5;">
                    <div style="font-size: 24px; font-weight: bold; color: #4338ca;">${data.students.length}</div>
                    <div style="color: #3730a3; font-size: 14px;">📊 Total Students</div>
                </div>
            `;

                    // Build grouped table
                    let html = '';

                    // Promoted students
                    if (promoted.length > 0) {
                        html += `
                    <div style="margin-bottom: 20px;">
                        <h5 style="color: #059669; margin-bottom: 10px;">⬆️ Students Being Promoted (${promoted.length})</h5>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Current Class</th>
                                    <th>New Class</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                        promoted.forEach(s => {
                            html += `<tr>
                        <td>${s.first_name} ${s.last_name} <span style="color: #64748b;">(${s.admission_number})</span></td>
                        <td>${s.current_class}</td>
                        <td><span style="background: #d1fae5; color: #059669; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">${s.action.replace('⬆️ Promote to ', '')}</span></td>
                    </tr>`;
                        });
                        html += '</tbody></table></div>';
                    }

                    // Retained students
                    if (retained.length > 0) {
                        html += `
                    <div style="margin-bottom: 20px;">
                        <h5 style="color: #d97706; margin-bottom: 10px;">🔄 Students Being Retained (${retained.length})</h5>
                        <div style="background: #fef3c7; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b; margin-bottom: 10px;">
                            <strong>Note:</strong> These students will repeat their current class in the next academic year.
                        </div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Class (Repeating)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                        retained.forEach(s => {
                            html += `<tr>
                        <td>${s.first_name} ${s.last_name} <span style="color: #64748b;">(${s.admission_number})</span></td>
                        <td>${s.current_class}</td>
                        <td><span style="background: #fef3c7; color: #d97706; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">🔄 Retained</span></td>
                    </tr>`;
                        });
                        html += '</tbody></table></div>';
                    }

                    // Graduating students
                    if (graduated.length > 0) {
                        html += `
                    <div style="margin-bottom: 20px;">
                        <h5 style="color: #6d28d9; margin-bottom: 10px;">🎓 Students Graduating (${graduated.length})</h5>
                        <div style="background: #ddd6fe; padding: 15px; border-radius: 8px; border-left: 4px solid #7c3aed; margin-bottom: 10px;">
                            <strong>Note:</strong> These students will be marked as Alumni and removed from active student lists.
                        </div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Final Class</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                        graduated.forEach(s => {
                            html += `<tr>
                        <td>${s.first_name} ${s.last_name} <span style="color: #64748b;">(${s.admission_number})</span></td>
                        <td>${s.current_class}</td>
                        <td><span style="background: #ddd6fe; color: #6d28d9; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">🎓 Alumni</span></td>
                    </tr>`;
                        });
                        html += '</tbody></table></div>';
                    }

                    preview.innerHTML = html;
                } else {
                    preview.innerHTML = '<p style="text-align: center; padding: 20px; color: #64748b;">No students to promote</p>';
                }
            });
    }

    function executePromotion() {
        showConfirm({
            type: 'warning',
            icon: '⚠️',
            title: 'Execute Promotion',
            message: '<p><strong>Are you sure you want to execute this promotion?</strong></p><p>This will update all student records and cannot be easily undone.</p>',
            confirmText: 'Execute Promotion',
            confirmType: 'primary',
            onConfirm: () => {
                const fromYear = document.getElementById('from_year').value;
                const toYear = document.getElementById('to_year').value;
                const batchName = document.getElementById('batch_name').value || `Promotion ${new Date().toLocaleDateString()}`;

                fetch('promotion_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'execute',
                        from_year: fromYear,
                        to_year: toYear,
                        batch_name: batchName,
                        mappings: classMappings,
                        retained_students: Array.from(retainedStudents)
                    })
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('completion-details').textContent =
                                `Successfully promoted ${data.count} students from ${data.from_year} to ${data.to_year}`;
                            goToStep(4);
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(err => {
                        showError('An error occurred while processing the promotion. Please try again.');
                        console.error(err);
                    });
            }
        });
    }

    function rollbackPromotion(batchId, batchName) {
        showConfirm({
            type: 'warning',
            icon: '🔄',
            title: 'Rollback Promotion',
            message: `<p><strong>Are you sure you want to rollback the promotion batch:</strong></p><p style="color: #667eea; font-weight: 600;">${batchName}</p><p>This will revert all student changes made during this promotion.</p>`,
            confirmText: 'Rollback',
            confirmType: 'danger',
            onConfirm: () => {
                fetch('promotion_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'rollback',
                        batch_id: batchId
                    })
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess('Promotion rolled back successfully!', () => {
                                location.reload();
                            });
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(err => {
                        showError('An error occurred during rollback. Please try again.');
                        console.error(err);
                    });
            }
        });
    }
</script>

<?php include '../../includes/footer.php'; ?>