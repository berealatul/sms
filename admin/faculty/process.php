<?php
require_once __DIR__ . '/../../includes/header.php';

// Authorization: Allow only admin
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'admin') {
    $_SESSION['error_message'] = "You are not authorized to perform this action.";
    header('Location: /');
    exit();
}

$action = $_REQUEST['action'] ?? null;
$id = $_REQUEST['id'] ?? null; // This will be the user_id
$redirect_url = '/admin/faculty/';

$conn->begin_transaction();

try {
    if ($action === 'add') {
        $name = trim($_POST['name']);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];
        $phone = trim($_POST['phone'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');

        if (!$name || !$email || empty($password)) {
            throw new Exception("Name, Email, and Password are required.");
        }

        // Get faculty user type ID
        $stmt_role = $conn->prepare("SELECT id FROM user_types WHERE name = 'faculty' LIMIT 1");
        $stmt_role->execute();
        $faculty_role_id = $stmt_role->get_result()->fetch_assoc()['id'];
        $stmt_role->close();
        if (!$faculty_role_id) throw new Exception("Faculty role not found.");

        // Insert into users table
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt_user = $conn->prepare("INSERT INTO users (name, email, password, user_type_id) VALUES (?, ?, ?, ?)");
        $stmt_user->bind_param("sssi", $name, $email, $hashed_password, $faculty_role_id);
        $stmt_user->execute();
        $user_id = $conn->insert_id;
        $stmt_user->close();

        // Insert into faculty table
        $stmt_faculty = $conn->prepare("INSERT INTO faculty (user_id, phone_number, specialization) VALUES (?, ?, ?)");
        $stmt_faculty->bind_param("iss", $user_id, $phone, $specialization);
        $stmt_faculty->execute();
        $stmt_faculty->close();

        $_SESSION['success_message'] = "Faculty added successfully.";
    } elseif ($action === 'edit' && !empty($id)) {
        $user_id = filter_var($id, FILTER_VALIDATE_INT);
        $name = trim($_POST['name']);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];
        $phone = trim($_POST['phone'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');

        if (!$user_id || !$name || !$email) {
            throw new Exception("Invalid input for editing faculty.");
        }

        // Update users table
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_user = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
            $stmt_user->bind_param("sssi", $name, $email, $hashed_password, $user_id);
        } else {
            $stmt_user = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt_user->bind_param("ssi", $name, $email, $user_id);
        }
        $stmt_user->execute();
        $stmt_user->close();

        // Update faculty table
        $stmt_faculty = $conn->prepare("UPDATE faculty SET phone_number = ?, specialization = ? WHERE user_id = ?");
        $stmt_faculty->bind_param("ssi", $phone, $specialization, $user_id);
        $stmt_faculty->execute();
        $stmt_faculty->close();

        $_SESSION['success_message'] = "Faculty updated successfully.";
    } elseif ($action === 'toggle_status' && !empty($id)) {
        $user_id = filter_var($id, FILTER_VALIDATE_INT);
        if (!$user_id) throw new Exception("Invalid User ID.");

        $stmt_get = $conn->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt_get->bind_param("i", $user_id);
        $stmt_get->execute();
        $current = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();

        if (!$current) throw new Exception("User not found.");

        $new_status = !$current['is_active'];
        $stmt_toggle = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt_toggle->bind_param("ii", $new_status, $user_id);
        $stmt_toggle->execute();
        $stmt_toggle->close();

        $_SESSION['success_message'] = "Faculty status updated successfully.";
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
}

header("Location: $redirect_url");
exit();
