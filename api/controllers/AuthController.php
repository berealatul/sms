<?php
require_once 'config/Database.php';
require_once 'services/JWTService.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'utils/Response.php';
require_once 'utils/Security.php';

class AuthController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('Invalid JSON input', 400);
        }
        
        $input = Security::validateInput($input, ['email', 'password']);
        
        if (!Security::validateEmail($input['email'])) {
            Response::error('Invalid email format', 400);
        }
        
        try {
            $stmt = $this->db->prepare('SELECT user_id, user_type, department_id, full_name, email, password_hash, is_active FROM user_accounts WHERE email = ?');
            $stmt->execute([$input['email']]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($input['password'], $user['password_hash'])) {
                error_log("Failed login attempt for email: " . $input['email']);
                Response::error('Invalid credentials', 401);
            }
            
            if (!$user['is_active']) {
                Response::error('Account is inactive', 401);
            }
            
            $payload = [
                'uid' => $user['user_id'],
                'email' => $user['email'],
                'user_type' => $user['user_type']
            ];
            
            $token = JWTService::encode($payload);
            
            error_log("Successful login for user: " . $user['email']);
            
            Response::send([
                'token' => $token
            ]);
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            Response::error('Login failed', 500);
        }
    }
    
    public function me() {
        $user = AuthMiddleware::authenticate();
        
        Response::send([
            'user_type' => $user['user_type'],
            'department_id' => $user['department_id'],
            'full_name' => $user['full_name'],
            'email' => $user['email']
        ]);
    }
    
    public function updateProfile() {
        $user = AuthMiddleware::authenticate();
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('Invalid JSON input', 400);
        }
        
        $allowedFields = ['full_name', 'email', 'current_password', 'new_password'];
        $updateData = [];
        
        // Validate input fields
        foreach ($input as $key => $value) {
            if (!in_array($key, $allowedFields)) {
                Response::error("Field '$key' is not allowed for update. Allowed fields: " . implode(', ', $allowedFields), 400);
            }
        }
        
        // If password change is requested, verify current password
        if (isset($input['new_password'])) {
            if (!isset($input['current_password'])) {
                Response::error('Current password is required to change password', 400);
            }
            
            $stmt = $this->db->prepare('SELECT password_hash FROM user_accounts WHERE user_id = ?');
            $stmt->execute([$user['user_id']]);
            $currentUser = $stmt->fetch();
            
            if (!password_verify($input['current_password'], $currentUser['password_hash'])) {
                Response::error('Current password is incorrect', 401);
            }
            
            if (strlen($input['new_password']) < 6) {
                Response::error('New password must be at least 6 characters', 400);
            }
            
            $updateData['password_hash'] = password_hash($input['new_password'], PASSWORD_DEFAULT);
        }
        
        // Validate and prepare full name update
        if (isset($input['full_name'])) {
            $fullName = Security::validateInput(['full_name' => $input['full_name']], ['full_name']);
            if (strlen($fullName['full_name']) < 2) {
                Response::error('Full name must be at least 2 characters', 400);
            }
            $updateData['full_name'] = $fullName['full_name'];
        }
        
        // Validate and prepare email update
        if (isset($input['email'])) {
            $emailInput = Security::validateInput(['email' => $input['email']], ['email']);
            
            if (!Security::validateEmail($emailInput['email'])) {
                Response::error('Invalid email format', 400);
            }
            
            if ($emailInput['email'] !== $user['email']) {
                // Check if email already exists
                $stmt = $this->db->prepare('SELECT user_id FROM user_accounts WHERE email = ? AND user_id != ?');
                $stmt->execute([$emailInput['email'], $user['user_id']]);
                
                if ($stmt->fetch()) {
                    Response::error('Email already exists', 409);
                }
                
                $updateData['email'] = $emailInput['email'];
            }
        }
        
        // If no fields to update
        if (empty($updateData)) {
            Response::error('No valid fields to update', 400);
        }
        
        try {
            // Build dynamic update query
            $setClause = [];
            $values = [];
            
            foreach ($updateData as $field => $value) {
                $setClause[] = "$field = ?";
                $values[] = $value;
            }
            
            $values[] = $user['user_id'];
            
            $sql = "UPDATE user_accounts SET " . implode(', ', $setClause) . " WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            
            Response::send(['message' => 'Profile updated successfully']);
            
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            Response::error('Profile update failed', 500);
        }
    }
}