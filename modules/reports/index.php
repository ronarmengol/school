<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher', 'accountant']);

$page_title = "System Reports";
$currency_symbol = get_setting('currency_symbol', '$');
include '../../includes/header.php';
?>

<style>
    /* Premium Page Styles */
    .page-header {
        margin-bottom: 40px;
    }

    .page-title {
        font-size: 32px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 8px 0;
        letter-spacing: -0.02em;
    }

    .page-subtitle {
        font-size: 16px;
        color: #64748b;
        margin: 0;
        font-weight: 500;
        line-height: 1.6;
    }

    /* Report Cards Grid */
    .reports-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }

    /* Premium Report Card */
    .report-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .report-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--card-color-start), var(--card-color-end));
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .report-card:hover::before {
        opacity: 1;
    }

    .report-card:hover {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08), 0 6px 12px rgba(0, 0, 0, 0.04);
        transform: translateY(-4px);
        border-color: #cbd5e1;
    }

    .report-card.financial {
        --card-color-start: #10b981;
        --card-color-end: #059669;
    }

    .report-card.academic {
        --card-color-start: #3b82f6;
        --card-color-end: #2563eb;
    }

    .report-card.quick-lists {
        --card-color-start: #8b5cf6;
        --card-color-end: #7c3aed;
    }

    /* Card Icon */
    .card-icon {
        width: 72px;
        height: 72px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
        font-size: 36px;
        transition: transform 0.3s ease;
    }

    .report-card:hover .card-icon {
        transform: scale(1.1) rotate(5deg);
    }

    .card-icon.financial {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    }

    .card-icon.academic {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    }

    .card-icon.quick-lists {
        background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    }

    /* Card Content */
    .card-title {
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 12px 0;
        letter-spacing: -0.01em;
    }

    .card-description {
        font-size: 14px;
        color: #64748b;
        margin: 0 0 24px 0;
        line-height: 1.6;
    }

    /* Primary Action Button */
    .btn-report-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px 24px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-report-primary.financial {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .btn-report-primary.financial:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        transform: translateY(-1px);
        color: white;
    }

    .btn-report-primary.academic {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }

    .btn-report-primary.academic:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        transform: translateY(-1px);
        color: white;
    }

    /* Quick Links List */
    .quick-links-list {
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #f1f5f9;
    }

    .quick-links-title {
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 0 0 16px 0;
    }

    .quick-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #475569;
        text-decoration: none;
        transition: all 0.2s ease;
        margin-bottom: 8px;
        border: 1.5px solid transparent;
    }

    .quick-link:hover {
        background: #f8fafc;
        color: #1e293b;
        border-color: #e2e8f0;
        transform: translateX(4px);
    }

    .quick-link-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        background: #f1f5f9;
        flex-shrink: 0;
    }

    .quick-link:hover .quick-link-icon {
        background: #e2e8f0;
    }

    /* Info Banner */
    .info-banner {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 32px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }

    .info-banner-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #3b82f6;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .info-banner-content {
        flex: 1;
    }

    .info-banner-title {
        font-size: 15px;
        font-weight: 700;
        color: #1e40af;
        margin: 0 0 4px 0;
    }

    .info-banner-text {
        font-size: 14px;
        color: #1e40af;
        margin: 0;
        line-height: 1.5;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-title {
            font-size: 26px;
        }

        .reports-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .report-card {
            padding: 24px;
        }

        .card-icon {
            width: 64px;
            height: 64px;
            font-size: 32px;
        }
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">System Reports</h1>
    <p class="page-subtitle">Access comprehensive reports, analytics, and print-ready lists for all modules</p>
</div>

<!-- Info Banner -->
<div class="info-banner">
    <div class="info-banner-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
    </div>
    <div class="info-banner-content">
        <div class="info-banner-title">Report Generation</div>
        <div class="info-banner-text">All reports are optimized for printing and can be exported to PDF. Data is updated
            in real-time.</div>
    </div>
</div>

<!-- Reports Grid -->
<div class="reports-grid">
    <!-- Financial Reports -->
    <?php if (in_array($_SESSION['role'], ['super_admin', 'admin', 'accountant'])): ?>
        <div class="report-card financial">
            <div class="card-icon financial">
                <span style="font-size: 40px; font-weight: 700; color: #059669;">
                    <?php echo htmlspecialchars($currency_symbol); ?>
                </span>
            </div>
            <h3 class="card-title">Financial Reports</h3>
            <p class="card-description">Comprehensive fee collection analysis, outstanding balance lists, revenue summaries,
                and payment tracking reports.</p>
            <a href="financial_reports.php" class="btn-report-primary financial">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                View Finance Reports
            </a>
        </div>
    <?php endif; ?>

    <!-- Academic Reports -->
    <div class="report-card academic">
        <div class="card-icon academic">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
            </svg>
        </div>
        <h3 class="card-title">Academic Reports</h3>
        <p class="card-description">Detailed class performance analysis, subject averages, student rankings, and
            examination statistics.</p>
        <a href="academic_reports.php" class="btn-report-primary academic">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            View Academic Reports
        </a>
    </div>

    <!-- Quick Lists -->
    <div class="report-card quick-lists">
        <div class="card-icon quick-lists">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <h3 class="card-title">Quick Lists</h3>
        <p class="card-description">Direct access to printable lists and directories from various modules across the
            system.</p>

        <div class="quick-links-list">
            <div class="quick-links-title">Available Lists</div>

            <a href="../students/index.php" class="quick-link">
                <div class="quick-link-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                </div>
                <span>Student Class Lists</span>
            </a>

            <a href="../staff/index.php" class="quick-link">
                <div class="quick-link-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <span>Staff Directory</span>
            </a>

            <a href="../classes/index.php" class="quick-link">
                <div class="quick-link-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                </div>
                <span>Class Lists</span>
            </a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>