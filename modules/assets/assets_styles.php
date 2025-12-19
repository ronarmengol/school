<style>
  /* Shared Asset Management Module Styles */
  :root {
    --asset-primary: #3b82f6;
    --asset-success: #10b981;
    --asset-warning: #f59e0b;
    --asset-danger: #ef4444;
    --asset-text: #1e293b;
    --asset-muted: #64748b;
    --asset-border: #e2e8f0;
    --asset-card-bg: #ffffff;
    --asset-radius: 16px;
    --asset-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --asset-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  }

  .asset-module-wrap {
    display: flex;
    flex-direction: column;
    gap: 24px;
    padding-bottom: 50px;
    color: var(--asset-text);
    animation: assetFadeIn 0.3s ease-out;
  }

  @keyframes assetFadeIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Page Header */
  .asset-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
  }

  .breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: var(--asset-muted);
    margin-bottom: 8px;
  }

  .breadcrumb a:hover {
    color: var(--asset-primary);
  }

  .asset-title {
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -0.025em;
    margin: 0;
  }

  /* Table Style */
  .asset-card {
    background: var(--asset-card-bg);
    border-radius: var(--asset-radius);
    box-shadow: var(--asset-shadow);
    border: 1px solid var(--asset-border);
    overflow: hidden;
  }

  .asset-table {
    width: 100%;
    border-collapse: collapse;
  }

  .asset-table th {
    text-align: left;
    padding: 16px 24px;
    background: #f8fafc;
    font-size: 12px;
    font-weight: 700;
    color: var(--asset-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid var(--asset-border);
  }

  .asset-table td {
    padding: 16px 24px;
    font-size: 14px;
    border-bottom: 1px solid var(--asset-border);
    vertical-align: middle;
  }

  /* Status Badges */
  .status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    gap: 6px;
    text-transform: uppercase;
  }

  .status-in-use {
    background: #dcfce7;
    color: #15803d;
  }

  .status-available {
    background: #dbeafe;
    color: #1e40af;
  }

  .status-maintenance {
    background: #fef3c7;
    color: #b45309;
  }

  .status-retired {
    background: #fee2e2;
    color: #b91c1c;
  }

  .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
  }

  /* Buttons */
  .asset-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
    text-decoration: none;
  }

  .asset-btn-primary {
    background: var(--asset-primary);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
  }

  .asset-btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3);
  }

  .asset-btn-secondary {
    background: white;
    color: var(--asset-muted);
    border-color: var(--asset-border);
  }

  .asset-btn-secondary:hover {
    background: #f8fafc;
    color: var(--asset-text);
    border-color: #cbd5e1;
  }

  /* Form Controls */
  .asset-form-group {
    margin-bottom: 20px;
  }

  .asset-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: var(--asset-text);
    margin-bottom: 8px;
  }

  .asset-input,
  .asset-select,
  .asset-textarea {
    width: 100%;
    padding: 12px 16px;
    border-radius: 10px;
    border: 1.5px solid var(--asset-border);
    font-size: 14px;
    color: var(--asset-text);
    transition: all 0.2s;
    background: #fcfcfc;
  }

  .asset-input:focus,
  .asset-select:focus,
  .asset-textarea:focus {
    border-color: var(--asset-primary);
    outline: none;
    background: white;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
  }

  /* Dashboard-Specific Styles */
  .kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 24px;
  }

  .kpi-card {
    background: var(--asset-card-bg);
    padding: 24px;
    border-radius: var(--asset-radius);
    box-shadow: var(--asset-shadow);
    border: 1px solid var(--asset-border);
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
  }

  .kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--asset-shadow-lg);
  }

  .kpi-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--asset-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 12px;
    display: block;
  }

  .kpi-value {
    font-size: 32px;
    font-weight: 800;
    display: block;
  }

  .kpi-trend {
    font-size: 13px;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 4px;
  }

  .trend-up {
    color: var(--asset-success);
  }

  .trend-down {
    color: var(--asset-danger);
  }

  .kpi-icon-bg {
    position: absolute;
    right: -10px;
    bottom: -10px;
    opacity: 0.05;
    transform: rotate(-15deg);
  }

  /* Analytics Section */
  .analytics-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
  }

  .analytics-card {
    background: var(--asset-card-bg);
    border-radius: var(--asset-radius);
    box-shadow: var(--asset-shadow);
    border: 1px solid var(--asset-border);
    padding: 24px;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--asset-border);
  }

  .card-title {
    font-size: 18px;
    font-weight: 700;
    margin: 0;
  }

  /* Distribution Bars */
  .distribution-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  .dist-label-row {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
  }

  .dist-bar-container {
    height: 10px;
    background: #f1f5f9;
    border-radius: 99px;
    overflow: hidden;
  }

  .dist-bar {
    height: 100%;
    border-radius: 99px;
    transition: width 1s ease-in-out;
  }

  /* Table Component */
  .table-container {
    background: var(--asset-card-bg);
    border-radius: var(--asset-radius);
    box-shadow: var(--asset-shadow);
    border: 1px solid var(--asset-border);
    overflow: hidden;
  }

  .table-tools {
    padding: 20px 24px;
    background: #fafafa;
    border-bottom: 1px solid var(--asset-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
  }

  .search-input-group {
    position: relative;
    flex: 1;
    max-width: 400px;
  }

  .search-input {
    width: 100%;
    padding: 10px 16px 10px 40px;
    border-radius: 10px;
    border: 1px solid var(--asset-border);
    font-size: 14px;
  }

  .search-input:focus {
    border-color: var(--asset-primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--asset-muted);
  }

  /* Right Side Panels */
  .side-panel-stack {
    display: flex;
    flex-direction: column;
    gap: 24px;
  }

  .maintenance-item {
    padding: 14px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid var(--asset-border);
    margin-bottom: 12px;
  }

  .maint-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
  }

  .maint-item-name {
    font-size: 14px;
    font-weight: 700;
    margin: 0;
  }

  .prio-badge {
    font-size: 10px;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 4px;
    text-transform: uppercase;
  }

  .prio-critical {
    background: #fee2e2;
    color: #ef4444;
  }

  .prio-high {
    background: #ffedd5;
    color: #f59e0b;
  }

  .prio-medium {
    background: #dbeafe;
    color: #3b82f6;
  }

  .maint-info {
    font-size: 12px;
    color: var(--asset-muted);
    display: flex;
    justify-content: space-between;
  }

  /* Activity Feed */
  .activity-feed {
    position: relative;
    padding-left: 20px;
  }

  .activity-feed::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #f1f5f9;
  }

  .activity-item {
    position: relative;
    padding-bottom: 20px;
  }

  .activity-item::before {
    content: '';
    position: absolute;
    left: -23px;
    top: 5px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--asset-primary);
    border: 2px solid white;
    box-shadow: 0 0 0 2px #f1f5f9;
    z-index: 1;
  }

  .activity-content {
    font-size: 13px;
  }

  .activity-action {
    font-weight: 700;
  }

  .activity-meta {
    color: var(--asset-muted);
    font-size: 12px;
    margin-top: 4px;
  }

  /* Responsive Adjustments */
  @media (max-width: 1100px) {
    .analytics-row {
      grid-template-columns: 1fr;
    }
  }

  /* Modal Styles */
  .modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    animation: modalFadeIn 0.2s ease-out;
  }

  @keyframes modalFadeIn {
    from {
      opacity: 0;
    }

    to {
      opacity: 1;
    }
  }

  .modal-content {
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow: hidden;
    animation: modalSlideIn 0.3s ease-out;
    display: flex;
    flex-direction: column;
  }

  @keyframes modalSlideIn {
    from {
      opacity: 0;
      transform: translateY(-20px) scale(0.95);
    }

    to {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 24px 20px 24px;
    border-bottom: 1px solid var(--asset-border);
    background: #fafafa;
  }

  .modal-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: var(--asset-text);
  }

  .modal-header button {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: var(--asset-muted);
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s;
    line-height: 1;
    padding: 0;
  }

  .modal-header button:hover {
    background: #e2e8f0;
    color: var(--asset-text);
  }

  .modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
  }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 20px 24px;
    border-top: 1px solid var(--asset-border);
    background: #fafafa;
  }

  /* Responsive Modal */
  @media (max-width: 640px) {
    .modal-content {
      width: 95%;
      max-height: 95vh;
    }

    .modal-header,
    .modal-body,
    .modal-footer {
      padding: 16px;
    }
  }
</style>