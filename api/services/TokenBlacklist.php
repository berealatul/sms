<?php
class TokenBlacklist {
    private static $blacklistFile;
    
    public static function init() {
        self::$blacklistFile = sys_get_temp_dir() . '/jwt_blacklist.json';
    }
    
    public static function addToken($token) {
        self::init();
        
        $blacklist = self::getBlacklist();
        $tokenHash = hash('sha256', $token);
        $blacklist[$tokenHash] = time();
        
        // Clean up expired tokens (older than 24 hours)
        $blacklist = array_filter($blacklist, function($timestamp) {
            return time() - $timestamp < 86400;
        });
        
        $result = file_put_contents(self::$blacklistFile, json_encode($blacklist));
        
        // Debug logging
        error_log("Token blacklisted: " . substr($tokenHash, 0, 10) . "... Result: " . ($result !== false ? 'Success' : 'Failed'));
    }
    
    public static function isBlacklisted($token) {
        self::init();
        
        $blacklist = self::getBlacklist();
        $tokenHash = hash('sha256', $token);
        
        $isBlacklisted = isset($blacklist[$tokenHash]);
        
        // Debug logging
        error_log("Checking token: " . substr($tokenHash, 0, 10) . "... Blacklisted: " . ($isBlacklisted ? 'Yes' : 'No'));
        
        return $isBlacklisted;
    }
    
    private static function getBlacklist() {
        if (!file_exists(self::$blacklistFile)) {
            return [];
        }
        
        $content = file_get_contents(self::$blacklistFile);
        return json_decode($content, true) ?: [];
    }
}