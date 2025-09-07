<?php
require_once 'config/Database.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'controllers/AuthController.php';
require_once 'utils/Response.php';
require_once 'utils/Security.php';

// Security headers
Security::setSecurityHeaders();

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    Response::send(['message' => 'OK'], 200);
}

// Rate limiting (simple implementation)
Security::rateLimiting();

// Parse request
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Route the request
$authController = new AuthController();

switch ($path) {
    case '/api/auth/login':
        if ($method === 'POST') {
            $authController->login();
        } else {
            Response::send(['error' => 'Method not allowed'], 405);
        }
        break;
        
    case '/api/auth/me':
        if ($method === 'GET') {
            $authController->me();
        } else {
            Response::send(['error' => 'Method not allowed'], 405);
        }
        break;
        
    default:
        Response::send(['error' => 'Endpoint not found'], 404);
}