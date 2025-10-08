<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// Get quiz information
$quiz_id = $_GET['quiz_id'] ?? null;
if (!$quiz_id || !is_numeric($quiz_id)) {
    redirect('trainings.php');
}

$stmt = $pdo->prepare("
    SELECT q.*, l.title as lesson_title, c.title as Training_title
    FROM quizzes q
    JOIN lessons l ON q.lesson_id = l.id
    JOIN courses c ON l.course_id = c.id
    WHERE q.id = ?
");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    redirect('trainings.php');
}

// Handle question deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $question_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ? AND quiz_id = ?");
    $stmt->execute([$question_id, $quiz_id]);
    $success = "Question deleted successfully.";
}

// Get all questions for this quiz with their options
$stmt = $pdo->prepare("
    SELECT q.*, COUNT(qo.id) as option_count
    FROM questions q
    LEFT JOIN question_options qo ON q.id = qo.question_id
    WHERE q.quiz_id = ?
    GROUP BY q.id
    ORDER BY q.created_at
");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - <?php echo htmlspecialchars($quiz['title']); ?></title>
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
                <h1>Manage Questions</h1>
                <p>Training: <strong><?php echo htmlspecialchars($quiz['Training_title']); ?></strong></p>
                <p>Lesson: <strong><?php echo htmlspecialchars($quiz['lesson_title']); ?></strong></p>
                <p>Quiz: <strong><?php echo htmlspecialchars($quiz['title']); ?></strong></p>
            </div>
            <div class="d-flex gap-10">
                <a href="question_form.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-primary">Add New Question</a>
                <a href="quizzes.php?lesson_id=<?php echo $quiz['lesson_id']; ?>" class="btn btn-secondary">Back to Quizzes</a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Questions for <?php echo htmlspecialchars($quiz['title']); ?>
            </div>
            <div class="card-body">
                <?php if (empty($questions)): ?>
                    <p>No questions found. <a href="question_form.php?quiz_id=<?php echo $quiz_id; ?>">Create your first question</a>.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Question</th>
                                    <th>Type</th>
                                    <th>Options</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($questions as $question): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(substr($question['question_text'], 0, 100)) . (strlen($question['question_text']) > 100 ? '...' : ''); ?></td>
                                        <td>
                                            <span class="btn btn-sm <?php echo $question['type'] === 'mcq' ? 'btn-primary' : 'btn-secondary'; ?>">
                                                <?php echo strtoupper($question['type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $question['option_count']; ?></td>
                                        <td>
                                            <div class="d-flex gap-10">
                                                <a href="question_form.php?id=<?php echo $question['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                                <a href="question_options.php?question_id=<?php echo $question['id']; ?>" class="btn btn-sm btn-primary">Options</a>
                                                <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>&delete=<?php echo $question['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this question? This will also delete all associated options.')">Delete</a>
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
