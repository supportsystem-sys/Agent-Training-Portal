<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isLearner()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();

// Get user's assigned Training with lesson counts
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT c.*, u.name as instructor_name,
           COUNT(l.id) as lesson_count,
           COUNT(q.id) as quiz_count,
           ta.status as assignment_status,
           ta.assigned_at
    FROM training_assignments ta
    JOIN courses c ON ta.course_id = c.id
    LEFT JOIN users u ON c.created_by = u.id
    LEFT JOIN lessons l ON c.id = l.course_id
    LEFT JOIN quizzes q ON l.id = q.lesson_id
    WHERE ta.user_id = ?
    GROUP BY c.id
    ORDER BY ta.assigned_at DESC
");
$stmt->execute([$user_id]);
$Training = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assigned Training - LMS</title>
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
        <h1>My Assigned Training</h1>
        <p>Training courses that have been assigned to you</p>

        <?php if (empty($Training)): ?>
            <div class="alert alert-info">
                You don't have any Training assigned yet. Contact your administrator to get assigned to Training courses.
            </div>
        <?php else: ?>
            <div class="Training-grid">
                <?php foreach ($Training as $Training): ?>
                    <div class="Training-card">
                        <div class="Training-header">
                            <div class="Training-title"><?php echo htmlspecialchars($Training['title']); ?></div>
                            <div class="Training-description">
                                <?php echo htmlspecialchars($Training['description']); ?>
                            </div>
                        </div>
                        <div class="Training-footer">
                            <div class="Training-stats">
                                <small>
                                    <strong><?php echo $Training['lesson_count']; ?></strong> lessons • 
                                    <strong><?php echo $Training['quiz_count']; ?></strong> quizzes
                                </small>
                                <br>
                                <small>By <?php echo htmlspecialchars($Training['instructor_name']); ?></small>
                                <br>
                                <small>
                                    Status: <strong><?php echo ucfirst($Training['assignment_status']); ?></strong>
                                    <br>Assigned: <?php echo formatDate($Training['assigned_at']); ?>
                                </small>
                            </div>
                            <a href="training.php?id=<?php echo $Training['id']; ?>" class="btn btn-primary">
                                <?php echo $Training['assignment_status'] == 'assigned' ? 'Start Training' : 'Continue Training'; ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="footer-content">
            Copyright Halcom Group 2025
        </div>
    </footer>
</body>
</html>
