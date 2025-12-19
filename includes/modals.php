<!-- Modal Overlay and Dialog -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal-dialog">
    <div class="modal-header">
      <div class="modal-icon" id="modalIcon">⚠️</div>
      <h3 class="modal-title" id="modalTitle">Confirm Action</h3>
    </div>
    <div class="modal-body" id="modalBody">
      Are you sure you want to proceed?
    </div>
    <div class="modal-footer" id="modalFooter">
      <button class="modal-btn modal-btn-secondary" onclick="closeModal()">Cancel</button>
      <button class="modal-btn modal-btn-primary" id="modalConfirmBtn">Confirm</button>
    </div>
  </div>
</div>

<script>
  /**
   * Global Modal System
   * Replaces default alert() and confirm() with beautiful dialogs
   */

  function showModal(options) {
    const modal = document.getElementById('modalOverlay');
    const icon = document.getElementById('modalIcon');
    const title = document.getElementById('modalTitle');
    const body = document.getElementById('modalBody');
    const footer = document.getElementById('modalFooter');

    // Set type-based styling
    const type = options.type || 'warning';
    icon.className = 'modal-icon ' + type;

    // Set icon content
    switch (type) {
      case 'success': icon.textContent = options.icon || '✓'; break;
      case 'error': icon.textContent = options.icon || '✗'; break;
      case 'info': icon.textContent = options.icon || 'ℹ'; break;
      default: icon.textContent = options.icon || '⚠️';
    }

    title.textContent = options.title || 'Confirm';
    body.innerHTML = options.message || '';

    footer.innerHTML = '';

    if (options.buttons) {
      options.buttons.forEach(btn => {
        const button = document.createElement('button');
        button.className = 'modal-btn modal-btn-' + (btn.type || 'secondary');
        button.textContent = btn.text;
        button.onclick = () => {
          if (btn.onClick) btn.onClick();
          closeModal();
        };
        footer.appendChild(button);
      });
    } else {
      // Default Confirm behavior
      const cancelBtn = document.createElement('button');
      cancelBtn.className = 'modal-btn modal-btn-secondary';
      cancelBtn.textContent = options.cancelText || 'Cancel';
      cancelBtn.onclick = closeModal;
      footer.appendChild(cancelBtn);

      const confirmBtn = document.createElement('button');
      confirmBtn.className = 'modal-btn modal-btn-' + (options.confirmType || 'primary');
      confirmBtn.textContent = options.confirmText || 'Confirm';
      confirmBtn.onclick = () => {
        if (options.onConfirm) options.onConfirm();
        closeModal();
      };
      footer.appendChild(confirmBtn);
    }

    modal.classList.add('active');
  }

  function closeModal() {
    document.getElementById('modalOverlay').classList.remove('active');
  }

  /**
   * Replaces alert()
   */
  window.showAlert = function (message, title = 'Notification', type = 'info') {
    showModal({
      type: type,
      title: title,
      message: message,
      buttons: [{
        text: 'Dismiss',
        type: type === 'error' ? 'danger' : (type === 'success' ? 'success' : 'primary'),
        onClick: () => { }
      }]
    });
  };

  /**
   * Convenience version of showError
   */
  window.showError = function (message) {
    showAlert(message, 'Error Occurred', 'error');
  };

  /**
   * Convenience version of showSuccess
   */
  window.showSuccess = function (message) {
    showAlert(message, 'Success!', 'success');
  };

  /**
   * Universal Modal API - Consistent with settings page
   */
  window.UniversalModal = {
    show: function (title, message, type = 'alert', onConfirm = null, onCancel = null) {
      const typeMap = {
        'alert': 'info',
        'confirm': 'warning',
        'warning': 'error'
      };
      const modalType = typeMap[type] || 'info';

      if (type === 'confirm' || type === 'warning') {
        showModal({
          type: modalType,
          title: title,
          message: message,
          confirmText: 'Confirm',
          cancelText: 'Cancel',
          confirmType: type === 'warning' ? 'danger' : 'primary',
          onConfirm: onConfirm,
          onCancel: onCancel
        });
      } else {
        showModal({
          type: modalType,
          title: title,
          message: message,
          buttons: [{
            text: 'OK',
            type: modalType === 'error' ? 'danger' : (modalType === 'success' ? 'success' : 'primary'),
            onClick: onConfirm
          }]
        });
      }
    },

    alert: function (title, message, onClose = null) {
      this.show(title, message, 'alert', onClose);
    },

    confirm: function (title, message, onConfirm, onCancel = null) {
      this.show(title, message, 'confirm', onConfirm, onCancel);
    },

    warning: function (title, message, onConfirm, onCancel = null) {
      this.show(title, message, 'warning', onConfirm, onCancel);
    },

    hide: function () {
      closeModal();
    }
  };

  // Close modal on overlay click
  document.addEventListener('click', function (e) {
    if (e.target.id === 'modalOverlay') {
      closeModal();
    }
  });

  // Close modal on Escape key
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeModal();
    }
  });
</script>