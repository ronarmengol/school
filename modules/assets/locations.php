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

// Fetch locations from database
$locations_query = "SELECT 
    al.location_id,
    al.location_name,
    al.location_type,
    al.description,
    COUNT(DISTINCT a.asset_id) as asset_count
FROM asset_locations al
LEFT JOIN assets a ON al.location_id = a.location_id
GROUP BY al.location_id
ORDER BY al.location_name ASC";

$locations_result = $conn->query($locations_query);
$locations = [];
if ($locations_result && $locations_result->num_rows > 0) {
  while ($row = $locations_result->fetch_assoc()) {
    $locations[] = $row;
  }
}

$page_title = "Locations & Rooms - Asset Management";
include '../../includes/header.php';
include 'assets_styles.php';
?>

<div class="asset-module-wrap">
  <div class="asset-header">
    <div>
      <div class="breadcrumb">
        <a href="index.php">Asset Management</a>
        <span>&rarr;</span>
        <span>Locations</span>
      </div>
      <h1 class="asset-title">Locations & Rooms</h1>
    </div>
    <div class="header-actions">
      <button class="asset-btn asset-btn-primary" onclick="openAddLocationModal()">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        Add Location
      </button>
    </div>
  </div>

  <?php include 'assets_header.php'; ?>

  <div class="asset-card">
    <table class="asset-table">
      <thead>
        <tr>
          <th>Location Name</th>
          <th>Type</th>
          <th>Description</th>
          <th>Asset Count</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody id="locationsTableBody">
        <?php if (empty($locations)): ?>
          <tr>
            <td colspan="5" style="text-align: center; padding: 60px 24px; color: var(--asset-muted);">
              <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                style="margin: 0 auto 16px; opacity: 0.3;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">No Locations Found</div>
              <div style="font-size: 14px;">Add locations to organize your assets by building, room, or department.</div>
            </td>
          </tr>
        <?php else:
          foreach ($locations as $loc): ?>
            <tr>
              <td>
                <div style="font-weight: 700; color: var(--asset-text);">
                  <?php echo htmlspecialchars($loc['location_name']); ?>
                </div>
              </td>
              <td>
                <?php if (!empty($loc['location_type'])): ?>
                  <span
                    style="font-size: 11px; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 4px 10px; border-radius: 6px; text-transform: uppercase;">
                    <?php echo htmlspecialchars($loc['location_type']); ?>
                  </span>
                <?php else: ?>
                  <span style="color: var(--asset-muted); font-size: 13px;">-</span>
                <?php endif; ?>
              </td>
              <td style="font-size: 13px; color: var(--asset-muted);">
                <?php echo htmlspecialchars($loc['description'] ?: '-'); ?>
              </td>
              <td style="font-weight: 700; color: var(--asset-primary);"><?php echo $loc['asset_count']; ?></td>
              <td style="text-align: right;">
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                  <button
                    onclick="editLocation(<?php echo $loc['location_id']; ?>, '<?php echo addslashes($loc['location_name']); ?>', '<?php echo addslashes($loc['location_type'] ?? ''); ?>', '<?php echo addslashes($loc['description'] ?? ''); ?>')"
                    class="asset-btn-icon"
                    style="padding: 8px; border: 1px solid var(--asset-border); background: white; border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                    title="Edit Location"
                    onmouseover="this.style.background='#eff6ff'; this.style.borderColor='var(--asset-primary)';"
                    onmouseout="this.style.background='white'; this.style.borderColor='var(--asset-border)';">
                    <svg width="16" height="16" fill="none" stroke="var(--asset-primary)" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    onclick="deleteLocation(<?php echo $loc['location_id']; ?>, '<?php echo addslashes($loc['location_name']); ?>')"
                    class="asset-btn-icon"
                    style="padding: 8px; border: 1px solid var(--asset-border); background: white; border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                    title="Archive Location"
                    onmouseover="this.style.background='#f8fafc'; this.style.borderColor='var(--asset-muted)';"
                    onmouseout="this.style.background='white'; this.style.borderColor='var(--asset-border)';">
                    <svg width="16" height="16" fill="none" stroke="var(--asset-muted)" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach;
        endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Location Modal -->
