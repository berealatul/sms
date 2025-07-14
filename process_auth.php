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

// --- Include Database Connection ---
require_once __DIR__ . '/config/db.php';

// --- Helper function to get role name from the database ---
function getRoleNameById($conn, $user_type_id)
{
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT name FROM user_types WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare failed in getRoleNameById: " . $conn->error);
        return 'unknown';
    }

    $stmt->bind_param("i", $user_type_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($role = $result->fetch_assoc()) {
        $stmt->close();
        return $role['name'];
    }
    $stmt->close();
    return 'unknown';
}

// --- Handle Logout ---
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}

// --- Handle Login Request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // --- Input Validation ---
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email) {
        $errors[] = "A valid email address is required.";
    }
    if (empty($password)) {
        $errors[] = "Password cannot be empty.";
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: index.php');
        exit();
    }

    // --- User Authentication using Prepared Statements (Corrected Query) ---
    $stmt = $conn->prepare("SELECT id, name, password, user_type_id, is_active FROM users WHERE email = ?");
    if (!$stmt) {
        error_log("Login Prepare Failed: " . $conn->error);
        $_SESSION['errors'] = ["An unexpected error occurred. Please try again."];
        header('Location: index.php');
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        // --- Verify Password and User Status ---
        if (password_verify($password, $user['password'])) {
            if ($user['is_active']) {
                // --- Login Successful: Regenerate session ID and set session data ---
                session_regenerate_id(true); // Prevents session fixation

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_type_id'] = $user['user_type_id']; // Corrected column name
                $_SESSION['user_role_name'] = getRoleNameById($conn, $user['user_type_id']); // Use the correct variable
                $_SESSION['login_time'] = time();

                // --- Redirect based on role ---
                switch ($_SESSION['user_role_name']) {
                    case 'admin':
                        header('Location: admin.php');
                        break;
                    // Add cases for 'faculty', 'student' etc. later
                    default:
                        // Log them out if they have an unknown role
                        session_unset();
                        session_destroy();
                        header('Location: index.php');
                        break;
                }
                exit();
            } else {
                $errors[] = "Your account has been deactivated.";
            }
        } else {
            $errors[] = "Invalid email or password combination.";
        }
    } else {
        $errors[] = "Invalid email or password combination.";
    }

    // --- Login Failed: Store errors and redirect back ---
    $_SESSION['errors'] = $errors;
    header('Location: index.php');
    exit();
} else {
    // --- Redirect if accessed directly ---
    header('Location: index.php');
    exit();
}
