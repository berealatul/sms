<?php
// Centralized Configuration
define('APP_DOMAIN', 'studentmonitoring.local');    // hosting domain on which app is live
define('IS_LIVE', false);                           // true for production with HTTPS
define('LOGOUT_TIME', 3600);                        // time after relogin required

define('DB_SERVER', 'localhost');                   // hosting provider's database host
define('DB_USERNAME', 'root');                      // MySQL username
define('DB_PASSWORD', '');                          // MySQL password
define('DB_NAME', 'student_monitoring_db');         // MySQL database name being used

// Set default timezone
date_default_timezone_set('Asia/Kolkata');

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => LOGOUT_TIME,
        'path' => '/',
        'domain' => APP_DOMAIN,
        'secure' => IS_LIVE,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Error reporting based on environment
if (IS_LIVE) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Create a new MySQLi connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Log the error securely, don't display sensitive info in production
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}
