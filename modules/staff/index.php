<?php
require_once '../../includes/auth_functions.php';
check_auth();
check_role(['super_admin', 'admin']);

$page_title = "Staff Management";
include '../../includes/header.php';

// Fetch Staff (Teachers and Admins, exclude Super Admin maybe?)
$sql = "SELECT u.*, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        WHERE r.role_name IN ('admin', 'teacher', 'accountant') 
        ORDER BY u.full_name";
$result = mysqli_query($conn, $sql);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #1e293b; font-weight: 700;">Staff Management</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Manage and track all staff members, teachers, and administrators.
        </p>
    </div>
    <a href="add.php" class="btn btn-primary"
        style="display: inline-flex; align-items: center; gap: 8px; border-radius: 10px; padding: 10px 20px; font-weight: 600;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
            stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Add New Staff
    </a>
</div>

<div class="card card-premium" style="overflow: hidden; border: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
    <table class="table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 2px solid #f1f5f9;">
                <th
                    style="padding: 15px 25px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                    Staff Member</th>
                <th
                    style="padding: 15px 25px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                    Role</th>
                <th
                    style="padding: 15px 25px; text-align: left; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                    Contact info</th>
                <th
                    style="padding: 15px 25px; text-align: center; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                    Status</th>
                <th
                    style="padding: 15px 25px; text-align: right; color: #64748b; font-weight: 600; font-size: 13px; text-transform: uppercase;">
                    Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)):
                    $role_color = match ($row['role_name']) {
                        'admin' => '#6366f1',
                        'teacher' => '#0891b2',
                        'accountant' => '#8b5cf6',
                        default => '#64748b'
                    };
                    ?>
                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;"
                        onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                        <td style="padding: 18px 25px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div
                                    style="width: 40px; height: 40px; background: #f1f5f9; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #64748b; font-weight: 700; font-size: 16px;">
                                    <?php echo strtoupper(substr($row['full_name'] ?? '', 0, 1)); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: #1e293b;">
                                        <?php echo htmlspecialchars($row['full_name'] ?? ''); ?>
                                    </div>
                                    <div style="font-size: 12px; color: #94a3b8;">
                                        @<?php echo htmlspecialchars($row['username'] ?? ''); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 18px 25px;">
                            <span
                                style="background: <?php echo $role_color; ?>15; color: <?php echo $role_color; ?>; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: capitalize;">
                                <?php echo htmlspecialchars($row['role_name'] ?? ''); ?>
                            </span>
                        </td>
                        <td style="padding: 18px 25px;">
                            <div style="color: #475569; font-size: 14px;">
                                <?php echo htmlspecialchars(($row['email'] ?? '') ?: 'No email set'); ?>
                            </div>
                        </td>
                        <td style="padding: 18px 25px; text-align: center;">
                            <?php if ($row['is_active']): ?>
                                <span
                                    style="display: inline-flex; align-items: center; gap: 5px; color: #10b981; font-weight: 700; font-size: 12px;">
                                    <span style="width: 6px; height: 6px; background: #10b981; border-radius: 50%;"></span> Active
                                </span>
                            <?php else: ?>
                                <span
                                    style="display: inline-flex; align-items: center; gap: 5px; color: #ef4444; font-weight: 700; font-size: 12px;">
                                    <span style="width: 6px; height: 6px; background: #ef4444; border-radius: 50%;"></span> Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 18px 25px; text-align: right;">
                            <a href="edit.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-outline-primary"
                                style="padding: 5px 12px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                Edit Record
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="padding: 60px; text-align: center; color: #94a3b8;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 15px; opacity: 0.5;">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <div style="font-weight: 600;">No staff members found.</div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>