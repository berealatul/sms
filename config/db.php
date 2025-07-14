<?php
// config/db.php
define('DB_SERVER', 'localhost'); // Or your hosting provider's database host
define('DB_USERNAME', 'root');    // Your MySQL username
define('DB_PASSWORD', '');        // Your MySQL password
define('DB_NAME', 'student_monitoring_db');

// Enable error reporting for development (disable in production i.e 0 for all three)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Create a new MySQLi connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Log the error securely, don't display sensitive info in production
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}
