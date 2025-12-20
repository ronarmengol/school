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
$total_categories = mysqli_num_rows($categories);

// Stats
$active_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM library_categories WHERE status = 'Active'"))['count'] ?? 0;
$inactive_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM library_categories WHERE status = 'Inactive'"))['count'] ?? 0;
?>

<style>
  :root {
    --lib-primary: #f59e0b;
    --lib-primary-dark: #d97706;
    --lib-primary-light: #fcd34d;
    --lib-secondary: #0ea5e9;
    --lib-success: #10b981;
    --lib-warning: #f59e0b;
    --lib-danger: #ef4444;
    --lib-bg: #f8fafc;
    --lib-card: #ffffff;
    --lib-border: #e2e8f0;
    --lib-text: #1e293b;
    --lib-muted: #64748b;
    --lib-light: #94a3b8;
  }

  .categories-page {
    min-height: 100vh;
    padding-bottom: 60px;
  }

  .categories-container {
    max-width: 1000px;
    margin: 0 auto;
  }

  /* Header Section */
  .page-header {
    background: linear-gradient(135deg, var(--lib-primary) 0%, var(--lib-primary-dark) 100%);
    color: white;
    padding: 32px;
    border-radius: 20px;
    margin-bottom: 32px;
    position: relative;
    overflow: hidden;
  }

  .page-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
  }

  .header-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .header-text h1 {
    font-size: 28px;
    font-weight: 800;
    margin: 0 0 8px 0;
  }

  .header-text p {
    font-size: 15px;
    opacity: 0.9;
    margin: 0;
  }

  .btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.2s ease;
  }

  .btn-back:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
  }

  /* Stats Cards */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 32px;
  }

  .stat-card {
    background: var(--lib-card);
    padding: 24px;
    border-radius: 16px;
    border: 1px solid var(--lib-border);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.2s ease;
  }

  .stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px -10px rgba(245, 158, 11, 0.2);
  }

  .stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .stat-icon.total {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #d97706;
  }

  .stat-icon.active {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #16a34a;
  }

  .stat-icon.inactive {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #dc2626;
  }

  .stat-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--lib-text);
  }

  .stat-label {
    font-size: 13px;
    color: var(--lib-muted);
    margin-top: 4px;
    font-weight: 500;
  }

  /* Table Card */
  .table-card {
    background: var(--lib-card);
    border-radius: 20px;
    border: 1px solid var(--lib-border);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    overflow: hidden;
  }

  .card-header {
    padding: 24px 32px;
    border-bottom: 1px solid var(--lib-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(to right, #fafafb, #f8fafc);
    flex-wrap: wrap;
    gap: 16px;
  }

  .card-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .card-header-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--lib-primary) 0%, var(--lib-primary-dark) 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
  }

  .card-header-text h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: var(--lib-text);
  }

  .card-header-text p {
    margin: 4px 0 0 0;
    font-size: 14px;
    color: var(--lib-muted);
  }

  .btn-add {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: linear-gradient(135deg, var(--lib-primary) 0%, var(--lib-primary-dark) 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
  }

  .btn-add:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(245, 158, 11, 0.35);
  }

  /* Search Bar */
  .search-bar {
    padding: 20px 32px;
    border-bottom: 1px solid var(--lib-border);
    background: #fafafa;
  }

  .search-wrap {
    position: relative;
    max-width: 400px;
  }

  .search-wrap .search-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--lib-muted);
    pointer-events: none;
  }

  .search-input {
    width: 100%;
    padding: 14px 16px 14px 48px;
    border: 2px solid var(--lib-border);
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.2s ease;
    background: white;
  }

  .search-input:focus {
    outline: none;
    border-color: var(--lib-primary);
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
  }

  /* Table */
  .cat-table {
    width: 100%;
    border-collapse: collapse;
  }

  .cat-table th {
    text-align: left;
    padding: 14px 24px;
    background: #f8fafc;
    color: var(--lib-muted);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--lib-border);
  }

  .cat-table td {
    padding: 20px 24px;
    border-bottom: 1px solid var(--lib-border);
    font-size: 14px;
    color: var(--lib-text);
    vertical-align: middle;
  }

  .cat-table tr:hover {
    background: linear-gradient(90deg, #fffbeb 0%, transparent 50%);
  }

  .cat-table tr:last-child td {
    border-bottom: none;
  }

  .cat-name {
    font-weight: 700;
    color: var(--lib-text);
  }

  .cat-desc {
    font-size: 13px;
    color: var(--lib-muted);
    max-width: 300px;
  }

  .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 12px;
  }

  .status-badge .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
  }

  .status-active {
    background: #dcfce7;
    color: #16a34a;
  }

  .status-inactive {
    background: #fee2e2;
    color: #dc2626;
  }

  .btn-edit {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: #f1f5f9;
    color: var(--lib-muted);
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .btn-edit:hover {
    background: #e2e8f0;
    color: var(--lib-text);
  }

  /* Modal */
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
  }

  .modal-overlay.active {
    display: flex;
  }

  .modal-content {
    background: white;
    width: 100%;
    max-width: 500px;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
  }

  .modal-header {
    padding: 24px 32px;
    border-bottom: 1px solid var(--lib-border);
    background: linear-gradient(to right, #fafafb, #f8fafc);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .modal-title {
    font-size: 20px;
    font-weight: 800;
    color: var(--lib-text);
    margin: 0;
  }

  .modal-close {
    width: 36px;
    height: 36px;
    background: #f1f5f9;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--lib-muted);
    transition: all 0.2s ease;
  }

  .modal-close:hover {
    background: #e2e8f0;
    color: var(--lib-text);
  }

  .modal-body {
    padding: 32px;
  }

  .form-group {
    margin-bottom: 24px;
  }

  .form-group:last-child {
    margin-bottom: 0;
  }

  .form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--lib-text);
    margin-bottom: 8px;
  }

  .form-input,
  .form-textarea {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid var(--lib-border);
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.2s ease;
    background: var(--lib-bg);
  }

  .form-textarea {
    min-height: 100px;
    resize: none;
    font-family: inherit;
  }

  .form-input:focus,
  .form-textarea:focus {
    outline: none;
    border-color: var(--lib-primary);
    background: white;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
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
    padding: 14px;
    border: 2px solid var(--lib-border);
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    color: var(--lib-muted);
    transition: all 0.2s ease;
  }

  .radio-item input:checked+.radio-label {
    border-color: var(--lib-primary);
    background: #fffbeb;
    color: var(--lib-primary-dark);
  }

  .modal-footer {
    padding: 24px 32px;
    border-top: 1px solid var(--lib-border);
    display: flex;
    gap: 12px;
    background: #fafafa;
  }

  .btn-cancel {
    padding: 14px 24px;
    background: white;
    color: var(--lib-muted);
    border: 2px solid var(--lib-border);
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .btn-cancel:hover {
    background: #f8fafc;
    color: var(--lib-text);
  }

  .btn-save {
    flex: 1;
    padding: 14px 24px;
    background: linear-gradient(135deg, var(--lib-primary) 0%, var(--lib-primary-dark) 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
  }

  .btn-save:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(245, 158, 11, 0.35);
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 80px 40px;
  }

  .empty-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: #fef3c7;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--lib-primary);
  }

  .empty-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--lib-text);
    margin-bottom: 8px;
  }

  .empty-text {
    font-size: 14px;
    color: var(--lib-muted);
  }

  /* Responsive */
  @media (max-width: 768px) {
    .categories-container {
      padding: 0 16px;
    }

    .page-header {
      padding: 24px;
    }

    .header-content {
      flex-direction: column;
      align-items: flex-start;
      gap: 16px;
    }

    .stats-row {
      grid-template-columns: 1fr;
    }

    .card-header {
      flex-direction: column;
      align-items: flex-start;
    }
  }
