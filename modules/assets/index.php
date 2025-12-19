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

// Initialize empty data (to be replaced with database queries)
$total_assets = 0;
$assets_in_use = 0;
$assets_available = 0;
$assets_maintenance = 0;
$currency = get_setting('currency_symbol', '$');
$total_value = $currency . "0.00";

$recent_activity = [];
$maintenance_alerts = [];
$categories = [];
$mock_assets = [];
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
      <div class="kpi-trend trend-up">
        <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd"
            d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z"
            clip-rule="evenodd" />
        </svg>
        <span>+12 this month</span>
      </div>
      <svg class="kpi-icon-bg" width="80" height="80" fill="currentColor" viewBox="0 0 24 24">
        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
      </svg>
    </div>
    <div class="kpi-card">
      <span class="kpi-label">Allocated</span>
      <span class="kpi-value"><?php echo number_format($assets_in_use); ?></span>
      <div class="kpi-trend" style="color: var(--asset-muted);">
        <span>76% Utilization rate</span>
      </div>
      <svg class="kpi-icon-bg" width="80" height="80" fill="currentColor" viewBox="0 0 24 24">
        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
      </svg>
    </div>
    <div class="kpi-card">
      <span class="kpi-label">Maintenance</span>
      <span class="kpi-value"
        style="color: var(--asset-warning);"><?php echo number_format($assets_maintenance); ?></span>
      <div class="kpi-trend trend-down">
        <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd"
            d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z"
            clip-rule="evenodd" />
        </svg>
        <span>+3 items flagged</span>
      </div>
      <svg class="kpi-icon-bg" width="80" height="80" fill="currentColor" viewBox="0 0 24 24">
        <path
          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
    </div>
    <div class="kpi-card">
      <span class="kpi-label">Valuation</span>
      <span class="kpi-value" style="color: var(--asset-primary);"><?php echo $total_value; ?></span>
      <div class="kpi-trend trend-up">
        <span>+2.4% vs last Q</span>
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
        <select class="form-control" style="width: auto; padding: 4px 12px; height: 32px; font-size: 13px;">
          <option>All Locations</option>
          <option>Main Campus</option>
          <option>East Wing</option>
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
          <a href="#" style="font-size: 12px; color: var(--asset-primary); font-weight: 600;">View All</a>
        </div>
        <?php foreach ($maintenance_alerts as $alert): ?>
          <div class="maintenance-item">
            <div class="maint-header">
              <h4 class="maint-item-name"><?php echo $alert['item']; ?></h4>
              <span
                class="prio-badge prio-<?php echo strtolower($alert['priority']); ?>"><?php echo $alert['priority']; ?></span>
            </div>
            <div style="font-size: 13px; margin-bottom: 8px;"><?php echo $alert['task']; ?></div>
            <div class="maint-info">
              <span>Due: <strong><?php echo $alert['due']; ?></strong></span>
              <a href="#" style="color: var(--asset-primary);">Action &rarr;</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Recent Activity -->
      <div class="analytics-card">
        <div class="card-header" style="margin-bottom: 16px;">
          <h3 class="card-title">Recent Activity</h3>
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

  <!-- 4. Asset List Table -->
  <div class="table-container">
    <div class="table-tools">
      <h3 class="card-title">Inventory Overview</h3>
      <div class="search-input-group">
        <svg class="search-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input type="text" class="search-input" placeholder="Search by asset code, name or user...">
      </div>
      <div style="display: flex; gap: 8px;">
        <select class="form-control" style="width: auto; font-size: 13px;">
          <option>All Status</option>
          <option>In Use</option>
          <option>Available</option>
        </select>
        <select class="form-control" style="width: auto; font-size: 13px;">
          <option>Category</option>
        </select>
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
          <th>Condition</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if (empty($mock_assets)): ?>
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
          foreach ($mock_assets as $asset):
            $status_slug = strtolower(str_replace(' ', '-', $asset['status']));
            ?>
            <tr>
              <td style="font-family: monospace; font-weight: 700; color: var(--asset-primary);">
                <?php echo $asset['code']; ?>
              </td>
              <td style="font-weight: 600;"><?php echo $asset['name']; ?></td>
              <td><?php echo $asset['cat']; ?></td>
              <td><?php echo $asset['loc']; ?></td>
              <td>
                <span class="status-badge status-<?php echo $status_slug; ?>">
                  <i class="dot"></i> <?php echo $asset['status']; ?>
                </span>
              </td>
              <td>
                <div style="display: flex; align-items: center; gap: 6px;">
                  <div style="width: 40px; height: 6px; background: #eee; border-radius: 3px; overflow: hidden;">
                    <?php
                    $c_color = '#10b981';
                    $c_w = '100%';
                    if ($asset['cond'] == 'Fair') {
                      $c_color = '#f59e0b';
                      $c_w = '60%';
                    }
                    if ($asset['cond'] == 'Damaged') {
                      $c_color = '#ef4444';
                      $c_w = '30%';
                    }
                    ?>
                    <div style="width: <?php echo $c_w; ?>; height: 100%; background: <?php echo $c_color; ?>;"></div>
                  </div>
                  <span
                    style="font-size: 12px; font-weight: 600; color: var(--asset-muted);"><?php echo $asset['cond']; ?></span>
                </div>
              </td>
              <td style="text-align: right;">
                <div style="display: flex; gap: 4px; justify-content: flex-end;">
                  <button class="btn btn-icon" style="padding: 6px; border: 1px solid #e2e8f0;" title="View Details">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                  <button class="btn btn-icon" style="padding: 6px; border: 1px solid #e2e8f0;" title="Edit Asset">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
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
      style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid var(--asset-border); display: flex; justify-content: space-between; align-items: center;">
      <span style="font-size: 13px; color: var(--asset-muted);">Showing 1-5 of <?php echo $total_assets; ?>
        assets</span>
      <div style="display: flex; gap: 8px;">
        <button class="btn" style="padding: 4px 12px; font-size: 13px; border: 1px solid #e2e8f0;">Previous</button>
        <button class="btn"
          style="padding: 4px 12px; font-size: 13px; background: white; border: 1px solid var(--asset-primary); color: var(--asset-primary);">1</button>
        <button class="btn" style="padding: 4px 12px; font-size: 13px; border: 1px solid #e2e8f0;">2</button>
        <button class="btn" style="padding: 4px 12px; font-size: 13px; border: 1px solid #e2e8f0;">Next</button>
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

  // Interactive Bars Animation
  document.addEventListener('DOMContentLoaded', () => {
    const bars = document.querySelectorAll('.dist-bar');
    bars.forEach(bar => {
      const w = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = w;
      }, 300);
    });
  });
</script>

<?php include '../../includes/footer.php'; ?>