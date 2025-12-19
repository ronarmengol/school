<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

$page_title = "System Settings";
include '../../includes/header.php';

$active_tab = $_GET['tab'] ?? 'general';
?>

<style>
    /* Premium Settings Styling */
    :root {
        --s-primary: #3b82f6;
        --s-primary-dark: #2563eb;
        --s-secondary: #6366f1;
        --s-success: #10b981;
        --s-danger: #ef4444;
        --s-warning: #f59e0b;
        --s-text-main: #0f172a;
        --s-text-muted: #64748b;
        --s-bg-light: #f8fafc;
        --s-border: #e2e8f0;
        --s-radius: 12px;
        --s-radius-lg: 16px;
        --s-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
        --s-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --s-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .settings-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Page Header & Hero */
    .settings-header {
        margin-bottom: 32px;
        padding: 0 4px;
    }

    .settings-title {
        font-size: 32px;
        font-weight: 800;
        color: var(--s-text-main);
        margin: 0 0 8px 0;
        letter-spacing: -0.025em;
    }

    .settings-subtitle {
        font-size: 16px;
        color: var(--s-text-muted);
        margin: 0;
        line-height: 1.5;
    }

    /* Premium Tabs */
    .settings-tabs {
        display: flex;
        gap: 8px;
        background: #f1f5f9;
        padding: 6px;
        border-radius: 14px;
        width: fit-content;
        margin-bottom: 8px;
    }

    .tab-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        color: var(--s-text-muted);
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid transparent;
    }

    .tab-item:hover {
        color: var(--s-text-main);
        background: rgba(255, 255, 255, 0.5);
    }

    .tab-item.active {
        background: white;
        color: var(--s-primary);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border-color: rgba(0, 0, 0, 0.02);
    }

    .tab-item svg {
        opacity: 0.7;
    }

    .tab-item.active svg {
        opacity: 1;
        color: var(--s-primary);
    }

    /* Content Area */
    .settings-content {
        animation: fadeIn 0.3s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Admin Hero Section */
    .admin-hero {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        border-radius: var(--s-radius-lg);
        padding: 40px;
        margin-bottom: 32px;
        color: white;
        position: relative;
        overflow: hidden;
        box-shadow: var(--s-shadow-lg);
    }

    .admin-hero::before {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, transparent 70%);
        border-radius: 50%;
    }

    .admin-hero-content {
        position: relative;
        z-index: 1;
    }

    .admin-hero h2 {
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 12px 0;
        letter-spacing: -0.01em;
    }

    .admin-hero p {
        font-size: 16px;
        opacity: 0.8;
        max-width: 600px;
        margin: 0;
        line-height: 1.6;
    }

    /* Premium Table Styles */
    .table-container {
        background: white;
        border-radius: var(--s-radius-lg);
        border: 1px solid var(--s-border);
        box-shadow: var(--s-shadow);
        overflow: hidden;
    }

    .table-header-row {
        padding: 24px 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--s-border);
        background: #fafafa;
    }

    .table-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--s-text-main);
        margin: 0;
    }

    .premium-table {
        width: 100%;
        border-collapse: collapse;
    }

    .premium-table th {
        text-align: left;
        padding: 16px 32px;
        font-size: 12px;
        font-weight: 700;
        color: var(--s-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: #f8fafc;
        border-bottom: 1px solid var(--s-border);
    }

    .premium-table td {
        padding: 18px 32px;
        font-size: 14px;
        color: var(--s-text-main);
        border-bottom: 1px solid var(--s-border);
        vertical-align: middle;
    }

    .premium-table tr:last-child td {
        border-bottom: none;
    }

    .premium-table tr:hover {
        background: #fcfcfc;
    }

    /* Badge Styles */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: -0.01em;
    }

    .badge-super-admin {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .badge-admin {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #bfdbfe;
    }

    .badge-active {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .badge-inactive {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    /* Action Buttons */
    .action-btn-group {
        display: flex;
        gap: 8px;
    }

    .btn-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid var(--s-border);
        background: white;
        color: var(--s-text-muted);
        transition: all 0.2s;
        cursor: pointer;
    }

    .btn-icon:hover {
        border-color: var(--s-primary);
        color: var(--s-primary);
        background: #eff6ff;
    }

    .btn-icon.btn-danger-icon:hover {
        border-color: var(--s-danger);
        color: var(--s-danger);
        background: #fef2f2;
    }

    /* Form Premium */
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: var(--s-text-main);
        margin-bottom: 8px;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        border-radius: 10px !important;
        border: 1px solid var(--s-border) !important;
        font-size: 14px !important;
        transition: all 0.2s !important;
    }

    .form-control:focus {
        border-color: var(--s-primary) !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        outline: none;
    }

    .btn-add {
        background: var(--s-primary);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 6px rgba(59, 130, 246, 0.25);
    }

    .btn-add:hover {
        background: var(--s-primary-dark);
        transform: translateY(-1px);
        box-shadow: 0 6px 12px rgba(59, 130, 246, 0.3);
    }
</style>

<!-- Premium Page Header -->
<div class="settings-header">
    <div class="settings-title-group">
        <h1 class="settings-title">System Settings</h1>
        <p class="settings-subtitle">Manage your school workspace, feature toggles, and administrator accounts</p>
    </div>
</div>

<div class="settings-container">
    <div class="settings-tabs">
        <a href="?tab=general" class="tab-item <?php echo $active_tab == 'general' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            General
        </a>
        <a href="?tab=school" class="tab-item <?php echo $active_tab == 'school' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            School Profile
        </a>
        <a href="?tab=features" class="tab-item <?php echo $active_tab == 'features' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Features
        </a>
        <?php if ($_SESSION['role'] == 'super_admin'): ?>
            <a href="?tab=admin" class="tab-item <?php echo $active_tab == 'admin' ? 'active' : ''; ?>">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Admin Management
            </a>
        <?php endif; ?>
    </div>

    <div class="settings-content">
        <?php if ($active_tab != 'admin'): ?>
            <form action="save.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
            <?php endif; ?>

            <?php if ($active_tab == 'general'): ?>
                <div class="table-container" style="padding: 32px;">
                    <h3 style="margin-top: 0; margin-bottom: 24px;">General Settings</h3>
                    <div class="form-group">
                        <label>Currency Symbol</label>
                        <input type="text" name="settings[currency_symbol]" class="form-control"
                            value="<?php echo htmlspecialchars(get_setting('currency_symbol', '$')); ?>">
                    </div>

                    <hr style="margin: 40px 0; border: none; border-top: 1px solid var(--s-border);">

                    <!-- Database Reset Section (Retained but styled) -->
                    <div
                        style="background: #fffbeb; border: 1px solid #fef3c7; padding: 24px; border-radius: 12px; margin-bottom: 24px;">
                        <h3
                            style="color: #92400e; margin: 0 0 12px 0; display: flex; align-items: center; font-size: 18px;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor"
                                style="width: 24px; height: 24px; margin-right: 12px; color: var(--s-warning);">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            Database Reset Tools
                        </h3>
                        <p style="color: #78350f; margin: 0; font-size: 14px; opacity: 0.9;">
                            <strong>Warning:</strong> These tools are for destructive operations. Data will be permanently
                            removed.
                        </p>
                    </div>

                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 32px;">
                        <!-- Reset Options Styled as Cards -->
                        <div
                            style="border: 1px solid var(--s-border); border-radius: 12px; padding: 20px; background: #fafafa;">
                            <label style="display: flex; gap: 12px; cursor: pointer; margin: 0;">
                                <input type="checkbox" id="reset_students" style="width: 18px; height: 18px;">
                                <div>
                                    <div style="font-weight: 700; color: var(--s-text-main);">Students Data</div>
                                    <div style="font-size: 12px; color: var(--s-text-muted); margin-top: 4px;">Removes
                                        students, attendance, and academic history</div>
                                </div>
                            </label>
                        </div>
                        <!-- ... other reset checkboxes ... -->
                        <div
                            style="border: 1px solid var(--s-border); border-radius: 12px; padding: 20px; background: #fafafa;">
                            <label style="display: flex; gap: 12px; cursor: pointer; margin: 0;">
                                <input type="checkbox" id="reset_classes" style="width: 18px; height: 18px;">
                                <div>
                                    <div style="font-weight: 700; color: var(--s-text-main);">Academic Structure</div>
                                    <div style="font-size: 12px; color: var(--s-text-muted); margin-top: 4px;">Removes all
                                        classes, subjects, and schedules</div>
                                </div>
                            </label>
                        </div>
                        <div
                            style="border: 1px solid var(--s-border); border-radius: 12px; padding: 20px; background: #fafafa;">
                            <label style="display: flex; gap: 12px; cursor: pointer; margin: 0;">
                                <input type="checkbox" id="reset_exams" style="width: 18px; height: 18px;">
                                <div>
                                    <div style="font-weight: 700; color: var(--s-text-main);">Exams & Marks</div>
                                    <div style="font-size: 12px; color: var(--s-text-muted); margin-top: 4px;">Removes all
                                        examination records and results</div>
                                </div>
                            </label>
                        </div>
                        <div
                            style="border: 1px solid var(--s-border); border-radius: 12px; padding: 20px; background: #fafafa;">
                            <label style="display: flex; gap: 12px; cursor: pointer; margin: 0;">
                                <input type="checkbox" id="reset_finance" style="width: 18px; height: 18px;">
                                <div>
                                    <div style="font-weight: 700; color: var(--s-text-main);">Financial Records</div>
                                    <div style="font-size: 12px; color: var(--s-text-muted); margin-top: 4px;">Removes
                                        invoices, fees, and payments</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Reset Pass Confirmation -->
                    <div
                        style="background: #f8fafc; border: 1px solid var(--s-border); border-radius: 12px; padding: 24px; margin-bottom: 32px;">
                        <div class="form-group" style="margin: 0;">
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Password Verification
                            </label>
                            <input type="password" id="reset_password" class="form-control" style="max-width: 400px;"
                                placeholder="Confirm your password to reset selected sections">
                        </div>
                    </div>

                    <div style="display: flex; gap: 16px;">
                        <button type="submit" class="btn-add">Save General Settings</button>
                        <button type="button" onclick="executeReset()" class="tab-item"
                            style="color: var(--s-danger); border: 1px solid var(--s-danger); background: transparent;">Execute
                            Partial Reset</button>
                    </div>
                </div>

                <script>
                    // Reset All checkbox handler - only if element exists
                    const resetAllCheckbox = document.getElementById('reset_all');
                    if (resetAllCheckbox) {
                        resetAllCheckbox.addEventListener('change', function () {
                            const checkboxes = ['reset_students', 'reset_classes', 'reset_exams', 'reset_finance', 'reset_academic_years', 'reset_staff'];
                            checkboxes.forEach(id => {
                                const elem = document.getElementById(id);
                                if (elem) elem.checked = this.checked;
                            });
                        });

                        // Individual checkboxes handler
                        ['reset_students', 'reset_classes', 'reset_exams', 'reset_finance', 'reset_academic_years', 'reset_staff'].forEach(id => {
                            const elem = document.getElementById(id);
                            if (elem) {
                                elem.addEventListener('change', function () {
                                    // Uncheck "reset all" if any individual is unchecked
                                    const allChecked = ['reset_students', 'reset_classes', 'reset_exams', 'reset_finance', 'reset_academic_years', 'reset_staff']
                                        .every(checkId => {
                                            const el = document.getElementById(checkId);
                                            return el ? el.checked : false;
                                        });
                                    resetAllCheckbox.checked = allChecked;
                                });
                            }
                        });
                    }

                    // Make executeReset globally accessible
                    window.executeReset = function () {
                        console.log('executeReset called');
                        const sections = [];

                        // Safely check each checkbox
                        const checkboxes = {
                            'reset_students': 'students',
                            'reset_classes': 'classes',
                            'reset_exams': 'exams',
                            'reset_finance': 'finance',
                            'reset_academic_years': 'academic_years',
                            'reset_staff': 'staff'
                        };

                        for (const [id, section] of Object.entries(checkboxes)) {
                            const elem = document.getElementById(id);
                            if (elem && elem.checked) {
                                sections.push(section);
                            }
                        }

                        const passwordElem = document.getElementById('reset_password');
                        const password = passwordElem ? passwordElem.value : '';

                        if (sections.length === 0) {
                            console.log('No sections selected');
                            showToastError('Please select at least one section to reset.');
                            return;
                        }

                        if (!password) {
                            console.log('No password entered');
                            showToastError('Please enter your password to confirm.');
                            return;
                        }

                        console.log('Showing modal...');
                        const sectionNames = sections.map(s => s.replace('_', ' ')).join(', ');

                        showModal({
                            type: 'warning',
                            title: 'WARNING',
                            message: `<p>You are about to PERMANENTLY DELETE data from:</p><p style="font-weight: bold; color: #ef4444;">${sectionNames}</p><p>This action CANNOT be undone!</p><p>Are you absolutely sure?</p>`,
                            confirmText: 'Yes, Continue',
                            confirmType: 'danger',
                            onConfirm: () => {
                                // Second confirmation for safety
                                showModal({
                                    type: 'error',
                                    title: 'FINAL WARNING',
                                    message: '<p style="font-weight: bold; font-size: 18px; color: #dc2626;">This is your FINAL warning.</p><p>Proceed with deletion?</p>',
                                    confirmText: 'DELETE DATA',
                                    confirmType: 'danger',
                                    onConfirm: () => {
                                        // Send reset request
                                        fetch('reset_database.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json'
                                            },
                                            body: JSON.stringify({
                                                sections: sections,
                                                password: password
                                            })
                                        })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.success) {
                                                    showToastSuccess('Database reset completed successfully!');
                                                    setTimeout(() => {
                                                        location.reload();
                                                    }, 2000);
                                                } else {
                                                    showToastError('Error: ' + data.message);
                                                }
                                            })
                                            .catch(error => {
                                                showToastError('An error occurred: ' + error);
                                                console.error('Error:', error);
                                            });
                                    }
                                });
                            }
                        });
                    }
                </script>
            <?php elseif ($active_tab == 'school'): ?>
                <div class="table-container" style="padding: 32px;">
                    <h3 style="margin-top: 0; margin-bottom: 24px;">School Profile</h3>

                    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 40px;">
                        <!-- Logo Upload with Drag & Drop -->
                        <div class="form-group">
                            <label>School Logo</label>
                            <div id="logo-drop-zone"
                                style="border: 2px dashed var(--s-border); border-radius: var(--s-radius); padding: 40px 20px; text-align: center; background: #fafafa; cursor: pointer; transition: all 0.3s; height: 260px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                <?php
                                $current_logo = get_setting('school_logo');
                                if ($current_logo && file_exists(__DIR__ . '/../../uploads/' . $current_logo)):
                                    ?>
                                    <img id="logo-preview" src="<?php echo BASE_URL . 'uploads/' . $current_logo; ?>"
                                        alt="School Logo"
                                        style="max-width: 140px; max-height: 140px; border-radius: 12px; margin-bottom: 16px; box-shadow: var(--s-shadow-md);">
                                <?php else: ?>
                                    <img id="logo-preview" src="" alt="Preview"
                                        style="max-width: 140px; max-height: 140px; border-radius: 12px; margin-bottom: 16px; display: none; box-shadow: var(--s-shadow-md);">
                                <?php endif; ?>

                                <div id="upload-prompt" style="<?php echo ($current_logo) ? 'display:none' : ''; ?>">
                                    <svg width="40" height="40" fill="none" stroke="var(--s-text-muted)" viewBox="0 0 24 24"
                                        style="margin-bottom: 12px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p style="color: var(--s-text-muted); margin: 0; font-size: 13px; font-weight: 600;">
                                        Click to upload logo</p>
                                </div>
                            </div>
                            <input type="file" id="logo-input" name="school_logo" accept="image/*" style="display: none;">
                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    const dropZone = document.getElementById('logo-drop-zone');
                                    const fileInput = document.getElementById('logo-input');
                                    const preview = document.getElementById('logo-preview');
                                    const uploadPrompt = document.getElementById('upload-prompt');

                                    if (dropZone && fileInput) {
                                        dropZone.addEventListener('click', () => fileInput.click());

                                        dropZone.addEventListener('dragover', (e) => {
                                            e.preventDefault();
                                            dropZone.style.borderColor = 'var(--s-primary)';
                                            dropZone.style.background = '#eff6ff';
                                        });

                                        dropZone.addEventListener('dragleave', () => {
                                            dropZone.style.borderColor = 'var(--s-border)';
                                            dropZone.style.background = '#fafafa';
                                        });

                                        dropZone.addEventListener('drop', (e) => {
                                            e.preventDefault();
                                            dropZone.style.borderColor = 'var(--s-border)';
                                            dropZone.style.background = '#fafafa';

                                            const files = e.dataTransfer.files;
                                            if (files.length > 0) {
                                                fileInput.files = files;
                                                previewImage(files[0]);
                                            }
                                        });

                                        fileInput.addEventListener('change', (e) => {
                                            if (e.target.files.length > 0) {
                                                previewImage(e.target.files[0]);
                                            }
                                        });
                                    }

                                    function previewImage(file) {
                                        if (file && file.type.startsWith('image/')) {
                                            const reader = new FileReader();
                                            reader.onload = (e) => {
                                                preview.src = e.target.result;
                                                preview.style.display = 'block';
                                                if (uploadPrompt) uploadPrompt.style.display = 'none';
                                            };
                                            reader.readAsDataURL(file);
                                        }
                                    }
                                });
                            </script>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <div class="form-group">
                                <label>School Name</label>
                                <input type="text" name="settings[school_name]" class="form-control"
                                    value="<?php echo htmlspecialchars(get_setting('school_name')); ?>"
                                    placeholder="Enter official school name">
                            </div>
                            <div class="form-group">
                                <label>School Motto / Slogan</label>
                                <input type="text" name="settings[school_motto]" class="form-control"
                                    value="<?php echo htmlspecialchars(get_setting('school_motto')); ?>"
                                    placeholder="e.g. Excellence in Education">
                            </div>

                            <div class="form-group">
                                <label>Logo Alignment (Sidebar)</label>
                                <div style="display: flex; gap: 16px; margin-top: 10px;">
                                    <label
                                        style="flex: 1; border: 1px solid var(--s-border); padding: 12px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                        <input type="radio" name="settings[logo_position]" value="above" <?php echo get_setting('logo_position', 'above') == 'above' ? 'checked' : ''; ?>>
                                        <span style="font-size: 14px; font-weight: 600;">Above Name</span>
                                    </label>
                                    <label
                                        style="flex: 1; border: 1px solid var(--s-border); padding: 12px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                        <input type="radio" name="settings[logo_position]" value="below" <?php echo get_setting('logo_position', 'above') == 'below' ? 'checked' : ''; ?>>
                                        <span style="font-size: 14px; font-weight: 600;">Below Name</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 32px; border-top: 1px solid var(--s-border); padding-top: 24px;">
                        <button type="submit" class="btn-add">Update School Profile</button>
                    </div>
                </div>
            <?php elseif ($active_tab == 'features'): ?>
                <div class="table-container" style="padding: 32px;">
                    <h3 style="margin-top: 0; margin-bottom: 24px;">Feature Configuration</h3>
                    <p style="color: var(--s-text-muted); font-size: 14px; margin-bottom: 32px;">Toggle system modules on or
                        off to customize your experience and optimize performance.</p>

                    <div style="display: grid; gap: 16px;">
                        <label
                            style="display: flex; align-items: center; padding: 20px; border: 1px solid var(--s-border); border-radius: 12px; cursor: pointer; transition: background 0.2s;">
                            <input type="hidden" name="settings[enable_attendance]" value="0">
                            <input type="checkbox" name="settings[enable_attendance]" value="1" <?php echo get_setting('enable_attendance') == '1' ? 'checked' : ''; ?> class="feature-checkbox"
                                style="width: 20px; height: 20px; margin-right: 16px; accent-color: var(--s-primary);">
                            <div>
                                <div style="font-weight: 700; color: var(--s-text-main);">Attendance Tracking</div>
                                <div style="font-size: 12px; color: var(--s-text-muted);">Enable student and staff daily
                                    attendance monitoring</div>
                            </div>
                        </label>

                        <label
                            style="display: flex; align-items: center; padding: 20px; border: 1px solid var(--s-border); border-radius: 12px; cursor: pointer; transition: background 0.2s;">
                            <input type="hidden" name="settings[enable_parent_portal]" value="0">
                            <input type="checkbox" name="settings[enable_parent_portal]" value="1" <?php echo get_setting('enable_parent_portal') == '1' ? 'checked' : ''; ?> class="feature-checkbox"
                                style="width: 20px; height: 20px; margin-right: 16px; accent-color: var(--s-primary);">
                            <div>
                                <div style="font-weight: 700; color: var(--s-text-main);">Parent Portal</div>
                                <div style="font-size: 12px; color: var(--s-text-muted);">Allow parents to view results,
                                    attendance, and finance online</div>
                            </div>
                        </label>

                        <label
                            style="display: flex; align-items: center; padding: 20px; border: 1px solid var(--s-border); border-radius: 12px; cursor: pointer; transition: background 0.2s;">
                            <input type="hidden" name="settings[enable_lesson_plans]" value="0">
                            <input type="checkbox" name="settings[enable_lesson_plans]" value="1" <?php echo get_setting('enable_lesson_plans') == '1' ? 'checked' : ''; ?> class="feature-checkbox"
                                style="width: 20px; height: 20px; margin-right: 16px; accent-color: var(--s-primary);">
                            <div>
                                <div style="font-weight: 700; color: var(--s-text-main);">Teacher Lesson Plans</div>
                                <div style="font-size: 12px; color: var(--s-text-muted);">Enable module for teachers to
                                    create and manage academic lesson plans</div>
                            </div>
                        </label>

                        <label
                            style="display: flex; align-items: center; padding: 20px; border: 1px solid var(--s-border); border-radius: 12px; cursor: pointer; transition: background 0.2s;">
                            <input type="hidden" name="settings[enable_library]" value="0">
                            <input type="checkbox" name="settings[enable_library]" value="1" <?php echo get_setting('enable_library') == '1' ? 'checked' : ''; ?> class="feature-checkbox"
                                style="width: 20px; height: 20px; margin-right: 16px; accent-color: var(--s-primary);">
                            <div>
                                <div style="font-weight: 700; color: var(--s-text-main);">Library Management</div>
                                <div style="font-size: 12px; color: var(--s-text-muted);">Track book inventory, issues, and
                                    returns</div>
                            </div>
                        </label>

                        <label
                            style="display: flex; align-items: center; padding: 20px; border: 1px solid var(--s-border); border-radius: 12px; cursor: pointer; transition: background 0.2s;">
                            <input type="hidden" name="settings[enable_asset_management]" value="0">
                            <input type="checkbox" name="settings[enable_asset_management]" value="1" <?php echo get_setting('enable_asset_management') == '1' ? 'checked' : ''; ?> class="feature-checkbox"
                                style="width: 20px; height: 20px; margin-right: 16px; accent-color: var(--s-primary);">
                            <div>
                                <div style="font-weight: 700; color: var(--s-text-main);">Asset Management</div>
                                <div style="font-size: 12px; color: var(--s-text-muted);">Track school equipment, furniture,
                                    and fixed assets</div>
                            </div>
                        </label>

                        <label
                            style="display: flex; align-items: center; padding: 20px; border: 1px solid var(--s-border); border-radius: 12px; cursor: pointer; transition: background 0.2s;">
                            <input type="hidden" name="settings[enable_parent_password]" value="0">
                            <input type="checkbox" name="settings[enable_parent_password]" value="1" <?php echo get_setting('enable_parent_password') == '1' ? 'checked' : ''; ?> class="feature-checkbox"
                                style="width: 20px; height: 20px; margin-right: 16px; accent-color: var(--s-primary);">
                            <div>
                                <div style="font-weight: 700; color: var(--s-text-main);">Parent Password Control</div>
                                <div style="font-size: 12px; color: var(--s-text-muted);">Enforce secure access and password
                                    management for parents</div>
                            </div>
                        </label>
                    </div>

                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const checkboxes = document.querySelectorAll('.feature-checkbox');
                        checkboxes.forEach(cb => {
                            cb.addEventListener('change', function () {
                                const name = this.getAttribute('name');
                                const value = this.checked ? '1' : '0';

                                // Create form data
                                const formData = new FormData();
                                formData.append('tab', 'features');
                                formData.append(name, value);

                                // Show "Saving..." toast or indicator
                                if (window.showToast) showToast('Saving changes...', 'info');

                                fetch('save.php', {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            if (window.showToastSuccess) showToastSuccess(data.message);
                                            else if (window.showToast) showToast(data.message, 'success');

                                            // Update sidebar visibility immediately
                                            const sidebarMaps = {
                                                'settings[enable_attendance]': 'nav-attendance',
                                                'settings[enable_lesson_plans]': 'nav-lesson-plans',
                                                'settings[enable_library]': 'nav-library',
                                                'settings[enable_asset_management]': 'nav-assets',
                                                'settings[enable_parent_password]': ['nav-parent-password-admin', 'nav-parent-password']
                                            };

                                            if (sidebarMaps[name]) {
                                                const items = Array.isArray(sidebarMaps[name]) ? sidebarMaps[name] : [sidebarMaps[name]];
                                                items.forEach(id => {
                                                    const navItem = document.getElementById(id);
                                                    if (navItem) {
                                                        navItem.style.display = (value === '1') ? 'block' : 'none';
                                                    }
                                                });
                                            }
                                        } else {
                                            if (window.showToastError) showToastError(data.message);
                                            else alert('Error: ' + data.message);
                                            // Revert checkbox state on failure
                                            this.checked = !this.checked;
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        if (window.showToastError) showToastError('An error occurred while saving.');
                                        this.checked = !this.checked;
                                    });
                            });
                        });
                    });
                </script>
        </div>
    <?php endif; ?>

    <?php if ($active_tab == 'admin' && $_SESSION['role'] == 'super_admin'): ?>
        <script>
            // Universal Modal Dialog System
            const UniversalModal = {
                show: function (title, message, type = 'alert', onConfirm = null, onCancel = null) {
                    const modal = document.getElementById('universal-modal');
                    const titleEl = document.getElementById('modal-dialog-title');
                    const messageEl = document.getElementById('modal-dialog-message');
                    const confirmBtn = document.getElementById('modal-confirm-btn');
                    const cancelBtn = document.getElementById('modal-cancel-btn');
                    const header = document.getElementById('modal-header');

                    titleEl.textContent = title;
                    messageEl.textContent = message;

                    // Style based on type
                    if (type === 'confirm' || type === 'warning') {
                        cancelBtn.style.display = 'block';
                        confirmBtn.textContent = 'Confirm';
                        if (type === 'warning') {
                            header.style.background = '#fef3c7';
                            header.style.borderBottom = '2px solid #f59e0b';
                            titleEl.style.color = '#92400e';
                            confirmBtn.className = 'btn btn-outline-danger';
                        } else {
                            header.style.background = 'white';
                            header.style.borderBottom = '1px solid #e2e8f0';
                            titleEl.style.color = '#1e293b';
                            confirmBtn.className = 'btn btn-primary';
                        }
                    } else {
                        cancelBtn.style.display = 'none';
                        confirmBtn.textContent = 'OK';
                        header.style.background = 'white';
                        header.style.borderBottom = '1px solid #e2e8f0';
                        titleEl.style.color = '#1e293b';
                        confirmBtn.className = 'btn btn-primary';
                    }

                    // Set up event handlers
                    confirmBtn.onclick = () => {
                        this.hide();
                        if (onConfirm) onConfirm();
                    };

                    cancelBtn.onclick = () => {
                        this.hide();
                        if (onCancel) onCancel();
                    };

                    modal.style.display = 'flex';
                },

                hide: function () {
                    document.getElementById('universal-modal').style.display = 'none';
                },

                alert: function (title, message, onClose = null) {
                    this.show(title, message, 'alert', onClose);
                },

                confirm: function (title, message, onConfirm, onCancel = null) {
                    this.show(title, message, 'confirm', onConfirm, onCancel);
                },

                warning: function (title, message, onConfirm, onCancel = null) {
                    this.show(title, message, 'warning', onConfirm, onCancel);
                }
            };

            // Close modal on outside click
            // Moved to end of page
            // document.getElementById('universal-modal').addEventListener('click', function(e) {
        </script>

        <!-- Hero Section for Admin Tab -->
        <div class="admin-hero">
            <div class="admin-hero-content">
                <h2>Admin Management</h2>
                <p>Configure system access and designate administrator roles. Control who can manage school
                    settings, financial records, and academic data with precision.</p>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"
                style="border-radius: 12px; margin-bottom: 24px; border: none; background: #dcfce7; color: #166534; padding: 16px 20px; font-weight: 600; display: flex; align-items: center; gap: 12px;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"
                style="border-radius: 12px; margin-bottom: 24px; border: none; background: #fee2e2; color: #991b1b; padding: 16px 20px; font-weight: 600; display: flex; align-items: center; gap: 12px;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd" />
                </svg>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Manage Admin Users Section -->
        <div class="table-container">
            <div class="table-header-row">
                <h4 class="table-title">Registered Administrators</h4>
                <button type="button" onclick="showAddAdminModal()" class="btn-add">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add New Admin
                </button>
            </div>

            <?php
            if ($_SESSION['role'] == 'super_admin') {
                $admin_sql = "SELECT u.*, r.role_name 
                                      FROM users u 
                                      JOIN roles r ON u.role_id = r.role_id 
                                      WHERE r.role_name IN ('super_admin', 'admin')
                                      ORDER BY r.role_name, u.username";
            } else {
                $admin_sql = "SELECT u.*, r.role_name 
                                      FROM users u 
                                      JOIN roles r ON u.role_id = r.role_id 
                                      WHERE r.role_name = 'admin'
                                      ORDER BY u.username";
            }
            $admin_result = mysqli_query($conn, $admin_sql);
            ?>

            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Administrator</th>
                        <th>Contact Information</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($admin = mysqli_fetch_assoc($admin_result)): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div
                                        style="width: 40px; height: 40px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #64748b; font-weight: 700; font-size: 16px;">
                                        <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--s-text-main);">
                                            <?php echo htmlspecialchars($admin['full_name'] ?? 'No Name'); ?>
                                        </div>
                                        <div style="font-size: 12px; color: var(--s-text-muted);">
                                            @<?php echo htmlspecialchars($admin['username']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 500;">
                                    <?php echo htmlspecialchars($admin['email'] ?? 'No Email'); ?>
                                </div>
                            </td>
                            <td>
                                <span
                                    class="badge <?php echo $admin['role_name'] == 'super_admin' ? 'badge-super-admin' : 'badge-admin'; ?>">
                                    <?php echo $admin['role_name'] == 'super_admin' ? 'Super Admin' : 'Administrator'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $admin['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btn-group" style="justify-content: flex-end;">
                                    <?php if ($admin['role_name'] == 'super_admin' && $admin['user_id'] != $_SESSION['user_id']): ?>
                                        <span
                                            style="color: var(--s-text-muted); font-size: 12px; font-weight: 600; padding: 0 8px;">Protected</span>
                                    <?php else: ?>
                                        <button type="button"
                                            onclick="editAdmin(<?php echo $admin['user_id']; ?>, '<?php echo addslashes($admin['username']); ?>', '<?php echo addslashes($admin['full_name'] ?? ''); ?>', '<?php echo addslashes($admin['email'] ?? ''); ?>', '<?php echo $admin['role_name']; ?>', <?php echo $admin['is_active']; ?>)"
                                            class="btn-icon" title="Edit Account">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <?php if ($admin['user_id'] != $_SESSION['user_id']): ?>
                                            <button type="button"
                                                onclick="deleteAdmin(<?php echo $admin['user_id']; ?>, '<?php echo addslashes($admin['username']); ?>')"
                                                class="btn-icon btn-danger-icon" title="Delete Account">
                                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Add/Edit Admin Modal Dialog -->
        <div id="admin-modal"
            style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
            <div
                style="background: white; border-radius: 20px; max-width: 520px; width: 90%; box-shadow: var(--s-shadow-lg); animation: modalReveal 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); overflow: hidden; border: 1px solid rgba(255,255,255,0.1);">
                <!-- Modal Header -->
                <div style="padding: 32px 32px 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 id="modal-title"
                            style="margin: 0; font-size: 22px; font-weight: 800; color: var(--s-text-main); letter-spacing: -0.02em;">
                            Account Settings</h4>
                        <p id="modal-subtitle" style="margin: 4px 0 0 0; font-size: 14px; color: var(--s-text-muted);">
                            Provide
                            administrator account details</p>
                    </div>
                    <button type="button" onclick="closeAdminModal()"
                        style="background: var(--s-bg-light); border: 1px solid var(--s-border); width: 32px; height: 32px; border-radius: 50%; color: var(--s-text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <form id="admin-form" action="admin_actions.php" method="POST"
                    onsubmit="return handleAdminFormSubmit(event)">
                    <div style="padding: 0 32px 32px;">
                        <input type="hidden" name="action" id="modal-action" value="add_admin">
                        <input type="hidden" name="user_id" id="modal-user-id">

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label>Username <span style="color: var(--s-danger);">*</span></label>
                                <input type="text" name="username" id="modal-username" class="form-control" required
                                    minlength="3" placeholder="e.g. john_doe">
                            </div>

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label>Full Name</label>
                                <input type="text" name="full_name" id="modal-fullname" class="form-control"
                                    placeholder="Johnathan Doe">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>Email Address</label>
                            <input type="email" name="email" id="modal-email" class="form-control"
                                placeholder="john@example.com">
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>Access Role</label>
                            <select name="role" id="modal-role" class="form-control"
                                style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 20 20\'%3E%3Cpath stroke=\'%236B7280\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M6 8l4 4 4-4\'/%3E%3C/svg%3E'); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; appearance: none;">
                                <option value="admin">Standard Administrator</option>
                                <?php if ($_SESSION['role'] == 'super_admin'): ?>
                                    <option value="super_admin">Super Administrator</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 24px;">
                            <label>Password <span id="password-required" style="color: var(--s-danger);">*</span></label>
                            <input type="password" name="password" id="modal-password" class="form-control" minlength="3"
                                placeholder="••••••••">
                            <small
                                style="color: var(--s-text-muted); font-size: 12px; margin-top: 6px; display: block;">Leave
                                blank to maintain existing password during editing.</small>
                        </div>

                        <div class="form-group"
                            style="margin-bottom: 0; background: var(--s-bg-light); padding: 16px; border-radius: 12px; border: 1px solid var(--s-border);">
                            <label
                                style="display: flex; align-items: center; cursor: pointer; font-size: 14px; color: var(--s-text-main); margin: 0;">
                                <div style="position: relative; margin-right: 12px;">
                                    <input type="checkbox" name="is_active" id="modal-active" value="1" checked
                                        style="width: 20px; height: 20px; cursor: pointer; accent-color: var(--s-primary);">
                                </div>
                                <div>
                                    <div style="font-weight: 700;">Account Status</div>
                                    <div style="font-size: 12px; color: var(--s-text-muted);">Enable or disable
                                        account access immediately</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div
                        style="display: flex; gap: 12px; justify-content: flex-end; padding: 24px 32px; border-top: 1px solid var(--s-border); background: #fafafa;">
                        <button type="button" onclick="closeAdminModal()"
                            style="padding: 12px 24px; background: white; color: var(--s-text-muted); border: 1px solid var(--s-border); border-radius: 10px; font-weight: 700; cursor: pointer; transition: all 0.2s;">Discard</button>
                        <button type="button" onclick="submitAdminForm()" id="save-admin-btn"
                            style="padding: 12px 24px; background: var(--s-primary); color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);">Save
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <style>
            @keyframes modalReveal {
                from {
                    opacity: 0;
                    transform: scale(0.9) translateY(20px);
                }

                to {
                    opacity: 1;
                    transform: scale(1) translateY(0);
                }
            }
        </style>

        <style>
            @keyframes modalSlideIn {
                from {
                    opacity: 0;
                    transform: scale(0.95) translateY(-10px);
                }

                to {
                    opacity: 1;
                    transform: scale(1) translateY(0);
                }
            }

            #admin-modal input:focus,
            #admin-modal select:focus {
                outline: none;
                border-color: #3b82f6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }
        </style>

        <script>
            function showAddAdminModal() {
                // Move modal to body to ensure it's not nested in another form
                const modal = document.getElementById('admin-modal');
                if (modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }

                document.getElementById('modal-title').textContent = 'Add Administrator';
                document.getElementById('modal-action').value = 'add_admin';
                document.getElementById('modal-user-id').value = '';
                document.getElementById('modal-username').value = '';
                document.getElementById('modal-fullname').value = '';
                document.getElementById('modal-email').value = '';
                document.getElementById('modal-role').value = 'admin';
                document.getElementById('modal-password').value = '';
                document.getElementById('modal-password').required = true;
                document.getElementById('password-required').style.display = 'inline';
                document.getElementById('modal-active').checked = true;
                document.getElementById('admin-modal').style.display = 'flex';
            }

            function editAdmin(userId, username, fullName, email, role, isActive) {
                // Move modal to body to ensure it's not nested in another form
                const modal = document.getElementById('admin-modal');
                if (modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }

                document.getElementById('modal-title').textContent = 'Edit Administrator';
                document.getElementById('modal-action').value = 'edit_admin';
                document.getElementById('modal-user-id').value = userId;
                document.getElementById('modal-username').value = username;
                document.getElementById('modal-fullname').value = fullName;
                document.getElementById('modal-email').value = email;
                document.getElementById('modal-role').value = role;
                document.getElementById('modal-password').value = '';
                document.getElementById('modal-password').required = false;
                document.getElementById('password-required').style.display = 'none';
                document.getElementById('modal-active').checked = isActive == 1;
                document.getElementById('admin-modal').style.display = 'flex';
            }

            function closeAdminModal() {
                document.getElementById('admin-modal').style.display = 'none';
            }

            function deleteAdmin(userId, username) {
                UniversalModal.confirm('Delete Administrator', 'Are you sure you want to delete admin user "' + username + '"?\n\nThis action cannot be undone.', () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'admin_actions.php';

                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete_admin';

                    const userIdInput = document.createElement('input');
                    userIdInput.type = 'hidden';
                    userIdInput.name = 'user_id';
                    userIdInput.value = userId;

                    form.appendChild(actionInput);
                    form.appendChild(userIdInput);
                    document.body.appendChild(form);
                    form.submit();
                });
            }

            // Close modal on outside click
            document.getElementById('admin-modal').addEventListener('click', function (e) {
                if (e.target.id === 'admin-modal') {
                    closeAdminModal();
                }
            });

            // Manual submit handler
            function submitAdminForm() {
                console.log('Manual submit triggered');

                const form = document.getElementById('admin-form');
                if (!form) {
                    showToastError('Form not found!');
                    return;
                }

                if (form.checkValidity()) {
                    // Create a synthetic event object
                    const event = {
                        preventDefault: function () { },
                        stopPropagation: function () { }
                    };
                    handleAdminFormSubmit(event);
                } else {
                    // Report validity to show native browser bubbles
                    form.reportValidity();
                }
            }

            // Handle AJAX form submission
            function handleAdminFormSubmit(event) {
                console.log('Form submit triggered');
                event.preventDefault();
                event.stopPropagation();

                const form = document.getElementById('admin-form');
                const formData = new FormData(form);

                console.log('Form data:', Object.fromEntries(formData));

                // Disable submit button during processing
                const submitBtn = document.getElementById('save-admin-btn');
                let originalText = 'Save Changes';
                if (submitBtn) {
                    originalText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Saving...';
                }

                // Send AJAX request
                fetch('admin_actions.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        // Re-enable button
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        }

                        // Close modal
                        closeAdminModal();

                        if (data.success) {
                            // Show success message and reload
                            showToastSuccess(data.message);
                            setTimeout(() => {
                                window.location.href = 'index.php?tab=admin&t=' + Date.now();
                            }, 1500);
                        } else {
                            // Show error message
                            showToastError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        // Re-enable button
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        }

                        showToastError('An error occurred while saving. Please try again.');
                    });

                return false;
            }

            // Attach event listener when DOM is loaded or immediately if script runs after DOM
            if (document.getElementById('admin-form')) {
                document.getElementById('admin-form').addEventListener('submit', handleAdminFormSubmit);
                console.log('Admin form submit listener attached');
            }
        </script>
    <?php endif; ?>

