<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$page_title = "Activity Log - Asset Management";
include '../../includes/header.php';

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Filter parameters
$filter_action = isset($_GET['action']) ? $_GET['action'] : '';
$filter_user = isset($_GET['user']) ? intval($_GET['user']) : 0;
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause
$where_conditions = [];
if ($filter_action) {
  $where_conditions[] = "aal.action_type = '" . mysqli_real_escape_string($conn, $filter_action) . "'";
}
if ($filter_user > 0) {
  $where_conditions[] = "aal.performed_by = " . $filter_user;
}
if ($filter_date_from) {
  $where_conditions[] = "DATE(aal.created_at) >= '" . mysqli_real_escape_string($conn, $filter_date_from) . "'";
}
if ($filter_date_to) {
  $where_conditions[] = "DATE(aal.created_at) <= '" . mysqli_real_escape_string($conn, $filter_date_to) . "'";
}
if ($search_query) {
  $search_escaped = mysqli_real_escape_string($conn, $search_query);
  $where_conditions[] = "(aal.description LIKE '%$search_escaped%' OR a.asset_code LIKE '%$search_escaped%' OR a.asset_name LIKE '%$search_escaped%')";
}

$where_clause = '';
if (!empty($where_conditions)) {
  $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count for pagination
$count_query = "
  SELECT COUNT(*) as total 
  FROM asset_activity_log aal
  LEFT JOIN assets a ON aal.asset_id = a.asset_id
  $where_clause
";
$total_items = 0;
if ($count_result = mysqli_query($conn, $count_query)) {
  $count_row = mysqli_fetch_assoc($count_result);
  $total_items = $count_row['total'];
  mysqli_free_result($count_result);
}

$total_pages = ceil($total_items / $items_per_page);

// Fetch activity log with pagination
$activity_log = [];
$activity_query = "
  SELECT 
    aal.log_id,
    aal.asset_id,
    aal.action_type,
    aal.description,
    aal.created_at,
    u.user_id,
    u.username,
    u.full_name,
    a.asset_code,
    a.asset_name
  FROM asset_activity_log aal
  LEFT JOIN users u ON aal.performed_by = u.user_id
  LEFT JOIN assets a ON aal.asset_id = a.asset_id
  $where_clause
  ORDER BY aal.created_at DESC
  LIMIT $items_per_page OFFSET $offset
";

if ($activity_result = mysqli_query($conn, $activity_query)) {
  while ($row = mysqli_fetch_assoc($activity_result)) {
    // Calculate time ago
    $time_diff = time() - strtotime($row['created_at']);
    if ($time_diff < 60) {
      $time_ago = 'Just now';
    } elseif ($time_diff < 3600) {
      $time_ago = floor($time_diff / 60) . ' min ago';
    } elseif ($time_diff < 86400) {
      $time_ago = floor($time_diff / 3600) . ' hrs ago';
    } elseif ($time_diff < 604800) {
      $time_ago = floor($time_diff / 86400) . ' days ago';
    } else {
      $time_ago = date('M j, Y', strtotime($row['created_at']));
    }

    $activity_log[] = [
      'log_id' => $row['log_id'],
      'asset_id' => $row['asset_id'],
      'asset_code' => $row['asset_code'],
      'asset_name' => $row['asset_name'],
      'action_type' => $row['action_type'],
      'description' => $row['description'],
      'user_id' => $row['user_id'],
      'username' => $row['username'] ?? 'System',
      'full_name' => $row['full_name'] ?? 'System',
      'created_at' => $row['created_at'],
      'time_ago' => $time_ago
    ];
  }
  mysqli_free_result($activity_result);
}

// Get unique action types for filter
$action_types = [];
$action_query = "SELECT DISTINCT action_type FROM asset_activity_log ORDER BY action_type";
if ($action_result = mysqli_query($conn, $action_query)) {
  while ($row = mysqli_fetch_assoc($action_result)) {
    $action_types[] = $row['action_type'];
  }
  mysqli_free_result($action_result);
}

// Get users who have performed actions
$users_list = [];
$users_query = "
  SELECT DISTINCT u.user_id, u.username, u.full_name 
  FROM users u
  INNER JOIN asset_activity_log aal ON u.user_id = aal.performed_by
  ORDER BY u.username
";
if ($users_result = mysqli_query($conn, $users_query)) {
  while ($row = mysqli_fetch_assoc($users_result)) {
    $users_list[] = $row;
  }
  mysqli_free_result($users_result);
}

// Helper function to get action icon and color
function getActionStyle($action_type)
{
  $styles = [
    'Registration' => ['icon' => 'plus', 'color' => '#10b981', 'bg' => '#d1fae5'],
    'Assignment' => ['icon' => 'arrow-right', 'color' => '#3b82f6', 'bg' => '#dbeafe'],
    'Transfer' => ['icon' => 'refresh', 'color' => '#8b5cf6', 'bg' => '#ede9fe'],
    'Maintenance' => ['icon' => 'tool', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
    'Status Change' => ['icon' => 'edit', 'color' => '#06b6d4', 'bg' => '#cffafe'],
    'Retirement' => ['icon' => 'archive', 'color' => '#ef4444', 'bg' => '#fee2e2'],
    'Other' => ['icon' => 'info', 'color' => '#6b7280', 'bg' => '#f3f4f6']
  ];

  return $styles[$action_type] ?? $styles['Other'];
}

// Helper function to get user initials
function getUserInitials($name)
{
  if ($name == 'System')
    return 'SYS';
  $words = explode(' ', $name);
  if (count($words) >= 2) {
    return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
  }
  return strtoupper(substr($name, 0, 2));
}
?>

<style>
  :root {
    --activity-primary: #3b82f6;
    --activity-bg: #f5f6fa;
    --activity-card-bg: #ffffff;
    --activity-border: #e2e8f0;
    --activity-text: #1e293b;
    --activity-muted: #64748b;
    --activity-hover: #f1f5f9;
  }

  .activity-page {
    background: var(--activity-bg);
    min-height: 100vh;
    padding-bottom: 40px;
  }

  .activity-container {
    max-width: 1400px;
    margin: 0 auto;
    color: var(--activity-text);
  }

  /* Force navigation link colors */
  .activity-container .asset-nav-link {
    color: #1e293b !important;
    background: transparent;
  }

  .activity-container .asset-nav-link.active {
    color: #ffffff !important;
    background: #3b82f6 !important;
  }

  .activity-container .asset-nav-link:hover:not(.active) {
    color: #3b82f6 !important;
    background: #f1f5f9;
  }

  /* Header Section - Simplified to match app brand */
  .activity-page-header {
    background: var(--activity-card-bg);
    padding: 32px 24px;
    margin-bottom: 24px;
    border-bottom: 1px solid var(--activity-border);
  }

  .activity-header-content {
    max-width: 1400px;
    margin: 0 auto;
  }

  .activity-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    margin-bottom: 12px;
    color: var(--activity-muted);
  }

  .activity-breadcrumb a {
    color: var(--activity-muted);
    text-decoration: none;
    transition: color 0.2s;
  }

  .activity-breadcrumb a:hover {
    color: var(--activity-primary);
  }

  .activity-page-title {
    font-size: 28px;
    font-weight: 800;
    margin: 0 0 8px 0;
    letter-spacing: -0.025em;
    color: var(--activity-text);
  }

  .activity-page-subtitle {
    font-size: 15px;
    color: var(--activity-muted);
    margin: 0;
  }

  /* Filter Section */
  .activity-filters {
    background: var(--activity-card-bg);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    border: 1px solid var(--activity-border);
  }

  .filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
  }

  .filter-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .filter-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--activity-text);
    letter-spacing: 0.3px;
  }

  .filter-input,
  .filter-select {
    padding: 10px 14px;
    border: 1.5px solid var(--activity-border);
    border-radius: 10px;
    font-size: 14px;
    color: var(--activity-text);
    background: white;
    transition: all 0.2s;
  }

  .filter-input:focus,
  .filter-select:focus {
    outline: none;
    border-color: var(--activity-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .search-wrapper {
    position: relative;
    grid-column: span 2;
  }

  .search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--activity-muted);
    pointer-events: none;
  }

  .search-input {
    padding-left: 42px;
    width: 100%;
  }

  .filter-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
  }

  .btn-filter {
    padding: 10px 24px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: transparent;
  }

  .btn-primary {
    color: var(--activity-primary);
    border-color: var(--activity-primary);
  }

  .btn-primary:hover {
    background: var(--activity-primary);
    color: white;
  }

  .btn-secondary {
    color: var(--activity-muted);
    border-color: var(--activity-border);
  }

  .btn-secondary:hover {
    background: var(--activity-hover);
    color: var(--activity-text);
  }

  /* Stats Bar */
  .activity-stats {
    background: var(--activity-card-bg);
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    border: 1px solid var(--activity-border);
  }

  .stats-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--activity-text);
  }

  .stats-count {
    font-size: 14px;
    color: var(--activity-muted);
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .stats-badge {
    background: #eff6ff;
    color: var(--activity-primary);
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 13px;
  }

  /* Activity Timeline */
  .activity-timeline {
    background: var(--activity-card-bg);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    border: 1px solid var(--activity-border);
  }

  .activity-entry {
    padding: 24px;
    border-bottom: 1px solid var(--activity-border);
    transition: all 0.2s;
    cursor: pointer;
    position: relative;
  }

  .activity-entry:last-child {
    border-bottom: none;
  }

  .activity-entry:hover {
    background: var(--activity-hover);
  }

  .activity-entry::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: transparent;
    transition: background 0.2s;
  }

  .activity-entry:hover::before {
    background: var(--activity-primary);
  }

  .activity-row {
    display: flex;
    gap: 20px;
    align-items: start;
  }

  /* User Avatar */
  .user-avatar {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--activity-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
  }

  /* Activity Content */
  .activity-content {
    flex: 1;
    min-width: 0;
  }

  .activity-header-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
    flex-wrap: wrap;
  }

  .activity-badge {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .asset-code-badge {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    font-weight: 700;
    color: var(--activity-primary);
    background: #eff6ff;
    padding: 4px 10px;
    border-radius: 6px;
  }

  .activity-description {
    font-size: 15px;
    color: var(--activity-text);
    line-height: 1.6;
    margin-bottom: 12px;
  }

  .activity-meta {
    display: flex;
    align-items: center;
    gap: 20px;
    font-size: 13px;
    color: var(--activity-muted);
    flex-wrap: wrap;
  }

  .meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .meta-icon {
    width: 16px;
    height: 16px;
    opacity: 0.7;
  }

  .user-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  .user-name {
    font-weight: 600;
    color: var(--activity-text);
  }

  /* Action Button */
  .view-asset-btn {
    padding: 8px 12px;
    border: 2px solid var(--activity-border);
    border-radius: 8px;
    background: white;
    color: var(--activity-muted);
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
  }

  .view-asset-btn:hover {
    border-color: var(--activity-primary);
    color: var(--activity-primary);
    background: #eff6ff;
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 80px 24px;
    background: var(--activity-card-bg);
    border-radius: 16px;
  }

  .empty-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    opacity: 0.3;
    color: var(--activity-muted);
  }

  .empty-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--activity-text);
    margin-bottom: 8px;
  }

  .empty-subtitle {
    font-size: 15px;
    color: var(--activity-muted);
  }

  /* Pagination */
  .pagination-wrapper {
    padding: 20px 24px;
    background: #f8fafc;
    border-top: 1px solid var(--activity-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 0 0 16px 16px;
  }

  .pagination-info {
    font-size: 14px;
    color: var(--activity-muted);
    font-weight: 500;
  }

  .pagination-controls {
    display: flex;
    gap: 8px;
  }

  .page-btn {
    padding: 8px 14px;
    border: 2px solid var(--activity-border);
    border-radius: 8px;
    background: white;
    color: var(--activity-text);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
  }

  .page-btn:hover {
    background: var(--activity-hover);
    border-color: var(--activity-primary);
    color: var(--activity-primary);
  }

  .page-btn.active {
    background: var(--activity-primary);
    color: white;
    border-color: var(--activity-primary);
  }

  .page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .filter-grid {
      grid-template-columns: 1fr;
    }

    .search-wrapper {
      grid-column: span 1;
    }

    .activity-stats {
      flex-direction: column;
      align-items: start;
      gap: 12px;
    }

    .activity-row {
      flex-direction: column;
      gap: 16px;
    }

    .pagination-wrapper {
      flex-direction: column;
      gap: 16px;
    }
  }

  /* Navigation Override - Ensure visibility */
  .asset-nav-link {
    color: #334155 !important;
  }

  .asset-nav-link.active {
    background: #3b82f6 !important;
    color: #ffffff !important;
  }

  .asset-nav-link:hover {
    color: #3b82f6 !important;
  }
