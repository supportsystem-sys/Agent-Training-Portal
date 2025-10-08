<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isLearner()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Log page view activity
updateSessionActivity($user_id);

// Get quiz information
$quiz_id = $_GET['id'] ?? null;
if (!$quiz_id || !is_numeric($quiz_id)) {
    redirect('trainings.php');
}

$stmt = $pdo->prepare("
    SELECT q.*, l.title as lesson_title, c.title as Training_title
    FROM quizzes q
    JOIN lessons l ON q.lesson_id = l.id
    JOIN courses c ON l.course_id = c.id
    JOIN training_assignments ta ON c.id = ta.course_id
    WHERE q.id = ? AND ta.user_id = ?
");
$stmt->execute([$quiz_id, $user_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    redirect('trainings.php');
}

// Log quiz view activity
logUserActivity($user_id, 'quiz_attempt', $_SERVER['REQUEST_URI'] ?? '', $quiz['title'], null, $quiz_id);

// Get questions with options
$stmt = $pdo->prepare("
    SELECT q.*, qo.id as option_id, qo.option_text, qo.is_correct
    FROM questions q
    LEFT JOIN question_options qo ON q.id = qo.question_id
    WHERE q.quiz_id = ?
    ORDER BY q.created_at, qo.created_at
");
$stmt->execute([$quiz_id]);
$results = $stmt->fetchAll();

// Organize questions and options
$questions = [];
foreach ($results as $row) {
    if (!isset($questions[$row['id']])) {
        $questions[$row['id']] = [
            'id' => $row['id'],
            'question_text' => $row['question_text'],
            'type' => $row['type'],
            'options' => []
        ];
    }
    
    if ($row['option_id']) {
        $questions[$row['id']]['options'][] = [
            'id' => $row['option_id'],
            'option_text' => $row['option_text'],
            'is_correct' => $row['is_correct']
        ];
    }
}

$error = '';
$success = '';

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Create quiz attempt
        $stmt = $pdo->prepare("
            INSERT INTO quiz_attempts (user_id, quiz_id, attempted_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$user_id, $quiz_id]);
        $attempt_id = $pdo->lastInsertId();
        
        // Process answers
        foreach ($_POST['answers'] as $question_id => $answer) {
            $question_id = (int)$question_id;
            
            if (isset($questions[$question_id])) {
                $question = $questions[$question_id];
                
                if ($question['type'] === 'mcq' && is_numeric($answer)) {
                    // MCQ answer
                    $option_id = (int)$answer;
                    
                    // Find the selected option
                    $selected_option = null;
                    foreach ($question['options'] as $option) {
                        if ($option['id'] == $option_id) {
                            $selected_option = $option;
                            break;
                        }
                    }
                    
                    if ($selected_option) {
                        $stmt = $pdo->prepare("
                            INSERT INTO answers (attempt_id, question_id, option_id, is_correct)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $attempt_id,
                            $question_id,
                            $option_id,
                            $selected_option['is_correct']
                        ]);
                    }
                } elseif ($question['type'] === 'essay' && !empty($answer)) {
                    // Essay answer
                    $stmt = $pdo->prepare("
                        INSERT INTO answers (attempt_id, question_id, answer_text, is_correct)
                        VALUES (?, ?, ?, 0)
                    ");
                    $stmt->execute([$attempt_id, $question_id, $answer]);
                }
            }
        }
        
        // Calculate and save score
        $result = markQuizAttempt($attempt_id, $quiz_id);
        
        $success = "Quiz submitted successfully! Your score: {$result['score']}% - " . 
                   ($result['passed'] ? 'PASSED' : 'FAILED');
        
        // Redirect to results
        redirect("quiz_result.php?attempt_id=$attempt_id");
        
    } catch (Exception $e) {
        error_log("Quiz submission error: " . $e->getMessage());
        $error = "An error occurred while submitting the quiz. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title']); ?> - LMS</title>
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
                <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
                <p>Training: <strong><?php echo htmlspecialchars($quiz['Training_title']); ?></strong></p>
                <p>Lesson: <strong><?php echo htmlspecialchars($quiz['lesson_title']); ?></strong></p>
                <p>Passing Score: <strong><?php echo $quiz['passing_score']; ?>%</strong></p>
            </div>
            <a href="lesson.php?id=<?php echo $quiz['lesson_id']; ?>" class="btn btn-secondary">Back to Lesson</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="quiz-container">
            <form method="POST" id="quizForm">
                <?php foreach ($questions as $question): ?>
                    <div class="question-card">
                        <div class="question-title">
                            <?php echo htmlspecialchars($question['question_text']); ?>
                        </div>
                        
                        <?php if ($question['type'] === 'mcq'): ?>
                            <?php foreach ($question['options'] as $option): ?>
                                <div class="option">
                                    <label>
                                        <input type="radio" 
                                               name="answers[<?php echo $question['id']; ?>]" 
                                               value="<?php echo $option['id']; ?>" required>
                                        <?php echo htmlspecialchars($option['option_text']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php elseif ($question['type'] === 'essay'): ?>
                            <div class="form-group">
                                <textarea name="answers[<?php echo $question['id']; ?>]" 
                                          rows="4" required 
                                          placeholder="Enter your answer here..."></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="d-flex justify-between align-center mt-20">
                    <a href="lesson.php?id=<?php echo $quiz['lesson_id']; ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Submit Quiz</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById('quizForm').addEventListener('submit', function(e) {
            const requiredInputs = this.querySelectorAll('input[required], textarea[required]');
            let allAnswered = true;
            
            requiredInputs.forEach(function(input) {
                if (!input.value.trim()) {
                    allAnswered = false;
                }
            });
            
            if (!allAnswered) {
                e.preventDefault();
                alert('Please answer all questions before submitting.');
            }
        });
    </script>
    <footer class="footer">
        <div class="footer-content">
            Copyright Halcom Group 2025
        </div>
    </footer>
</body>
</html>
