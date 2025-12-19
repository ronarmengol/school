<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

$plan_id = $_GET['id'] ?? null;
if (!$plan_id) {
  header("Location: lesson_plans_list.php");
  exit();
}

// Fetch lesson plan details
$sql = "SELECT lp.*, c.class_name, s.subject_name, u.full_name as teacher_name 
        FROM lesson_plans lp 
        JOIN classes c ON lp.class_id = c.class_id 
        JOIN subjects s ON lp.subject_id = s.subject_id 
        JOIN users u ON lp.teacher_id = u.user_id 
        WHERE lp.plan_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $plan_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$plan = mysqli_fetch_assoc($result);

if (!$plan) {
  echo "Lesson plan not found.";
  exit();
}

// Decode JSON fields
$teaching_methods = json_decode($plan['teaching_methods'] ?? '[]', true) ?: [];
$learner_activities = json_decode($plan['learner_activities'] ?? '[]', true) ?: [];
$teaching_aids = json_decode($plan['teaching_aids'] ?? '[]', true) ?: [];

$page_title = "View Lesson Plan";
include '../../includes/header.php';
?>

<style>
  /* Professional Academic Display Styling */
  :root {
    --academic-blue: #1e3a8a;
    --academic-slate: #334155;
    --academic-gray: #f8fafc;
    --border-light: #e2e8f0;
  }

  body {
    background-color: #f1f5f9;
    font-family: 'Inter', -apple-system, blinkmacsystemfont, 'Segoe UI', roboto, sans-serif;
  }

  .lesson-view-container {
    max-width: 900px;
    margin: 20px auto;
    background: white;
    padding: 50px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    color: var(--academic-slate);
    line-height: 1.6;
  }

  /* Print Header */
  .print-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px solid var(--academic-blue);
    padding-bottom: 20px;
    margin-bottom: 30px;
  }

  .school-info {
    display: flex;
    align-items: center;
    gap: 15px;
  }

  .school-logo {
    width: 60px;
    height: 60px;
    object-fit: contain;
  }

  .school-name {
    margin: 0;
    font-size: 22px;
    font-weight: 800;
    color: var(--academic-blue);
    text-transform: uppercase;
  }

  .document-title {
    text-align: right;
  }

  .document-title h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: var(--academic-blue);
  }

  .meta-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    background: var(--academic-gray);
    padding: 15px 20px;
    border-radius: 6px;
    margin-bottom: 30px;
    border: 1px solid var(--border-light);
  }

  .meta-item {
    display: flex;
    flex-direction: column;
  }

  .meta-label {
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 0.05em;
  }

  .meta-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--academic-slate);
  }

  /* Sections */
  .view-section {
    margin-bottom: 30px;
  }

  .view-section-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--academic-blue);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .view-section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border-light);
  }

  .content-box {
    border: 1px solid var(--border-light);
    border-radius: 6px;
    padding: 20px;
    background: #fff;
  }

  .focus-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 15px;
  }

  .objective-text {
    font-size: 15px;
    color: #334155;
    border-top: 1px dashed var(--border-light);
    padding-top: 15px;
    margin-top: 15px;
  }

  /* Time Blocks */
  .time-flex {
    display: flex;
    gap: 10px;
  }

  .time-block {
    flex: 1;
    background: var(--academic-gray);
    border: 1px solid var(--border-light);
    border-radius: 6px;
    padding: 12px;
    text-align: center;
  }

  .time-block .val {
    display: block;
    font-size: 18px;
    font-weight: 800;
    color: var(--academic-blue);
  }

  .time-block .lbl {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
  }

  /* Tags/Lists */
  .tag-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .tag {
    background: #eff6ff;
    color: #1d4ed8;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 500;
    border: 1px solid #dbeafe;
  }

  .list-bullets {
    margin: 0;
    padding-left: 20px;
    font-size: 14px;
  }

  .list-bullets li {
    margin-bottom: 5px;
  }

  .remarks-box {
    background: #fffbeb;
    border: 1px solid #fef3c7;
    color: #92400e;
    padding: 15px;
    border-radius: 4px;
    font-style: italic;
    font-size: 14px;
  }

  .back-nav {
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  @media print {
    .main-content {
      margin-left: 0 !important;
      padding: 0 !important;
    }

    .sidebar,
    .top-bar,
    .back-nav,
    .sidebar-nav,
    .user-info {
      display: none !important;
    }

    .lesson-view-container {
      box-shadow: none !important;
      margin: 0 !important;
      width: 100% !important;
      max-width: none !important;
      padding: 0 !important;
    }

    body {
      background: white !important;
    }
  }
</style>

<div class="back-nav">
  <a href="lesson_plans_list.php" class="btn btn-secondary" style="display: flex; align-items: center; gap: 8px;">
    <i class="fas fa-arrow-left"></i> Back to List
  </a>
  <button onclick="window.print()" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px;">
    <i class="fas fa-print"></i> Print Lesson Plan
  </button>
</div>

<div class="lesson-view-container">
  <!-- Top Header -->
  <header class="print-header">
    <div class="school-info">
      <?php
      $logo = get_setting('school_logo');
      if ($logo && file_exists(__DIR__ . '/../../uploads/' . $logo)): ?>
        <img src="<?php echo BASE_URL . 'uploads/' . $logo; ?>" class="school-logo">
      <?php endif; ?>
      <div>
        <h2 class="school-name"><?php echo get_setting('school_name', 'Sunrise School'); ?></h2>
        <p style="margin:0; font-size: 12px; color: #64748b;"><?php echo get_setting('school_motto'); ?></p>
      </div>
    </div>
    <div class="document-title">
      <h1>LESSON PLAN</h1>
      <p style="margin:0; font-size: 13px; font-weight: 600; color: #64748b;">Academic Session:
        <?php echo get_setting('current_year'); ?></p>
    </div>
  </header>

  <!-- Meta Details Inline -->
  <div class="meta-grid">
    <div class="meta-item">
      <span class="meta-label">Class</span>
      <span class="meta-value"><?php echo htmlspecialchars($plan['class_name']); ?></span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Subject</span>
      <span class="meta-value"><?php echo htmlspecialchars($plan['subject_name']); ?></span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Date</span>
      <span class="meta-value"><?php echo date('F d, Y', strtotime($plan['lesson_date'])); ?></span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Duration</span>
      <span class="meta-value"><?php echo htmlspecialchars($plan['duration']); ?></span>
    </div>
  </div>

  <!-- Lesson Details Section -->
  <div class="view-section">
    <h3 class="view-section-title">Lesson Overview</h3>
    <div class="content-box">
      <div class="focus-grid">
        <div>
          <label class="meta-label">Main Topic</label>
          <div style="font-size: 18px; font-weight: 700; color: var(--academic-blue);">
            <?php echo htmlspecialchars($plan['topic']); ?>
          </div>
        </div>
        <?php if ($plan['sub_topic']): ?>
          <div>
            <label class="meta-label">Sub-topic</label>
            <div style="font-size: 16px; font-weight: 600;">
              <?php echo htmlspecialchars($plan['sub_topic']); ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <div class="objective-text">
        <label class="meta-label" style="display:block; margin-bottom: 5px;">Learning Objectives</label>
        <?php echo htmlspecialchars($plan['objective']); ?>
      </div>
    </div>
  </div>

  <!-- Time Allocation Section -->
  <div class="view-section">
    <h3 class="view-section-title">Time Allocation</h3>
    <div class="time-flex">
      <div class="time-block">
        <span class="val"><?php echo $plan['intro_minutes']; ?>m</span>
        <span class="lbl">Introduction</span>
      </div>
      <div class="time-block">
        <span class="val"><?php echo $plan['teaching_minutes']; ?>m</span>
        <span class="lbl">Teaching</span>
      </div>
      <div class="time-block">
        <span class="val"><?php echo $plan['practice_minutes']; ?>m</span>
        <span class="lbl">Practice</span>
      </div>
      <div class="time-block">
        <span class="val"><?php echo $plan['assessment_minutes']; ?>m</span>
        <span class="lbl">Assessment</span>
      </div>
      <div class="time-block" style="background:var(--academic-blue); border-color:var(--academic-blue);">
        <span class="val" style="color:#fff;"><?php echo $plan['total_minutes']; ?>m</span>
        <span class="lbl" style="color: rgba(255,255,255,0.7);">Total Time</span>
      </div>
    </div>
  </div>

  <!-- Teaching & Learning Section -->
  <div class="focus-grid">
    <div class="view-section">
      <h3 class="view-section-title">Teaching Methods</h3>
      <div class="tag-container">
        <?php foreach ($teaching_methods as $method): ?>
          <span class="tag"><?php echo htmlspecialchars($method); ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="view-section">
      <h3 class="view-section-title">Teaching Aids</h3>
      <div class="tag-container" style="gap:5px;">
        <?php foreach ($teaching_aids as $aid): ?>
          <span class="tag" style="background:#f0fdf4; color:#166534; border-color:#dcfce7;">
            <?php echo htmlspecialchars($aid); ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="view-section">
    <h3 class="view-section-title">Learner Activities</h3>
    <ul class="list-bullets">
      <?php foreach ($learner_activities as $activity): ?>
        <li><?php echo htmlspecialchars($activity); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- Remarks / Evaluation Section -->
  <?php if ($plan['remarks']): ?>
    <div class="view-section">
      <h3 class="view-section-title">Teacher Remarks / Evaluation</h3>
      <div class="remarks-box">
        <?php echo nl2br(htmlspecialchars($plan['remarks'])); ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Signature Area for Printing -->
  <div style="margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px;" class="print-only">
    <div style="border-top: 1px solid #000; padding-top: 10px; text-align: center;">
      <p style="margin:0; font-weight: 700;"><?php echo htmlspecialchars($plan['teacher_name']); ?></p>
      <p style="margin:0; font-size: 11px; color: #64748b;">Subject Teacher</p>
    </div>
    <div style="border-top: 1px solid #000; padding-top: 10px; text-align: center;">
      <p style="margin:0; font-weight: 700;">__________________________</p>
      <p style="margin:0; font-size: 11px; color: #64748b;">H.O.D / Principal Signature</p>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>