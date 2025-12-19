<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
require_once '../../config/database.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

$page_title = "Library Categories";
include '../../includes/header.php';

// Handle Save Category
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
  $cat_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
  $name = mysqli_real_escape_string($conn, $_POST['category_name']);
  $description = mysqli_real_escape_string($conn, $_POST['description']);
  $status = mysqli_real_escape_string($conn, $_POST['status']);

  if (empty($name)) {
    $message = "Category name is required.";
    $message_type = "error";
  } else {
    if ($cat_id > 0) {
      $sql = "UPDATE library_categories SET category_name = ?, description = ?, status = ? WHERE category_id = ?";
      $stmt = mysqli_prepare($conn, $sql);
      mysqli_stmt_bind_param($stmt, "sssi", $name, $description, $status, $cat_id);
    } else {
      $sql = "INSERT INTO library_categories (category_name, description, status) VALUES (?, ?, ?)";
      $stmt = mysqli_prepare($conn, $sql);
      mysqli_stmt_bind_param($stmt, "sss", $name, $description, $status);
    }

    if (mysqli_stmt_execute($stmt)) {
      $message = $cat_id > 0 ? "Category updated successfully!" : "Category added successfully!";
      $message_type = "success";
      if ($cat_id == 0)
        unset($_POST);
    } else {
      $message = "Error saving category: " . mysqli_error($conn);
      $message_type = "error";
    }
  }
}

// Fetch Categories
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = !empty($search) ? "WHERE category_name LIKE '%$search%' OR description LIKE '%$search%'" : "";
$sql = "SELECT * FROM library_categories $where ORDER BY category_name ASC";
$categories = mysqli_query($conn, $sql);
?>

<style>
  :root {
    --cat-primary: #2c3e50;
    --cat-secondary: #3498db;
    --cat-success: #27ae60;
    --cat-bg: #f8fafc;
    --cat-border: #e2e8f0;
    --radius-lg: 16px;
    --transition: all 250ms cubic-bezier(0.4, 0, 0.2, 1);
  }

  .category-mgmt-container {
    padding: 10px 0;
  }

  .cat-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
  }

  .cat-page-header h1 {
    font-size: 28px;
    font-weight: 800;
    color: var(--cat-primary);
    margin: 0;
  }

  .cat-page-header p {
    color: #64748b;
    margin: 4px 0 0 0;
    font-size: 15px;
  }

  .add-cat-btn {
    padding: 12px 24px;
    background: var(--cat-primary);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: var(--transition);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }

  .add-cat-btn:hover {
    background: #1e293b;
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  }

  .cat-panel {
    background: white;
    border-radius: var(--radius-lg);
    border: 1px solid var(--cat-border);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .search-wrapper {
    position: relative;
    padding: 24px;
    border-bottom: 1px solid var(--cat-border);
    background: #fafafa;
  }

  .search-input {
    width: 100%;
    padding: 12px 16px 12px 44px;
    border: 1px solid var(--cat-border);
    border-radius: 12px;
    font-size: 14px;
    background: white;
    transition: var(--transition);
  }

  .search-input:focus {
    border-color: var(--cat-secondary);
    box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
    outline: none;
  }

  .search-icon {
    position: absolute;
    left: 40px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
  }

  .cat-table {
    width: 100%;
    border-collapse: collapse;
  }

  .cat-table th {
    text-align: left;
    padding: 16px 24px;
    background: #f8fafc;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    border-bottom: 1px solid var(--cat-border);
  }

  .cat-table td {
    padding: 20px 24px;
    border-bottom: 1px solid var(--cat-border);
    font-size: 14px;
    color: var(--cat-primary);
  }

  .cat-table tr:hover {
    background: #fcfcfc;
  }

  .cat-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
  }

  .status-active {
    background: #f0fdf4;
    color: #16a34a;
  }

  .status-inactive {
    background: #fef2f2;
    color: #ef4444;
  }

  .edit-btn {
    padding: 8px 16px;
    background: #f1f5f9;
    color: var(--cat-secondary);
    border: none;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: var(--transition);
  }

  .edit-btn:hover {
    background: #e2e8f0;
    color: var(--cat-primary);
  }

  /* Modal Styles */
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    opacity: 0;
    transition: opacity 250ms ease;
  }

  .modal-overlay.active {
    display: flex;
    opacity: 1;
  }

  .modal-content {
    background: white;
    width: 100%;
    max-width: 500px;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    transform: scale(0.9);
    transition: transform 250ms cubic-bezier(0.34, 1.56, 0.64, 1);
    overflow: hidden;
  }

  .modal-overlay.active .modal-content {
    transform: scale(1);
  }

  .modal-header {
    padding: 24px;
    border-bottom: 1px solid var(--cat-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fafafa;
  }

  .modal-title {
    font-size: 20px;
    font-weight: 800;
    color: var(--cat-primary);
    margin: 0;
  }

  .close-modal {
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: var(--transition);
  }

  .close-modal:hover {
    background: #f1f5f9;
    color: #475569;
  }

  .modal-body {
    padding: 32px;
  }

  .form-group {
    margin-bottom: 24px;
  }

  .form-group label {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: var(--cat-primary);
    margin-bottom: 8px;
  }

  .form-control {
    width: 100%;
    padding: 14px;
    border: 1px solid var(--cat-border);
    border-radius: 12px;
    font-size: 14px;
    background: #f8fafc;
    transition: var(--transition);
  }

  .form-control:focus {
    border-color: var(--cat-secondary);
    background: white;
    box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
    outline: none;
  }

  .radio-group {
    display: flex;
    gap: 16px;
  }

  .radio-item {
    flex: 1;
    cursor: pointer;
  }

  .radio-item input {
    display: none;
  }

  .radio-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border: 1px solid var(--cat-border);
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    transition: var(--transition);
    color: #64748b;
  }

  .radio-item input:checked+.radio-label {
    border-color: var(--cat-secondary);
    background: #eff6ff;
    color: var(--cat-secondary);
  }

  .modal-footer {
    padding: 24px 32px;
    border-top: 1px solid var(--cat-border);
    display: flex;
    gap: 12px;
  }

  .btn {
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    transition: var(--transition);
  }

  .btn-primary {
    background: var(--cat-primary);
    color: white;
    flex: 1;
  }

  .btn-primary:hover {
    background: #1e293b;
  }

  .btn-ghost {
    background: #f1f5f9;
    color: #475569;
  }

  .btn-ghost:hover {
    background: #e2e8f0;
  }
