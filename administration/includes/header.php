<?php
require_once __DIR__ . '/../../config/db.php';

// --- Comprehensive Security Check ---

$is_authenticated = true;

// 1. Check for basic session variables
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrator') {
    $is_authenticated = false;
}

// 2. Check for session timeout
if ($is_authenticated && (time() - $_SESSION['login_time']) > LOGOUT_TIME) {
    $is_authenticated = false;
    // Optionally, set a message: $_SESSION['logout_message'] = "You have been logged out due to inactivity.";
}

// 3. Check if password has changed since session was created
if ($is_authenticated) {
    $stmt = $conn->prepare("SELECT password_updated_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result || md5($result['password_updated_at']) !== $_SESSION['password_hash']) {
        // Password has changed or user doesn't exist. Invalidate session.
        $is_authenticated = false;
    }
}

// If any check fails, destroy the session and redirect to login
if (!$is_authenticated) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
    header('Location: /administration/index.php');
    exit();
}

// Refresh the session timeout on each valid page load
$_SESSION['login_time'] = time();
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') : 'Administration Portal'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
</head>

<body class="h-full font-sans">