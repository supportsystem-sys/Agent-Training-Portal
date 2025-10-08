<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isLearner()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['lesson_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

$lesson_id = (int)$input['lesson_id'];
$user_id = $_SESSION['user_id'];
$watched_seconds = isset($input['watched_seconds']) ? (int)$input['watched_seconds'] : 0;
$completed = isset($input['completed']) ? (bool)$input['completed'] : false;

try {
    updateProgress($user_id, $lesson_id, $watched_seconds, $completed);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save progress']);
}
?>
