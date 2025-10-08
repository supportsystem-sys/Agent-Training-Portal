<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isLearner()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get user's detailed statistics
$stats = [];

// Total Training enrolled
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT l.course_id) as total
    FROM lessons l
    JOIN progress p ON l.id = p.lesson_id
    WHERE p.user_id = ?
");
$stmt->execute([$user_id]);
$stats['Training_enrolled'] = $stmt->fetch()['total'];

// Total lessons completed
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM progress WHERE user_id = ? AND completed = 1");
$stmt->execute([$user_id]);
$stats['lessons_completed'] = $stmt->fetch()['total'];

// Total lessons in progress
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM progress WHERE user_id = ? AND completed = 0 AND watched_seconds > 0");
$stmt->execute([$user_id]);
$stats['lessons_in_progress'] = $stmt->fetch()['total'];

// Total quiz attempts
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM quiz_attempts WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats['quiz_attempts'] = $stmt->fetch()['total'];

// Passed quizzes
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM quiz_attempts WHERE user_id = ? AND passed = 1");
$stmt->execute([$user_id]);
$stats['passed_quizzes'] = $stmt->fetch()['total'];

// Average quiz score
$stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM quiz_attempts WHERE user_id = ? AND score > 0");
$stmt->execute([$user_id]);
$stats['avg_score'] = round($stmt->fetch()['avg_score'] ?? 0, 1);

// Get all quiz attempts with details
$stmt = $pdo->prepare("
    SELECT qa.*, q.title as quiz_title, q.passing_score,
           l.title as lesson_title, c.title as Training_title
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN lessons l ON q.lesson_id = l.id
    JOIN courses c ON l.course_id = c.id
    WHERE qa.user_id = ?
    ORDER BY qa.attempted_at DESC
");
$stmt->execute([$user_id]);
$quiz_attempts = $stmt->fetchAll();

// Get Training progress details
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(l.id) as total_lessons,
           COUNT(p.lesson_id) as completed_lessons,
           ROUND((COUNT(p.lesson_id) / COUNT(l.id)) * 100, 1) as progress_percentage
    FROM courses c
    LEFT JOIN lessons l ON c.id = l.course_id
    LEFT JOIN progress p ON l.id = p.lesson_id AND p.user_id = ? AND p.completed = 1
    GROUP BY c.id
    HAVING completed_lessons > 0 OR c.id IN (
        SELECT DISTINCT l2.course_id 
        FROM lessons l2 
        JOIN progress p2 ON l2.id = p2.lesson_id 
        WHERE p2.user_id = ?
    )
    ORDER BY progress_percentage DESC, c.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$Training_progress = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - LMS</title>
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
        <h1>My Profile</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>

        <!-- Personal Information -->
        <div class="card">
            <div class="card-header">
                Personal Information
            </div>
            <div class="card-body">
                <div class="d-flex justify-between align-center mb-20">
                    <div class="d-flex gap-20">
                        <div>
                            <strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </div>
                        <div>
                            <strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?>
                        </div>
                        <div>
                            <strong>Member Since:</strong> <?php echo formatDate($_SESSION['created_at'] ?? date('Y-m-d')); ?>
                        </div>
                    </div>
                    <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="dashboard">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['Training_enrolled']; ?></div>
                <div class="stat-label">Training Enrolled</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['lessons_completed']; ?></div>
                <div class="stat-label">Lessons Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['lessons_in_progress']; ?></div>
                <div class="stat-label">Lessons In Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['quiz_attempts']; ?></div>
                <div class="stat-label">Quiz Attempts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['passed_quizzes']; ?></div>
                <div class="stat-label">Quizzes Passed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['avg_score']; ?>%</div>
                <div class="stat-label">Average Score</div>
            </div>
        </div>

        <!-- Training Progress -->
        <div class="card">
            <div class="card-header">
                Training Progress
            </div>
            <div class="card-body">
                <?php if (empty($Training_progress)): ?>
                    <p>You haven't started any Training yet. <a href="trainings.php">Browse available Training</a> to get started!</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Training</th>
                                    <th>Progress</th>
                                    <th>Lessons</th>
                                    <th>Completed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($Training_progress as $Training): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($Training['title']); ?></strong><br>
                                            <small><?php echo htmlspecialchars(substr($Training['description'], 0, 100)) . (strlen($Training['description']) > 100 ? '...' : ''); ?></small>
                                        </td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar <?php echo $Training['progress_percentage'] >= 100 ? 'success' : ''; ?>" 
                                                     style="width: <?php echo $Training['progress_percentage']; ?>%"></div>
                                            </div>
                                            <small><?php echo $Training['progress_percentage']; ?>%</small>
                                        </td>
                                        <td><?php echo $Training['total_lessons']; ?></td>
                                        <td><?php echo $Training['completed_lessons']; ?></td>
                                        <td>
                                            <a href="trainings.php?id=<?php echo $Training['id']; ?>" class="btn btn-sm btn-primary">
                                                View Training
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

        <!-- Quiz History -->
        <div class="card">
            <div class="card-header">
                Quiz History
            </div>
            <div class="card-body">
                <?php if (empty($quiz_attempts)): ?>
                    <p>No quiz attempts yet. Complete some lessons and take quizzes to track your progress!</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Quiz</th>
                                    <th>Training</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quiz_attempts as $attempt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($attempt['quiz_title']); ?></td>
                                        <td><?php echo htmlspecialchars($attempt['Training_title']); ?></td>
                                        <td><?php echo $attempt['score']; ?>%</td>
                                        <td>
                                            <span class="btn btn-sm <?php echo $attempt['passed'] ? 'btn-success' : 'btn-danger'; ?>">
                                                <?php echo $attempt['passed'] ? 'Passed' : 'Failed'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDateTime($attempt['attempted_at']); ?></td>
                                        <td>
                                            <a href="quiz_result.php?attempt_id=<?php echo $attempt['id']; ?>" class="btn btn-sm btn-primary">
                                                View Result
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
