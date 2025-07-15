<?php
// process_auth.php
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => 'studentmonitoring.com',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once __DIR__ . '/config/db.php';

function getRoleNameById($conn, $user_type_id)
{
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

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: /index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email) $errors[] = "A valid email address is required.";
    if (empty($password)) $errors[] = "Password cannot be empty.";

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: /index.php');
        exit();
    }

    $stmt = $conn->prepare("SELECT id, name, password, user_type_id, is_active FROM users WHERE email = ?");
    if (!$stmt) {
        error_log("Login Prepare Failed: " . $conn->error);
        $_SESSION['errors'] = ["An unexpected error occurred."];
        header('Location: /index.php');
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['is_active']) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_type_id'] = $user['user_type_id'];
            $_SESSION['user_role_name'] = getRoleNameById($conn, $user['user_type_id']);
            $_SESSION['login_time'] = time();

            // --- Corrected Redirect Logic ---
            switch ($_SESSION['user_role_name']) {
                case 'admin':
                    header('Location: /admin/');
                    break;
                case 'faculty':
                    header('Location: /faculty/');
                    break;
                case 'student':
                    header('Location: /student/');
                    break;
                case 'staff':
                    header('Location: /staff/');
                    break;
                default:
                    session_unset();
                    session_destroy();
                    header('Location: /index.php');
                    break;
            }
            exit();
        } else {
            $errors[] = "Your account has been deactivated.";
        }
    } else {
        $errors[] = "Invalid email or password combination.";
    }

    $_SESSION['errors'] = $errors;
    header('Location: /index.php');
    exit();
} else {
    header('Location: /index.php');
    exit();
}
