<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'traininga_training');
define('DB_USER', 'traininga_training');
define('DB_PASS', 'training@321@');

// Create database connection
function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Initialize session
session_start();
?>
