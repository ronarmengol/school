<?php
// modules/assets/assets_header.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
  .asset-nav {
    display: flex;
    gap: 8px;
    background: white;
    padding: 8px;
    border-radius: 12px;
    border: 1px solid var(--asset-border);
    margin-bottom: 24px;
    overflow-x: auto;
    white-space: nowrap;
  }

  .asset-nav-link {
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--asset-muted);
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
  }

  .asset-nav-link:hover {
    background: #f8fafc;
    color: var(--asset-primary);
  }

  .asset-nav-link.active {
    background: var(--asset-primary);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
  }

  .asset-nav-link svg {
    width: 18px;
    height: 18px;
  }
</style>

<div class="asset-nav">
  <a href="index.php" class="asset-nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
    </svg>
    Dashboard
  </a>
  <a href="list.php"
    class="asset-nav-link <?php echo $current_page == 'list.php' || $current_page == 'add.php' || $current_page == 'edit.php' ? 'active' : ''; ?>">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
    </svg>
    Asset List
  </a>
  <a href="categories.php" class="asset-nav-link <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M7 7h.01M7 11h.01M7 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2z" />
    </svg>
    Categories
  </a>
  <a href="locations.php" class="asset-nav-link <?php echo $current_page == 'locations.php' ? 'active' : ''; ?>">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
    Locations
  </a>
  <a href="maintenance.php" class="asset-nav-link <?php echo $current_page == 'maintenance.php' ? 'active' : ''; ?>">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
    Maintenance
  </a>
  <a href="assignment.php" class="asset-nav-link <?php echo $current_page == 'assignment.php' ? 'active' : ''; ?>">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
    </svg>
    Assignments
  </a>
  <a href="reports.php" class="asset-nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
    </svg>
    Reports
  </a>
</div>