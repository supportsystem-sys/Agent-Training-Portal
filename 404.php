<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Halcom Marketing</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="container">
        <div class="auth-form">
            <div class="logo-container">
                <img src="assets/images/logo.png" alt="Halcom Marketing Logo" class="login-logo">
            </div>
            <h2>Page Not Found</h2>
            <p>The page you're looking for doesn't exist.</p>
            <div class="d-flex gap-10">
                <a href="index.php" class="btn btn-primary">Go to Home</a>
                <a href="auth/login.php" class="btn btn-secondary">Login</a>
            </div>
        </div>
    </div>
</body>
</html>
