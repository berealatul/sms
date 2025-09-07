<?php
require_once 'utils/Response.php';

class JWTService {
    private static $secret_key;
    private static $algorithm = 'HS256';
    private static $token_expiry = 3600; // 1 hour
    
    public static function init() {
        // In production, store this in environment variables
        self::$secret_key = $_ENV['JWT_SECRET'] ?? 'sms_jwt_secret_key_2024_very_secure_random_string';
    }
    
    public static function encode($payload) {
        self::init();
        
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algorithm]);
        $payload['iat'] = time();
        $payload['exp'] = time() + self::$token_expiry;
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::$secret_key, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    public static function decode($jwt) {
        self::init();
        
        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
        $signatureProvided = $tokenParts[2];
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::$secret_key, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if ($base64Signature !== $signatureProvided) {
            throw new Exception('Invalid token signature');
        }
        
        $payloadArray = json_decode($payload, true);
        
        if (!isset($payloadArray['exp']) || $payloadArray['exp'] < time()) {
            throw new Exception('Token has expired');
        }
        
        return $payloadArray;
    }
    
    public static function getBearerToken() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}