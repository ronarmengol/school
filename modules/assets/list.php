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

// Fetch assets from database with pagination
require_once '../../config/database.php';

// Pagination settings
$items_per_page = 15;
$current_page = (int) (isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1);
$offset = ($current_page - 1) * $items_per_page;

// Build query string for pagination
$filter_params = $_GET;
unset($filter_params['page']);
$base_query = http_build_query($filter_params);
$page_link = "?" . ($base_query ? $base_query . "&" : "") . "page=";

// Filtering
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where = "WHERE a.status != 'Removed'";
if ($category_id > 0) {
  $where .= " AND a.category_id = $category_id";
}
if (!empty($status_filter)) {
  $where .= " AND a.status = '$status_filter'";
}
if (!empty($search)) {
  $where .= " AND (a.asset_name LIKE '%$search%' OR a.asset_code LIKE '%$search%' OR a.assigned_to LIKE '%$search%')";
}

// Get total count of assets with filters
$count_query = "SELECT COUNT(*) as total FROM assets a $where";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_assets = intval($count_row['total']);
$total_pages = (int) ceil($total_assets / $items_per_page);
mysqli_free_result($count_result);

// Fetch assets for current page
$assets = [];
$assets_query = "
  SELECT 
    a.asset_id,
    a.asset_code,
    a.asset_name,
    a.status,
    a.assigned_to,
    ac.category_name,
    al.location_name
  FROM assets a
  LEFT JOIN asset_categories ac ON a.category_id = ac.category_id
  LEFT JOIN asset_locations al ON a.location_id = al.location_id
  $where
  ORDER BY a.created_at DESC
  LIMIT $items_per_page OFFSET $offset
";

if ($assets_result = mysqli_query($conn, $assets_query)) {
  while ($row = mysqli_fetch_assoc($assets_result)) {
    $assets[] = [
      'code' => $row['asset_code'],
      'name' => $row['asset_name'],
      'cat' => $row['category_name'] ?? 'Uncategorized',
      'loc' => $row['location_name'] ?? 'Unassigned',
      'status' => $row['status'],
      'assigned' => $row['assigned_to'] ?? 'Not Assigned'
    ];
  }
  mysqli_free_result($assets_result);
}

// Fetch categories for edit modal
$categories_query = "SELECT category_id, category_name FROM asset_categories WHERE is_active = 1 ORDER BY category_name ASC";
$categories_result = mysqli_query($conn, $categories_query);
$available_categories = [];
if ($categories_result) {
  while ($row = mysqli_fetch_assoc($categories_result)) {
    $available_categories[] = $row;
  }
  mysqli_free_result($categories_result);
}

// Fetch locations for edit modal
$locations_query = "SELECT location_id, location_name FROM asset_locations ORDER BY location_name ASC";
$locations_result = mysqli_query($conn, $locations_query);
$available_locations = [];
if ($locations_result) {
  while ($row = mysqli_fetch_assoc($locations_result)) {
    $available_locations[] = $row;
  }
  mysqli_free_result($locations_result);
}

// Calculate display range
$start_entry = $total_assets > 0 ? $offset + 1 : 0;
$end_entry = min($offset + $items_per_page, $total_assets);


?>

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
  @keyframes spin {
    from {
      transform: rotate(0deg);
    }

    to {
      transform: rotate(360deg);
    }
  }
