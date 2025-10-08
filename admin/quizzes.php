<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// Get lesson information
$lesson_id = $_GET['lesson_id'] ?? null;
if (!$lesson_id || !is_numeric($lesson_id)) {
    redirect('trainings.php');
}

$stmt = $pdo->prepare("
    SELECT l.*, c.title as Training_title
    FROM lessons l
    JOIN courses c ON l.course_id = c.id
    WHERE l.id = ?
");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch();

if (!$lesson) {
    redirect('trainings.php');
}

// Handle quiz deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $quiz_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ? AND lesson_id = ?");
    $stmt->execute([$quiz_id, $lesson_id]);
    $success = "Quiz deleted successfully.";
}

// Get all quizzes for this lesson
$stmt = $pdo->prepare("
    SELECT q.*, COUNT(qq.id) as question_count
    FROM quizzes q
    LEFT JOIN questions qq ON q.id = qq.quiz_id
    WHERE q.lesson_id = ?
    GROUP BY q.id
    ORDER BY q.created_at
");
$stmt->execute([$lesson_id]);
$quizzes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quizzes - <?php echo htmlspecialchars($lesson['title']); ?></title>
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
                <h1>Manage Quizzes</h1>
                <p>Training: <strong><?php echo htmlspecialchars($lesson['Training_title']); ?></strong></p>
                <p>Lesson: <strong><?php echo htmlspecialchars($lesson['title']); ?></strong></p>
            </div>
            <div class="d-flex gap-10">
                <a href="quiz_form.php?lesson_id=<?php echo $lesson_id; ?>" class="btn btn-primary">Add New Quiz</a>
                <a href="lessons.php?course_id=<?php echo $lesson['course_id']; ?>" class="btn btn-secondary">Back to Lessons</a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Quizzes for <?php echo htmlspecialchars($lesson['title']); ?>
            </div>
            <div class="card-body">
                <?php if (empty($quizzes)): ?>
                    <p>No quizzes found. <a href="quiz_form.php?lesson_id=<?php echo $lesson_id; ?>">Create your first quiz</a>.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Questions</th>
                                    <th>Passing Score</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quizzes as $quiz): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                        <td><?php echo $quiz['question_count']; ?></td>
                                        <td><?php echo $quiz['passing_score']; ?>%</td>
                                        <td><?php echo formatDate($quiz['created_at']); ?></td>
                                        <td>
                                            <div class="d-flex gap-10">
                                                <a href="quiz_form.php?id=<?php echo $quiz['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                                <a href="questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-sm btn-primary">Questions</a>
                                                <a href="quizzes.php?lesson_id=<?php echo $lesson_id; ?>&delete=<?php echo $quiz['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this quiz? This will also delete all associated questions.')">Delete</a>
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
