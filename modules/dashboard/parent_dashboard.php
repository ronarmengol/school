<?php
// Fetch special messages
$msgs_res = mysqli_query($conn, "SELECT * FROM calendar_messages ORDER BY created_at DESC LIMIT 5");
?>
<h3>Parent Dashboard</h3>
<p>Welcome. Track your children's performance and fee status here.</p>

<div class="row">
    <div class="col-md-8">
        <div class="alert alert-info">
            <!-- Placeholder for student specific info -->
            Select a child to view detailed performance.
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card" style="box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: white;">
            <div class="card-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 15px;">
                <h5 style="margin:0; color: #334155; font-size: 16px; font-weight: 600;">School Updates & Reminders</h5>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if ($msgs_res && mysqli_num_rows($msgs_res) > 0): ?>
                    <ul class="list-group list-group-flush" style="margin: 0; padding: 0; list-style: none;">
                        <?php while($msg = mysqli_fetch_assoc($msgs_res)): ?>
                            <li class="list-group-item" style="padding: 15px; border-bottom: 1px solid #f1f5f9;">
                                <div style="font-size: 14px; color: #475569; margin-bottom: 6px; line-height: 1.4;">
                                    <?php echo htmlspecialchars($msg['message']); ?>
                                </div>
                                <div style="font-size: 11px; color: #94a3b8; display: flex; align-items: center; gap: 4px;">
                                    <i class="feather icon-clock"></i> 
                                    <?php echo date('d M Y', strtotime($msg['created_at'])); ?>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div style="padding: 30px; text-align: center; color: #94a3b8;">
                        <i class="feather icon-bell-off" style="font-size: 24px; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                        No new updates.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
