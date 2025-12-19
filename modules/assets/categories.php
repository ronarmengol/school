<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$page_title = "Asset Categories - Asset Management";
include '../../includes/header.php';
include 'assets_styles.php';

// Mock Categories
$categories = [
  ['name' => 'ICT Equipment', 'assets' => 0, 'status' => 'Active'],
  ['name' => 'Furniture', 'assets' => 0, 'status' => 'Active'],
  ['name' => 'Lab Gear', 'assets' => 0, 'status' => 'Active'],
  ['name' => 'Sports Gear', 'assets' => 0, 'status' => 'Active'],
  ['name' => 'Vehicles', 'assets' => 0, 'status' => 'Active'],
  ['name' => 'Office Supplies', 'assets' => 0, 'status' => 'Inactive'],
];
?>

<div class="asset-module-wrap">
  <div class="asset-header">
    <div>
      <div class="breadcrumb">
        <a href="index.php">Asset Management</a>
        <span>&rarr;</span>
        <span>Categories</span>
      </div>
      <h1 class="asset-title">Asset Categories</h1>
    </div>
    <div class="header-actions">
      <button class="asset-btn asset-btn-primary" onclick="showToast('Add Category modal would open here', 'info')">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        Add Category
      </button>
    </div>
  </div>

  <?php include 'assets_header.php'; ?>

  <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
    <?php foreach ($categories as $cat): ?>
      <div class="asset-card" style="padding: 24px; position: relative;">
        <div style="position: absolute; top: 20px; right: 20px;">
          <span
            style="font-size: 11px; padding: 4px 10px; border-radius: 99px; font-weight: 700; <?php echo $cat['status'] == 'Active' ? 'background: #dcfce7; color: #15803d;' : 'background: #f1f5f9; color: #64748b;'; ?>">
            <?php echo $cat['status']; ?>
          </span>
        </div>

        <div
          style="width: 56px; height: 56px; border-radius: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
          <?php
          // SVG icons based on category
          $svg_icon = '';
          switch ($cat['name']) {
            case 'ICT Equipment':
              $svg_icon = '<svg width="28" height="28" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
              break;
            case 'Furniture':
              $svg_icon = '<svg width="28" height="28" fill="white" viewBox="0 0 24 24"><path d="M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V7H1v10h22V7h-2v4h-2V7z"/></svg>';
              break;
            case 'Lab Gear':
              $svg_icon = '<svg width="28" height="28" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>';
              break;
            case 'Sports Gear':
              $svg_icon = '<svg width="28" height="28" fill="white" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 3.3l1.35-.95c1.82.56 3.37 1.76 4.38 3.34l-.39 1.34-1.35.46L13 6.7V5.3zm-3.35-.95L11 5.3v1.4L7.01 9.49l-1.35-.46-.39-1.34c1.01-1.58 2.56-2.78 4.38-3.34zM7.08 17.11l-1.14.1A7.938 7.938 0 014 12c0-.12.01-.23.02-.35l1-.73 1.38.48 1.46 4.34-.78 1.37zm7.42 2.48c-.79.26-1.63.41-2.5.41s-1.71-.15-2.5-.41l-.69-1.49.64-1.1h5.11l.64 1.11-.7 1.48zM14.27 15H9.73l-1.35-4.02L12 8.44l3.63 2.54L14.27 15zm3.79 2.21l-1.14-.1-.79-1.37 1.46-4.34 1.39-.47 1 .73c.01.11.02.22.02.34 0 1.99-.73 3.81-1.94 5.21z"/></svg>';
              break;
            case 'Vehicles':
              $svg_icon = '<svg width="28" height="28" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-4 0v10m-6 0h12a2 2 0 002-2V9a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm-2 4h16M4 9h16"/></svg>';
              break;
            case 'Office Supplies':
              $svg_icon = '<svg width="28" height="28" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
              break;
            default:
              $svg_icon = '<svg width="28" height="28" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>';
          }
          echo $svg_icon;
          ?>
        </div>
        <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800; color: var(--asset-text);">
          <?php echo $cat['name']; ?>
        </h3>
        <p style="margin: 0 0 24px 0; font-size: 14px; color: var(--asset-muted);">
          Total Items: <strong><?php echo $cat['assets']; ?></strong>
        </p>

        <div style="display: flex; gap: 8px; border-top: 1px solid var(--asset-border); padding-top: 20px;">
          <button class="asset-btn asset-btn-secondary"
            style="flex: 1; justify-content: center; padding: 8px;">Edit</button>
          <button class="asset-btn asset-btn-secondary" style="flex: 1; justify-content: center; padding: 8px;">View
            Items</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>