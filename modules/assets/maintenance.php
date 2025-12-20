<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

if (get_setting('enable_asset_management', '0') == '0') {
  header("Location: ../dashboard/index.php");
  exit();
}

$page_title = "Maintenance Management - Asset Management";
include '../../includes/header.php';
include 'assets_styles.php';
include '../../includes/modals.php';

// Fetch maintenance records from database
$maintenance = [];
$maintenance_query = "
  SELECT 
    am.maintenance_id,
    am.task_description,
    am.scheduled_date,
    am.completed_date,
    am.priority,
    am.status,
    am.cost,
    am.performed_by,
    am.notes,
    a.asset_id,
    a.asset_code,
    a.asset_name,
    ac.category_name
  FROM asset_maintenance am
  INNER JOIN assets a ON am.asset_id = a.asset_id
  LEFT JOIN asset_categories ac ON a.category_id = ac.category_id
  ORDER BY 
    CASE am.priority
      WHEN 'Critical' THEN 1
      WHEN 'High' THEN 2
      WHEN 'Medium' THEN 3
      WHEN 'Low' THEN 4
    END,
    am.scheduled_date ASC
";

if ($maintenance_result = mysqli_query($conn, $maintenance_query)) {
  while ($row = mysqli_fetch_assoc($maintenance_result)) {
    $maintenance[] = $row;
  }
  mysqli_free_result($maintenance_result);
}

// Calculate statistics
$total_maintenance = count($maintenance);
$critical_high = 0;
$overdue = 0;
$completed = 0;

foreach ($maintenance as $m) {
  if ($m['priority'] == 'Critical' || $m['priority'] == 'High') {
    $critical_high++;
  }
  if ($m['status'] == 'Overdue') {
    $overdue++;
  }
  if ($m['status'] == 'Completed') {
    $completed++;
  }
}


?>

