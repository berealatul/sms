<?php
require_once 'config/Database.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/DepartmentController.php';
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
$departmentController = new DepartmentController();

// Extract department ID from path if present
$pathParts = explode('/', trim($path, '/'));
$departmentId = null;
if (count($pathParts) >= 3 && $pathParts[0] === 'api' && $pathParts[1] === 'departments' && is_numeric($pathParts[2])) {
    $departmentId = (int)$pathParts[2];
}

switch (true) {
    // Auth routes
    case $path === '/api/auth/login' && $method === 'POST':
        $authController->login();
        break;
        
    case $path === '/api/auth/me' && $method === 'GET':
        $authController->me();
        break;
        
    case $path === '/api/auth/me' && $method === 'PUT':
        $authController->updateProfile();
        break;
        
    // Department routes
    case $path === '/api/departments' && $method === 'GET':
        $departmentController->index();
        break;
        
    case $path === '/api/departments' && $method === 'POST':
        $departmentController->store();
        break;
        
    case $departmentId && $method === 'GET':
        $departmentController->show($departmentId);
        break;
        
    case $departmentId && $method === 'PUT':
        $departmentController->update($departmentId);
        break;
        
    case $departmentId && $method === 'DELETE':
        $departmentController->destroy($departmentId);
        break;
        
    default:
        Response::send(['error' => 'Endpoint not found'], 404);
}