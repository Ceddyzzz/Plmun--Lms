<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    // Log the logout action
    error_log("User " . $_SESSION['user_id'] . " logged out at " . date('Y-m-d H:i:s'));
}

// Logout the user
logout();

// Redirect to login page
header('Location: index.php?logout=success');
exit();
?>
