<?php if (isset($_SESSION['user_id'])): ?>
            </div> <!-- End content-body -->
        </main> <!-- End main-content -->
    </div> <!-- End app-container -->
<?php endif; ?>

    <script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }
    </script>
    <script src="<?php echo ASSETS_PATH; ?>js/main.js"></script>
</body>
</html>
