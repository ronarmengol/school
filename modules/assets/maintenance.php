<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$page_title = "Maintenance Management - Asset Management";
include '../../includes/header.php';
include 'assets_styles.php';

// Initialize empty data
$maintenance = [];
?>

<div class="asset-module-wrap">
  <div class="asset-header">
    <div>
      <div class="breadcrumb">
        <a href="index.php">Asset Management</a>
        <span>&rarr;</span>
        <span>Maintenance</span>
      </div>
      <h1 class="asset-title">Maintenance Schedule</h1>
    </div>
    <div class="header-actions">
      <button class="asset-btn asset-btn-primary">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        Schedule Maintenance
      </button>
    </div>
  </div>

  <?php include 'assets_header.php'; ?>

  <div style="display: grid; grid-template-columns: 1fr 300px; gap: 24px;">
    <div class="asset-card">
      <div style="padding: 20px 24px; border-bottom: 1px solid var(--asset-border); background: #fafafa;">
        <h3 class="asset-title" style="font-size: 18px;">Maintenance Logs</h3>
      </div>
      <table class="asset-table">
        <thead>
          <tr>
            <th>Asset</th>
            <th>Task / Service</th>
            <th>Scheduled Date</th>
            <th>Priority</th>
            <th>Status</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($maintenance)): ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 60px 24px; color: var(--asset-muted);">
                <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  style="margin: 0 auto 16px; opacity: 0.3;">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">No Maintenance Records</div>
                <div style="font-size: 14px;">Schedule maintenance tasks to keep your assets in optimal condition.</div>
              </td>
            </tr>
          <?php else:
            foreach ($maintenance as $m): ?>
              <tr>
                <td style="font-weight: 700;"><?php echo $m['asset']; ?></td>
                <td style="font-size: 13px;"><?php echo $m['task']; ?></td>
                <td style="font-weight: 600; color: var(--asset-muted);">
                  <?php echo date('M d, Y', strtotime($m['date'])); ?>
                </td>
                <td>
                  <?php $p_color = ($m['prio'] == 'Critical' || $m['prio'] == 'High') ? 'color: #ef4444; background: #fef2f2;' : 'color: #3b82f6; background: #eff6ff;'; ?>
                  <span
                    style="font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 4px; text-transform: uppercase; <?php echo $p_color; ?>">
                    <?php echo $m['prio']; ?>
                  </span>
                </td>
                <td>
                  <?php
                  $s_style = 'background: #f1f5f9; color: #64748b;';
                  if ($m['status'] == 'Overdue')
                    $s_style = 'background: #fee2e2; color: #b91c1c;';
                  if ($m['status'] == 'Completed')
                    $s_style = 'background: #dcfce7; color: #15803d;';
                  if ($m['status'] == 'Scheduled')
                    $s_style = 'background: #dbeafe; color: #1e40af;';
                  ?>
                  <span
                    style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px; <?php echo $s_style; ?>">
                    <?php echo $m['status']; ?>
                  </span>
                </td>
                <td style="text-align: right;">
                  <button class="asset-btn asset-btn-secondary" style="padding: 6px 10px;">Mark Done</button>
                </td>
              </tr>
            <?php endforeach;
          endif; ?>
        </tbody>
      </table>
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px;">
      <div class="asset-card" style="padding: 24px;">
        <h4 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 700; color: var(--asset-text);">Calendar Preview
        </h4>
        <div
          style="text-align: center; padding: 20px; border: 2px dashed #f1f5f9; border-radius: 12px; color: var(--asset-muted); font-size: 13px;">
          Calendar Widget Placeholder
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>