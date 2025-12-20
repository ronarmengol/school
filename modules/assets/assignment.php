<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$page_title = "Asset Assignments - Asset Management";
include '../../includes/header.php';
include 'assets_styles.php';

// Fetch assignments from database
require_once '../../config/database.php';
$assignments_query = "
  SELECT 
    aa.assignment_id,
    aa.assignment_date,
    aa.return_date,
    aa.status,
    aa.assigned_to_name,
    aa.assigned_to_role,
    aa.assigned_to_type,
    a.asset_id,
    a.asset_code,
    a.asset_name
  FROM asset_assignments aa
  INNER JOIN assets a ON aa.asset_id = a.asset_id
  WHERE aa.status = 'Active'
  ORDER BY aa.assignment_date DESC
";

$assignments = [];
if ($result = mysqli_query($conn, $assignments_query)) {
  while ($row = mysqli_fetch_assoc($result)) {
    $assignments[] = $row;
  }
  mysqli_free_result($result);
}
?>

<div class="asset-module-wrap">
  <div class="asset-header">
    <div>
      <div class="breadcrumb">
        <a href="index.php">Asset Management</a>
        <span>&rarr;</span>
        <span>Assignments</span>
      </div>
      <h1 class="asset-title">Asset Assignments</h1>
    </div>
    <div class="header-actions">
      <a href="create_assignment.php" class="asset-btn asset-btn-primary">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
        </svg>
        New Assignment / Transfer
      </a>
    </div>
  </div>

  <?php include 'assets_header.php'; ?>

  <div class="asset-card">
    <div
      style="padding: 24px; border-bottom: 1px solid var(--asset-border); background: #fafafa; display: flex; justify-content: space-between; align-items: center;">
      <h3 class="asset-title" style="font-size: 18px;">Active Assignments</h3>
      <div style="display: flex; gap: 8px;">
        <input type="text" class="asset-input" placeholder="Search by user or asset..." style="max-width: 300px;">
      </div>
    </div>
    <table class="asset-table">
      <thead>
        <tr>
          <th>Asset Tag</th>
          <th>Asset Item</th>
          <th>Assigned To</th>
          <th>Department / Role</th>
          <th>Assignment Date</th>
          <th>Status</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($assignments)): ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 60px 24px; color: var(--asset-muted);">
              <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                style="margin: 0 auto 16px; opacity: 0.3;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
              </svg>
              <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">No Asset Assignments</div>
              <div style="font-size: 14px;">Assign assets to staff, departments, or locations to track accountability.
              </div>
            </td>
          </tr>
        <?php else:
          foreach ($assignments as $a): ?>
            <tr>
              <td style="font-family: monospace; font-weight: 700; color: var(--asset-primary);">
                <?php echo htmlspecialchars($a['asset_code']); ?>
              </td>
              <td style="font-weight: 700;"><?php echo htmlspecialchars($a['asset_name']); ?></td>
              <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                  <div
                    style="width: 32px; height: 32px; border-radius: 50%; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800;">
                    <?php echo strtoupper(substr($a['assigned_to_name'], 0, 1)); ?>
                  </div>
                  <span style="font-weight: 600;"><?php echo htmlspecialchars($a['assigned_to_name']); ?></span>
                </div>
              </td>
              <td><span style="font-size: 13px; color: var(--asset-muted);">
                  <?php echo htmlspecialchars($a['assigned_to_role'] ?? 'N/A'); ?>
                </span></td>
              <td style="font-weight: 500;"><?php echo date('d/m/Y', strtotime($a['assignment_date'])); ?></td>
              <td>
                <span
                  style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px; <?php echo $a['status'] == 'Active' ? 'background: #dcfce7; color: #15803d;' : 'background: #f1f5f9; color: #64748b;'; ?>">
                  <?php echo htmlspecialchars($a['status']); ?>
                </span>
              </td>
              <td style="text-align: right;">
                <button class="asset-btn asset-btn-secondary" style="padding: 6px 12px;">Transfer</button>
                <button class="asset-btn asset-btn-secondary" style="padding: 6px 12px;">Recover</button>
              </td>
            </tr>
          <?php endforeach;
        endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>