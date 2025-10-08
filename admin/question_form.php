<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();
$question = null;
$is_edit = false;
$quiz_id = $_GET['quiz_id'] ?? null;

// Handle edit mode
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $is_edit = true;
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $question = $stmt->fetch();
    
    if (!$question) {
        redirect('trainings.php');
    }
    $quiz_id = $question['quiz_id'];
} elseif (!$quiz_id || !is_numeric($quiz_id)) {
    redirect('trainings.php');
}

// Get quiz, lesson and Training information
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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = $_POST['question_text']; // Don't sanitize as it may contain HTML
    $type = sanitize($_POST['type']);
    
    if (empty($question_text)) {
        $error = 'Please fill in question text.';
    } elseif (!in_array($type, ['mcq', 'essay'])) {
        $error = 'Invalid question type.';
    } else {
        try {
            if ($is_edit) {
                $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, type = ? WHERE id = ?");
                $stmt->execute([$question_text, $type, $question['id']]);
                $success = "Question updated successfully.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, type) VALUES (?, ?, ?)");
                $stmt->execute([$quiz_id, $question_text, $type]);
                $question_id = $pdo->lastInsertId();
                $success = "Question created successfully. <a href='question_options.php?question_id=$question_id'>Add options now</a>.";
            }
        } catch (Exception $e) {
            $error = "An error occurred. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Question - Halcom Marketing Admin</title>
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
                <h1><?php echo $is_edit ? 'Edit Question' : 'Add New Question'; ?></h1>
                <p>Training: <strong><?php echo htmlspecialchars($quiz['Training_title']); ?></strong></p>
                <p>Lesson: <strong><?php echo htmlspecialchars($quiz['lesson_title']); ?></strong></p>
                <p>Quiz: <strong><?php echo htmlspecialchars($quiz['title']); ?></strong></p>
            </div>
            <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-secondary">Back to Questions</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Question Information
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="question_text">Question Text:</label>
                        <textarea id="question_text" name="question_text" rows="4" required><?php echo $question ? htmlspecialchars($question['question_text']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Question Type:</label>
                        <select id="type" name="type" required>
                            <option value="mcq" <?php echo (!$question || $question['type'] === 'mcq') ? 'selected' : ''; ?>>Multiple Choice (MCQ)</option>
                            <option value="essay" <?php echo ($question && $question['type'] === 'essay') ? 'selected' : ''; ?>>Essay Question</option>
                        </select>
                    </div>
                    
                    <div class="d-flex gap-10">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $is_edit ? 'Update Question' : 'Create Question'; ?>
                        </button>
                        <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
