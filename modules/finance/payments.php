<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin', 'accountant']);

$page_title = "Receive Payments";
include '../../includes/header.php';

$student_id = $_GET['student_id'] ?? '';
$search_term = $_GET['search'] ?? '';
$currency_symbol = get_setting('currency_symbol', '$');

// Handle Payment Logic moved to process_payment_ajax.php
?>

<div style="margin-bottom: 30px;">
    <h2 style="margin: 0; color: #1e293b; font-weight: 700;">Student Fee Payments</h2>
    <p style="color: #64748b; margin: 5px 0 0 0;">Record and manage student fee payments across all invoices.</p>
</div>

<!-- Search Bar -->
<div class="card card-premium"
    style="padding: 20px; margin-bottom: 30px; border: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
    <form method="GET" style="display: grid; grid-template-columns: 1fr auto auto; gap: 12px; align-items: center;">
        <div style="position: relative;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"
                style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); pointer-events: none;">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="text" name="search" placeholder="Search by student name or admission number..."
                class="form-control" value="<?php echo htmlspecialchars($search_term); ?>"
                style="border-radius: 10px; border: 1px solid #e2e8f0; height: 48px; padding: 0 15px 0 45px; width: 100%;">
        </div>
        <button type="submit" class="btn btn-primary"
            style="height: 48px; border-radius: 10px; padding: 0 24px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            Search
        </button>
        <?php if ($search_term): ?>
            <a href="payments.php" class="btn btn-outline-secondary"
                style="height: 48px; border-radius: 10px; padding: 0 20px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                Clear
            </a>
        <?php endif; ?>
    </form>
</div>

<?php
// Build query to find students with invoices
if ($search_term) {
    $sql_s = "SELECT DISTINCT s.student_id, s.first_name, s.last_name, s.admission_number, c.class_name, c.section_name
              FROM students s
              LEFT JOIN classes c ON s.current_class_id = c.class_id
              INNER JOIN student_fees sf ON s.student_id = sf.student_id
              WHERE (s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_number LIKE ?)
              AND s.status = 'Active'
              ORDER BY s.first_name, s.last_name";
    $stmt = mysqli_prepare($conn, $sql_s);
    $search_param = "%$search_term%";
    mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
    mysqli_stmt_execute($stmt);
    $res_s = mysqli_stmt_get_result($stmt);
} else {
    // Show all students with invoices
    $sql_s = "SELECT DISTINCT s.student_id, s.first_name, s.last_name, s.admission_number, c.class_name, c.section_name
              FROM students s
              LEFT JOIN classes c ON s.current_class_id = c.class_id
              INNER JOIN student_fees sf ON s.student_id = sf.student_id
              WHERE s.status = 'Active'
              ORDER BY s.first_name, s.last_name";
    $res_s = mysqli_query($conn, $sql_s);
}

