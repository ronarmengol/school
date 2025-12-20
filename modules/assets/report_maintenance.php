<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$page_title = "Maintenance History";
include '../../includes/header.php';

// Get currency from settings
$currency_symbol = get_setting('currency_symbol', '$');

// Pagination
$items_per_page = 15;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Filters
$filter_asset = isset($_GET['asset']) ? intval($_GET['asset']) : 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_priority = isset($_GET['priority']) ? $_GET['priority'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause
$where_conditions = [];
if ($filter_asset > 0) {
  $where_conditions[] = "m.asset_id = " . $filter_asset;
}
if ($filter_status) {
  $where_conditions[] = "m.status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}
if ($filter_priority) {
  $where_conditions[] = "m.priority = '" . mysqli_real_escape_string($conn, $filter_priority) . "'";
}
if ($filter_date_from) {
  $where_conditions[] = "m.scheduled_date >= '" . mysqli_real_escape_string($conn, $filter_date_from) . "'";
}
if ($filter_date_to) {
  $where_conditions[] = "m.scheduled_date <= '" . mysqli_real_escape_string($conn, $filter_date_to) . "'";
}
if ($search_query) {
  $search_escaped = mysqli_real_escape_string($conn, $search_query);
  $where_conditions[] = "(m.task_description LIKE '%$search_escaped%' OR a.asset_name LIKE '%$search_escaped%' OR a.asset_code LIKE '%$search_escaped%')";
}

$where_clause = '';
if (!empty($where_conditions)) {
  $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM asset_maintenance m LEFT JOIN assets a ON m.asset_id = a.asset_id $where_clause";
$total_items = 0;
if ($count_result = mysqli_query($conn, $count_query)) {
  $count_row = mysqli_fetch_assoc($count_result);
  $total_items = $count_row['total'];
  mysqli_free_result($count_result);
}
$total_pages = ceil($total_items / $items_per_page);

// Fetch maintenance records
$records = [];
$records_query = "
  SELECT 
    m.maintenance_id,
    m.task_description,
    m.priority,
    m.status,
    m.scheduled_date,
    m.completed_date,
    m.cost,
    m.notes,
    m.performed_by,
    m.created_at,
    a.asset_id,
    a.asset_code,
    a.asset_name,
    c.category_name
  FROM asset_maintenance m
  LEFT JOIN assets a ON m.asset_id = a.asset_id
  LEFT JOIN asset_categories c ON a.category_id = c.category_id
  $where_clause
  ORDER BY m.scheduled_date DESC
  LIMIT $items_per_page OFFSET $offset
";

if ($result = mysqli_query($conn, $records_query)) {
  while ($row = mysqli_fetch_assoc($result)) {
    $records[] = $row;
  }
  mysqli_free_result($result);
}

// Stats
$stats_query = "
  SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status IN ('Scheduled', 'In Progress', 'Pending') THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Overdue' OR (status NOT IN ('Completed', 'Cancelled') AND scheduled_date < CURDATE()) THEN 1 ELSE 0 END) as overdue,
    SUM(COALESCE(cost, 0)) as total_cost
  FROM asset_maintenance
";
$stats = ['total' => 0, 'completed' => 0, 'pending' => 0, 'overdue' => 0, 'total_cost' => 0];
if ($stats_result = mysqli_query($conn, $stats_query)) {
  $stats = mysqli_fetch_assoc($stats_result);
  mysqli_free_result($stats_result);
}

// Get assets for filter
$assets_list = [];
$assets_query = "SELECT asset_id, asset_code, asset_name FROM assets ORDER BY asset_name";
if ($assets_result = mysqli_query($conn, $assets_query)) {
  while ($row = mysqli_fetch_assoc($assets_result)) {
    $assets_list[] = $row;
  }
  mysqli_free_result($assets_result);
}

// Helper functions
function getStatusBadge($status, $scheduled_date)
{
  $is_overdue = $status != 'Completed' && $status != 'Cancelled' && strtotime($scheduled_date) < time();

  if ($is_overdue) {
    return ['class' => 'status-overdue', 'label' => 'Overdue', 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'];
  }

  switch ($status) {
    case 'Completed':
      return ['class' => 'status-completed', 'label' => 'Completed', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'];
    case 'In Progress':
      return ['class' => 'status-progress', 'label' => 'In Progress', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'];
    case 'Scheduled':
      return ['class' => 'status-scheduled', 'label' => 'Scheduled', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'];
    case 'Cancelled':
      return ['class' => 'status-cancelled', 'label' => 'Cancelled', 'icon' => 'M6 18L18 6M6 6l12 12'];
    default:
      return ['class' => 'status-pending', 'label' => $status ?: 'Pending', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'];
  }
}

function getPriorityBadge($priority)
{
  switch ($priority) {
    case 'Critical':
      return ['class' => 'priority-critical', 'label' => 'Critical'];
    case 'High':
      return ['class' => 'priority-high', 'label' => 'High'];
    case 'Medium':
      return ['class' => 'priority-medium', 'label' => 'Medium'];
    default:
      return ['class' => 'priority-low', 'label' => $priority ?: 'Low'];
  }
}
?>

<style>
  :root {
    --maint-primary: #f59e0b;
    --maint-primary-dark: #d97706;
    --maint-bg: #f5f6fa;
    --maint-card: #ffffff;
    --maint-border: #e2e8f0;
    --maint-text: #1e293b;
    --maint-muted: #64748b;
  }

  .maintenance-page {
    min-height: 100vh;
    padding-bottom: 60px;
  }

  .maintenance-container {
    max-width: 1400px;
    margin: 0 auto;
  }

  /* Header */
  .maint-header {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    padding: 40px 32px;
    border-radius: 20px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
  }

  .maint-header::before {
    content: '';
    position: absolute;
    top: -40%;
    right: -10%;
    width: 350px;
    height: 350px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
  }

  .header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 1;
  }

  .header-text h1 {
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 8px 0;
    letter-spacing: -0.5px;
  }

  .header-text p {
    font-size: 15px;
    opacity: 0.9;
    margin: 0;
  }

  .header-actions {
    display: flex;
    gap: 12px;
  }

  .btn-header {
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    transition: all 0.2s;
    border: 2px solid;
  }

  .btn-print {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border-color: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
  }

  .btn-print:hover {
    background: rgba(255, 255, 255, 0.3);
  }

  .btn-back {
    background: white;
    color: var(--maint-primary-dark);
    border-color: white;
  }

  .btn-back:hover {
    background: #fef3c7;
  }

  /* Stats */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    margin-bottom: 24px;
  }

  .stat-card {
    background: var(--maint-card);
    padding: 20px;
    border-radius: 14px;
    border: 1px solid var(--maint-border);
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.2s;
  }

  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px -8px rgba(245, 158, 11, 0.2);
  }

  .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .stat-icon.amber {
    background: #fef3c7;
    color: #d97706;
  }

  .stat-icon.green {
    background: #dcfce7;
    color: #16a34a;
  }

  .stat-icon.blue {
    background: #dbeafe;
    color: #2563eb;
  }

  .stat-icon.red {
    background: #fee2e2;
    color: #dc2626;
  }

  .stat-icon.purple {
    background: #ede9fe;
    color: #7c3aed;
  }

  .stat-info {
    flex: 1;
  }

  .stat-value {
    font-size: 26px;
    font-weight: 800;
    color: var(--maint-text);
    line-height: 1;
  }

  .stat-label {
    font-size: 12px;
    color: var(--maint-muted);
    margin-top: 4px;
    text-transform: uppercase;
    font-weight: 600;
  }

  /* Filters */
  .filters-section {
    background: var(--maint-card);
    padding: 24px;
    border-radius: 16px;
    border: 1px solid var(--maint-border);
    margin-bottom: 24px;
  }

  .filters-row {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr 1fr 2fr;
    gap: 14px;
    margin-bottom: 16px;
  }

  .filter-group label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: var(--maint-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
  }

  .filter-input,
  .filter-select {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid var(--maint-border);
    border-radius: 10px;
    font-size: 14px;
    color: var(--maint-text);
    transition: all 0.2s;
    background: white;
  }

  .filter-input:focus,
  .filter-select:focus {
    outline: none;
    border-color: var(--maint-primary);
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
  }

  .search-wrap {
    position: relative;
  }

  .search-wrap svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--maint-muted);
  }

  .search-wrap input {
    padding-left: 42px;
  }

  .filter-actions {
    display: flex;
    gap: 10px;
  }

  .btn-filter {
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 2px solid;
  }

  .btn-apply {
    background: var(--maint-primary);
    color: white;
    border-color: var(--maint-primary);
  }

  .btn-apply:hover {
    background: var(--maint-primary-dark);
    border-color: var(--maint-primary-dark);
  }

  .btn-clear {
    background: white;
    color: var(--maint-muted);
    border-color: var(--maint-border);
  }

  .btn-clear:hover {
    background: #f8fafc;
  }

  /* Records Table */
  .records-section {
    background: var(--maint-card);
    border-radius: 16px;
    border: 1px solid var(--maint-border);
    overflow: hidden;
  }

  .records-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--maint-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fafafa;
  }

  .records-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--maint-text);
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .records-title .icon {
    width: 32px;
    height: 32px;
    background: #fef3c7;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--maint-primary-dark);
  }

  .records-count {
    font-size: 14px;
    color: var(--maint-muted);
  }

  /* Table */
  .maint-table {
    width: 100%;
    border-collapse: collapse;
  }

  .maint-table th {
    text-align: left;
    padding: 14px 16px;
    background: #f8fafc;
    font-size: 11px;
    font-weight: 700;
    color: var(--maint-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--maint-border);
  }

  .maint-table td {
    padding: 18px 16px;
    border-bottom: 1px solid var(--maint-border);
    vertical-align: middle;
  }

  .maint-table tr:hover {
    background: linear-gradient(90deg, #fffbeb 0%, transparent 50%);
  }

  .maint-table tr:last-child td {
    border-bottom: none;
  }

  /* Asset Cell */
  .asset-cell {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .asset-icon {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
  }

  .asset-info .asset-name {
    font-weight: 700;
    color: var(--maint-text);
    font-size: 14px;
    margin-bottom: 2px;
  }

  .asset-info .asset-code {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    color: #3b82f6;
    background: #eff6ff;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 600;
  }

  /* Task */
  .task-text {
    font-size: 14px;
    color: var(--maint-text);
    line-height: 1.5;
    max-width: 280px;
  }

  /* Badges */
  .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }

  .status-badge svg {
    width: 14px;
    height: 14px;
  }

  .status-completed {
    background: #dcfce7;
    color: #15803d;
  }

  .status-progress {
    background: #dbeafe;
    color: #1d4ed8;
  }

  .status-scheduled {
    background: #fef3c7;
    color: #b45309;
  }

  .status-pending {
    background: #f3f4f6;
    color: #6b7280;
  }

  .status-overdue {
    background: #fee2e2;
    color: #dc2626;
  }

  .status-cancelled {
    background: #f1f5f9;
    color: #64748b;
  }

  .priority-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
  }

  .priority-critical {
    background: #fee2e2;
    color: #dc2626;
  }

  .priority-high {
    background: #ffedd5;
    color: #ea580c;
  }

  .priority-medium {
    background: #fef3c7;
    color: #d97706;
  }

  .priority-low {
    background: #dbeafe;
    color: #2563eb;
  }

  /* Date & Cost */
  .date-cell {
    font-size: 13px;
    color: var(--maint-muted);
  }

  .date-cell strong {
    display: block;
    color: var(--maint-text);
    font-weight: 600;
  }

  .cost-cell {
    font-size: 15px;
    font-weight: 700;
    color: var(--maint-text);
  }

  .cost-cell.no-cost {
    color: var(--maint-muted);
    font-weight: 500;
    font-size: 13px;
  }

  .performer-cell {
    font-size: 13px;
    color: var(--maint-text);
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
    color: var(--maint-primary);
  }

  .empty-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--maint-text);
    margin-bottom: 8px;
  }

  .empty-text {
    font-size: 14px;
    color: var(--maint-muted);
  }

  /* Pagination */
  .pagination-footer {
    padding: 20px 24px;
    background: #fafafa;
    border-top: 1px solid var(--maint-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .pagination-info {
    font-size: 14px;
    color: var(--maint-muted);
  }

  .pagination-info strong {
    color: var(--maint-text);
    font-weight: 600;
  }

  .pagination-controls {
    display: flex;
    gap: 8px;
  }

  .page-btn {
    padding: 8px 14px;
    border: 2px solid var(--maint-border);
    border-radius: 8px;
    background: white;
    color: var(--maint-text);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
  }

  .page-btn:hover {
    border-color: var(--maint-primary);
    color: var(--maint-primary);
  }

  .page-btn.active {
    background: var(--maint-primary);
    color: white;
    border-color: var(--maint-primary);
  }

  /* Responsive */
  @media (max-width: 1200px) {
    .stats-grid {
      grid-template-columns: repeat(3, 1fr);
    }

    .filters-row {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  @media (max-width: 768px) {
    .stats-grid {
      grid-template-columns: 1fr 1fr;
    }

    .filters-row {
      grid-template-columns: 1fr;
    }

    .header-row {
      flex-direction: column;
      gap: 16px;
      text-align: center;
    }

    .maint-table {
      display: block;
      overflow-x: auto;
    }

    .pagination-footer {
      flex-direction: column;
      gap: 16px;
    }
  }

  @media print {
    .no-print {
      display: none !important;
    }

    .maint-header {
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
  }
</style>

<div class="maintenance-page">
  <div class="maintenance-container">
    <!-- Header -->
    <div class="maint-header">
      <div class="header-row">
        <div class="header-text">
          <h1>Maintenance History</h1>
          <p>Complete record of all asset maintenance activities, inspections, and service tasks</p>
        </div>
        <div class="header-actions no-print">
          <button class="btn-header btn-print" onclick="window.print()">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print Report
          </button>
          <a href="reports.php" class="btn-header btn-back">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Reports
          </a>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon amber">
          <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
        </div>
        <div class="stat-info">
          <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
          <div class="stat-label">Total Records</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="stat-info">
          <div class="stat-value"><?php echo number_format($stats['completed']); ?></div>
          <div class="stat-label">Completed</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="stat-info">
          <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
          <div class="stat-label">Pending</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red">
          <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="stat-info">
          <div class="stat-value"><?php echo number_format($stats['overdue']); ?></div>
          <div class="stat-label">Overdue</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple">
          <span style="font-size: 20px; font-weight: 700;"><?php echo htmlspecialchars($currency_symbol); ?></span>
        </div>
        <div class="stat-info">
          <div class="stat-value"><?php echo htmlspecialchars($currency_symbol); ?>
            <?php echo number_format($stats['total_cost'], 0); ?>
          </div>
          <div class="stat-label">Total Spent</div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section no-print">
      <form method="GET" action="report_maintenance.php">
        <div class="filters-row">
          <div class="filter-group">
            <label>Asset</label>
            <select name="asset" class="filter-select">
              <option value="0">All Assets</option>
              <?php foreach ($assets_list as $asset): ?>
                <option value="<?php echo $asset['asset_id']; ?>" <?php echo $filter_asset == $asset['asset_id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($asset['asset_code'] . ' - ' . $asset['asset_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-group">
            <label>Status</label>
            <select name="status" class="filter-select">
              <option value="">All Status</option>
              <option value="Scheduled" <?php echo $filter_status == 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
              <option value="In Progress" <?php echo $filter_status == 'In Progress' ? 'selected' : ''; ?>>In Progress
              </option>
              <option value="Completed" <?php echo $filter_status == 'Completed' ? 'selected' : ''; ?>>Completed</option>
              <option value="Cancelled" <?php echo $filter_status == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Priority</label>
            <select name="priority" class="filter-select">
              <option value="">All Priority</option>
              <option value="Critical" <?php echo $filter_priority == 'Critical' ? 'selected' : ''; ?>>Critical</option>
              <option value="High" <?php echo $filter_priority == 'High' ? 'selected' : ''; ?>>High</option>
              <option value="Medium" <?php echo $filter_priority == 'Medium' ? 'selected' : ''; ?>>Medium</option>
              <option value="Low" <?php echo $filter_priority == 'Low' ? 'selected' : ''; ?>>Low</option>
            </select>
          </div>
          <div class="filter-group">
            <label>From Date</label>
            <input type="text" name="date_from" id="dateFrom" class="filter-input datepicker" placeholder="dd/mm/yyyy"
              value="<?php echo $filter_date_from ? date('d/m/Y', strtotime($filter_date_from)) : ''; ?>">
          </div>
          <div class="filter-group">
            <label>To Date</label>
            <input type="text" name="date_to" id="dateTo" class="filter-input datepicker" placeholder="dd/mm/yyyy"
              value="<?php echo $filter_date_to ? date('d/m/Y', strtotime($filter_date_to)) : ''; ?>">
          </div>
          <div class="filter-group search-wrap">
            <label>Search</label>
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" name="search" class="filter-input" placeholder="Search tasks, assets..."
              value="<?php echo htmlspecialchars($search_query); ?>">
          </div>
        </div>
        <div class="filter-actions">
          <button type="submit" class="btn-filter btn-apply">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            Apply Filters
          </button>
          <a href="report_maintenance.php" class="btn-filter btn-clear">Clear</a>
        </div>
      </form>
    </div>

    <!-- Records Table -->
    <div class="records-section">
      <div class="records-header">
        <div class="records-title">
          <span class="icon">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </span>
          Maintenance Records
        </div>
        <div class="records-count">
          <strong><?php echo number_format($total_items); ?></strong> records found
        </div>
      </div>

      <?php if (empty($records)): ?>
        <div class="empty-state">
          <div class="empty-icon">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            </svg>
          </div>
          <div class="empty-title">No Maintenance Records Found</div>
          <div class="empty-text">No maintenance tasks match your current filters. Try adjusting your search criteria.
          </div>
        </div>
      <?php else: ?>
        <table class="maint-table">
          <thead>
            <tr>
              <th>Asset</th>
              <th>Task Description</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Scheduled</th>
              <th>Completed</th>
              <th>Performed By</th>
              <th>Cost</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $record):
              $status_badge = getStatusBadge($record['status'], $record['scheduled_date']);
              $priority_badge = getPriorityBadge($record['priority']);
              ?>
              <tr>
                <td>
                  <div class="asset-cell">
                    <div class="asset-icon">
                      <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                      </svg>
                    </div>
                    <div class="asset-info">
                      <div class="asset-name"><?php echo htmlspecialchars($record['asset_name'] ?? 'Unknown Asset'); ?>
                      </div>
                      <span class="asset-code"><?php echo htmlspecialchars($record['asset_code'] ?? 'N/A'); ?></span>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="task-text"><?php echo htmlspecialchars($record['task_description']); ?></div>
                </td>
                <td>
                  <span
                    class="priority-badge <?php echo $priority_badge['class']; ?>"><?php echo $priority_badge['label']; ?></span>
                </td>
                <td>
                  <span class="status-badge <?php echo $status_badge['class']; ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="<?php echo $status_badge['icon']; ?>" />
                    </svg>
                    <?php echo $status_badge['label']; ?>
                  </span>
                </td>
                <td>
                  <div class="date-cell">
                    <strong><?php echo $record['scheduled_date'] ? date('d/m/Y', strtotime($record['scheduled_date'])) : '—'; ?></strong>
                  </div>
                </td>
                <td>
                  <div class="date-cell">
                    <?php echo $record['completed_date'] ? date('d/m/Y', strtotime($record['completed_date'])) : '—'; ?>
                  </div>
                </td>
                <td>
                  <div class="performer-cell"><?php echo htmlspecialchars($record['performed_by'] ?? '—'); ?></div>
                </td>
                <td>
                  <?php if ($record['cost'] && $record['cost'] > 0): ?>
                    <div class="cost-cell"><?php echo htmlspecialchars($currency_symbol); ?>
                      <?php echo number_format($record['cost'], 0); ?>
                    </div>
                  <?php else: ?>
                    <div class="cost-cell no-cost">—</div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
          <div class="pagination-footer">
            <div class="pagination-info">
              Page <strong><?php echo $page; ?></strong> of <strong><?php echo $total_pages; ?></strong> •
              <strong><?php echo number_format($total_items); ?></strong> total records
            </div>
            <div class="pagination-controls">
              <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $filter_asset ? '&asset=' . $filter_asset : ''; ?><?php echo $filter_status ? '&status=' . urlencode($filter_status) : ''; ?><?php echo $filter_priority ? '&priority=' . urlencode($filter_priority) : ''; ?><?php echo $filter_date_from ? '&date_from=' . $filter_date_from : ''; ?><?php echo $filter_date_to ? '&date_to=' . $filter_date_to : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                  class="page-btn">Previous</a>
              <?php endif; ?>

              <?php
              $start_page = max(1, $page - 2);
              $end_page = min($total_pages, $page + 2);
              for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <a href="?page=<?php echo $i; ?><?php echo $filter_asset ? '&asset=' . $filter_asset : ''; ?><?php echo $filter_status ? '&status=' . urlencode($filter_status) : ''; ?><?php echo $filter_priority ? '&priority=' . urlencode($filter_priority) : ''; ?><?php echo $filter_date_from ? '&date_from=' . $filter_date_from : ''; ?><?php echo $filter_date_to ? '&date_to=' . $filter_date_to : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                  class="page-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
              <?php endfor; ?>

              <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $filter_asset ? '&asset=' . $filter_asset : ''; ?><?php echo $filter_status ? '&status=' . urlencode($filter_status) : ''; ?><?php echo $filter_priority ? '&priority=' . urlencode($filter_priority) : ''; ?><?php echo $filter_date_from ? '&date_from=' . $filter_date_from : ''; ?><?php echo $filter_date_to ? '&date_to=' . $filter_date_to : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                  class="page-btn">Next</a>
              <?php endif; ?>
            </div>
          </div>
        <?php else: ?>
          <div class="pagination-footer">
            <div class="pagination-info">
              Showing all <strong><?php echo number_format($total_items); ?></strong> records
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Flatpickr Date Picker -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const config = {
      dateFormat: 'd/m/Y',
      altInput: true,
      altFormat: 'd M Y',
      allowInput: true,
      theme: 'airbnb'
    };

    const dateFrom = flatpickr('#dateFrom', {
      ...config,
      onChange: function (selectedDates) {
        if (selectedDates.length > 0 && dateTo) {
          dateTo.set('minDate', selectedDates[0]);
        }
      }
    });

    const dateTo = flatpickr('#dateTo', {
      ...config,
      onChange: function (selectedDates) {
        if (selectedDates.length > 0 && dateFrom) {
          dateFrom.set('maxDate', selectedDates[0]);
        }
      }
    });

    // Convert to Y-m-d before form submit
    document.querySelector('.filters-section form').addEventListener('submit', function (e) {
      const fromInput = document.getElementById('dateFrom');
      const toInput = document.getElementById('dateTo');

      if (fromInput.value) {
        const parts = fromInput.value.split('/');
        if (parts.length === 3) {
          fromInput.value = parts[2] + '-' + parts[1] + '-' + parts[0];
        }
      }
      if (toInput.value) {
        const parts = toInput.value.split('/');
        if (parts.length === 3) {
          toInput.value = parts[2] + '-' + parts[1] + '-' + parts[0];
        }
      }
    });
  });
</script>

<?php include '../../includes/footer.php'; ?>