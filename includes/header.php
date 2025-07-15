<?php
// --- Session Security ---
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => 'studentmonitoring.com',
    'secure' => false, // Set to true for HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// --- Security: Regenerate session ID ---
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// --- General Login Check ---
// This checks if ANY user is logged in. Role-specific checks will be done on their respective pages.
if (!isset($_SESSION['user_id'])) {
    header('Location: /index.php');
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
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Student Monitoring System'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="h-full">