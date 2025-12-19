<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

$page_title = "My Lesson Plans";
include '../../includes/header.php';
?>

<!-- Premium UI Libs -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  body {
    background-color: #f1f5f9;
  }

  .card-premium {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border: 1px solid #eee;
    padding: 40px !important;
  }

  .table thead th {
    background: #f8fafc;
    border-bottom: 2px solid #f1f5f9;
    text-transform: uppercase;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    padding: 15px;
  }

  .table td {
    padding: 15px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
  }
</style>

<?php
$teacher_id = $_SESSION['user_id'];
$is_admin = in_array($_SESSION['role'], ['admin', 'super_admin']);

// Fetch lesson plans
$sql = "SELECT lp.*, c.class_name, s.subject_name, u.full_name as teacher_name 
        FROM lesson_plans lp 
        JOIN classes c ON lp.class_id = c.class_id 
        JOIN subjects s ON lp.subject_id = s.subject_id 
        JOIN users u ON lp.teacher_id = u.user_id";

if (!$is_admin) {
  $sql .= " WHERE lp.teacher_id = $teacher_id";
}
$sql .= " ORDER BY lp.lesson_date DESC, lp.created_at DESC";

$plans = [];
$res = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($res)) {
  $plans[] = $row;
}
?>

<div class="card card-premium" style="padding: 30px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
      <h2 style="margin: 0; color: #1e293b;">Lesson Plans</h2>
      <p style="color: #64748b; margin: 5px 0 0;">Manage and view your school lesson plans.</p>
    </div>
    <a href="lesson_plans.php" class="btn btn-primary"
      style="display: flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 12px;">
      <i class="fas fa-plus"></i> Create New Plan
    </a>
  </div>

  <?php if (empty($plans)): ?>
    <div
      style="text-align: center; padding: 60px 20px; background: #f8fafc; border-radius: 20px; border: 2px dashed #e2e8f0;">
      <div style="font-size: 48px; color: #cbd5e1; margin-bottom: 20px;">
        <i class="fas fa-book-open"></i>
      </div>
      <h3 style="color: #475569; margin-bottom: 10px;">No Lesson Plans Found</h3>
      <p style="color: #94a3b8; max-width: 400px; margin: 0 auto 24px;">You haven't created any lesson plans yet. Start by
        creating your first lesson plan for your classes.</p>
      <a href="lesson_plans.php" class="btn btn-primary">Create Your First Plan</a>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <?php if ($is_admin): ?>
              <th>Teacher</th><?php endif; ?>
            <th>Date</th>
            <th>Class</th>
            <th>Subject</th>
            <th>Topic</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($plans as $plan): ?>
            <tr>
              <?php if ($is_admin): ?>
                <td><strong><?php echo htmlspecialchars($plan['teacher_name']); ?></strong></td>
              <?php endif; ?>
              <td><?php echo date('d M Y', strtotime($plan['lesson_date'])); ?></td>
              <td><span class="badge"
                  style="background: #eef2ff; color: #6366f1;"><?php echo htmlspecialchars($plan['class_name']); ?></span>
              </td>
              <td><?php echo htmlspecialchars($plan['subject_name']); ?></td>
              <td><?php echo htmlspecialchars($plan['topic']); ?></td>
              <td>
                <?php if ($plan['status'] == 'submitted'): ?>
                  <span class="badge" style="background: #ecfdf5; color: #10b981;">Submitted</span>
                <?php else: ?>
                  <span class="badge" style="background: #fffbeb; color: #f59e0b;">Draft</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display: flex; gap: 8px;">
                  <a href="view_lesson_plan.php?id=<?php echo $plan['plan_id']; ?>" class="btn btn-sm btn-outline-info"
                    title="View">
                    <i class="fas fa-eye"></i>
                  </a>
                  <a href="lesson_plans.php?id=<?php echo $plan['plan_id']; ?>" class="btn btn-sm btn-outline-primary"
                    title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                  <button class="btn btn-sm btn-outline-danger" onclick="deletePlan(<?php echo $plan['plan_id']; ?>)"
                    title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<script>
  function deletePlan(id) {
    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData();
        formData.append('action', 'delete_lesson_plan');
        formData.append('plan_id', id);

        fetch('lesson_plans_api.php', {
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              Swal.fire('Deleted!', data.message, 'success').then(() => {
                location.reload();
              });
            } else {
              Swal.fire('Error', data.message, 'error');
            }
          });
      }
    });
  }
</script>

<?php include '../../includes/footer.php'; ?>