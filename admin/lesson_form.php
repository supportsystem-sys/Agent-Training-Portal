<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$pdo = getDBConnection();
$lesson = null;
$is_edit = false;
$course_id = $_GET['course_id'] ?? null;

// Handle edit mode
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $is_edit = true;
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $lesson = $stmt->fetch();
    
    if (!$lesson) {
        redirect('trainings.php');
    }
    $course_id = $lesson['course_id'];
} elseif (!$course_id || !is_numeric($course_id)) {
    redirect('trainings.php');
}

// Get Training information
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$Training = $stmt->fetch();

if (!$Training) {
    redirect('trainings.php');
}

$error = '';
$success = '';

// Check if this is a redirect after successful update
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $success = "Lesson updated successfully.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $video_url = sanitize($_POST['video_url']);
    $embed_code = $_POST['embed_code']; // Don't sanitize embed code as it contains HTML
    // Handle multiple audio files
    $audio_files = [];
    if (!empty($_POST['audio_url'])) {
        $audio_urls = is_array($_POST['audio_url']) ? $_POST['audio_url'] : [$_POST['audio_url']];
        foreach ($audio_urls as $url) {
            $url = trim($url);
            if (!empty($url)) {
                $audio_files[] = $url;
            }
        }
    }
    $audio_url = !empty($audio_files) ? json_encode($audio_files) : '';
    $reference_files = sanitize($_POST['reference_files']);
    $position = (int)$_POST['position'];
    
    if (empty($title)) {
        $error = 'Please fill in title.';
    } else {
        try {
            if ($is_edit) {
                $stmt = $pdo->prepare("UPDATE lessons SET title = ?, content = '', video_url = ?, embed_code = ?, audio_url = ?, reference_files = ?, position = ? WHERE id = ?");
                $stmt->execute([$title, $video_url, $embed_code, $audio_url, $reference_files, $position, $lesson['id']]);
                
                // Delete existing questions for this lesson
                $stmt = $pdo->prepare("DELETE FROM lesson_questions WHERE lesson_id = ?");
                $stmt->execute([$lesson['id']]);
                
                // Save new questions
                if (!empty($_POST['questions'])) {
                    $stmt = $pdo->prepare("INSERT INTO lesson_questions (lesson_id, question_text, position) VALUES (?, ?, ?)");
                    foreach ($_POST['questions'] as $index => $question_text) {
                        $question_text = trim($question_text);
                        if (!empty($question_text)) {
                            $stmt->execute([$lesson['id'], $question_text, $index + 1]);
                        }
                    }
                }
                
                // Redirect to prevent form resubmission
                header("Location: lesson_form.php?id=" . $lesson['id'] . "&updated=1");
                exit();
            } else {
                $stmt = $pdo->prepare("INSERT INTO lessons (course_id, title, content, video_url, embed_code, audio_url, reference_files, position) VALUES (?, ?, '', ?, ?, ?, ?, ?)");
                $stmt->execute([$course_id, $title, $video_url, $embed_code, $audio_url, $reference_files, $position]);
                $lesson_id = $pdo->lastInsertId();
                
                // Save questions
                if (!empty($_POST['questions'])) {
                    $stmt = $pdo->prepare("INSERT INTO lesson_questions (lesson_id, question_text, position) VALUES (?, ?, ?)");
                    foreach ($_POST['questions'] as $index => $question_text) {
                        $question_text = trim($question_text);
                        if (!empty($question_text)) {
                            $stmt->execute([$lesson_id, $question_text, $index + 1]);
                        }
                    }
                }
                
                $success = "Lesson created successfully!";
            }
        } catch (Exception $e) {
            $error = "An error occurred. Please try again.";
        }
    }
}

