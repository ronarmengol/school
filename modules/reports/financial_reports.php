<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin', 'accountant']);

$page_title = "Financial Reports";
include '../../includes/header.php';

// Currency
$currency = get_setting('currency_symbol', '$');
$school_name = get_setting('school_name', 'School');

// 1. Fee Collection by Class
$sql_class_fees = "SELECT 
    c.class_name, 
    COUNT(s.student_id) as student_count,
    COALESCE(SUM(sf.total_amount), 0) as expected, 
    COALESCE(SUM(sf.paid_amount), 0) as collected
    FROM classes c
    LEFT JOIN students s ON c.class_id = s.current_class_id
    LEFT JOIN student_fees sf ON s.student_id = sf.student_id
    GROUP BY c.class_id
    ORDER BY c.class_name";
$res_class_fees = mysqli_query($conn, $sql_class_fees);

// Calculate totals for summary
$total_exp = 0;
$total_col = 0;
$total_outstanding = 0;
$class_data = [];
while ($row = mysqli_fetch_assoc($res_class_fees)) {
    $balance = $row['expected'] - $row['collected'];
    $total_exp += $row['expected'];
    $total_col += $row['collected'];
    $total_outstanding += $balance;
    $class_data[] = $row;
}
$collection_rate = $total_exp > 0 ? round(($total_col / $total_exp) * 100, 1) : 0;

// 2. Outstanding Balances (Details)
$filter_class = $_GET['class_id'] ?? '';
$where_clause = "HAVING balance > 0";
if ($filter_class) {
    $where_clause .= " AND s.current_class_id = " . intval($filter_class);
}

$sql_debtors = "SELECT 
    s.student_id, 
    s.first_name, 
    s.last_name, 
    s.admission_number,
    c.class_name,
    (COALESCE(SUM(sf.total_amount), 0) - COALESCE(SUM(sf.paid_amount), 0)) as balance,
    p.username as parent_name,
    p.phone
    FROM students s
    JOIN student_fees sf ON s.student_id = sf.student_id
    LEFT JOIN classes c ON s.current_class_id = c.class_id
    LEFT JOIN users p ON s.parent_id = p.user_id
    GROUP BY s.student_id
    $where_clause
    ORDER BY balance DESC";
$res_debtors = mysqli_query($conn, $sql_debtors);
$debtor_count = mysqli_num_rows($res_debtors);
?>

