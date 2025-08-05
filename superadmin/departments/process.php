<?php
require_once __DIR__ . '/../includes/header.php'; // Ensures session and DB connection

// Double-check authorization
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'superadmin') {
    $_SESSION['error_message'] = "You are not authorized to perform this action.";
    header('Location: /superadmin/index.php');
    exit();
}

$action = $_REQUEST['action'] ?? null;
$id = $_REQUEST['id'] ?? null;
$redirect_url = '/superadmin/departments/';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize all inputs
        $department_name = trim($_POST['department_name'] ?? '');
        $department_code = trim($_POST['department_code'] ?? '');
        $hod_name = trim($_POST['hod_name'] ?? '');
        $hod_email = filter_input(INPUT_POST, 'hod_email', FILTER_VALIDATE_EMAIL);
        $db_name = trim($_POST['db_name'] ?? '');

        // Basic validation
        if (empty($department_name) || empty($department_code) || empty($hod_name) || !$hod_email || empty($db_name)) {
            throw new Exception("Invalid input. Please check all fields.");
        }

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO departments (department_name, department_code, hod_name, hod_email, db_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $department_name, $department_code, $hod_name, $hod_email, $db_name);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Department added successfully.";
                // Here, you could also add logic to automatically create the new database and its tables.
            } else {
                throw new Exception("Failed to add department. Code, HOD email, or DB name might already exist.");
            }
            $stmt->close();
        } elseif ($action === 'edit' && !empty($id)) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            if ($id === false) throw new Exception("Invalid Department ID.");

            $stmt = $conn->prepare("UPDATE departments SET department_name = ?, department_code = ?, hod_name = ?, hod_email = ?, db_name = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $department_name, $department_code, $hod_name, $hod_email, $db_name, $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Department updated successfully.";
            } else {
                throw new Exception("Failed to update department. Check for duplicate values.");
            }
            $stmt->close();
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'toggle_status' && !empty($id)) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false) throw new Exception("Invalid Department ID.");

        $stmt_get = $conn->prepare("SELECT is_active FROM departments WHERE id = ?");
        $stmt_get->bind_param("i", $id);
        $stmt_get->execute();
        $current = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();

        if (!$current) throw new Exception("Department not found.");

        $new_status = !$current['is_active'];
        $stmt_toggle = $conn->prepare("UPDATE departments SET is_active = ? WHERE id = ?");
        $stmt_toggle->bind_param("ii", $new_status, $id);
        if ($stmt_toggle->execute()) {
            $_SESSION['success_message'] = "Department status updated successfully.";
        } else {
            throw new Exception("Failed to update department status.");
        }
        $stmt_toggle->close();
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

header("Location: $redirect_url");
exit();
