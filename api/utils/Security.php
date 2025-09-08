<?php
class Security {
    public static function setSecurityHeaders() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: http://localhost:3000'); // Specific origin for React dev server
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    public static function rateLimiting() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $current_time = time();
        $time_window = 60; // 1 minute
        $max_requests = 60; // Max 60 requests per minute
        
        // Simple file-based rate limiting (in production, use Redis or database)
        $safe_ip = str_replace([':', '.'], '_', $ip);
        $rate_limit_file = sys_get_temp_dir() . "/rate_limit_$safe_ip.json";
        
        if (file_exists($rate_limit_file)) {
            $data = json_decode(file_get_contents($rate_limit_file), true);
            if ($current_time - $data['first_request'] < $time_window) {
                if ($data['request_count'] >= $max_requests) {
                    Response::error('Rate limit exceeded', 429);
                }
                $data['request_count']++;
            } else {
                $data = ['first_request' => $current_time, 'request_count' => 1];
            }
        } else {
            $data = ['first_request' => $current_time, 'request_count' => 1];
        }
        
        file_put_contents($rate_limit_file, json_encode($data));
    }
    
    public static function validateInput($data, $required_fields) {
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                Response::error("Field '$field' is required", 400);
            }
        }
        
        // Sanitize inputs
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            }
        }
        
        return $data;
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}