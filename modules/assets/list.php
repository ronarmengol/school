<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$page_title = "Asset Inventory - Asset Management";
include '../../includes/header.php';
include 'assets_styles.php';


// Initialize empty data
$assets = [];
?>

<div class="asset-module-wrap">
  <div class="asset-header">
    <div>
      <div class="breadcrumb">
        <a href="index.php">Asset Management</a>
        <span>&rarr;</span>
        <span>Asset Inventory</span>
      </div>
      <h1 class="asset-title">Asset Inventory</h1>
    </div>
    <div class="header-actions">
      <a href="add.php" class="asset-btn asset-btn-primary">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        Add New Asset
      </a>
    </div>
  </div>

  <?php include 'assets_header.php'; ?>

  <div class="asset-card">
    <div
      style="padding: 24px; background: #fafafa; border-bottom: 1px solid var(--asset-border); display: flex; justify-content: space-between; align-items: center; gap: 20px;">
      <div style="position: relative; flex: 1; max-width: 400px;">
        <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--asset-muted);">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </span>
        <input type="text" class="asset-input" style="padding-left: 40px;" placeholder="Search assets...">
      </div>
      <div style="display: flex; gap: 12px;">
        <select class="asset-select" style="width: auto;">
          <option>All Categories</option>
          <option>ICT Equipment</option>
          <option>Furniture</option>
        </select>
        <select class="asset-select" style="width: auto;">
          <option>All Status</option>
          <option>In Use</option>
          <option>Available</option>
        </select>
        <button class="asset-btn asset-btn-secondary">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
          </svg>
          Filter
        </button>
      </div>
    </div>
    <table class="asset-table">
      <thead>
        <tr>
          <th>Asset Code</th>
          <th>Asset Name</th>
          <th>Category</th>
          <th>Location</th>
          <th>Status</th>
          <th>Assigned To</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($assets)): ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 60px 24px; color: var(--asset-muted);">
              <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                style="margin: 0 auto 16px; opacity: 0.3;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
              <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">No Assets Found</div>
              <div style="font-size: 14px;">Start by adding your first asset to the inventory.</div>
            </td>
          </tr>
        <?php else:
          foreach ($assets as $asset): ?>
            <tr>
              <td style="font-family: monospace; font-weight: 700; color: var(--asset-primary);">
                <?php echo $asset['code']; ?>
              </td>
              <td style="font-weight: 700;"><?php echo $asset['name']; ?></td>
              <td><span style="font-size: 13px; color: var(--asset-muted);"><?php echo $asset['cat']; ?></span></td>
              <td><span style="font-weight: 500;"><?php echo $asset['loc']; ?></span></td>
              <td>
                <?php $status_slug = strtolower(str_replace(' ', '-', $asset['status'])); ?>
                <span class="status-badge status-<?php echo $status_slug; ?>">
                  <i class="dot"></i> <?php echo $asset['status']; ?>
                </span>
              </td>
              <td><span style="font-weight: 500;"><?php echo $asset['assigned']; ?></span></td>
              <td style="text-align: right;">
                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                  <a href="view.php?code=<?php echo $asset['code']; ?>" class="asset-btn asset-btn-secondary"
                    style="padding: 6px 10px;" title="View Detail">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </a>
                  <a href="edit.php?code=<?php echo $asset['code']; ?>" class="asset-btn asset-btn-secondary"
                    style="padding: 6px 10px;" title="Edit Asset">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach;
        endif; ?>
      </tbody>
    </table>
    <div
      style="padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
      <span style="font-size: 13px; color: var(--asset-muted);">Showing 1 to 7 of 150 assets</span>
      <div style="display: flex; gap: 8px;">
        <button class="asset-btn asset-btn-secondary" disabled>Previous</button>
        <button class="asset-btn asset-btn-primary" style="padding: 8px 14px;">1</button>
        <button class="asset-btn asset-btn-secondary" style="padding: 8px 14px;">2</button>
        <button class="asset-btn asset-btn-secondary">Next</button>
      </div>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>