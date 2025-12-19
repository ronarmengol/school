<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin', 'accountant']);

$page_title = "Finance Dashboard";
include '../../includes/header.php';
?>

<div class="row" style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
    <!-- Configuration -->
    <div class="card card-premium"
        style="flex: 1; min-width: 250px; text-align: center; padding: 30px; display: flex; flex-direction: column; align-items: center; gap: 15px;">
        <div
            style="width: 60px; height: 60px; background: #e0e7ff; color: #4f46e5; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path
                    d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83">
                </path>
            </svg>
        </div>
        <h3 style="margin: 0;">Fee Structure</h3>
        <p style="color: #64748b; font-size: 14px; margin: 0;">Set up and manage class-based fees and structures.</p>
        <a href="fees_structure.php" class="btn btn-primary" style="width: 100%; margin-top: auto;">Manage Fees</a>
    </div>

    <!-- Payments -->
    <div class="card card-premium"
        style="flex: 1; min-width: 250px; text-align: center; padding: 30px; display: flex; flex-direction: column; align-items: center; gap: 15px;">
        <div
            style="width: 60px; height: 60px; background: #dcfce7; color: #16a34a; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                <line x1="2" y1="10" x2="22" y2="10"></line>
            </svg>
        </div>
        <h3 style="margin: 0;">Record Payment</h3>
        <p style="color: #64748b; font-size: 14px; margin: 0;">Receive and record fee payments from students/parents.
        </p>
        <a href="payments.php" class="btn btn-success" style="width: 100%; margin-top: auto;">Receive Payment</a>
    </div>

    <!-- Billing Check -->
    <div class="card card-premium"
        style="flex: 1; min-width: 250px; text-align: center; padding: 30px; display: flex; flex-direction: column; align-items: center; gap: 15px;">
        <div
            style="width: 60px; height: 60px; background: #ecfeff; color: #0891b2; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        </div>
        <h3 style="margin: 0;">Billing Check</h3>
        <p style="color: #64748b; font-size: 14px; margin: 0;">Verify which students have been invoiced for the term.
        </p>
        <a href="billing_status.php" class="btn btn-info" style="width: 100%; margin-top: auto;">Check
            Status</a>
    </div>

    <!-- Reports -->
    <div class="card card-premium"
        style="flex: 1; min-width: 250px; text-align: center; padding: 30px; display: flex; flex-direction: column; align-items: center; gap: 15px;">
        <div
            style="width: 60px; height: 60px; background: #fff7ed; color: #ea580c; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
            </svg>
        </div>
        <h3 style="margin: 0;">Finance Reports</h3>
        <p style="color: #64748b; font-size: 14px; margin: 0;">View collections, debtors, and financial summaries.</p>
        <div style="display: flex; gap: 10px; width: 100%; margin-top: auto;">
            <a href="../reports/financial_reports.php" class="btn btn-warning"
                style="flex: 1; font-size: 13px; font-weight: 600;">View Reports</a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>