<div id="addLocationModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 600px;">
    <div class="modal-header">
      <h3 style="margin: 0; font-size: 20px; font-weight: 800;">Add New Location</h3>
      <button onclick="closeAddLocationModal()"
        style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--asset-muted);">&times;</button>
    </div>
    <form id="addLocationForm" onsubmit="submitLocation(event)">
      <div class="modal-body" style="padding: 24px;">
        <div class="asset-form-group">
          <label class="asset-label">Location Name <span style="color: #ef4444;">*</span></label>
          <input type="text" name="location_name" class="asset-input" placeholder="e.g. Computer Lab A" required>
        </div>
        <div class="asset-form-group">
          <label class="asset-label">Location Type (Optional)</label>
          <input type="text" name="location_type" class="asset-input"
            placeholder="e.g. Building, Room, Department, Area">
        </div>
        <div class="asset-form-group" style="margin-bottom: 0;">
          <label class="asset-label">Description (Optional)</label>
          <textarea name="description" class="asset-textarea" rows="3"
            placeholder="Brief description of this location..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="asset-btn asset-btn-secondary" onclick="closeAddLocationModal()">Cancel</button>
        <button type="submit" class="asset-btn asset-btn-primary">Save Location</button>
      </div>
    </form>
  </div>
</div>
</div>

<!-- Edit Location Modal -->
<div id="editLocationModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 600px;">
    <div class="modal-header">
      <h3 style="margin: 0; font-size: 20px; font-weight: 800;">Edit Location</h3>
      <button onclick="closeEditLocationModal()"
        style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--asset-muted);">&times;</button>
    </div>
    <form id="editLocationForm" onsubmit="submitEditLocation(event)">
      <input type="hidden" name="location_id" id="edit_location_id">
      <div class="modal-body" style="padding: 24px;">
        <div class="asset-form-group">
          <label class="asset-label">Location Name <span style="color: #ef4444;">*</span></label>
          <input type="text" name="location_name" id="edit_location_name" class="asset-input"
            placeholder="e.g. Computer Lab A" required>
        </div>
        <div class="asset-form-group">
          <label class="asset-label">Location Type (Optional)</label>
          <input type="text" name="location_type" id="edit_location_type" class="asset-input"
            placeholder="e.g. Building, Room, Department, Area">
        </div>
        <div class="asset-form-group" style="margin-bottom: 0;">
          <label class="asset-label">Description (Optional)</label>
          <textarea name="description" id="edit_description" class="asset-textarea" rows="3"
            placeholder="Brief description of this location..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="asset-btn asset-btn-secondary" onclick="closeEditLocationModal()">Cancel</button>
        <button type="submit" class="asset-btn asset-btn-primary">Update Location</button>
      </div>
    </form>
  </div>
</div>
</div>

<!-- Confirmation Dialog Modal -->
<div id="confirmDialog" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 480px;">
    <div class="modal-header">
      <h3 style="margin: 0; font-size: 20px; font-weight: 800; color: var(--asset-danger);">Confirm Deletion</h3>
      <button onclick="closeConfirmDialog()"
        style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--asset-muted);">&times;</button>
    </div>
    <div class="modal-body" style="padding: 24px;">
      <div style="display: flex; gap: 16px; align-items: start;">
        <div
          style="flex-shrink: 0; width: 48px; height: 48px; border-radius: 12px; background: #fef2f2; display: flex; align-items: center; justify-content: center;">
          <svg width="24" height="24" fill="none" stroke="var(--asset-danger)" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div style="flex: 1;">
          <p style="margin: 0 0 12px 0; font-size: 15px; font-weight: 600; color: var(--asset-text);">
            Are you sure you want to delete this location?
          </p>
          <p id="confirmLocationName"
            style="margin: 0 0 12px 0; font-size: 14px; color: var(--asset-muted); padding: 12px; background: #f8fafc; border-radius: 8px; font-weight: 600;">
          </p>
          <p style="margin: 0; font-size: 13px; color: var(--asset-danger);">
            ⚠️ This action cannot be undone.
          </p>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="asset-btn asset-btn-secondary" onclick="closeConfirmDialog()">Cancel</button>
      <button type="button" class="asset-btn" id="confirmDeleteBtn"
        style="background: var(--asset-danger); color: white; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);"
        onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='var(--asset-danger)'">
        Delete Location
      </button>
    </div>
  </div>
