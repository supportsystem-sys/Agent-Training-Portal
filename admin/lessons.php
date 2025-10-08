<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// Get Training information
$course_id = $_GET['course_id'] ?? null;
if (!$course_id || !is_numeric($course_id)) {
    redirect('trainings.php');
}

$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$Training = $stmt->fetch();

if (!$Training) {
    redirect('trainings.php');
}

// Handle lesson deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $lesson_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ? AND course_id = ?");
    $stmt->execute([$lesson_id, $course_id]);
    $success = "Lesson deleted successfully.";
}

// Get all lessons for this Training
$stmt = $pdo->prepare("
    SELECT l.*, 
           COUNT(DISTINCT lq.id) as question_count,
           COUNT(DISTINCT la.id) as answer_count
    FROM lessons l
    LEFT JOIN lesson_questions lq ON l.id = lq.lesson_id
    LEFT JOIN lesson_answers la ON lq.id = la.lesson_question_id
    WHERE l.course_id = ?
    GROUP BY l.id
    ORDER BY l.position, l.created_at
");
$stmt->execute([$course_id]);
$lessons = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lessons - <?php echo htmlspecialchars($Training['title']); ?></title>
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
                <h1>Manage Lessons</h1>
                <p>Training: <strong><?php echo htmlspecialchars($Training['title']); ?></strong></p>
            </div>
            <div class="d-flex gap-10">
                <a href="lesson_form.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">Add New Lesson</a>
                <a href="trainings.php" class="btn btn-secondary">Back to Training</a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Lessons for <?php echo htmlspecialchars($Training['title']); ?>
            </div>
            <div class="card-body">
                <?php if (empty($lessons)): ?>
                    <p>No lessons found. <a href="lesson_form.php?course_id=<?php echo $course_id; ?>">Create your first lesson</a>.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Title</th>
                                    <th>Questions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lessons as $lesson): ?>
                                    <tr>
                                        <td><?php echo $lesson['position']; ?></td>
                                        <td><?php echo htmlspecialchars($lesson['title']); ?></td>
                                        <td><?php echo $lesson['question_count']; ?> question<?php echo $lesson['question_count'] != 1 ? 's' : ''; ?></td>
                                        <td>
                                            <div class="d-flex gap-10">
                                                <a href="lesson_form.php?id=<?php echo $lesson['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                                <?php
                                                // Determine button color based on question and answer count
                                                if ($lesson['question_count'] == 0) {
                                                    // No questions - grey/disabled button
                                                    echo '<span class="btn btn-sm" style="background-color: #ccc; color: #666; cursor: not-allowed;">View Answers</span>';
                                                } elseif ($lesson['answer_count'] > 0) {
                                                    // Has answers - green button
                                                    echo '<a href="lesson_answers.php?lesson_id=' . $lesson['id'] . '" class="btn btn-sm btn-success">View Answers</a>';
                                                } else {
                                                    // Has questions but no answers - blue button
                                                    echo '<a href="lesson_answers.php?lesson_id=' . $lesson['id'] . '" class="btn btn-sm btn-primary">View Answers</a>';
                                                }
                                                ?>
                                                <a href="lessons.php?course_id=<?php echo $course_id; ?>&delete=<?php echo $lesson['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this lesson? This will also delete all associated questions and answers.')">Delete</a>
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