</style>

<div class="category-mgmt-container">
  <div class="cat-page-header">
    <div>
      <a href="index.php"
        style="display: inline-flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-weight: 600; font-size: 13px; margin-bottom: 12px; transition: color 0.2s;"
        onmouseover="this.style.color='var(--cat-secondary)'" onmouseout="this.style.color='#64748b'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M19 12H5M12 19l-7-7 7-7" />
        </svg>
        Back to Dashboard
      </a>
      <h1>Library Categories</h1>
      <p>Organize your books into searchable, academic genres and departments.</p>
    </div>
    <button class="add-cat-btn" onclick="openModal()">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M12 5v14M5 12h14" />
      </svg>
      Add Category
    </button>
  </div>

  <div class="cat-panel">
    <div class="search-wrapper">
      <form method="GET">
        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2.5">
          <circle cx="11" cy="11" r="8" />
          <path d="M21 21l-4.35-4.35" />
        </svg>
        <input type="text" name="search" class="search-input" placeholder="Search categories..."
          value="<?php echo htmlspecialchars($search); ?>">
      </form>
    </div>

    <table class="cat-table">
      <thead>
        <tr>
          <th>Category Name</th>
          <th>Description</th>
          <th>Status</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
          <tr>
            <td style="font-weight: 700;"><?php echo htmlspecialchars($cat['category_name']); ?></td>
            <td style="color: #64748b; font-size: 13px;">
              <?php echo htmlspecialchars($cat['description'] ?: 'No description'); ?>
            </td>
            <td>
              <span class="cat-status-badge status-<?php echo strtolower($cat['status']); ?>">
                <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span>
                <?php echo $cat['status']; ?>
              </span>
            </td>
            <td style="text-align: right;">
              <button class="edit-btn" onclick="openModal(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Edit
              </button>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Category Modal -->
<div class="modal-overlay" id="catModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2 class="modal-title" id="modalTitle">Add New Category</h2>
      <button class="close-modal" onclick="closeModal()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M18 6L6 18M6 6l12 12" />
        </svg>
      </button>
    </div>
    <form action="categories.php" method="POST">
      <div class="modal-body">
        <input type="hidden" name="category_id" id="cat_id" value="0">
        <div class="form-group">
          <label>Category Name</label>
          <input type="text" name="category_name" id="cat_name" class="form-control" placeholder="e.g. Science"
            required>
        </div>
        <div class="form-group">
          <label>Description (Optional)</label>
          <textarea name="description" id="cat_desc" class="form-control" style="min-height: 100px; resize: none;"
            placeholder="Brief details about this category..."></textarea>
        </div>
        <div class="form-group">
          <label>Status</label>
          <div class="radio-group">
            <label class="radio-item">
              <input type="radio" name="status" id="status_active" value="Active" checked>
              <div class="radio-label">Active</div>
            </label>
            <label class="radio-item">
              <input type="radio" name="status" id="status_inactive" value="Inactive">
              <div class="radio-label">Inactive</div>
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" name="save_category" class="btn btn-primary" id="saveBtn">Save Category</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openModal(data = null) {
    const modal = document.getElementById('catModal');
    const title = document.getElementById('modalTitle');
    const saveBtn = document.getElementById('saveBtn');

    if (data) {
      title.innerText = 'Edit Category';
      saveBtn.innerText = 'Update Category';
      document.getElementById('cat_id').value = data.category_id;
      document.getElementById('cat_name').value = data.category_name;
      document.getElementById('cat_desc').value = data.description || '';
      document.getElementById('status_' + data.status.toLowerCase()).checked = true;
    } else {
      title.innerText = 'Add New Category';
      saveBtn.innerText = 'Save Category';
      document.getElementById('cat_id').value = '0';
      document.getElementById('cat_name').value = '';
      document.getElementById('cat_desc').value = '';
      document.getElementById('status_active').checked = true;
    }

    modal.classList.add('active');
  }

  function closeModal() {
    document.getElementById('catModal').classList.remove('active');
  }

  // Close on backdrop click
  document.getElementById('catModal').addEventListener('click', function (e) {
    if (e.target === this) closeModal();
  });

  <?php if (!empty($message)): ?>
    document.addEventListener('DOMContentLoaded', function () {
      showToast("<?php echo $message; ?>", "<?php echo $message_type; ?>");
    });
  <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>