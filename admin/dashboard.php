<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// Get dashboard statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'learner'");
$stats['users'] = $stmt->fetch()['total'];

// Total Training
$stmt = $pdo->query("SELECT COUNT(*) as total FROM courses");
$stats['Training'] = $stmt->fetch()['total'];

// Total lessons
$stmt = $pdo->query("SELECT COUNT(*) as total FROM lessons");
$stats['lessons'] = $stmt->fetch()['total'];

// Total lesson questions
$stmt = $pdo->query("SELECT COUNT(*) as total FROM lesson_questions");
$stats['questions'] = $stmt->fetch()['total'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">Halcom Marketing Admin</div>
            <div class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="trainings.php">Training</a>
                <a href="users.php">Users</a>
                <a href="training_assignments.php">Assignments</a>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>Admin Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>

        <!-- Statistics Cards -->
        <div class="dashboard">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['users']; ?></div>
                <div class="stat-label">Total Learners</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['Training']; ?></div>
                <div class="stat-label">Total Training</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['lessons']; ?></div>
                <div class="stat-label">Total Lessons</div>
            </div>
            <a href="all_questions.php" class="stat-card" style="text-decoration: none; color: inherit; cursor: pointer;">
                <div class="stat-number"><?php echo $stats['questions']; ?></div>
                <div class="stat-label">Total Questions</div>
            </a>
        </div>
    </div>
</body>
</html>
