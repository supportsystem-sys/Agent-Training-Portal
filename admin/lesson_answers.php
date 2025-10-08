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
    SELECT l.*, c.title as course_title, c.id as course_id
    FROM lessons l
    JOIN courses c ON l.course_id = c.id
    WHERE l.id = ?
");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch();

if (!$lesson) {
    redirect('trainings.php');
}

// Get lesson questions
$stmt = $pdo->prepare("SELECT * FROM lesson_questions WHERE lesson_id = ? ORDER BY position ASC");
$stmt->execute([$lesson_id]);
$questions = $stmt->fetchAll();

// Get all answers from learners
$stmt = $pdo->prepare("
    SELECT la.*, u.name as user_name, u.email, lq.question_text, lq.position
    FROM lesson_answers la
    JOIN users u ON la.user_id = u.id
    JOIN lesson_questions lq ON la.lesson_question_id = lq.id
    WHERE lq.lesson_id = ?
    ORDER BY u.name, lq.position
");
$stmt->execute([$lesson_id]);
$all_answers = $stmt->fetchAll();

// Organize answers by user
$answers_by_user = [];
foreach ($all_answers as $answer) {
    if (!isset($answers_by_user[$answer['user_name']])) {
        $answers_by_user[$answer['user_name']] = [
            'email' => $answer['email'],
            'answers' => []
        ];
    }
    $answers_by_user[$answer['user_name']]['answers'][] = $answer;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesson Answers - <?php echo htmlspecialchars($lesson['title']); ?></title>
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
                <h1>Learner Answers</h1>
                <p>Lesson: <strong><?php echo htmlspecialchars($lesson['title']); ?></strong></p>
                <p>Training: <strong><?php echo htmlspecialchars($lesson['course_title']); ?></strong></p>
            </div>
            <a href="lessons.php?course_id=<?php echo $lesson['course_id']; ?>" class="btn btn-secondary">Back to Lessons</a>
        </div>

        <?php if (empty($questions)): ?>
            <div class="alert alert-info">
                No questions have been added to this lesson yet.
            </div>
        <?php elseif (empty($answers_by_user)): ?>
            <div class="alert alert-info">
                No learners have submitted answers yet.
            </div>
        <?php else: ?>
            <?php foreach ($answers_by_user as $user_name => $user_data): ?>
                <div class="card mb-20">
                    <div class="card-header">
                        <h3><?php echo htmlspecialchars($user_name); ?></h3>
                        <small><?php echo htmlspecialchars($user_data['email']); ?></small>
                    </div>
                    <div class="card-body">
                        <?php foreach ($user_data['answers'] as $answer): ?>
                            <div class="mb-20" style="padding: 15px; background: #f9f9f9; border-left: 4px solid #14B8A6; border-radius: 4px;">
                                <p style="margin: 0 0 10px 0; font-weight: 600;">
                                    Question <?php echo $answer['position']; ?>: 
                                    <?php echo nl2br(htmlspecialchars($answer['question_text'])); ?>
                                </p>
                                <p style="margin: 0; padding: 10px; background: white; border-radius: 4px;">
                                    <?php echo nl2br(htmlspecialchars($answer['answer_text'])); ?>
                                </p>
                                <small style="color: #666; margin-top: 5px; display: block;">
                                    Submitted: <?php echo date('M d, Y g:i A', strtotime($answer['submitted_at'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
