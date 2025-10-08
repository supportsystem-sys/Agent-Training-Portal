<?php
/**
 * LMS Setup Script
 * Run this script to check system requirements and setup the database
 */

echo "<h1>LMS Setup Script</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .info { color: blue; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

// Check PHP version
echo "<div class='section'>";
echo "<h2>PHP Version Check</h2>";
if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
    echo "<p class='success'>✓ PHP Version: " . PHP_VERSION . " (OK)</p>";
} else {
    echo "<p class='error'>✗ PHP Version: " . PHP_VERSION . " (Requires 7.4.0 or higher)</p>";
}
echo "</div>";

// Check required extensions
echo "<div class='section'>";
echo "<h2>Required Extensions</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'session', 'json'];
$all_ok = true;

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✓ $ext extension loaded</p>";
    } else {
        echo "<p class='error'>✗ $ext extension not loaded</p>";
        $all_ok = false;
    }
}
echo "</div>";

// Check file permissions
echo "<div class='section'>";
echo "<h2>File Permissions</h2>";
$writable_dirs = ['config', 'includes', 'assets', 'auth', 'admin', 'learner'];
foreach ($writable_dirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "<p class='success'>✓ $dir directory is writable</p>";
        } else {
            echo "<p class='warning'>⚠ $dir directory is not writable</p>";
        }
    } else {
        echo "<p class='error'>✗ $dir directory does not exist</p>";
    }
}
echo "</div>";

// Check database connection
echo "<div class='section'>";
echo "<h2>Database Connection Test</h2>";

// Try to connect to database
try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    echo "<p class='success'>✓ MySQL connection successful</p>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'lms_system'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✓ lms_system database exists</p>";
        
        // Check tables
        $pdo->exec("USE lms_system");
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $required_tables = ['users', 'Training', 'lessons', 'quizzes', 'questions', 'question_options', 'quiz_attempts', 'answers', 'progress'];
        $missing_tables = array_diff($required_tables, $tables);
        
        if (empty($missing_tables)) {
            echo "<p class='success'>✓ All required tables exist</p>";
        } else {
            echo "<p class='error'>✗ Missing tables: " . implode(', ', $missing_tables) . "</p>";
            echo "<p class='info'>Please run the SQL script from database/schema.sql</p>";
        }
        
    } else {
        echo "<p class='error'>✗ lms_system database does not exist</p>";
        echo "<p class='info'>Please create the database and import database/schema.sql</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p class='info'>Please check your MySQL configuration and credentials</p>";
}
echo "</div>";

// Sample data check
echo "<div class='section'>";
echo "<h2>Sample Data Check</h2>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=lms_system", "root", "");
    
    // Check admin user
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $admin_count = $stmt->fetch()['count'];
    if ($admin_count > 0) {
        echo "<p class='success'>✓ Admin user exists</p>";
    } else {
        echo "<p class='warning'>⚠ No admin user found</p>";
    }
    
    // Check Training
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses");
    $Training_count = $stmt->fetch()['count'];
    if ($Training_count > 0) {
        echo "<p class='success'>✓ Sample Training found ($Training_count Training)</p>";
    } else {
        echo "<p class='warning'>⚠ No Training found</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ Cannot check sample data: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Setup instructions
echo "<div class='section'>";
echo "<h2>Setup Instructions</h2>";
echo "<ol>";
echo "<li>Ensure all requirements above are met</li>";
echo "<li>If database setup is needed, import <code>database/schema.sql</code> in phpMyAdmin</li>";
echo "<li>Configure database settings in <code>config/database.php</code> if needed</li>";
echo "<li>Access the application at <a href='index.php'>index.php</a></li>";
echo "<li>Login with admin credentials: admin@lms.com / admin123</li>";
echo "</ol>";
echo "</div>";

// Quick links
echo "<div class='section'>";
echo "<h2>Quick Links</h2>";
echo "<p><a href='index.php'>Go to LMS</a> | <a href='auth/login.php'>Login Page</a> | <a href='auth/register.php'>Register</a></p>";
echo "</div>";

echo "<hr>";
echo "<p><small>LMS Setup Script - Check completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
