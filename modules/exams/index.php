<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin', 'teacher']);

$page_title = "Manage Exams";
include '../../includes/header.php';

$sql = "SELECT e.*, t.term_name, y.year_name 
        FROM exams e 
        JOIN terms t ON e.term_id = t.term_id 
        JOIN academic_years y ON t.academic_year_id = y.year_id 
        ORDER BY e.start_date DESC";
$result = mysqli_query($conn, $sql);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #1e293b; font-weight: 700;">Examinations</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Manage and schedule school exams.</p>
    </div>
    <?php if (in_array($_SESSION['role'], ['super_admin', 'admin'])): ?>
        <a href="create.php" class="btn btn-primary"
            style="display: flex; align-items: center; gap: 8px; border-radius: 10px; padding: 10px 20px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Create New Exam
        </a>
    <?php endif; ?>
</div>

<div class="card card-premium" style="overflow: hidden;">
    <table class="table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 2px solid #f1f5f9;">
                <th
                    style="padding: 15px 20px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                    Exam Name</th>
                <th
                    style="padding: 15px 20px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                    Grade</th>
                <th
                    style="padding: 15px 20px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                    Term</th>
                <th
                    style="padding: 15px 20px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                    Start Date</th>
                <th
                    style="padding: 15px 20px; text-align: right; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                    Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;"
                        onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                        <td style="padding: 15px 20px;">
                            <div style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($row['exam_name']); ?>
                            </div>
                        </td>
                        <td style="padding: 15px 20px;">
                            <span
                                style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700;">
                                <?php echo htmlspecialchars($row['class_name'] ?: 'All Grades'); ?>
                            </span>
                        </td>
                        <td style="padding: 15px 20px;">
                            <span
                                style="background: #eff6ff; color: #3b82f6; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                <?php echo htmlspecialchars($row['term_name']); ?>
                            </span>
                        </td>
                        <td style="padding: 15px 20px; color: #64748b;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #94a3b8;">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <?php echo date('d M Y', strtotime($row['start_date'])); ?>
                            </div>
                        </td>
                        <td style="padding: 15px 20px; text-align: right;">
                            <a href="enter_marks.php?exam_id=<?php echo $row['exam_id']; ?>" class="btn btn-primary btn-sm"
                                style="display: inline-flex; align-items: center; gap: 6px; border-radius: 8px; font-size: 12px; padding: 6px 15px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path>
                                </svg>
                                Enter Marks
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="padding: 40px; text-align: center; color: #94a3b8;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 15px; opacity: 0.5;">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <p style="margin: 0;">No examinations found in the system.</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
include '../../includes/modals.php';
include '../../includes/footer.php';
?>