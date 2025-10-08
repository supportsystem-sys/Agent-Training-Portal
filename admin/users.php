<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    // Don't allow deletion of admin users
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user && $user['role'] !== 'admin') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $success = "User deleted successfully.";
    } else {
        $error = "Cannot delete admin users.";
    }
}

// Get all learners with their stats
$stmt = $pdo->query("
    SELECT u.*, 
           COUNT(DISTINCT qa.id) as quiz_attempts,
           COUNT(DISTINCT p.lesson_id) as lessons_completed,
           AVG(qa.score) as avg_score
    FROM users u
    LEFT JOIN quiz_attempts qa ON u.id = qa.user_id
    LEFT JOIN progress p ON u.id = p.user_id AND p.completed = 1
    WHERE u.role = 'learner'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Halcom Marketing Admin</title>
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
        <div class="d-flex justify-between align-center mb-20">
            <h1>Manage Users</h1>
            <div class="d-flex gap-10">
                <a href="user_create.php" class="btn btn-primary">Create New User</a>
                <span class="btn btn-secondary">Total Learners: <?php echo count($users); ?></span>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                All Learners
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <p>No learners found.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Quiz Attempts</th>
                                    <th>Lessons Completed</th>
                                    <th>Average Score</th>
                                    <th>Joined Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo $user['quiz_attempts']; ?></td>
                                        <td><?php echo $user['lessons_completed']; ?></td>
                                        <td><?php echo $user['avg_score'] ? round($user['avg_score'], 1) . '%' : 'N/A'; ?></td>
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                        <td>
                                            <div class="d-flex gap-10">
                                                <a href="user_details.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                                <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                                <a href="users.php?delete=<?php echo $user['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this user? This will also delete all their progress and quiz attempts.')">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