</div><!-- End settings-content -->
</div><!-- End settings-container -->
<br><br>

<!-- Universal Modal Dialog (Enhanced) -->
<div id="universal-modal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
    <div id="modal-container"
        style="background: white; border-radius: 20px; max-width: 480px; width: 90%; box-shadow: var(--s-shadow-lg); overflow: hidden; animation: modalReveal 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
        <div id="modal-header" style="padding: 32px 32px 16px;">
            <h4 id="modal-dialog-title"
                style="margin: 0; font-size: 20px; font-weight: 800; color: var(--s-text-main); letter-spacing: -0.01em;">
            </h4>
        </div>
        <div id="modal-body" style="padding: 0 32px 32px;">
            <p id="modal-dialog-message"
                style="margin: 0; color: var(--s-text-muted); line-height: 1.6; font-size: 15px;"></p>
        </div>
        <div id="modal-footer"
            style="padding: 24px 32px; background: #fafafa; border-top: 1px solid var(--s-border); display: flex; gap: 12px; justify-content: flex-end;">
            <button id="modal-cancel-btn" class="btn btn-secondary"
                style="display: none; padding: 10px 20px; border-radius: 10px; font-weight: 700;">Cancel</button>
            <button id="modal-confirm-btn" class="btn btn-primary"
                style="padding: 10px 20px; border-radius: 10px; font-weight: 700;">OK</button>
        </div>
    </div>
</div>



<script>
    // Close universal modal on outside click
    if (document.getElementById('universal-modal')) {
        document.getElementById('universal-modal').addEventListener('click', function (e) {
            if (e.target.id === 'universal-modal') {
                UniversalModal.hide();
            }
        });
    }
</script>

<?php
include '../../includes/modals.php';
include '../../includes/footer.php';
?>