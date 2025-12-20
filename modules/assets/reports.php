<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$page_title = "Asset Reports - Asset Management";
include '../../includes/header.php';
include 'assets_styles.php';
?>

<div class="asset-module-wrap">
  <div class="asset-header">
    <div>
      <div class="breadcrumb">
        <a href="index.php">Asset Management</a>
        <span>&rarr;</span>
        <span>Reports</span>
      </div>
      <h1 class="asset-title">Reports & Exports</h1>
    </div>
  </div>

  <?php include 'assets_header.php'; ?>

  <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 24px;">
    <!-- Asset Register -->
    <div class="asset-card" style="padding: 32px; display: flex; flex-direction: column; gap: 20px;">
      <div
        style="width: 48px; height: 48px; border-radius: 12px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center;">
        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <div>
        <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800;">Complete Asset Register</h3>
        <p style="margin: 0; font-size: 14px; color: var(--asset-muted);">Generate a comprehensive list of all school
          assets with their current status, location, and owner.</p>
      </div>
      <div style="display: flex; gap: 10px; margin-top: auto;">
        <a href="report_asset_register.php?format=pdf" class="asset-btn asset-btn-primary"
          style="flex: 1; justify-content: center; text-decoration: none;">Generate PDF</a>
        <a href="report_asset_register.php?format=excel" class="asset-btn asset-btn-secondary"
          style="flex: 1; justify-content: center; text-decoration: none;">Excel Export</a>
      </div>
    </div>

    <!-- Maintenance Report -->
    <div class="asset-card" style="padding: 32px; display: flex; flex-direction: column; gap: 20px;">
      <div
        style="width: 48px; height: 48px; border-radius: 12px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center;">
        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div>
      <div>
        <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800;">Maintenance History</h3>
        <p style="margin: 0; font-size: 14px; color: var(--asset-muted);">View all past and upcoming maintenance tasks
          across all asset categories.</p>
      </div>
      <div style="display: flex; gap: 10px; margin-top: auto;">
        <a href="report_maintenance.php" class="asset-btn asset-btn-primary"
          style="flex: 1; justify-content: center; text-decoration: none;">Generate Report</a>
      </div>
    </div>

    <!-- Valuation & Depreciation -->
    <div class="asset-card" style="padding: 32px; display: flex; flex-direction: column; gap: 20px;">
      <div
        style="width: 48px; height: 48px; border-radius: 12px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center;">
        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.407 2.67 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.407-2.67-1M12 16V7" />
        </svg>
      </div>
      <div>
        <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800;">Valuation & Depreciation</h3>
        <p style="margin: 0; font-size: 14px; color: var(--asset-muted);">Financial summary of asset values, purchase
          history, and calculated depreciation over time.</p>
      </div>
      <div style="display: flex; gap: 10px; margin-top: auto;">
        <a href="report_valuation.php" class="asset-btn asset-btn-primary"
          style="flex: 1; justify-content: center; text-decoration: none;">Financial Summary</a>
      </div>
    </div>

    <!-- Disposal Register -->
    <div class="asset-card" style="padding: 32px; display: flex; flex-direction: column; gap: 20px;">
      <div
        style="width: 48px; height: 48px; border-radius: 12px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center;">
        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
      </div>
      <div>
        <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 800;">Disposal Register</h3>
        <p style="margin: 0; font-size: 14px; color: var(--asset-muted);">Audit log of all retired, sold, or disposed
          school assets for fixed asset accounting.</p>
      </div>
      <div style="display: flex; gap: 10px; margin-top: auto;">
        <a href="removed_items.php" class="asset-btn asset-btn-primary"
          style="flex: 1; justify-content: center; text-decoration: none;">View Register</a>
      </div>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>