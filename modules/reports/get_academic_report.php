<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

// Filter inputs
$selected_exam = $_GET['exam_id'] ?? '';
$selected_class = $_GET['class_id'] ?? '';

if (!$selected_exam || !$selected_class) {
  echo '<div class="empty-state" style="padding: 80px 24px; text-align: center;">
            <div style="font-size: 18px; font-weight: 600; color: #64748b; margin-bottom: 8px;">No Selection</div>
            <div style="font-size: 14px; color: #94a3b8;">Please select both exam and class to generate the report</div>
          </div>';
  exit;
}

// Get exam and class details
$exam_name = '';
$class_name = '';
$term_name = '';
$year_name = '';

$exam_res = mysqli_query($conn, "SELECT e.exam_name, t.term_name, ay.year_name 
                                  FROM exams e
                                  LEFT JOIN terms t ON e.term_id = t.term_id
                                  LEFT JOIN academic_years ay ON t.academic_year_id = ay.year_id
                                  WHERE e.exam_id = $selected_exam");
if ($exam_row = mysqli_fetch_assoc($exam_res)) {
  $exam_name = $exam_row['exam_name'];
  $term_name = $exam_row['term_name'] ?? '';
  $year_name = $exam_row['year_name'] ?? '';
}

$class_res = mysqli_query($conn, "SELECT class_name, section_name FROM classes WHERE class_id = $selected_class");
if ($class_row = mysqli_fetch_assoc($class_res)) {
  $class_name = $class_row['class_name'] . ' ' . $class_row['section_name'];
}

$report_data = [];
$subjects = [];

// Get Subjects
$sub_sql = "SELECT subject_name FROM subjects ORDER BY subject_name";
$sub_res = mysqli_query($conn, $sub_sql);
while ($s = mysqli_fetch_assoc($sub_res)) {
  $subjects[] = $s['subject_name'];
}

// Get Student Marks
$sql_marks = "SELECT
    s.student_id,
    s.first_name,
    s.last_name,
    sub.subject_name,
    em.marks_obtained
FROM students s
CROSS JOIN subjects sub
LEFT JOIN exam_results em ON s.student_id = em.student_id AND sub.subject_id = em.subject_id AND em.exam_id = $selected_exam
WHERE s.current_class_id = $selected_class
ORDER BY s.first_name, s.last_name, sub.subject_name";

$res_marks = mysqli_query($conn, $sql_marks);

// Process into pivots
while ($row = mysqli_fetch_assoc($res_marks)) {
  $sid = $row['student_id'];
  if (!isset($report_data[$sid])) {
    $report_data[$sid] = [
      'name' => $row['first_name'] . ' ' . $row['last_name'],
      'marks' => [],
      'total' => 0,
      'count' => 0
    ];
  }
  $marks = is_numeric($row['marks_obtained']) ? floatval($row['marks_obtained']) : 0;
  $report_data[$sid]['marks'][$row['subject_name']] = $row['marks_obtained'];

  if (is_numeric($row['marks_obtained'])) {
    $report_data[$sid]['total'] += $marks;
    $report_data[$sid]['count']++;
  }
}

// Calculate Average
foreach ($report_data as $sid => &$data) {
  $data['average'] = $data['count'] > 0 ? round($data['total'] / $data['count'], 1) : 0;
}
unset($data);

// Sort by Total Descending
uasort($report_data, function ($a, $b) {
  return $b['total'] <=> $a['total'];
});
?>

<!-- Report Card -->
<div class="report-card">
  <div class="report-header">
    <div class="report-title-section">
      <h2 class="report-title">Performance Analysis</h2>
      <div class="report-meta">
        <?php if ($year_name): ?>
          <?php echo htmlspecialchars($year_name); ?>
          <?php if ($term_name): ?>
            • <?php echo htmlspecialchars($term_name); ?>
          <?php endif; ?>
          •
        <?php endif; ?>
        <?php echo htmlspecialchars($exam_name); ?> • <?php echo htmlspecialchars($class_name); ?> •
        <?php echo count($report_data); ?> Students
      </div>
    </div>
    <button onclick="window.print()" class="btn-print no-print">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="6 9 6 2 18 2 18 9"></polyline>
        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
        <rect x="6" y="14" width="12" height="8"></rect>
      </svg>
      Print Report
    </button>
  </div>

  <div class="table-container">
    <?php if (!empty($report_data)): ?>
      <table class="premium-report-table">
        <thead>
          <tr>
            <th class="text-left">Rank</th>
            <th class="text-left">Student Name</th>
            <?php foreach ($subjects as $sub): ?>
              <th title="<?php echo htmlspecialchars($sub); ?>">
                <?php echo htmlspecialchars(substr($sub, 0, 3)); ?>
              </th>
            <?php endforeach; ?>
            <th>Total</th>
            <th>Average</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $rank = 1;
          foreach ($report_data as $student):
            $rank_class = 'rank-other';
            if ($rank == 1)
              $rank_class = 'rank-1';
            elseif ($rank == 2)
              $rank_class = 'rank-2';
            elseif ($rank == 3)
              $rank_class = 'rank-3';
            ?>
            <tr>
              <td class="text-left rank-cell">
                <span class="rank-badge <?php echo $rank_class; ?>">
                  <?php echo $rank; ?>
                </span>
              </td>
              <td class="text-left name-cell">
                <?php echo htmlspecialchars($student['name']); ?>
              </td>
              <?php foreach ($subjects as $sub): ?>
                <td>
                  <?php
                  $m = $student['marks'][$sub] ?? '-';
                  echo $m !== '-' ? $m : '<span style="color: #cbd5e1;">—</span>';
                  ?>
                </td>
              <?php endforeach; ?>
              <td class="total-cell"><?php echo $student['total']; ?></td>
              <td class="avg-cell"><?php echo $student['average']; ?>%</td>
            </tr>
            <?php
            $rank++;
          endforeach;
          ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="empty-state">
        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
          </path>
        </svg>
        <div class="empty-title">No Data Available</div>
        <div class="empty-text">No marks have been entered for this exam and class combination</div>
      </div>
    <?php endif; ?>
  </div>
</div>