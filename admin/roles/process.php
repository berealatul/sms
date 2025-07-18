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
$redirect_url = '/admin/roles/';

try {
    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        if (empty($name)) throw new Exception("Role name cannot be empty.");

        $stmt = $conn->prepare("INSERT INTO association_types (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $_SESSION['success_message'] = "Role added successfully.";
    } elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name']);
        if (!$id || empty($name)) throw new Exception("Invalid input for editing role.");

        $stmt = $conn->prepare("UPDATE association_types SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
        $_SESSION['success_message'] = "Role updated successfully.";
    } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) throw new Exception("Invalid Role ID.");

        $stmt = $conn->prepare("DELETE FROM association_types WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['success_message'] = "Role deleted successfully.";
    } else {
        throw new Exception("Invalid action.");
    }
} catch (Exception $e) {
    // Catch database errors, such as foreign key constraints on delete
    if ($conn->errno == 1451) { // Error code for foreign key constraint violation
        $_SESSION['error_message'] = "Cannot delete this role because it is currently assigned to one or more students.";
    } else {
        $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
    }
}

header("Location: $redirect_url");
exit();
