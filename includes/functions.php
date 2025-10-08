<?php
// Utility functions

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isLearner() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'learner';
}

function redirect($url) {
    // If URL is relative, make it absolute
    if (strpos($url, 'http') !== 0) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        
        // Handle different base paths
        if (strpos($base_path, '/admin') !== false) {
            $base_path = str_replace('/admin', '', $base_path);
        } elseif (strpos($base_path, '/learner') !== false) {
            $base_path = str_replace('/learner', '', $base_path);
        } elseif (strpos($base_path, '/auth') !== false) {
            $base_path = str_replace('/auth', '', $base_path);
        }
        
        $url = $protocol . '://' . $host . $base_path . '/' . ltrim($url, '/');
    }
    
    // Prevent redirect loops by checking if we're already on the target page
    $current_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $target_path = parse_url($url, PHP_URL_PATH);
    
    if ($current_path === $target_path) {
        // If we're already on the target page, just exit without redirecting
        exit();
    }
    
    header("Location: $url");
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($date) {
    return date('M d, Y H:i', strtotime($date));
}

function calculateQuizScore($attempt_id) {
    $pdo = getDBConnection();
    
    // Get all answers for this attempt (only MCQ questions for scoring)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total, SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) as correct
        FROM answers a
        JOIN question_options qo ON a.option_id = qo.id
        WHERE a.attempt_id = ? AND a.option_id IS NOT NULL
    ");
    $stmt->execute([$attempt_id]);
    $result = $stmt->fetch();
    
    if ($result['total'] > 0) {
        return round(($result['correct'] / $result['total']) * 100, 2);
    }
    return 0;
}

function markQuizAttempt($attempt_id, $quiz_id) {
    $pdo = getDBConnection();
    $score = calculateQuizScore($attempt_id);
    
    // Get passing score for the quiz
    $stmt = $pdo->prepare("SELECT passing_score FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    $passed = $score >= $quiz['passing_score'];
    
    // Update attempt with score and pass status
    $stmt = $pdo->prepare("
        UPDATE quiz_attempts 
        SET score = ?, passed = ?, completed_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$score, $passed, $attempt_id]);
    
    return ['score' => $score, 'passed' => $passed];
}

function updateProgress($user_id, $lesson_id, $watched_seconds, $completed = false) {
    $pdo = getDBConnection();
    
    // Check if progress record exists
    $stmt = $pdo->prepare("SELECT id FROM progress WHERE user_id = ? AND lesson_id = ?");
    $stmt->execute([$user_id, $lesson_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing record
        $stmt = $pdo->prepare("
            UPDATE progress 
            SET watched_seconds = ?, completed = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$watched_seconds, $completed, $existing['id']]);
    } else {
        // Insert new record
        $stmt = $pdo->prepare("
            INSERT INTO progress (user_id, lesson_id, watched_seconds, completed, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$user_id, $lesson_id, $watched_seconds, $completed]);
    }
}

function getProgress($user_id, $lesson_id) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT * FROM progress 
        WHERE user_id = ? AND lesson_id = ?
    ");
    $stmt->execute([$user_id, $lesson_id]);
    return $stmt->fetch();
}

function getTrainingProgress($user_id, $course_id) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT 
            l.id,
            l.title,
            l.video_url,
            l.embed_code,
            p.watched_seconds,
            p.completed,
            p.updated_at
        FROM lessons l
        LEFT JOIN progress p ON l.id = p.lesson_id AND p.user_id = ?
        WHERE l.course_id = ?
        ORDER BY l.position
    ");
    $stmt->execute([$user_id, $course_id]);
    return $stmt->fetchAll();
}

// User Activity Logging Functions
function logUserActivity($user_id, $activity_type, $page_url = '', $page_title = '', $lesson_id = null, $quiz_id = null, $activity_data = null) {
    $pdo = getDBConnection();
    
    $session_id = session_id();
    $ip_address = getUserIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_activity_logs 
            (user_id, session_id, activity_type, page_url, page_title, lesson_id, quiz_id, activity_data, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $activity_data_json = $activity_data ? json_encode($activity_data) : null;
        
        $stmt->execute([
            $user_id, $session_id, $activity_type, $page_url, $page_title, 
            $lesson_id, $quiz_id, $activity_data_json, $ip_address, $user_agent
        ]);
    } catch (Exception $e) {
        error_log("Failed to log user activity: " . $e->getMessage());
    }
}

