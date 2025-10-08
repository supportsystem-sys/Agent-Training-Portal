<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// Get filter values
$selected_training = $_GET['training_id'] ?? 'all';
$selected_learner = $_GET['learner_id'] ?? 'all';

// Get all trainings for filter dropdown
$stmt = $pdo->query("SELECT id, title FROM courses ORDER BY title");
$all_trainings = $stmt->fetchAll();

// Get all learners for filter dropdown
$stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'learner' ORDER BY name");
$all_learners = $stmt->fetchAll();

// Build dynamic query based on filters
$query = "
    SELECT 
        c.id as course_id,
        c.title as course_title,
        l.id as lesson_id,
        l.title as lesson_title,
        l.position as lesson_position,
        lq.id as question_id,
        lq.question_text,
        lq.position as question_position,
        COUNT(DISTINCT la.id) as answer_count
    FROM courses c
    JOIN lessons l ON c.id = l.course_id
    JOIN lesson_questions lq ON l.id = lq.lesson_id
    LEFT JOIN lesson_answers la ON lq.id = la.lesson_question_id
";

$conditions = [];
$params = [];

// Apply training filter
if ($selected_training !== 'all' && is_numeric($selected_training)) {
    $conditions[] = "c.id = ?";
    $params[] = $selected_training;
}

