<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$page_title = "Asset Management Dashboard";
include '../../includes/header.php';

// Fetch categories from database
$categories_list = [];
$cat_query = "SELECT category_id, category_name FROM asset_categories WHERE is_active = 1 ORDER BY category_name ASC";
if ($cat_result = mysqli_query($conn, $cat_query)) {
  while ($row = mysqli_fetch_assoc($cat_result)) {
    $categories_list[] = $row;
  }
  mysqli_free_result($cat_result);
}

// Fetch assets from database
$assets_data = [];
$asset_query = "
  SELECT 
    a.asset_id,
    a.asset_code,
    a.asset_name,
    a.status,
    a.condition,
    ac.category_name,
    al.location_name
  FROM assets a
  LEFT JOIN asset_categories ac ON a.category_id = ac.category_id
  LEFT JOIN asset_locations al ON a.location_id = al.location_id
  WHERE a.status != 'Removed'
  ORDER BY a.asset_code ASC
  LIMIT 10
";
if ($asset_result = mysqli_query($conn, $asset_query)) {
  while ($row = mysqli_fetch_assoc($asset_result)) {
    $assets_data[] = $row;
  }
  mysqli_free_result($asset_result);
}

// Fetch real asset statistics from database
$total_assets = 0;
$assets_in_use = 0;
$assets_available = 0;
$assets_maintenance = 0;
$total_value = 0;

// Get total assets count
$count_query = "SELECT COUNT(*) as total FROM assets WHERE status != 'Removed'";
if ($count_result = mysqli_query($conn, $count_query)) {
  $count_row = mysqli_fetch_assoc($count_result);
  $total_assets = $count_row['total'];
  mysqli_free_result($count_result);
}

// Get assets by status
$status_query = "
  SELECT 
    status,
    COUNT(*) as count
  FROM assets
  WHERE status != 'Removed'
  GROUP BY status
";
if ($status_result = mysqli_query($conn, $status_query)) {
  while ($row = mysqli_fetch_assoc($status_result)) {
    if ($row['status'] == 'In Use') {
      $assets_in_use = $row['count'];
    } elseif ($row['status'] == 'Available') {
      $assets_available = $row['count'];
    } elseif ($row['status'] == 'Maintenance') {
      $assets_maintenance = $row['count'];
    }
  }
  mysqli_free_result($status_result);
}

// Get total asset value
$value_query = "SELECT SUM(purchase_price) as total_value FROM assets WHERE purchase_price IS NOT NULL AND status != 'Removed'";
if ($value_result = mysqli_query($conn, $value_query)) {
  $value_row = mysqli_fetch_assoc($value_result);
  $total_value = $value_row['total_value'] ?? 0;
  mysqli_free_result($value_result);
}

// === DYNAMIC TREND CALCULATIONS ===

// 1. Total Assets Trend (compare with last month)
$total_assets_last_month = 0;
$last_month_query = "
  SELECT COUNT(*) as total 
  FROM assets 
  WHERE status != 'Removed' 
  AND created_at < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
";
if ($last_month_result = mysqli_query($conn, $last_month_query)) {
  $last_month_row = mysqli_fetch_assoc($last_month_result);
  $total_assets_last_month = $last_month_row['total'];
  mysqli_free_result($last_month_result);
}
$assets_change = $total_assets - $total_assets_last_month;
$assets_trend_direction = $assets_change >= 0 ? 'up' : 'down';
$assets_trend_text = ($assets_change >= 0 ? '+' : '') . $assets_change . ' this month';

// 2. Utilization Rate (percentage of assets in use)
$utilization_rate = 0;
if ($total_assets > 0) {
  $utilization_rate = round(($assets_in_use / $total_assets) * 100);
}
$utilization_text = $utilization_rate . '% Utilization rate';

// 3. Maintenance Trend (compare with last month)
$maintenance_last_month = 0;
$maint_last_month_query = "
  SELECT COUNT(*) as total 
  FROM assets 
  WHERE status = 'Maintenance'
  AND updated_at < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