<style>
    /* Financial Report Specific Styles */
    .financial-report-container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .report-header {
        background: white;
        border-radius: 12px;
        padding: 32px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
    }

    .report-title {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 8px 0;
        letter-spacing: -0.5px;
    }

    .report-meta {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #f1f5f9;
    }

    .report-meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #64748b;
        font-size: 14px;
    }

    .report-meta-item svg {
        flex-shrink: 0;
    }

    .report-meta-value {
        font-weight: 600;
        color: #1e293b;
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .metric-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }

    .metric-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    .metric-label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 8px;
    }

    .metric-value {
        font-size: 32px;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        line-height: 1.2;
        margin-bottom: 4px;
    }

    .metric-value.primary {
        color: #2c3e50;
    }

    .metric-value.success {
        color: #10b981;
    }

    .metric-value.danger {
        color: #ef4444;
    }

    .metric-value.info {
        color: #3498db;
    }

    .metric-subtitle {
        font-size: 13px;
        color: #94a3b8;
        font-weight: 500;
    }

    .report-section {
        background: white;
        border-radius: 12px;
        padding: 0;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .section-header {
        padding: 20px 24px;
        border-bottom: 2px solid #f1f5f9;
        background: #fafbfc;
    }

    .section-title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-subtitle {
        font-size: 13px;
        color: #64748b;
        margin: 4px 0 0 0;
        font-weight: 500;
    }

    .financial-table {
        width: 100%;
        border-collapse: collapse;
        font-variant-numeric: tabular-nums;
    }

    .financial-table thead th {
        background: #f8fafc;
        padding: 14px 20px;
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        border-bottom: 2px solid #e2e8f0;
    }

    .financial-table thead th.text-right {
        text-align: right;
    }

    .financial-table thead th.text-center {
        text-align: center;
    }

    .financial-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s ease;
    }

    .financial-table tbody tr:hover {
        background: #f8fafc;
    }

    .financial-table tbody tr:last-child {
        border-bottom: none;
    }

    .financial-table tbody td {
        padding: 16px 20px;
        color: #475569;
        font-size: 14px;
    }

    .financial-table tbody td.text-right {
        text-align: right;
    }

    .financial-table tbody td.text-center {
        text-align: center;
    }

    .financial-table tfoot tr {
        background: #f8fafc;
        border-top: 2px solid #e2e8f0;
    }

    .financial-table tfoot td {
        padding: 18px 20px;
        font-weight: 700;
        color: #1e293b;
        font-size: 15px;
    }

    .financial-table tfoot td.text-right {
        text-align: right;
    }

    .financial-table tfoot td.text-center {
        text-align: center;
    }

    .amount {
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }

    .amount-large {
        font-size: 15px;
        font-weight: 700;
    }

    .amount-positive {
        color: #10b981;
    }

    .amount-negative {
        color: #ef4444;
    }

    .amount-neutral {
        color: #475569;
    }

    .progress-bar-wrapper {
        width: 100%;
        max-width: 120px;
        height: 8px;
        background: #f1f5f9;
        border-radius: 4px;
        overflow: hidden;
        margin: 0 auto;
    }

    .progress-bar-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .progress-bar-fill.high {
        background: #10b981;
    }

    .progress-bar-fill.medium {
        background: #f59e0b;
    }

    .progress-bar-fill.low {
        background: #ef4444;
    }

    .progress-label {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        margin-top: 4px;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }

    .btn-report {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 2px solid;
        background: transparent;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-report.btn-print {
        color: #64748b;
        border-color: #cbd5e1;
    }

    .btn-report.btn-print:hover {
        background: #f1f5f9;
        color: #1e293b;
    }

    .empty-state {
        padding: 60px 24px;
        text-align: center;
        color: #94a3b8;
    }

    .empty-state svg {
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .empty-state-title {
        font-size: 16px;
        font-weight: 600;
        color: #64748b;
        margin: 0 0 8px 0;
    }

    .empty-state-text {
        font-size: 14px;
        color: #94a3b8;
        margin: 0;
    }

    @media print {

        .action-buttons,
        .top-bar,
        .sidebar {
            display: none !important;
        }

        .report-section,
        .report-header,
        .metric-card {
            box-shadow: none !important;
            page-break-inside: avoid;
        }

        body {
            background: white !important;
        }
    }

    @media (max-width: 768px) {
        .metrics-grid {
            grid-template-columns: 1fr;
        }

        .report-header {
            padding: 20px;
        }

        .report-title {
            font-size: 22px;
        }

        .metric-value {
            font-size: 24px;
        }

        .financial-table {
            font-size: 12px;
        }

        .financial-table thead th,
        .financial-table tbody td {
            padding: 10px 12px;
        }
    }
</style>

<div class="financial-report-container">
    <!-- Report Header -->
    <div class="report-header">
        <div
            style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 class="report-title">Financial Report</h1>
                <p style="color: #64748b; margin: 0; font-size: 15px;"><?php echo htmlspecialchars($school_name); ?> •
                    Fee Collection Analysis</p>
            </div>
            <div class="action-buttons">
                <button onclick="window.print()" class="btn-report btn-print">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Print
                </button>
            </div>
        </div>

        <div class="report-meta">
            <div class="report-meta-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>Generated: <span class="report-meta-value"><?php echo date('d M Y, H:i'); ?></span></span>
            </div>
            <div class="report-meta-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Prepared by: <span
                        class="report-meta-value"><?php echo htmlspecialchars($_SESSION['username']); ?></span></span>
            </div>
            <div class="report-meta-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                <span>Report Type: <span class="report-meta-value">Fee Collection Summary</span></span>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-label">Expected Revenue</div>
            <div class="metric-value primary"><?php echo $currency . number_format($total_exp, 2); ?></div>
            <div class="metric-subtitle">Total fees invoiced</div>
        </div>

        <div class="metric-card">
            <div class="metric-label">Collected Revenue</div>
            <div class="metric-value success"><?php echo $currency . number_format($total_col, 2); ?></div>
            <div class="metric-subtitle">Successfully received</div>
        </div>

        <div class="metric-card">
            <div class="metric-label">Outstanding Balance</div>
            <div class="metric-value danger"><?php echo $currency . number_format($total_outstanding, 2); ?></div>
            <div class="metric-subtitle"><?php echo $debtor_count; ?>
                student<?php echo $debtor_count != 1 ? 's' : ''; ?> with arrears</div>
        </div>

        <div class="metric-card">
            <div class="metric-label">Collection Rate</div>
            <div class="metric-value info"><?php echo $collection_rate; ?>%</div>
            <div class="metric-subtitle">Overall performance</div>
        </div>
    </div>

    <!-- Fee Collection by Class -->
    <div class="report-section">
        <div class="section-header">
            <h2 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                Fee Collection by Class
            </h2>
            <p class="section-subtitle">Detailed breakdown of expected vs. collected fees per class</p>
        </div>
        <div style="overflow-x: auto;">
            <table class="financial-table">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th class="text-center">Students</th>
                        <th class="text-right">Expected</th>
                        <th class="text-right">Collected</th>
                        <th class="text-right">Outstanding</th>
                        <th class="text-center">Collection Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($class_data) > 0):
                        foreach ($class_data as $row):
                            $balance = $row['expected'] - $row['collected'];
                            $percent = $row['expected'] > 0 ? round(($row['collected'] / $row['expected']) * 100, 1) : 0;
                            $bar_class = $percent >= 75 ? 'high' : ($percent >= 50 ? 'medium' : 'low');
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['class_name']); ?></strong></td>
                                <td class="text-center"><?php echo number_format($row['student_count']); ?></td>
                                <td class="text-right amount amount-neutral">
                                    <?php echo $currency . number_format($row['expected'], 2); ?>
                                </td>
                                <td class="text-right amount amount-positive">
                                    <?php echo $currency . number_format($row['collected'], 2); ?>
                                </td>
                                <td class="text-right amount amount-negative">
                                    <?php echo $currency . number_format($balance, 2); ?>
                                </td>
                                <td class="text-center">
                                    <div class="progress-bar-wrapper">
                                        <div class="progress-bar-fill <?php echo $bar_class; ?>"
                                            style="width: <?php echo $percent; ?>%;"></div>
                                    </div>
                                    <div class="progress-label"><?php echo $percent; ?>%</div>
                                </td>
                            </tr>
                            <?php
                        endforeach;
                    else:
                        ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 40px; color: #94a3b8;">No class data
                                available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (count($class_data) > 0): ?>
                    <tfoot>
                        <tr>
                            <td colspan="2"><strong>TOTAL</strong></td>
                            <td class="text-right"><strong><?php echo $currency . number_format($total_exp, 2); ?></strong>
                            </td>
                            <td class="text-right"><strong><?php echo $currency . number_format($total_col, 2); ?></strong>
                            </td>
                            <td class="text-right">
                                <strong><?php echo $currency . number_format($total_outstanding, 2); ?></strong>
                            </td>
                            <td class="text-center"><strong><?php echo $collection_rate; ?>%</strong></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Outstanding Balances (Debtors) -->
    <div class="report-section">
        <div class="section-header">
            <h2 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                Outstanding Balances
            </h2>
            <p class="section-subtitle">Students with pending fee payments requiring follow-up</p>
        </div>
        <div style="overflow-x: auto;">
            <?php if ($debtor_count > 0): ?>
                <table class="financial-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Admission No.</th>
                            <th>Class</th>
                            <th>Parent Contact</th>
                            <th class="text-right">Balance Owed</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($debtor = mysqli_fetch_assoc($res_debtors)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($debtor['first_name'] . ' ' . $debtor['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($debtor['admission_number']); ?></td>
                                <td><?php echo htmlspecialchars($debtor['class_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <div style="font-weight: 600; color: #1e293b;">
                                        <?php echo htmlspecialchars($debtor['parent_name'] ?? 'N/A'); ?>
                                    </div>
                                    <?php if (!empty($debtor['phone'])): ?>
                                        <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">
                                            <?php echo htmlspecialchars($debtor['phone']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right amount-large amount-negative">
                                    <?php echo $currency . number_format($debtor['balance'], 2); ?>
                                </td>
                                <td class="text-center">
                                    <a href="../finance/payments.php?search=<?php echo urlencode($debtor['admission_number']); ?>"
                                        class="btn btn-primary"
                                        style="padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                            <polyline points="12 5 19 12 12 19"></polyline>
                                        </svg>
                                        Collect
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <h3 class="empty-state-title">All Fees Collected</h3>
                    <p class="empty-state-text">There are no outstanding balances at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Report Footer Notes -->
    <div
        style="margin-top: 32px; padding: 20px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #3498db;">
        <div style="display: flex; gap: 10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3498db" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <div>
                <div style="font-weight: 700; color: #1e293b; margin-bottom: 4px;">Report Notes</div>
                <div style="font-size: 14px; color: #64748b; line-height: 1.6;">
                    This report provides a comprehensive overview of fee collection across all classes.
                    All amounts are displayed in <?php echo htmlspecialchars($currency); ?>.
                    For detailed transaction history or to record payments, please navigate to the Finance module.
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>