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

// Get currency symbol from settings
$currency_symbol = get_setting('currency_symbol', '$');
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
        <span style="font-size: 22px; font-weight: 700;"><?php echo htmlspecialchars($currency_symbol); ?></span>
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
  </div>
</div>

<?php include '../../includes/footer.php'; ?>