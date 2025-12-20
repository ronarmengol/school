<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
require_once '../../config/database.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$page_title = "New Asset Assignment - Asset Management";
include '../../includes/header.php';
include 'assets_styles.php';

// Fetch available assets (not removed)
$assets_query = "SELECT asset_id, asset_code, asset_name, status FROM assets WHERE status != 'Removed' ORDER BY asset_name ASC";
$assets_result = mysqli_query($conn, $assets_query);
$available_assets = [];
if ($assets_result) {
  while ($row = mysqli_fetch_assoc($assets_result)) {
    $available_assets[] = $row;
  }
}

// Fetch users for assignment
$users_query = "SELECT u.user_id, u.full_name, r.role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.role_id 
                WHERE u.is_active = 1 
                ORDER BY u.full_name ASC";
$users_result = mysqli_query($conn, $users_query);
$available_users = [];
if ($users_result) {
  while ($row = mysqli_fetch_assoc($users_result)) {
    $available_users[] = $row;
  }
}

// Fetch locations
$locations_query = "SELECT location_id, location_name, location_type FROM asset_locations ORDER BY location_name ASC";
$locations_result = mysqli_query($conn, $locations_query);
$available_locations = [];
if ($locations_result) {
  while ($row = mysqli_fetch_assoc($locations_result)) {
    $available_locations[] = $row;
  }
}
?>

<div class="asset-module-wrap">
  <div class="asset-header">
    <div>
      <div class="breadcrumb">
        <a href="index.php">Asset Management</a>
        <span>&rarr;</span>
        <a href="assignment.php">Assignments</a>
        <span>&rarr;</span>
        <span>New Assignment</span>
      </div>
      <h1 class="asset-title">Create Asset Assignment</h1>
    </div>
    <div class="header-actions">
      <a href="assignment.php" class="asset-btn asset-btn-secondary">Cancel</a>
    </div>
  </div>

  <?php include 'assets_header.php'; ?>

  <form method="POST" action="save_assignment.php" id="assignmentForm">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
      <!-- Main Details -->
      <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="asset-card" style="padding: 32px;">
          <h3
            style="margin: 0 0 24px 0; font-size: 18px; font-weight: 700; color: var(--asset-text); display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" fill="none" stroke="var(--asset-primary)" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Asset Selection
          </h3>
          <div class="asset-form-group">
            <label class="asset-label">Select Asset <span style="color: #ef4444;">*</span></label>
            <select name="asset_id" class="asset-select" required id="asset_select">
              <option value="">Choose an asset...</option>
              <?php foreach ($available_assets as $asset): ?>
                <option value="<?php echo $asset['asset_id']; ?>"
                  data-code="<?php echo htmlspecialchars($asset['asset_code']); ?>"
                  data-status="<?php echo htmlspecialchars($asset['status']); ?>">
                  <?php echo htmlspecialchars($asset['asset_name']); ?>
                  (<?php echo htmlspecialchars($asset['asset_code']); ?>)
                  - <?php echo htmlspecialchars($asset['status']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div id="asset_preview"
            style="display: none; margin-top: 16px; padding: 16px; background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <div style="font-size: 12px; color: var(--asset-muted); margin-bottom: 4px;">Asset Code</div>
                <div style="font-weight: 700; font-family: monospace; color: var(--asset-primary);" id="preview_code">
                </div>
              </div>
              <div>
                <div style="font-size: 12px; color: var(--asset-muted); margin-bottom: 4px;">Current Status</div>
                <div style="font-weight: 700;" id="preview_status"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="asset-card" style="padding: 32px;">
          <h3
            style="margin: 0 0 24px 0; font-size: 18px; font-weight: 700; color: var(--asset-text); display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" fill="none" stroke="var(--asset-primary)" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Assignment Details
          </h3>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="asset-form-group">
              <label class="asset-label">Assign To (User) <span style="color: #ef4444;">*</span></label>
              <select name="assigned_to_user_id" class="asset-select" required>
                <option value="">Select User...</option>
                <?php foreach ($available_users as $user): ?>
                  <option value="<?php echo $user['user_id']; ?>">
                    <?php echo htmlspecialchars($user['full_name']); ?>
                    (<?php echo htmlspecialchars($user['role_name']); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="asset-form-group">
              <label class="asset-label">Assignment Date <span style="color: #ef4444;">*</span></label>
              <input type="date" name="assignment_date" class="asset-input" value="<?php echo date('Y-m-d'); ?>"
                required>
            </div>
          </div>
          <div class="asset-form-group">
            <label class="asset-label">Purpose / Reason</label>
            <textarea name="purpose" class="asset-textarea" rows="3"
              placeholder="e.g., For daily administrative work, Teaching purposes, etc."></textarea>
          </div>
        </div>
      </div>

      <!-- Sidebar Details -->
      <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="asset-card" style="padding: 24px;">
          <h3 style="margin: 0 0 20px 0; font-size: 16px; font-weight: 700; color: var(--asset-text);">Location &
            Tracking</h3>
          <div class="asset-form-group">
            <label class="asset-label">Assigned Location</label>
            <select name="location_id" class="asset-select">
              <option value="">Select Location...</option>
              <?php foreach ($available_locations as $loc): ?>
                <option value="<?php echo $loc['location_id']; ?>">
                  <?php echo htmlspecialchars($loc['location_name']); ?>
                  <?php if (!empty($loc['location_type'])): ?>
                    (<?php echo htmlspecialchars($loc['location_type']); ?>)
                  <?php endif; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="asset-form-group" style="margin-bottom: 0;">
            <label class="asset-label">Expected Return Date</label>
            <input type="date" name="expected_return_date" class="asset-input">
            <small style="display: block; margin-top: 6px; font-size: 12px; color: var(--asset-muted);">Leave blank
              for permanent assignment</small>
          </div>
        </div>

        <div class="asset-card" style="padding: 24px;">
          <h3 style="margin: 0 0 20px 0; font-size: 16px; font-weight: 700; color: var(--asset-text);">Additional
            Information</h3>
          <div class="asset-form-group">
            <label class="asset-label">Condition at Assignment</label>
            <select name="condition_at_assignment" class="asset-select">
              <option value="Excellent">Excellent</option>
              <option value="Good" selected>Good</option>
              <option value="Fair">Fair</option>
              <option value="Poor">Poor</option>
            </select>
          </div>
          <div class="asset-form-group" style="margin-bottom: 0;">
            <label class="asset-label">Notes</label>
            <textarea name="notes" class="asset-textarea" rows="4"
              placeholder="Any additional notes or instructions..."></textarea>
          </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
          <button type="submit" class="asset-btn asset-btn-primary"
            style="width: 100%; justify-content: center; padding: 14px;">
            Create Assignment
          </button>
          <a href="assignment.php" class="asset-btn asset-btn-secondary"
            style="width: 100%; justify-content: center; padding: 14px; text-decoration: none;">
            Cancel
          </a>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
  // Asset preview functionality
  document.getElementById('asset_select').addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    const preview = document.getElementById('asset_preview');

    if (this.value) {
      const code = selectedOption.getAttribute('data-code');
      const status = selectedOption.getAttribute('data-status');

      document.getElementById('preview_code').textContent = code;
      document.getElementById('preview_status').textContent = status;
      preview.style.display = 'block';
    } else {
      preview.style.display = 'none';
    }
  });
</script>

<?php include '../../includes/footer.php'; ?>