<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin', 'accountant']);

$is_ajax = isset($_GET['ajax']);

if (!$is_ajax) {
  $page_title = "Billing Check";
  include '../../includes/header.php';
}

$selected_term = $_GET['term_id'] ?? '';
if (!$selected_term) {
  $latest_term_res = mysqli_query($conn, "SELECT t.term_id FROM terms t JOIN academic_years y ON t.academic_year_id = y.year_id ORDER BY y.year_name DESC, t.term_name DESC LIMIT 1");
  if ($latest_row = mysqli_fetch_assoc($latest_term_res)) {
    $selected_term = $latest_row['term_id'];
  }
}
$selected_class = $_GET['class_id'] ?? '';

// Fetch all terms and classes for filters
$terms = mysqli_query($conn, "SELECT t.*, y.year_name FROM terms t JOIN academic_years y ON t.academic_year_id = y.year_id ORDER BY y.year_name DESC, t.term_name ASC");
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name ASC");

// Handle Bulk Billing from this page too
$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_bill'])) {
  $class_id = $_POST['class_id'];
  $term_id = $_POST['term_id'];

  // Get total fee amount for this class & term
  $sql_fee = "SELECT SUM(amount) as total FROM fee_structures WHERE class_id = ? AND term_id = ?";
  $stmt_fee = mysqli_prepare($conn, $sql_fee);
  mysqli_stmt_bind_param($stmt_fee, "ii", $class_id, $term_id);
  mysqli_stmt_execute($stmt_fee);
  $res_fee = mysqli_stmt_get_result($stmt_fee);
  $row_fee = mysqli_fetch_assoc($res_fee);
  $total_amount = $row_fee['total'] ?? 0;

  if ($total_amount > 0) {
    $sql_students = "SELECT student_id FROM students WHERE current_class_id = ? AND status='Active'";
    $stmt_s = mysqli_prepare($conn, $sql_students);
    mysqli_stmt_bind_param($stmt_s, "i", $class_id);
    mysqli_stmt_execute($stmt_s);
    $res_s = mysqli_stmt_get_result($stmt_s);

    $count = 0;
    while ($st = mysqli_fetch_assoc($res_s)) {
      $check = mysqli_query($conn, "SELECT invoice_id FROM student_fees WHERE student_id='{$st['student_id']}' AND term_id='$term_id'");
      if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO student_fees (student_id, term_id, total_amount) VALUES ('{$st['student_id']}', '$term_id', '$total_amount')");
        $inv_id = mysqli_insert_id($conn);
        $inv_num = 'INV-' . str_pad($inv_id, 6, '0', STR_PAD_LEFT);
        // Check if column exists before updating
        $res_cols = mysqli_query($conn, "SHOW COLUMNS FROM student_fees LIKE 'invoice_number'");
        if (mysqli_num_rows($res_cols) > 0) {
          mysqli_query($conn, "UPDATE student_fees SET invoice_number='$inv_num' WHERE invoice_id=$inv_id");
        }
        $count++;
      }
    }
    $success = "Successfully billed $count students.";
  } else {
    $error = "No fee structure defined for this class/term.";
  }
}
?>

