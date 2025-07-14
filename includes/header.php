<?php
// --- Session Security ---
// Set session cookie parameters for enhanced security before starting the session
session_set_cookie_params([
    'lifetime' => 3600, // 60 minutes
    'path' => '/',
    'domain' => 'studentmonitoring.com', // Or your specific full domain like 'studentmonitoring.infy.uk'
    'secure' => false, // <<< Set this to TRUE for HTTPS
    'httponly' => true, // Prevent JavaScript access to cookie
    'samesite' => 'Lax' // Protect against CSRF
]);
session_start();

// --- Security: Regenerate session ID periodically to prevent session fixation ---
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// --- Check if user is logged in ---
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// --- Authorization Check: Only allow 'admin' role for admin pages (Corrected) ---
// This assumes that any file including this header is an admin-only area.
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'admin') {
    // Destroy the session and redirect to login
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}

// --- Include Database Connection ---
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Admin Area'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="h-full">