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

$page_title = "Removed Assets - Asset Management";
include '../../includes/header.php';
include 'assets_styles.php';

// Pagination settings
$items_per_page = 15;
$current_page = (int) (isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1);
$offset = ($current_page - 1) * $items_per_page;

// Filtering
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where = "WHERE a.status = 'Removed'";
if ($category_id > 0) {
  $where .= " AND a.category_id = $category_id";
}
if (!empty($search)) {
  $where .= " AND (a.asset_name LIKE '%$search%' OR a.asset_code LIKE '%$search%')";
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM assets a $where";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_assets = intval($count_row['total']);
$total_pages = (int) ceil($total_assets / $items_per_page);
mysqli_free_result($count_result);

// Build query string for pagination
$filter_params = $_GET;
unset($filter_params['page']);
$base_query = http_build_query($filter_params);
$page_link = "?" . ($base_query ? $base_query . "&" : "") . "page=";

// Fetch removed assets
$assets = [];
$assets_query = "
  SELECT 
    a.asset_id,
    a.asset_code,
    a.asset_name,
    a.status,
    a.removal_reason,
    a.removed_at,
    ac.category_name
  FROM assets a
  LEFT JOIN asset_categories ac ON a.category_id = ac.category_id
  $where
  ORDER BY a.removed_at DESC
  LIMIT $items_per_page OFFSET $offset
";

if ($result = mysqli_query($conn, $assets_query)) {
  while ($row = mysqli_fetch_assoc($result)) {
    $assets[] = $row;
  }
  mysqli_free_result($result);
}

// Fetch categories for filter
$categories_query = "SELECT category_id, category_name FROM asset_categories WHERE is_active = 1 ORDER BY category_name ASC";
$categories_result = mysqli_query($conn, $categories_query);
$available_categories = [];
if ($categories_result) {
  while ($row = mysqli_fetch_assoc($categories_result)) {
    $available_categories[] = $row;
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
        <span>Removed Items</span>
      </div>
      <h1 class="asset-title">Removed Items Inventory</h1>
    </div>
  </div>

  <?php include 'assets_header.php'; ?>

  <div class="table-container">
    <div class="table-tools">
      <div class="search-input-group">
        <span class="search-icon">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </span>
        <input type="text" id="removedSearchInput" class="asset-input" style="padding-left: 40px;"
          placeholder="Search removed assets..." value="<?php echo htmlspecialchars($search); ?>">
      </div>
      <div style="display: flex; gap: 12px;">
        <select class="asset-select" style="width: auto;" id="removedCategoryFilter" onchange="applyRemovedFilters()">
          <option value="0">All Categories</option>
          <?php foreach ($available_categories as $cat): ?>
            <option value="<?php echo $cat['category_id']; ?>" <?php echo $category_id == $cat['category_id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cat['category_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button class="asset-btn asset-btn-secondary" onclick="applyRemovedFilters()">Filter</button>
      </div>
    </div>

    <table class="asset-table">
      <thead>
        <tr>
          <th>Asset Code</th>
          <th>Asset Name</th>
          <th>Category</th>
          <th>Removal Reason</th>
          <th>Removed On</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($assets)): ?>
          <tr>
            <td colspan="6" style="text-align: center; padding: 48px; color: var(--asset-muted);">No removed items found.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($assets as $asset): ?>
            <tr>
              <td><span
                  style="font-family: monospace; font-weight: 700; color: var(--asset-primary);"><?php echo $asset['asset_code']; ?></span>
              </td>
              <td><span
                  style="font-weight: 700; color: var(--asset-text);"><?php echo htmlspecialchars($asset['asset_name']); ?></span>
              </td>
              <td><?php echo htmlspecialchars($asset['category_name'] ?? 'Uncategorized'); ?></td>
              <td>
                <div style="max-width: 300px; font-size: 13px; color: var(--asset-danger); font-weight: 500;">
                  <?php echo htmlspecialchars($asset['removal_reason'] ?? 'Not specified'); ?>
                </div>
              </td>
              <td style="white-space: nowrap; font-size: 13px; color: var(--asset-muted);">
                <?php echo date('d/m/Y', strtotime($asset['removed_at'])); ?>
              </td>
              <td style="text-align: right;">
                <button onclick="restoreAsset('<?php echo $asset['asset_code']; ?>')" class="asset-btn asset-btn-secondary"
                  style="padding: 6px 12px; font-size: 12px;">
                  Restore
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
      <div
        style="padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
        <span style="font-size: 13px; color: var(--asset-muted);">
          Showing <?php echo number_format($offset + 1); ?> to
          <?php echo number_format(min($offset + $items_per_page, $total_assets)); ?> of
          <?php echo number_format($total_assets); ?> items
        </span>
        <div style="display: flex; gap: 8px;">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="<?php echo $page_link . $i; ?>"
              class="asset-btn <?php echo $i == $current_page ? 'asset-btn-primary' : 'asset-btn-secondary'; ?>"
              style="padding: 8px 14px;"><?php echo $i; ?></a>
          <?php endfor; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Restore Confirmation Modal -->
<div id="restoreModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 450px;">
    <div class="modal-header">
      <h3 style="margin: 0; font-size: 18px; font-weight: 800;">Restore Asset</h3>
      <button onclick="closeRestoreModal()"
        style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--asset-muted);">&times;</button>
    </div>
    <div class="modal-body" style="padding: 24px; text-align: center;">
      <div
        style="width: 64px; height: 64px; background: #dcfce7; color: #15803d; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
        <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
      </div>
      <p style="margin: 0 0 8px 0; font-weight: 700; color: var(--asset-text); font-size: 16px;">Are you sure?</p>
      <p style="margin: 0; color: var(--asset-muted); font-size: 14px;">This will restore asset <strong
          id="restoreAssetName" style="color: var(--asset-text);"></strong> to the active inventory.</p>
      <input type="hidden" id="restoreAssetCode">
    </div>
    <div class="modal-footer" style="justify-content: center;">
      <button class="asset-btn asset-btn-secondary" onclick="closeRestoreModal()">Cancel</button>
      <button class="asset-btn asset-btn-primary" onclick="confirmRestore()">Restore Item</button>
    </div>
  </div>
</div>

<script>
  function applyRemovedFilters() {
    const cat = document.getElementById('removedCategoryFilter').value;
    const search = document.getElementById('removedSearchInput').value;
    let url = 'removed_items.php?';
    if (cat !== '0') url += 'category_id=' + cat + '&';
    if (search) url += 'search=' + encodeURIComponent(search);
    window.location.href = url;
  }

  document.getElementById('removedSearchInput').addEventListener('keypress', e => { if (e.key === 'Enter') applyRemovedFilters(); });

  function restoreAsset(code) {
    document.getElementById('restoreAssetCode').value = code;
    document.getElementById('restoreAssetName').textContent = code;
    document.getElementById('restoreModal').style.display = 'flex';
  }

  function closeRestoreModal() {
    document.getElementById('restoreModal').style.display = 'none';
  }

  function confirmRestore() {
    const code = document.getElementById('restoreAssetCode').value;
    fetch('restore_asset.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ asset_code: code })
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showToastSuccess(data.message);
          closeRestoreModal();
          setTimeout(() => window.location.href = 'list.php', 1000);
        } else {
          showToastError(data.message);
        }
      });
  }

  // Close modal when clicking outside
  window.onclick = function (event) {
    const modal = document.getElementById('restoreModal');
    if (event.target == modal) closeRestoreModal();
  }
</script>

<?php include '../../includes/footer.php'; ?>