<?php if (!$is_ajax): ?>
  <div style="margin-bottom: 30px;">
    <h2 style="margin: 0; color: #1e293b; font-weight: 700;">Billing & Invoicing Status</h2>
    <p style="color: #64748b; margin: 5px 0 0 0;">Comprehensive overview of student billing for the academic term.</p>
  </div>

  <div class="card card-premium" style="padding: 25px; margin-bottom: 30px;">
    <form id="filter-form" method="GET"
      style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: flex-end;">
      <div class="form-group" style="margin: 0;">
        <label
          style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Academic
          Term</label>
        <select name="term_id" id="term_id" class="form-control" required
          style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px;" onchange="updateResults()">
          <option value="">-- Select Term --</option>
          <?php mysqli_data_seek($terms, 0);
          while ($t = mysqli_fetch_assoc($terms)): ?>
            <option value="<?php echo $t['term_id']; ?>" <?php echo $selected_term == $t['term_id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars("{$t['year_name']} - {$t['term_name']}"); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group" style="margin: 0;">
        <label
          style="color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase; margin-bottom: 8px; display: block;">Narrow
          to Class</label>
        <select name="class_id" id="class_id" class="form-control"
          style="border-radius: 10px; border: 1px solid #e2e8f0; height: 45px;" onchange="updateResults()">
          <option value="">-- All Classes --</option>
          <?php
          mysqli_data_seek($classes, 0);
          while ($c = mysqli_fetch_assoc($classes)): ?>
            <option value="<?php echo $c['class_id']; ?>" <?php echo $selected_class == $c['class_id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars("{$c['class_name']} {$c['section_name']}"); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-primary"
        style="height: 45px; border-radius: 10px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
        Filter Results
      </button>
    </form>
  </div>

  <div id="ajax-results-container">
  <?php endif; ?>

  <?php if ($success): ?>
    <script>window.onload = () => showToastSuccess("<?php echo $success; ?>");</script>
  <?php endif; ?>

  <?php if ($error): ?>
    <script>window.onload = () => showToastError("<?php echo $error; ?>");</script>
  <?php endif; ?>

  <?php if ($selected_term): ?>
    <?php
    // Fetch the selected term details for the banner
    $sql_term_info = "SELECT t.term_name, y.year_name 
                      FROM terms t 
                      JOIN academic_years y ON t.academic_year_id = y.year_id 
                      WHERE t.term_id = ?";
    $stmt_term = mysqli_prepare($conn, $sql_term_info);
    mysqli_stmt_bind_param($stmt_term, "i", $selected_term);
    mysqli_stmt_execute($stmt_term);
    $res_term = mysqli_stmt_get_result($stmt_term);
    $term_info = mysqli_fetch_assoc($res_term);

    // Fetch summary per class for the selected term
    $sql_summary = "SELECT 
        c.class_id, c.class_name, c.section_name,
        COUNT(s.student_id) as total_students,
        COUNT(sf.invoice_id) as billed_count,
        COALESCE(fs.total_fee, 0) as expected_fee
        FROM classes c
        LEFT JOIN students s ON c.class_id = s.current_class_id AND s.status = 'Active'
        LEFT JOIN student_fees sf ON s.student_id = sf.student_id AND sf.term_id = ?
        LEFT JOIN (
            SELECT class_id, SUM(amount) as total_fee 
            FROM fee_structures 
            WHERE term_id = ? 
            GROUP BY class_id
        ) fs ON c.class_id = fs.class_id
        WHERE (? = '' OR c.class_id = ?)
        GROUP BY c.class_id
        HAVING total_students > 0
        ORDER BY c.class_name";

    $stmt = mysqli_prepare($conn, $sql_summary);
    mysqli_stmt_bind_param($stmt, "iisi", $selected_term, $selected_term, $selected_class, $selected_class);
    mysqli_stmt_execute($stmt);
    $res_summary = mysqli_stmt_get_result($stmt);
    ?>

    <!-- Academic Term Banner -->
    <div
      style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px 40px; border-radius: 16px; margin-bottom: 35px; box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.3);">
      <div style="display: flex; align-items: center; gap: 15px;">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"
          stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        <div>
          <div
            style="color: rgba(255, 255, 255, 0.9); font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">
            Academic Term</div>
          <h2 style="margin: 0; color: white; font-size: 32px; font-weight: 800; letter-spacing: -0.5px;">
            <?php echo htmlspecialchars($term_info['year_name'] . ' - ' . $term_info['term_name']); ?>
          </h2>
        </div>
      </div>
    </div>

    <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
      <div style="width: 12px; height: 12px; background: #6366f1; border-radius: 3px;"></div>
      <h3 style="margin: 0; font-size: 18px; color: #1e293b; font-weight: 700;">Class-wise Billing Summary</h3>
    </div>

    <div class="card card-premium" style="overflow: hidden; margin-bottom: 40px; border: none;">
      <table class="table" style="width: 100%; border-collapse: collapse;">
        <thead>
          <tr style="background: #f8fafc; border-bottom: 2px solid #f1f5f9;">
            <th
              style="padding: 15px 25px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
              Class</th>
            <th
              style="padding: 15px 25px; text-align: center; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
              Students</th>
            <th
              style="padding: 15px 25px; text-align: center; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
              Status</th>
            <th
              style="padding: 15px 25px; text-align: center; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
              Expected Fee</th>
            <th
              style="padding: 15px 25px; text-align: right; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
              Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($res_summary) > 0): ?>
            <?php while ($sum = mysqli_fetch_assoc($res_summary)):
              $unbilled = $sum['total_students'] - $sum['billed_count'];
              $progress = ($sum['total_students'] > 0) ? ($sum['billed_count'] / $sum['total_students']) * 100 : 0;
              $status_color = $progress == 100 ? '#10b981' : ($progress > 0 ? '#f59e0b' : '#ef4444');
              ?>
              <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;"
                onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                <td style="padding: 18px 25px;">
                  <div style="font-weight: 700; color: #1e293b;">
                    <?php echo htmlspecialchars("{$sum['class_name']} {$sum['section_name']}"); ?>
                  </div>
                </td>
                <td style="padding: 18px 25px; text-align: center; font-weight: 600; color: #475569;">
                  <?php echo $sum['total_students']; ?>
                </td>
                <td style="padding: 18px 25px; text-align: center;">
                  <div style="display: flex; flex-direction: column; align-items: center; gap: 5px;">
                    <div style="font-size: 12px; font-weight: 700; color: <?php echo $status_color; ?>;">
                      <?php echo $sum['billed_count']; ?> / <?php echo $sum['total_students']; ?> Billed
                    </div>
                    <div style="height: 6px; width: 100px; background: #e2e8f0; border-radius: 99px; overflow: hidden;">
                      <div
                        style="height: 100%; width: <?php echo $progress; ?>%; background: <?php echo $status_color; ?>; border-radius: 99px;">
                      </div>
                    </div>
                  </div>
                </td>
                <td style="padding: 18px 25px; text-align: center; font-weight: 700; color: #1e293b;">
                  <?php echo number_format($sum['expected_fee'], 2); ?>
                </td>
                <td style="padding: 18px 25px; text-align: right;">
                  <?php if ($unbilled > 0 && $sum['expected_fee'] > 0): ?>
                    <button type="button" class="btn btn-sm btn-success"
                      style="border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 600;"
                      onclick="confirmBulkBill(<?php echo $sum['class_id']; ?>, '<?php echo addslashes($sum['class_name']); ?>', <?php echo $unbilled; ?>)">
                      Bill <?php echo $unbilled; ?> Missing
                    </button>
                  <?php elseif ($sum['expected_fee'] == 0): ?>
                    <span
                      style="background: #f1f5f9; color: #94a3b8; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase;">Missing
                      Setup</span>
                  <?php else: ?>
                    <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                      <span
                        style="background: #f0fdf4; color: #10b981; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase;">Complete</span>
                      <button type="button" class="btn btn-sm btn-outline-danger"
                        style="border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 4px;"
                        onclick="confirmUndoBilling(<?php echo $sum['class_id']; ?>, '<?php echo addslashes($sum['class_name']); ?>', <?php echo $selected_term; ?>, <?php echo $sum['billed_count']; ?>)"
                        title="Undo billing for this class">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                          stroke-linecap="round" stroke-linejoin="round">
                          <path d="M3 7v6h6"></path>
                          <path d="M21 17a9 9 0 00-9-9 9 9 0 00-6 2.3L3 13"></path>
                        </svg>
                        Undo
                      </button>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="padding: 40px; text-align: center; color: #94a3b8; font-weight: 500;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 10px; color: #cbd5e1;">
                  <line x1="18" y1="20" x2="18" y2="10"></line>
                  <line x1="12" y1="20" x2="12" y2="4"></line>
                  <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                <div style="font-size: 14px;">No class data found for the selected criteria.</div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Hidden Bulk Bill Form -->
    <form id="bulk-bill-form" method="POST" style="display: none;">
      <input type="hidden" name="bulk_bill" value="1">
      <input type="hidden" name="class_id" id="bulk-class-id">
      <input type="hidden" name="term_id" value="<?php echo $selected_term; ?>">
    </form>

    <script>
      function confirmBulkBill(classId, className, count) {
        showModal({
          type: 'warning',
          icon: '',
          title: 'Confirm Bulk Billing',
          message: `<p>You are about to generate invoices for <strong>${count}</strong> students in <strong>${className}</strong>.</p>
                  <p style="margin-top: 10px; color: #64748b; font-size: 14px;">This will create new invoice records for the current term. Existing invoices will not be duplicated.</p>`,
          confirmText: 'Generate Invoices',
          confirmType: 'success',
          onConfirm: () => {
            document.getElementById('bulk-class-id').value = classId;
            document.getElementById('bulk-bill-form').submit();
          }
        });
      }
    </script>

    <!-- Detailed List -->
    <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
      <div style="width: 12px; height: 12px; background: #0891b2; border-radius: 3px;"></div>
      <h3 style="margin: 0; font-size: 18px; color: #1e293b; font-weight: 700;">Individual Student Records</h3>
    </div>

    <div class="card card-premium" style="overflow: hidden; border: none;">
      <table class="table" style="width: 100%; border-collapse: collapse;">
        <thead>
          <tr style="background: #f8fafc; border-bottom: 2px solid #f1f5f9;">
            <th
              style="padding: 15px 25px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
              ADM NO</th>
            <th
              style="padding: 15px 25px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
              Student Name</th>
            <th
              style="padding: 15px 25px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
              Class</th>
            <th
              style="padding: 15px 25px; text-align: center; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
              Billed Amount</th>
            <th
              style="padding: 15px 25px; text-align: right; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
              Status</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $sql_details = "SELECT 
                    s.student_id, s.first_name, s.last_name, s.admission_number, c.class_name, c.section_name,
                    sf.invoice_id, sf.invoice_number, sf.total_amount, sf.paid_amount, sf.status as payment_status
                    FROM students s
                    JOIN classes c ON s.current_class_id = c.class_id
                    LEFT JOIN student_fees sf ON s.student_id = sf.student_id AND sf.term_id = ?
                    WHERE s.status = 'Active'
                    AND (? = '' OR s.current_class_id = ?)
                    ORDER BY c.class_name, s.first_name";

          $stmt_d = mysqli_prepare($conn, $sql_details);
          mysqli_stmt_bind_param($stmt_d, "isi", $selected_term, $selected_class, $selected_class);
          mysqli_stmt_execute($stmt_d);
          $res_details = mysqli_stmt_get_result($stmt_d);

          // Debug: Log query parameters
          error_log("Billing Status Query - Term ID: $selected_term, Class Filter: $selected_class");
          ?>
          <?php if ($res_details && mysqli_num_rows($res_details) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($res_details)): ?>
              <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;"
                onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                <td style="padding: 15px 25px; font-weight: 600; color: #6366f1;">
                  <?php echo htmlspecialchars($row['admission_number']); ?>
                </td>
                <td style="padding: 15px 25px; font-weight: 700; color: #1e293b;">
                  <?php echo htmlspecialchars("{$row['first_name']} {$row['last_name']}"); ?>
                </td>
                <td style="padding: 15px 25px; color: #64748b;">
                  <?php echo htmlspecialchars("{$row['class_name']} {$row['section_name']}"); ?>
                </td>
                <td style="padding: 15px 25px; text-align: center; font-weight: 700; color: #334155;">
                  <?php if ($row['invoice_id']): ?>
                    <div><?php echo number_format($row['total_amount'], 2); ?></div>
                    <?php if ($row['invoice_number']): ?>
                      <div style="font-size: 10px; color: #94a3b8; margin-top: 4px; font-weight: 500;">
                        #<?php echo htmlspecialchars($row['invoice_number']); ?>
                      </div>
                    <?php endif; ?>
                  <?php else: ?>
                    <span style="color: #cbd5e1;">Not Billed</span>
                  <?php endif; ?>
                </td>
                <td style="padding: 15px 25px; text-align: right;">
                  <?php if ($row['invoice_id']): ?>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                      <span
                        style="display: inline-flex; align-items: center; gap: 6px; background: #f0fdf4; color: #10b981; padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                        Billed
                      </span>
                      <span
                        style="font-size: 10px; font-weight: 700; color: <?php echo $row['payment_status'] === 'Paid' ? '#10b981' : ($row['payment_status'] === 'Partial' ? '#f59e0b' : '#ef4444'); ?>;">
                        <?php echo $row['payment_status']; ?>
                      </span>
                    </div>
                  <?php else: ?>
                    <span
                      style="display: inline-flex; align-items: center; gap: 6px; background: #fef2f2; color: #ef4444; padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                      Unbilled
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="padding: 40px; text-align: center; color: #94a3b8; font-weight: 500;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 10px; color: #cbd5e1;">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                  <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <div style="font-size: 14px;">No students found for this selection.</div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="alert alert-info"
      style="padding: 40px; text-align: center; border-radius: 12px; background: #f8fafc; border: 1px dashed #cbd5e1; color: #64748b; display: flex; flex-direction: column; align-items: center; justify-content: center;">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
        stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 15px; color: #94a3b8;">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
        <line x1="16" y1="2" x2="16" y2="6"></line>
        <line x1="8" y1="2" x2="8" y2="6"></line>
        <line x1="3" y1="10" x2="21" y2="10"></line>
      </svg>
      <div style="font-size: 16px; font-weight: 600; color: #475569;">Select Academic Term</div>
      <div style="font-size: 14px; color: #94a3b8; margin-top: 5px;">Please select a term from the filters above to view
        billing status.</div>
    </div>
  <?php endif; ?>
