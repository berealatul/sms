<?php
require_once __DIR__ . '/../../includes/header.php';

// Authorization: Allow only admin and staff
if (!isset($_SESSION['user_role_name']) || !in_array($_SESSION['user_role_name'], ['admin', 'staff'])) {
    $_SESSION['error_message'] = "You are not authorized to perform this action.";
    header('Location: /');
    exit();
}

$redirect_url = '/admin/assignments/';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect_url);
    exit();
}

$conn->begin_transaction();

try {
    $faculty_id = filter_input(INPUT_POST, 'faculty_id', FILTER_VALIDATE_INT);
    $association_type_id = filter_input(INPUT_POST, 'association_type_id', FILTER_VALIDATE_INT);
    $roll_numbers_raw = trim($_POST['roll_numbers'] ?? '');

    if (!$faculty_id || !$association_type_id || empty($roll_numbers_raw)) {
        throw new Exception("All fields are required.");
    }

    // Process the roll numbers
    $roll_numbers_array = array_map('trim', explode(',', $roll_numbers_raw));
    $roll_numbers_array = array_filter($roll_numbers_array); // Remove empty entries

    if (empty($roll_numbers_array)) {
        throw new Exception("Please provide at least one valid roll number.");
    }

    // Prepare statements
    $stmt_get_student = $conn->prepare("SELECT id FROM students WHERE roll_number = ?");
    $stmt_insert_assoc = $conn->prepare("INSERT INTO faculty_student_associations (faculty_id, student_id, association_type_id) VALUES (?, ?, ?)");

    $assigned_count = 0;
    $not_found_rolls = [];
    $already_assigned_rolls = [];

    foreach ($roll_numbers_array as $roll) {
        $stmt_get_student->bind_param("s", $roll);
        $stmt_get_student->execute();
        $student_result = $stmt_get_student->get_result();

        if ($student = $student_result->fetch_assoc()) {
            $student_id = $student['id'];
            
            // Try to insert the new association
            $stmt_insert_assoc->bind_param("iii", $faculty_id, $student_id, $association_type_id);
            if ($stmt_insert_assoc->execute()) {
                if ($conn->affected_rows > 0) {
                    $assigned_count++;
                } else {
                    // This can happen if the insert is ignored due to a duplicate key
                    $already_assigned_rolls[] = $roll;
                }
            } else {
                 // Check for duplicate entry error specifically
                if ($conn->errno == 1062) { // 1062 is the MySQL error code for duplicate entry
                    $already_assigned_rolls[] = $roll;
                } else {
                    throw new Exception("Database error on inserting association for roll: " . $roll);
                }
            }
        } else {
            $not_found_rolls[] = $roll;
        }
    }
    
    $stmt_get_student->close();
    $stmt_insert_assoc->close();
    
    // Construct feedback message
    $success_msg = "Assignment complete.\n- Successfully assigned to " . $assigned_count . " student(s).";
    if (!empty($already_assigned_rolls)) {
        $success_msg .= "\n- Already assigned to: " . implode(', ', $already_assigned_rolls);
    }
    if (!empty($not_found_rolls)) {
        $success_msg .= "\n- Roll numbers not found: " . implode(', ', $not_found_rolls);
    }
    
    $_SESSION['success_message'] = $success_msg;
    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
}

header('Location: ' . $redirect_url);
exit();