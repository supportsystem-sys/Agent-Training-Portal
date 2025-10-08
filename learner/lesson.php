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

// Get lesson information
$lesson_id = $_GET['id'] ?? null;
if (!$lesson_id || !is_numeric($lesson_id)) {
    redirect('trainings.php');
}

$stmt = $pdo->prepare("
    SELECT l.*, c.title as Training_title, c.id as course_id, ta.status as assignment_status
    FROM lessons l
    JOIN courses c ON l.course_id = c.id
    JOIN training_assignments ta ON c.id = ta.course_id
    WHERE l.id = ? AND ta.user_id = ?
");
$stmt->execute([$lesson_id, $user_id]);
$lesson = $stmt->fetch();

if (!$lesson) {
    redirect('trainings.php');
}

// Handle question answer submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_answers'])) {
    try {
        if (!empty($_POST['answers'])) {
            $stmt = $pdo->prepare("
                INSERT INTO lesson_answers (user_id, lesson_question_id, answer_text, submitted_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE answer_text = VALUES(answer_text), submitted_at = NOW()
            ");
            
            foreach ($_POST['answers'] as $question_id => $answer_text) {
                $answer_text = trim($answer_text);
                if (!empty($answer_text)) {
                    $stmt->execute([$user_id, $question_id, $answer_text]);
                }
            }
            
            $success = "Your answers have been submitted successfully!";
            
            // Log answer submission activity
            logUserActivity($user_id, 'lesson_answers_submitted', $_SERVER['REQUEST_URI'] ?? '', $lesson['title'], $lesson_id);
        }
    } catch (Exception $e) {
        $error = "Failed to submit answers. Please try again.";
    }
}

// Handle lesson completion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_complete'])) {
    try {
        // Mark lesson as completed
        $stmt = $pdo->prepare("
            INSERT INTO progress (user_id, lesson_id, watched_seconds, completed, created_at, updated_at)
            VALUES (?, ?, 0, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE completed = 1, updated_at = NOW()
        ");
        $stmt->execute([$user_id, $lesson_id]);
        
        // Log lesson completion activity
        logUserActivity($user_id, 'lesson_complete', $_SERVER['REQUEST_URI'] ?? '', $lesson['title'], $lesson_id);
        
        $success = "Lesson marked as complete!";
    } catch (Exception $e) {
        $error = "Failed to mark lesson as complete. Please try again.";
    }
}

// Log lesson view activity
logUserActivity($user_id, 'lesson_view', $_SERVER['REQUEST_URI'] ?? '', $lesson['title'], $lesson_id);

// Get user's progress for this lesson
$progress = getProgress($user_id, $lesson_id);

// Get lesson questions
$stmt = $pdo->prepare("SELECT * FROM lesson_questions WHERE lesson_id = ? ORDER BY position ASC");
$stmt->execute([$lesson_id]);
$lesson_questions = $stmt->fetchAll();

// Get user's existing answers for these questions
$user_answers = [];
if (!empty($lesson_questions)) {
    $question_ids = array_column($lesson_questions, 'id');
    $placeholders = str_repeat('?,', count($question_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT lesson_question_id, answer_text FROM lesson_answers WHERE user_id = ? AND lesson_question_id IN ($placeholders)");
    $stmt->execute(array_merge([$user_id], $question_ids));
    $answers = $stmt->fetchAll();
    foreach ($answers as $answer) {
        $user_answers[$answer['lesson_question_id']] = $answer['answer_text'];
    }
}

// Get all lessons for this course to populate sidebar
$stmt = $pdo->prepare("
    SELECT l.*, p.completed, p.watched_seconds
    FROM lessons l
    LEFT JOIN progress p ON l.id = p.lesson_id AND p.user_id = ?
    WHERE l.course_id = ?
    ORDER BY l.position ASC
");
$stmt->execute([$user_id, $lesson['course_id']]);
$all_lessons = $stmt->fetchAll();

// Calculate course progress
$total_lessons = count($all_lessons);
$completed_lessons = 0;
foreach ($all_lessons as $l) {
    if ($l['completed']) {
        $completed_lessons++;
    }
}
$course_progress = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;

// Get next and previous lessons
$stmt = $pdo->prepare("
    SELECT id, title FROM lessons 
    WHERE course_id = ? AND position < ? 
    ORDER BY position DESC LIMIT 1
");
$stmt->execute([$lesson['course_id'], $lesson['position']]);
$prev_lesson = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT id, title FROM lessons 
    WHERE course_id = ? AND position > ? 
    ORDER BY position ASC LIMIT 1
");
$stmt->execute([$lesson['course_id'], $lesson['position']]);
$next_lesson = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lesson['title']); ?> - LMS</title>
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

    <div class="lesson-container">
        <!-- Main Content Area -->
        <div class="lesson-main-content">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Lesson Header with Back Button -->
            <div class="lesson-header">
                <a href="dashboard.php" class="back-to-dashboard">← Back to Dashboard</a>
            </div>

            <!-- Lesson Title -->
            <h1 class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></h1>

        <!-- Video Content -->
        <?php if ($lesson['video_url'] || $lesson['embed_code']): ?>
            <div class="card">
                <div class="card-body">
                    <?php if ($lesson['embed_code']): ?>
                        <!-- Embedded Video -->
                        <div class="video-container embed-video" style="margin-bottom: 0;">
                            <?php echo $lesson['embed_code']; ?>
                        </div>
                    <?php elseif ($lesson['video_url']): ?>
                        <!-- Direct Video File -->
                        <div class="video-container">
                            <video id="lessonVideo" class="video-player" controls>
                                <source src="<?php echo htmlspecialchars($lesson['video_url']); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                        <div class="mt-20">
                            <div class="progress">
                                <div class="progress-bar" id="videoProgress" style="width: 0%"></div>
                            </div>
                            <small>Progress: <span id="progressText">0%</span></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Audio Content -->
        <?php 
        // Parse audio files
        $audio_files = [];
        if (!empty($lesson['audio_url'])) {
            $decoded = json_decode($lesson['audio_url'], true);
            if (is_array($decoded)) {
                $audio_files = $decoded;
            } else {
                // Handle legacy single audio URL
                $audio_files = [$lesson['audio_url']];
            }
        }
        
        if (!empty($audio_files)): 
            foreach ($audio_files as $index => $audio_url): 
                $audio_number = $index + 1;
        ?>
            <div class="card">
                <div class="card-header">
                    Audio <?php echo $audio_number; ?>
                </div>
                <div class="card-body">
                    <div class="audio-container">
                        <audio id="lessonAudio<?php echo $audio_number; ?>" class="audio-player" controls>
                            <source src="<?php echo htmlspecialchars($audio_url); ?>" type="audio/mpeg">
                            <source src="<?php echo htmlspecialchars($audio_url); ?>" type="audio/wav">
                            <source src="<?php echo htmlspecialchars($audio_url); ?>" type="audio/ogg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                </div>
            </div>
        <?php 
            endforeach;
        endif; 
        ?>

        <!-- Reference Files -->
        <?php if ($lesson['reference_files']): ?>
            <div class="card">
                <div class="card-header">
                    Reference Materials
                </div>
                <div class="card-body">
                    <div class="reference-files">
                        <?php 
                        $files = explode("\n", $lesson['reference_files']);
                        foreach ($files as $file): 
                            $file = trim($file);
                            if (!empty($file)):
                        ?>
                            <div class="reference-item">
                                <?php if (filter_var($file, FILTER_VALIDATE_URL)): ?>
                                    <a href="<?php echo htmlspecialchars($file); ?>" target="_blank" class="btn btn-outline">
                                        📄 <?php echo basename(parse_url($file, PHP_URL_PATH)) ?: 'Reference File'; ?>
                                    </a>
                                <?php else: ?>
                                    <span class="reference-text"><?php echo htmlspecialchars($file); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Lesson Questions -->
        <?php if (!empty($lesson_questions)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Lesson Questions</h3>
            </div>
            <div class="card-body">
                <form method="POST" id="questionsForm">
                    <?php foreach ($lesson_questions as $index => $question): ?>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="answer_<?php echo $question['id']; ?>">
                                <strong>Question <?php echo $index + 1; ?>:</strong>
                                <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                            </label>
                            <textarea 
                                id="answer_<?php echo $question['id']; ?>" 
                                name="answers[<?php echo $question['id']; ?>]" 
                                rows="4" 
                                class="form-control"
                                placeholder="Type your answer here..."
                                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"><?php echo isset($user_answers[$question['id']]) ? htmlspecialchars($user_answers[$question['id']]) : ''; ?></textarea>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="margin-top: 20px;">
                        <button type="submit" name="submit_answers" class="btn btn-primary">
                            Submit Answers
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>


            <!-- Lesson Actions -->
            <div class="lesson-actions">
                <div class="action-buttons">
                    <!-- Mark as Complete Button -->
                    <?php if (!$progress || !$progress['completed']): ?>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="mark_complete" class="btn btn-success btn-mark-complete" 
                                    onclick="return confirm('Mark this lesson as complete?')">
                                ✓ Mark Complete
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="btn btn-success disabled">✓ Completed</span>
                    <?php endif; ?>
                    
                    <?php if ($next_lesson): ?>
                        <a href="lesson.php?id=<?php echo $next_lesson['id']; ?>" class="btn btn-primary btn-next-lesson">
                            Next Lesson →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Video progress tracking
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('lessonVideo');
            const progressBar = document.getElementById('videoProgress');
            const progressText = document.getElementById('progressText');
            
            if (video) {
                let lastSavedTime = 0;
                
                // Update progress every 5 seconds
                setInterval(function() {
                    if (video.currentTime > lastSavedTime + 5) {
                        saveProgress(video.currentTime);
                        lastSavedTime = Math.floor(video.currentTime);
                    }
                }, 5000);
                
                // Update progress bar
                video.addEventListener('timeupdate', function() {
                    const progress = (video.currentTime / video.duration) * 100;
                    progressBar.style.width = progress + '%';
                    progressText.textContent = Math.round(progress) + '%';
                    
                    // Auto-mark as completed when 90% watched
                    if (progress >= 90) {
                        markLessonCompleted();
                    }
                });
                
                // Save progress when video ends
                video.addEventListener('ended', function() {
                    saveProgress(video.duration);
                    markLessonCompleted();
                });
            }
        });
        
        function saveProgress(currentTime) {
            fetch('save_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    lesson_id: <?php echo $lesson_id; ?>,
                    watched_seconds: Math.floor(currentTime)
                })
            });
        }
        
        function markLessonCompleted() {
            fetch('save_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    lesson_id: <?php echo $lesson_id; ?>,
                    completed: true
                })
            });
        }
    </script>
    <footer class="footer">
        <div class="footer-content">
            Copyright Halcom Group 2025
        </div>
    </footer>
</body>
</html>
