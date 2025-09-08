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
        // HOD can view users in their department
        $user = AuthMiddleware::requireRole(['HOD']);
        
        try {
            $stmt = $this->db->prepare('
                SELECT user_id, user_type, full_name, email, is_active 
                FROM user_accounts 
                WHERE department_id = ? AND user_type IN ("FACULTY", "STAFF", "STUDENT")
                ORDER BY user_type, full_name
            ');
            $stmt->execute([$user['department_id']]);
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
        // HOD can update users in their department
        $user = AuthMiddleware::requireRole(['HOD']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('Invalid JSON input', 400);
        }
        
        try {
            // Check if user belongs to HOD's department and is not admin/hod
            $stmt = $this->db->prepare('
                SELECT user_type, department_id FROM user_accounts 
                WHERE user_id = ? AND department_id = ? AND user_type IN ("FACULTY", "STAFF", "STUDENT")
            ');
            $stmt->execute([$userId, $user['department_id']]);
            $targetUser = $stmt->fetch();
            
            if (!$targetUser) {
                Response::error('User not found or not authorized to modify', 404);
            }
            
            $updateData = [];
            $allowedFields = ['user_type', 'full_name', 'email'];
            
            foreach ($input as $key => $value) {
                if (!in_array($key, $allowedFields)) {
                    Response::error("Field '$key' is not allowed for update", 400);
                }
                
                if ($key === 'user_type' && !in_array($value, ['FACULTY', 'STAFF', 'STUDENT'])) {
                    Response::error('Invalid user type', 400);
                }
                
                if ($key === 'email' && !Security::validateEmail($value)) {
                    Response::error('Invalid email format', 400);
                }
                
                $updateData[$key] = $value;
            }
            
            if (empty($updateData)) {
                Response::error('No fields provided for update', 400);
            }
            
            $this->db->beginTransaction();
            
            // Check email uniqueness if provided
            if (isset($updateData['email'])) {
                $stmt = $this->db->prepare('SELECT user_id FROM user_accounts WHERE email = ? AND user_id != ?');
                $stmt->execute([$updateData['email'], $userId]);
                if ($stmt->fetch()) {
                    Response::error('Email already exists', 409);
                }
            }
            
            // Update user account
            $setClause = [];
            $values = [];
            
            foreach ($updateData as $field => $value) {
                $setClause[] = "$field = ?";
                $values[] = $value;
            }
            
            $values[] = $userId;
            
            $sql = "UPDATE user_accounts SET " . implode(', ', $setClause) . " WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            
            $this->db->commit();
            
            Response::send(['message' => 'User updated successfully']);
            
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
}