// Get next position for new lesson
if (!$is_edit) {
    $stmt = $pdo->prepare("SELECT MAX(position) as max_pos FROM lessons WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $result = $stmt->fetch();
    $next_position = ($result['max_pos'] ?? 0) + 1;
} else {
    $next_position = $lesson['position'];
}

// Get existing questions for edit mode
$existing_questions = [];
if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM lesson_questions WHERE lesson_id = ? ORDER BY position ASC");
    $stmt->execute([$lesson['id']]);
    $existing_questions = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Lesson - Halcom Marketing Admin</title>
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
                <h1><?php echo $is_edit ? 'Edit Lesson' : 'Add New Lesson'; ?></h1>
                <p>Training: <strong><?php echo htmlspecialchars($Training['title']); ?></strong></p>
            </div>
            <a href="lessons.php?course_id=<?php echo $course_id; ?>" class="btn btn-secondary">Back to Lessons</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Lesson Information
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="title">Lesson Title:</label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo $lesson ? htmlspecialchars($lesson['title']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Position in Training:</label>
                        <input type="number" id="position" name="position" required min="1" 
                               value="<?php echo $lesson ? $lesson['position'] : $next_position; ?>">
                    </div>
                    
                    <input type="hidden" id="video_url" name="video_url" value="<?php echo $lesson ? htmlspecialchars($lesson['video_url']) : ''; ?>">
                    
                    <div class="form-group">
                        <label for="embed_code">Video Embed Code (optional):</label>
                        <textarea id="embed_code" name="embed_code" rows="4" 
                                  placeholder="Paste embed code from YouTube, Vimeo, or other video platforms:&#10;&lt;iframe src=&quot;https://www.youtube.com/embed/VIDEO_ID&quot; width=&quot;560&quot; height=&quot;315&quot; frameborder=&quot;0&quot; allowfullscreen&gt;&lt;/iframe&gt;"><?php echo $lesson ? $lesson['embed_code'] : ''; ?></textarea>
                        <small>For embedded videos from YouTube, Vimeo, or other platforms. Use either Video URL OR Embed Code, not both.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="audio_files">Audio Files (optional):</label>
                        <div id="audio-files-container">
                            <?php
                            // Parse existing audio files
                            $existing_audio = [];
                            if ($lesson && !empty($lesson['audio_url'])) {
                                $decoded = json_decode($lesson['audio_url'], true);
                                if (is_array($decoded)) {
                                    $existing_audio = $decoded;
                                } else {
                                    // Handle legacy single audio URL
                                    $existing_audio = [$lesson['audio_url']];
                                }
                            }
                            
                            // Display existing audio files
                            if (!empty($existing_audio)) {
                                foreach ($existing_audio as $index => $audio_url) {
                                    echo '<div class="audio-file-input" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                            <input type="url" name="audio_url[]" value="' . htmlspecialchars($audio_url) . '" 
                                                   placeholder="https://example.com/audio.mp3" class="flex-1">
                                            <button type="button" class="btn btn-sm btn-danger remove-audio" onclick="removeAudioFile(this)">Remove</button>
                                          </div>';
                                }
                            } else {
                                // Default single input
                                echo '<div class="audio-file-input" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                        <input type="url" name="audio_url[]" placeholder="https://example.com/audio.mp3" class="flex-1">
                                        <button type="button" class="btn btn-sm btn-danger remove-audio" onclick="removeAudioFile(this)">Remove</button>
                                      </div>';
                            }
                            ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addAudioFile()">Add Audio File</button>
                        <small>Add multiple audio files or URLs for this lesson.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="reference_files">Reference Files (optional):</label>
                        <div class="d-flex gap-10 mb-10">
                            <input type="text" id="ref_name" placeholder="Reference name (e.g., Slides)">
                            <input type="url" id="ref_url" placeholder="https://example.com/document.pdf">
                            <button type="button" class="btn btn-secondary" id="add_ref_btn">Add</button>
                        </div>
                        <textarea id="reference_files" name="reference_files" rows="3" 
                                  placeholder="Enter one per line. You can use 'Name: URL' format:&#10;Slides: https://example.com/slides.pdf&#10;Worksheet: https://example.com/worksheet.docx"><?php echo $lesson ? htmlspecialchars($lesson['reference_files']) : ''; ?></textarea>
                        <small>Use the fields above to append a reference name and its file URL into the list.</small>
                    </div>

                    <script>
                    (function(){
                        var btn = document.getElementById('add_ref_btn');
                        if (btn) {
                            btn.addEventListener('click', function(){
                                var nameInput = document.getElementById('ref_name');
                                var urlInput = document.getElementById('ref_url');
                                var textarea = document.getElementById('reference_files');
                                var name = (nameInput && nameInput.value || '').trim();
                                var url = (urlInput && urlInput.value || '').trim();
                                if (!name || !url) { return; }
                                var line = name + ': ' + url;
                                if (textarea) {
                                    textarea.value = textarea.value ? (textarea.value.replace(/\s+$/, '') + "\n" + line) : line;
                                }
                                if (nameInput) nameInput.value = '';
                                if (urlInput) urlInput.value = '';
                                if (nameInput) nameInput.focus();
                            });
                        }
                    })();
                    
                    function addAudioFile() {
                        var container = document.getElementById('audio-files-container');
                        var audioInput = document.createElement('div');
                        audioInput.className = 'audio-file-input';
                        audioInput.style.cssText = 'display: flex; gap: 10px; margin-bottom: 10px; align-items: center;';
                        audioInput.innerHTML = '<input type="url" name="audio_url[]" placeholder="https://example.com/audio.mp3" class="flex-1">' +
                                             '<button type="button" class="btn btn-sm btn-danger remove-audio" onclick="removeAudioFile(this)">Remove</button>';
                        container.appendChild(audioInput);
                    }
                    
                    function removeAudioFile(button) {
                        var container = document.getElementById('audio-files-container');
                        var inputs = container.querySelectorAll('.audio-file-input');
                        if (inputs.length > 1) {
                            button.closest('.audio-file-input').remove();
                        } else {
                            // If only one input, just clear it instead of removing
                            var input = button.closest('.audio-file-input').querySelector('input');
                            input.value = '';
                        }
                    }
                    
                    // Question management functions
                    function addQuestion() {
                        var container = document.getElementById('questions-container');
                        var questionCount = container.querySelectorAll('.question-input-group').length + 1;
                        var questionDiv = document.createElement('div');
                        questionDiv.className = 'question-input-group';
                        questionDiv.style.cssText = 'margin-bottom: 15px;';
                        questionDiv.innerHTML = '<label>Question ' + questionCount + ':</label>' +
                                              '<div style="display: flex; gap: 10px; align-items: start;">' +
                                              '<textarea name="questions[]" rows="3" class="flex-1" placeholder="Enter your question here..."></textarea>' +
                                              '<button type="button" class="btn btn-sm btn-danger remove-question" onclick="removeQuestion(this)">Remove</button>' +
                                              '</div>';
                        container.appendChild(questionDiv);
                        updateQuestionLabels();
                    }
                    
                    function removeQuestion(button) {
                        var container = document.getElementById('questions-container');
                        var inputs = container.querySelectorAll('.question-input-group');
                        if (inputs.length > 1) {
                            button.closest('.question-input-group').remove();
                            updateQuestionLabels();
                        } else {
                            // If only one question, just clear it instead of removing
                            var textarea = button.closest('.question-input-group').querySelector('textarea');
                            textarea.value = '';
                        }
                    }
                    
                    function updateQuestionLabels() {
                        var container = document.getElementById('questions-container');
                        var questions = container.querySelectorAll('.question-input-group');
                        questions.forEach(function(question, index) {
                            question.querySelector('label').textContent = 'Question ' + (index + 1) + ':';
                        });
                    }
                    </script>
                    
                    <div class="form-group">
                        <label>Lesson Questions:</label>
                        <div id="questions-container">
                            <?php
                            // Display existing questions or default one
                            if (!empty($existing_questions)) {
                                foreach ($existing_questions as $index => $question) {
                                    $question_num = $index + 1;
                                    echo '<div class="question-input-group" style="margin-bottom: 15px;">
                                            <label>Question ' . $question_num . ':</label>
                                            <div style="display: flex; gap: 10px; align-items: start;">
                                                <textarea name="questions[]" rows="3" class="flex-1" placeholder="Enter your question here...">' . htmlspecialchars($question['question_text']) . '</textarea>
                                                <button type="button" class="btn btn-sm btn-danger remove-question" onclick="removeQuestion(this)">Remove</button>
                                            </div>
                                          </div>';
                                }
                            } else {
                                // Default single question input
                                echo '<div class="question-input-group" style="margin-bottom: 15px;">
                                        <label>Question 1:</label>
                                        <div style="display: flex; gap: 10px; align-items: start;">
                                            <textarea name="questions[]" rows="3" class="flex-1" placeholder="Enter your question here..."></textarea>
                                            <button type="button" class="btn btn-sm btn-danger remove-question" onclick="removeQuestion(this)">Remove</button>
                                        </div>
                                      </div>';
                            }
                            ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addQuestion()">+ Add More Question</button>
                        <small>Add questions that learners will need to answer for this lesson.</small>
                    </div>
                    
                    <div class="d-flex gap-10">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $is_edit ? 'Update Lesson' : 'Create Lesson'; ?>
                        </button>
                        <a href="lessons.php?course_id=<?php echo $course_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