function startUserSession($user_id) {
    $pdo = getDBConnection();
    
    $session_id = session_id();
    $ip_address = getUserIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    try {
        // End any existing active sessions for this user
        $stmt = $pdo->prepare("
            UPDATE user_sessions 
            SET status = 'expired', logout_time = NOW(), 
                session_duration = TIMESTAMPDIFF(SECOND, login_time, NOW())
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$user_id]);
        
        // Start new session
        $stmt = $pdo->prepare("
            INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $session_id, $ip_address, $user_agent]);
        
        // Update last login in users table
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Log login activity
        logUserActivity($user_id, 'login', $_SERVER['REQUEST_URI'] ?? '', 'Login');
        
    } catch (Exception $e) {
        error_log("Failed to start user session: " . $e->getMessage());
    }
}

function endUserSession($user_id) {
    $pdo = getDBConnection();
    
    $session_id = session_id();
    
    try {
        $stmt = $pdo->prepare("
            UPDATE user_sessions 
            SET status = 'logout', logout_time = NOW(),
                session_duration = TIMESTAMPDIFF(SECOND, login_time, NOW())
            WHERE user_id = ? AND session_id = ? AND status = 'active'
        ");
        $stmt->execute([$user_id, $session_id]);
        
        // Update total portal time
        $stmt = $pdo->prepare("
            SELECT session_duration FROM user_sessions 
            WHERE user_id = ? AND session_id = ? AND status = 'logout'
        ");
        $stmt->execute([$user_id, $session_id]);
        $session = $stmt->fetch();
        
        if ($session) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET total_portal_time = total_portal_time + ?
                WHERE id = ?
            ");
            $stmt->execute([$session['session_duration'], $user_id]);
        }
        
        // Log logout activity
        logUserActivity($user_id, 'logout', $_SERVER['REQUEST_URI'] ?? '', 'Logout');
        
    } catch (Exception $e) {
        error_log("Failed to end user session: " . $e->getMessage());
    }
}

function updateSessionActivity($user_id) {
    $pdo = getDBConnection();
    $session_id = session_id();
    
    try {
        $stmt = $pdo->prepare("
            UPDATE user_sessions 
            SET last_activity = NOW()
            WHERE user_id = ? AND session_id = ? AND status = 'active'
        ");
        $stmt->execute([$user_id, $session_id]);
    } catch (Exception $e) {
        error_log("Failed to update session activity: " . $e->getMessage());
    }
}

function getUserIP() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

function getUserActivityLogs($user_id = null, $limit = 100) {
    $pdo = getDBConnection();
    
    $sql = "
        SELECT ual.*, u.name as user_name, u.email as user_email,
               l.title as lesson_title, q.title as quiz_title
        FROM user_activity_logs ual
        JOIN users u ON ual.user_id = u.id
        LEFT JOIN lessons l ON ual.lesson_id = l.id
        LEFT JOIN quizzes q ON ual.quiz_id = q.id
    ";
    
    $params = [];
    if ($user_id) {
        $sql .= " WHERE ual.user_id = ?";
        $params[] = $user_id;
    }
    
    $sql .= " ORDER BY ual.created_at DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

function getUserSessions($user_id = null, $limit = 50) {
    $pdo = getDBConnection();
    
    $sql = "
        SELECT us.*, u.name as user_name, u.email as user_email
        FROM user_sessions us
        JOIN users u ON us.user_id = u.id
    ";
    
    $params = [];
    if ($user_id) {
        $sql .= " WHERE us.user_id = ?";
        $params[] = $user_id;
    }
    
    $sql .= " ORDER BY us.login_time DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

function formatDuration($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . 'h ' . $minutes . 'm';
    }
}
?>
