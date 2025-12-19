<?php
require_once 'config/database.php';
require_once 'config/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "modules/dashboard/index.php");
} else {
    header("Location: " . BASE_URL . "auth/login.php");
}
exit();
?>
