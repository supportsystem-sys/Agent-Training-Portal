<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// Handle Training deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $course_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $success = "Training deleted successfully.";
}

// Get all Training with lesson counts
$stmt = $pdo->query("
    SELECT c.*, u.name as created_by_name,
           COUNT(l.id) as lesson_count
    FROM courses c
    LEFT JOIN users u ON c.created_by = u.id
    LEFT JOIN lessons l ON c.id = l.course_id
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$Training = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Training - Halcom Marketing Admin</title>
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
            <h1>Manage Training</h1>
            <a href="training_form.php" class="btn btn-primary">Add New Training</a>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                All Training
            </div>
            <div class="card-body">
                <?php if (empty($Training)): ?>
                    <p>No Training found. <a href="Training_form.php">Create your first Training</a>.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Lessons</th>
                                    <th>Created By</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($Training as $Training): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($Training['title']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($Training['description'], 0, 100)) . (strlen($Training['description']) > 100 ? '...' : ''); ?></td>
                                        <td><?php echo $Training['lesson_count']; ?></td>
                                        <td><?php echo htmlspecialchars($Training['created_by_name']); ?></td>
                                        <td><?php echo formatDate($Training['created_at']); ?></td>
                                        <td>
                                            <div class="d-flex gap-10">
                                                <a href="training_form.php?id=<?php echo $Training['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                                <a href="lessons.php?course_id=<?php echo $Training['id']; ?>" class="btn btn-sm btn-primary">Lessons</a>
                                                <a href="trainings.php?delete=<?php echo $Training['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this Training? This will also delete all associated lessons and quizzes.')">Delete</a>
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
