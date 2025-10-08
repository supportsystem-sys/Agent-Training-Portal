<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();
$option = null;
$is_edit = false;
$question_id = $_GET['question_id'] ?? null;

// Handle edit mode
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $is_edit = true;
    $stmt = $pdo->prepare("SELECT * FROM question_options WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $option = $stmt->fetch();
    
    if (!$option) {
        redirect('trainings.php');
    }
    $question_id = $option['question_id'];
} elseif (!$question_id || !is_numeric($question_id)) {
    redirect('trainings.php');
}

// Get question, quiz, lesson and Training information
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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $option_text = sanitize($_POST['option_text']);
    $is_correct = isset($_POST['is_correct']) ? 1 : 0;
    
    if (empty($option_text)) {
        $error = 'Please fill in option text.';
    } else {
        try {
            if ($is_edit) {
                $stmt = $pdo->prepare("UPDATE question_options SET option_text = ?, is_correct = ? WHERE id = ?");
                $stmt->execute([$option_text, $is_correct, $option['id']]);
                $success = "Option updated successfully.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                $stmt->execute([$question_id, $option_text, $is_correct]);
                $success = "Option created successfully.";
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
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Option - Halcom Marketing Admin</title>
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
                <h1><?php echo $is_edit ? 'Edit Option' : 'Add New Option'; ?></h1>
                <p>Training: <strong><?php echo htmlspecialchars($question['Training_title']); ?></strong></p>
                <p>Lesson: <strong><?php echo htmlspecialchars($question['lesson_title']); ?></strong></p>
                <p>Quiz: <strong><?php echo htmlspecialchars($question['quiz_title']); ?></strong></p>
                <p>Question: <strong><?php echo htmlspecialchars(substr($question['question_text'], 0, 100)) . (strlen($question['question_text']) > 100 ? '...' : ''); ?></strong></p>
            </div>
            <a href="question_options.php?question_id=<?php echo $question_id; ?>" class="btn btn-secondary">Back to Options</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Option Information
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="option_text">Option Text:</label>
                        <input type="text" id="option_text" name="option_text" required 
                               value="<?php echo $option ? htmlspecialchars($option['option_text']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_correct" value="1" 
                                   <?php echo ($option && $option['is_correct']) ? 'checked' : ''; ?>>
                            This is the correct answer
                        </label>
                    </div>
                    
                    <div class="d-flex gap-10">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $is_edit ? 'Update Option' : 'Create Option'; ?>
                        </button>
                        <a href="question_options.php?question_id=<?php echo $question_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
