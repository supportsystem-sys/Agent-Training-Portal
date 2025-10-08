<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();
$quiz = null;
$is_edit = false;
$lesson_id = $_GET['lesson_id'] ?? null;

// Handle edit mode
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $is_edit = true;
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        redirect('trainings.php');
    }
    $lesson_id = $quiz['lesson_id'];
} elseif (!$lesson_id || !is_numeric($lesson_id)) {
    redirect('trainings.php');
}

// Get lesson and Training information
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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $passing_score = (int)$_POST['passing_score'];
    
    if (empty($title)) {
        $error = 'Please fill in quiz title.';
    } elseif ($passing_score < 0 || $passing_score > 100) {
        $error = 'Passing score must be between 0 and 100.';
    } else {
        try {
            if ($is_edit) {
                $stmt = $pdo->prepare("UPDATE quizzes SET title = ?, passing_score = ? WHERE id = ?");
                $stmt->execute([$title, $passing_score, $quiz['id']]);
                $success = "Quiz updated successfully.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO quizzes (lesson_id, title, passing_score) VALUES (?, ?, ?)");
                $stmt->execute([$lesson_id, $title, $passing_score]);
                $quiz_id = $pdo->lastInsertId();
                $success = "Quiz created successfully. <a href='questions.php?quiz_id=$quiz_id'>Add questions now</a>.";
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
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Quiz - Halcom Marketing Admin</title>
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
                <h1><?php echo $is_edit ? 'Edit Quiz' : 'Add New Quiz'; ?></h1>
                <p>Training: <strong><?php echo htmlspecialchars($lesson['Training_title']); ?></strong></p>
                <p>Lesson: <strong><?php echo htmlspecialchars($lesson['title']); ?></strong></p>
            </div>
            <a href="quizzes.php?lesson_id=<?php echo $lesson_id; ?>" class="btn btn-secondary">Back to Quizzes</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Quiz Information
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="title">Quiz Title:</label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo $quiz ? htmlspecialchars($quiz['title']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="passing_score">Passing Score (%):</label>
                        <input type="number" id="passing_score" name="passing_score" required min="0" max="100" 
                               value="<?php echo $quiz ? $quiz['passing_score'] : 70; ?>">
                    </div>
                    
                    <div class="d-flex gap-10">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $is_edit ? 'Update Quiz' : 'Create Quiz'; ?>
                        </button>
                        <a href="quizzes.php?lesson_id=<?php echo $lesson_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
