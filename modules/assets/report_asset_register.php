<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$format = isset($_GET['format']) ? $_GET['format'] : 'view';

// Fetch all assets with related data
$assets_query = "
  SELECT 
    a.asset_id,
    a.asset_code,
    a.asset_name,
    a.description,
    a.brand,
    a.purchase_date,
    a.purchase_price,
    a.status,
    a.`condition`,
    c.category_name,
    l.location_name,
    a.assigned_to
  FROM assets a
  LEFT JOIN asset_categories c ON a.category_id = c.category_id
  LEFT JOIN asset_locations l ON a.location_id = l.location_id
  WHERE a.status != 'Retired'
  ORDER BY a.asset_code ASC
";

$assets = [];
if ($result = mysqli_query($conn, $assets_query)) {
  while ($row = mysqli_fetch_assoc($result)) {
    $assets[] = $row;
  }
  mysqli_free_result($result);
}

// Calculate totals
$total_assets = count($assets);
$total_value = array_sum(array_column($assets, 'purchase_price'));

// Handle Excel Export
if ($format === 'excel') {
  header('Content-Type: application/vnd.ms-excel');
  header('Content-Disposition: attachment; filename="asset_register_' . date('Y-m-d') . '.xls"');
  header('Pragma: no-cache');
  header('Expires: 0');

  echo "<table border='1'>";
  echo "<tr><th colspan='9' style='font-size: 18px; font-weight: bold;'>Complete Asset Register - Generated " . date('d/m/Y H:i') . "</th></tr>";
  echo "<tr><th>Asset Code</th><th>Asset Name</th><th>Category</th><th>Location</th><th>Brand</th><th>Status</th><th>Condition</th><th>Purchase Date</th><th>Purchase Price</th></tr>";

  foreach ($assets as $asset) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($asset['asset_code']) . "</td>";
    echo "<td>" . htmlspecialchars($asset['asset_name']) . "</td>";
    echo "<td>" . htmlspecialchars($asset['category_name'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($asset['location_name'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($asset['brand'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($asset['status']) . "</td>";
    echo "<td>" . htmlspecialchars($asset['condition']) . "</td>";
    echo "<td>" . ($asset['purchase_date'] ? date('d/m/Y', strtotime($asset['purchase_date'])) : 'N/A') . "</td>";
    echo "<td>" . number_format($asset['purchase_price'] ?? 0, 2) . "</td>";
    echo "</tr>";
  }

  echo "<tr><td colspan='8' style='text-align: right; font-weight: bold;'>Total Value:</td><td style='font-weight: bold;'>" . number_format($total_value, 2) . "</td></tr>";
  echo "<tr><td colspan='9'>Total Assets: " . $total_assets . "</td></tr>";
  echo "</table>";
  exit();
}

// Handle PDF Export (using HTML for print)
if ($format === 'pdf') {
  $page_title = "Asset Register Report";
  include '../../includes/header.php';
  ?>
  <!DOCTYPE html>
  <html>

  <head>
    <title>Asset Register Report</title>
    <style>
      @media print {
        .no-print {
          display: none !important;
        }

        body {
          font-size: 11px;
        }

        .print-header {
          display: block !important;
        }
      }

      .report-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      .report-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #3b82f6;
      }

      .report-title {
        font-size: 28px;
        font-weight: 800;
        color: #1e293b;
        margin: 0 0 8px 0;
      }

      .report-subtitle {
        font-size: 14px;
        color: #64748b;
      }

      .report-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
        padding: 16px;
        background: #f8fafc;
        border-radius: 8px;
      }

      .meta-item {
        text-align: center;
      }

      .meta-value {
        font-size: 24px;
        font-weight: 700;
        color: #3b82f6;
      }

      .meta-label {
        font-size: 12px;
        color: #64748b;
        text-transform: uppercase;
      }

      .report-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
      }

      .report-table th {
        background: #1e293b;
        color: white;
        padding: 12px 8px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
      }

      .report-table td {
        padding: 10px 8px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 13px;
      }

      .report-table tr:nth-child(even) {
        background: #f8fafc;
      }

      .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
      }

      .status-available {
        background: #dbeafe;
        color: #1e40af;
      }

      .status-in-use {
        background: #dcfce7;
        color: #15803d;
      }

      .status-maintenance {
        background: #fef3c7;
        color: #b45309;
      }

      .status-retired {
        background: #fee2e2;
        color: #b91c1c;
      }

      .action-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
      }

      .btn-print {
        padding: 10px 24px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
      }

      .btn-back {
        padding: 10px 24px;
        background: white;
        color: #64748b;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
      }
    </style>
  </head>

  <body>
    <div class="report-container">
      <div class="action-buttons no-print">
        <button class="btn-print" onclick="window.print()">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"
            style="display: inline; vertical-align: middle; margin-right: 6px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          Print / Save as PDF
        </button>
        <a href="reports.php" class="btn-back">← Back to Reports</a>
      </div>

      <div class="report-header">
        <h1 class="report-title">Complete Asset Register</h1>
        <p class="report-subtitle">Generated on <?php echo date('d F Y \a\t H:i'); ?></p>
      </div>

      <div class="report-meta">
        <div class="meta-item">
          <div class="meta-value"><?php echo number_format($total_assets); ?></div>
          <div class="meta-label">Total Assets</div>
        </div>
        <div class="meta-item">
          <div class="meta-value"><?php echo number_format($total_value, 2); ?></div>
          <div class="meta-label">Total Value (KES)</div>
        </div>
        <div class="meta-item">
          <div class="meta-value"><?php echo count(array_filter($assets, fn($a) => $a['status'] == 'In Use')); ?></div>
          <div class="meta-label">In Use</div>
        </div>
        <div class="meta-item">
          <div class="meta-value"><?php echo count(array_filter($assets, fn($a) => $a['status'] == 'Available')); ?></div>
          <div class="meta-label">Available</div>
        </div>
      </div>

      <table class="report-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Asset Code</th>
            <th>Asset Name</th>
            <th>Category</th>
            <th>Location</th>
            <th>Brand</th>
            <th>Status</th>
            <th>Condition</th>
            <th>Purchase Date</th>
            <th>Value (KES)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($assets as $index => $asset):
            $status_class = 'status-' . strtolower(str_replace(' ', '-', $asset['status']));
            ?>
            <tr>
              <td><?php echo $index + 1; ?></td>
              <td style="font-family: monospace; font-weight: 700;"><?php echo htmlspecialchars($asset['asset_code']); ?>
              </td>
              <td><?php echo htmlspecialchars($asset['asset_name']); ?></td>
              <td><?php echo htmlspecialchars($asset['category_name'] ?? 'N/A'); ?></td>
              <td><?php echo htmlspecialchars($asset['location_name'] ?? 'N/A'); ?></td>
              <td><?php echo htmlspecialchars($asset['brand'] ?? '-'); ?></td>
              <td><span
                  class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($asset['status']); ?></span>
              </td>
              <td><?php echo htmlspecialchars($asset['condition']); ?></td>
              <td><?php echo $asset['purchase_date'] ? date('d/m/Y', strtotime($asset['purchase_date'])) : '-'; ?></td>
              <td style="text-align: right;"><?php echo number_format($asset['purchase_price'] ?? 0, 2); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background: #1e293b; color: white;">
            <td colspan="9" style="text-align: right; font-weight: 700; padding: 12px;">Total Value:</td>
            <td style="text-align: right; font-weight: 700; padding: 12px;"><?php echo number_format($total_value, 2); ?>
            </td>
          </tr>
        </tfoot>
      </table>

      <div style="text-align: center; color: #64748b; font-size: 12px; margin-top: 40px;">
        <p>This report was generated automatically by the Asset Management System.</p>
        <p>© <?php echo date('Y'); ?> School Management System</p>
      </div>
    </div>
  </body>

  </html>
  <?php
  exit();
}

// Default view - redirect to PDF view
header("Location: report_asset_register.php?format=pdf");
exit();
?>