</style>

<div class="categories-page">
  <div class="categories-container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-text">
          <h1>Library Categories</h1>
          <p>Organize your books into searchable, academic genres and departments.</p>
        </div>
        <a href="index.php" class="btn-back">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Back to Library
        </a>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon total">
          <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
          </svg>
        </div>
        <div>
          <div class="stat-value"><?php echo $total_categories; ?></div>
          <div class="stat-label">Total Categories</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon active">
          <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <div class="stat-value"><?php echo $active_count; ?></div>
          <div class="stat-label">Active Categories</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon inactive">
          <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
          </svg>
        </div>
        <div>
          <div class="stat-value"><?php echo $inactive_count; ?></div>
          <div class="stat-label">Inactive Categories</div>
        </div>
      </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">
      <div class="card-header">
        <div class="card-header-left">
          <div class="card-header-icon">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
          </div>
          <div class="card-header-text">
            <h2>Category List</h2>
            <p><?php echo $total_categories; ?> categories</p>
          </div>
        </div>
        <button class="btn-add" onclick="openModal()">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
          </svg>
          Add Category
        </button>
      </div>

      <div class="search-bar">
        <div class="search-wrap">
          <form method="GET">
            <svg class="search-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" name="search" class="search-input" placeholder="Search categories..."
              value="<?php echo htmlspecialchars($search); ?>">
          </form>
        </div>
      </div>

      <?php if ($total_categories > 0): ?>
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
                <td><span class="cat-name"><?php echo htmlspecialchars($cat['category_name']); ?></span></td>
                <td><span class="cat-desc"><?php echo htmlspecialchars($cat['description'] ?: 'No description'); ?></span>
                </td>
                <td>
                  <span class="status-badge status-<?php echo strtolower($cat['status']); ?>">
                    <span class="dot"></span>
                    <?php echo $cat['status']; ?>
                  </span>
                </td>
                <td style="text-align: right;">
                  <button class="btn-edit" onclick="openModal(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                  </button>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
          </div>
          <div class="empty-title">No Categories Found</div>
          <div class="empty-text">Start by creating your first library category to organize books.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Category Modal -->
<div class="modal-overlay" id="catModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2 class="modal-title" id="modalTitle">Add New Category</h2>
      <button class="modal-close" onclick="closeModal()">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <form action="categories.php" method="POST">
      <div class="modal-body">
        <input type="hidden" name="category_id" id="cat_id" value="0">
        <div class="form-group">
          <label class="form-label">Category Name *</label>
          <input type="text" name="category_name" id="cat_name" class="form-input" placeholder="e.g. Science" required>
        </div>
        <div class="form-group">
          <label class="form-label">Description (Optional)</label>
          <textarea name="description" id="cat_desc" class="form-textarea"
            placeholder="Brief details about this category..."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
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
        <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
        <button type="submit" name="save_category" class="btn-save" id="saveBtn">Save Category</button>
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

  document.getElementById('catModal').addEventListener('click', function (e) {
    if (e.target === this) closeModal();
  });

  <?php if (!empty($message)): ?>
    document.addEventListener('DOMContentLoaded', function () {
      <?php if ($message_type === 'success'): ?>
        showToastSuccess("<?php echo addslashes($message); ?>");
      <?php else: ?>
        showToastError("<?php echo addslashes($message); ?>");
      <?php endif; ?>
    });
  <?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>