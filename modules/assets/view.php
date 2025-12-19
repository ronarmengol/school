<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$asset_code = $_GET['code'] ?? 'Unknown';

$page_title = "Asset Details: $asset_code - Asset Management";
include '../../includes/header.php';
include 'assets_styles.php';

// Mock Detail
$asset = [
  'code' => $asset_code,
  'name' => 'HP EliteBook 840 G8',
  'cat' => 'ICT Equipment',
  'loc' => 'Office - 201',
  'status' => 'In Use',
  'cond' => 'Good',
  'assigned' => 'John Doe',
  'brand' => 'HP',
  'purchase_date' => '2023-01-15',
  'price' => get_setting('currency_symbol', '$') . '1,200.00',
  'warranty' => '2025-01-15',
  'description' => 'Intel Core i7, 16GB RAM, 512GB SSD. Used by the Principal for daily administrative tasks.'
];
?>

<div class="asset-module-wrap">
  <div class="asset-header">
    <div>
      <div class="breadcrumb">
        <a href="index.php">Asset Management</a>
        <span>&rarr;</span>
        <a href="list.php">Asset Inventory</a>
        <span>&rarr;</span>
        <span>Asset Details</span>
      </div>
      <h1 class="asset-title"><?php echo $asset['name']; ?> <small
          style="color: var(--asset-muted); font-size: 18px; font-weight: 500; font-family: monospace;">#<?php echo $asset['code']; ?></small>
      </h1>
    </div>
    <div class="header-actions">
      <a href="edit.php?code=<?php echo $asset['code']; ?>" class="asset-btn asset-btn-primary">Edit Details</a>
      <a href="list.php" class="asset-btn asset-btn-secondary">Back to List</a>
    </div>
  </div>

  <?php include 'assets_header.php'; ?>

  <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <div style="display: flex; flex-direction: column; gap: 24px;">
      <div class="asset-card" style="padding: 32px;">
        <h3 style="margin: 0 0 24px 0; font-size: 18px; font-weight: 800;">Information Overview</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
          <div>
            <div style="margin-bottom: 20px;">
              <label
                style="display: block; font-size: 12px; font-weight: 700; color: var(--asset-muted); text-transform: uppercase; margin-bottom: 4px;">Category</label>
              <div style="font-weight: 600;"><?php echo $asset['cat']; ?></div>
            </div>
            <div style="margin-bottom: 20px;">
              <label
                style="display: block; font-size: 12px; font-weight: 700; color: var(--asset-muted); text-transform: uppercase; margin-bottom: 4px;">Brand
                / Manufacturer</label>
              <div style="font-weight: 600;"><?php echo $asset['brand']; ?></div>
            </div>
          </div>
          <div>
            <div style="margin-bottom: 20px;">
              <label
                style="display: block; font-size: 12px; font-weight: 700; color: var(--asset-muted); text-transform: uppercase; margin-bottom: 4px;">Purchase
                Date</label>
              <div style="font-weight: 600;"><?php echo date('M d, Y', strtotime($asset['purchase_date'])); ?></div>
            </div>
            <div style="margin-bottom: 20px;">
              <label
                style="display: block; font-size: 12px; font-weight: 700; color: var(--asset-muted); text-transform: uppercase; margin-bottom: 4px;">Warranty
                Expiry</label>
              <div style="font-weight: 600;"><?php echo date('M d, Y', strtotime($asset['warranty'])); ?></div>
            </div>
          </div>
        </div>
        <div style="margin-top: 10px; padding-top: 20px; border-top: 1px solid var(--asset-border);">
          <label
            style="display: block; font-size: 12px; font-weight: 700; color: var(--asset-muted); text-transform: uppercase; margin-bottom: 8px;">Description
            & Notes</label>
          <p style="margin: 0; font-size: 14px; line-height: 1.6; color: var(--asset-text);">
            <?php echo $asset['description']; ?>
          </p>
        </div>
      </div>

      <div class="asset-card" style="padding: 32px;">
        <h3 style="margin: 0 0 24px 0; font-size: 18px; font-weight: 800;">Recent Maintenance</h3>
        <div
          style="text-align: center; padding: 40px; color: var(--asset-muted); font-size: 14px; background: #fafafa; border-radius: 12px; border: 1.5px dashed var(--asset-border);">
          No maintenance records found for this asset.
        </div>
      </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px;">
      <div class="asset-card" style="padding: 24px;">
        <h3 style="margin: 0 0 20px 0; font-size: 16px; font-weight: 700;">Lifecycle Status</h3>
        <div style="margin-bottom: 16px;">
          <?php $status_slug = strtolower(str_replace(' ', '-', $asset['status'])); ?>
          <span class="status-badge status-<?php echo $status_slug; ?>"
            style="width: 100%; justify-content: center; padding: 8px;">
            <i class="dot"></i> <?php echo $asset['status']; ?>
          </span>
        </div>
        <div style="padding: 16px; background: #f8fafc; border-radius: 10px; border: 1px solid var(--asset-border);">
          <div style="font-size: 12px; font-weight: 700; color: var(--asset-muted); margin-bottom: 4px;">CONDITION</div>
          <div style="font-weight: 800; color: var(--asset-text);"><?php echo $asset['cond']; ?></div>
        </div>
      </div>

      <div class="asset-card" style="padding: 24px;">
        <h3 style="margin: 0 0 20px 0; font-size: 16px; font-weight: 700;">Current Assignment</h3>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
          <div
            style="width: 44px; height: 44px; border-radius: 50%; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-weight: 800;">
            JD</div>
          <div>
            <div style="font-weight: 700;"><?php echo $asset['assigned']; ?></div>
            <div style="font-size: 12px; color: var(--asset-muted);"><?php echo $asset['loc']; ?></div>
          </div>
        </div>
        <button class="asset-btn asset-btn-secondary" style="width: 100%; justify-content: center;">Change
          Assignment</button>
      </div>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>