<div class="asset-module-wrap">
  <div class="asset-header">
    <div>
      <div class="breadcrumb">
        <a href="index.php">Asset Management</a>
        <span>&rarr;</span>
        <span>Maintenance</span>
      </div>
      <h1 class="asset-title">Maintenance Schedule</h1>
    </div>
    <div class="header-actions">
      <a href="schedule_maintenance.php" class="asset-btn asset-btn-primary">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        Schedule Maintenance
      </a>
    </div>
  </div>

  <?php include 'assets_header.php'; ?>

  <!-- Maintenance Summary KPIs -->
  <div class="kpi-grid" style="margin-bottom: 24px;">
    <div class="kpi-card">
      <span class="kpi-label">Total Tasks</span>
      <span class="kpi-value"><?php echo number_format($total_maintenance); ?></span>
      <div class="kpi-trend" style="color: var(--asset-muted);">
        <span>All maintenance records</span>
      </div>
      <svg class="kpi-icon-bg" width="80" height="80" fill="currentColor" viewBox="0 0 24 24">
        <path
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
      </svg>
    </div>
    <div class="kpi-card">
      <span class="kpi-label">Critical / High Priority</span>
      <span class="kpi-value" style="color: var(--asset-danger);"><?php echo number_format($critical_high); ?></span>
      <div class="kpi-trend" style="color: var(--asset-muted);">
        <span>Requires immediate attention</span>
      </div>
      <svg class="kpi-icon-bg" width="80" height="80" fill="currentColor" viewBox="0 0 24 24">
        <path fill-rule="evenodd"
          d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
          clip-rule="evenodd" />
      </svg>
    </div>
    <div class="kpi-card">
      <span class="kpi-label">Overdue</span>
      <span class="kpi-value" style="color: var(--asset-warning);"><?php echo number_format($overdue); ?></span>
      <div class="kpi-trend trend-down">
        <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
            clip-rule="evenodd" />
        </svg>
        <span>Past scheduled date</span>
      </div>
      <svg class="kpi-icon-bg" width="80" height="80" fill="currentColor" viewBox="0 0 24 24">
        <path fill-rule="evenodd"
          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
          clip-rule="evenodd" />
      </svg>
    </div>
    <div class="kpi-card">
      <span class="kpi-label">Completed</span>
      <span class="kpi-value" style="color: var(--asset-success);"><?php echo number_format($completed); ?></span>
      <div class="kpi-trend trend-up">
        <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
            clip-rule="evenodd" />
        </svg>
        <span>Successfully finished</span>
      </div>
      <svg class="kpi-icon-bg" width="80" height="80" fill="currentColor" viewBox="0 0 24 24">
        <path fill-rule="evenodd"
          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
          clip-rule="evenodd" />
      </svg>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: 1fr 300px; gap: 24px;">
    <div class="asset-card">
      <div style="padding: 20px 24px; border-bottom: 1px solid var(--asset-border); background: #fafafa;">
        <h3 class="asset-title" style="font-size: 18px;">Maintenance Logs</h3>
      </div>
      <table class="asset-table">
        <thead>
          <tr>
            <th>Asset</th>
            <th>Task / Service</th>
            <th>Scheduled Date</th>
            <th>Priority</th>
            <th>Status</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($maintenance)): ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 60px 24px; color: var(--asset-muted);">
                <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  style="margin: 0 auto 16px; opacity: 0.3;">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">No Maintenance Records</div>
                <div style="font-size: 14px;">Schedule maintenance tasks to keep your assets in optimal condition.</div>
              </td>
            </tr>
          <?php else:
            foreach ($maintenance as $m): ?>
              <tr>
                <td style="font-weight: 700;">
                  <div style="font-family: monospace; color: var(--asset-primary); font-size: 12px; margin-bottom: 4px;">
                    <?php echo htmlspecialchars($m['asset_code']); ?>
                  </div>
                  <div style="font-size: 14px; font-weight: 600;">
                    <?php echo htmlspecialchars($m['asset_name']); ?>
                  </div>
                  <?php if (!empty($m['category_name'])): ?>
                    <div style="font-size: 12px; color: var(--asset-muted); margin-top: 2px;">
                      <?php echo htmlspecialchars($m['category_name']); ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td style="font-size: 13px;"><?php echo htmlspecialchars($m['task_description']); ?></td>
                <td style="font-weight: 600; color: var(--asset-muted);">
                  <?php echo date('d/m/Y', strtotime($m['scheduled_date'])); ?>
                </td>
                <td>
                  <?php
                  $p_class = 'prio-' . strtolower($m['priority']);
                  ?>
                  <span class="prio-badge <?php echo $p_class; ?>">
                    <?php echo htmlspecialchars($m['priority']); ?>
                  </span>
                </td>
                <td>
                  <?php
                  $s_style = 'background: #f1f5f9; color: #64748b;';
                  if ($m['status'] == 'Overdue')
                    $s_style = 'background: #fee2e2; color: #b91c1c;';
                  if ($m['status'] == 'Completed')
                    $s_style = 'background: #dcfce7; color: #15803d;';
                  if ($m['status'] == 'Scheduled')
                    $s_style = 'background: #dbeafe; color: #1e40af;';
                  if ($m['status'] == 'In Progress')
                    $s_style = 'background: #fef3c7; color: #92400e;';
                  ?>
                  <span
                    style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px; <?php echo $s_style; ?>">
                    <?php echo htmlspecialchars($m['status']); ?>
                  </span>
                </td>
                <td style="text-align: right;">
                  <?php if ($m['status'] !== 'Completed'): ?>
                    <button class="asset-btn asset-btn-primary mark-done-btn" style="padding: 6px 12px; font-size: 12px;"
                      data-id="<?php echo $m['maintenance_id']; ?>">
                      Mark Done
                    </button>
                  <?php else: ?>
                    <span
                      style="color: var(--asset-success); font-weight: 700; font-size: 12px; display: flex; align-items: center; justify-content: flex-end; gap: 4px;">
                      <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clip-rule="evenodd" />
                      </svg>
                      Done
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach;
          endif; ?>
        </tbody>
      </table>
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px;">
      <div class="asset-card" style="padding: 24px;">
        <h4 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 700; color: var(--asset-text);">Maintenance
          Calendar
        </h4>
        <div id="maintenanceCalendar"></div>
        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--asset-border);">
          <div style="display: flex; flex-direction: column; gap: 8px; font-size: 11px;">
            <div style="display: flex; align-items: center; gap: 6px;">
              <div style="width: 8px; height: 8px; border-radius: 50%; background: #dc2626;"></div>
              <span style="color: var(--asset-muted);">Critical/High</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
              <div style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b;"></div>
              <span style="color: var(--asset-muted);">Medium</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
              <div style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></div>
              <span style="color: var(--asset-muted);">Low</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .calendar-widget {
    width: 100%;
  }

  .calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--asset-border);
  }

  .calendar-month {
    font-size: 14px;
    font-weight: 700;
    color: var(--asset-text);
  }

  .calendar-nav {
    display: flex;
    gap: 8px;
  }

  .calendar-nav-btn {
    width: 28px;
    height: 28px;
    border: 1px solid var(--asset-border);
    background: white;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
  }

  .calendar-nav-btn:hover {
    background: var(--asset-primary);
    border-color: var(--asset-primary);
    color: white;
  }

  .calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
  }

  .calendar-day-header {
    text-align: center;
    font-size: 10px;
    font-weight: 700;
    color: var(--asset-muted);
    padding: 8px 4px;
    text-transform: uppercase;
  }

  .calendar-day {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    position: relative;
    transition: all 0.2s;
    background: #fafafa;
    border: 1px solid transparent;
  }

  .calendar-day:hover {
    background: #f0f9ff;
    border-color: var(--asset-primary);
  }

  .calendar-day.other-month {
    color: #cbd5e1;
    background: transparent;
  }

  .calendar-day.today {
    background: var(--asset-primary);
    color: white;
    font-weight: 800;
  }

  .calendar-day.has-task {
    border: 2px solid;
  }

  .calendar-day.has-task.priority-critical,
  .calendar-day.has-task.priority-high {
    border-color: #dc2626;
    background: #fef2f2;
    color: #1f2937;
  }

  .calendar-day.has-task.priority-medium {
    border-color: #f59e0b;
    background: #fffbeb;
    color: #1f2937;
  }

  .calendar-day.has-task.priority-low {
    border-color: #10b981;
    background: #f0fdf4;
    color: #1f2937;
  }

  .calendar-day-dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    position: absolute;
    bottom: 4px;
  }