// Apply learner filter for answer count
if ($selected_learner !== 'all' && is_numeric($selected_learner)) {
    $conditions[] = "(la.user_id = ? OR la.user_id IS NULL)";
    $params[] = $selected_learner;
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " GROUP BY c.id, l.id, lq.id ORDER BY c.title, l.position, lq.position";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$all_data = $stmt->fetchAll();

// Organize data by training and lesson
$training_data = [];
foreach ($all_data as $row) {
    $course_id = $row['course_id'];
    $lesson_id = $row['lesson_id'];
    
    if (!isset($training_data[$course_id])) {
        $training_data[$course_id] = [
            'title' => $row['course_title'],
            'lessons' => []
        ];
    }
    
    if (!isset($training_data[$course_id]['lessons'][$lesson_id])) {
        $training_data[$course_id]['lessons'][$lesson_id] = [
            'title' => $row['lesson_title'],
            'position' => $row['lesson_position'],
            'questions' => []
        ];
    }
    
    $training_data[$course_id]['lessons'][$lesson_id]['questions'][] = [
        'id' => $row['question_id'],
        'text' => $row['question_text'],
        'position' => $row['question_position'],
        'answer_count' => $row['answer_count']
    ];
}

// Get answers for a specific question (with optional learner filter)
function getQuestionAnswers($pdo, $question_id, $learner_id = 'all') {
    $query = "
        SELECT la.*, u.name as user_name, u.email
        FROM lesson_answers la
        JOIN users u ON la.user_id = u.id
        WHERE la.lesson_question_id = ?
    ";
    
    $params = [$question_id];
    
    if ($learner_id !== 'all' && is_numeric($learner_id)) {
        $query .= " AND la.user_id = ?";
        $params[] = $learner_id;
    }
    
    $query .= " ORDER BY la.submitted_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Questions & Answers - Halcom Marketing Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .training-section {
            margin-bottom: 40px;
        }
        .lesson-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .question-block {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #14B8A6;
            border-radius: 4px;
        }
        .question-header {
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .answer-block {
            background: #f0fdf4;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            border-left: 3px solid #10b981;
        }
        .answer-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .user-name {
            font-weight: 600;
            color: #059669;
        }
        .answer-time {
            color: #666;
            font-size: 12px;
        }
        .answer-text {
            color: #333;
            line-height: 1.6;
        }
        .no-answers {
            color: #999;
            font-style: italic;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        .section-header {
            background: linear-gradient(135deg, #14B8A6 0%, #0d9488 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .lesson-header {
            background: #e0f2f1;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-weight: 600;
            color: #0d9488;
        }
        .answer-count-badge {
            background: #10b981;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
        }
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        .filter-group {
            flex: 1;
            min-width: 250px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .filter-actions {
            display: flex;
            gap: 10px;
        }
    </style>
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
                <h1>All Questions & Answers</h1>
                <p>View all lesson questions and learner submissions across all trainings</p>
            </div>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h3 style="margin-top: 0; margin-bottom: 20px; color: #14B8A6;">🔍 Filter Questions & Answers</h3>
            <form method="GET" action="all_questions.php">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="training_id">Training:</label>
                        <select name="training_id" id="training_id">
                            <option value="all" <?php echo $selected_training === 'all' ? 'selected' : ''; ?>>All Trainings</option>
                            <?php foreach ($all_trainings as $training): ?>
                                <option value="<?php echo $training['id']; ?>" 
                                        <?php echo $selected_training == $training['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($training['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="learner_id">Learner:</label>
                        <select name="learner_id" id="learner_id">
                            <option value="all" <?php echo $selected_learner === 'all' ? 'selected' : ''; ?>>All Learners</option>
                            <?php foreach ($all_learners as $learner): ?>
                                <option value="<?php echo $learner['id']; ?>" 
                                        <?php echo $selected_learner == $learner['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($learner['name']); ?> (<?php echo htmlspecialchars($learner['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="all_questions.php" class="btn btn-secondary">Clear Filters</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Active Filters Info -->
        <?php if ($selected_training !== 'all' || $selected_learner !== 'all'): ?>
            <div class="alert alert-info" style="background: #e0f2f1; border-left: 4px solid #14B8A6; margin-bottom: 20px;">
                <strong>Active Filters:</strong>
                <?php if ($selected_training !== 'all'): ?>
                    <?php
                    $training_name = '';
                    foreach ($all_trainings as $t) {
                        if ($t['id'] == $selected_training) {
                            $training_name = $t['title'];
                            break;
                        }
                    }
                    ?>
                    <span style="background: #14B8A6; color: white; padding: 4px 10px; border-radius: 12px; margin: 0 5px; display: inline-block;">
                        Training: <?php echo htmlspecialchars($training_name); ?>
                    </span>
                <?php endif; ?>
                <?php if ($selected_learner !== 'all'): ?>
                    <?php
                    $learner_name = '';
                    foreach ($all_learners as $l) {
                        if ($l['id'] == $selected_learner) {
                            $learner_name = $l['name'];
                            break;
                        }
                    }
                    ?>
                    <span style="background: #059669; color: white; padding: 4px 10px; border-radius: 12px; margin: 0 5px; display: inline-block;">
                        Learner: <?php echo htmlspecialchars($learner_name); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($training_data)): ?>
            <div class="alert alert-info">
                <?php if ($selected_training !== 'all' || $selected_learner !== 'all'): ?>
                    No questions found matching the selected filters.
                <?php else: ?>
                    No questions have been added to any lessons yet.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($training_data as $course_id => $training): ?>
                <div class="training-section">
                    <div class="section-header">
                        <h2 style="margin: 0; font-size: 20px;">📚 <?php echo htmlspecialchars($training['title']); ?></h2>
                    </div>
                    
                    <?php foreach ($training['lessons'] as $lesson_id => $lesson): ?>
                        <div class="lesson-section">
                            <div class="lesson-header">
                                📖 Lesson <?php echo $lesson['position']; ?>: <?php echo htmlspecialchars($lesson['title']); ?>
                            </div>
                            
                            <?php foreach ($lesson['questions'] as $question): ?>
                                <div class="question-block">
                                    <div class="question-header">
                                        ❓ Question <?php echo $question['position']; ?>: 
                                        <?php echo nl2br(htmlspecialchars($question['text'])); ?>
                                        <span class="answer-count-badge">
                                            <?php echo $question['answer_count']; ?> answer<?php echo $question['answer_count'] != 1 ? 's' : ''; ?>
                                        </span>
                                    </div>
                                    
                                    <?php 
                                    $answers = getQuestionAnswers($pdo, $question['id'], $selected_learner); 
                                    if (empty($answers)):
                                    ?>
                                        <div class="no-answers">
                                            <?php if ($selected_learner !== 'all'): ?>
                                                This learner has not submitted an answer yet.
                                            <?php else: ?>
                                                No learners have submitted answers yet.
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="margin-top: 15px;">
                                            <strong style="color: #059669; margin-bottom: 10px; display: block;">Learner Responses:</strong>
                                            <?php foreach ($answers as $answer): ?>
                                                <div class="answer-block">
                                                    <div class="answer-meta">
                                                        <span class="user-name">
                                                            👤 <?php echo htmlspecialchars($answer['user_name']); ?>
                                                            <span style="color: #666; font-weight: normal; font-size: 12px;">
                                                                (<?php echo htmlspecialchars($answer['email']); ?>)
                                                            </span>
                                                        </span>
                                                        <span class="answer-time">
                                                            📅 <?php echo date('M d, Y - g:i A', strtotime($answer['submitted_at'])); ?>
                                                        </span>
                                                    </div>
                                                    <div class="answer-text">
                                                        <?php echo nl2br(htmlspecialchars($answer['answer_text'])); ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>

