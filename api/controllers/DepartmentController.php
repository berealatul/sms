<?php
require_once 'config/Database.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'utils/Response.php';
require_once 'utils/Security.php';

class DepartmentController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        // Only admin can view all departments
        $user = AuthMiddleware::requireRole(['ADMIN']);
        
        try {
            $stmt = $this->db->prepare('
                SELECT d.department_id, d.department_code, d.department_name, 
                       u.full_name as hod_name, u.email as hod_email 
                FROM departments d 
                LEFT JOIN user_accounts u ON u.department_id = d.department_id AND u.user_type = "HOD"
                ORDER BY d.department_code
            ');
            $stmt->execute();
            $departments = $stmt->fetchAll();
            
            Response::send($departments);
            
        } catch (Exception $e) {
            error_log("Get departments error: " . $e->getMessage());
            Response::error('Failed to retrieve departments', 500);
        }
    }
    
    public function store() {
        // Only admin can create departments
        $user = AuthMiddleware::requireRole(['ADMIN']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('Invalid JSON input', 400);
        }
        
        $input = Security::validateInput($input, ['department_code', 'department_name', 'hod_email']);
        
        if (!Security::validateEmail($input['hod_email'])) {
            Response::error('Invalid email format', 400);
        }
        
        try {
            $this->db->beginTransaction();
            
            // Check if department code already exists
            $stmt = $this->db->prepare('SELECT department_id FROM departments WHERE department_code = ?');
            $stmt->execute([$input['department_code']]);
            if ($stmt->fetch()) {
                Response::error('Department code already exists', 409);
            }
            
            // Create department
            $stmt = $this->db->prepare('INSERT INTO departments (department_code, department_name) VALUES (?, ?)');
            $stmt->execute([$input['department_code'], $input['department_name']]);
            $departmentId = $this->db->lastInsertId();
            
            // Check if user already exists
            $stmt = $this->db->prepare('SELECT user_id, user_type, department_id FROM user_accounts WHERE email = ?');
            $stmt->execute([$input['hod_email']]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser) {
                // Update existing user to HOD of this department
                $stmt = $this->db->prepare('UPDATE user_accounts SET user_type = ?, department_id = ? WHERE user_id = ?');
                $stmt->execute(['HOD', $departmentId, $existingUser['user_id']]);
                
                // If user was a student, update student_profiles department_id
                if ($existingUser['user_type'] === 'STUDENT') {
                    $stmt = $this->db->prepare('UPDATE student_profiles SET department_id = ? WHERE user_id = ?');
                    $stmt->execute([$departmentId, $existingUser['user_id']]);
                }
                
                // If user was faculty, update faculty_profiles department_id
                if (in_array($existingUser['user_type'], ['FACULTY', 'HOD'])) {
                    $stmt = $this->db->prepare('UPDATE faculty_profiles SET department_id = ? WHERE user_id = ?');
                    $stmt->execute([$departmentId, $existingUser['user_id']]);
                }
                
                error_log("Existing user {$input['hod_email']} assigned as HOD to department {$input['department_code']}");
            } else {
                // Create new user as HOD
                $defaultPassword = password_hash($input['hod_email'], PASSWORD_DEFAULT);
                $stmt = $this->db->prepare('
                    INSERT INTO user_accounts (user_type, department_id, full_name, email, password_hash) 
                    VALUES (?, ?, ?, ?, ?)
                ');
                $stmt->execute(['HOD', $departmentId, $input['hod_email'], $input['hod_email'], $defaultPassword]);
                $newUserId = $this->db->lastInsertId();
                
                // Create faculty profile for new HOD
                $stmt = $this->db->prepare('INSERT INTO faculty_profiles (user_id, department_id) VALUES (?, ?)');
                $stmt->execute([$newUserId, $departmentId]);
                
                error_log("New user created and assigned as HOD: {$input['hod_email']}");
            }
            
            $this->db->commit();
            
            Response::send([
                'message' => 'Department created successfully',
                'department_id' => $departmentId,
                'department_code' => $input['department_code'],
                'department_name' => $input['department_name'],
                'hod_email' => $input['hod_email']
            ], 201);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Create department error: " . $e->getMessage());
            Response::error('Failed to create department', 500);
        }
    }
    
    public function show($departmentId) {
        // Only admin can view specific department details
        $user = AuthMiddleware::requireRole(['ADMIN']);
        
        try {
            $stmt = $this->db->prepare('
                SELECT d.department_id, d.department_code, d.department_name, 
                       u.full_name as hod_name, u.email as hod_email 
                FROM departments d 
                LEFT JOIN user_accounts u ON u.department_id = d.department_id AND u.user_type = "HOD"
                WHERE d.department_id = ?
            ');
            $stmt->execute([$departmentId]);
            $department = $stmt->fetch();
            
            if (!$department) {
                Response::error('Department not found', 404);
            }
            
            Response::send($department);
            
        } catch (Exception $e) {
            error_log("Get department error: " . $e->getMessage());
            Response::error('Failed to retrieve department', 500);
        }
    }
    
    public function update($departmentId) {
        // Only admin can update departments
        $user = AuthMiddleware::requireRole(['ADMIN']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('Invalid JSON input', 400);
        }
        
        // Validate only provided fields
        $allowedFields = ['department_code', 'department_name', 'hod_email'];
        $updateData = [];
        
        foreach ($input as $key => $value) {
            if (!in_array($key, $allowedFields)) {
                Response::error("Field '$key' is not allowed for update", 400);
            }
        }
        
        if (isset($input['hod_email']) && !Security::validateEmail($input['hod_email'])) {
            Response::error('Invalid email format', 400);
        }
        
        if (empty($input)) {
            Response::error('No fields provided for update', 400);
        }
        
        try {
            $this->db->beginTransaction();
            
            // Check if department exists
            $stmt = $this->db->prepare('SELECT department_code, department_name FROM departments WHERE department_id = ?');
            $stmt->execute([$departmentId]);
            $department = $stmt->fetch();
            
            if (!$department) {
                Response::error('Department not found', 404);
            }
            
            // Check if new department code already exists (if provided and different)
            if (isset($input['department_code']) && $input['department_code'] !== $department['department_code']) {
                $stmt = $this->db->prepare('SELECT department_id FROM departments WHERE department_code = ? AND department_id != ?');
                $stmt->execute([$input['department_code'], $departmentId]);
                if ($stmt->fetch()) {
                    Response::error('Department code already exists', 409);
                }
            }
            
            // Update department fields if provided
            $departmentUpdates = [];
            $departmentValues = [];
            
            if (isset($input['department_code'])) {
                $departmentUpdates[] = 'department_code = ?';
                $departmentValues[] = $input['department_code'];
            }
            
            if (isset($input['department_name'])) {
                $departmentUpdates[] = 'department_name = ?';
                $departmentValues[] = $input['department_name'];
            }
            
            if (!empty($departmentUpdates)) {
                $departmentValues[] = $departmentId;
                $sql = "UPDATE departments SET " . implode(', ', $departmentUpdates) . " WHERE department_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($departmentValues);
            }
            
            // Update HOD if provided
            if (isset($input['hod_email'])) {
                // Get current HOD info before removing
                $stmt = $this->db->prepare('SELECT user_id FROM user_accounts WHERE department_id = ? AND user_type = "HOD"');
                $stmt->execute([$departmentId]);
                $currentHod = $stmt->fetch();
                
                // Remove current HOD
                $stmt = $this->db->prepare('UPDATE user_accounts SET user_type = "FACULTY" WHERE department_id = ? AND user_type = "HOD"');
                $stmt->execute([$departmentId]);
                
                // Check if new HOD email exists
                $stmt = $this->db->prepare('SELECT user_id, user_type, department_id FROM user_accounts WHERE email = ?');
                $stmt->execute([$input['hod_email']]);
                $existingUser = $stmt->fetch();
                
                if ($existingUser) {
                    // Update existing user to HOD of this department
                    $stmt = $this->db->prepare('UPDATE user_accounts SET user_type = ?, department_id = ? WHERE user_id = ?');
                    $stmt->execute(['HOD', $departmentId, $existingUser['user_id']]);
                    
                    // Update profile tables if needed
                    if ($existingUser['user_type'] === 'STUDENT') {
                        $stmt = $this->db->prepare('UPDATE student_profiles SET department_id = ? WHERE user_id = ?');
                        $stmt->execute([$departmentId, $existingUser['user_id']]);
                    }
                    
                    if (in_array($existingUser['user_type'], ['FACULTY', 'HOD'])) {
                        $stmt = $this->db->prepare('UPDATE faculty_profiles SET department_id = ? WHERE user_id = ?');
                        $stmt->execute([$departmentId, $existingUser['user_id']]);
                    }
                } else {
                    // Create new user as HOD
                    $defaultPassword = password_hash($input['hod_email'], PASSWORD_DEFAULT);
                    $stmt = $this->db->prepare('
                        INSERT INTO user_accounts (user_type, department_id, full_name, email, password_hash) 
                        VALUES (?, ?, ?, ?, ?)
                    ');
                    $stmt->execute(['HOD', $departmentId, $input['hod_email'], $input['hod_email'], $defaultPassword]);
                    $newUserId = $this->db->lastInsertId();
                    
                    // Create faculty profile for new HOD
                    $stmt = $this->db->prepare('INSERT INTO faculty_profiles (user_id, department_id) VALUES (?, ?)');
                    $stmt->execute([$newUserId, $departmentId]);
                }
            }
            
            $this->db->commit();
            
            Response::send(['message' => 'Department updated successfully']);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Update department error: " . $e->getMessage());
            Response::error('Failed to update department', 500);
        }
    }
    
    public function destroy($departmentId) {
        // Only admin can delete departments
        $user = AuthMiddleware::requireRole(['ADMIN']);
        
        try {
            $this->db->beginTransaction();
            
            // Check if department exists
            $stmt = $this->db->prepare('SELECT department_code FROM departments WHERE department_id = ?');
            $stmt->execute([$departmentId]);
            $department = $stmt->fetch();
            
            if (!$department) {
                Response::error('Department not found', 404);
            }
            
            // Check if department has users (this will prevent deletion due to foreign key constraint)
            $stmt = $this->db->prepare('SELECT COUNT(*) as user_count FROM user_accounts WHERE department_id = ?');
            $stmt->execute([$departmentId]);
            $result = $stmt->fetch();
            
            if ($result['user_count'] > 0) {
                Response::error('Cannot delete department with existing users. Please reassign or remove users first.', 409);
            }
            
            // Also check student_profiles and faculty_profiles for department references
            $stmt = $this->db->prepare('SELECT COUNT(*) as student_count FROM student_profiles WHERE department_id = ?');
            $stmt->execute([$departmentId]);
            $studentResult = $stmt->fetch();
            
            $stmt = $this->db->prepare('SELECT COUNT(*) as faculty_count FROM faculty_profiles WHERE department_id = ?');
            $stmt->execute([$departmentId]);
            $facultyResult = $stmt->fetch();
            
            if ($studentResult['student_count'] > 0 || $facultyResult['faculty_count'] > 0) {
                Response::error('Cannot delete department with existing student or faculty profiles.', 409);
            }
            
            // Delete department
            $stmt = $this->db->prepare('DELETE FROM departments WHERE department_id = ?');
            $stmt->execute([$departmentId]);
            
            $this->db->commit();
            
            Response::send(['message' => 'Department deleted successfully']);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Delete department error: " . $e->getMessage());
            Response::error('Failed to delete department', 500);
        }
    }
}