</style>

<script>
  // Calendar Widget Implementation
  (function () {
    const maintenanceData = <?php echo json_encode($maintenance); ?>;
    let currentDate = new Date();

    function renderCalendar() {
      const year = currentDate.getFullYear();
      const month = currentDate.getMonth();

      const firstDay = new Date(year, month, 1);
      const lastDay = new Date(year, month + 1, 0);
      const prevLastDay = new Date(year, month, 0);

      const firstDayOfWeek = firstDay.getDay();
      const lastDate = lastDay.getDate();
      const prevLastDate = prevLastDay.getDate();

      const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];

      let html = '<div class="calendar-widget">';

      // Header
      html += '<div class="calendar-header">';
      html += `<div class="calendar-month">${monthNames[month]} ${year}</div>`;
      html += '<div class="calendar-nav">';
      html += '<button class="calendar-nav-btn" onclick="changeMonth(-1)">‹</button>';
      html += '<button class="calendar-nav-btn" onclick="changeMonth(1)">›</button>';
      html += '</div>';
      html += '</div>';

      // Grid
      html += '<div class="calendar-grid">';

      // Day headers
      const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
      dayHeaders.forEach(day => {
        html += `<div class="calendar-day-header">${day}</div>`;
      });

      // Previous month days
      for (let i = firstDayOfWeek - 1; i >= 0; i--) {
        html += `<div class="calendar-day other-month">${prevLastDate - i}</div>`;
      }

      // Current month days
      const today = new Date();
      for (let day = 1; day <= lastDate; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const isToday = today.getDate() === day && today.getMonth() === month && today.getFullYear() === year;

        // Check for maintenance tasks on this day
        const tasksOnDay = maintenanceData.filter(m => m.scheduled_date === dateStr);
        let dayClass = 'calendar-day';

        if (isToday) dayClass += ' today';

        if (tasksOnDay.length > 0) {
          const highestPriority = getHighestPriority(tasksOnDay);
          dayClass += ` has-task priority-${highestPriority.toLowerCase()}`;
        }

        html += `<div class="${dayClass}" title="${tasksOnDay.length > 0 ? tasksOnDay.length + ' task(s)' : ''}">${day}</div>`;
      }

      // Next month days
      const remainingCells = 42 - (firstDayOfWeek + lastDate);
      for (let day = 1; day <= remainingCells; day++) {
        html += `<div class="calendar-day other-month">${day}</div>`;
      }

      html += '</div>';
      html += '</div>';

      document.getElementById('maintenanceCalendar').innerHTML = html;
    }

    function getHighestPriority(tasks) {
      const priorities = ['Critical', 'High', 'Medium', 'Low'];
      for (const priority of priorities) {
        if (tasks.some(t => t.priority === priority)) {
          return priority;
        }
      }
      return 'Low';
    }

    window.changeMonth = function (delta) {
      currentDate.setMonth(currentDate.getMonth() + delta);
      renderCalendar();
    };

    // Initial render
    renderCalendar();
  })();
</script>

<script>
  document.querySelectorAll('.mark-done-btn').forEach(button => {
    button.addEventListener('click', function () {
      const maintenanceId = this.getAttribute('data-id');
      const btn = this;

      UniversalModal.confirm(
        'Complete maintenance task',
        'Are you sure you want to mark this task as completed?',
        function () {
          btn.disabled = true;
          btn.textContent = 'Updating...';

          fetch('complete_maintenance.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ maintenance_id: maintenanceId })
          })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                showToastSuccess(data.message);
                setTimeout(() => window.location.reload(), 800);
              } else {
                showToastError(data.message);
                btn.disabled = false;
                btn.textContent = 'Mark Done';
              }
            })
            .catch(error => {
              console.error('Error:', error);
              showToastError('An error occurred. Please try again.');
              btn.disabled = false;
              btn.textContent = 'Mark Done';
            });
        }
      );
    });
  });
</script>

<?php include '../../includes/footer.php'; ?>