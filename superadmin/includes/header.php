<?php
require_once __DIR__ . '/../../config/db.php';

// Security: Regenerate session ID on new session
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// /*
//  * --- DEBUGGING BLOCK ---
//  * Uncomment the following lines to see what's in the session.
//  * This will stop the redirect and show you the session variables.
//  * After debugging, re-comment this block.
//  */
// echo "<pre>";
// print_r($_SESSION);
// echo "</pre>";
// die("Session debug complete. Comment out this block in /superadmin/includes/header.php to restore functionality.");


// Authorization check for all superadmin pages
// If ANY of these session variables are not set, redirect to the login page.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'superadmin') {
    // Before redirecting, destroy the invalid session for security
    session_unset();
    session_destroy();
    header('Location: /superadmin/index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Superadmin Portal'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="h-full">