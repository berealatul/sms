<?php
require_once 'config/Database.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/DepartmentController.php';
require_once 'controllers/UserController.php';
require_once 'controllers/ProgrammeController.php';
require_once 'controllers/BatchController.php';
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

// Remove the base path if running in subdirectory
$basePath = '/sms/api';
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}
if (empty($path)) {
    $path = '/';
}

// Route the request
$authController = new AuthController();
$departmentController = new DepartmentController();
$userController = new UserController();
$programmeController = new ProgrammeController();
$batchController = new BatchController();

// Extract IDs from path if present
$pathParts = explode('/', trim($path, '/'));
$departmentId = null;
$userId = null;
$programmeId = null;
$batchId = null;

if (count($pathParts) >= 2) {
    if ($pathParts[0] === 'departments' && is_numeric($pathParts[1])) {
        $departmentId = (int)$pathParts[1];
    } elseif ($pathParts[0] === 'users' && is_numeric($pathParts[1])) {
        $userId = (int)$pathParts[1];
    } elseif ($pathParts[0] === 'programmes' && is_numeric($pathParts[1])) {
        $programmeId = (int)$pathParts[1];
    } elseif ($pathParts[0] === 'batches' && is_numeric($pathParts[1])) {
        $batchId = (int)$pathParts[1];
    }
}

switch (true) {
    // Auth routes
    case $path === '/auth/login' && $method === 'POST':
        $authController->login();
        break;
        
    case $path === '/auth/me' && $method === 'GET':
        $authController->me();
        break;
        
    case $path === '/auth/me' && $method === 'PUT':
        $authController->updateProfile();
        break;
        
    // Department routes (Admin only)
    case $path === '/departments' && $method === 'GET':
        $departmentController->index();
        break;
        
    case $path === '/departments' && $method === 'POST':
        $departmentController->store();
        break;
        
    case $pathParts[0] === 'departments' && $departmentId && $method === 'GET':
        $departmentController->show($departmentId);
        break;
        
    case $pathParts[0] === 'departments' && $departmentId && $method === 'PUT':
        $departmentController->update($departmentId);
        break;
        
    case $pathParts[0] === 'departments' && $departmentId && $method === 'DELETE':
        $departmentController->destroy($departmentId);
        break;
        
    // User routes (HOD only)
    case $path === '/users' && $method === 'GET':
        $userController->index();
        break;
        
    case $path === '/users' && $method === 'POST':
        $userController->store();
        break;
        
    case $path === '/users/bulk' && $method === 'POST':
        $userController->bulkStore();
        break;
        
    case $path === '/users/activate' && $method === 'PUT':
        $userController->activate();
        break;
        
    case $pathParts[0] === 'users' && $userId && $method === 'PUT':
        $userController->update($userId);
        break;
        
    case $pathParts[0] === 'users' && $userId && $method === 'DELETE':
        $userController->destroy($userId);
        break;
        
    // Programme routes (HOD only)
    case $path === '/programmes' && $method === 'GET':
        $programmeController->index();
        break;
        
    case $path === '/programmes' && $method === 'POST':
        $programmeController->store();
        break;
        
    case $pathParts[0] === 'programmes' && $programmeId && $method === 'PUT':
        $programmeController->update($programmeId);
        break;
        
    case $pathParts[0] === 'programmes' && $programmeId && $method === 'DELETE':
        $programmeController->destroy($programmeId);
        break;
        
    // Batch routes (HOD only)
    case $path === '/batches' && $method === 'GET':
        $batchController->index();
        break;
        
    case $path === '/batches' && $method === 'POST':
        $batchController->store();
        break;
        
    case $pathParts[0] === 'batches' && $batchId && $method === 'PUT':
        $batchController->update($batchId);
        break;
        
    case $pathParts[0] === 'batches' && $batchId && $method === 'DELETE':
        $batchController->destroy($batchId);
        break;
        
    default:
        Response::send(['error' => 'Endpoint not found', 'path' => $path, 'method' => $method], 404);
}