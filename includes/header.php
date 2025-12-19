<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth_functions.php';
check_auth();
require_once __DIR__ . '/../config/constants.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo isset($page_title) ? $page_title . " - " . get_setting('school_name', APP_NAME) : get_setting('school_name', APP_NAME); ?>
    </title>
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/mobile.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Auto Logout Script -->
    <script>
        (function () {
            const TIMEOUT_DURATION = 300000; // 5 minutes in ms
            let logoutTimer;

            function resetTimer() {
                clearTimeout(logoutTimer);
                logoutTimer = setTimeout(logout, TIMEOUT_DURATION);
            }

            function logout() {
                window.location.href = "<?php echo BASE_URL; ?>auth/login.php?error=timeout";
            }

            // Events that reset the timer
            window.onload = resetTimer;
            document.onmousemove = resetTimer;
            document.onkeypress = resetTimer;
            document.onclick = resetTimer;
            document.onscroll = resetTimer;

            // Global interception for Fetch/XHR to reset timer on successful AJAX
            const originalFetch = window.fetch;
            window.fetch = function () {
                resetTimer();
                return originalFetch.apply(this, arguments).then(response => {
                    if (response.status === 401) {
                        logout();
                    }
                    return response;
                });
            };

            const originalXHR = window.XMLHttpRequest.prototype.open;
            window.XMLHttpRequest.prototype.open = function () {
                this.addEventListener('load', function () {
                    if (this.status === 401) {
                        logout();
                    }
                });
                resetTimer();
                return originalXHR.apply(this, arguments);
            };
        })();
    </script>
</head>

<body>

    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleSidebar()" style="display: none;">☰</button>
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="app-container">
            <!-- Sidebar -->
            <?php include __DIR__ . '/sidebar.php'; ?>

            <!-- Main Content Wrapper -->
            <main class="main-content">
                <!-- Top Bar -->
                <header class="top-bar">
                    <h2><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h2>
                    <div class="user-info">
                        <span>Welcome <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-danger"
                            style="padding: 5px 10px; font-size: 12px; margin-left: 10px;">Logout</a>
                    </div>
                </header>

                <!-- Page Content -->
                <div class="content-body">
                <?php endif; ?>