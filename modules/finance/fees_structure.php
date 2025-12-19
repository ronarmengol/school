<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin', 'accountant']);

$currency_symbol = get_setting('currency_symbol', '$');
$error = "";
$success = "";

// Handle Edit Fee Structure
if (isset($_POST['edit_fee'])) {
    $structure_id = $_POST['structure_id'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];

    $stmt = mysqli_prepare($conn, "UPDATE fee_structures SET amount = ?, description = ? WHERE structure_id = ?");
    mysqli_stmt_bind_param($stmt, "dsi", $amount, $description, $structure_id);
    if (mysqli_stmt_execute($stmt))
        $success = "Fee Structure Updated.";
    else
        $error = "Error updating fee structure.";
}

// Handle Delete Fee Structure
if (isset($_POST['delete_fee'])) {
    $structure_id = $_POST['structure_id'];
    $password = $_POST['password'];
    $user_id = $_SESSION['user_id'];

    // Verify password
    $user_sql = "SELECT password_hash FROM users WHERE user_id = ?";
    $user_stmt = mysqli_prepare($conn, $user_sql);
    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user = mysqli_fetch_assoc($user_result);

    // Check password (support both hashed and plain text for development)
    $password_valid = false;
    if ($user) {
        // Try password_verify first (for hashed passwords)
        if (password_verify($password, $user['password_hash'])) {
            $password_valid = true;
        }
        // Fallback to plain text comparison (for development/legacy)
        elseif ($user['password_hash'] === $password) {
            $password_valid = true;
        }
    }

    if ($password_valid) {
        $stmt = mysqli_prepare($conn, "DELETE FROM fee_structures WHERE structure_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $structure_id);
        if (mysqli_stmt_execute($stmt))
            $success = "Fee Structure Deleted.";
        else
            $error = "Error deleting fee structure.";
    } else {
        $error = "Incorrect password. Fee structure not deleted.";
    }
}

// Handle Add Fee Structure
if (isset($_POST['add_fee'])) {
    $class_id = $_POST['class_id'];
    $term_id = $_POST['term_id'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];

    $stmt = mysqli_prepare($conn, "INSERT INTO fee_structures (class_id, term_id, amount, description) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iids", $class_id, $term_id, $amount, $description);
    if (mysqli_stmt_execute($stmt))
        $success = "Fee Structure Added.";
    else
        $error = "Error adding fee structure.";
}

// Handle Invoice Generation (Bulk)
if (isset($_POST['generate_invoices'])) {
    $class_id = $_POST['class_id'];
    $term_id = $_POST['term_id'];

    // 1. Get total fee amount for this class & term
    $sql_fee = "SELECT SUM(amount) as total FROM fee_structures WHERE class_id = ? AND term_id = ?";
    $stmt_fee = mysqli_prepare($conn, $sql_fee);
    mysqli_stmt_bind_param($stmt_fee, "ii", $class_id, $term_id);
    mysqli_stmt_execute($stmt_fee);
    $res_fee = mysqli_stmt_get_result($stmt_fee);
    $row_fee = mysqli_fetch_assoc($res_fee);
    $total_amount = $row_fee['total'] ?? 0;

    if ($total_amount > 0) {
        // 2. Get all active students in class
        $sql_students = "SELECT student_id FROM students WHERE current_class_id = ? AND status='Active'";
        $stmt_s = mysqli_prepare($conn, $sql_students);
        mysqli_stmt_bind_param($stmt_s, "i", $class_id);
        mysqli_stmt_execute($stmt_s);
        $res_s = mysqli_stmt_get_result($stmt_s);

        $count = 0;
        $errors = [];
        while ($st = mysqli_fetch_assoc($res_s)) {
            // Check if invoice exists
            $check = mysqli_query($conn, "SELECT invoice_id FROM student_fees WHERE student_id='{$st['student_id']}' AND term_id='$term_id'");
            if (mysqli_num_rows($check) == 0) {
                // Create Invoice
                $insert_result = mysqli_query($conn, "INSERT INTO student_fees (student_id, term_id, total_amount) VALUES ('{$st['student_id']}', '$term_id', '$total_amount')");

                if ($insert_result) {
                    $inv_id = mysqli_insert_id($conn);
                    $inv_number = 'INV-' . str_pad($inv_id, 6, '0', STR_PAD_LEFT);

                    // Check if column exists before updating to avoid errors
                    $res_cols = mysqli_query($conn, "SHOW COLUMNS FROM student_fees LIKE 'invoice_number'");
                    if (mysqli_num_rows($res_cols) > 0) {
                        mysqli_query($conn, "UPDATE student_fees SET invoice_number='$inv_number' WHERE invoice_id=$inv_id");
                    }

                    $count++;
                } else {
                    $errors[] = "Failed to create invoice for student ID {$st['student_id']}: " . mysqli_error($conn);
                }
            }
        }

        if ($count > 0) {
            $success = "Generated invoices for $count students.";
        } elseif (!empty($errors)) {
            $error = "Errors occurred: " . implode("; ", $errors);
        } else {
            $success = "All students already have invoices for this term.";
        }
    } else {
        $error = "No fee structure found for this class/term.";
    }
}

$page_title = "Fees Structure";
include '../../includes/header.php';

// Data for dropdowns
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name ASC");
$terms = mysqli_query($conn, "SELECT t.*, y.year_name FROM terms t JOIN academic_years y ON t.academic_year_id = y.year_id ORDER BY y.year_name DESC");
?>

<?php if ($success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            showToastSuccess('<?php echo addslashes($success); ?>');
        });
    </script>
