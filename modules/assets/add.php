<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}


$page_title = "Add New Asset - Asset Management";
include '../../includes/header.php';
include 'assets_styles.php';

// Fetch locations from database
require_once '../../config/database.php';
$locations_query = "SELECT location_id, location_name, location_type FROM asset_locations ORDER BY location_name ASC";
$locations_result = $conn->query($locations_query);
$available_locations = [];
if ($locations_result && $locations_result->num_rows > 0) {
  while ($row = $locations_result->fetch_assoc()) {
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
        <a href="list.php">Asset Inventory</a>
        <span>&rarr;</span>
        <span>Add New Asset</span>
      </div>
      <h1 class="asset-title">Add New Asset</h1>
    </div>
    <div class="header-actions">
      <a href="list.php" class="asset-btn asset-btn-secondary">Cancel</a>
    </div>
  </div>

  <?php include 'assets_header.php'; ?>

  <form method="POST" action="save_asset.php">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
      <!-- Main Details -->
      <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="asset-card" style="padding: 32px;">
          <h3
            style="margin: 0 0 24px 0; font-size: 18px; font-weight: 700; color: var(--asset-text); display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" fill="none" stroke="var(--asset-primary)" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Basic Information
          </h3>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="asset-form-group">
              <label class="asset-label">Asset Name</label>
              <input type="text" name="name" class="asset-input" placeholder="e.g. HP EliteBook 840 G8" required>
            </div>
            <div class="asset-form-group">
              <label class="asset-label">Asset Code / Tag</label>
              <input type="text" name="code" class="asset-input" placeholder="e.g. ICT-LP-001" required>
            </div>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="asset-form-group">
              <label class="asset-label">Category</label>
              <select name="category" class="asset-select" required>
                <option value="">Select Category</option>
                <option>ICT Equipment</option>
                <option>Furniture</option>
                <option>Lab Gear</option>
                <option>Sports Gear</option>
                <option>Vehicles</option>
              </select>
            </div>
            <div class="asset-form-group">
              <label class="asset-label">Manufacturer / Brand</label>
              <input type="text" name="brand" class="asset-input" placeholder="e.g. HP, Dell, Samsung">
            </div>
          </div>
          <div class="asset-form-group" style="margin-bottom: 0;">
            <label class="asset-label">Description / Specifications</label>
            <textarea name="description" class="asset-textarea" rows="4"
              placeholder="Enter technical specs, model numbers, or physical description..."></textarea>
          </div>
        </div>

        <div class="asset-card" style="padding: 32px;">
          <h3
            style="margin: 0 0 24px 0; font-size: 18px; font-weight: 700; color: var(--asset-text); display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" fill="none" stroke="var(--asset-primary)" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.407 2.67 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.407-2.67-1M12 16V7" />
            </svg>
            Purchase & Warranty
          </h3>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="asset-form-group">
              <label class="asset-label">Purchase Date</label>
              <input type="date" name="purchase_date" class="asset-input">
            </div>
            <div class="asset-form-group">
              <label class="asset-label">Purchase Price (<?php echo get_setting('currency_symbol', '$'); ?>)</label>
              <input type="number" step="0.01" name="purchase_price" class="asset-input" placeholder="0.00">
            </div>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="asset-form-group">
              <label class="asset-label">Supplier / Vendor</label>
              <input type="text" name="supplier" class="asset-input" placeholder="e.g. ABC Tech Solutions">
            </div>
            <div class="asset-form-group" style="margin-bottom: 0;">
              <label class="asset-label">Warranty Expiry Date</label>
              <input type="date" name="warranty_expiry" class="asset-input">
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar Details -->
      <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="asset-card" style="padding: 24px;">
          <h3 style="margin: 0 0 20px 0; font-size: 16px; font-weight: 700; color: var(--asset-text);">Status &
            Condition</h3>
          <div class="asset-form-group">
            <label class="asset-label">Current Status</label>
            <select name="status" class="asset-select">
              <option value="Available">Available</option>
              <option value="In Use">In Use</option>
              <option value="Maintenance">Maintenance</option>
              <option value="Reserved">Reserved</option>
            </select>
          </div>
          <div class="asset-form-group" style="margin-bottom: 0;">
            <label class="asset-label">Condition</label>
            <select name="condition" class="asset-select">
              <option value="New">Excellent / New</option>
              <option value="Good" selected>Good</option>
              <option value="Fair">Fair</option>
              <option value="Poor">Poor / Damaged</option>
            </select>
          </div>
        </div>

        <div class="asset-card" style="padding: 24px;">
          <h3 style="margin: 0 0 20px 0; font-size: 16px; font-weight: 700; color: var(--asset-text);">Location Mapping
          </h3>
          <div class="asset-form-group">
            <label class="asset-label">Primary Location</label>
            <select name="location" class="asset-select">
              <option value="">Select Location</option>
              <?php foreach ($available_locations as $loc): ?>
                <option value="<?php echo htmlspecialchars($loc['location_id']); ?>">
                  <?php echo htmlspecialchars($loc['location_name']); ?>
                  <?php if (!empty($loc['location_type'])): ?>
                    (<?php echo htmlspecialchars($loc['location_type']); ?>)
                  <?php endif; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="asset-form-group" style="margin-bottom: 0;">
            <label class="asset-label">Sub-Location / Room</label>
            <input type="text" name="sub_location" class="asset-input" placeholder="e.g. Cabinet 3, Desk 12">
          </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
          <button type="submit" class="asset-btn asset-btn-primary"
            style="width: 100%; justify-content: center; padding: 14px;">
            Save Asset Record
          </button>
          <button type="button" class="asset-btn asset-btn-secondary"
            style="width: 100%; justify-content: center; padding: 14px;">
            Save & Add Another
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<?php include '../../includes/footer.php'; ?>