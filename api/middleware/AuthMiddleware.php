<?php
require_once 'services/JWTService.php';
require_once 'config/Database.php';
require_once 'utils/Response.php';

class AuthMiddleware {
    public static function authenticate() {
        $token = JWTService::getBearerToken();
        
        if (!$token) {
            Response::error('Authorization token required', 401);
        }
        
        try {
            $payload = JWTService::decode($token);
            
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT user_id, user_type, department_id, full_name, email, is_active FROM user_accounts WHERE user_id = ?');
            $stmt->execute([$payload['uid']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                Response::error('User not found', 401);
            }
            
            if (!$user['is_active']) {
                Response::error('Account is inactive', 401);
            }
            
            return $user;
            
        } catch (Exception $e) {
            Response::error('Invalid or expired token', 401);
        }
    }
    
    public static function requireRole($allowedRoles) {
        $user = self::authenticate();
        
        if (!in_array($user['user_type'], $allowedRoles)) {
            Response::error('Insufficient permissions', 403);
        }
        
        return $user;
    }
}