<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

$page_title = "Lesson Plans";
include '../../includes/header.php';
?>

<!-- Premium UI Libs -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// Fetch classes
$classes = [];
$class_res = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name ASC");
while ($row = mysqli_fetch_assoc($class_res)) {
    $classes[] = $row;
}

// Fetch subjects
$subjects = [];
$subject_res = mysqli_query($conn, "SELECT * FROM subjects ORDER BY subject_name ASC");
while ($row = mysqli_fetch_assoc($subject_res)) {
    $subjects[] = $row;
}

// Check if editing
$edit_mode = false;
$plan_id = $_GET['id'] ?? null;
$plan_data = [];
if ($plan_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM lesson_plans WHERE plan_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $plan_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($plan_data = mysqli_fetch_assoc($res)) {
        $edit_mode = true;
        // Decode JSON fields
        $teaching_methods_saved = json_decode($plan_data['teaching_methods'] ?? '[]', true) ?: [];
        $learner_activities_saved = json_decode($plan_data['learner_activities'] ?? '[]', true) ?: [];
        $teaching_aids_saved = json_decode($plan_data['teaching_aids'] ?? '[]', true) ?: [];
    }
}
?>

<style>
    :root {
        --primary-blue: #3b82f6;
        --secondary-blue: #dbeafe;
        --success-green: #a7f3d0;
        --success-green-dark: #065f46;
        --gray-text: #64748b;
        --border-color: #e2e8f0;
        --bg-light: #f8fafc;
    }

    body {
        background-color: #f1f5f9;
        font-family: 'Inter', -apple-system, blinkmacsystemfont, 'Segoe UI', roboto, oxygen, ubuntu, cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }

    .lesson-plan-container {
        max-width: 1000px;
        margin: 40px auto;
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        padding: 40px;
        border: 1px solid #eee;
    }

    .form-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px solid #f1f5f9;
    }

    .form-header-icon {
        width: 40px;
        height: 40px;
        background: var(--secondary-blue);
        color: var(--primary-blue);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .form-header h1 {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    /* Breadcrumbs Navigation */
    .form-nav {
        display: flex;
        gap: 15px;
        margin-bottom: 40px;
        font-size: 14px;
        color: var(--gray-text);
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 10px;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nav-item.active {
        color: var(--primary-blue);
        font-weight: 600;
        border-bottom: 2px solid var(--primary-blue);
        margin-bottom: -12px;
        padding-bottom: 10px;
    }

    .nav-item i {
        font-size: 10px;
    }

    /* Section Styling */
    .form-section {
        margin-bottom: 40px;
    }

    .section-num-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 20px;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 15px;
    }

    .form-group-custom {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    label {
        font-size: 13px;
        font-weight: 500;
        color: var(--gray-text);
    }

    .input-field {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 14px;
        color: #1e293b;
        background: white;
        transition: border 0.2s;
    }

    .input-field:focus {
        border-color: var(--primary-blue);
        outline: none;
    }

    /* Specialized Grid for Focus Area */
    .focus-area-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }

    /* Time Allocation Specifics */
    .total-time-badge {
        background: #eff6ff;
        color: var(--primary-blue);
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        border: 1px solid var(--secondary-blue);
    }

    /* Checkbox Styles to Match Image */
    .method-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }

    .checkbox-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #475569;
        cursor: pointer;
    }

    .checkbox-wrap input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-blue);
    }

    /* Activity "Chips" to match section 5 */
    .activity-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .activity-chip {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 20px;
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 14px;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s;
    }

    .activity-chip.active {
        background: #eff6ff;
        border-color: var(--primary-blue);
        color: var(--primary-blue);
    }

    .activity-chip input {
        display: none;
    }

    /* Teaching Aids with Icons */
    .aid-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }

    .aid-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        color: #475569;
    }

    .aid-item .icon {
        font-size: 16px;
    }

    /* Actions */
    .form-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 40px;
    }

    .btn-action {
        padding: 14px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
        text-align: center;
    }

    .btn-save-draft {
        background: #eff6ff;
        color: var(--primary-blue);
    }

    .btn-save-draft:hover {
        background: var(--secondary-blue);
    }

    .btn-submit {
        background: #6ee7b7;
        /* Green as in image */
        color: #064e3b;
    }

    .btn-submit:hover {
        background: #34d399;
    }

    @media (max-width: 768px) {

        .form-row,
        .focus-area-grid,
        .form-actions {
            grid-template-columns: 1fr;
        }

        .form-nav {
            overflow-x: auto;
            white-space: nowrap;
        }
    }
</style>

