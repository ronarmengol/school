<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']);

$error = "";
$success = "";

// Get Roles (excluding admin - only super_admin can manage admin accounts)
$roles_res = mysqli_query($conn, "SELECT * FROM roles WHERE role_name IN ('teacher', 'accountant')");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role_id = $_POST['role_id'];
    $specialization = trim($_POST['specialization']); // Only for teachers

    // Validation
    if (empty($username) || empty($password)) {
        $error = "Username and Password are required.";
    } else {
        // DEVELOPMENT ONLY: Store plain text password
        $password_to_store = $password;

        // Start Transaction
        mysqli_begin_transaction($conn);

        try {
            // Insert User
            $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password_hash, role_id, email, full_name) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssiss", $username, $password_to_store, $role_id, $email, $full_name);
            mysqli_stmt_execute($stmt);
            $new_user_id = mysqli_insert_id($conn);

            // If Teacher, insert into teachers table
            // Get role name to check if teacher
            $r_check = mysqli_query($conn, "SELECT role_name FROM roles WHERE role_id = $role_id");
            $r_row = mysqli_fetch_assoc($r_check);

            if ($r_row['role_name'] == 'teacher') {
                $stmt2 = mysqli_prepare($conn, "INSERT INTO teachers (user_id, specialization) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt2, "is", $new_user_id, $specialization);
                mysqli_stmt_execute($stmt2);
            }

            mysqli_commit($conn);
            header("Location: index.php");
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error creating user: " . $e->getMessage(); // Duplicate username likely
        }
    }
}

$page_title = "Add Staff";
include '../../includes/header.php';
?>

<div style="max-width: 600px; margin: 40px auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 700;">Add Staff Member</h2>
            <p style="color: #64748b; margin: 5px 0 0 0;">Create a new account for a staff member, teacher, or admin.
            </p>
        </div>
        <a href="index.php" class="btn btn-outline-danger"
            style="display: inline-flex; align-items: center; gap: 8px; border-radius: 10px; padding: 10px 20px; font-weight: 600;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to List
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"
            style="border-radius: 12px; padding: 15px 20px; margin-bottom: 25px; border: none; background: #fef2f2; color: #ef4444; display: flex; align-items: center; gap: 10px; font-weight: 600;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="card card-premium"
        style="background: white; padding: 35px; border-radius: 16px; border: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] ?? ''); ?>" method="POST"
            style="display: grid; gap: 20px;">
            <div class="form-group" style="margin: 0;">
                <label
                    style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Full
                    Name</label>
                <input type="text" name="full_name" class="form-control" required
                    style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;"
                    placeholder="e.g. John Doe">
            </div>

            <div class="form-group" style="margin: 0;">
                <label
                    style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Staff
                    Role</label>
                <select name="role_id" class="form-control" required id="roleSelect" onchange="toggleSpecialization()"
                    style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;">
                    <?php while ($r = mysqli_fetch_assoc($roles_res)): ?>
                        <option value="<?php echo $r['role_id']; ?>" data-name="<?php echo $r['role_name']; ?>">
                            <?php echo ucfirst($r['role_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Teacher Only Field -->
            <div class="form-group" id="specField" style="display: none; margin: 0;">
                <label
                    style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Specialization
                    (Subject)</label>
                <input type="text" name="specialization" class="form-control" placeholder="e.g. Mathematics"
                    style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;">
            </div>

            <div class="form-group" style="margin: 0;">
                <label
                    style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Email
                    Address</label>
                <input type="email" name="email" class="form-control"
                    style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;"
                    placeholder="john.doe@school.com">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group" style="margin: 0;">
                    <label
                        style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Username</label>
                    <input type="text" name="username" class="form-control" require autocomplete="off"
                        style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;"
                        placeholder="johndoe">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label
                        style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Temp
                        Password</label>
                    <input type="password" name="password" class="form-control" required autocomplete="new-password"
                        style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;">
                </div>
            </div>

            <div class="form-group" style="margin-top: 10px;">
                <button type="submit" class="btn btn-primary"
                    style="width: 100%; height: 50px; border-radius: 12px; font-size: 16px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <polyline points="16 11 18 13 22 9"></polyline>
                    </svg>
                    Create Staff Account
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleSpecialization() {
        var select = document.getElementById('roleSelect');
        var selectedOption = select.options[select.selectedIndex];
        var roleName = selectedOption.getAttribute('data-name');
        var specField = document.getElementById('specField');

        if (roleName === 'teacher') {
            specField.style.display = 'block';
        } else {
            specField.style.display = 'none';
        }
    }
    // Run on load
    document.addEventListener('DOMContentLoaded', function () {
        toggleSpecialization();
    });
</script>

<?php include '../../includes/footer.php'; ?>