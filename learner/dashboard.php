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
logUserActivity($user_id, 'page_view', $_SERVER['REQUEST_URI'] ?? '', 'Dashboard');

// Get user's assigned Training progress
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(l.id) as total_lessons,
           COUNT(p.lesson_id) as completed_lessons,
           ROUND((COUNT(p.lesson_id) / COUNT(l.id)) * 100, 1) as progress_percentage,
           ta.status as assignment_status,
           ta.assigned_at
    FROM training_assignments ta
    JOIN courses c ON ta.course_id = c.id
    LEFT JOIN lessons l ON c.id = l.course_id
    LEFT JOIN progress p ON l.id = p.lesson_id AND p.user_id = ? AND p.completed = 1
    WHERE ta.user_id = ?
    GROUP BY c.id
    ORDER BY progress_percentage DESC, c.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$Training_progress = $stmt->fetchAll();

// Get recent quiz attempts
$stmt = $pdo->prepare("
    SELECT qa.*, q.title as quiz_title, l.title as lesson_title, c.title as Training_title
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN lessons l ON q.lesson_id = l.id
    JOIN courses c ON l.course_id = c.id
    WHERE qa.user_id = ?
    ORDER BY qa.attempted_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_attempts = $stmt->fetchAll();

// Get overall statistics
$stats = [];

// Total Training assigned
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM training_assignments
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats['Training_assigned'] = $stmt->fetch()['total'];

// Total lessons completed
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM progress WHERE user_id = ? AND completed = 1");
$stmt->execute([$user_id]);
$stats['lessons_completed'] = $stmt->fetch()['total'];

// Total quiz attempts
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM quiz_attempts WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats['quiz_attempts'] = $stmt->fetch()['total'];

// Average quiz score
$stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM quiz_attempts WHERE user_id = ? AND score > 0");
$stmt->execute([$user_id]);
$stats['avg_score'] = round($stmt->fetch()['avg_score'] ?? 0, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LMS</title>
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
                <!-- Training Progress -->
        <div class="card" style="margin-top:5%;">
            <div class="card-header training-progress-header">
                <h2 class="training-progress-title">Verticals and Campaign Training</h2>
            </div>
            <div class="card-body">
                <?php if (empty($Training_progress)): ?>
                    <p>You don't have any Training assigned yet. Contact your administrator to get assigned to Training.</p>
                <?php else: ?>
                    <div class="training-list">
                        <?php foreach ($Training_progress as $index => $Training): ?>
                            <div class="training-card">
                                <div class="training-card-header">
                                    <div class="training-number">TRAINING : <?php echo $index + 1; ?></div>
                                    <span class="header-progress">PROGRESS</span>
                                    <span class="header-actions">ACTIONS</span>
                                </div>
                                <div class="training-card-content">
                                    <div class="training-info">
                                        <div class="training-title"><?php echo htmlspecialchars($Training['title']); ?></div>
                                        <div class="training-description"><?php echo htmlspecialchars($Training['description']); ?></div>
                                    </div>
                                    <div class="training-progress">
                                        <div class="progress">
                                            <div class="progress-bar <?php echo $Training['progress_percentage'] >= 100 ? 'success' : ''; ?>" 
                                                 style="width: <?php echo $Training['progress_percentage']; ?>%"></div>
                                        </div>
                                        <div class="progress-text"><?php echo $Training['progress_percentage']; ?>%</div>
                                    </div>
                                    <div class="training-actions">
                                        <a href="training.php?id=<?php echo $Training['id']; ?>" class="btn btn-primary">
                                            <?php echo $Training['completed_lessons'] > 0 ? 'Continue' : 'Start'; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</body>
</html>