</style>

<div class="activity-page">
  <!-- Header -->
  <div class="activity-page-header">
    <div class="activity-header-content">
      <div class="activity-breadcrumb">
        <a href="../dashboard/index.php">Dashboard</a>
        <span>→</span>
        <a href="index.php">Assets</a>
        <span>→</span>
        <span>Activity Log</span>
      </div>
      <h1 class="activity-page-title">Activity Log</h1>
      <p class="activity-page-subtitle">Complete audit trail of all asset management activities</p>
    </div>
  </div>

  <div class="activity-container">
    <?php include 'assets_header.php'; ?>

    <!-- Filters -->
    <div class="activity-filters">
      <form method="GET" action="activity.php">
        <div class="filter-grid">
          <div class="filter-field">
            <label class="filter-label">Action Type</label>
            <select name="action" class="filter-select">
              <option value="">All Actions</option>
              <?php foreach ($action_types as $action): ?>
                <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $filter_action == $action ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($action); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="filter-field">
            <label class="filter-label">User</label>
            <select name="user" class="filter-select">
              <option value="0">All Users</option>
              <?php foreach ($users_list as $user): ?>
                <option value="<?php echo $user['user_id']; ?>" <?php echo $filter_user == $user['user_id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="filter-field">
            <label class="filter-label">Date From</label>
            <input type="text" name="date_from" id="dateFrom" class="filter-input datepicker" placeholder="dd-mm-yyyy"
              value="<?php echo $filter_date_from ? date('d-m-Y', strtotime($filter_date_from)) : ''; ?>">
          </div>

          <div class="filter-field">
            <label class="filter-label">Date To</label>
            <input type="text" name="date_to" id="dateTo" class="filter-input datepicker" placeholder="dd-mm-yyyy"
              value="<?php echo $filter_date_to ? date('d-m-Y', strtotime($filter_date_to)) : ''; ?>">
          </div>

          <div class="search-wrapper">
            <svg class="search-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" name="search" class="filter-input search-input"
              placeholder="Search activities, asset codes, or descriptions..."
              value="<?php echo htmlspecialchars($search_query); ?>">
          </div>
        </div>

        <div class="filter-actions">
          <button type="submit" class="btn-filter btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            Apply Filters
          </button>
          <a href="activity.php" class="btn-filter btn-secondary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Clear All
          </a>
        </div>
      </form>
    </div>

    <!-- Stats -->
    <div class="activity-stats">
      <h2 class="stats-title">Activity Timeline</h2>
      <div class="stats-count">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <span class="stats-badge"><?php echo number_format($total_items); ?></span>
        <span>Total Activities</span>
      </div>
    </div>

    <!-- Timeline -->
    <?php if (empty($activity_log)): ?>
      <div class="empty-state">
        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="empty-title">No Activities Found</div>
        <div class="empty-subtitle">No activities match your current filters. Try adjusting your search criteria.</div>
      </div>
    <?php else: ?>
      <div class="activity-timeline">
        <?php foreach ($activity_log as $log):
          $style = getActionStyle($log['action_type']);
          $initials = getUserInitials($log['full_name']);
          ?>
          <div class="activity-entry">
            <div class="activity-row">
              <div class="user-avatar"
                style="background: linear-gradient(135deg, <?php echo $style['color']; ?> 0%, <?php echo $style['color']; ?>dd 100%);">
                <?php echo $initials; ?>
              </div>

              <div class="activity-content">
                <div class="activity-header-row">
                  <span class="activity-badge"
                    style="background: <?php echo $style['bg']; ?>; color: <?php echo $style['color']; ?>;">
                    <?php echo htmlspecialchars($log['action_type']); ?>
                  </span>
                  <?php if ($log['asset_code']): ?>
                    <span class="asset-code-badge">
                      <?php echo htmlspecialchars($log['asset_code']); ?>
                    </span>
                  <?php endif; ?>
                </div>

                <div class="activity-description">
                  <?php echo htmlspecialchars($log['description']); ?>
                </div>

                <div class="activity-meta">
                  <div class="meta-item">
                    <svg class="meta-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <div class="user-info">
                      <span class="user-name"><?php echo htmlspecialchars($log['full_name']); ?></span>
                    </div>
                  </div>

                  <div class="meta-item">
                    <svg class="meta-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?php echo $log['time_ago']; ?></span>
                  </div>

                  <div class="meta-item">
                    <svg class="meta-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span><?php echo date('M j, Y \a\t g:i A', strtotime($log['created_at'])); ?></span>
                  </div>
                </div>
              </div>

              <?php if ($log['asset_id']): ?>
                <a href="list.php?view=<?php echo $log['asset_id']; ?>" class="view-asset-btn">
                  <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  View Asset
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
          <div class="pagination-wrapper">
            <div class="pagination-info">
              Page <?php echo $page; ?> of <?php echo $total_pages; ?> &nbsp;•&nbsp;
              <strong><?php echo number_format($total_items); ?></strong> total entries
            </div>
            <div class="pagination-controls">
              <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $filter_action ? '&action=' . urlencode($filter_action) : ''; ?><?php echo $filter_user ? '&user=' . $filter_user : ''; ?><?php echo $filter_date_from ? '&date_from=' . $filter_date_from : ''; ?><?php echo $filter_date_to ? '&date_to=' . $filter_date_to : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                  class="page-btn">
                  Previous
                </a>
              <?php endif; ?>

              <?php
              $start_page = max(1, $page - 2);
              $end_page = min($total_pages, $page + 2);

              for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <a href="?page=<?php echo $i; ?><?php echo $filter_action ? '&action=' . urlencode($filter_action) : ''; ?><?php echo $filter_user ? '&user=' . $filter_user : ''; ?><?php echo $filter_date_from ? '&date_from=' . $filter_date_from : ''; ?><?php echo $filter_date_to ? '&date_to=' . $filter_date_to : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                  class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                  <?php echo $i; ?>
                </a>
              <?php endfor; ?>

              <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $filter_action ? '&action=' . urlencode($filter_action) : ''; ?><?php echo $filter_user ? '&user=' . $filter_user : ''; ?><?php echo $filter_date_from ? '&date_from=' . $filter_date_from : ''; ?><?php echo $filter_date_to ? '&date_to=' . $filter_date_to : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                  class="page-btn">
                  Next
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php else: ?>
          <!-- Single page - still show total count -->
          <div class="pagination-wrapper">
            <div class="pagination-info">
              Showing all <strong><?php echo number_format($total_items); ?></strong> entries
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Flatpickr Date Picker Library -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Configure Flatpickr for date inputs
    const flatpickrConfig = {
      dateFormat: 'd-m-Y',
      altInput: true,
      altFormat: 'd M Y',
      allowInput: true,
      theme: 'airbnb',
      disableMobile: true
    };

    // Initialize Date From picker
    const dateFrom = flatpickr('#dateFrom', {
      ...flatpickrConfig,
      onChange: function (selectedDates, dateStr, instance) {
        // Set minDate for Date To when Date From is selected
        if (selectedDates.length > 0) {
          dateTo.set('minDate', selectedDates[0]);
        }
      }
    });

    // Initialize Date To picker
    const dateTo = flatpickr('#dateTo', {
      ...flatpickrConfig,
      onChange: function (selectedDates, dateStr, instance) {
        // Set maxDate for Date From when Date To is selected
        if (selectedDates.length > 0) {
          dateFrom.set('maxDate', selectedDates[0]);
        }
      }
    });

    // Convert dates to Y-m-d format before form submission for backend
    document.querySelector('.activity-filters form').addEventListener('submit', function (e) {
      const dateFromInput = document.getElementById('dateFrom');
      const dateToInput = document.getElementById('dateTo');

      if (dateFromInput.value) {
        const parts = dateFromInput.value.split('-');
        if (parts.length === 3) {
          dateFromInput.value = parts[2] + '-' + parts[1] + '-' + parts[0];
        }
      }

      if (dateToInput.value) {
        const parts = dateToInput.value.split('-');
        if (parts.length === 3) {
          dateToInput.value = parts[2] + '-' + parts[1] + '-' + parts[0];
        }
      }
    });
  });
</script>

<?php include '../../includes/footer.php'; ?>