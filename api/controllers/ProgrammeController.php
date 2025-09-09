<?php
require_once 'config/Database.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'utils/Response.php';
require_once 'utils/Security.php';

class ProgrammeController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        // Any authenticated user from department can view programmes
        $user = AuthMiddleware::requireAuth();
        
        // Admin has no department, so deny access
        if ($user['user_type'] === 'ADMIN') {
            Response::error('Admin users cannot access department programmes', 403);
        }
        
        try {
            $stmt = $this->db->prepare('
                SELECT p.programme_id, p.programme_name, p.minimum_duration_years, 
                       p.maximum_duration_years, p.is_active,
                       dl.level_name as degree_level,
                       d.department_code, d.department_name
                FROM programmes p 
                LEFT JOIN degree_levels dl ON p.degree_level_id = dl.degree_level_id
                LEFT JOIN departments d ON p.department_id = d.department_id
                WHERE p.department_id = ?
                ORDER BY p.programme_name
            ');
            $stmt->execute([$user['department_id']]);
            $programmes = $stmt->fetchAll();
            
            Response::send($programmes);
            
        } catch (Exception $e) {
            error_log("Get programmes error: " . $e->getMessage());
            Response::error('Failed to retrieve programmes', 500);
        }
    }

    public function show($programmeId) {
        // Any authenticated user from department can view specific programme
        $user = AuthMiddleware::requireAuth();
        
        // Admin has no department, so deny access
        if ($user['user_type'] === 'ADMIN') {
            Response::error('Admin users cannot access department programmes', 403);
        }
        
        try {
            $stmt = $this->db->prepare('
                SELECT p.programme_id, p.programme_name, p.minimum_duration_years, 
                       p.maximum_duration_years, p.is_active,
                       dl.level_name as degree_level, dl.degree_level_id,
                       d.department_code, d.department_name
                FROM programmes p 
                LEFT JOIN degree_levels dl ON p.degree_level_id = dl.degree_level_id
                LEFT JOIN departments d ON p.department_id = d.department_id
                WHERE p.programme_id = ? AND p.department_id = ?
            ');
            $stmt->execute([$programmeId, $user['department_id']]);
            $programme = $stmt->fetch();
            
            if (!$programme) {
                Response::error('Programme not found in your department', 404);
            }
            
            Response::send($programme);
            
        } catch (Exception $e) {
            error_log("Get programme error: " . $e->getMessage());
            Response::error('Failed to retrieve programme', 500);
        }
    }
    
    public function store() {
        // HOD can create programmes in their department
        $user = AuthMiddleware::requireRole(['HOD']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('Invalid JSON input', 400);
        }
        
        $input = Security::validateInput($input, ['programme_name', 'degree_level_id', 'minimum_duration_years']);
        
        if (!is_numeric($input['degree_level_id']) || !is_numeric($input['minimum_duration_years'])) {
            Response::error('Invalid numeric values', 400);
        }
        
        try {
            // Check if programme name already exists
            $stmt = $this->db->prepare('SELECT programme_id FROM programmes WHERE programme_name = ?');
            $stmt->execute([$input['programme_name']]);
            if ($stmt->fetch()) {
                Response::error('Programme name already exists', 409);
            }
            
            // Verify degree level exists
            $stmt = $this->db->prepare('SELECT degree_level_id FROM degree_levels WHERE degree_level_id = ?');
            $stmt->execute([$input['degree_level_id']]);
            if (!$stmt->fetch()) {
                Response::error('Invalid degree level', 400);
            }
            
            $maxDuration = isset($input['maximum_duration_years']) ? $input['maximum_duration_years'] : null;
            
            $stmt = $this->db->prepare('
                INSERT INTO programmes (programme_name, degree_level_id, minimum_duration_years, maximum_duration_years, department_id) 
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $input['programme_name'], 
                $input['degree_level_id'], 
                $input['minimum_duration_years'], 
                $maxDuration, 
                $user['department_id']
            ]);
            
            $programmeId = $this->db->lastInsertId();
            
            Response::send([
                'message' => 'Programme created successfully',
                'programme_id' => $programmeId,
                'programme_name' => $input['programme_name']
            ], 201);
            
        } catch (Exception $e) {
            error_log("Create programme error: " . $e->getMessage());
            Response::error('Failed to create programme', 500);
        }
    }
    
    public function update($programmeId) {
        // HOD can update programmes in their department
        $user = AuthMiddleware::requireRole(['HOD']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('Invalid JSON input', 400);
        }
        
        try {
            // Check if programme belongs to HOD's department
            $stmt = $this->db->prepare('SELECT programme_name FROM programmes WHERE programme_id = ? AND department_id = ?');
            $stmt->execute([$programmeId, $user['department_id']]);
            $programme = $stmt->fetch();
            
            if (!$programme) {
                Response::error('Programme not found', 404);
            }
            
            $allowedFields = ['programme_name', 'degree_level_id', 'minimum_duration_years', 'maximum_duration_years', 'is_active'];
            $updateData = [];
            
            foreach ($input as $key => $value) {
                if (!in_array($key, $allowedFields)) {
                    Response::error("Field '$key' is not allowed for update", 400);
                }
                $updateData[$key] = $value;
            }
            
            if (empty($updateData)) {
                Response::error('No fields provided for update', 400);
            }
            
            $this->db->beginTransaction();
            
            // Check programme name uniqueness if provided
            if (isset($updateData['programme_name'])) {
                $stmt = $this->db->prepare('SELECT programme_id FROM programmes WHERE programme_name = ? AND programme_id != ?');
                $stmt->execute([$updateData['programme_name'], $programmeId]);
                if ($stmt->fetch()) {
                    Response::error('Programme name already exists', 409);
                }
            }
            
            // If deactivating programme, deactivate related students
            if (isset($updateData['is_active']) && $updateData['is_active'] == false) {
                $stmt = $this->db->prepare('
                    UPDATE user_accounts ua 
                    JOIN student_profiles sp ON ua.user_id = sp.user_id 
                    JOIN batches b ON sp.batch_id = b.batch_id 
                    SET ua.is_active = FALSE 
                    WHERE b.programme_id = ?
                ');
                $stmt->execute([$programmeId]);
            }
            
            // Update programme
            $setClause = [];
            $values = [];
            
            foreach ($updateData as $field => $value) {
                $setClause[] = "$field = ?";
                $values[] = $value;
            }
            
            $values[] = $programmeId;
            
            $sql = "UPDATE programmes SET " . implode(', ', $setClause) . " WHERE programme_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            
            $this->db->commit();
            
            Response::send(['message' => 'Programme updated successfully']);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Update programme error: " . $e->getMessage());
            Response::error('Failed to update programme', 500);
        }
    }
    
    public function destroy($programmeId) {
        // HOD can delete programmes if no batches are associated
        $user = AuthMiddleware::requireRole(['HOD']);
        
        try {
            // Check if programme belongs to HOD's department
            $stmt = $this->db->prepare('SELECT programme_name FROM programmes WHERE programme_id = ? AND department_id = ?');
            $stmt->execute([$programmeId, $user['department_id']]);
            $programme = $stmt->fetch();
            
            if (!$programme) {
                Response::error('Programme not found', 404);
            }
            
            // Check if programme has associated batches
            $stmt = $this->db->prepare('SELECT COUNT(*) as batch_count FROM batches WHERE programme_id = ?');
            $stmt->execute([$programmeId]);
            $result = $stmt->fetch();
            
            if ($result['batch_count'] > 0) {
                Response::error('Cannot delete programme with associated batches', 409);
            }
            
            $stmt = $this->db->prepare('DELETE FROM programmes WHERE programme_id = ?');
            $stmt->execute([$programmeId]);
            
            Response::send(['message' => 'Programme deleted successfully']);
            
        } catch (Exception $e) {
            error_log("Delete programme error: " . $e->getMessage());
            Response::error('Failed to delete programme', 500);
        }
    }
}