";
if ($maint_last_result = mysqli_query($conn, $maint_last_month_query)) {
  $maint_last_row = mysqli_fetch_assoc($maint_last_result);
  $maintenance_last_month = $maint_last_row['total'];
  mysqli_free_result($maint_last_result);
}
$maintenance_change = $assets_maintenance - $maintenance_last_month;
$maintenance_trend_direction = $maintenance_change >= 0 ? 'down' : 'up'; // More maintenance is bad, so down arrow
$maintenance_trend_text = ($maintenance_change >= 0 ? '+' : '') . abs($maintenance_change) . ' items flagged';

// 4. Valuation Trend (compare with last quarter)
$total_value_last_quarter = 0;
$value_last_quarter_query = "
  SELECT SUM(purchase_price) as total_value 
  FROM assets 
  WHERE purchase_price IS NOT NULL 
  AND status != 'Removed'
  AND created_at < DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
";
if ($value_last_q_result = mysqli_query($conn, $value_last_quarter_query)) {
  $value_last_q_row = mysqli_fetch_assoc($value_last_q_result);
  $total_value_last_quarter = $value_last_q_row['total_value'] ?? 0;
  mysqli_free_result($value_last_q_result);
}
$value_change_percent = 0;
if ($total_value_last_quarter > 0) {
  $value_change_percent = (($total_value - $total_value_last_quarter) / $total_value_last_quarter) * 100;
}
$value_trend_direction = $value_change_percent >= 0 ? 'up' : 'down';
$value_trend_text = ($value_change_percent >= 0 ? '+' : '') . number_format($value_change_percent, 1) . '% vs last Q';

$currency = get_setting('currency_symbol', '$');
$total_value_formatted = $currency . number_format($total_value, 2);

// Fetch recent activity
$recent_activity = [];
$activity_query = "
  SELECT 
    aal.description,
    aal.action_type,
    aal.created_at,
    u.username
  FROM asset_activity_log aal
  LEFT JOIN users u ON aal.performed_by = u.user_id
  ORDER BY aal.created_at DESC
  LIMIT 5
";
if ($activity_result = mysqli_query($conn, $activity_query)) {
  while ($row = mysqli_fetch_assoc($activity_result)) {
    $time_diff = time() - strtotime($row['created_at']);
    if ($time_diff < 60) {
      $time_ago = 'Just now';
    } elseif ($time_diff < 3600) {
      $time_ago = floor($time_diff / 60) . ' min ago';
    } elseif ($time_diff < 86400) {
      $time_ago = floor($time_diff / 3600) . ' hrs ago';
    } else {
      $time_ago = floor($time_diff / 86400) . ' days ago';
    }

    $recent_activity[] = [
      'action' => $row['action_type'],
      'item' => $row['description'],
      'user' => $row['username'] ?? 'System',
      'time' => $time_ago
    ];
  }
  mysqli_free_result($activity_result);
}

// Fetch locations for dropdown filter
$locations_list = [];
$locations_query = "SELECT location_id, location_name FROM asset_locations ORDER BY location_name ASC";
if ($locations_result = mysqli_query($conn, $locations_query)) {
  while ($row = mysqli_fetch_assoc($locations_result)) {
    $locations_list[] = $row;
  }
  mysqli_free_result($locations_result);
}

// Fetch category distribution data with counts
$categories = [];
$category_colors = [
  '#3b82f6', // blue
  '#8b5cf6', // purple
  '#ec4899', // pink
  '#f59e0b', // amber
  '#10b981', // green
  '#06b6d4', // cyan
  '#f97316', // orange
  '#6366f1', // indigo
];

$category_dist_query = "
  SELECT 
    ac.category_name,
    COUNT(a.asset_id) as asset_count
  FROM asset_categories ac
  LEFT JOIN assets a ON ac.category_id = a.category_id AND a.status != 'Removed'
  WHERE ac.is_active = 1
  GROUP BY ac.category_id, ac.category_name
  HAVING asset_count > 0
  ORDER BY asset_count DESC
";

if ($category_dist_result = mysqli_query($conn, $category_dist_query)) {
  $total_for_distribution = 0;
  $temp_categories = [];

  while ($row = mysqli_fetch_assoc($category_dist_result)) {
    $temp_categories[] = $row;
    $total_for_distribution += $row['asset_count'];
  }

  // Add total and color to each category
  $color_index = 0;
  foreach ($temp_categories as $cat) {
    $categories[] = [
      'name' => $cat['category_name'],
      'count' => $cat['asset_count'],
      'total' => $total_for_distribution,
      'color' => $category_colors[$color_index % count($category_colors)]
    ];
    $color_index++;
  }

  mysqli_free_result($category_dist_result);
}

