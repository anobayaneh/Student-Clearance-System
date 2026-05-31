<?php
// logout.php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    log_activity($conn, $_SESSION['user_id'], 'Logout', 'User logged out.');
}

session_unset();
session_destroy();

header('Location: login.php');
exit;
?>