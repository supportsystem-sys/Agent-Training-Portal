<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// Get question information
$question_id = $_GET['question_id'] ?? null;
if (!$question_id || !is_numeric($question_id)) {
    redirect('trainings.php');
}

$stmt = $pdo->prepare("
    SELECT q.*, qz.title as quiz_title, l.title as lesson_title, c.title as Training_title
    FROM questions q
    JOIN quizzes qz ON q.quiz_id = qz.id
    JOIN lessons l ON qz.lesson_id = l.id
    JOIN courses c ON l.course_id = c.id
    WHERE q.id = ?
");
$stmt->execute([$question_id]);
$question = $stmt->fetch();

if (!$question) {
    redirect('trainings.php');
}

// Handle option deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $option_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM question_options WHERE id = ? AND question_id = ?");
    $stmt->execute([$option_id, $question_id]);
    $success = "Option deleted successfully.";
}

// Get all options for this question
$stmt = $pdo->prepare("
    SELECT * FROM question_options
    WHERE question_id = ?
    ORDER BY created_at
");
$stmt->execute([$question_id]);
$options = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Options - <?php echo htmlspecialchars($question['question_text']); ?></title>
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
                <h1>Manage Question Options</h1>
                <p>Training: <strong><?php echo htmlspecialchars($question['Training_title']); ?></strong></p>
                <p>Lesson: <strong><?php echo htmlspecialchars($question['lesson_title']); ?></strong></p>
                <p>Quiz: <strong><?php echo htmlspecialchars($question['quiz_title']); ?></strong></p>
                <p>Question: <strong><?php echo htmlspecialchars(substr($question['question_text'], 0, 100)) . (strlen($question['question_text']) > 100 ? '...' : ''); ?></strong></p>
            </div>
            <div class="d-flex gap-10">
                <a href="option_form.php?question_id=<?php echo $question_id; ?>" class="btn btn-primary">Add New Option</a>
                <a href="questions.php?quiz_id=<?php echo $question['quiz_id']; ?>" class="btn btn-secondary">Back to Questions</a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Options for Question
            </div>
            <div class="card-body">
                <?php if ($question['type'] === 'essay'): ?>
                    <div class="alert alert-info">
                        This is an essay question and doesn't require options.
                    </div>
                <?php elseif (empty($options)): ?>
                    <p>No options found. <a href="option_form.php?question_id=<?php echo $question_id; ?>">Create your first option</a>.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Option Text</th>
                                    <th>Correct Answer</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($options as $option): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($option['option_text']); ?></td>
                                        <td>
                                            <?php if ($option['is_correct']): ?>
                                                <span class="btn btn-sm btn-success">Correct</span>
                                            <?php else: ?>
                                                <span class="btn btn-sm btn-secondary">Incorrect</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-10">
                                                <a href="option_form.php?id=<?php echo $option['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                                <a href="question_options.php?question_id=<?php echo $question_id; ?>&delete=<?php echo $option['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this option?')">Delete</a>
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