<?php endif; ?>

<?php if ($error): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            showToastError('<?php echo addslashes($error); ?>');
        });
    </script>
<?php endif; ?>

<div style="margin-bottom: 30px;">
    <h2 style="margin: 0; color: #1e293b; font-weight: 700;">Fee Structure Management</h2>
    <p style="color: #64748b; margin: 5px 0 0 0;">Configure class fees, manage structures, and generate student
        invoices.</p>
</div>

<div style="display: grid; grid-template-columns: 380px 1fr; gap: 30px; margin-bottom: 30px;">
    <!-- Left Sidebar: Actions -->
    <div style="display: flex; flex-direction: column; gap: 25px;">
        <!-- Add Fee Structure Card -->
        <div class="card card-premium"
            style="padding: 25px; border: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <h3 style="margin: 0; font-size: 16px; color: #1e293b; font-weight: 700;">Add Fee Structure</h3>
            </div>
            <form method="POST" id="add-fee-form" style="display: grid; gap: 16px;">
                <input type="hidden" name="add_fee" value="1">
                <div class="form-group" style="margin: 0;">
                    <label
                        style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Class</label>
                    <select name="class_id" class="form-control" required
                        style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;">
                        <option value="">-- Select Class --</option>
                        <?php
                        mysqli_data_seek($classes, 0);
                        while ($c = mysqli_fetch_assoc($classes))
                            echo "<option value='{$c['class_id']}'>{$c['class_name']} {$c['section_name']}</option>"; ?>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label
                        style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Academic
                        Term</label>
                    <select name="term_id" class="form-control" required
                        style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;">
                        <option value="">-- Select Term --</option>
                        <?php
                        mysqli_data_seek($terms, 0);
                        while ($t = mysqli_fetch_assoc($terms))
                            echo "<option value='{$t['term_id']}'>{$t['year_name']} - {$t['term_name']}</option>"; ?>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label
                        style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Fee
                        Amount</label>
                    <input type="number" name="amount" class="form-control" required step="0.01" min="0"
                        placeholder="0.00"
                        style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label
                        style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Description</label>
                    <input type="text" name="description" placeholder="e.g. Tuition Fee, Lab Fee" class="form-control"
                        style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px; padding: 0 15px;">
                </div>
                <button type="submit" class="btn btn-primary"
                    style="width: 100%; height: 48px; border-radius: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Save Fee Structure
                </button>
            </form>
        </div>

        <!-- Generate Invoices Card -->
        <div class="card card-premium"
            style="padding: 25px; border: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <h3 style="margin: 0; font-size: 16px; color: #065f46; font-weight: 700;">Bulk Invoice Generator</h3>
            </div>
            <p style="font-size: 13px; color: #047857; margin: 0 0 20px 0; line-height: 1.5;">Generate invoices for all
                active students in a class based on the configured fee structure.</p>
            <form method="POST" id="generate-invoices-form" style="display: grid; gap: 16px;">
                <input type="hidden" name="generate_invoices" value="1">
                <div class="form-group" style="margin: 0;">
                    <label
                        style="color: #065f46; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Class</label>
                    <select name="class_id" id="invoice-class" class="form-control" required
                        style="border-radius: 10px; border: 1px solid #86efac; height: 45px; padding: 0 15px; background: white;">
                        <option value="">-- Select Class --</option>
                        <?php
                        mysqli_data_seek($classes, 0);
                        while ($c = mysqli_fetch_assoc($classes))
                            echo "<option value='{$c['class_id']}'>{$c['class_name']} {$c['section_name']}</option>"; ?>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label
                        style="color: #065f46; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Academic
                        Term</label>
                    <select name="term_id" id="invoice-term" class="form-control" required
                        style="border-radius: 10px; border: 1px solid #86efac; height: 45px; padding: 0 15px; background: white;">
                        <option value="">-- Select Term --</option>
                        <?php
                        mysqli_data_seek($terms, 0);
                        while ($t = mysqli_fetch_assoc($terms))
                            echo "<option value='{$t['term_id']}'>{$t['year_name']} - {$t['term_name']}</option>"; ?>
                    </select>
                </div>
                <button type="button" onclick="confirmGenerateInvoices()" class="btn btn-success"
                    style="width: 100%; height: 48px; border-radius: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 8px; background: #10b981; border: none; color: white;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 11 12 14 22 4"></polyline>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                    Generate Invoices
                </button>
            </form>
        </div>
    </div>

    <!-- Right: Fee Structures List -->
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <div style="width: 12px; height: 12px; background: #6366f1; border-radius: 3px;"></div>
            <h3 style="margin: 0; font-size: 18px; color: #1e293b; font-weight: 700;">Current Fee Structures</h3>
        </div>

        <?php
        // Pagination logic
        $limit = 15;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1)
            $page = 1;
        $offset = ($page - 1) * $limit;

        // Count total records
        $total_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM fee_structures");
        $total_row = mysqli_fetch_assoc($total_res);
        $total_records = $total_row['total'];
        $total_pages = ceil($total_records / $limit);

        $sql_list = "SELECT f.*, c.class_name, c.section_name, t.term_name, y.year_name 
                     FROM fee_structures f 
                     JOIN classes c ON f.class_id = c.class_id 
                     JOIN terms t ON f.term_id = t.term_id 
                     JOIN academic_years y ON t.academic_year_id = y.year_id 
                     ORDER BY y.year_name DESC, c.class_name
                     LIMIT $limit OFFSET $offset";
        $res_list = mysqli_query($conn, $sql_list);
        ?>

        <div class="card card-premium"
            style="overflow: hidden; border: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
            <?php if (mysqli_num_rows($res_list) > 0): ?>
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #f1f5f9;">
                            <th
                                style="padding: 15px 25px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                                Academic Period</th>
                            <th
                                style="padding: 15px 25px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                                Class</th>
                            <th
                                style="padding: 15px 25px; text-align: right; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                                Amount</th>
                            <th
                                style="padding: 15px 25px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                                Description</th>
                            <?php if (in_array($_SESSION['role'], ['super_admin', 'admin'])): ?>
                                <th
                                    style="padding: 15px 25px; text-align: right; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                                    Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($res_list)): ?>
                            <tr id="row-<?php echo $row['structure_id']; ?>"
                                style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;"
                                onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                                <td style="padding: 18px 25px;">
                                    <div style="font-weight: 600; color: #1e293b; font-size: 14px;">
                                        <?php echo htmlspecialchars($row['year_name']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">
                                        <?php echo htmlspecialchars($row['term_name']); ?>
                                    </div>
                                </td>
                                <td style="padding: 18px 25px;">
                                    <span
                                        style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700;">
                                        <?php echo htmlspecialchars($row['class_name'] . ' ' . $row['section_name']); ?>
                                    </span>
                                </td>
                                <td style="padding: 18px 25px; text-align: right;">
                                    <span class="view-mode" id="amount-view-<?php echo $row['structure_id']; ?>"
                                        style="font-weight: 700; color: #10b981; font-size: 16px;">
                                        <?php echo number_format($row['amount'], 2); ?>
                                    </span>
                                    <input type="number" class="form-control edit-mode"
                                        id="amount-edit-<?php echo $row['structure_id']; ?>"
                                        value="<?php echo $row['amount']; ?>"
                                        style="display: none; width: 140px; border-radius: 8px; border: 1px solid #e2e8f0; height: 40px; padding: 0 12px; text-align: right;">
                                </td>
                                <td style="padding: 18px 25px;">
                                    <span class="view-mode" id="desc-view-<?php echo $row['structure_id']; ?>"
                                        style="color: #64748b; font-size: 14px;">
                                        <?php echo htmlspecialchars($row['description'] ?: 'No description'); ?>
                                    </span>
                                    <input type="text" class="form-control edit-mode"
                                        id="desc-edit-<?php echo $row['structure_id']; ?>"
                                        value="<?php echo htmlspecialchars($row['description'] ?? ''); ?>"
                                        style="display: none; border-radius: 8px; border: 1px solid #e2e8f0; height: 40px; padding: 0 12px;">
                                </td>
                                <?php if (in_array($_SESSION['role'], ['super_admin', 'admin'])): ?>
                                    <td style="padding: 18px 25px; text-align: right;">
                                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <button class="btn btn-sm btn-outline-primary view-mode"
                                                onclick="enableEdit(<?php echo $row['structure_id']; ?>)"
                                                id="edit-btn-<?php echo $row['structure_id']; ?>"
                                                style="padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                                Edit
                                            </button>
                                            <button class="btn btn-sm btn-success edit-mode"
                                                onclick="saveEdit(<?php echo $row['structure_id']; ?>)"
                                                id="save-btn-<?php echo $row['structure_id']; ?>"
                                                style="display: none; padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                                Save
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary edit-mode"
                                                onclick="cancelEdit(<?php echo $row['structure_id']; ?>)"
                                                id="cancel-btn-<?php echo $row['structure_id']; ?>"
                                                style="display: none; padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                                Cancel
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger view-mode"
                                                onclick="confirmDelete(<?php echo $row['structure_id']; ?>, '<?php echo addslashes($row['class_name'] . ' ' . $row['section_name']); ?>', '<?php echo addslashes($row['term_name']); ?>')"
                                                id="delete-btn-<?php echo $row['structure_id']; ?>"
                                                style="padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Pagination UI -->
                <?php if ($total_pages > 1): ?>
                    <div
                        style="padding: 20px 25px; border-top: 1px solid #f1f5f9; background: #fff; display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 13px; color: #64748b;">
                            Showing <strong><?php echo $offset + 1; ?></strong> to
                            <strong><?php echo min($offset + $limit, $total_records); ?></strong> of
                            <strong><?php echo $total_records; ?></strong> entries
                        </div>
                        <div style="display: flex; gap: 5px;">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="btn-pagination">Previous</a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="?page=<?php echo $i; ?>" class="btn-pagination <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="btn-pagination">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <style>
                        .btn-pagination {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            min-width: 35px;
                            height: 35px;
                            padding: 0 10px;
                            border-radius: 8px;
                            border: 1px solid #e2e8f0;
                            background: white;
                            color: #475569;
                            font-size: 13px;
                            font-weight: 600;
                            text-decoration: none;
                            transition: all 0.2s;
                        }

                        .btn-pagination:hover {
                            background: #f8fafc;
                            border-color: #cbd5e1;
                            color: #1e293b;
                        }

                        .btn-pagination.active {
                            background: #6366f1;
                            border-color: #6366f1;
                            color: white;
                        }
                    </style>
                <?php endif; ?>
            <?php else: ?>
                <div style="padding: 60px; text-align: center; color: #94a3b8;">
                    <div style="font-size: 48px; font-weight: 700; color: #cbd5e1; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($currency_symbol); ?>
                    </div>
                    <div style="font-weight: 600; font-size: 16px; margin-bottom: 5px;">No Fee Structures Found</div>
                    <div style="font-size: 14px;">Add your first fee structure using the form on the left.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Hidden forms for edit and delete -->
<form id="edit-form" method="POST" style="display: none;">
    <input type="hidden" name="edit_fee" value="1">
    <input type="hidden" name="structure_id" id="edit-structure-id">
    <input type="hidden" name="amount" id="edit-amount">
    <input type="hidden" name="description" id="edit-description">
</form>

<form id="delete-form" method="POST" style="display: none;">
    <input type="hidden" name="delete_fee" value="1">
    <input type="hidden" name="structure_id" id="delete-structure-id">
    <input type="hidden" name="password" id="delete-password">
</form>

<script>
    // Edit Functions
    let originalValues = {};

    function enableEdit(feeId) {
        // Store original values
        originalValues[feeId] = {
            amount: document.getElementById('amount-edit-' + feeId).value,
            description: document.getElementById('desc-edit-' + feeId).value
        };

        // Hide view mode, show edit mode
        document.querySelectorAll('#row-' + feeId + ' .view-mode').forEach(el => el.style.display = 'none');
        document.querySelectorAll('#row-' + feeId + ' .edit-mode').forEach(el => el.style.display = 'inline-block');
    }

    function cancelEdit(feeId) {
        // Restore original values
        if (originalValues[feeId]) {
            document.getElementById('amount-edit-' + feeId).value = originalValues[feeId].amount;
            document.getElementById('desc-edit-' + feeId).value = originalValues[feeId].description;
        }

        // Show view mode, hide edit mode
        document.querySelectorAll('#row-' + feeId + ' .view-mode').forEach(el => el.style.display = '');
        document.querySelectorAll('#row-' + feeId + ' .edit-mode').forEach(el => el.style.display = 'none');
    }

    function saveEdit(structureId) {
        const amount = document.getElementById('amount-edit-' + structureId).value;
        const description = document.getElementById('desc-edit-' + structureId).value;

        if (!amount || amount <= 0) {
            showToastError('Please enter a valid amount.');
            return;
        }

        // Set form values and submit
        document.getElementById('edit-structure-id').value = structureId;
        document.getElementById('edit-amount').value = amount;
        document.getElementById('edit-description').value = description;
        document.getElementById('edit-form').submit();
    }

    function confirmDelete(structureId, className, termName) {
        showModal({
            type: 'warning',
            icon: '',
            title: 'Delete Fee Structure',
            message: `
            <p><strong>Are you sure you want to delete this fee structure?</strong></p>
            <div style="background: #fef3c7; padding: 12px; border-radius: 8px; margin: 15px 0;">
                <p style="margin: 5px 0;"><strong>Class:</strong> ${className}</p>
                <p style="margin: 5px 0;"><strong>Term:</strong> ${termName}</p>
            </div>
            <div style="background: #fee2e2; padding: 12px; border-radius: 8px; margin-top: 10px;">
                <p style="margin: 0; color: #991b1b; font-size: 14px;"><strong>⚠️ Warning:</strong> This action cannot be undone!</p>
            </div>
            <div style="margin-top: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Enter your password to confirm:</label>
                <input type="password" id="delete-password-input" class="form-control" placeholder="Your password" 
                       style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
            </div>
        `,
            confirmText: 'Delete Fee Structure',
            confirmType: 'danger',
            onConfirm: () => {
                const password = document.getElementById('delete-password-input').value;

                if (!password) {
                    showToastError('Please enter your password to confirm deletion.');
                    return;
                }

                // Set form values and submit
                document.getElementById('delete-structure-id').value = structureId;
                document.getElementById('delete-password').value = password;
                document.getElementById('delete-form').submit();
            }
        });

        // Focus password input after modal opens
        setTimeout(() => {
            const passwordInput = document.getElementById('delete-password-input');
            if (passwordInput) passwordInput.focus();
        }, 300);
    }

    function confirmGenerateInvoices() {
        const classSelect = document.getElementById('invoice-class');
        const termSelect = document.getElementById('invoice-term');

        if (!classSelect.value || !termSelect.value) {
            showToastError('Please select both a class and a term.');
            return;
        }

        const className = classSelect.options[classSelect.selectedIndex].text;
        const termName = termSelect.options[termSelect.selectedIndex].text;

        showModal({
            type: 'warning',
            icon: '',
            title: 'Generate Bulk Invoices',
            message: `
            <p><strong>You are about to generate invoices for all active students.</strong></p>
            <div style="background: #dbeafe; padding: 12px; border-radius: 8px; margin: 15px 0;">
                <p style="margin: 5px 0;"><strong>Class:</strong> ${className}</p>
                <p style="margin: 5px 0;"><strong>Term:</strong> ${termName}</p>
            </div>
            <div style="background: #fef3c7; padding: 12px; border-radius: 8px; margin-top: 10px;">
                <p style="margin: 0; color: #92400e; font-size: 14px;"><strong>ℹ️ Note:</strong> Existing invoices will not be duplicated.</p>
            </div>
        `,
            confirmText: 'Generate Invoices',
            confirmType: 'success',
            onConfirm: () => {
                document.getElementById('generate-invoices-form').submit();
            }
        });
    }
</script>

<?php
include '../../includes/modals.php';
include '../../includes/footer.php';
?>