<?php
require_once __DIR__ . '/../../includes/header.php';

// Authorization: Allow only admin and staff
if (!isset($_SESSION['user_role_name']) || !in_array($_SESSION['user_role_name'], ['admin', 'staff'])) {
    $_SESSION['error_message'] = "You are not authorized to perform this action.";
    header('Location: /');
    exit();
}

$action = $_REQUEST['action'] ?? null;
$id = $_REQUEST['id'] ?? null;
$redirect_url = '/admin/students/';

// Get the user type ID for 'student'
$student_role_stmt = $conn->prepare("SELECT id FROM user_types WHERE name = 'student' LIMIT 1");
$student_role_stmt->execute();
$student_role_id = $student_role_stmt->get_result()->fetch_assoc()['id'];
$student_role_stmt->close();

if (!$student_role_id) {
    $_SESSION['error_message'] = "Critical error: 'student' role not found.";
    header("Location: $redirect_url");
    exit();
}

$conn->begin_transaction();

try {
    if ($action === 'add') {
        $name = trim($_POST['name']);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $roll_number = trim($_POST['roll_number']);
        $programme_id = filter_input(INPUT_POST, 'programme_id', FILTER_VALIDATE_INT);
        $batch_id = filter_input(INPUT_POST, 'batch_id', FILTER_VALIDATE_INT);

        if (!$name || !$email || !$roll_number || !$programme_id || !$batch_id) throw new Exception("All fields are required.");

        // Default password is the email
        $hashed_password = password_hash($email, PASSWORD_DEFAULT);

        // Insert into users table
        $stmt_user = $conn->prepare("INSERT INTO users (name, email, password, user_type_id) VALUES (?, ?, ?, ?)");
        $stmt_user->bind_param("sssi", $name, $email, $hashed_password, $student_role_id);
        $stmt_user->execute();
        $user_id = $conn->insert_id;
        $stmt_user->close();

        // Insert into students table
        $stmt_student = $conn->prepare("INSERT INTO students (user_id, roll_number, programme_id, batch_id) VALUES (?, ?, ?, ?)");
        $stmt_student->bind_param("isii", $user_id, $roll_number, $programme_id, $batch_id);
        $stmt_student->execute();
        $stmt_student->close();

        $_SESSION['success_message'] = "Student added successfully.";
    } elseif ($action === 'edit' && !empty($id)) {
        $user_id = filter_var($id, FILTER_VALIDATE_INT);
        $name = trim($_POST['name']);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $roll_number = trim($_POST['roll_number']);
        $programme_id = filter_input(INPUT_POST, 'programme_id', FILTER_VALIDATE_INT);
        $batch_id = filter_input(INPUT_POST, 'batch_id', FILTER_VALIDATE_INT);
        $is_active = filter_input(INPUT_POST, 'is_active', FILTER_VALIDATE_INT); // Get the new status

        if (!$user_id || !$name || !$email || !$roll_number || !$programme_id || !$batch_id || !in_array($is_active, [0, 1])) { // Add validation
            throw new Exception("All fields are required for editing.");
        }

        // Update users table
        $stmt_user = $conn->prepare("UPDATE users SET name = ?, email = ?, is_active = ? WHERE id = ?"); // Add is_active
        $stmt_user->bind_param("ssii", $name, $email, $is_active, $user_id); // Update binding
        $stmt_user->execute();
        $stmt_user->close();

        // Update students table (this part remains the same)
        $stmt_student = $conn->prepare("UPDATE students SET roll_number = ?, programme_id = ?, batch_id = ? WHERE user_id = ?");
        $stmt_student->bind_param("siii", $roll_number, $programme_id, $batch_id, $user_id);
        $stmt_student->execute();
        $stmt_student->close();

        $_SESSION['success_message'] = "Student updated successfully.";
    } elseif ($action === 'reset_password' && !empty($id)) {
        $user_id = filter_var($id, FILTER_VALIDATE_INT);
        if (!$user_id) throw new Exception("Invalid User ID.");

        $stmt_get = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt_get->bind_param("i", $user_id);
        $stmt_get->execute();
        $user = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();

        if (!$user) throw new Exception("User not found.");
        $new_plaintext_password = strtolower($user['email']);
        $new_password = password_hash($user['email'], PASSWORD_DEFAULT);
        $stmt_reset = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt_reset->bind_param("si", $new_password, $user_id);
        $stmt_reset->execute();
        $stmt_reset->close();

        $_SESSION['success_message'] = "Password has been reset to the user's email address(all in small letters).";
    } elseif ($action === 'upload_csv' && isset($_FILES['csv_file'])) {
        $programme_id = filter_input(INPUT_POST, 'programme_id', FILTER_VALIDATE_INT);
        $batch_id = filter_input(INPUT_POST, 'batch_id', FILTER_VALIDATE_INT);

        if (!$programme_id || !$batch_id) throw new Exception("Programme and Batch must be selected for CSV upload.");

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");

        // Skip header row
        fgetcsv($handle);

        $count = 0;
        while (($data = fgetcsv($handle)) !== FALSE) {
            $name = trim($data[0]);
            $roll_number = trim($data[1]);
            $email = filter_var(trim($data[2]), FILTER_VALIDATE_EMAIL);

            if (!$name || !$roll_number || !$email) continue; // Skip invalid rows

            $new_plaintext_password = strtolower($email);
            $hashed_password = password_hash($new_plaintext_password, PASSWORD_DEFAULT);
            $stmt_user = $conn->prepare("INSERT INTO users (name, email, password, user_type_id) VALUES (?, ?, ?, ?)");
            $stmt_user->bind_param("sssi", $name, $email, $hashed_password, $student_role_id);
            $stmt_user->execute();
            $user_id = $conn->insert_id;
            $stmt_user->close();

            $stmt_student = $conn->prepare("INSERT INTO students (user_id, roll_number, programme_id, batch_id) VALUES (?, ?, ?, ?)");
            $stmt_student->bind_param("isii", $user_id, $roll_number, $programme_id, $batch_id);
            $stmt_student->execute();
            $stmt_student->close();
            $count++;
        }
        fclose($handle);
        $_SESSION['success_message'] = "$count students uploaded successfully.";
    } elseif ($action === 'archive_batch') {
        if ($_SESSION['user_role_name'] !== 'admin') {
            throw new Exception("You are not authorized to perform this action.");
        }
        $programme_id = filter_input(INPUT_POST, 'programme_id', FILTER_VALIDATE_INT);
        $batch_id = filter_input(INPUT_POST, 'batch_id', FILTER_VALIDATE_INT);
        $archive_action = $_POST['archive_action'] ?? '';

        if (!$programme_id || !$batch_id || !in_array($archive_action, ['deactivate', 'delete'])) {
            throw new Exception("Invalid input for archiving.");
        }

        // Get all student user IDs in the batch
        $stmt_get_users = $conn->prepare("SELECT user_id FROM students WHERE programme_id = ? AND batch_id = ?");
        $stmt_get_users->bind_param("ii", $programme_id, $batch_id);
        $stmt_get_users->execute();
        $result = $stmt_get_users->get_result();
        $user_ids = [];
        while ($row = $result->fetch_assoc()) {
            $user_ids[] = $row['user_id'];
        }
        $stmt_get_users->close();

        if (empty($user_ids)) {
            throw new Exception("No students found in the selected batch.");
        }

        $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
        $types = str_repeat('i', count($user_ids));

        if ($archive_action === 'deactivate') {
            $stmt_deactivate = $conn->prepare("UPDATE users SET is_active = 0 WHERE id IN ($placeholders)");
            $stmt_deactivate->bind_param($types, ...$user_ids);
            $stmt_deactivate->execute();
            $stmt_deactivate->close();
            $_SESSION['success_message'] = count($user_ids) . " students have been deactivated.";
        } elseif ($archive_action === 'delete') {
            // Deleting from the `users` table will cascade to `students`
            $stmt_delete = $conn->prepare("DELETE FROM users WHERE id IN ($placeholders)");
            $stmt_delete->bind_param($types, ...$user_ids);
            $stmt_delete->execute();
            $stmt_delete->close();
            $_SESSION['success_message'] = count($user_ids) . " students have been permanently deleted.";
        }
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
}

header("Location: $redirect_url");
exit();
