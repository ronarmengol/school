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

// Fetch categories from database
$categories_query = "SELECT category_id, category_name FROM asset_categories WHERE is_active = 1 ORDER BY category_name ASC";
$categories_result = $conn->query($categories_query);
$available_categories = [];
if ($categories_result && $categories_result->num_rows > 0) {
  while ($row = $categories_result->fetch_assoc()) {
    $available_categories[] = $row;
  }
}

?>

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

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

  <form method="POST" action="save_asset.php" id="addAssetForm">
    <input type="hidden" name="add_another" id="add_another" value="0">
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
              <div style="position: relative;">
                <input type="text" name="code" id="asset_code_input" class="asset-input" placeholder="e.g. ICT-LP-001"
                  required>
                <div id="code_status_indicator"
                  style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); display: none; align-items: center; gap: 4px; font-size: 12px; font-weight: 700;">
                  <!-- Icon and text will be inserted here -->
                </div>
              </div>
              <div id="code_error_msg"
                style="display: none; color: var(--asset-danger); font-size: 11px; font-weight: 700; margin-top: 6px;">
                This code already exists in the system.
              </div>
            </div>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="asset-form-group">
              <label class="asset-label">Category</label>
              <select name="category" class="asset-select" required>
                <option value="">Select Category</option>
                <?php foreach ($available_categories as $cat): ?>
                  <option value="<?php echo htmlspecialchars($cat['category_id']); ?>">
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                  </option>
                <?php endforeach; ?>
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
              <input type="text" name="purchase_date" class="asset-input datepicker" placeholder="Select Date">
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
              <input type="text" name="warranty_expiry" class="asset-input datepicker" placeholder="Select Date">
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
          <button type="button" class="asset-btn asset-btn-secondary" onclick="submitAndAddAnother()"
            style="width: 100%; justify-content: center; padding: 14px;">
            Save & Add Another
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
  function submitAndAddAnother() {
    document.getElementById('add_another').value = '1';
    document.getElementById('addAssetForm').submit();
  }

  // Display toast notifications if there are any
  <?php if (isset($_SESSION['toast_message']) && isset($_SESSION['toast_type'])): ?>
    window.addEventListener('DOMContentLoaded', function () {
      <?php if ($_SESSION['toast_type'] === 'success'): ?>
        showToastSuccess('<?php echo addslashes($_SESSION['toast_message']); ?>');
      <?php else: ?>
        showToastError('<?php echo addslashes($_SESSION['toast_message']); ?>');
      <?php endif; ?>
    });
    <?php
    unset($_SESSION['toast_message']);
    unset($_SESSION['toast_type']);
    ?>
  <?php endif; ?>
</script>

<script>
  // AJAX Asset Code existence check
  const codeInput = document.getElementById('asset_code_input');
  const indicator = document.getElementById('code_status_indicator');
  const errorMsg = document.getElementById('code_error_msg');
  const saveBtn = document.querySelector('button[type="submit"]');
  const addAnotherBtn = document.querySelector('button[onclick="submitAndAddAnother()"]');

  let checkTimeout;

  codeInput.addEventListener('input', function () {
    const code = this.value.trim();

    // Clear previous state
    indicator.style.display = 'none';
    indicator.innerHTML = '';
    errorMsg.style.display = 'none';
    saveBtn.disabled = false;
    addAnotherBtn.disabled = false;
    this.style.borderColor = '';

    if (code.length < 2) return;

    // Show loading state
    indicator.style.display = 'flex';
    indicator.style.color = 'var(--asset-muted)';
    indicator.innerHTML = '<div class="spinner-small"></div> checking...';

    clearTimeout(checkTimeout);
    checkTimeout = setTimeout(() => {
      fetch(`check_asset_code.php?code=${encodeURIComponent(code)}`)
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            if (data.exists) {
              // Code exists - Show error
              indicator.style.color = 'var(--asset-danger)';
              indicator.innerHTML = '<svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>';
              errorMsg.style.display = 'block';
              codeInput.style.borderColor = 'var(--asset-danger)';
              saveBtn.disabled = true;
              addAnotherBtn.disabled = true;
              saveBtn.style.opacity = '0.5';
              addAnotherBtn.style.opacity = '0.5';
            } else {
              // Code available - Show success
              indicator.style.color = 'var(--asset-success)';
              indicator.innerHTML = '<svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>';
              errorMsg.style.display = 'none';
              codeInput.style.borderColor = 'var(--asset-success)';
              saveBtn.disabled = false;
              addAnotherBtn.disabled = false;
              saveBtn.style.opacity = '1';
              addAnotherBtn.style.opacity = '1';
            }
          }
        })
        .catch(err => {
          console.error(err);
          indicator.style.display = 'none';
        });
    }, 500);
  });
</script>

<style>
  .spinner-small {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-top-color: var(--asset-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }
</style>

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    flatpickr(".datepicker", {
      dateFormat: "Y-m-d",
      altInput: true,
      altFormat: "d/m/Y",
      allowInput: true,
      monthSelectorType: "static"
    });
  });
</script>

<?php include '../../includes/footer.php'; ?>