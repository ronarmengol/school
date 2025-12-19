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

$page_title = "Asset Categories - Asset Management";
include '../../includes/header.php';
include 'assets_styles.php';

// Fetch Categories from Database with Asset Counts
$categories_query = "
  SELECT 
    ac.category_id,
    ac.category_name,
    ac.description,
    ac.is_active,
    COUNT(CASE WHEN a.status != 'Removed' THEN a.asset_id END) as asset_count
  FROM asset_categories ac
  LEFT JOIN assets a ON ac.category_id = a.category_id
  WHERE ac.category_name NOT LIKE '%Library%' AND ac.category_name NOT LIKE '%Book%'
  GROUP BY ac.category_id
  ORDER BY ac.category_name ASC
";

$categories = [];
if ($result = mysqli_query($conn, $categories_query)) {
  while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = [
      'id' => $row['category_id'],
      'name' => $row['category_name'],
      'description' => $row['description'],
      'assets' => intval($row['asset_count']),
      'status' => $row['is_active'] ? 'Active' : 'Inactive'
    ];
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
        <span>Categories</span>
      </div>
      <h1 class="asset-title">Asset Categories</h1>
    </div>
    <div class="header-actions">
      <button class="asset-btn asset-btn-primary" onclick="openAddCategoryModal()">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        Add Category
      </button>
    </div>
  </div>

  <?php include 'assets_header.php'; ?>

  <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
    <?php if (empty($categories)): ?>
      <div
        style="grid-column: 1 / -1; text-align: center; padding: 100px 24px; background: white; border-radius: 16px; border: 1px dashed var(--asset-border);">
        <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24"
          style="margin: 0 auto 20px; opacity: 0.2; color: var(--asset-primary);">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
        <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 700; color: var(--asset-text);">No Categories Found
        </h3>
        <p style="margin: 0 0 24px 0; color: var(--asset-muted);">Start by creating your first asset category.</p>
        <button class="asset-btn asset-btn-primary" style="margin: 0 auto;" onclick="openAddCategoryModal()">Add
          Category</button>
      </div>
    <?php else: ?>
      <?php foreach ($categories as $cat): ?>
        <div class="asset-card" style="padding: 24px; position: relative;">
          <div style="position: absolute; top: 20px; right: 20px;">
            <span
              style="font-size: 11px; padding: 4px 10px; border-radius: 99px; font-weight: 700; <?php echo $cat['status'] == 'Active' ? 'background: #dcfce7; color: #15803d;' : 'background: #f1f5f9; color: #64748b;'; ?>">
              <?php echo $cat['status']; ?>
            </span>
          </div>

          <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800; color: var(--asset-text);">
            <?php echo htmlspecialchars($cat['name']); ?>
          </h3>
          <p style="margin: 0 0 24px 0; font-size: 14px; color: var(--asset-muted);">
            Total Items: <strong><?php echo $cat['assets']; ?></strong>
          </p>

          <div style="display: flex; gap: 8px; border-top: 1px solid var(--asset-border); padding-top: 20px;">
            <a href="list.php?category_id=<?php echo $cat['id']; ?>" class="asset-btn asset-btn-secondary"
              style="flex: 1; justify-content: center; padding: 8px;" title="View Items">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </a>
            <button onclick="editCategory(<?php echo $cat['id']; ?>)" class="asset-btn asset-btn-secondary"
              style="flex: 1; justify-content: center; padding: 8px;" title="Edit Category">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <?php if ($cat['assets'] == 0): ?>
              <button onclick="quickDeleteCategory(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>')"
                class="asset-btn asset-btn-secondary"
                style="flex: 1; justify-content: center; padding: 8px; color: #ef4444; border-color: #fee2e2;"
                title="Delete Category">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 500px;">
    <div class="modal-header">
      <h3 style="margin: 0; font-size: 20px; font-weight: 800;">Add New Category</h3>
      <button onclick="closeAddCategoryModal()"
        style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--asset-muted);">&times;</button>
    </div>
    <form id="addCategoryForm" onsubmit="submitCategory(event)">
      <div class="modal-body" style="padding: 24px;">
        <div class="asset-form-group">
          <label class="asset-label">Category Name <span style="color: #ef4444;">*</span></label>
          <input type="text" name="category_name" class="asset-input" placeholder="e.g. ICT Equipment" required>
        </div>
        <div class="asset-form-group">
          <label class="asset-label">Description (Optional)</label>
          <textarea name="description" class="asset-textarea" rows="3"
            placeholder="Brief description of this category..."></textarea>
        </div>
        <div class="asset-form-group" style="margin-bottom: 0; display: flex; align-items: center; gap: 8px;">
          <input type="checkbox" name="is_active" id="add_is_active" checked
            style="width: 18px; height: 18px; cursor: pointer;">
          <label for="add_is_active"
            style="margin: 0; font-size: 14px; font-weight: 600; color: var(--asset-text); cursor: pointer;">Active
            Category</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="asset-btn asset-btn-secondary" onclick="closeAddCategoryModal()">Cancel</button>
        <button type="submit" class="asset-btn asset-btn-primary">Save Category</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 500px;">
    <div class="modal-header">
      <h3 style="margin: 0; font-size: 20px; font-weight: 800;">Edit Category</h3>
      <button onclick="closeEditCategoryModal()"
        style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--asset-muted);">&times;</button>
    </div>
    <form id="editCategoryForm" onsubmit="submitEditCategory(event)">
      <input type="hidden" name="category_id" id="edit_category_id">
      <div class="modal-body" style="padding: 24px;">
        <div class="asset-form-group">
          <label class="asset-label">Category Name <span style="color: #ef4444;">*</span></label>
          <input type="text" name="category_name" id="edit_category_name" class="asset-input" required>
        </div>
        <div class="asset-form-group">
          <label class="asset-label">Description (Optional)</label>
          <textarea name="description" id="edit_description" class="asset-textarea" rows="3"></textarea>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <div class="asset-form-group" style="margin-bottom: 0; display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" name="is_active" id="edit_is_active"
              style="width: 18px; height: 18px; cursor: pointer;">
            <label for="edit_is_active"
              style="margin: 0; font-size: 14px; font-weight: 600; color: var(--asset-text); cursor: pointer;">Active</label>
          </div>
          <button type="button" onclick="deleteCategory()"
            style="color: #ef4444; border: none; background: none; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Delete
          </button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="asset-btn asset-btn-secondary" onclick="closeEditCategoryModal()">Cancel</button>
        <button type="submit" class="asset-btn asset-btn-primary">Update Category</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Category Confirmation Modal -->
