<?php
// Note the change in path due to the new directory structure
require_once __DIR__ . '/../../includes/header.php';

// Security check: Only admins can perform these actions
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'admin') {
    $_SESSION['error_message'] = "You are not authorized to perform this action.";
    header('Location: /admin/programmes/');
    exit();
}

$action = $_REQUEST['action'] ?? null;
$id = $_REQUEST['id'] ?? null;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize and validate inputs
        $name = trim($_POST['name'] ?? '');
        $credit_required = filter_input(INPUT_POST, 'credit_required', FILTER_VALIDATE_INT);
        $minimum_year = filter_input(INPUT_POST, 'minimum_year', FILTER_VALIDATE_INT);
        $maximum_year = filter_input(INPUT_POST, 'maximum_year', FILTER_VALIDATE_INT);
        $degree_levels_id = filter_input(INPUT_POST, 'degree_levels_id', FILTER_VALIDATE_INT);

        if (empty($name) || $credit_required === false || $minimum_year === false || $maximum_year === false || $degree_levels_id === false) {
            throw new Exception("Invalid input provided. Please check all fields.");
        }

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO programmes (name, credit_required, minimum_year, maximum_year, degree_levels_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("siiii", $name, $credit_required, $minimum_year, $maximum_year, $degree_levels_id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Programme added successfully.";
            } else {
                throw new Exception("Failed to add programme. It might already exist.");
            }
            $stmt->close();
        } elseif ($action === 'edit' && !empty($id)) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            if ($id === false) throw new Exception("Invalid Programme ID.");

            $stmt = $conn->prepare("UPDATE programmes SET name = ?, credit_required = ?, minimum_year = ?, maximum_year = ?, degree_levels_id = ? WHERE id = ?");
            $stmt->bind_param("siiiii", $name, $credit_required, $minimum_year, $maximum_year, $degree_levels_id, $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Programme updated successfully.";
            } else {
                throw new Exception("Failed to update programme.");
            }
            $stmt->close();
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'toggle_status' && !empty($id)) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false) throw new Exception("Invalid Programme ID.");

        $stmt_get = $conn->prepare("SELECT is_active FROM programmes WHERE id = ?");
        $stmt_get->bind_param("i", $id);
        $stmt_get->execute();
        $result = $stmt_get->get_result();
        $current = $result->fetch_assoc();
        $stmt_get->close();

        if (!$current) throw new Exception("Programme not found.");

        $new_status = !$current['is_active'];
        $stmt_toggle = $conn->prepare("UPDATE programmes SET is_active = ? WHERE id = ?");
        $stmt_toggle->bind_param("ii", $new_status, $id);
        if ($stmt_toggle->execute()) {
            $_SESSION['success_message'] = "Programme status updated successfully.";
        } else {
            throw new Exception("Failed to update programme status.");
        }
        $stmt_toggle->close();
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

// Redirect back to the programmes page
header('Location: /admin/programmes/');
exit();
