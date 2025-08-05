<?php
require_once __DIR__ . '/../includes/header.php'; // Ensures session, db, and admin auth

// Only allow POST requests for actions that modify data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['action'])) {
    if ($_GET['action'] !== 'toggle_status') {
        header('Location: index.php');
        exit();
    }
}

$action = $_REQUEST['action'] ?? null;
$id = $_REQUEST['id'] ?? null;
$redirect_url = '/administration/departments/';

try {
    $conn->begin_transaction();

    if ($action === 'add') {
        // --- Add Department and HOD ---
        $name = trim($_POST['name']);
        $code = trim($_POST['code']);
        $hod_name = trim($_POST['hod_name']);
        $hod_email = filter_input(INPUT_POST, 'hod_email', FILTER_VALIDATE_EMAIL);
        $hod_password = $_POST['hod_password'];

        if (empty($name) || empty($code) || empty($hod_name) || !$hod_email || empty($hod_password)) {
            throw new Exception("All fields are required.");
        }
        if (strlen($hod_password) < 8) {
            throw new Exception("Password must be at least 8 characters long.");
        }

        // 1. Create the department
        $stmt = $conn->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $code);
        $stmt->execute();
        $department_id = $conn->insert_id;
        $stmt->close();

        // 2. Create the HOD user
        $hashed_password = password_hash($hod_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (department_id, name, email, password, role) VALUES (?, ?, ?, ?, 'hod')");
        $stmt->bind_param("isss", $department_id, $hod_name, $hod_email, $hashed_password);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success_message'] = "Department and HOD created successfully.";
    } elseif ($action === 'edit' && !empty($id)) {
        // --- Edit Department and HOD ---
        $name = trim($_POST['name']);
        $code = trim($_POST['code']);
        $hod_name = trim($_POST['hod_name']);
        $hod_email = filter_input(INPUT_POST, 'hod_email', FILTER_VALIDATE_EMAIL);
        $hod_password = $_POST['hod_password'];

        if (empty($name) || empty($code) || empty($hod_name) || !$hod_email) {
            throw new Exception("Department and HOD name/email fields cannot be empty.");
        }

        // 1. Update department details
        $stmt = $conn->prepare("UPDATE departments SET name = ?, code = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $code, $id);
        $stmt->execute();
        $stmt->close();

        // 2. Update HOD details
        if (!empty($hod_password)) {
            if (strlen($hod_password) < 8) {
                throw new Exception("New password must be at least 8 characters long.");
            }
            // If password is provided, update it and the password_updated_at timestamp
            $hashed_password = password_hash($hod_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, password_updated_at = NOW() WHERE department_id = ? AND role = 'hod'");
            $stmt->bind_param("sssi", $hod_name, $hod_email, $hashed_password, $id);
        } else {
            // Otherwise, update without changing password
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE department_id = ? AND role = 'hod'");
            $stmt->bind_param("ssi", $hod_name, $hod_email, $id);
        }
        $stmt->execute();
        $stmt->close();

        $_SESSION['success_message'] = "Department updated successfully.";
    } elseif ($action === 'toggle_status' && !empty($id)) {
        // --- Toggle Department Status ---
        $stmt = $conn->prepare("UPDATE departments SET is_active = !is_active WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success_message'] = "Department status updated.";
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    // Log the detailed, technical error for the administrator to review
    error_log("Department Processing Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());

    // Check for specific, user-friendly errors
    if ($conn->errno == 1062) {
        $_SESSION['error_message'] = "A department with that code or an HOD with that email already exists.";
    } elseif (strpos($e->getMessage(), 'Password') !== false) {
        $_SESSION['error_message'] = $e->getMessage(); // Show password policy errors
    } else {
        // For all other errors, show a generic message
        $_SESSION['error_message'] = "An unexpected error occurred. Please try again.";
    }
}

header("Location: $redirect_url");
exit();
