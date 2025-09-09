<?php
require_once 'config/Database.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'utils/Response.php';
require_once 'utils/Security.php';

class UserController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        // HOD, STAFF, FACULTY can view users in their department
        $user = AuthMiddleware::requireRole(['HOD', 'STAFF', 'FACULTY']);
        
        // Get query parameters
        $userType = isset($_GET['type']) ? $_GET['type'] : null;
        
        // Validate user type if provided
        $validUserTypes = ['STUDENT', 'FACULTY', 'STAFF', 'HOD'];
        if ($userType && !in_array($userType, $validUserTypes)) {
            Response::error('Invalid user type. Valid types: ' . implode(', ', $validUserTypes), 400);
        }
        
        try {
            // Build query based on user type filter
            $sql = '
                SELECT u.user_id, u.user_type, u.full_name, u.email, u.is_active 
                FROM user_accounts u 
                WHERE u.department_id = ?
            ';
            $params = [$user['department_id']];
            
            // Add user type filter if specified
            if ($userType) {
                $sql .= ' AND u.user_type = ?';
                $params[] = $userType;
            }
            
            $sql .= ' ORDER BY u.user_type, u.full_name';
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll();
            
            Response::send($users);
            
        } catch (Exception $e) {
            error_log("Get users error: " . $e->getMessage());
            Response::error('Failed to retrieve users', 500);
        }
    }
    
    public function store() {
        // HOD can create users in their department
        $user = AuthMiddleware::requireRole(['HOD']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('Invalid JSON input', 400);
        }
        
        $input = Security::validateInput($input, ['user_type', 'email']);
        
        if (!Security::validateEmail($input['email'])) {
            Response::error('Invalid email format', 400);
        }
        
        if (!in_array($input['user_type'], ['FACULTY', 'STAFF', 'STUDENT'])) {
            Response::error('Invalid user type. Only FACULTY, STAFF, STUDENT allowed', 400);
        }
        
        try {
            $this->db->beginTransaction();
            
            // Check if email already exists
            $stmt = $this->db->prepare('SELECT user_id FROM user_accounts WHERE email = ?');
            $stmt->execute([$input['email']]);
            if ($stmt->fetch()) {
                Response::error('Email already exists', 409);
            }
            
            // Create user account
            $defaultPassword = password_hash($input['email'], PASSWORD_DEFAULT);
            $stmt = $this->db->prepare('
                INSERT INTO user_accounts (user_type, department_id, full_name, email, password_hash) 
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$input['user_type'], $user['department_id'], $input['email'], $input['email'], $defaultPassword]);
            $newUserId = $this->db->lastInsertId();
            
            // Create appropriate profile
            if (in_array($input['user_type'], ['FACULTY', 'HOD'])) {
                $stmt = $this->db->prepare('INSERT INTO faculty_profiles (user_id, department_id) VALUES (?, ?)');
                $stmt->execute([$newUserId, $user['department_id']]);
            }
            
            $this->db->commit();
            
            Response::send([
                'message' => 'User created successfully',
                'user_id' => $newUserId,
                'user_type' => $input['user_type'],
                'email' => $input['email']
            ], 201);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Create user error: " . $e->getMessage());
            Response::error('Failed to create user', 500);
        }
    }
    
    public function bulkStore() {
        // HOD can create multiple users at once
        $user = AuthMiddleware::requireRole(['HOD']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['users']) || !is_array($input['users'])) {
            Response::error('Invalid JSON input. Expected {users: [...]}', 400);
        }
        
        if (count($input['users']) > 100) {
            Response::error('Maximum 100 users allowed per bulk operation', 400);
        }
        
        $results = [];
        $errors = [];
        
        try {
            $this->db->beginTransaction();
            
            foreach ($input['users'] as $index => $userData) {
                try {
                    $userData = Security::validateInput($userData, ['user_type', 'email']);
                    
                    if (!Security::validateEmail($userData['email'])) {
                        $errors[] = "Row $index: Invalid email format";
                        continue;
                    }
                    
                    if (!in_array($userData['user_type'], ['FACULTY', 'STAFF', 'STUDENT'])) {
                        $errors[] = "Row $index: Invalid user type";
                        continue;
                    }
                    
                    // Check if email already exists
                    $stmt = $this->db->prepare('SELECT user_id FROM user_accounts WHERE email = ?');
                    $stmt->execute([$userData['email']]);
                    if ($stmt->fetch()) {
                        $errors[] = "Row $index: Email {$userData['email']} already exists";
                        continue;
                    }
                    
                    // Create user account
                    $defaultPassword = password_hash($userData['email'], PASSWORD_DEFAULT);
                    $stmt = $this->db->prepare('
                        INSERT INTO user_accounts (user_type, department_id, full_name, email, password_hash) 
                        VALUES (?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([$userData['user_type'], $user['department_id'], $userData['email'], $userData['email'], $defaultPassword]);
                    $newUserId = $this->db->lastInsertId();
                    
                    // Create appropriate profile
                    if (in_array($userData['user_type'], ['FACULTY', 'HOD'])) {
                        $stmt = $this->db->prepare('INSERT INTO faculty_profiles (user_id, department_id) VALUES (?, ?)');
                        $stmt->execute([$newUserId, $user['department_id']]);
                    }
                    
                    $results[] = [
                        'user_id' => $newUserId,
                        'user_type' => $userData['user_type'],
                        'email' => $userData['email'],
                        'status' => 'created'
                    ];
                    
                } catch (Exception $e) {
                    $errors[] = "Row $index: " . $e->getMessage();
                }
            }
            
            $this->db->commit();
            
            Response::send([
                'message' => 'Bulk user creation completed',
                'created_users' => $results,
                'errors' => $errors,
                'total_created' => count($results),
                'total_errors' => count($errors)
            ]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Bulk create users error: " . $e->getMessage());
            Response::error('Failed to create users', 500);
        }
    }
    
    public function update($userId) {
        // HOD can update any user, STAFF can update students only
        $user = AuthMiddleware::requireRole(['HOD', 'STAFF']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('Invalid JSON input', 400);
        }
        
        try {
            // Get target user details
            $stmt = $this->db->prepare('
                SELECT user_id, user_type, email, department_id 
                FROM user_accounts 
                WHERE user_id = ? AND department_id = ?
            ');
            $stmt->execute([$userId, $user['department_id']]);
            $targetUser = $stmt->fetch();
            
            if (!$targetUser) {
                Response::error('User not found in your department', 404);
            }
            
            // Check permissions based on user role
            if ($user['user_type'] === 'STAFF') {
                // STAFF can only update STUDENT details
                if ($targetUser['user_type'] !== 'STUDENT') {
                    Response::error('Staff can only update student details', 403);
                }
            } elseif ($user['user_type'] === 'HOD') {
                // HOD can update anyone except other HODs from different departments
                if ($targetUser['user_type'] === 'HOD' && $targetUser['user_id'] !== $user['user_id']) {
                    // Check if trying to update another department's HOD
                    if ($targetUser['department_id'] !== $user['department_id']) {
                        Response::error('Cannot update HOD from different department', 403);
                    }
                }
            }
            
            // Validate input fields
            $allowedFields = ['full_name', 'email', 'user_type', 'is_active', 'reset_password'];
            $updateData = [];
            
            foreach ($input as $key => $value) {
                if (!in_array($key, $allowedFields)) {
                    Response::error("Field '$key' is not allowed for update", 400);
                }
            }
            
            // Validate email if provided
            if (isset($input['email']) && !Security::validateEmail($input['email'])) {
                Response::error('Invalid email format', 400);
            }
            
            // Validate user_type if provided (only HOD can change user types)
            if (isset($input['user_type'])) {
                if ($user['user_type'] !== 'HOD') {
                    Response::error('Only HOD can change user types', 403);
                }
                
                $validUserTypes = ['STUDENT', 'FACULTY', 'STAFF'];
                if (!in_array($input['user_type'], $validUserTypes)) {
                    Response::error('Invalid user type. Valid types: ' . implode(', ', $validUserTypes), 400);
                }
            }
            
            $this->db->beginTransaction();
            
            // Handle password reset
            if (isset($input['reset_password']) && $input['reset_password'] === true) {
                // Reset password to email address
                $newPassword = password_hash($targetUser['email'], PASSWORD_DEFAULT);
                $stmt = $this->db->prepare('UPDATE user_accounts SET password_hash = ? WHERE user_id = ?');
                $stmt->execute([$newPassword, $userId]);
                
                error_log("Password reset for user {$targetUser['email']} by {$user['email']}");
            }
            
            // Build update query for other fields
            $updateFields = [];
            $updateValues = [];
            
            if (isset($input['full_name'])) {
                $updateFields[] = 'full_name = ?';
                $updateValues[] = $input['full_name'];
            }
            
            if (isset($input['email'])) {
                // Check if email already exists
                $stmt = $this->db->prepare('SELECT user_id FROM user_accounts WHERE email = ? AND user_id != ?');
                $stmt->execute([$input['email'], $userId]);
                if ($stmt->fetch()) {
                    Response::error('Email already exists', 409);
                }
                
                $updateFields[] = 'email = ?';
                $updateValues[] = $input['email'];
            }
            
            if (isset($input['user_type'])) {
                $updateFields[] = 'user_type = ?';
                $updateValues[] = $input['user_type'];
            }
            
            if (isset($input['is_active'])) {
                $updateFields[] = 'is_active = ?';
                $updateValues[] = $input['is_active'] ? 1 : 0;
            }
            
            // Execute update if there are fields to update
            if (!empty($updateFields)) {
                $updateValues[] = $userId;
                $sql = "UPDATE user_accounts SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($updateValues);
            }
            
            $this->db->commit();
            
            $message = 'User updated successfully';
            if (isset($input['reset_password']) && $input['reset_password'] === true) {
                $message .= '. Password has been reset to user email.';
            }
            
            Response::send(['message' => $message]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Update user error: " . $e->getMessage());
            Response::error('Failed to update user', 500);
        }
    }
    
    public function destroy($userId) {
        // HOD can delete users in their department
        $user = AuthMiddleware::requireRole(['HOD']);
        
        try {
            // Check if user belongs to HOD's department and is not admin/hod
            $stmt = $this->db->prepare('
                SELECT user_type FROM user_accounts 
                WHERE user_id = ? AND department_id = ? AND user_type IN ("FACULTY", "STAFF", "STUDENT")
            ');
            $stmt->execute([$userId, $user['department_id']]);
            $targetUser = $stmt->fetch();
            
            if (!$targetUser) {
                Response::error('User not found or not authorized to delete', 404);
            }
            
            // Delete user (cascade will handle profile deletion)
            $stmt = $this->db->prepare('DELETE FROM user_accounts WHERE user_id = ?');
            $stmt->execute([$userId]);
            
            Response::send(['message' => 'User deleted successfully']);
            
        } catch (Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            Response::error('Failed to delete user', 500);
        }
    }
    
    public function activate() {
        // HOD can activate users in their department
        $user = AuthMiddleware::requireRole(['HOD']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['user_ids'])) {
            Response::error('Invalid JSON input. Expected {user_ids: [...]}', 400);
        }
        
        $userIds = is_array($input['user_ids']) ? $input['user_ids'] : [$input['user_ids']];
        
        if (empty($userIds)) {
            Response::error('No user IDs provided', 400);
        }
        
        try {
            $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
            $params = array_merge($userIds, [$user['department_id']]);
            
            $stmt = $this->db->prepare("
                UPDATE user_accounts 
                SET is_active = TRUE 
                WHERE user_id IN ($placeholders) AND department_id = ?
            ");
            $stmt->execute($params);
            
            $affectedRows = $stmt->rowCount();
            
            Response::send([
                'message' => 'Users activated successfully',
                'activated_count' => $affectedRows
            ]);
            
        } catch (Exception $e) {
            error_log("Activate users error: " . $e->getMessage());
            Response::error('Failed to activate users', 500);
        }
    }
    
    public function activateUser($userId) {
        // Only HOD can activate users in their department
        $user = AuthMiddleware::requireRole(['HOD']);
        
        try {
            // Check if user exists and belongs to HOD's department
            $stmt = $this->db->prepare('
                SELECT user_id, user_type, full_name, email, is_active 
                FROM user_accounts 
                WHERE user_id = ? AND department_id = ?
            ');
            $stmt->execute([$userId, $user['department_id']]);
            $targetUser = $stmt->fetch();
            
            if (!$targetUser) {
                Response::error('User not found in your department', 404);
            }
            
            if ($targetUser['is_active']) {
                Response::error('User is already active', 400);
            }
            
            // Activate user
            $stmt = $this->db->prepare('UPDATE user_accounts SET is_active = 1 WHERE user_id = ?');
            $stmt->execute([$userId]);
            
            error_log("User activated: {$targetUser['email']} by {$user['email']}");
            
            Response::send([
                'message' => 'User activated successfully',
                'user_id' => $userId,
                'email' => $targetUser['email']
            ]);
            
        } catch (Exception $e) {
            error_log("Activate user error: " . $e->getMessage());
            Response::error('Failed to activate user', 500);
        }
    }
    
    public function deactivate($userId) {
        // Only HOD can deactivate users in their department
        $user = AuthMiddleware::requireRole(['HOD']);
        
        try {
            // Check if user exists and belongs to HOD's department
            $stmt = $this->db->prepare('
                SELECT user_id, user_type, full_name, email, is_active 
                FROM user_accounts 
                WHERE user_id = ? AND department_id = ?
            ');
            $stmt->execute([$userId, $user['department_id']]);
            $targetUser = $stmt->fetch();
            
            if (!$targetUser) {
                Response::error('User not found in your department', 404);
            }
            
            // Prevent HOD from deactivating themselves
            if ($targetUser['user_id'] == $user['user_id']) {
                Response::error('You cannot deactivate yourself', 403);
            }
            
            if (!$targetUser['is_active']) {
                Response::error('User is already inactive', 400);
            }
            
            // Deactivate user
            $stmt = $this->db->prepare('UPDATE user_accounts SET is_active = 0 WHERE user_id = ?');
            $stmt->execute([$userId]);
            
            error_log("User deactivated: {$targetUser['email']} by {$user['email']}");
            
            Response::send([
                'message' => 'User deactivated successfully',
                'user_id' => $userId,
                'email' => $targetUser['email']
            ]);
            
        } catch (Exception $e) {
            error_log("Deactivate user error: " . $e->getMessage());
            Response::error('Failed to deactivate user', 500);
        }
    }

    public function show($userId) {
        // HOD, STAFF, FACULTY can view users in their department
        $user = AuthMiddleware::requireRole(['HOD', 'STAFF', 'FACULTY']);
        
        try {
            $stmt = $this->db->prepare('
                SELECT u.user_id, u.user_type, u.full_name, u.email, u.is_active,
                       u.department_id, d.department_name, d.department_code
                FROM user_accounts u 
                LEFT JOIN departments d ON u.department_id = d.department_id
                WHERE u.user_id = ? AND u.department_id = ?
            ');
            $stmt->execute([$userId, $user['department_id']]);
            $targetUser = $stmt->fetch();
            
            if (!$targetUser) {
                Response::error('User not found in your department', 404);
            }
            
            Response::send($targetUser);
            
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            Response::error('Failed to retrieve user', 500);
        }
    }

    public function findByCredentials() {
        // Any authenticated user can search for user_id using roll number or email
        $user = AuthMiddleware::requireAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('Invalid JSON input', 400);
        }
        
        // Validate that at least one search parameter is provided
        if (!isset($input['roll_number']) && !isset($input['email'])) {
            Response::error('Either roll_number or email must be provided', 400);
        }
        
        try {
            $sql = '';
            $params = [];
            
            if (isset($input['roll_number']) && isset($input['email'])) {
                // Search by both roll number and email
                $sql = '
                    SELECT u.user_id, u.user_type, u.full_name, u.email, 
                           sp.roll_number, d.department_code, d.department_name
                    FROM user_accounts u 
                    LEFT JOIN student_profiles sp ON u.user_id = sp.user_id 
                    LEFT JOIN departments d ON u.department_id = d.department_id
                    WHERE sp.roll_number = ? AND u.email = ?
                ';
                $params = [$input['roll_number'], $input['email']];
                
            } elseif (isset($input['roll_number'])) {
                // Search by roll number only
                $sql = '
                    SELECT u.user_id, u.user_type, u.full_name, u.email, 
                           sp.roll_number, d.department_code, d.department_name
                    FROM user_accounts u 
                    INNER JOIN student_profiles sp ON u.user_id = sp.user_id 
                    LEFT JOIN departments d ON u.department_id = d.department_id
                    WHERE sp.roll_number = ?
                ';
                $params = [$input['roll_number']];
                
            } elseif (isset($input['email'])) {
                // Search by email only
                $sql = '
                    SELECT u.user_id, u.user_type, u.full_name, u.email, 
                           sp.roll_number, d.department_code, d.department_name
                    FROM user_accounts u 
                    LEFT JOIN student_profiles sp ON u.user_id = sp.user_id 
                    LEFT JOIN departments d ON u.department_id = d.department_id
                    WHERE u.email = ?
                ';
                $params = [$input['email']];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            if (!$result) {
                Response::error('User not found with provided credentials', 404);
            }
            
            // Remove null roll_number for non-students
            if ($result['roll_number'] === null) {
                unset($result['roll_number']);
            }
            
            Response::send($result);
            
        } catch (Exception $e) {
            error_log("Find user by credentials error: " . $e->getMessage());
            Response::error('Failed to find user', 500);
        }
    }

    public function bulkActivate() {
        // Only HOD can bulk activate users in their department
        $user = AuthMiddleware::requireRole(['HOD']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['user_ids']) || !is_array($input['user_ids'])) {
            Response::error('user_ids array is required', 400);
        }
        
        if (empty($input['user_ids'])) {
            Response::error('At least one user_id is required', 400);
        }
        
        try {
            $this->db->beginTransaction();
            
            $successCount = 0;
            $errors = [];
            
            foreach ($input['user_ids'] as $userId) {
                // Check if user exists and belongs to HOD's department
                $stmt = $this->db->prepare('
                    SELECT user_id, email, is_active 
                    FROM user_accounts 
                    WHERE user_id = ? AND department_id = ?
                ');
                $stmt->execute([$userId, $user['department_id']]);
                $targetUser = $stmt->fetch();
                
                if (!$targetUser) {
                    $errors[] = "User ID $userId not found in your department";
                    continue;
                }
                
                if ($targetUser['is_active']) {
                    $errors[] = "User ID $userId is already active";
                    continue;
                }
                
                // Activate user
                $stmt = $this->db->prepare('UPDATE user_accounts SET is_active = 1 WHERE user_id = ?');
                $stmt->execute([$userId]);
                $successCount++;
                
                error_log("User activated: {$targetUser['email']} by {$user['email']}");
            }
            
            $this->db->commit();
            
            $response = [
                'message' => "Successfully activated $successCount users",
                'activated_count' => $successCount,
                'total_requested' => count($input['user_ids'])
            ];
            
            if (!empty($errors)) {
                $response['errors'] = $errors;
            }
            
            Response::send($response);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Bulk activate users error: " . $e->getMessage());
            Response::error('Failed to activate users', 500);
        }
    }
    
    public function bulkDeactivate() {
        // Only HOD can bulk deactivate users in their department
        $user = AuthMiddleware::requireRole(['HOD']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['user_ids']) || !is_array($input['user_ids'])) {
            Response::error('user_ids array is required', 400);
        }
        
        if (empty($input['user_ids'])) {
            Response::error('At least one user_id is required', 400);
        }
        
        try {
            $this->db->beginTransaction();
            
            $successCount = 0;
            $errors = [];
            
            foreach ($input['user_ids'] as $userId) {
                // Check if user exists and belongs to HOD's department
                $stmt = $this->db->prepare('
                    SELECT user_id, email, is_active 
                    FROM user_accounts 
                    WHERE user_id = ? AND department_id = ?
                ');
                $stmt->execute([$userId, $user['department_id']]);
                $targetUser = $stmt->fetch();
                
                if (!$targetUser) {
                    $errors[] = "User ID $userId not found in your department";
                    continue;
                }
                
                // Prevent HOD from deactivating themselves
                if ($targetUser['user_id'] == $user['user_id']) {
                    $errors[] = "Cannot deactivate yourself (User ID $userId)";
                    continue;
                }
                
                if (!$targetUser['is_active']) {
                    $errors[] = "User ID $userId is already inactive";
                    continue;
                }
                
                // Deactivate user
                $stmt = $this->db->prepare('UPDATE user_accounts SET is_active = 0 WHERE user_id = ?');
                $stmt->execute([$userId]);
                $successCount++;
                
                error_log("User deactivated: {$targetUser['email']} by {$user['email']}");
            }
            
            $this->db->commit();
            
            $response = [
                'message' => "Successfully deactivated $successCount users",
                'deactivated_count' => $successCount,
                'total_requested' => count($input['user_ids'])
            ];
            
            if (!empty($errors)) {
                $response['errors'] = $errors;
            }
            
            Response::send($response);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Bulk deactivate users error: " . $e->getMessage());
            Response::error('Failed to deactivate users', 500);
        }
    }
}