</div> <!-- End ajax-results-container -->

<?php if (!$is_ajax): ?>
  <script>
    function updateResults() {
      const termId = document.getElementById('term_id').value;
      const classId = document.getElementById('class_id').value;
      const container = document.getElementById('ajax-results-container');

      // Show placeholder/loading state
      container.style.opacity = '0.5';

      fetch(`billing_status.php?ajax=1&term_id=${termId}&class_id=${classId}`)
        .then(response => response.text())
        .then(html => {
          container.innerHTML = html;
          container.style.opacity = '1';

          // Re-initialize any necessary tooltips or scripts if needed
          // For this page, confirmBulkBill is defined below and available globally
        })
        .catch(err => {
          console.error('Fetch error:', err);
          container.style.opacity = '1';
        });
    }

    function confirmBulkBill(classId, className, count) {
      showModal({
        type: 'warning',
        icon: '',
        title: 'Confirm Bulk Billing',
        message: `<p>You are about to generate invoices for <strong>${count}</strong> students in <strong>${className}</strong>.</p>
                <p style="margin-top: 10px; color: #64748b; font-size: 14px;">This will create new invoice records for the current term. Existing invoices will not be duplicated.</p>`,
        confirmText: 'Generate Invoices',
        confirmType: 'success',
        onConfirm: () => {
          document.getElementById('bulk-class-id').value = classId;
          document.getElementById('bulk-bill-form').submit();
        }
      });
    }

    function confirmUndoBilling(classId, className, termId, billedCount) {
      showModal({
        type: 'error',
        icon: '⚠️',
        title: 'Undo Billing for ' + className,
        message: `
          <p>You are about to <strong style="color: #dc2626;">DELETE ${billedCount} invoice(s)</strong> for <strong>${className}</strong>.</p>
          <div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 12px; margin: 15px 0; border-radius: 6px;">
            <p style="margin: 0 0 8px 0; font-weight: 700; color: #991b1b;">⚠️ WARNING: This action is PERMANENT</p>
            <p style="margin: 0; font-size: 13px; color: #7f1d1d;">Deleted invoices cannot be recovered. This operation will be logged for audit purposes.</p>
          </div>
          <div style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 12px; margin: 15px 0; border-radius: 6px;">
            <p style="margin: 0 0 5px 0; font-weight: 600; color: #065f46;">✓ Safety Checks:</p>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #047857;">
              <li>Only invoices with <strong>zero payments</strong> will be deleted</li>
              <li>Invoices with payments will be <strong>protected</strong></li>
              <li>All operations are <strong>logged</strong> for accountability</li>
            </ul>
          </div>
          <p style="margin-top: 15px; font-size: 14px; color: #64748b;">Are you absolutely sure you want to proceed?</p>
        `,
        confirmText: 'Yes, Undo Billing',
        cancelText: 'Cancel',
        confirmType: 'danger',
        onConfirm: () => {
          // Show loading state
          const formData = new FormData();
          formData.append('class_id', classId);
          formData.append('term_id', termId);

          fetch('undo_billing.php', {
            method: 'POST',
            body: formData
          })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                showToastSuccess(data.message);
                // Reload page after short delay
                setTimeout(() => {
                  window.location.reload();
                }, 1500);
              } else {
                showToastError(data.message);
              }
            })
            .catch(error => {
              console.error('Error:', error);
              showToastError('An error occurred while undoing billing. Please try again.');
            });
        }
      });
    }
  </script>

  <?php
  include '../../includes/modals.php';
  include '../../includes/footer.php';
endif;
?>