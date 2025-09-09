<?php
require_once 'config/Database.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'utils/Response.php';
require_once 'utils/Security.php';

class BatchController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        // Any authenticated user from department can view batches
        $user = AuthMiddleware::requireAuth();
        
        // Admin has no department, so deny access
        if ($user['user_type'] === 'ADMIN') {
            Response::error('Admin users cannot access department batches', 403);
        }
        
        try {
            $stmt = $this->db->prepare('
                SELECT b.batch_id, b.batch_name, b.start_year, b.start_semester, b.is_active,
                       p.programme_name, p.programme_id,
                       d.department_code, d.department_name
                FROM batches b 
                LEFT JOIN programmes p ON b.programme_id = p.programme_id
                LEFT JOIN departments d ON b.department_id = d.department_id
                WHERE b.department_id = ?
                ORDER BY b.start_year DESC, b.batch_name
            ');
            $stmt->execute([$user['department_id']]);
            $batches = $stmt->fetchAll();
            
            Response::send($batches);
            
        } catch (Exception $e) {
            error_log("Get batches error: " . $e->getMessage());
            Response::error('Failed to retrieve batches', 500);
        }
    }
    
    public function show($batchId) {
        // Any authenticated user from department can view specific batch
        $user = AuthMiddleware::requireAuth();
        
        // Admin has no department, so deny access
        if ($user['user_type'] === 'ADMIN') {
            Response::error('Admin users cannot access department batches', 403);
        }
        
        try {
            $stmt = $this->db->prepare('
                SELECT b.batch_id, b.batch_name, b.start_year, b.start_semester, b.is_active,
                       p.programme_name, p.programme_id,
                       d.department_code, d.department_name
                FROM batches b 
                LEFT JOIN programmes p ON b.programme_id = p.programme_id
                LEFT JOIN departments d ON b.department_id = d.department_id
                WHERE b.batch_id = ? AND b.department_id = ?
            ');
            $stmt->execute([$batchId, $user['department_id']]);
            $batch = $stmt->fetch();
            
            if (!$batch) {
                Response::error('Batch not found in your department', 404);
            }
            
            Response::send($batch);
            
        } catch (Exception $e) {
            error_log("Get batch error: " . $e->getMessage());
            Response::error('Failed to retrieve batch', 500);
        }
    }

    public function store() {
        // HOD and STAFF can create batches in their department
        $user = AuthMiddleware::requireRole(['HOD', 'STAFF']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('Invalid JSON input', 400);
        }
        
        $input = Security::validateInput($input, ['programme_id', 'batch_name', 'start_year', 'start_semester']);
        
        if (!is_numeric($input['programme_id']) || !is_numeric($input['start_year'])) {
            Response::error('Invalid numeric values', 400);
        }
        
        if (!in_array($input['start_semester'], ['SPRING', 'AUTUMN'])) {
            Response::error('Invalid semester. Must be SPRING or AUTUMN', 400);
        }
        
        try {
            // Check if programme belongs to HOD's department
            $stmt = $this->db->prepare('SELECT programme_name FROM programmes WHERE programme_id = ? AND department_id = ?');
            $stmt->execute([$input['programme_id'], $user['department_id']]);
            $programme = $stmt->fetch();
            
            if (!$programme) {
                Response::error('Programme not found in your department', 404);
            }
            
            // Check if batch name already exists for this programme
            $stmt = $this->db->prepare('SELECT batch_id FROM batches WHERE programme_id = ? AND batch_name = ?');
            $stmt->execute([$input['programme_id'], $input['batch_name']]);
            if ($stmt->fetch()) {
                Response::error('Batch name already exists for this programme', 409);
            }
            
            $stmt = $this->db->prepare('
                INSERT INTO batches (programme_id, department_id, batch_name, start_year, start_semester) 
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $input['programme_id'], 
                $user['department_id'], 
                $input['batch_name'], 
                $input['start_year'], 
                $input['start_semester']
            ]);
            
            $batchId = $this->db->lastInsertId();
            
            Response::send([
                'message' => 'Batch created successfully',
                'batch_id' => $batchId,
                'batch_name' => $input['batch_name']
            ], 201);
            
        } catch (Exception $e) {
            error_log("Create batch error: " . $e->getMessage());
            Response::error('Failed to create batch', 500);
        }
    }
    
    public function update($batchId) {
        // HOD and STAFF can update batches in their department
        $user = AuthMiddleware::requireRole(['HOD', 'STAFF']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('Invalid JSON input', 400);
        }
        
        try {
            // Check if batch belongs to HOD's department
            $stmt = $this->db->prepare('SELECT batch_name FROM batches WHERE batch_id = ? AND department_id = ?');
            $stmt->execute([$batchId, $user['department_id']]);
            $batch = $stmt->fetch();
            
            if (!$batch) {
                Response::error('Batch not found', 404);
            }
            
            $allowedFields = ['batch_name', 'start_year', 'start_semester', 'is_active'];
            $updateData = [];
            
            foreach ($input as $key => $value) {
                if (!in_array($key, $allowedFields)) {
                    Response::error("Field '$key' is not allowed for update", 400);
                }
                
                if ($key === 'start_semester' && !in_array($value, ['SPRING', 'AUTUMN'])) {
                    Response::error('Invalid semester. Must be SPRING or AUTUMN', 400);
                }
                
                $updateData[$key] = $value;
            }
            
            if (empty($updateData)) {
                Response::error('No fields provided for update', 400);
            }
            
            $this->db->beginTransaction();
            
            // If deactivating batch, deactivate related students
            if (isset($updateData['is_active']) && $updateData['is_active'] == false) {
                $stmt = $this->db->prepare('
                    UPDATE user_accounts ua 
                    JOIN student_profiles sp ON ua.user_id = sp.user_id 
                    SET ua.is_active = FALSE 
                    WHERE sp.batch_id = ?
                ');
                $stmt->execute([$batchId]);
            }
            
            // Update batch
            $setClause = [];
            $values = [];
            
            foreach ($updateData as $field => $value) {
                $setClause[] = "$field = ?";
                $values[] = $value;
            }
            
            $values[] = $batchId;
            
            $sql = "UPDATE batches SET " . implode(', ', $setClause) . " WHERE batch_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            
            $this->db->commit();
            
            Response::send(['message' => 'Batch updated successfully']);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Update batch error: " . $e->getMessage());
            Response::error('Failed to update batch', 500);
        }
    }
    
    public function destroy($batchId) {
        // Only HOD can delete batches in their department
        $user = AuthMiddleware::requireRole(['HOD']);
        
        try {
            // Check if batch belongs to HOD's department
            $stmt = $this->db->prepare('SELECT batch_name FROM batches WHERE batch_id = ? AND department_id = ?');
            $stmt->execute([$batchId, $user['department_id']]);
            $batch = $stmt->fetch();
            
            if (!$batch) {
                Response::error('Batch not found', 404);
            }
            
            // Check if batch has associated students
            $stmt = $this->db->prepare('SELECT COUNT(*) as student_count FROM student_profiles WHERE batch_id = ?');
            $stmt->execute([$batchId]);
            $result = $stmt->fetch();
            
            if ($result['student_count'] > 0) {
                Response::error('Cannot delete batch with associated students', 409);
            }
            
            $stmt = $this->db->prepare('DELETE FROM batches WHERE batch_id = ?');
            $stmt->execute([$batchId]);
            
            Response::send(['message' => 'Batch deleted successfully']);
            
        } catch (Exception $e) {
            error_log("Delete batch error: " . $e->getMessage());
            Response::error('Failed to delete batch', 500);
        }
    }
}