<div id="deleteCategoryModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 450px;">
    <div class="modal-header">
      <h3 style="margin: 0; font-size: 18px; font-weight: 800;">Delete Category</h3>
      <button onclick="closeDeleteCategoryModal()"
        style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--asset-muted);">&times;</button>
    </div>
    <div class="modal-body" style="padding: 24px; text-align: center;">
      <div
        style="width: 64px; height: 64px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
        <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
      </div>
      <p style="margin: 0 0 8px 0; font-weight: 700; color: var(--asset-text); font-size: 16px;">Delete Category?</p>
      <p style="margin: 0; color: var(--asset-muted); font-size: 14px;">Are you sure you want to delete <strong
          id="deleteCategoryNameDisplay" style="color: var(--asset-text);"></strong>? This action cannot be undone.</p>
      <input type="hidden" id="deleteCategoryId">
    </div>
    <div class="modal-footer" style="justify-content: center;">
      <button class="asset-btn asset-btn-secondary" onclick="closeDeleteCategoryModal()">Cancel</button>
      <button class="asset-btn asset-btn-primary"
        style="background: var(--asset-danger); border-color: var(--asset-danger);"
        onclick="confirmDeleteCategory()">Delete Now</button>
    </div>
  </div>
</div>

<script>
  function openAddCategoryModal() {
    document.getElementById('addCategoryModal').style.display = 'flex';
  }

  function closeAddCategoryModal() {
    document.getElementById('addCategoryModal').style.display = 'none';
  }

  function submitCategory(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    fetch('save_category.php', {
      method: 'POST',
      body: formData
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showToastSuccess(data.message);
          closeAddCategoryModal();
          setTimeout(() => window.location.reload(), 1000);
        } else {
          showToastError(data.message);
        }
      })
      .catch(e => showToastError('An error occurred.'));
  }

  function editCategory(id) {
    fetch(`get_category.php?id=${id}`)
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          const cat = data.category;
          document.getElementById('edit_category_id').value = cat.category_id;
          document.getElementById('edit_category_name').value = cat.category_name;
          document.getElementById('edit_description').value = cat.description || '';
          document.getElementById('edit_is_active').checked = cat.is_active == 1;
          document.getElementById('editCategoryModal').style.display = 'flex';
        } else {
          showToastError(data.message);
        }
      });
  }

  function closeEditCategoryModal() {
    document.getElementById('editCategoryModal').style.display = 'none';
  }

  function submitEditCategory(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    fetch('update_category.php', {
      method: 'POST',
      body: formData
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showToastSuccess(data.message);
          closeEditCategoryModal();
          setTimeout(() => window.location.reload(), 1000);
        } else {
          showToastError(data.message);
        }
      });
  }

  function deleteCategory() {
    const id = document.getElementById('edit_category_id').value;
    const name = document.getElementById('edit_category_name').value;
    quickDeleteCategory(id, name);
  }

  function quickDeleteCategory(id, name) {
    document.getElementById('deleteCategoryId').value = id;
    document.getElementById('deleteCategoryNameDisplay').textContent = name;
    document.getElementById('deleteCategoryModal').style.display = 'flex';
  }

  function closeDeleteCategoryModal() {
    document.getElementById('deleteCategoryModal').style.display = 'none';
  }

  function confirmDeleteCategory() {
    const id = document.getElementById('deleteCategoryId').value;
    fetch('delete_category.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ category_id: id })
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showToastSuccess(data.message);
          closeDeleteCategoryModal();
          closeEditCategoryModal();
          setTimeout(() => window.location.reload(), 1000);
        } else {
          showToastError(data.message);
        }
      });
  }

  window.onclick = function (event) {
    if (event.target == document.getElementById('addCategoryModal')) closeAddCategoryModal();
    if (event.target == document.getElementById('editCategoryModal')) closeEditCategoryModal();
    if (event.target == document.getElementById('deleteCategoryModal')) closeDeleteCategoryModal();
  }
</script>

<?php include '../../includes/footer.php'; ?>