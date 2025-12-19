<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']);

$error = "";
$success = "";

// Auto-generate Admission Number suggestion
$sql_last = "SELECT admission_number FROM students ORDER BY student_id DESC LIMIT 1";
$res_last = mysqli_query($conn, $sql_last);
$next_admn = "ADM001";
if ($row_last = mysqli_fetch_assoc($res_last)) {
    // Basic increment logic for demo
    // ADM001 -> 001
    $num = intval(substr($row_last['admission_number'], 3)) + 1;
    $next_admn = "ADM" . str_pad($num, 3, "0", STR_PAD_LEFT);
}

// Fetch Classes
$classes_res = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
$classes = mysqli_fetch_all($classes_res, MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $admission_number = trim($_POST['admission_number']);
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $class_id = $_POST['class_id'];
    $enrollment_date = $_POST['enrollment_date'];

    // Guardian fields (optional)
    $guardian1_name = trim($_POST['guardian1_name'] ?? '');
    $guardian1_contact = trim($_POST['guardian1_contact'] ?? '');
    $guardian2_name = trim($_POST['guardian2_name'] ?? '');
    $guardian2_contact = trim($_POST['guardian2_contact'] ?? '');

    // Split Full Name into First and Last Name
    $name_parts = explode(' ', $full_name);
    if (count($name_parts) > 1) {
        $last_name = array_pop($name_parts);
        $first_name = implode(' ', $name_parts);
    } else {
        $first_name = $full_name;
        $last_name = "";
    }

    if (empty($first_name) || empty($admission_number)) {
        $error = "Required fields missing.";
    } else {
        // Check duplicate admission
        $check = mysqli_query($conn, "SELECT student_id FROM students WHERE admission_number = '$admission_number'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Admission Number already exists.";
        } else {
            $sql = "INSERT INTO students (first_name, last_name, admission_number, gender, dob, current_class_id, enrollment_date, guardian1_name, guardian1_contact, guardian2_name, guardian2_contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssssssssss", $first_name, $last_name, $admission_number, $gender, $dob, $class_id, $enrollment_date, $guardian1_name, $guardian1_contact, $guardian2_name, $guardian2_contact);
                if (mysqli_stmt_execute($stmt)) {
                    // Redirect
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Database Error: " . mysqli_error($conn);
                }
            }
        }
    }
}

$page_title = "Admit Student";
include '../../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>Student Registration</h3>
        <a href="index.php" class="btn btn-danger">Back</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card" style="background: white; padding: 25px; border-radius: 8px;">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Left Col -->
                <div>
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="text" id="dob-display" class="form-control" placeholder="DD/MM/YYYY">
                        <input type="hidden" name="dob" id="dob-hidden">
                    </div>
                </div>

                <!-- Right Col -->
                <div>
                    <div class="form-group">
                        <label class="form-label">Admission Number <small
                                style="color: #94a3b8;">(Auto-generated)</small></label>
                        <input type="text" name="admission_number" class="form-control"
                            value="<?php echo $next_admn; ?>" readonly
                            style="background-color: #f1f5f9; cursor: not-allowed; color: #64748b;" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assign Class</label>
                        <select name="class_id" class="form-control" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['class_id']; ?>">
                                    <?php echo $c['class_name'] . ' ' . $c['section_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Enrollment Date</label>
                        <input type="text" id="enrollment-display" class="form-control" placeholder="DD/MM/YYYY"
                            required>
                        <input type="hidden" name="enrollment_date" id="enrollment-hidden">
                    </div>
                </div>
            </div>

            <!-- Guardian Information Section (Optional) -->
            <div style="margin-top: 30px; padding-top: 30px; border-top: 2px solid #f1f5f9;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; cursor: pointer;"
                    onclick="toggleGuardianSection()">
                    <svg id="guardian-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6366f1"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        style="transition: transform 0.3s;">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                    <h4 style="margin: 0; color: #1e293b; font-weight: 700;">Guardian / Parent Information <span
                            style="color: #94a3b8; font-size: 14px; font-weight: 500;">(Optional)</span></h4>
                </div>

                <div id="guardian-fields" style="display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <!-- Guardian 1 -->
                        <div>
                            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                <h5
                                    style="margin: 0 0 15px 0; color: #475569; font-size: 14px; font-weight: 700; text-transform: uppercase;">
                                    Guardian 1</h5>
                                <div class="form-group">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="guardian1_name" class="form-control"
                                        placeholder="e.g. John Doe">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="guardian1_contact" class="form-control"
                                        placeholder="e.g. +260 XXX XXX XXX">
                                </div>
                            </div>
                        </div>

                        <!-- Guardian 2 -->
                        <div>
                            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                <h5
                                    style="margin: 0 0 15px 0; color: #475569; font-size: 14px; font-weight: 700; text-transform: uppercase;">
                                    Guardian 2</h5>
                                <div class="form-group">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="guardian2_name" class="form-control"
                                        placeholder="e.g. Jane Doe">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="guardian2_contact" class="form-control"
                                        placeholder="e.g. +260 XXX XXX XXX">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary" style="width: 200px;">Register Student</button>
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

    // Initialize Enrollment Date picker
    flatpickr("#enrollment-display", {
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
                document.getElementById('enrollment-hidden').value = `${year}-${month}-${day}`;
            }
        }
    });

    // Set default enrollment date
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    document.getElementById('enrollment-hidden').value = `${year}-${month}-${day}`;

    // Toggle Guardian Section
    function toggleGuardianSection() {
        const fields = document.getElementById('guardian-fields');
        const chevron = document.getElementById('guardian-chevron');

        if (fields.style.display === 'none') {
            fields.style.display = 'block';
            chevron.style.transform = 'rotate(180deg)';
        } else {
            fields.style.display = 'none';
            chevron.style.transform = 'rotate(0deg)';
        }
    }

</script>

<?php include '../../includes/footer.php'; ?>