<?php
require_once __DIR__ . '/../../includes/header.php';

// Authorization: Allow only admin and staff
if (!isset($_SESSION['user_role_name']) || !in_array($_SESSION['user_role_name'], ['admin', 'staff'])) {
    $_SESSION['error_message'] = "You are not authorized to perform this action.";
    header('Location: /' . $_SESSION['user_role_name'] . '/');
    exit();
}

$action = $_REQUEST['action'] ?? null;
$id = $_REQUEST['id'] ?? null;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $programme_id = filter_input(INPUT_POST, 'programme_id', FILTER_VALIDATE_INT);

        if (empty($name) || $programme_id === false) {
            throw new Exception("Invalid input. Please fill out all fields.");
        }

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO batches (name, programme_id) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $programme_id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Batch added successfully.";
            } else {
                throw new Exception("Failed to add batch. It might already exist.");
            }
            $stmt->close();
        } elseif ($action === 'edit' && !empty($id)) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            if ($id === false) throw new Exception("Invalid Batch ID.");

            $stmt = $conn->prepare("UPDATE batches SET name = ?, programme_id = ? WHERE id = ?");
            $stmt->bind_param("sii", $name, $programme_id, $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Batch updated successfully.";
            } else {
                throw new Exception("Failed to update batch.");
            }
            $stmt->close();
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'toggle_status' && !empty($id)) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false) throw new Exception("Invalid Batch ID.");

        $stmt_get = $conn->prepare("SELECT is_active FROM batches WHERE id = ?");
        $stmt_get->bind_param("i", $id);
        $stmt_get->execute();
        $current = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();

        if (!$current) throw new Exception("Batch not found.");

        $new_status = !$current['is_active'];
        $stmt_toggle = $conn->prepare("UPDATE batches SET is_active = ? WHERE id = ?");
        $stmt_toggle->bind_param("ii", $new_status, $id);
        if ($stmt_toggle->execute()) {
            $_SESSION['success_message'] = "Batch status updated successfully.";
        } else {
            throw new Exception("Failed to update batch status.");
        }
        $stmt_toggle->close();
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

header('Location: /admin/batches/');
exit();
