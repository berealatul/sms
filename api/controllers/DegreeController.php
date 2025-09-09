<?php
require_once 'config/Database.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'utils/Response.php';
require_once 'utils/Security.php';

class DegreeController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        // Any authenticated user can view degree levels
        $user = AuthMiddleware::requireAuth();
        
        try {
            $stmt = $this->db->prepare('
                SELECT degree_level_id, level_name 
                FROM degree_levels 
                ORDER BY level_name
            ');
            $stmt->execute();
            $degrees = $stmt->fetchAll();
            
            Response::send($degrees);
            
        } catch (Exception $e) {
            error_log("Get degrees error: " . $e->getMessage());
            Response::error('Failed to retrieve degrees', 500);
        }
    }
    
    public function show($degreeId) {
        // Any authenticated user can view specific degree
        $user = AuthMiddleware::requireAuth();
        
        try {
            $stmt = $this->db->prepare('
                SELECT degree_level_id, level_name 
                FROM degree_levels 
                WHERE degree_level_id = ?
            ');
            $stmt->execute([$degreeId]);
            $degree = $stmt->fetch();
            
            if (!$degree) {
                Response::error('Degree level not found', 404);
            }
            
            Response::send($degree);
            
        } catch (Exception $e) {
            error_log("Get degree error: " . $e->getMessage());
            Response::error('Failed to retrieve degree', 500);
        }
    }
    
    public function store() {
        // Only HOD can create degree levels
        $user = AuthMiddleware::requireRole(['HOD']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('Invalid JSON input', 400);
        }
        
        $input = Security::validateInput($input, ['level_name']);
        
        try {
            // Check if degree level already exists
            $stmt = $this->db->prepare('SELECT degree_level_id FROM degree_levels WHERE level_name = ?');
            $stmt->execute([$input['level_name']]);
            if ($stmt->fetch()) {
                Response::error('Degree level already exists', 409);
            }
            
            // Create degree level
            $stmt = $this->db->prepare('INSERT INTO degree_levels (level_name) VALUES (?)');
            $stmt->execute([$input['level_name']]);
            $degreeId = $this->db->lastInsertId();
            
            Response::send([
                'message' => 'Degree level created successfully',
                'degree_level_id' => $degreeId,
                'level_name' => $input['level_name']
            ], 201);
            
        } catch (Exception $e) {
            error_log("Create degree error: " . $e->getMessage());
            Response::error('Failed to create degree level', 500);
        }
    }
    
    public function update($degreeId) {
        // Only HOD can update degree levels
        $user = AuthMiddleware::requireRole(['HOD']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['level_name'])) {
            Response::error('level_name is required', 400);
        }
        
        try {
            // Check if degree exists
            $stmt = $this->db->prepare('SELECT level_name FROM degree_levels WHERE degree_level_id = ?');
            $stmt->execute([$degreeId]);
            $degree = $stmt->fetch();
            
            if (!$degree) {
                Response::error('Degree level not found', 404);
            }
            
            // Check if new name already exists
            $stmt = $this->db->prepare('SELECT degree_level_id FROM degree_levels WHERE level_name = ? AND degree_level_id != ?');
            $stmt->execute([$input['level_name'], $degreeId]);
            if ($stmt->fetch()) {
                Response::error('Degree level name already exists', 409);
            }
            
            // Update degree
            $stmt = $this->db->prepare('UPDATE degree_levels SET level_name = ? WHERE degree_level_id = ?');
            $stmt->execute([$input['level_name'], $degreeId]);
            
            Response::send(['message' => 'Degree level updated successfully']);
            
        } catch (Exception $e) {
            error_log("Update degree error: " . $e->getMessage());
            Response::error('Failed to update degree level', 500);
        }
    }
    
    public function destroy($degreeId) {
        // Only HOD can delete degree levels
        $user = AuthMiddleware::requireRole(['HOD']);
        
        try {
            // Check if degree exists
            $stmt = $this->db->prepare('SELECT level_name FROM degree_levels WHERE degree_level_id = ?');
            $stmt->execute([$degreeId]);
            $degree = $stmt->fetch();
            
            if (!$degree) {
                Response::error('Degree level not found', 404);
            }
            
            // Check if degree is used in programmes
            $stmt = $this->db->prepare('SELECT COUNT(*) as programme_count FROM programmes WHERE degree_level_id = ?');
            $stmt->execute([$degreeId]);
            $result = $stmt->fetch();
            
            if ($result['programme_count'] > 0) {
                Response::error('Cannot delete degree level with existing programmes', 409);
            }
            
            // Delete degree
            $stmt = $this->db->prepare('DELETE FROM degree_levels WHERE degree_level_id = ?');
            $stmt->execute([$degreeId]);
            
            Response::send(['message' => 'Degree level deleted successfully']);
            
        } catch (Exception $e) {
            error_log("Delete degree error: " . $e->getMessage());
            Response::error('Failed to delete degree level', 500);
        }
    }
}