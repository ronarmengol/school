<?php
require_once '../../includes/auth_functions.php';
require_once '../../includes/load_settings.php';
check_auth();
check_role(['super_admin', 'admin']);

$page_title = "Archived School Notes";
include '../../includes/header.php';

// Fetch all classes for the filter
$classes_sql = "SELECT class_id, class_name, section_name FROM classes ORDER BY class_name";
$classes_result = mysqli_query($conn, $classes_sql);
?>

<style>
  .archived-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 20px;
  }

  .page-header {
    background: white;
    padding: 32px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid #e2e8f0;
  }

  .page-header h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.02em;
  }

  .filter-section {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    display: flex;
    gap: 16px;
    align-items: center;
  }

  .notes-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
  }

  .note-card {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
  }

  .note-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
  }

  .note-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
  }

  .note-title {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 8px 0;
  }

  .note-meta {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 16px;
  }

  .badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    border: 1px solid transparent;
  }

  .badge-priority-urgent {
    background: #fee2e2;
    color: #dc2626;
    border-color: #fecaca;
  }

  .badge-priority-high {
    background: #fef3c7;
    color: #d97706;
    border-color: #fde68a;
  }

  .badge-priority-medium {
    background: #dbeafe;
    color: #2563eb;
    border-color: #bfdbfe;
  }

  .badge-priority-low {
    background: #f3f4f6;
    color: #6b7280;
    border-color: #e5e7eb;
  }

  .note-content {
    color: #475569;
    font-size: 15px;
    line-height: 1.6;
    white-space: pre-wrap;
  }

  .note-footer {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    color: #94a3b8;
  }

  .btn {
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .btn-outline {
    background: white;
    border-color: #e2e8f0;
    color: #64748b;
  }

  .btn-outline:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #1e293b;
  }

  .empty-state {
    text-align: center;
    padding: 80px 40px;
    color: #94a3b8;
  }
</style>

<div class="archived-container">
  <div class="page-header">
    <div>
      <h1>Archived School Notes</h1>
      <p style="color: #64748b; margin: 4px 0 0 0;">History of resolved and closed school communication</p>
    </div>
    <a href="../dashboard/index.php" class="btn btn-outline">
      <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      Back to Dashboard
    </a>
  </div>

  <div class="filter-section">
    <div style="flex: 1;">
      <label
        style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px; text-transform: uppercase;">Filter
        by Class</label>
      <select id="class-filter" class="form-control" onchange="loadArchivedNotes()"
        style="width: 100%; max-width: 300px;">
        <option value="">All Classes</option>
        <?php while ($class = mysqli_fetch_assoc($classes_result)): ?>
          <option value="<?php echo $class['class_id']; ?>">
            <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section_name']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
  </div>

  <div id="archived-notes-list" class="notes-grid">
    <!-- Notes will be loaded here -->
    <div class="empty-state">
      <div class="loading-shimmer" style="width: 100%; height: 20px; margin-bottom: 12px;"></div>
      <div class="loading-shimmer" style="width: 80%; height: 20px; margin: 0 auto;"></div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', loadArchivedNotes);

  function loadArchivedNotes() {
    const classId = document.getElementById('class-filter').value;
    const container = document.getElementById('archived-notes-list');

    container.innerHTML = `
        <div style="text-align: center; padding: 60px;">
            <p style="color: #94a3b8;">Loading archived notes...</p>
        </div>
    `;

    fetch(`notes_api.php?action=get_notes&status=Closed&class_id=${classId}`)
      .then(response => response.json())
      .then(data => {
        if (data.success && data.notes.length > 0) {
          let html = '';
          data.notes.forEach(note => {
            const priorityClass = `badge-priority-${note.priority.toLowerCase()}`;
            const date = new Date(note.created_at).toLocaleDateString('en-GB', {
              day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
            });

            html += `
                        <div class="note-card">
                            <div class="note-header">
                                <div style="flex: 1;">
                                    <div class="note-meta">
                                        <span class="badge ${priorityClass}">${note.priority}</span>
                                        <span class="badge" style="background: #f1f5f9; color: #64748b;">${note.category}</span>
                                        <span class="badge" style="background: #f3f4f6; color: #6b7280;">Closed</span>
                                    </div>
                                    <h2 class="note-title">${note.title}</h2>
                                </div>
                            </div>
                            <div class="note-content">${note.note_content}</div>
                            <div class="note-footer">
                                <div>
                                    <span style="font-weight: 600; color: #64748b;">Created by:</span> ${note.created_by_name || 'System'} • ${date}
                                </div>
                                <div>
                                    ${note.student_first_name ? `Student: <span style="font-weight: 600; color: #475569;">${note.student_first_name} ${note.student_last_name}</span>` : `Class: <span style="font-weight: 600; color: #475569;">${note.class_name || 'N/A'}</span>`}
                                </div>
                            </div>
                        </div>
                    `;
          });
          container.innerHTML = html;
        } else {
          container.innerHTML = `
                    <div class="empty-state">
                        <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom: 16px; opacity: 0.5;">
                            <path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #64748b;">No archived notes found</h3>
                        <p style="margin: 8px 0 0 0;">When notes are closed, they will appear here.</p>
                    </div>
                `;
        }
      });
  }
</script>

<?php include '../../includes/footer.php'; ?>