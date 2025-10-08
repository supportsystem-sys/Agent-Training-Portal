<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isLearner()) {
    redirect('../auth/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vapi Assistant - LMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Vapi Widget Script -->
    <script src="https://unpkg.com/@vapi-ai/client-sdk-react/dist/embed/widget.umd.js" async type="text/javascript"></script>
    <style>
       

        .vapi-button-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        vapi-widget {
            width: 300px; /* Adjust size as needed */
            height: 60px; /* Adjust height */
            border-radius: 40px;
            background-color: #fffbe6; /* Button color */
            font-size: 18px;
            font-weight: bold;
            color: #000;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        vapi-widget:hover {
            background-color: #f0e7cc;
        }
    </style>
    
</head>
<body class="dashboard-page">
    <div class="header learner-header">
        <div class="header-content">
            <div class="header-left">
                <img src="../assets/images/logo.png" alt="Halcom Marketing Logo" class="header-logo">
            </div>
            <div class="header-center">
                <h1 class="welcome-text">Welcome to Halcom Training Module</h1>
            </div>
            <div class="header-right">
                <div class="nav">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="vapi.php">Vapi</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container" style="margin-top: 2rem;">
        <div class="vapi-button-container">
        <vapi-widget
            public-key="f5e1427d-7d0d-40a4-a84d-51759cc52a3a"
        >
            TALK TO VAPI
        </vapi-widget>
    </div>


       


    </div>

    <footer class="footer">
        <div class="footer-content">
            Copyright Halcom Group 2025
        </div>
    </footer>
</body>
</html>



