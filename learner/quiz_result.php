<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isLearner()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get attempt information
$attempt_id = $_GET['attempt_id'] ?? null;
if (!$attempt_id || !is_numeric($attempt_id)) {
    redirect('dashboard.php');
}

$stmt = $pdo->prepare("
    SELECT qa.*, q.title as quiz_title, q.passing_score,
           l.title as lesson_title, c.title as Training_title
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN lessons l ON q.lesson_id = l.id
    JOIN courses c ON l.course_id = c.id
    WHERE qa.id = ? AND qa.user_id = ?
");
$stmt->execute([$attempt_id, $user_id]);
$attempt = $stmt->fetch();

if (!$attempt) {
    redirect('dashboard.php');
}

// Get questions and answers for this attempt
$stmt = $pdo->prepare("
    SELECT q.*, a.option_id, a.answer_text, a.is_correct,
           qo.option_text, qo.is_correct as option_is_correct
    FROM questions q
    LEFT JOIN answers a ON q.id = a.question_id AND a.attempt_id = ?
    LEFT JOIN question_options qo ON a.option_id = qo.id
    WHERE q.quiz_id = ?
    ORDER BY q.created_at
");
$stmt->execute([$attempt_id, $attempt['quiz_id']]);
$results = $stmt->fetchAll();

// Organize questions and answers
$questions = [];
foreach ($results as $row) {
    if (!isset($questions[$row['id']])) {
        $questions[$row['id']] = [
            'id' => $row['id'],
            'question_text' => $row['question_text'],
            'type' => $row['type'],
            'user_answer' => null,
            'is_correct' => false,
            'correct_answer' => null
        ];
    }
    
    if ($row['option_text'] !== null || $row['answer_text'] !== null) {
        $questions[$row['id']]['user_answer'] = $row['option_text'] ?: $row['answer_text'];
        $questions[$row['id']]['is_correct'] = $row['is_correct'];
    }
}

// Get all options for each question to show correct answers
$stmt = $pdo->prepare("
    SELECT q.id as question_id, qo.option_text, qo.is_correct
    FROM questions q
    JOIN question_options qo ON q.id = qo.question_id
    WHERE q.quiz_id = ?
    ORDER BY q.created_at, qo.created_at
");
$stmt->execute([$attempt['quiz_id']]);
$all_options = $stmt->fetchAll();

// Organize correct answers
foreach ($all_options as $option) {
    if (!isset($questions[$option['question_id']]['correct_answer'])) {
        $questions[$option['question_id']]['correct_answer'] = [];
    }
    if ($option['is_correct']) {
        $questions[$option['question_id']]['correct_answer'][] = $option['option_text'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result - <?php echo htmlspecialchars($attempt['quiz_title']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">
    <vapi-widget
  public-key="68fd39ef-c1b8-40f8-bfdb-d19626c0e586"
  assistant-id="eba6366f-d9ed-4c0c-96f1-6db69ce72686"
  mode="voice"
  theme="dark"
  base-bg-color="#000000"
  accent-color="#14B8A6"
  cta-button-color="#000000"
  cta-button-text-color="#ffffff"
  border-radius="large"
  size="full"
  position="bottom-right"
  title="Speak with Script Trainer"
  start-button-text="Start"
  end-button-text="End Call"
  voice-empty-message="Anyone, How can I help ?"
  chat-first-message="Hey, How can I help you today?"
  chat-placeholder="Type your message..."
  voice-show-transcript="true"
  consent-required="true"
  consent-title="Terms and conditions"
  consent-content="By clicking "Agree," and each time I interact with this AI agent, I consent to the recording, storage, and sharing of my communications with third-party service providers, and as otherwise described in our Terms of Service."
  consent-storage-key="vapi_widget_consent"
></vapi-widget>

<script src="https://unpkg.com/@vapi-ai/client-sdk-react/dist/embed/widget.umd.js" async type="text/javascript"></script>
    <div class="header learner-header">
        <div class="header-content">
            <div class="header-left">
                <img src="../assets/images/logo.png" alt="Halcom Marketing Logo" class="header-logo">
            </div>
            <div class="header-center">
                <h1 class="welcome-text">Welcome to Halcom Training Module</h1>
            </div>
            <div class="header-right">
                <div class="nav">
                    <a href="dashboard.php">Dashboard</a>
                          <a href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="d-flex justify-between align-center mb-20">
            <div>
                <h1>Quiz Result</h1>
                <p>Training: <strong><?php echo htmlspecialchars($attempt['Training_title']); ?></strong></p>
                <p>Lesson: <strong><?php echo htmlspecialchars($attempt['lesson_title']); ?></strong></p>
                <p>Quiz: <strong><?php echo htmlspecialchars($attempt['quiz_title']); ?></strong></p>
            </div>
            <a href="lesson.php?id=<?php echo $attempt['lesson_id']; ?>" class="btn btn-secondary">Back to Lesson</a>
        </div>

        <!-- Score Summary -->
        <div class="card">
            <div class="card-header">
                Your Score
            </div>
            <div class="card-body text-center">
                <div class="stat-number <?php echo $attempt['passed'] ? 'text-success' : 'text-danger'; ?>">
                    <?php echo $attempt['score']; ?>%
                </div>
                <h3 class="<?php echo $attempt['passed'] ? 'text-success' : 'text-danger'; ?>">
                    <?php echo $attempt['passed'] ? 'PASSED' : 'FAILED'; ?>
                </h3>
                <p>Passing Score: <?php echo $attempt['passing_score']; ?>%</p>
                <p>Attempted on: <?php echo formatDateTime($attempt['attempted_at']); ?></p>
            </div>
        </div>

        <!-- Detailed Results -->
        <div class="card">
            <div class="card-header">
                Question Review
            </div>
            <div class="card-body">
                <?php foreach ($questions as $question): ?>
                    <div class="question-card">
                        <div class="question-title">
                            <?php echo htmlspecialchars($question['question_text']); ?>
                        </div>
                        
                        <div class="mt-20">
                            <div class="d-flex gap-10 align-center mb-10">
                                <strong>Your Answer:</strong>
                                <?php if ($question['user_answer']): ?>
                                    <span class="btn btn-sm <?php echo $question['is_correct'] ? 'btn-success' : 'btn-danger'; ?>">
                                        <?php echo htmlspecialchars($question['user_answer']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="btn btn-sm btn-secondary">Not answered</span>
                                <?php endif; ?>
                                
                                <?php if ($question['is_correct']): ?>
                                    <span class="btn btn-sm btn-success">✓ Correct</span>
                                <?php else: ?>
                                    <span class="btn btn-sm btn-danger">✗ Incorrect</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!$question['is_correct'] && $question['correct_answer']): ?>
                                <div>
                                    <strong>Correct Answer:</strong>
                                    <?php foreach ($question['correct_answer'] as $correct): ?>
                                        <span class="btn btn-sm btn-success"><?php echo htmlspecialchars($correct); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Actions -->
        <div class="d-flex justify-between align-center mt-20">
            <a href="lesson.php?id=<?php echo $attempt['lesson_id']; ?>" class="btn btn-primary">
                Back to Lesson
            </a>
            <?php if (!$attempt['passed']): ?>
                <a href="quiz.php?id=<?php echo $attempt['quiz_id']; ?>" class="btn btn-secondary">
                    Retake Quiz
                </a>
            <?php endif; ?>
        </div>
    </div>
    <footer class="footer">
        <div class="footer-content">
            Copyright Halcom Group 2025
        </div>
    </footer>
</body>
</html>
