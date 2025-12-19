<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']);

$error = "";
$success = "";
$class_id = $_GET['id'] ?? 0;

// Fetch class data
$sql = "SELECT * FROM classes WHERE class_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $class_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($result);

if (!$class) {
    header("Location: index.php");
    exit();
}

// Fetch all subjects
$all_subjects_res = mysqli_query($conn, "SELECT * FROM subjects ORDER BY subject_name");
$all_subjects = [];
while ($subj = mysqli_fetch_assoc($all_subjects_res)) {
    $all_subjects[] = $subj;
}

// Fetch assigned subjects
$assigned_subjects = [];
$stmt_assigned = mysqli_prepare($conn, "SELECT subject_id FROM class_subjects WHERE class_id = ?");
mysqli_stmt_bind_param($stmt_assigned, "i", $class_id);
mysqli_stmt_execute($stmt_assigned);
$res_assigned = mysqli_stmt_get_result($stmt_assigned);
while ($row_assigned = mysqli_fetch_assoc($res_assigned)) {
    $assigned_subjects[] = $row_assigned['subject_id'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_name = trim($_POST['class_name']);
    $section_name = trim($_POST['section_name']);

    // Get selected subjects (array of IDs)
    $selected_subjects = $_POST['subjects'] ?? [];

    if (empty($class_name)) {
        $error = "Class Name is required.";
    } else {
        $sql = "UPDATE classes SET class_name = ?, section_name = ? WHERE class_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $class_name, $section_name, $class_id);
            if (mysqli_stmt_execute($stmt)) {

                // Update subjects: Delete existing -> Insert new
                $del_stmt = mysqli_prepare($conn, "DELETE FROM class_subjects WHERE class_id = ?");
                mysqli_stmt_bind_param($del_stmt, "i", $class_id);
                mysqli_stmt_execute($del_stmt);

                if (!empty($selected_subjects)) {
                    $insert_sql = "INSERT INTO class_subjects (class_id, subject_id) VALUES (?, ?)";
                    $insert_stmt = mysqli_prepare($conn, $insert_sql);
                    foreach ($selected_subjects as $subj_id) {
                        mysqli_stmt_bind_param($insert_stmt, "ii", $class_id, $subj_id);
                        mysqli_stmt_execute($insert_stmt);
                    }
                }

                $success = "Class and subjects updated successfully.";
                header("Location: index.php?msg=updated"); // Redirect to index to 'close' the edit view
                exit();
            } else {
                $error = "Something went wrong. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $success = "Class updated successfully.";
}

$page_title = "Edit Class";
include '../../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>Edit Class</h3>
        <a href="index.php" class="btn btn-danger"
            style="background: white; color: #dc3545; border: 1px solid #dc3545;">Back</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card" style="background: white; padding: 20px; border-radius: 8px;">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $class_id; ?>" method="POST">
            <div class="row" style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <h5 style="border-bottom: 1px solid #eee; padding-bottom: 10px;">Class Details</h5>
                    <div class="form-group">
                        <label for="class_name" class="form-label">Class Name (e.g. Grade 1)</label>
                        <input type="text" name="class_name" class="form-control"
                            value="<?php echo htmlspecialchars($class['class_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="section_name" class="form-label">Section (e.g. A, North)</label>
                        <input type="text" name="section_name" class="form-control"
                            value="<?php echo htmlspecialchars($class['section_name']); ?>">
                    </div>
                </div>

                <div style="flex: 1; border-left: 1px solid #eee; padding-left: 20px;">
                    <h5 style="border-bottom: 1px solid #eee; padding-bottom: 10px;">Assign Subjects</h5>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php if (empty($all_subjects)): ?>
                            <p class="text-muted">No subjects found. <a href="index.php?tab=subjects">Add subjects
                                    here</a>.</p>
                        <?php else: ?>
                            <?php foreach ($all_subjects as $subj): ?>
                                <div class="form-check" style="margin-bottom: 8px;">
                                    <input class="form-check-input" type="checkbox" name="subjects[]"
                                        value="<?php echo $subj['subject_id']; ?>" id="subj_<?php echo $subj['subject_id']; ?>"
                                        <?php echo in_array($subj['subject_id'], $assigned_subjects) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="subj_<?php echo $subj['subject_id']; ?>">
                                        <?php echo htmlspecialchars($subj['subject_name']); ?>
                                        <?php if (!empty($subj['subject_code'])): ?>
                                            <small
                                                class="text-muted">(<?php echo htmlspecialchars($subj['subject_code']); ?>)</small>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Class & Subjects</button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>