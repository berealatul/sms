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
                // Log failed login attempt
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
            
            // Log successful login
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
}