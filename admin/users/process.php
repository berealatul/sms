<?php
require_once __DIR__ . '/../../includes/header.php';

// Authorization: Allow only admin
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'admin') {
    $_SESSION['error_message'] = "You are not authorized to perform this action.";
    header('Location: /');
    exit();
}

$action = $_REQUEST['action'] ?? null;
$id = $_REQUEST['id'] ?? null;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $user_type_id = filter_input(INPUT_POST, 'user_type_id', FILTER_VALIDATE_INT);

        if ($action === 'add') {
            if (empty($name) || !$email || empty($password) || !$user_type_id) {
                throw new Exception("Invalid input. Please fill out all required fields.");
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $name, $email, $hashed_password, $user_type_id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "User added successfully.";
            } else {
                throw new Exception("Failed to add user. Email might already exist.");
            }
            $stmt->close();
        } elseif ($action === 'edit' && !empty($id)) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            if (!$id || empty($name) || !$email || !$user_type_id) {
                throw new Exception("Invalid input for editing user.");
            }

            if (!empty($password)) {
                // Update password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, user_type_id = ? WHERE id = ?");
                $stmt->bind_param("sssii", $name, $email, $hashed_password, $user_type_id, $id);
            } else {
                // Do not update password
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, user_type_id = ? WHERE id = ?");
                $stmt->bind_param("ssii", $name, $email, $user_type_id, $id);
            }

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "User updated successfully.";
            } else {
                throw new Exception("Failed to update user. Email might already exist.");
            }
            $stmt->close();
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'toggle_status' && !empty($id)) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false) throw new Exception("Invalid User ID.");

        // Prevent admin from deactivating themselves
        if ($id === $_SESSION['user_id']) {
            throw new Exception("You cannot change the status of your own account.");
        }

        $stmt_get = $conn->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt_get->bind_param("i", $id);
        $stmt_get->execute();
        $current = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();

        if (!$current) throw new Exception("User not found.");

        $new_status = !$current['is_active'];
        $stmt_toggle = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt_toggle->bind_param("ii", $new_status, $id);
        if ($stmt_toggle->execute()) {
            $_SESSION['success_message'] = "User status updated successfully.";
        } else {
            throw new Exception("Failed to update user status.");
        }
        $stmt_toggle->close();
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

header('Location: /admin/users/');
exit();
