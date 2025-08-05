<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /superadmin/register.php');
    exit();
}

$errors = [];
$name = trim($_POST['name'] ?? '');
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

// --- Validation ---
if (empty($name)) {
    $errors[] = "Name is required.";
}
if (!$email) {
    $errors[] = "A valid email address is required.";
}
if (empty($password) || strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters long.";
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: /superadmin/register.php');
    exit();
}

// --- Process Registration ---
$conn->begin_transaction();

try {
    // 1. Get the role_id for 'superadmin'
    $role_stmt = $conn->prepare("SELECT id FROM roles WHERE name = 'superadmin' LIMIT 1");
    $role_stmt->execute();
    $role_result = $role_stmt->get_result();
    if ($role_result->num_rows === 0) {
        throw new Exception("Critical Error: 'superadmin' role not found in the database.");
    }
    $superadmin_role_id = $role_result->fetch_assoc()['id'];
    $role_stmt->close();

    // 2. Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 3. Insert the new user into the 'users' table
    $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)");
    $insert_stmt->bind_param("sssi", $name, $email, $hashed_password, $superadmin_role_id);

    if ($insert_stmt->execute()) {
        $conn->commit();
        $_SESSION['success_message'] = "Registration successful! You can now log in.";
        header('Location: /superadmin/register.php');
    } else {
        // Handle potential duplicate email error
        if ($conn->errno == 1062) { // 1062 is the MySQL error code for duplicate entry
            throw new Exception("An account with this email address already exists.");
        } else {
            throw new Exception("An unknown error occurred during registration.");
        }
    }
    $insert_stmt->close();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['errors'] = [$e->getMessage()];
    header('Location: /superadmin/register.php');
    exit();
}