</div>

<script>
  function openAddLocationModal() {
    document.getElementById('addLocationModal').style.display = 'flex';
    document.getElementById('addLocationForm').reset();
  }

  function closeAddLocationModal() {
    document.getElementById('addLocationModal').style.display = 'none';
  }

  function submitLocation(event) {
    event.preventDefault();

    const formData = new FormData(event.target);

    fetch('save_location.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showToast(data.message || 'Location added successfully!', 'success');
          closeAddLocationModal();
          setTimeout(() => window.location.reload(), 1000);
        } else {
          showToast(data.message || 'Failed to add location', 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
      });
  }

  // Edit location function
  function editLocation(locationId, locationName, locationType, description) {
    // Populate the edit form
    document.getElementById('edit_location_id').value = locationId;
    document.getElementById('edit_location_name').value = locationName;
    document.getElementById('edit_location_type').value = locationType || '';
    document.getElementById('edit_description').value = description || '';

    // Open the edit modal
    document.getElementById('editLocationModal').style.display = 'flex';
  }

  function closeEditLocationModal() {
    document.getElementById('editLocationModal').style.display = 'none';
  }

  function submitEditLocation(event) {
    event.preventDefault();

    const formData = new FormData(event.target);

    fetch('update_location.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showToastSuccess(data.message || 'Location updated successfully!');
          closeEditLocationModal();
          setTimeout(() => window.location.reload(), 1000);
        } else {
          showToastError(data.message || 'Failed to update location');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToastError('An error occurred. Please try again.');
      });
  }

  // Delete location function
  let pendingDeleteId = null;
  let pendingDeleteName = null;

  function deleteLocation(locationId, locationName) {
    // Store the location details
    pendingDeleteId = locationId;
    pendingDeleteName = locationName;

    // Update the confirmation dialog
    document.getElementById('confirmLocationName').textContent = locationName;

    // Show the confirmation dialog
    document.getElementById('confirmDialog').style.display = 'flex';
  }

  function closeConfirmDialog() {
    document.getElementById('confirmDialog').style.display = 'none';
    pendingDeleteId = null;
    pendingDeleteName = null;
  }

  function confirmDelete() {
    if (!pendingDeleteId) {
      console.error('No pending delete ID');
      return;
    }

    // Ensure ID is an integer
    const locationId = parseInt(pendingDeleteId, 10);
    console.log('Pending Delete ID:', pendingDeleteId);
    console.log('Deleting location ID:', locationId);

    // Perform the delete
    fetch('delete_location.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ location_id: locationId })
    })
      .then(response => response.json())
      .then(data => {
        console.log('Delete response:', data);

        // Close the dialog AFTER the request
        closeConfirmDialog();

        if (data.success) {
          showToastSuccess(data.message || 'Location deleted successfully!');
          setTimeout(() => window.location.reload(), 1000);
        } else {
          showToastError(data.message || 'Failed to delete location');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        closeConfirmDialog();
        showToastError('An error occurred. Please try again.');
      });
  }

  // Attach confirm handler to the delete button
  document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('confirmDeleteBtn').onclick = confirmDelete;
  });


  // Close modal when clicking outside
  window.onclick = function (event) {
    const addModal = document.getElementById('addLocationModal');
    const editModal = document.getElementById('editLocationModal');
    const confirmModal = document.getElementById('confirmDialog');

    if (event.target === addModal) {
      closeAddLocationModal();
    }
    if (event.target === editModal) {
      closeEditLocationModal();
    }
    if (event.target === confirmModal) {
      closeConfirmDialog();
    }
  }
</script>

<?php include '../../includes/footer.php'; ?>