<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'sms_main');

define('LOCAL_DOMAIN', 'studentmonitoring.local');
define('LIVE_DOMAIN', 'studentmonitoring.infy.uk');
define('IS_LIVE', false);
define('LOGOUT_TIME', 3600);
define('LOGIN_ATTEMPT_LIMIT', 5);
define('LOGIN_LOCKOUT_PERIOD', '5 MINUTE');

date_default_timezone_set('Asia/Kolkata');

// start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => IS_LIVE ? LIVE_DOMAIN : LOCAL_DOMAIN,
        'secure' => IS_LIVE,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// error reporting
if (IS_LIVE) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    error_log("Database Connection Failed: " . $conn->connect_error);
    die("A critical error occurred. Please contact the system administrator.");
}