<div class="lesson-plan-container">
    <div class="form-header">
        <div class="form-header-icon">
            <i class="fas fa-book-open"></i>
        </div>
        <h1>Daily Lesson Plan</h1>
    </div>

    <!-- Progress Nav (Inspired by image) -->
    <div class="form-nav">
        <div class="nav-item active">Basic Details <i class="fas fa-chevron-right"></i></div>
        <div class="nav-item">Lesson Focus <i class="fas fa-chevron-right"></i></div>
        <div class="nav-item">Time Allocation <i class="fas fa-chevron-right"></i></div>
        <div class="nav-item">Methods & Activities <i class="fas fa-chevron-right"></i></div>
        <div class="nav-item">Review</div>
    </div>

    <form id="lessonPlanForm">
        <input type="hidden" name="action" value="save_lesson_plan">
        <input type="hidden" name="plan_id" value="<?php echo $plan_id; ?>">

        <!-- 1. Basic Details -->
        <div class="form-section">
            <div class="section-num-title">1. Basic Details (Auto / Quick Select)</div>
            <div class="form-row">
                <div class="form-group-custom">
                    <label>Class / Grade</label>
                    <select name="class_id" class="input-field" required>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['class_id']; ?>" <?php echo (isset($plan_data['class_id']) && $plan_data['class_id'] == $class['class_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group-custom">
                    <label>Subject</label>
                    <select name="subject_id" class="input-field" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['subject_id']; ?>" <?php echo (isset($plan_data['subject_id']) && $plan_data['subject_id'] == $subject['subject_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group-custom">
                    <label>Date</label>
                    <input type="date" name="lesson_date" class="input-field"
                        value="<?php echo $plan_data['lesson_date'] ?? date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group-custom">
                    <label>Lesson Duration</label>
                    <select name="duration" class="input-field" required>
                        <option value="40 min" <?php echo (isset($plan_data['duration']) && $plan_data['duration'] == '40 min') ? 'selected' : ''; ?>>40 min</option>
                        <option value="1 hr" <?php echo (isset($plan_data['duration']) && $plan_data['duration'] == '1 hr') ? 'selected' : ''; ?>>1 hr</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 2. Lesson Focus -->
        <div class="form-section">
            <div class="section-num-title">2. Lesson Focus (Short Inputs)</div>
            <div class="focus-area-grid">
                <div class="form-group-custom">
                    <label>Topic</label>
                    <input type="text" name="topic" class="input-field" placeholder="Topic name"
                        value="<?php echo htmlspecialchars($plan_data['topic'] ?? ''); ?>" required>
                </div>
                <div class="form-group-custom">
                    <label>Sub-topic (optional)</label>
                    <input type="text" name="sub_topic" class="input-field" placeholder="Sub-topic"
                        value="<?php echo htmlspecialchars($plan_data['sub_topic'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group-custom mt-3">
                <label>Lesson Objective</label>
                <input type="text" name="objective" class="input-field" placeholder="Primary learning objective"
                    value="<?php echo htmlspecialchars($plan_data['objective'] ?? ''); ?>" required>
            </div>
        </div>

        <!-- 3. Time Allocation -->
        <div class="form-section">
            <div class="section-num-title">3. Time Allocation (Preset Options)</div>
            <div class="form-row">
                <div class="form-group-custom">
                    <label>Introduction</label>
                    <select name="intro_minutes" class="input-field time-calc">
                        <?php for ($i = 0; $i <= 20; $i += 5): ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($plan_data['intro_minutes']) && $plan_data['intro_minutes'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?> min</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group-custom">
                    <label>Teaching</label>
                    <select name="teaching_minutes" class="input-field time-calc">
                        <?php for ($i = 0; $i <= 60; $i += 5): ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($plan_data['teaching_minutes']) && $plan_data['teaching_minutes'] == $i) ? 'selected' : ($i == 20 ? 'selected' : ''); ?>>
                                <?php echo $i; ?> min</option><?php endfor; ?>
                    </select>
                </div>
                <div class="form-group-custom">
                    <label>Practice / Activity</label>
                    <select name="practice_minutes" class="input-field time-calc">
                        <?php for ($i = 0; $i <= 60; $i += 5): ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($plan_data['practice_minutes']) && $plan_data['practice_minutes'] == $i) ? 'selected' : ($i == 10 ? 'selected' : ''); ?>>
                                <?php echo $i; ?> min</option><?php endfor; ?>
                    </select>
                </div>
                <div class="form-group-custom">
                    <label>Assessment</label>
                    <select name="assessment_minutes" class="input-field time-calc">
                        <?php for ($i = 0; $i <= 30; $i += 5): ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($plan_data['assessment_minutes']) && $plan_data['assessment_minutes'] == $i) ? 'selected' : ($i == 5 ? 'selected' : ''); ?>>
                                <?php echo $i; ?> min</option><?php endfor; ?>
                    </select>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                <div class="total-time-badge">
                    Total: <span id="totalTime" style="margin-left: 5px;">40 min</span>
                    <input type="hidden" name="total_minutes" id="totalMinutesInput" value="40">
                </div>
            </div>
        </div>

        <!-- 4. Teaching Method -->
        <div class="form-section">
            <div class="section-num-title">4. Teaching Method (Checkboxes)</div>
            <div class="method-grid">
                <?php
                $methods = ['Lecture', 'Discussion', 'Practical / Demonstration', 'Group work'];
                foreach ($methods as $method):
                    $checked = (isset($teaching_methods_saved) && in_array($method, $teaching_methods_saved)) ? 'checked' : '';
                    ?>
                    <label class="checkbox-wrap">
                        <input type="checkbox" name="teaching_methods[]" value="<?php echo $method; ?>" <?php echo $checked; ?>> <?php echo $method; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 5. Learner Activity -->
        <div class="form-section">
            <div class="section-num-title">5. Learner Activity (Quick Select)</div>
            <div class="activity-chips">
                <?php
                $activities = ['Classwork', 'Group work', 'Homework', 'Oral questions'];
                foreach ($activities as $activity):
                    $checked = (isset($learner_activities_saved) && in_array($activity, $learner_activities_saved)) ? 'checked' : '';
                    $active = $checked ? 'active' : '';
                    ?>
                    <label class="activity-chip <?php echo $active; ?>">
                        <input type="checkbox" name="learner_activities[]" value="<?php echo $activity; ?>" <?php echo $checked; ?>>
                        <?php if ($checked): ?><i class="fas fa-check" style="font-size: 10px;"></i><?php endif; ?>
                        <?php echo $activity; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 6. Teaching Aids -->
        <div class="form-section">
            <div class="section-num-title">6. Teaching Aids (Optional Checkboxes)</div>
            <div class="aid-grid">
                <?php
                $aids = [
                    ['name' => 'Textbook', 'icon' => '📙'],
                    ['name' => 'Chalkboard / Whiteboard', 'icon' => '🖼️'],
                    ['name' => 'Charts', 'icon' => '📈'],
                    ['name' => 'Digital content', 'icon' => '💻']
                ];
                foreach ($aids as $aid):
                    $checked = (isset($teaching_aids_saved) && in_array($aid['name'], $teaching_aids_saved)) ? 'checked' : '';
                    ?>
                    <label class="checkbox-wrap">
                        <input type="checkbox" name="teaching_aids[]" value="<?php echo $aid['name']; ?>" <?php echo $checked; ?>>
                        <span class="icon"><?php echo $aid['icon']; ?></span> <?php echo $aid['name']; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 7. Remarks -->
        <div class="form-section">
            <div class="section-num-title">7. Remarks (Optional)</div>
            <div class="form-group-custom">
                <label>Remarks (Short notes - max 2 lines)</label>
                <textarea name="remarks" class="input-field" rows="2"
                    placeholder="Focus on visual aids for the diagram..."><?php echo htmlspecialchars($plan_data['remarks'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" class="btn-action btn-save-draft" onclick="savePlan('draft')">Save Draft</button>
            <button type="submit" class="btn-action btn-submit">Submit Lesson Plan</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Auto-calculate time
        const timeSelectors = document.querySelectorAll('.time-calc');
        const totalTimeSpan = document.getElementById('totalTime');
        const totalMinutesInput = document.getElementById('totalMinutesInput');

        function calculateTotal() {
            let total = 0;
            timeSelectors.forEach(select => {
                total += parseInt(select.value) || 0;
            });
            totalTimeSpan.textContent = total + ' Minutes';
            totalMinutesInput.value = total;
        }

        timeSelectors.forEach(select => {
            select.addEventListener('change', calculateTotal);
        });

        // Initialize total on load
        calculateTotal();

        // Activity chip toggling
        const chips = document.querySelectorAll('.activity-chip input');
        chips.forEach(cb => {
            cb.addEventListener('change', function () {
                if (this.checked) {
                    this.parentElement.classList.add('active');
                    // Add check icon if not present
                    if (!this.parentElement.querySelector('i')) {
                        const icon = document.createElement('i');
                        icon.className = 'fas fa-check';
                        icon.style.fontSize = '10px';
                        icon.style.marginRight = '8px';
                        this.parentElement.insertBefore(icon, this.parentElement.firstChild);
                    }
                } else {
                    this.parentElement.classList.remove('active');
                    const icon = this.parentElement.querySelector('i');
                    if (icon) icon.remove();
                }
            });
        });

        // Form submission
        const form = document.getElementById('lessonPlanForm');
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            savePlan('submitted');
        });
    });

    function savePlan(status) {
        const form = document.getElementById('lessonPlanForm');
        const formData = new FormData(form);
        formData.append('status', status);

        const submitBtn = document.querySelector('.btn-submit');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        fetch('lesson_plans_api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'lesson_plans_list.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Request Failed',
                    text: 'An error occurred while saving.'
                });
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
    }
</script>

<?php include '../../includes/footer.php'; ?>