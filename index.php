<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if this is a valid request to the root
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';

// If the request URI contains additional path segments beyond the script name,
// it's likely an invalid request that should show 404
if ($request_uri !== '/' && $request_uri !== $script_name && strpos($request_uri, '?') === false) {
    // Check if the additional path is a valid directory (admin, learner, auth)
    $path_parts = explode('/', trim($request_uri, '/'));
    if (count($path_parts) > 1) {
        $first_part = $path_parts[1];
        if (!in_array($first_part, ['admin', 'learner', 'auth', 'assets', 'config', 'includes'])) {
            // Invalid path, show 404
            include '404.php';
            exit();
        }
    }
}

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('learner/dashboard.php');
    }
} else {
    redirect('auth/login.php');
}
?>
