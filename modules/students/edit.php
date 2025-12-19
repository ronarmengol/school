<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']);

$error = "";
$success = "";
$student_id = $_GET['id'] ?? 0;

// Fetch student data
$sql = "SELECT * FROM students WHERE student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    header("Location: index.php");
    exit();
}

// Fetch Classes
$classes_res = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
$classes = mysqli_fetch_all($classes_res, MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $class_id = $_POST['class_id'];
    $status = $_POST['status'];

    // Split Full Name into First and Last Name
    $name_parts = explode(' ', $full_name);
    if (count($name_parts) > 1) {
        $last_name = array_pop($name_parts);
        $first_name = implode(' ', $name_parts);
    } else {
        $first_name = $full_name;
        $last_name = "";
    }

    if (empty($first_name)) {
        $error = "Required fields missing.";
    } else {
        $sql = "UPDATE students SET first_name=?, last_name=?, gender=?, dob=?, current_class_id=?, status=? WHERE student_id=?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssi", $first_name, $last_name, $gender, $dob, $class_id, $status, $student_id);
            if (mysqli_stmt_execute($stmt)) {
                // Redirect to index page after successful update
                header("Location: index.php?success=updated");
                exit();
            } else {
                $error = "Database Error: " . mysqli_error($conn);
            }
        }
    }
}

$page_title = "Edit Student";
include '../../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>Edit Student - <?php echo htmlspecialchars($student['admission_number']); ?></h3>
        <a href="index.php" class="btn btn-danger">Back</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card" style="background: white; padding: 25px; border-radius: 8px;">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $student_id; ?>" method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Left Col -->
                <div>
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control"
                            value="<?php echo htmlspecialchars($student['first_name'] . ($student['last_name'] ? ' ' . $student['last_name'] : '')); ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control">
                            <option value="Male" <?php echo $student['gender'] == 'Male' ? 'selected' : ''; ?>>Male
                            </option>
                            <option value="Female" <?php echo $student['gender'] == 'Female' ? 'selected' : ''; ?>>Female
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="text" id="dob-display" class="form-control" placeholder="DD/MM/YYYY"
                            value="<?php echo $student['dob'] ? date('d/m/Y', strtotime($student['dob'])) : ''; ?>">
                        <input type="hidden" name="dob" id="dob-hidden" value="<?php echo $student['dob']; ?>">
                    </div>
                </div>

                <!-- Right Col -->
                <div>
                    <div class="form-group">
                        <label class="form-label">Admission Number <small style="color: #94a3b8;">(Cannot be
                                changed)</small></label>
                        <input type="text" class="form-control"
                            value="<?php echo htmlspecialchars($student['admission_number']); ?>" readonly
                            style="background-color: #f1f5f9; cursor: not-allowed; color: #64748b;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assign Class</label>
                        <select name="class_id" class="form-control" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['class_id']; ?>" <?php echo $student['current_class_id'] == $c['class_id'] ? 'selected' : ''; ?>>
                                    <?php echo $c['class_name'] . ' ' . $c['section_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="Active" <?php echo $student['status'] == 'Active' ? 'selected' : ''; ?>>Active
                            </option>
                            <option value="Suspended" <?php echo $student['status'] == 'Suspended' ? 'selected' : ''; ?>>
                                Suspended</option>
                            <option value="Alumni" <?php echo $student['status'] == 'Alumni' ? 'selected' : ''; ?>>Alumni
                            </option>
                            <option value="Transferred" <?php echo $student['status'] == 'Transferred' ? 'selected' : ''; ?>>Transferred</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Enrollment Date</label>
                        <input type="text" class="form-control"
                            value="<?php echo $student['enrollment_date'] ? date('d/m/Y', strtotime($student['enrollment_date'])) : ''; ?>"
                            readonly style="background-color: #f1f5f9; cursor: not-allowed; color: #64748b;">
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary" style="width: 200px;">Update Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    // Initialize Date of Birth picker
    flatpickr("#dob-display", {
        dateFormat: "d/m/Y",
        altInput: true,
        altFormat: "d/m/Y",
        allowInput: true,
        maxDate: "today",
        onChange: function (selectedDates, dateStr, instance) {
            if (selectedDates.length > 0) {
                const date = selectedDates[0];
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                document.getElementById('dob-hidden').value = `${year}-${month}-${day}`;
            }
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>