if (mysqli_num_rows($res_s) > 0) {
    $student_count = mysqli_num_rows($res_s);
    echo "<div style='display: flex; align-items: center; gap: 8px; margin-bottom: 20px; padding: 12px 20px; background: #f8fafc; border-radius: 10px; border-left: 4px solid #6366f1;'>";
    echo "<svg width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='#6366f1' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2'></path><circle cx='9' cy='7' r='4'></circle><path d='M23 21v-2a4 4 0 0 0-3-3.87'></path><path d='M16 3.13a4 4 0 0 1 0 7.75'></path></svg>";
    echo "<span style='color: #475569; font-weight: 600;'>Showing <strong style='color: #1e293b;'>$student_count</strong> student" . ($student_count != 1 ? 's' : '') . " with invoices</span>";
    echo "</div>";

    while ($st = mysqli_fetch_assoc($res_s)) {
        // Show Invoices for this Student
        ?>
        <div class="card card-premium"
            style="margin-bottom: 18px; border: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); overflow: hidden;">
            <!-- Student Header -->
            <div style="background: #f8fafc; padding: 12px 18px; border-bottom: 2px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #1e293b;">
                            <?php echo htmlspecialchars($st['first_name'] . ' ' . $st['last_name']); ?>
                        </h4>
                        <div style="display: flex; gap: 12px; align-items: center; font-size: 12px; color: #64748b;">
                            <span style="display: flex; align-items: center; gap: 4px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                ADM: <?php echo htmlspecialchars($st['admission_number']); ?>
                            </span>
                            <span style="display: flex; align-items: center; gap: 4px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                </svg>
                                Class:
                                <?php echo htmlspecialchars(($st['class_name'] ?? 'N/A') . ' ' . ($st['section_name'] ?? '')); ?>
                            </span>
                        </div>
                    </div>
                    <div
                        style="width: 38px; height: 38px; background: #6366f1; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; color: white;">
                        <?php echo strtoupper(substr($st['first_name'], 0, 1)); ?>
                    </div>
                </div>
            </div>

            <!-- Invoices Table -->
            <?php
            $sql_inv = "SELECT i.*, t.term_name, y.year_name 
                        FROM student_fees i 
                        JOIN terms t ON i.term_id = t.term_id 
                        JOIN academic_years y ON t.academic_year_id = y.year_id 
                        WHERE i.student_id = {$st['student_id']} 
                        ORDER BY y.year_name DESC, t.term_name";
            $res_inv = mysqli_query($conn, $sql_inv);

            if (mysqli_num_rows($res_inv) > 0) {
                ?>
                <table class="table" style="width: 100%; border-collapse: collapse; margin: 0;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #f1f5f9;">
                            <th
                                style="padding: 10px 18px; text-align: left; color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase;">
                                Academic Term</th>
                            <th
                                style="padding: 10px 18px; text-align: right; color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase;">
                                Total Fee</th>
                            <th
                                style="padding: 10px 18px; text-align: right; color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase;">
                                Paid</th>
                            <th
                                style="padding: 10px 18px; text-align: right; color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase;">
                                Balance</th>
                            <th
                                style="padding: 10px 18px; text-align: center; color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase;">
                                Status</th>
                            <th
                                style="padding: 10px 18px; text-align: right; color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase;">
                                Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($inv = mysqli_fetch_assoc($res_inv)):
                            $balance = $inv['total_amount'] - $inv['paid_amount'];
                            $status_color = $inv['status'] == 'Paid' ? '#10b981' : ($inv['status'] == 'Partial' ? '#f59e0b' : '#ef4444');
                            $status_bg = $inv['status'] == 'Paid' ? '#f0fdf4' : ($inv['status'] == 'Partial' ? '#fef3c7' : '#fef2f2');
                            ?>
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;"
                                onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                                <td style="padding: 12px 18px;">
                                    <div style="font-weight: 600; color: #1e293b; font-size: 13px;">
                                        <?php echo htmlspecialchars($inv['year_name']); ?>
                                    </div>
                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                                        <?php echo htmlspecialchars($inv['term_name']); ?>
                                    </div>
                                </td>
                                <td style="padding: 12px 18px; text-align: right; font-weight: 700; color: #475569; font-size: 14px;">
                                    <?php echo number_format($inv['total_amount'], 2); ?>
                                </td>
                                <td style="padding: 12px 18px; text-align: right; font-weight: 700; color: #10b981; font-size: 14px;">
                                    <?php echo number_format($inv['paid_amount'], 2); ?>
                                </td>
                                <td
                                    style="padding: 12px 18px; text-align: right; font-weight: 700; font-size: 15px; color: <?php echo $balance > 0 ? '#ef4444' : '#10b981'; ?>;">
                                    <?php echo number_format($balance, 2); ?>
                                </td>
                                <td style="padding: 12px 18px; text-align: center;">
                                    <span
                                        style="display: inline-flex; align-items: center; gap: 5px; background: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <?php if ($inv['status'] == 'Paid'): ?>
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($inv['status']); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 18px; text-align: right;">
                                    <?php if ($balance > 0): ?>
                                        <button
                                            onclick="openPaymentModal(<?php echo $inv['invoice_id']; ?>, <?php echo $balance; ?>, '<?php echo addslashes($st['first_name'] . ' ' . $st['last_name']); ?>')"
                                            class="btn btn-success"
                                            style="padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                                            <span
                                                style="font-size: 16px; font-weight: 700;"><?php echo htmlspecialchars($currency_symbol); ?></span>
                                            Record Payment
                                        </button>
                                    <?php else: ?>
                                        <span
                                            style="display: inline-flex; align-items: center; gap: 6px; color: #10b981; font-weight: 700; font-size: 13px;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                            Fully Paid
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php
            } else {
                echo "<div style='padding: 40px; text-align: center; color: #94a3b8;'>";
                echo "<svg width='40' height='40' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round' style='margin-bottom: 10px; opacity: 0.5;'><path d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'></path><polyline points='14 2 14 8 20 8'></polyline></svg>";
                echo "<div style='font-weight: 600;'>No invoices found for this student.</div>";
                echo "</div>";
            }
            ?>
        </div>
        <?php
    }
} else {
    if ($search_term) {
        echo "<div class='card card-premium' style='padding: 60px; text-align: center; border: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);'>";
        echo "<svg width='64' height='64' viewBox='0 0 24 24' fill='none' stroke='#cbd5e1' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round' style='margin-bottom: 20px;'><circle cx='11' cy='11' r='8'></circle><path d='m21 21-4.35-4.35'></path></svg>";
        echo "<h3 style='margin: 0 0 10px 0; color: #1e293b; font-weight: 700;'>No Results Found</h3>";
        echo "<p style='color: #64748b; margin: 0;'>No students found matching '<strong>" . htmlspecialchars($search_term) . "</strong>'</p>";
        echo "</div>";
    } else {
        echo "<div class='card card-premium' style='padding: 60px; text-align: center; border: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);'>";
        echo "<svg width='64' height='64' viewBox='0 0 24 24' fill='none' stroke='#cbd5e1' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round' style='margin-bottom: 20px;'><path d='M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2'></path><circle cx='9' cy='7' r='4'></circle><path d='M23 21v-2a4 4 0 0 0-3-3.87'></path><path d='M16 3.13a4 4 0 0 1 0 7.75'></path></svg>";
        echo "<h3 style='margin: 0 0 10px 0; color: #1e293b; font-weight: 700;'>No Students with Invoices</h3>";
        echo "<p style='color: #64748b; margin: 0;'>There are currently no students with pending or paid invoices.</p>";
        echo "</div>";
    }
}
?>

