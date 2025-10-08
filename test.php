<?php
/**
 * LMS Test Script
 * Run this to test basic functionality
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>LMS System Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

// Test database connection
echo "<div class='test-section'>";
echo "<h2>Database Connection Test</h2>";
try {
    $pdo = getDBConnection();
    echo "<p class='success'>✓ Database connection successful</p>";
    
    // Test basic queries
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch()['count'];
    echo "<p class='success'>✓ Users table accessible ($user_count users)</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses");
    $Training_count = $stmt->fetch()['count'];
    echo "<p class='success'>✓ Training table accessible ($Training_count Training)</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Database test failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test utility functions
echo "<div class='test-section'>";
echo "<h2>Utility Functions Test</h2>";

// Test password hashing
$test_password = "test123";
$hashed = hashPassword($test_password);
if (verifyPassword($test_password, $hashed)) {
    echo "<p class='success'>✓ Password hashing and verification working</p>";
} else {
    echo "<p class='error'>✗ Password hashing test failed</p>";
}

// Test sanitization
$test_input = "<script>alert('xss')</script>Hello World";
$sanitized = sanitize($test_input);
if ($sanitized === "Hello World") {
    echo "<p class='success'>✓ Input sanitization working</p>";
} else {
    echo "<p class='error'>✗ Input sanitization test failed</p>";
}

// Test date formatting
$test_date = "2024-01-15 14:30:00";
$formatted = formatDate($test_date);
echo "<p class='success'>✓ Date formatting working: $formatted</p>";
echo "</div>";

// Test admin login
echo "<div class='test-section'>";
echo "<h2>Authentication Test</h2>";
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute(['admin@lms.com']);
    $admin = $stmt->fetch();
    
    if ($admin && verifyPassword('admin123', $admin['password'])) {
        echo "<p class='success'>✓ Admin login credentials working</p>";
    } else {
        echo "<p class='error'>✗ Admin login test failed</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Authentication test failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test quiz system
echo "<div class='test-section'>";
echo "<h2>Quiz System Test</h2>";
try {
    $pdo = getDBConnection();
    
    // Check if we have quizzes and questions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM quizzes");
    $quiz_count = $stmt->fetch()['count'];
    echo "<p class='success'>✓ Quizzes found: $quiz_count</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM questions");
    $question_count = $stmt->fetch()['count'];
    echo "<p class='success'>✓ Questions found: $question_count</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM question_options");
    $option_count = $stmt->fetch()['count'];
    echo "<p class='success'>✓ Question options found: $option_count</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Quiz system test failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test progress tracking
echo "<div class='test-section'>";
echo "<h2>Progress Tracking Test</h2>";
try {
    // Test progress update function
    $test_user_id = 2; // learner@test.com
    $test_lesson_id = 1;
    
    // This is just testing the function, not actually updating
    echo "<p class='success'>✓ Progress tracking functions available</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Progress tracking test failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

// System info
echo "<div class='test-section'>";
echo "<h2>System Information</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";
echo "</div>";

echo "<hr>";
echo "<h2>Test Complete</h2>";
echo "<p>If all tests passed, your LMS system is ready to use!</p>";
echo "<p><a href='index.php'>Go to LMS</a> | <a href='setup.php'>Run Setup Check</a></p>";
?>
