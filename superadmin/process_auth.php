<?php
// Start output buffering to prevent header errors
ob_start();

require_once __DIR__ . '/../config/db.php';

// Logout logic
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: /superadmin/index.php');
    exit();
}

// Login Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'superadmin_login') {
    $errors = [];
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email) $errors[] = "A valid email address is required.";
    if (empty($password)) $errors[] = "Password cannot be empty.";

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: /superadmin/index.php');
        exit();
    }

    // Prepare statement to fetch user details including the role name
    $stmt = $conn->prepare(
        "SELECT u.id, u.name, u.password, u.is_active, r.name as role_name 
         FROM users u 
         JOIN roles r ON u.role_id = r.id 
         WHERE u.email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // --- Verification Logic ---
    if ($user && password_verify($password, $user['password'])) {
        // Condition 1: Check if the account is active
        if (!$user['is_active']) {
            $errors[] = "Your account has been deactivated.";
        }
        // Condition 2: Check if the user has the 'superadmin' role
        elseif ($user['role_name'] !== 'superadmin') {
            $errors[] = "You are not authorized to access this portal.";
        }
        // Success: All checks passed
        else {
            // Regenerate session ID for security
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role_name'] = $user['role_name'];
            $_SESSION['login_time'] = time();

            // Redirect to the dashboard
            header('Location: /superadmin/dashboard.php');
            exit(); // Crucial to prevent further script execution
        }
    } else {
        $errors[] = "Invalid email or password combination.";
    }

    // If we reached here, it means login failed.
    $_SESSION['errors'] = $errors;
    header('Location: /superadmin/index.php');
    exit();
} else {
    // Redirect if not a POST request
    header('Location: /superadmin/index.php');
    exit();
}

// Flush the output buffer
ob_end_flush();
