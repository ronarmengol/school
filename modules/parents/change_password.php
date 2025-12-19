<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['parent']); 

// Check for JIT default password usage to remove the "alert" 
// We will look up the current user's password hash from DB
$user_id = $_SESSION['user_id'];
$sql = "SELECT password_hash FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);

// If hash matches '123' (which is plain text right now in development, or verify hash if hashed)
// Based on current auth_functions.php, passwords are stored as plain text for dev or hashed
// In attempt_login, we inserted "123" directly or via password_hash depending on exact impl.
// In auth_functions.php, we saw: mysqli_stmt_bind_param($ins_stmt, "ssis", $username, $password, ...);
// where $password was '123'. So it's plain text '123' in DB.
$is_default = ($row['password_hash'] === '123');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please enter both fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password === '123') {
        $error = "You cannot use the default password.";
    } else {
        // Update Password
        // In existing system, are we hashing? 
        // Checking auth_functions.php: attempt_login compares ($password == $row['password_hash'])
        // So currently using PLAIN TEXT.
        $update_sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $new_password, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success = "Password updated successfully!";
            $is_default = false;
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

$page_title = "Change Password";
include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        
        <?php if($is_default): ?>
        <div class="alert alert-warning" style="border-left: 4px solid #f59e0b; background-color: #fffbeb;">
            <strong style="color: #b45309;">Security Alert:</strong>
            <p style="color: #92400e; margin: 5px 0 0;">You are currently using the default password. For the security of your account and child's data, please change it immediately.</p>
        </div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if(isset($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?> 
                <a href="index.php" style="margin-left: 10px; font-weight: bold;">Return to Dashboard</a>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h4 style="margin: 0;">Change Password</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Update Password</button>
                        <a href="index.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
