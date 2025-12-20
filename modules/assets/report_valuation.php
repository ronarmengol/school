<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$page_title = "Asset Valuation Report";
include '../../includes/header.php';
include 'assets_styles.php';

// Depreciation settings (default: straight-line, 5 years useful life)
$default_useful_life_years = 5;
$depreciation_method = 'straight-line';

// Fetch all assets with valuation data
$assets_query = "
  SELECT 
    a.asset_id,
    a.asset_code,
    a.asset_name,
    a.purchase_date,
    a.purchase_price,
    a.status,
    c.category_name,
    l.location_name
  FROM assets a
  LEFT JOIN asset_categories c ON a.category_id = c.category_id
  LEFT JOIN asset_locations l ON a.location_id = l.location_id
  WHERE a.status != 'Retired' AND a.purchase_price > 0
  ORDER BY a.purchase_price DESC
";

$assets = [];
$total_purchase_value = 0;
$total_current_value = 0;
$total_depreciation = 0;

if ($result = mysqli_query($conn, $assets_query)) {
  while ($row = mysqli_fetch_assoc($result)) {
    $purchase_price = floatval($row['purchase_price']);
    $purchase_date = $row['purchase_date'];

    // Calculate depreciation (straight-line)
    $current_value = $purchase_price;
    $accumulated_depreciation = 0;
    $annual_depreciation = 0;
    $remaining_life = $default_useful_life_years;

    if ($purchase_date) {
      $purchase_datetime = new DateTime($purchase_date);
      $today = new DateTime();
      $age_years = $purchase_datetime->diff($today)->y + ($purchase_datetime->diff($today)->m / 12);

      $annual_depreciation = $purchase_price / $default_useful_life_years;
      $accumulated_depreciation = min($purchase_price, $annual_depreciation * $age_years);
      $current_value = max(0, $purchase_price - $accumulated_depreciation);
      $remaining_life = max(0, $default_useful_life_years - $age_years);
    }

    $row['annual_depreciation'] = $annual_depreciation;
    $row['accumulated_depreciation'] = $accumulated_depreciation;
    $row['current_value'] = $current_value;
    $row['remaining_life'] = $remaining_life;
    $row['depreciation_rate'] = ($purchase_price > 0) ? ($accumulated_depreciation / $purchase_price) * 100 : 0;

    $assets[] = $row;
    $total_purchase_value += $purchase_price;
    $total_current_value += $current_value;
    $total_depreciation += $accumulated_depreciation;
  }
  mysqli_free_result($result);
}

// Group by category for chart
$category_values = [];
foreach ($assets as $asset) {
  $cat = $asset['category_name'] ?? 'Uncategorized';
  if (!isset($category_values[$cat])) {
    $category_values[$cat] = ['purchase' => 0, 'current' => 0];
  }
  $category_values[$cat]['purchase'] += $asset['purchase_price'];
  $category_values[$cat]['current'] += $asset['current_value'];
}
arsort($category_values);
?>

