<?php
require_once __DIR__ . '/../../includes/header.php';

// Authorization check for student
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'student' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$redirect_url = '/student/personal/index.php';

$conn->begin_transaction();

try {
    // Sanitize and get data for the 'students' table
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $phone_self = trim($_POST['phone_self'] ?? '');
    $phone_guardian = trim($_POST['phone_guardian'] ?? '');
    $current_address = trim($_POST['current_address'] ?? '');
    $permanent_address = trim($_POST['permanent_address'] ?? '');

    // Sanitize and get data for the 'users' table
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$email) {
        throw new Exception("A valid email address is required.");
    }

    // --- Upsert logic for the 'students' table ---
    $stmt_check = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $student_exists = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($student_exists) {
        // UPDATE existing student record
        $stmt_student = $conn->prepare("UPDATE students SET date_of_birth = ?, phone_number_self = ?, phone_number_guardian = ?, current_address = ?, permanent_address = ? WHERE user_id = ?");
        $stmt_student->bind_param("sssssi", $date_of_birth, $phone_self, $phone_guardian, $current_address, $permanent_address, $user_id); // Add 's' for the date string
    } else {
        // INSERT new student record
        $stmt_student = $conn->prepare("INSERT INTO students (user_id, date_of_birth, phone_number_self, phone_number_guardian, current_address, permanent_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_student->bind_param("isssss", $user_id, $date_of_birth, $phone_self, $phone_guardian, $current_address, $permanent_address); // Add 's' for the date string
    }
    $stmt_student->execute();
    $stmt_student->close();

    // Update the 'users'
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt_user = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
        $stmt_user->bind_param("ssi", $email, $hashed_password, $user_id);
    } else {
        $stmt_user = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt_user->bind_param("si", $email, $user_id);
    }
    $stmt_user->execute();
    $stmt_user->close();

    $conn->commit();
    $_SESSION['success_message'] = "Your profile has been updated successfully.";

} catch (Exception $e) {
    $conn->rollback();
    if ($conn->errno == 1062) {
         $_SESSION['error_message'] = "This email address is already in use by another account.";
    } else {
        $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
    }
}

header("Location: $redirect_url");
exit();