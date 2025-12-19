<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

$error = "";

// Fetch Terms
$terms_sql = "SELECT t.term_id, t.term_name, y.year_name 
              FROM terms t 
              JOIN academic_years y ON t.academic_year_id = y.year_id 
              ORDER BY y.year_name DESC, t.term_name ASC";
$terms_res = mysqli_query($conn, $terms_sql);

// Fetch Grades (Class Levels)
$grades_sql = "SELECT DISTINCT class_name FROM classes ORDER BY class_name ASC";
$grades_res = mysqli_query($conn, $grades_sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $exam_name = trim($_POST['exam_name']);
    $term_id = $_POST['term_id'];
    $start_date = $_POST['start_date'];
    $target_grade = $_POST['target_grade'] ?? null;

    if (empty($exam_name) || empty($term_id)) {
        $error = "Required fields missing.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO exams (exam_name, term_id, start_date, class_name) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "siss", $exam_name, $term_id, $start_date, $target_grade);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: index.php");
            exit();
        } else {
            $error = "Error creating exam.";
        }
    }
}

$page_title = "Create Exam";
include '../../includes/header.php';
?>

<div style="max-width: 600px; margin: 0 auto; padding: 20px 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 700;">Create Examination</h2>
            <p style="color: #64748b; margin: 5px 0 0 0;">Fill in the details to schedule a new exam.</p>
        </div>
        <a href="index.php" class="btn btn-secondary"
            style="display: flex; align-items: center; gap: 8px; border-radius: 10px; padding: 10px 20px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="border-radius: 12px; margin-bottom: 20px;"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card card-premium" style="padding: 30px;">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="form-group" style="margin-bottom: 25px;">
                <label class="form-label"
                    style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">Exam
                    Name</label>
                <input type="text" name="exam_name" class="form-control" required
                    style="border-radius: 10px; padding: 12px 15px; border: 1px solid #e2e8f0;"
                    placeholder="e.g. End of Term One Exams">
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label class="form-label"
                    style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">Academic
                    Term</label>
                <select name="term_id" class="form-control" required
                    style="border-radius: 10px; padding: 12px 15px; border: 1px solid #e2e8f0; height: 50px;">
                    <option value="">Select Term</option>
                    <?php while ($t = mysqli_fetch_assoc($terms_res)): ?>
                        <option value="<?php echo $t['term_id']; ?>">
                            <?php echo htmlspecialchars($t['year_name'] . ' - ' . $t['term_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label class="form-label"
                    style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">Applicable
                    Grade (Optional)</label>
                <select name="target_grade" class="form-control"
                    style="border-radius: 10px; padding: 12px 15px; border: 1px solid #e2e8f0; height: 50px;">
                    <option value="">All Grades</option>
                    <?php
                    mysqli_data_seek($grades_res, 0);
                    while ($g = mysqli_fetch_assoc($grades_res)): ?>
                        <option value="<?php echo htmlspecialchars($g['class_name']); ?>">
                            <?php echo htmlspecialchars($g['class_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 30px;">
                <label class="form-label"
                    style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">Start
                    Date</label>
                <div style="position: relative;">
                    <input type="text" id="date-display" class="form-control" placeholder="DD/MM/YYYY" required
                        style="border-radius: 10px; padding: 12px 15px; border: 1px solid #e2e8f0; height: 50px;">
                </div>
                <input type="hidden" name="start_date" id="date-hidden">
            </div>

            <button type="submit" class="btn btn-primary"
                style="width: 100%; padding: 15px; border-radius: 12px; font-weight: 700; font-size: 16px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); display: flex; align-items: center; justify-content: center; gap: 10px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Create Exam Schedule
            </button>
        </form>
    </div>
</div>

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    flatpickr("#date-display", {
        dateFormat: "d/m/Y",
        altInput: true,
        altFormat: "d/m/Y",
        allowInput: true,
        defaultDate: "today",
        onChange: function (selectedDates, dateStr, instance) {
            if (selectedDates.length > 0) {
                const date = selectedDates[0];
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                document.getElementById('date-hidden').value = `${year}-${month}-${day}`;
            }
        },
        onReady: function (selectedDates, dateStr, instance) {
            if (selectedDates.length > 0) {
                const date = selectedDates[0];
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                document.getElementById('date-hidden').value = `${year}-${month}-${day}`;
            }
        }
    });
</script>

<?php
include '../../includes/modals.php';
include '../../includes/footer.php';
?>