<style>
  .report-container {
    max-width: 1400px;
    margin: 0 auto;
  }

  .report-header {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 32px;
    border-radius: 16px;
    margin-bottom: 24px;
  }

  .report-title {
    font-size: 28px;
    font-weight: 800;
    margin: 0 0 8px 0;
  }

  .report-subtitle {
    font-size: 14px;
    opacity: 0.9;
    margin: 0;
  }

  .summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
  }

  .summary-card {
    background: white;
    padding: 28px;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    border: 1px solid #e2e8f0;
  }

  .summary-label {
    font-size: 13px;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 600;
  }

  .summary-value {
    font-size: 36px;
    font-weight: 800;
    margin: 8px 0;
  }

  .summary-change {
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 4px;
  }

  .valuation-table-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    margin-bottom: 24px;
  }

  .table-header {
    padding: 20px 24px;
    background: #fafafa;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .table-title {
    font-size: 18px;
    font-weight: 700;
    margin: 0;
  }

  .valuation-table {
    width: 100%;
    border-collapse: collapse;
  }

  .valuation-table th {
    text-align: left;
    padding: 14px 16px;
    background: #f8fafc;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    border-bottom: 1px solid #e2e8f0;
  }

  .valuation-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 14px;
  }

  .valuation-table tr:hover {
    background: #f8fafc;
  }

  .depreciation-bar {
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    width: 100px;
  }

  .depreciation-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.5s;
  }

  .category-chart {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .category-row {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .category-name {
    width: 150px;
    font-weight: 600;
    font-size: 14px;
  }

  .category-bars {
    flex: 1;
  }

  .bar-container {
    height: 24px;
    background: #f1f5f9;
    border-radius: 6px;
    overflow: hidden;
    position: relative;
    margin-bottom: 4px;
  }

  .bar-fill {
    height: 100%;
    border-radius: 6px;
  }

  .bar-purchase {
    background: #3b82f6;
  }

  .bar-current {
    background: #10b981;
  }

  .category-values {
    display: flex;
    gap: 20px;
    font-size: 12px;
    color: #64748b;
  }

  .action-buttons {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
  }

  .btn-action {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 2px solid;
  }

  .btn-print {
    background: #10b981;
    color: white;
    border-color: #10b981;
  }

  .btn-print:hover {
    background: #059669;
    border-color: #059669;
  }

  .btn-back {
    background: white;
    color: #64748b;
    border-color: #e2e8f0;
  }

  .btn-back:hover {
    background: #f1f5f9;
  }

  @media print {
    .no-print {
      display: none !important;
    }

    .report-header,
    .summary-card {
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
  }
</style>

<div class="asset-module-wrap">
  <div class="report-container">
    <div class="action-buttons no-print">
      <button class="btn-action btn-print" onclick="window.print()">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
        Print Report
      </button>
      <a href="reports.php" class="btn-action btn-back">← Back to Reports</a>
    </div>

    <div class="report-header">
      <h1 class="report-title">Asset Valuation & Depreciation Report</h1>
      <p class="report-subtitle">Financial summary using <?php echo $default_useful_life_years; ?>-year straight-line
        depreciation • Generated <?php echo date('d M Y'); ?></p>
    </div>

    <div class="summary-grid">
      <div class="summary-card">
        <div class="summary-label">Total Purchase Value</div>
        <div class="summary-value" style="color: #3b82f6;">KES <?php echo number_format($total_purchase_value, 0); ?>
        </div>
        <div class="summary-change" style="color: #64748b;">Original investment value</div>
      </div>
      <div class="summary-card">
        <div class="summary-label">Current Book Value</div>
        <div class="summary-value" style="color: #10b981;">KES <?php echo number_format($total_current_value, 0); ?>
        </div>
        <div class="summary-change" style="color: #10b981;">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
          After accumulated depreciation
        </div>
      </div>
      <div class="summary-card">
        <div class="summary-label">Total Depreciation</div>
        <div class="summary-value" style="color: #ef4444;">KES <?php echo number_format($total_depreciation, 0); ?>
        </div>
        <div class="summary-change" style="color: #ef4444;">
          <?php echo $total_purchase_value > 0 ? number_format(($total_depreciation / $total_purchase_value) * 100, 1) : 0; ?>%
          of original value
        </div>
      </div>
    </div>

    <!-- Category Breakdown -->
    <div class="valuation-table-container">
      <div class="table-header">
        <h3 class="table-title">Value by Category</h3>
        <div style="display: flex; gap: 20px; font-size: 12px;">
          <span><span
              style="display: inline-block; width: 12px; height: 12px; background: #3b82f6; border-radius: 2px; margin-right: 4px;"></span>
            Purchase Value</span>
          <span><span
              style="display: inline-block; width: 12px; height: 12px; background: #10b981; border-radius: 2px; margin-right: 4px;"></span>
            Current Value</span>
        </div>
      </div>
      <div style="padding: 24px;">
        <div class="category-chart">
          <?php
          $max_value = max(array_column($category_values, 'purchase'));
          foreach ($category_values as $cat => $values):
            $purchase_pct = ($max_value > 0) ? ($values['purchase'] / $max_value) * 100 : 0;
            $current_pct = ($max_value > 0) ? ($values['current'] / $max_value) * 100 : 0;
            ?>
            <div class="category-row">
              <div class="category-name"><?php echo htmlspecialchars($cat); ?></div>
              <div class="category-bars">
                <div class="bar-container">
                  <div class="bar-fill bar-purchase" style="width: <?php echo $purchase_pct; ?>%;"></div>
                </div>
                <div class="bar-container">
                  <div class="bar-fill bar-current" style="width: <?php echo $current_pct; ?>%;"></div>
                </div>
              </div>
              <div class="category-values">
                <span style="color: #3b82f6;">KES <?php echo number_format($values['purchase'], 0); ?></span>
                <span style="color: #10b981;">KES <?php echo number_format($values['current'], 0); ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Detailed Asset Table -->
    <div class="valuation-table-container">
      <div class="table-header">
        <h3 class="table-title">Asset Depreciation Details</h3>
        <span style="color: #64748b; font-size: 14px;"><?php echo count($assets); ?> assets</span>
      </div>

      <table class="valuation-table">
        <thead>
          <tr>
            <th>Asset</th>
            <th>Category</th>
            <th>Purchase Date</th>
            <th>Purchase Price</th>
            <th>Annual Depr.</th>
            <th>Accumulated</th>
            <th>Current Value</th>
            <th>Depreciation</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($assets)): ?>
            <tr>
              <td colspan="8" style="text-align: center; padding: 60px; color: #64748b;">
                No assets with valuation data found
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($assets as $asset):
              $depr_color = $asset['depreciation_rate'] > 80 ? '#ef4444' : ($asset['depreciation_rate'] > 50 ? '#f59e0b' : '#10b981');
              ?>
              <tr>
                <td>
                  <div style="font-weight: 600;"><?php echo htmlspecialchars($asset['asset_name']); ?></div>
                  <div style="font-size: 12px; color: #64748b; font-family: monospace;">
                    <?php echo htmlspecialchars($asset['asset_code']); ?>
                  </div>
                </td>
                <td><?php echo htmlspecialchars($asset['category_name'] ?? 'N/A'); ?></td>
                <td><?php echo $asset['purchase_date'] ? date('d/m/Y', strtotime($asset['purchase_date'])) : '-'; ?></td>
                <td style="text-align: right; font-weight: 600;"><?php echo number_format($asset['purchase_price'], 2); ?>
                </td>
                <td style="text-align: right; color: #64748b;">
                  <?php echo number_format($asset['annual_depreciation'], 2); ?>
                </td>
                <td style="text-align: right; color: #ef4444;">
                  <?php echo number_format($asset['accumulated_depreciation'], 2); ?>
                </td>
                <td style="text-align: right; font-weight: 700; color: #10b981;">
                  <?php echo number_format($asset['current_value'], 2); ?>
                </td>
                <td>
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <div class="depreciation-bar">
                      <div class="depreciation-fill"
                        style="width: <?php echo min(100, $asset['depreciation_rate']); ?>%; background: <?php echo $depr_color; ?>;">
                      </div>
                    </div>
                    <span
                      style="font-size: 12px; color: #64748b;"><?php echo number_format($asset['depreciation_rate'], 0); ?>%</span>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <?php if (!empty($assets)): ?>
          <tfoot>
            <tr style="background: #1e293b; color: white;">
              <td colspan="3" style="padding: 16px; font-weight: 700;">TOTALS</td>
              <td style="text-align: right; padding: 16px; font-weight: 700;">
                <?php echo number_format($total_purchase_value, 2); ?>
              </td>
              <td style="padding: 16px;"></td>
              <td style="text-align: right; padding: 16px; font-weight: 700;">
                <?php echo number_format($total_depreciation, 2); ?>
              </td>
              <td style="text-align: right; padding: 16px; font-weight: 700;">
                <?php echo number_format($total_current_value, 2); ?>
              </td>
              <td style="padding: 16px;"></td>
            </tr>
          </tfoot>
        <?php endif; ?>
      </table>
    </div>

    <div style="text-align: center; color: #64748b; font-size: 12px; margin-top: 40px;" class="no-print">
      <p>Depreciation calculated using straight-line method with <?php echo $default_useful_life_years; ?>-year useful
        life.</p>
      <p>Report generated on <?php echo date('d F Y \a\t H:i'); ?></p>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>