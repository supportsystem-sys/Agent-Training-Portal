<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// End user session logging before destroying session
if (isset($_SESSION['user_id'])) {
    endUserSession($_SESSION['user_id']);
}

session_destroy();
redirect('login.php');
?>
