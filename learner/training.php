<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isLearner()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get Training information
$course_id = $_GET['id'] ?? null;
if (!$course_id || !is_numeric($course_id)) {
    redirect('trainings.php');
}

$stmt = $pdo->prepare("
    SELECT c.*, u.name as instructor_name, ta.status as assignment_status
    FROM training_assignments ta
    JOIN courses c ON ta.course_id = c.id
    LEFT JOIN users u ON c.created_by = u.id
    WHERE c.id = ? AND ta.user_id = ?
");
$stmt->execute([$course_id, $user_id]);
$Training = $stmt->fetch();

if (!$Training) {
    redirect('trainings.php');
}

// Get lessons with progress
$lessons = getTrainingProgress($user_id, $course_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($Training['title']); ?> - LMS</title>
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
        <div class="d-flex justify-end mb-20">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        <div class="training-title-section">
            <h1 class="training-title-centered"><?php echo htmlspecialchars($Training['title']); ?></h1>
        </div>


        <!-- Lessons -->
        <div class="card">
            <div class="card-header">
                Training Lessons
            </div>
            <div class="card-body">
                <?php if (empty($lessons)): ?>
                    <p>No lessons available for this Training.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="training-lessons-table">
                            <thead>
                                <tr>
                                    <th>Lesson</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lessons as $lesson): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($lesson['title']); ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($lesson['completed']): ?>
                                                <span class="btn btn-sm btn-success">Completed</span>
                                            <?php elseif ($lesson['watched_seconds'] > 0): ?>
                                                <span class="btn btn-sm btn-primary">In Progress</span>
                                            <?php else: ?>
                                                <span class="btn btn-sm btn-secondary">Not Started</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="lesson.php?id=<?php echo $lesson['id']; ?>" class="btn btn-sm btn-primary">
                                                <?php echo $lesson['watched_seconds'] > 0 ? 'Continue' : 'Start'; ?>
                                            </a>
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

    <footer class="footer">
        <div class="footer-content">
            Copyright Halcom Group 2025
        </div>
    </footer>
</body>
</html>
