<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// Get user information
$user_id = $_GET['id'] ?? null;
if (!$user_id || !is_numeric($user_id)) {
    redirect('users.php');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'learner'");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('users.php');
}

// Get user's Training progress
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(l.id) as total_lessons,
           COUNT(p.lesson_id) as completed_lessons,
           ROUND((COUNT(p.lesson_id) / COUNT(l.id)) * 100, 1) as progress_percentage
    FROM courses c
    LEFT JOIN lessons l ON c.id = l.course_id
    LEFT JOIN progress p ON l.id = p.lesson_id AND p.user_id = ? AND p.completed = 1
    GROUP BY c.id
    ORDER BY progress_percentage DESC, c.created_at DESC
");
$stmt->execute([$user_id]);
$Training_progress = $stmt->fetchAll();

// Get user's quiz attempts
$stmt = $pdo->prepare("
    SELECT qa.*, q.title as quiz_title, q.passing_score,
           l.title as lesson_title, c.title as Training_title
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN lessons l ON q.lesson_id = l.id
    JOIN courses c ON l.course_id = c.id
    WHERE qa.user_id = ?
    ORDER BY qa.attempted_at DESC
");
$stmt->execute([$user_id]);
$quiz_attempts = $stmt->fetchAll();

// Get user statistics
$stats = [];

// Total lessons completed
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM progress WHERE user_id = ? AND completed = 1");
$stmt->execute([$user_id]);
$stats['lessons_completed'] = $stmt->fetch()['total'];

// Total quiz attempts
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM quiz_attempts WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats['quiz_attempts'] = $stmt->fetch()['total'];

// Passed quizzes
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM quiz_attempts WHERE user_id = ? AND passed = 1");
$stmt->execute([$user_id]);
$stats['passed_quizzes'] = $stmt->fetch()['total'];

// Average quiz score
$stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM quiz_attempts WHERE user_id = ? AND score > 0");
$stmt->execute([$user_id]);
$stats['avg_score'] = round($stmt->fetch()['avg_score'] ?? 0, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - <?php echo htmlspecialchars($user['name']); ?> - Halcom Marketing Admin</title>
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
            <div>
                <h1>User Details</h1>
                <p><strong><?php echo htmlspecialchars($user['name']); ?></strong></p>
            </div>
            <div class="d-flex gap-10">
                <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">Edit User</a>
                <a href="users.php" class="btn btn-secondary">Back to Users</a>
            </div>
        </div>

        <!-- User Information -->
        <div class="card">
            <div class="card-header">
                Personal Information
            </div>
            <div class="card-body">
                <div class="d-flex gap-20">
                    <div>
                        <strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?>
                    </div>
                    <div>
                        <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                    <div>
                        <strong>Role:</strong> <?php echo ucfirst($user['role']); ?>
                    </div>
                    <div>
                        <strong>Member Since:</strong> <?php echo formatDate($user['created_at']); ?>
                    </div>
                </div>
                <div class="d-flex gap-20 mt-20">
                    <div>
                        <strong>Last Login:</strong> 
                        <?php if ($user['last_login']): ?>
                            <?php echo formatDateTime($user['last_login']); ?>
                        <?php else: ?>
                            <em>Never</em>
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong>Total Portal Time:</strong> 
                        <?php if ($user['total_portal_time'] > 0): ?>
                            <?php echo formatDuration($user['total_portal_time']); ?>
                        <?php else: ?>
                            <em>0m</em>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="dashboard">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['lessons_completed']; ?></div>
                <div class="stat-label">Lessons Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['quiz_attempts']; ?></div>
                <div class="stat-label">Quiz Attempts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['passed_quizzes']; ?></div>
                <div class="stat-label">Quizzes Passed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['avg_score']; ?>%</div>
                <div class="stat-label">Average Score</div>
            </div>
        </div>

        <!-- Training Progress -->
        <div class="card">
            <div class="card-header">
                Training Progress
            </div>
            <div class="card-body">
                <?php if (empty($Training_progress)): ?>
                    <p>This user hasn't started any Training yet.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Training</th>
                                    <th>Progress</th>
                                    <th>Lessons</th>
                                    <th>Completed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($Training_progress as $Training): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($Training['title']); ?></strong><br>
                                            <small><?php echo htmlspecialchars(substr($Training['description'], 0, 100)) . (strlen($Training['description']) > 100 ? '...' : ''); ?></small>
                                        </td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar <?php echo $Training['progress_percentage'] >= 100 ? 'success' : ''; ?>" 
                                                     style="width: <?php echo $Training['progress_percentage']; ?>%"></div>
                                            </div>
                                            <small><?php echo $Training['progress_percentage']; ?>%</small>
                                        </td>
                                        <td><?php echo $Training['total_lessons']; ?></td>
                                        <td><?php echo $Training['completed_lessons']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quiz History -->
        <div class="card">
            <div class="card-header">
                Quiz History
            </div>
            <div class="card-body">
                <?php if (empty($quiz_attempts)): ?>
                    <p>This user hasn't taken any quizzes yet.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Quiz</th>
                                    <th>Training</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Passing Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quiz_attempts as $attempt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($attempt['quiz_title']); ?></td>
                                        <td><?php echo htmlspecialchars($attempt['Training_title']); ?></td>
                                        <td><?php echo $attempt['score']; ?>%</td>
                                        <td>
                                            <span class="btn btn-sm <?php echo $attempt['passed'] ? 'btn-success' : 'btn-danger'; ?>">
                                                <?php echo $attempt['passed'] ? 'Passed' : 'Failed'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDateTime($attempt['attempted_at']); ?></td>
                                        <td><?php echo $attempt['passing_score']; ?>%</td>
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
