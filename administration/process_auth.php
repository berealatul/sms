<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'admin_login') {
    header('Location: index.php');
    exit();
}

$errors = [];
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email || empty($password)) {
    $_SESSION['errors'] = ["Email and password are required."];
    header('Location: index.php');
    exit();
}

// Prepare to fetch user data for brute-force check
$stmt = $conn->prepare("SELECT id, name, password, role, is_active, password_updated_at, failed_login_attempts, lockout_until FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Brute-Force Protection ---
if ($user) {
    // Check if account is currently locked
    if ($user['lockout_until'] && new DateTime() < new DateTime($user['lockout_until'])) {
        $_SESSION['errors'] = ["This account is locked due to too many failed login attempts. Please try again later."];
        header('Location: index.php');
        exit();
    }
}

if ($user && password_verify($password, $user['password'])) {
    // --- Login Success ---
    if (!$user['is_active']) {
        $errors[] = "Your account has been deactivated.";
    } elseif ($user['role'] !== 'administrator') {
        $errors[] = "You are not authorized to access this portal.";
    } else {
        // Reset failed attempts on successful login
        $stmt = $conn->prepare("UPDATE users SET failed_login_attempts = 0, lockout_until = NULL WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $stmt->close();

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['password_hash'] = md5($user['password_updated_at']); // Store a hash of the password timestamp
        $_SESSION['initiated'] = true;

        session_write_close();
        header('Location: dashboard.php');
        exit();
    }
} else {
    // --- Login Failure ---
    if ($user) {
        // User exists, so it's a failed password attempt.
        $attempts = $user['failed_login_attempts'] + 1;
        $lockout_sql = "";
        if ($attempts >= LOGIN_ATTEMPT_LIMIT) {
            $lockout_sql = ", lockout_until = NOW() + INTERVAL " . LOGIN_LOCKOUT_PERIOD;
            $_SESSION['errors'] = ["This account has been locked for your security. Please try again in 5 minutes."];
        } else {
            $_SESSION['errors'] = ["Invalid email or password combination."];
        }

        $stmt = $conn->prepare("UPDATE users SET failed_login_attempts = ? $lockout_sql WHERE id = ?");
        $stmt->bind_param("ii", $attempts, $user['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        // User does not exist. Generic error.
        $_SESSION['errors'] = ["Invalid email or password combination."];
    }
    header('Location: index.php');
    exit();
}

// If we reach here, it's a failure with a generic message
$_SESSION['errors'] = $errors;
header('Location: index.php');
exit();
