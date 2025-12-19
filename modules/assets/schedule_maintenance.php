<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
require_once '../../config/database.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$page_title = "Schedule Maintenance - Asset Management";
include '../../includes/header.php';
include 'assets_styles.php';

// Fetch assets for selection
$assets = [];
$assets_query = "SELECT asset_id, asset_code, asset_name FROM assets WHERE status != 'Removed' ORDER BY asset_name ASC";
if ($result = mysqli_query($conn, $assets_query)) {
  while ($row = mysqli_fetch_assoc($result)) {
    $assets[] = $row;
  }
}

// Fetch staff (users) for assignment
$staff = [];
$staff_query = "SELECT u.user_id, u.full_name, r.role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.role_id 
                WHERE u.is_active = 1 
                ORDER BY u.full_name ASC";
if ($result = mysqli_query($conn, $staff_query)) {
  while ($row = mysqli_fetch_assoc($result)) {
    $staff[] = $row;
  }
}

$pre_selected_asset = isset($_GET['asset_id']) ? intval($_GET['asset_id']) : 0;
?>

<style>
  .schedule-container {
    max-width: 1000px;
    margin: 0 auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    border: 1px solid var(--asset-border);
  }

  .schedule-header {
    background: #334155;
    padding: 20px 32px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .schedule-header-title h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
  }

  .schedule-header-title .breadcrumb {
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 4px;
    display: block;
  }

  .schedule-steps {
    display: flex;
    justify-content: center;
    padding: 24px;
    background: #f8fafc;
    border-bottom: 1px solid var(--asset-border);
    gap: 40px;
  }

  .step-item {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 600;
    color: var(--asset-muted);
    position: relative;
  }

  .step-item.active {
    color: var(--asset-primary);
  }

  .step-item.active::after {
    content: '';
    position: absolute;
    bottom: -24px;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--asset-primary);
  }

  .schedule-body {
    padding: 32px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
  }

  .section-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--asset-text);
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .section-title span {
    color: var(--asset-muted);
  }

  /* Custom styling for priority dropdown with dots */
  .priority-select-wrapper {
    position: relative;
  }

  .priority-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
  }

  /* Schedule Type Radios */
  .type-grid {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 20px;
  }

  .type-option {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    cursor: pointer;
    color: var(--asset-text);
  }

  .type-option input {
    width: 18px;
    height: 18px;
    cursor: pointer;
  }

  .form-column {
    display: flex;
    flex-direction: column;
    gap: 32px;
  }

  .schedule-footer {
    padding: 24px 32px;
    background: #f8fafc;
    border-top: 1px solid var(--asset-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .footer-left {
    display: flex;
    gap: 12px;
  }

  .btn-save-primary {
    background: var(--asset-primary);
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 700;
    border: none;
    cursor: pointer;
  }

  .btn-outline {
    background: transparent;
    border: 1.5px solid var(--asset-primary);
    color: var(--asset-primary);
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
  }

  .btn-cancel {
    background: #e2e8f0;
    color: #475569;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 700;
    border: none;
    cursor: pointer;
  }
</style>

<div class="asset-module-wrap">
  <div class="schedule-container">
    <div class="schedule-header">
      <div class="schedule-header-title">
        <span class="breadcrumb">Asset Management > Schedule</span>
        <h1>Add Asset Schedule</h1>
      </div>
      <div class="schedule-header-icon">
        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        </svg>
      </div>
    </div>

    <div class="schedule-steps">
      <div class="step-item">Asset</div>
      <div class="step-item active">Details</div>
      <div class="step-item">Timing</div>
      <div class="step-item">Assign</div>
      <div class="step-item">Review</div>
    </div>

    <form action="save_maintenance.php" method="POST" id="scheduleForm">
      <div class="schedule-body">
        <!-- Left Column -->
        <div class="form-column">
          <!-- 1. Asset Information -->
          <section>
            <h3 class="section-title">1. Asset Information</h3>
            <div class="asset-form-group" style="position: relative;">
              <span style="position: absolute; left: 12px; top: 38px; color: var(--asset-muted);">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </span>
              <input type="text" id="assetSearchInput" class="asset-input" style="padding-left: 36px;"
                placeholder="Search Asset by Name or Code..." <?php echo $pre_selected_asset ? 'disabled' : ''; ?>>
              <input type="hidden" name="asset_id" id="selected_asset_id" value="<?php echo $pre_selected_asset; ?>"
                required>

              <!-- Results dropdown for search -->
              <div id="assetSearchResults"
                style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid var(--asset-border); border-radius: 8px; z-index: 50; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-height: 200px; overflow-y: auto; margin-top: 4px;">
                <?php foreach ($assets as $asset): ?>
                  <div class="asset-search-item" data-id="<?php echo $asset['asset_id']; ?>"
                    data-name="<?php echo htmlspecialchars($asset['asset_name']); ?>"
                    data-code="<?php echo htmlspecialchars($asset['asset_code']); ?>"
                    style="padding: 10px 16px; cursor: pointer; border-bottom: 1px solid #f8fafc; font-size: 14px;">
                    <strong
                      style="display: block; color: var(--asset-text);"><?php echo htmlspecialchars($asset['asset_name']); ?></strong>
                    <span
                      style="font-size: 12px; color: var(--asset-muted);"><?php echo htmlspecialchars($asset['asset_code']); ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </section>

          <!-- 2. Schedule Details -->
          <section>
            <h3 class="section-title">2. Schedule Details</h3>
            <label class="asset-label">Schedule Type</label>
            <div class="type-grid">
              <label class="type-option"><input type="radio" name="task_type" value="Maintenance" checked>
                Maintenance</label>
              <label class="type-option"><input type="radio" name="task_type" value="Inspection"> Inspection</label>
              <label class="type-option"><input type="radio" name="task_type" value="Calibration"> Calibration</label>
              <label class="type-option"><input type="radio" name="task_type" value="Service"> Service</label>
            </div>

            <div class="asset-form-group">
              <label class="asset-label">Description</label>
              <textarea name="task_description" class="asset-textarea" rows="4"
                placeholder="Enter schedule details (e.g., quarterly cleaning and check)"></textarea>
            </div>

            <div class="asset-form-group">
              <label class="asset-label">Priority</label>
              <select name="priority" class="asset-select">
                <option value="Low">Low (Green)</option>
                <option value="Medium" selected>Medium (Orange)</option>
                <option value="High">High (Red)</option>
                <option value="Critical">Critical (Dark Red)</option>
              </select>
            </div>
          </section>
        </div>

        <!-- Right Column -->
        <div class="form-column">
          <!-- 3. Schedule Timing -->
          <section>
            <h3 class="section-title">3. Schedule Timing</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="asset-form-group">
                <label class="asset-label">Start Date</label>
                <input type="date" name="scheduled_date" class="asset-input" value="<?php echo date('Y-m-d'); ?>"
                  required>
              </div>
              <div class="asset-form-group">
                <label class="asset-label">Due Date</label>
                <input type="date" name="due_date" class="asset-input"
                  value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
              </div>
            </div>

            <div style="margin-top: 20px;">
              <label class="type-option" style="font-weight: 600;">
                <input type="checkbox" name="repeat_schedule" value="1" id="repeatCheckbox"> Repeat Schedule?
              </label>
            </div>

            <div class="asset-form-group" style="margin-top: 16px; opacity: 0.5; pointer-events: none;"
              id="frequencyGroup">
              <label class="asset-label">Frequency</label>
              <select name="frequency" class="asset-select">
                <option value="Daily">Daily</option>
                <option value="Weekly">Weekly</option>
                <option value="Monthly" selected>Monthly</option>
                <option value="Yearly">Yearly</option>
              </select>
            </div>
          </section>

          <!-- 4. Responsibility & Status -->
          <section>
            <h3 class="section-title">4. Responsibility & Status</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="asset-form-group">
                <label class="asset-label">Assigned Staff</label>
                <select name="performed_by_user_id" class="asset-select">
                  <option value="">Select Staff...</option>
                  <?php foreach ($staff as $s): ?>
                    <option value="<?php echo $s['user_id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?>
                      (<?php echo htmlspecialchars($s['role_name']); ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="asset-form-group">
                <label class="asset-label">Status</label>
                <select name="status" class="asset-select">
                  <option value="Scheduled" selected>Scheduled</option>
                  <option value="Pending">Pending</option>
                  <option value="In Progress">In Progress</option>
                </select>
              </div>
            </div>
          </section>

          <!-- 5. Notes & Attachments -->
          <section>
            <h3 class="section-title">5. Notes & Attachments</h3>
            <div class="asset-form-group">
              <label class="asset-label">Notes</label>
              <input type="text" name="notes" class="asset-input" placeholder="Add any extra notes here...">
            </div>
            <div style="margin-top: 16px;">
              <button type="button" class="asset-btn asset-btn-secondary"
                style="width: 100%; justify-content: center; border-style: dashed;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                Upload File (Optional)
              </button>
            </div>
          </section>
        </div>
      </div>

      <div class="schedule-footer">
        <div class="footer-left">
          <button type="submit" class="btn-save-primary">Save Schedule</button>
          <button type="button" class="btn-outline" onclick="saveAndAddAnother()">Save & Add Another</button>
          <button type="button" class="btn-cancel" onclick="window.location.href='maintenance.php'">Cancel</button>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; color: var(--asset-muted);">
          <img src="../../assets/img/logo_placeholder.png" alt="SchoolSys Pro" style="height: 24px; opacity: 0.5;">
          <span style="font-size: 12px; font-weight: 600;">SchoolSys Pro</span>
        </div>
      </div>
      <input type="hidden" name="add_another" id="add_another" value="0">
    </form>
  </div>
</div>

<script>
  // Asset Search and Selection Logic
  const searchInput = document.getElementById('assetSearchInput');
  const resultsDiv = document.getElementById('assetSearchResults');
  const hiddenIdInput = document.getElementById('selected_asset_id');
  const searchItems = document.querySelectorAll('.asset-search-item');

  searchInput.addEventListener('input', function () {
    const filter = this.value.toLowerCase();
    let hasResults = false;

    if (filter.length > 0) {
      resultsDiv.style.display = 'block';
      searchItems.forEach(item => {
        const name = item.getAttribute('data-name').toLowerCase();
        const code = item.getAttribute('data-code').toLowerCase();
        if (name.includes(filter) || code.includes(filter)) {
          item.style.display = 'block';
          hasResults = true;
        } else {
          item.style.display = 'none';
        }
      });
      if (!hasResults) resultsDiv.style.display = 'none';
    } else {
      resultsDiv.style.display = 'none';
    }
  });

  searchItems.forEach(item => {
    item.addEventListener('click', function () {
      const id = this.getAttribute('data-id');
      const name = this.getAttribute('data-name');
      const code = this.getAttribute('data-code');

      searchInput.value = `${name} (${code})`;
      hiddenIdInput.value = id;
      resultsDiv.style.display = 'none';

      // Highlight selection
      searchInput.style.borderColor = 'var(--asset-primary)';
      searchInput.style.background = '#f0f9ff';
    });
  });

  // Close search results when clicking outside
  document.addEventListener('click', function (e) {
    if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
      resultsDiv.style.display = 'none';
    }
  });

  // Handle Repeat Schedule toggle
  document.getElementById('repeatCheckbox').addEventListener('change', function () {
    const group = document.getElementById('frequencyGroup');
    if (this.checked) {
      group.style.opacity = '1';
      group.style.pointerEvents = 'auto';
    } else {
      group.style.opacity = '0.5';
      group.style.pointerEvents = 'none';
    }
  });

  // Initialize display if pre-selected
  window.onload = function () {
    if (hiddenIdInput.value) {
      const selectedItem = document.querySelector(`.asset-search-item[data-id="${hiddenIdInput.value}"]`);
      if (selectedItem) {
        const name = selectedItem.getAttribute('data-name');
        const code = selectedItem.getAttribute('data-code');
        searchInput.value = `${name} (${code})`;
      }
    }
  };

  function saveAndAddAnother() {
    document.getElementById('add_another').value = '1';
    document.getElementById('scheduleForm').submit();
  }
</script>

<?php include '../../includes/footer.php'; ?>