<!-- Premium Payment Modal -->
<div id="paymentModal" class="modal-overlay">
    <div class="modal-container">
        <!-- Modal Header -->
        <div class="modal-header">
            <div class="modal-title-group">
                <div class="modal-icon">
                    <span class="currency-icon"><?php echo htmlspecialchars($currency_symbol); ?></span>
                </div>
                <div>
                    <h3 class="modal-title">Record Payment</h3>
                    <p class="modal-subtitle">Process fee payment for student</p>
                </div>
            </div>
            <button type="button" class="modal-close" onclick="closePaymentModal()" aria-label="Close modal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="paymentForm" onsubmit="submitPayment(event)" class="modal-body">
            <input type="hidden" name="pay_invoice" value="1">
            <input type="hidden" id="modal_invoice_id" name="invoice_id">

            <!-- Student Info Card -->
            <div class="info-card">
                <label class="field-label">Student Name</label>
                <div id="modal_student_name" class="info-display"></div>
            </div>

            <!-- Balance Display -->
            <div class="balance-card">
                <label class="field-label">Outstanding Balance</label>
                <div id="modal_balance_display" class="balance-amount"></div>
            </div>

            <!-- Amount Input -->
            <div class="form-field">
                <label for="modal_amount" class="field-label">
                    Amount to Pay
                    <span class="field-required">*</span>
                </label>
                <div class="input-wrapper">
                    <span class="input-prefix"><?php echo htmlspecialchars($currency_symbol); ?></span>
                    <input type="number" step="0.01" name="amount" id="modal_amount" class="form-input" required
                        placeholder="0.00">
                </div>
            </div>

            <!-- Payment Method -->
            <div class="form-field">
                <label for="modal_payment_method" class="field-label">
                    Payment Method
                    <span class="field-required">*</span>
                </label>
                <select name="payment_method" id="modal_payment_method" class="form-select" required>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Mobile Money">Mobile Money</option>
                    <option value="Cheque">Cheque</option>
                </select>
            </div>

            <!-- Invoice Reference (Optional) -->
            <div class="form-field">
                <label for="modal_invoice_ref" class="field-label">
                    Invoice/Receipt Reference
                    <span
                        style="color: #94a3b8; font-weight: 500; text-transform: none; font-size: 11px; margin-left: 4px;">(Optional)</span>
                </label>
                <input type="text" name="invoice_ref" id="modal_invoice_ref" class="form-input"
                    placeholder="e.g., RCT-2024-001, TXN-12345" maxlength="50" style="padding-left: 16px;">
                <small style="display: block; margin-top: 6px; font-size: 12px; color: #94a3b8;">
                    Enter receipt number, transaction ID, or check number for tracking
                </small>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closePaymentModal()">
                    Cancel
                </button>
                <button type="submit" id="btnConfirmPay" class="btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Confirm Payment
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Premium Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 20px;
        opacity: 0;
        transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .modal-overlay.active {
        opacity: 1;
    }

    .modal-container {
        background: white;
        width: 100%;
        max-width: 520px;
        border-radius: 16px;
        box-shadow:
            0 20px 25px -5px rgba(0, 0, 0, 0.1),
            0 10px 10px -5px rgba(0, 0, 0, 0.04),
            0 0 0 1px rgba(0, 0, 0, 0.05);
        position: relative;
        transform: scale(0.95) translateY(-20px);
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        max-height: 90vh;
        display: flex;
        flex-direction: column;
    }

    .modal-overlay.active .modal-container {
        transform: scale(1) translateY(0);
    }

    /* Modal Header */
    .modal-header {
        padding: 28px 32px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .modal-title-group {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        flex: 1;
    }

    .modal-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .currency-icon {
        font-size: 24px;
        font-weight: 700;
        color: #059669;
    }

    .modal-title {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.01em;
        line-height: 1.3;
    }

    .modal-subtitle {
        margin: 4px 0 0 0;
        font-size: 14px;
        color: #64748b;
        font-weight: 500;
    }

    .modal-close {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .modal-close:hover {
        background: #f1f5f9;
        color: #475569;
    }

    .modal-close:active {
        transform: scale(0.95);
    }

    /* Modal Body */
    .modal-body {
        padding: 32px;
        overflow-y: auto;
        flex: 1;
    }

    /* Info Cards */
    .info-card {
        background: #f8fafc;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
    }

    .balance-card {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        border: 1px solid #fecaca;
        border-left: 4px solid #ef4444;
    }

    .field-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
    }

    .info-display {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.01em;
    }

    .balance-amount {
        font-size: 28px;
        font-weight: 700;
        color: #dc2626;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.02em;
    }

    /* Form Fields */
    .form-field {
        margin-bottom: 24px;
    }

    .field-required {
        color: #ef4444;
        margin-left: 2px;
    }

    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-prefix {
        position: absolute;
        left: 16px;
        font-size: 16px;
        font-weight: 700;
        color: #64748b;
        pointer-events: none;
    }

    .form-input {
        width: 100%;
        padding: 14px 16px 14px 40px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        color: #0f172a;
        transition: all 0.2s ease;
        background: white;
    }

    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-input::placeholder {
        color: #cbd5e1;
        font-weight: 500;
    }

    .form-select {
        width: 100%;
        padding: 14px 16px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        color: #0f172a;
        transition: all 0.2s ease;
        background: white;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1.5L6 6.5L11 1.5' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        padding-right: 44px;
    }

    .form-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Modal Footer */
    .modal-footer {
        padding: 24px 32px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background: #fafbfc;
        border-radius: 0 0 16px 16px;
    }

    .btn-secondary {
        padding: 12px 24px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        border: 1.5px solid #e2e8f0;
        background: white;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-secondary:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }

    .btn-secondary:active {
        transform: scale(0.98);
    }

    .btn-primary {
        padding: 12px 24px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        border: none;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
        transform: translateY(-1px);
    }

    .btn-primary:active {
        transform: translateY(0) scale(0.98);
    }

    /* Responsive */
    @media (max-width: 640px) {
        .modal-header {
            padding: 20px;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 16px 20px;
            flex-direction: column-reverse;
        }

        .btn-secondary,
        .btn-primary {
            width: 100%;
            justify-content: center;
        }

        .modal-title {
            font-size: 18px;
        }

        .modal-icon {
            width: 40px;
            height: 40px;
        }

        .currency-icon {
            font-size: 20px;
        }
    }
</style>

<script>
    // Open payment modal with smooth animation
    function openPaymentModal(invoiceId, balance, studentName) {
        const modal = document.getElementById('paymentModal');
        modal.style.display = 'flex';

        // Trigger animation
        requestAnimationFrame(() => {
            modal.classList.add('active');
        });

        // Set form values
        document.getElementById('modal_invoice_id').value = invoiceId;
        document.getElementById('modal_student_name').innerText = studentName;
        document.getElementById('modal_balance_display').innerText = '<?php echo $currency_symbol; ?>' + parseFloat(balance).toFixed(2);

        // Set max amount to balance
        const amountInput = document.getElementById('modal_amount');
        amountInput.max = balance;
        amountInput.value = balance; // Default to full payment

        // Focus amount input after animation
        setTimeout(() => {
            amountInput.focus();
            amountInput.select();
        }, 300);
    }

    // Close payment modal with smooth animation
    function closePaymentModal() {
        const modal = document.getElementById('paymentModal');
        modal.classList.remove('active');

        // Hide modal after animation completes
        setTimeout(() => {
            modal.style.display = 'none';
            // Reset form
            document.getElementById('paymentForm').reset();
        }, 250);
    }

    // Submit payment
    function submitPayment(e) {
        e.preventDefault();

        const form = document.getElementById('paymentForm');
        const formData = new FormData(form);
        const btn = document.getElementById('btnConfirmPay');
        const originalHTML = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg> Processing...';

        fetch('process_payment_ajax.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closePaymentModal();
                    showToastSuccess('Payment recorded successfully!');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToastError(data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showError('An unexpected error occurred.');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            });
    }

    // Close modal on ESC key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('paymentModal');
            if (modal && modal.classList.contains('active')) {
                closePaymentModal();
            }
        }
    });

    // Close modal on backdrop click
    document.getElementById('paymentModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closePaymentModal();
        }
    });

    // Trap focus within modal when open
    document.getElementById('paymentModal').addEventListener('keydown', function (e) {
        if (!this.classList.contains('active')) return;

        if (e.key === 'Tab') {
            const focusableElements = this.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    });
</script>

<style>
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }
</style>

<?php
include '../../includes/modals.php';
include '../../includes/footer.php';
?>