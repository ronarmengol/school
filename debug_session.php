<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f3f4f6; font-weight: 600; }
    </style>
</head>
<body>
    <h1>🔍 Session Debug Information</h1>
    
    <div class="box">
        <h2>Current Session Data</h2>
        <table>
            <tr>
                <th>Key</th>
                <th>Value</th>
            </tr>
            <tr>
                <td><strong>User ID</strong></td>
                <td><?php echo $_SESSION['user_id'] ?? '<span class="error">NOT SET</span>'; ?></td>
            </tr>
            <tr>
                <td><strong>Username</strong></td>
                <td><?php echo $_SESSION['username'] ?? '<span class="error">NOT SET</span>'; ?></td>
            </tr>
            <tr>
                <td><strong>Role</strong></td>
                <td><?php echo $_SESSION['role'] ?? '<span class="error">NOT SET</span>'; ?></td>
            </tr>
            <tr>
                <td><strong>Full Name</strong></td>
                <td><?php echo $_SESSION['full_name'] ?? '<span class="error">NOT SET</span>'; ?></td>
            </tr>
        </table>
        
        <h3>Full Session Array</h3>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>

    <div class="box">
        <h2>Transaction History Visibility Check</h2>
        <?php
        $role = $_SESSION['role'] ?? 'guest';
        $allowed_roles = ['super_admin', 'admin', 'accountant'];
        $is_allowed = in_array($role, $allowed_roles);
        ?>
        <table>
            <tr>
                <td><strong>Your Current Role:</strong></td>
                <td><code><?php echo htmlspecialchars($role); ?></code></td>
            </tr>
            <tr>
                <td><strong>Allowed Roles:</strong></td>
                <td><code><?php echo implode(', ', $allowed_roles); ?></code></td>
            </tr>
            <tr>
                <td><strong>Can See Transaction History:</strong></td>
                <td class="<?php echo $is_allowed ? 'success' : 'error'; ?>">
                    <?php echo $is_allowed ? '✓ YES' : '✗ NO'; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>Database User Information</h2>
        <?php
        if (isset($_SESSION['user_id'])) {
          $user_id = $_SESSION['user_id'];
          $sql = "SELECT u.*, r.role_name 
                    FROM users u 
                    JOIN roles r ON u.role_id = r.role_id 
                    WHERE u.user_id = ?";
          $stmt = mysqli_prepare($conn, $sql);
          mysqli_stmt_bind_param($stmt, "i", $user_id);
          mysqli_stmt_execute($stmt);
          $result = mysqli_stmt_get_result($stmt);
          $user = mysqli_fetch_assoc($result);

          if ($user) {
            echo "<table>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            foreach ($user as $key => $value) {
              if ($key !== 'password_hash') {
                echo "<tr><td><strong>" . htmlspecialchars($key) . "</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
              }
            }
            echo "</table>";

            // Check if session role matches database role
            if ($user['role_name'] !== $_SESSION['role']) {
              echo "<p class='error'>⚠️ WARNING: Session role ('{$_SESSION['role']}') does not match database role ('{$user['role_name']}'). Please log out and log in again.</p>";
            }
          } else {
            echo "<p class='error'>User not found in database!</p>";
          }
        } else {
          echo "<p class='error'>No user logged in</p>";
        }
        ?>
    </div>

    <div class="box">
        <h2>Quick Actions</h2>
        <p>
            <a href="index.php" style="display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; margin-right: 10px;">Go to Home</a>
            <a href="auth/logout.php" style="display: inline-block; padding: 10px 20px; background: #ef4444; color: white; text-decoration: none; border-radius: 6px;">Logout & Re-login</a>
        </p>
    </div>
</body>
</html>