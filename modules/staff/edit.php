<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']);

$error = "";
$success = "";

// Get User ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$user_id = intval($_GET['id']);

// Fetch User Data
$sql = "SELECT u.*, r.role_name, t.specialization 
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        LEFT JOIN teachers t ON u.user_id = t.user_id 
        WHERE u.user_id = ? AND r.role_name IN ('admin', 'teacher', 'accountant')";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Get Roles
$roles_res = mysqli_query($conn, "SELECT * FROM roles WHERE role_name IN ('admin', 'teacher', 'accountant')");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role_id = $_POST['role_id'];
    $specialization = trim($_POST['specialization']); // Only for teachers
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if(empty($username)) {
        $error = "Username is required.";
    } else {
        // Start Transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Update User - only update password if provided
            if (!empty($password)) {
                // DEVELOPMENT ONLY: Store plain text password
                $password_to_store = $password;
                $stmt = mysqli_prepare($conn, "UPDATE users SET username = ?, password_hash = ?, role_id = ?, email = ?, full_name = ?, is_active = ? WHERE user_id = ?");
                mysqli_stmt_bind_param($stmt, "ssissii", $username, $password_to_store, $role_id, $email, $full_name, $is_active, $user_id);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET username = ?, role_id = ?, email = ?, full_name = ?, is_active = ? WHERE user_id = ?");
                mysqli_stmt_bind_param($stmt, "sissii", $username, $role_id, $email, $full_name, $is_active, $user_id);
            }
            mysqli_stmt_execute($stmt);
            
            // Get role name to check if teacher
            $r_check = mysqli_query($conn, "SELECT role_name FROM roles WHERE role_id = $role_id");
            $r_row = mysqli_fetch_assoc($r_check);
            
            if ($r_row['role_name'] == 'teacher') {
                // Check if teacher record exists
                $t_check = mysqli_query($conn, "SELECT teacher_id FROM teachers WHERE user_id = $user_id");
                if (mysqli_num_rows($t_check) > 0) {
                    // Update existing teacher record
                    $stmt2 = mysqli_prepare($conn, "UPDATE teachers SET specialization = ? WHERE user_id = ?");
                    mysqli_stmt_bind_param($stmt2, "si", $specialization, $user_id);
                    mysqli_stmt_execute($stmt2);
                } else {
                    // Insert new teacher record
                    $stmt2 = mysqli_prepare($conn, "INSERT INTO teachers (user_id, specialization) VALUES (?, ?)");
                    mysqli_stmt_bind_param($stmt2, "is", $user_id, $specialization);
                    mysqli_stmt_execute($stmt2);
                }
            } else {
                // If role changed from teacher to something else, delete teacher record
                mysqli_query($conn, "DELETE FROM teachers WHERE user_id = $user_id");
            }
            
            mysqli_commit($conn);
            $success = "Staff member updated successfully!";
            
            // Refresh user data
            $stmt = mysqli_prepare($conn, "SELECT u.*, r.role_name, t.specialization 
                    FROM users u 
                    JOIN roles r ON u.role_id = r.role_id 
                    LEFT JOIN teachers t ON u.user_id = t.user_id 
                    WHERE u.user_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error updating user: " . $e->getMessage(); // Duplicate username likely
        }
    }
}

$page_title = "Edit Staff";
include '../../includes/header.php';
?>

<div style="max-width: 600px; margin: 40px auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 700;">Edit Staff Member</h2>
            <p style="color: #64748b; margin: 5px 0 0 0;">Update account details and status for <?php echo htmlspecialchars($user['full_name'] ?? ''); ?>.</p>
        </div>
        <a href="index.php" class="btn btn-outline-danger" style="display: inline-flex; align-items: center; gap: 8px; border-radius: 10px; padding: 10px 20px; font-weight: 600;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to List
        </a>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger" style="border-radius: 12px; padding: 15px 20px; margin-bottom: 25px; border: none; background: #fef2f2; color: #ef4444; display: flex; align-items: center; gap: 10px; font-weight: 600;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success" style="border-radius: 12px; padding: 15px 20px; margin-bottom: 25px; border: none; background: #f0fdf4; color: #10b981; display: flex; align-items: center; gap: 10px; font-weight: 600;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="card card-premium" style="background: white; padding: 35px; border-radius: 16px; border: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] ?? ''); ?>?id=<?php echo $user_id; ?>" method="POST" style="display: grid; gap: 20px;">
            <div class="form-group" style="margin: 0;">
                <label style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Staff Role</label>
                <select name="role_id" class="form-control" required id="roleSelect" onchange="toggleSpecialization()" style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;">
                    <?php 
                    mysqli_data_seek($roles_res, 0); // Reset pointer
                    while($r = mysqli_fetch_assoc($roles_res)): 
                    ?>
                        <option value="<?php echo $r['role_id']; ?>" 
                                data-name="<?php echo $r['role_name']; ?>"
                                <?php echo ($r['role_id'] == $user['role_id']) ? 'selected' : ''; ?>>
                            <?php echo ucfirst($r['role_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Teacher Only Field -->
            <div class="form-group" id="specField" style="display: none; margin: 0;">
                <label style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Specialization (Subject)</label>
                <input type="text" name="specialization" class="form-control" 
                       value="<?php echo htmlspecialchars($user['specialization'] ?? ''); ?>" 
                       placeholder="e.g. Mathematics" style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;">
            </div>

            <div class="form-group" style="margin: 0;">
                <label style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Email Address</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group" style="margin: 0;">
                    <label style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Username</label>
                    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required autocomplete="off" style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;">
                </div>
                
                <div class="form-group" style="margin: 0;">
                    <label style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">New Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Leave blank" autocomplete="new-password" style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;">
                    <small style="font-size: 11px; color: #94a3b8; margin-top: 4px; display: block;">Only fill to change current password</small>
                </div>
            </div>
            
            <div class="form-group" style="margin: 10px 0;">
                <label style="display: flex; align-items: center; cursor: pointer; color: #475569; font-weight: 600; font-size: 14px;">
                    <input type="checkbox" name="is_active" value="1" <?php echo $user['is_active'] ? 'checked' : ''; ?> style="width: 18px; height: 18px; margin-right: 12px; cursor: pointer; accent-color: #6366f1;">
                    Account is Active (Allow Login)
                </label>
            </div>

            <div class="form-group" style="margin-top: 10px;">
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 50px; border-radius: 12px; font-size: 16px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Update Staff Record
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
    
    if(roleName === 'teacher') {
        specField.style.display = 'block';
    } else {
        specField.style.display = 'none';
    }
}
// Run on load
document.addEventListener('DOMContentLoaded', function() {
    toggleSpecialization();
});
</script>

<?php include '../../includes/footer.php'; ?>