// Fetch maintenance alerts (high priority or overdue)
$maintenance_alerts = [];
$maintenance_query = "
  SELECT 
    am.maintenance_id,
    am.task_description,
    am.scheduled_date,
    am.priority,
    am.status,
    a.asset_name,
    a.asset_code
  FROM asset_maintenance am
  INNER JOIN assets a ON am.asset_id = a.asset_id
  WHERE 
    (am.priority IN ('High', 'Critical') OR am.status = 'Overdue')
    AND am.status NOT IN ('Completed', 'Cancelled')
  ORDER BY 
    FIELD(am.priority, 'Critical', 'High', 'Medium', 'Low'),
    am.scheduled_date ASC
  LIMIT 5
";

if ($maintenance_result = mysqli_query($conn, $maintenance_query)) {
  while ($row = mysqli_fetch_assoc($maintenance_result)) {
    // Calculate due date display
    $scheduled_date = strtotime($row['scheduled_date']);
    $today = strtotime(date('Y-m-d'));
    $days_diff = floor(($scheduled_date - $today) / 86400);

    if ($days_diff < 0) {
      $due_text = abs($days_diff) . ' days overdue';
    } elseif ($days_diff == 0) {
      $due_text = 'Today';
    } elseif ($days_diff == 1) {
      $due_text = 'Tomorrow';
    } else {
      $due_text = date('M j, Y', $scheduled_date);
    }

    $maintenance_alerts[] = [
      'item' => $row['asset_name'] . ' (' . $row['asset_code'] . ')',
      'task' => $row['task_description'],
      'priority' => $row['priority'],
      'due' => $due_text,
      'maintenance_id' => $row['maintenance_id']
    ];
  }
  mysqli_free_result($maintenance_result);
}

?>

<?php
include 'assets_styles.php';
?>