</style>

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
        <input type="text" id="listSearchInput" class="asset-input" style="padding-left: 40px;"
          placeholder="Search assets..." value="<?php echo htmlspecialchars($search); ?>">
      </div>
      <div style="display: flex; gap: 12px;">
        <select class="asset-select" style="width: auto;" id="listCategoryFilter" onchange="applyFilters()">
          <option value="0">All Categories</option>
          <?php foreach ($available_categories as $cat): ?>
            <option value="<?php echo $cat['category_id']; ?>" <?php echo $category_id == $cat['category_id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cat['category_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <select class="asset-select" style="width: auto;" id="listStatusFilter" onchange="applyFilters()">
          <option value="">All Status</option>
          <option value="Available" <?php echo $status_filter == 'Available' ? 'selected' : ''; ?>>Available</option>
          <option value="In Use" <?php echo $status_filter == 'In Use' ? 'selected' : ''; ?>>In Use</option>
          <option value="Maintenance" <?php echo $status_filter == 'Maintenance' ? 'selected' : ''; ?>>Maintenance
          </option>
          <option value="Reserved" <?php echo $status_filter == 'Reserved' ? 'selected' : ''; ?>>Reserved</option>
          <option value="Retired" <?php echo $status_filter == 'Retired' ? 'selected' : ''; ?>>Retired</option>
        </select>
        <button class="asset-btn asset-btn-secondary" onclick="applyFilters()">
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
                  <button onclick="openEditAssetModal('<?php echo $asset['code']; ?>')"
                    class="asset-btn asset-btn-secondary" style="padding: 6px 10px;" title="Edit Asset">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    onclick="openRemoveAssetModal('<?php echo $asset['code']; ?>', '<?php echo addslashes($asset['name']); ?>')"
                    class="asset-btn asset-btn-secondary"
                    style="padding: 6px 10px; color: var(--asset-muted); border-color: var(--asset-border);"
                    title="Archive Asset">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach;
        endif; ?>
      </tbody>
    </table>
    <div
      style="padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
      <span style="font-size: 13px; color: var(--asset-muted);">
        Showing <?php echo number_format($start_entry); ?> to <?php echo number_format($end_entry); ?> of
        <?php echo number_format($total_assets); ?> assets
      </span>
      <div style="display: flex; gap: 8px;">
        <?php if ($current_page > 1): ?>
          <a href="<?php echo $page_link . intval($current_page - 1); ?>"
            class="asset-btn asset-btn-secondary">Previous</a>
        <?php else: ?>
          <button class="asset-btn asset-btn-secondary" disabled>Previous</button>
        <?php endif; ?>

        <?php
        // Calculate page range to display
        $page_range = 5; // Show 5 page numbers at a time
        $start_page = max(1, $current_page - floor($page_range / 2));
        $end_page = min($total_pages, $start_page + $page_range - 1);

        // Adjust start_page if we're near the end
        if ($end_page - $start_page < $page_range - 1) {
          $start_page = max(1, $end_page - $page_range + 1);
        }

        // Show first page if not in range
        if ($start_page > 1): ?>
          <a href="<?php echo $page_link; ?>1" class="asset-btn asset-btn-secondary" style="padding: 8px 14px;">1</a>
          <?php if ($start_page > 2): ?>
            <span style="padding: 8px 4px; color: var(--asset-muted);">...</span>
          <?php endif;
        endif;

        // Show page numbers
        for ($i = $start_page; $i <= $end_page; $i++):
          if ($i == $current_page): ?>
            <button class="asset-btn asset-btn-primary" style="padding: 8px 14px;"><?php echo $i; ?></button>
          <?php else: ?>
            <a href="<?php echo $page_link . $i; ?>" class="asset-btn asset-btn-secondary"
              style="padding: 8px 14px;"><?php echo $i; ?></a>
          <?php endif;
        endfor;

        // Show last page if not in range
        if ($end_page < $total_pages):
          if ($end_page < $total_pages - 1): ?>
            <span style="padding: 8px 4px; color: var(--asset-muted);">...</span>
          <?php endif; ?>
          <a href="<?php echo $page_link . $total_pages; ?>" class="asset-btn asset-btn-secondary"
            style="padding: 8px 14px;"><?php echo $total_pages; ?></a>
        <?php endif; ?>

        <?php if ($current_page < $total_pages): ?>
          <a href="<?php echo $page_link . intval($current_page + 1); ?>" class="asset-btn asset-btn-secondary">Next</a>
        <?php else: ?>
          <button class="asset-btn asset-btn-secondary" disabled>Next</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Edit Asset Modal -->
<div id="editAssetModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 800px;">
    <div class="modal-header">
      <h3 style="margin: 0; font-size: 20px; font-weight: 800;">Edit Asset</h3>
      <button onclick="closeEditAssetModal()"
        style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--asset-muted);">&times;</button>
    </div>
    <form id="editAssetForm" onsubmit="submitEditAsset(event)">
      <input type="hidden" name="asset_id" id="edit_asset_id">
      <div class="modal-body" style="padding: 24px; max-height: 70vh; overflow-y: auto;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
          <div class="asset-form-group">
            <label class="asset-label">Asset Name <span style="color: #ef4444;">*</span></label>
            <input type="text" name="asset_name" id="edit_asset_name" class="asset-input" required>
          </div>
          <div class="asset-form-group">
            <label class="asset-label">Asset Code <span style="color: #ef4444;">*</span></label>
            <input type="text" name="asset_code" id="edit_asset_code" class="asset-input" required readonly
              style="background: #f8fafc;">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
          <div class="asset-form-group">
            <label class="asset-label">Category</label>
            <select name="category_id" id="edit_category_id" class="asset-select">
              <option value="">Select Category</option>
              <?php foreach ($available_categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>">
                  <?php echo htmlspecialchars($cat['category_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="asset-form-group">
            <label class="asset-label">Location</label>
            <select name="location_id" id="edit_location_id" class="asset-select">
              <option value="">Select Location</option>
              <?php foreach ($available_locations as $loc): ?>
                <option value="<?php echo $loc['location_id']; ?>">
                  <?php echo htmlspecialchars($loc['location_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
          <div class="asset-form-group">
            <label class="asset-label">Status</label>
            <select name="status" id="edit_status" class="asset-select">
              <option value="Available">Available</option>
              <option value="In Use">In Use</option>
              <option value="Maintenance">Maintenance</option>
              <option value="Retired">Retired</option>
            </select>
          </div>
          <div class="asset-form-group">
            <label class="asset-label">Assigned To</label>
            <input type="text" name="assigned_to" id="edit_assigned_to" class="asset-input">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
          <div class="asset-form-group">
            <label class="asset-label">Brand / Model</label>
            <input type="text" name="brand" id="edit_brand" class="asset-input">
          </div>
          <div class="asset-form-group">
            <label class="asset-label">Supplier</label>
            <input type="text" name="supplier" id="edit_supplier" class="asset-input">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
          <div class="asset-form-group">
            <label class="asset-label">Condition</label>
            <select name="condition" id="edit_condition" class="asset-select">
              <option value="New">New</option>
              <option value="Good">Good</option>
              <option value="Fair">Fair</option>
              <option value="Poor">Poor</option>
              <option value="Damaged">Broken / Damaged</option>
            </select>
          </div>
          <div class="asset-form-group">
            <label class="asset-label">Sub-Location</label>
            <input type="text" name="sub_location" id="edit_sub_location" class="asset-input"
              placeholder="e.g. Rack 4, Shelf B">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
          <div class="asset-form-group">
            <label class="asset-label">Purchase Price</label>
            <input type="number" step="0.01" name="purchase_price" id="edit_purchase_price" class="asset-input">
          </div>
          <div class="asset-form-group">
            <label class="asset-label">Purchase Date</label>
            <input type="text" name="purchase_date" id="edit_purchase_date" class="asset-input datepicker">
          </div>
          <div class="asset-form-group">
            <label class="asset-label">Warranty Expiry</label>
            <input type="text" name="warranty_expiry" id="edit_warranty_expiry" class="asset-input datepicker">
          </div>
        </div>

        <div class="asset-form-group">
          <label class="asset-label">Description</label>
          <textarea name="description" id="edit_description" class="asset-textarea" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="asset-btn asset-btn-secondary" onclick="closeEditAssetModal()">Cancel</button>
        <button type="submit" class="asset-btn asset-btn-primary">Update Asset</button>
      </div>
    </form>
  </div>
</div>

<!-- Remove Asset Modal -->
<div id="removeAssetModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 500px;">
    <div class="modal-header">
      <h3 style="margin: 0; font-size: 20px; font-weight: 800;">Remove Asset</h3>
      <button onclick="closeRemoveAssetModal()"
        style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--asset-muted);">&times;</button>
    </div>
    <form id="removeAssetForm" onsubmit="submitRemoveAsset(event)">
      <input type="hidden" name="asset_code" id="remove_asset_code">
      <div class="modal-body" style="padding: 24px;">
        <p style="margin: 0 0 16px 0; color: var(--asset-muted); font-size: 14px;">
          You are about to remove <strong id="remove_asset_name_display" style="color: var(--asset-text);"></strong>
          from the active inventory. This will mark the item as "Removed".
        </p>
        <div class="asset-form-group">
          <label class="asset-label">Reason for Removal <span style="color: #ef4444;">*</span></label>
          <textarea name="removal_reason" class="asset-textarea" rows="3"
            placeholder="e.g. Beyond economical repair, Lost, Stolen, Donated..." required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="asset-btn asset-btn-secondary" onclick="closeRemoveAssetModal()">Cancel</button>
        <button type="submit" class="asset-btn asset-btn-primary"
          style="background: var(--asset-danger); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);">Mark as
          Removed</button>
      </div>
    </form>
  </div>
</div>

<script>
  console.log('Script tag loaded - START');

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

  // Edit Asset Modal Functions - ALWAYS AVAILABLE
  console.log('Edit Asset functions loaded');

  function openEditAssetModal(assetCode) {
    console.log('openEditAssetModal called with code:', assetCode);

    // Fetch asset data
    fetch(`get_asset.php?code=${assetCode}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const asset = data.asset;

          // Populate form fields
          document.getElementById('edit_asset_id').value = asset.asset_id;
          document.getElementById('edit_asset_name').value = asset.asset_name;
          document.getElementById('edit_asset_code').value = asset.asset_code;
          document.getElementById('edit_category_id').value = asset.category_id || '';
          document.getElementById('edit_location_id').value = asset.location_id || '';
          document.getElementById('edit_status').value = asset.status;
          document.getElementById('edit_assigned_to').value = asset.assigned_to || '';
          document.getElementById('edit_purchase_price').value = asset.purchase_price || '';
          document.getElementById('edit_purchase_date').value = asset.purchase_date || '';
          document.getElementById('edit_warranty_expiry').value = asset.warranty_expiry || '';
          document.getElementById('edit_description').value = asset.description || '';
          document.getElementById('edit_brand').value = asset.brand || '';
          document.getElementById('edit_supplier').value = asset.supplier || '';
          document.getElementById('edit_condition').value = asset.condition || 'Good';
          document.getElementById('edit_sub_location').value = asset.sub_location || '';

          // Show modal
          document.getElementById('editAssetModal').style.display = 'flex';
        } else {
          showToastError('Failed to load asset data');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToastError('An error occurred while loading asset data');
      });
  }

  function closeEditAssetModal() {
    document.getElementById('editAssetModal').style.display = 'none';
  }

  function submitEditAsset(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;

    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="animation: spin 1s linear infinite;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Saving...';

    fetch('update_asset.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        console.log('Update response:', data);
        if (data.success) {
          // Get the updated values from the form
          const assetCode = document.getElementById('edit_asset_code').value;
          const assetName = document.getElementById('edit_asset_name').value;
          const categorySelect = document.getElementById('edit_category_id');
          const categoryName = categorySelect.options[categorySelect.selectedIndex]?.text || 'Uncategorized';
          const locationSelect = document.getElementById('edit_location_id');
          const locationName = locationSelect.options[locationSelect.selectedIndex]?.text || 'Unassigned';
          const status = document.getElementById('edit_status').value;
          const assignedTo = document.getElementById('edit_assigned_to').value || 'Not Assigned';

          // Find and update the table row
          const tableRows = document.querySelectorAll('.asset-table tbody tr');
          tableRows.forEach(row => {
            const codeCell = row.querySelector('td:first-child');
            if (codeCell && codeCell.textContent.trim() === assetCode) {
              // Update each cell
              const cells = row.querySelectorAll('td');
              if (cells.length >= 6) {
                cells[1].innerHTML = `<span style="font-weight: 700;">${assetName}</span>`;
                cells[2].innerHTML = `<span style="font-size: 13px; color: var(--asset-muted);">${categoryName === 'Select Category' ? 'Uncategorized' : categoryName}</span>`;
                cells[3].innerHTML = `<span style="font-weight: 500;">${locationName === 'Select Location' ? 'Unassigned' : locationName}</span>`;

                // Update status badge
                const statusSlug = status.toLowerCase().replace(' ', '-');
                cells[4].innerHTML = `<span class="status-badge status-${statusSlug}"><i class="dot"></i> ${status}</span>`;

                cells[5].innerHTML = `<span style="font-weight: 500;">${assignedTo}</span>`;

                // Add highlight animation
                row.style.transition = 'background-color 0.3s ease';
                row.style.backgroundColor = '#dcfce7';
                setTimeout(() => {
                  row.style.backgroundColor = '';
                }, 1500);
              }
            }
          });

          showToastSuccess(data.message || 'Asset updated successfully!');
          closeEditAssetModal();
        } else {
          showToastError(data.message || 'Failed to update asset');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToastError('An error occurred. Please try again.');
      })
      .finally(() => {
        // Re-enable button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
      });
  }

  function openRemoveAssetModal(code, name) {
    document.getElementById('remove_asset_code').value = code;
    document.getElementById('remove_asset_name_display').textContent = name;
    document.getElementById('removeAssetModal').style.display = 'flex';
  }

  function closeRemoveAssetModal() {
    document.getElementById('removeAssetModal').style.display = 'none';
  }

  function submitRemoveAsset(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    fetch('remove_asset.php', {
      method: 'POST',
      body: formData
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showToastSuccess(data.message);
          closeRemoveAssetModal();
          setTimeout(() => window.location.reload(), 1000);
        } else {
          showToastError(data.message);
        }
      })
      .catch(e => showToastError('An error occurred while removing the asset.'));
  }

  // Close modal when clicking outside
  window.addEventListener('click', function (event) {
    const editModal = document.getElementById('editAssetModal');
    const removeModal = document.getElementById('removeAssetModal');
    if (event.target === editModal) closeEditAssetModal();
    if (event.target === removeModal) closeRemoveAssetModal();
  });

  function applyFilters() {
    const categoryId = document.getElementById('listCategoryFilter').value;
    const status = document.getElementById('listStatusFilter').value;
    const search = document.getElementById('listSearchInput').value;

    let url = 'list.php?';
    if (categoryId !== '0') url += 'category_id=' + categoryId + '&';
    if (status) url += 'status=' + encodeURIComponent(status) + '&';
    if (search) url += 'search=' + encodeURIComponent(search) + '&';

    window.location.href = url.slice(0, -1);
  }

  // Handle Enter key on search input
  document.getElementById('listSearchInput').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
      applyFilters();
    }
  });

</script>

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