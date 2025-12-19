<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_parent_password', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Reset Action
if (isset($_POST['action']) && $_POST['action'] == 'reset_password') {
  $parent_user_id = intval($_POST['user_id']);
  $default_pass = '123'; // Development default

  $sql = "UPDATE users SET password_hash = ? WHERE user_id = ? AND role_id = 5";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "si", $default_pass, $parent_user_id);

  if (mysqli_stmt_execute($stmt)) {
    header("Location: password_management.php?success=reset");
    exit();
  } else {
    $error = "Error resetting password: " . mysqli_error($conn);
  }
}

// Fetch all parents and their linked students
$sql = "
    SELECT u.user_id, u.username, u.full_name, u.email, u.last_login,
           GROUP_CONCAT(CONCAT(s.first_name, ' ', s.last_name, ' (', s.admission_number, ')') SEPARATOR ', ') as children
    FROM users u
    LEFT JOIN students s ON u.user_id = s.parent_id
    WHERE u.role_id = 5
    GROUP BY u.user_id
    ORDER BY u.full_name ASC
";
$result = mysqli_query($conn, $sql);
$parents = mysqli_fetch_all($result, MYSQLI_ASSOC);

$page_title = "Parent Password Management";
include '../../includes/header.php';
?>

<div class="settings-container">
  <div class="settings-header">
    <h1 class="settings-title">Parent Password Management</h1>
    <p class="settings-subtitle">Manage access and reset passwords for parent accounts</p>
  </div>

  <?php if ($success == 'reset'): ?>
    <div class="alert alert-success"
      style="margin-bottom: 24px; border-radius: 12px; border: none; background: #d1fae5; color: #065f46; padding: 16px 20px;">
      Password has been reset to the default "123" successfully.
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert alert-danger"
      style="margin-bottom: 24px; border-radius: 12px; border: none; background: #fee2e2; color: #991b1b; padding: 16px 20px;">
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endif; ?>

  <div class="table-container">
    <div class="table-header-row">
      <h4 class="table-title">Parent Accounts</h4>
    </div>
    <table class="premium-table">
      <thead>
        <tr>
          <th>Parent / Username</th>
          <th>Linked Children</th>
          <th>Last Login</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($parents)): ?>
          <tr>
            <td colspan="4" style="text-align: center; padding: 40px; color: var(--s-text-muted);">
              No parent accounts found. Accounts are created automatically when parents log in with student admission
              numbers.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($parents as $parent): ?>
            <tr>
              <td>
                <div style="font-weight: 700; color: var(--s-text-main);">
                  <?php echo htmlspecialchars($parent['full_name'] ?: 'No Name'); ?></div>
                <div style="font-size: 12px; color: var(--s-text-muted);">
                  @<?php echo htmlspecialchars($parent['username']); ?></div>
                <div style="font-size: 12px; color: var(--s-text-muted);">
                  <?php echo htmlspecialchars($parent['email'] ?: ''); ?></div>
              </td>
              <td>
                <div style="max-width: 300px; font-size: 13px;">
                  <?php echo htmlspecialchars($parent['children'] ?: 'No children linked'); ?>
                </div>
              </td>
              <td>
                <div style="font-size: 13px;">
                  <?php echo $parent['last_login'] ? date('M d, Y H:i', strtotime($parent['last_login'])) : 'Never'; ?>
                </div>
              </td>
              <td style="text-align: right;">
                <form method="POST" onsubmit="return confirm('Are you sure you want to reset this password to 123?');"
                  style="display: inline;">
                  <input type="hidden" name="action" value="reset_password">
                  <input type="hidden" name="user_id" value="<?php echo $parent['user_id']; ?>">
                  <button type="submit" class="btn-icon" title="Reset to Default (123)"
                    style="width: auto; padding: 0 12px; height: 32px; font-size: 12px; font-weight: 700; gap: 6px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset Password
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
  /* Reuse settings styles if needed, but they are mostly global or in header */
  .table-container {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    overflow: hidden;
  }

  .table-header-row {
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
    background: #fafafa;
  }

  .table-title {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
  }

  .premium-table {
    width: 100%;
    border-collapse: collapse;
  }

  .premium-table th {
    text-align: left;
    padding: 12px 24px;
    background: #f8fafc;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #e2e8f0;
  }

  .premium-table td {
    padding: 16px 24px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
  }

  .btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-icon:hover {
    border-color: #3b82f6;
    color: #3b82f6;
    background: #eff6ff;
  }
</style>

<?php include '../../includes/footer.php'; ?>