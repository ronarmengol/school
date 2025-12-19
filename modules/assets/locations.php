<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$page_title = "Locations & Rooms - Asset Management";
include '../../includes/header.php';
include 'assets_styles.php';

// Initialize empty data
$locations = [];
?>

<div class="asset-module-wrap">
  <div class="asset-header">
    <div>
      <div class="breadcrumb">
        <a href="index.php">Asset Management</a>
        <span>&rarr;</span>
        <span>Locations</span>
      </div>
      <h1 class="asset-title">Locations & Rooms</h1>
    </div>
    <div class="header-actions">
      <button class="asset-btn asset-btn-primary" onclick="showToast('Add Location wizard would start here', 'info')">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        Add Location
      </button>
    </div>
  </div>

  <?php include 'assets_header.php'; ?>

  <div class="asset-card">
    <table class="asset-table">
      <thead>
        <tr>
          <th>Location Name</th>
          <th>Type</th>
          <th>Sub-Rooms / Areas</th>
          <th>Asset Count</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($locations)): ?>
          <tr>
            <td colspan="5" style="text-align: center; padding: 60px 24px; color: var(--asset-muted);">
              <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                style="margin: 0 auto 16px; opacity: 0.3;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">No Locations Found</div>
              <div style="font-size: 14px;">Add locations to organize your assets by building, room, or department.</div>
            </td>
          </tr>
        <?php else:
          foreach ($locations as $loc): ?>
            <tr>
              <td>
                <div style="font-weight: 700; color: var(--asset-text);"><?php echo $loc['name']; ?></div>
              </td>
              <td>
                <span
                  style="font-size: 11px; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 4px 10px; border-radius: 6px; text-transform: uppercase;">
                  <?php echo $loc['type']; ?>
                </span>
              </td>
              <td style="font-weight: 600; color: var(--asset-muted);"><?php echo $loc['rooms'] ?: 'N/A'; ?></td>
              <td style="font-weight: 700; color: var(--asset-primary);"><?php echo $loc['assets']; ?></td>
              <td style="text-align: right;">
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                  <button class="asset-btn asset-btn-secondary" style="padding: 6px 12px;">Edit</button>
                  <button class="asset-btn asset-btn-secondary" style="padding: 6px 12px;">Manage Layout</button>
                </div>
              </td>
            </tr>
          <?php endforeach;
        endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>