<div class="asset-module-wrap">
  <!-- 1. Header & Actions -->
  <div class="asset-header">
    <div>
      <div class="breadcrumb">
        <a href="../dashboard/index.php">Dashboard</a>
        <span>&rarr;</span>
        <span>Assets</span>
      </div>
      <h1 class="asset-title">Asset Management</h1>
    </div>
    <div class="header-actions">
      <!-- Dashboard specific actions are already in the layout below, but we can add more if needed -->
    </div>
  </div>

  <?php include 'assets_header.php'; ?>

  <!-- 2. KPI Summary -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <span class="kpi-label">Total Assets</span>
      <span class="kpi-value"><?php echo number_format($total_assets); ?></span>
      <div class="kpi-trend trend-<?php echo $assets_trend_direction; ?>">
        <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
          <?php if ($assets_trend_direction == 'up'): ?>
            <path fill-rule="evenodd"
              d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z"
              clip-rule="evenodd" />
          <?php else: ?>
            <path fill-rule="evenodd"
              d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z"
              clip-rule="evenodd" />
          <?php endif; ?>
        </svg>
        <span><?php echo $assets_trend_text; ?></span>
      </div>
      <svg class="kpi-icon-bg" width="80" height="80" fill="currentColor" viewBox="0 0 24 24">
        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
      </svg>
    </div>
    <div class="kpi-card">
      <span class="kpi-label">Allocated</span>
      <span class="kpi-value"><?php echo number_format($assets_in_use); ?></span>
      <div class="kpi-trend" style="color: var(--asset-muted);">
        <span><?php echo $utilization_text; ?></span>
      </div>
      <svg class="kpi-icon-bg" width="80" height="80" fill="currentColor" viewBox="0 0 24 24">
        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
      </svg>
    </div>
    <div class="kpi-card">
      <span class="kpi-label">Maintenance</span>
      <span class="kpi-value"
        style="color: var(--asset-warning);"><?php echo number_format($assets_maintenance); ?></span>
      <div class="kpi-trend trend-<?php echo $maintenance_trend_direction; ?>">
        <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
          <?php if ($maintenance_trend_direction == 'down'): ?>
            <path fill-rule="evenodd"
              d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z"
              clip-rule="evenodd" />
          <?php else: ?>
            <path fill-rule="evenodd"
              d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z"
              clip-rule="evenodd" />
          <?php endif; ?>
        </svg>
        <span><?php echo $maintenance_trend_text; ?></span>
      </div>
      <svg class="kpi-icon-bg" width="80" height="80" fill="currentColor" viewBox="0 0 24 24">
        <path
          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
    </div>
    <div class="kpi-card">
      <span class="kpi-label">Valuation</span>
      <span class="kpi-value" style="color: var(--asset-primary);"><?php echo $total_value_formatted; ?></span>
      <div class="kpi-trend trend-<?php echo $value_trend_direction; ?>">
        <?php if ($value_trend_direction == 'up'): ?>
          <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
              d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z"
              clip-rule="evenodd" />
          </svg>
        <?php elseif ($value_trend_direction == 'down'): ?>
          <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
              d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z"
              clip-rule="evenodd" />
          </svg>
        <?php endif; ?>
        <span><?php echo $value_trend_text; ?></span>
      </div>
      <svg class="kpi-icon-bg" width="80" height="80" fill="currentColor" viewBox="0 0 24 24">
        <path
          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.407 2.67 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.407-2.67-1M12 16V7" />
      </svg>
    </div>
  </div>

  <!-- 3. Distribution & Panels Row -->
  <div class="analytics-row">
    <!-- Asset Distribution -->
    <div class="analytics-card">
      <div class="card-header">
        <h3 class="card-title">Assets by Category</h3>
        <select class="form-control" id="locationFilter"
          style="width: auto; padding: 4px 12px; height: 32px; font-size: 13px;">
          <option value="">All Locations</option>
          <?php foreach ($locations_list as $location): ?>
            <option value="<?php echo $location['location_id']; ?>">
              <?php echo htmlspecialchars($location['location_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="distribution-list">
        <?php
        if (empty($categories)): ?>
          <div style="text-align: center; padding: 40px; color: var(--asset-muted); font-size: 14px;">
            No asset categories found. Add categories to see distribution.
          </div>
        <?php else:
          foreach ($categories as $cat):
            $pct = ($cat['count'] / $cat['total']) * 100;
            ?>
            <div class="dist-item">
              <div class="dist-label-row">
                <span><?php echo $cat['name']; ?></span>
                <span style="color: var(--asset-muted);"><?php echo $cat['count']; ?> items
                  (<?php echo round($pct); ?>%)</span>
              </div>
              <div class="dist-bar-container">
                <div class="dist-bar" style="width: <?php echo $pct; ?>%; background: <?php echo $cat['color']; ?>;"></div>
              </div>
            </div>
          <?php endforeach;
        endif; ?>
      </div>
    </div>

    <!-- Maintenance & Activity Panels -->
    <div class="side-panel-stack">
      <!-- Maintenance Panel -->
      <div class="analytics-card">
        <div class="card-header" style="margin-bottom: 16px;">
          <h3 class="card-title">Maintenance Alerts</h3>
          <a href="maintenance.php" style="font-size: 12px; color: var(--asset-primary); font-weight: 600;">View All</a>
        </div>
        <?php if (empty($maintenance_alerts)): ?>
          <div style="text-align: center; padding: 40px 20px; color: var(--asset-muted); font-size: 14px;">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"
              style="margin: 0 auto 12px; opacity: 0.3;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div style="font-weight: 600; margin-bottom: 4px;">All Clear!</div>
            <div style="font-size: 13px;">No high priority or overdue maintenance tasks.</div>
          </div>
        <?php else: ?>
          <?php foreach ($maintenance_alerts as $alert): ?>
            <div class="maintenance-item">
              <div class="maint-header">
                <h4 class="maint-item-name"><?php echo htmlspecialchars($alert['item']); ?></h4>
                <span
                  class="prio-badge prio-<?php echo strtolower($alert['priority']); ?>"><?php echo $alert['priority']; ?></span>
              </div>
              <div style="font-size: 13px; margin-bottom: 8px;"><?php echo htmlspecialchars($alert['task']); ?></div>
              <div class="maint-info">
                <span>Due: <strong><?php echo $alert['due']; ?></strong></span>
                <a href="maintenance.php?id=<?php echo $alert['maintenance_id']; ?>"
                  style="color: var(--asset-primary);">Action &rarr;</a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Recent Activity -->
      <div class="analytics-card">
        <div class="card-header" style="margin-bottom: 16px;">
          <h3 class="card-title">Recent Activity</h3>
          <a href="activity.php" style="font-size: 12px; color: var(--asset-primary); font-weight: 600;">View All</a>
        </div>
        <div class="activity-feed">
          <?php foreach ($recent_activity as $act): ?>
            <div class="activity-item">
              <div class="activity-content">
                <span class="activity-action"><?php echo $act['action']; ?>:</span>
                <?php echo $act['item']; ?>
                <span style="color: var(--asset-muted);">by <?php echo $act['user']; ?></span>
              </div>
              <div class="activity-meta"><?php echo $act['time']; ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
  function addAsset() {
    showToast('Add Asset feature logic to be implemented.', 'info');
  }

  function exportReport() {
    showToast('Generating asset report...', 'success');
  }

  // Interactive Bars Animation & Dynamic Filtering
  document.addEventListener('DOMContentLoaded', () => {
    // Animate distribution bars
    const bars = document.querySelectorAll('.dist-bar');
    bars.forEach(bar => {
      const w = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = w;
      }, 300);
    });

    // Location Filter for Category Distribution
    const locationFilter = document.getElementById('locationFilter');
    const distributionList = document.querySelector('.distribution-list');

    if (locationFilter && distributionList) {
      locationFilter.addEventListener('change', function () {
        const locationId = this.value;

        // Show loading state
        distributionList.innerHTML = `
          <div style="text-align: center; padding: 40px; color: var(--asset-muted); font-size: 14px;">
            <div style="display: inline-block; width: 32px; height: 32px; border: 3px solid #f3f4f6; border-top-color: var(--asset-primary); border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <div style="margin-top: 12px;">Loading distribution...</div>
          </div>
        `;

        // Fetch category distribution
        const params = new URLSearchParams();
        if (locationId) {
          params.append('location_id', locationId);
        }

        fetch(`api_get_category_distribution.php?${params.toString()}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              renderCategoryDistribution(data.categories);
            } else {
              distributionList.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #dc2626; font-size: 14px;">
                  Failed to load distribution data.
                </div>
              `;
            }
          })
          .catch(error => {
            console.error('Error loading category distribution:', error);
            distributionList.innerHTML = `
              <div style="text-align: center; padding: 40px; color: #dc2626; font-size: 14px;">
                Network error. Please try again.
              </div>
            `;
          });
      });
    }

    // Function to render category distribution
    function renderCategoryDistribution(categories) {
      if (categories.length === 0) {
        distributionList.innerHTML = `
          <div style="text-align: center; padding: 40px; color: var(--asset-muted); font-size: 14px;">
            No asset categories found. Add categories to see distribution.
          </div>
        `;
        return;
      }

      let html = '';
      categories.forEach(cat => {
        const pct = (cat.count / cat.total) * 100;
        html += `
          <div class="dist-item">
            <div class="dist-label-row">
              <span>${escapeHtml(cat.name)}</span>
              <span style="color: var(--asset-muted);">${cat.count} items (${Math.round(pct)}%)</span>
            </div>
            <div class="dist-bar-container">
              <div class="dist-bar" style="width: 0%; background: ${cat.color};"></div>
            </div>
          </div>
        `;
      });

      distributionList.innerHTML = html;

      // Animate the new bars
      setTimeout(() => {
        const newBars = distributionList.querySelectorAll('.dist-bar');
        newBars.forEach((bar, index) => {
          const pct = (categories[index].count / categories[index].total) * 100;
          bar.style.transition = 'width 0.6s ease-out';
          bar.style.width = pct + '%';
        });
      }, 50);
    }


    // Dynamic Asset Filtering
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const assetsTableBody = document.getElementById('assetsTableBody');

    // Function to load assets based on filters
    function loadAssets() {
      const categoryId = categoryFilter.value;
      const status = statusFilter.value;

      // Show loading state
      assetsTableBody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align: center; padding: 40px; color: var(--asset-muted);">
          <div style="display: inline-block; width: 32px; height: 32px; border: 3px solid #f3f4f6; border-top-color: var(--asset-primary); border-radius: 50%; animation: spin 1s linear infinite;"></div>
          <div style="margin-top: 12px; font-size: 14px;">Loading assets...</div>
        </td>
      </tr>
    `;

      // Build query parameters
      const params = new URLSearchParams();
      if (categoryId && categoryId !== '0') {
        params.append('category_id', categoryId);
      }
      if (status) {
        params.append('status', status);
      }

      // Fetch assets from API
      fetch(`api_get_assets.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            renderAssets(data.assets);
          } else {
            showError('Failed to load assets: ' + (data.error || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error('Error loading assets:', error);
          showError('Network error. Please try again.');
        });
    }

    // Function to render assets in the table
    function renderAssets(assets) {
      if (assets.length === 0) {
        assetsTableBody.innerHTML = `
        <tr id="emptyRow">
          <td colspan="7" style="text-align: center; padding: 60px 24px; color: var(--asset-muted);">
            <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24"
              style="margin: 0 auto 16px; opacity: 0.3;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">No Assets Found</div>
            <div style="font-size: 14px;">No assets match the selected filters.</div>
          </td>
        </tr>
      `;
        return;
      }

      let html = '';
      assets.forEach(asset => {
        const statusSlug = asset.status.toLowerCase().replace(' ', '-');

        // Determine condition color and width
        let condColor = '#10b981';
        let condWidth = '100%';
        if (asset.condition === 'Fair') {
          condColor = '#f59e0b';
          condWidth = '60%';
        } else if (asset.condition === 'Poor') {
          condColor = '#ef4444';
          condWidth = '40%';
        } else if (asset.condition === 'Damaged') {
          condColor = '#dc2626';
          condWidth = '30%';
        } else if (asset.condition === 'New') {
          condColor = '#059669';
          condWidth = '100%';
        }

        html += `
        <tr>
          <td style="font-family: monospace; font-weight: 700; color: var(--asset-primary);">
            ${escapeHtml(asset.asset_code)}
          </td>
          <td style="font-weight: 600;">${escapeHtml(asset.asset_name)}</td>
          <td>${escapeHtml(asset.category_name || 'N/A')}</td>
          <td>${escapeHtml(asset.location_name || 'N/A')}</td>
          <td>
            <span class="status-badge status-${statusSlug}">
              <i class="dot"></i> ${escapeHtml(asset.status)}
            </span>
          </td>
          <td>
            <div style="display: flex; align-items: center; gap: 6px;">
              <div style="width: 40px; height: 6px; background: #eee; border-radius: 3px; overflow: hidden;">
                <div style="width: ${condWidth}; height: 100%; background: ${condColor};"></div>
              </div>
              <span style="font-size: 12px; font-weight: 600; color: var(--asset-muted);">${escapeHtml(asset.condition)}</span>
            </div>
          </td>
          <td style="text-align: right;">
            <div style="display: flex; gap: 4px; justify-content: flex-end;">
              <button class="btn btn-icon" style="padding: 6px; border: 1px solid #e2e8f0;" title="View Details">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
              <button class="btn btn-icon" style="padding: 6px; border: 1px solid #e2e8f0;" title="Edit Asset">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
            </div>
          </td>
        </tr>
      `;
      });

      assetsTableBody.innerHTML = html;
    }

    // Function to show error message
    function showError(message) {
      assetsTableBody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align: center; padding: 40px; color: #dc2626;">
          <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin: 0 auto 12px; opacity: 0.5;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div style="font-size: 14px; font-weight: 600;">${message}</div>
        </td>
      </tr>
    `;
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // Add event listeners to filters
    categoryFilter.addEventListener('change', loadAssets);
    statusFilter.addEventListener('change', loadAssets);

    // Add CSS for loading spinner animation
    const style = document.createElement('style');
    style.textContent = `
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  `;
    document.head.appendChild(style);
  }); // End DOMContentLoaded

</script>

<?php include '../../includes/footer.php'; ?>