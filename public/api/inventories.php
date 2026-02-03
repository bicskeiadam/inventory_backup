<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/ApiResponse.php';
require_once __DIR__ . '/../../app/models/Inventory.php';

$db = (new Database())->getConnection();
$inventoryModel = new Inventory($db);

// token auth (same pattern as items)
$headers = getallheaders();
$auth = $headers['Authorization'] ?? ($headers['authorization'] ?? null);
if (!$auth || !preg_match('/Bearer\s(\S+)/', $auth, $m)) ApiResponse::json(['message'=>'Unauthorized'], 401);
$token = $m[1];
$stmt = $db->prepare("SELECT * FROM users WHERE api_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();
if (!$user) ApiResponse::json(['message'=>'Invalid token'], 401);

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    // Get worker's assigned company
    if (isset($_GET['get_worker_company'])) {
        if ($user['role'] !== 'worker') {
            ApiResponse::json(['company' => null], 200);
        }
        
        $stmt = $db->prepare("
            SELECT c.* FROM companies c 
            JOIN company_user cu ON c.id = cu.company_id 
            WHERE cu.user_id = ? LIMIT 1
        ");
        $stmt->execute([$user['id']]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        ApiResponse::json(['company' => $company], 200);
    }
    
    // Get companies for this user (employer sees their assigned companies, admin sees all)
    if (isset($_GET['get_companies'])) {
        if ($user['role'] === 'admin') {
            // Admin sees all companies
            $stmt = $db->prepare("SELECT * FROM companies ORDER BY name");
            $stmt->execute();
        } else {
            // Employer sees only their assigned companies
            $stmt = $db->prepare("
                SELECT c.* FROM companies c
                INNER JOIN company_user cu ON c.id = cu.company_id
                WHERE cu.user_id = ?
                ORDER BY c.name
            ");
            $stmt->execute([$user['id']]);
        }
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        ApiResponse::json(['companies' => $companies], 200);
    }
    
    // Get free workers and assigned workers
    if (isset($_GET['get_workers'])) {
        if ($user['role'] !== 'employer' && $user['role'] !== 'admin') {
            ApiResponse::json(['message' => 'Unauthorized'], 403);
        }
        
        // Free workers (not assigned to any company)
        $stmt = $db->prepare("
            SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as name, u.email 
            FROM users u 
            LEFT JOIN company_user cu ON u.id = cu.user_id 
            WHERE u.role = 'worker' 
            AND u.is_active = 1 
            AND u.is_blocked = 0 
            AND cu.user_id IS NULL 
            ORDER BY u.first_name, u.last_name
        ");
        $stmt->execute();
        $freeWorkers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Assigned workers
        $stmt = $db->prepare("
            SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as name, u.email, c.name as company_name, c.id as company_id
            FROM users u 
            JOIN company_user cu ON u.id = cu.user_id 
            JOIN companies c ON cu.company_id = c.id 
            WHERE u.role = 'worker' 
            AND u.is_active = 1 
            ORDER BY c.name, u.first_name, u.last_name
        ");
        $stmt->execute();
        $assignedWorkers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        ApiResponse::json([
            'free_workers' => $freeWorkers,
            'assigned_workers' => $assignedWorkers
        ], 200);
    }
    
    // Get worker's own submissions (for "My Submissions" screen)
    if (isset($_GET['get_my_submissions'])) {
        $submissions = $inventoryModel->getSubmissionsByUser((int)$user['id']);
        ApiResponse::json(['submissions' => $submissions], 200);
    }
    
    // Get submissions for an inventory (for employer review)
    if (isset($_GET['get_submissions'])) {
        if ($user['role'] !== 'employer' && $user['role'] !== 'admin') {
            ApiResponse::json(['message' => 'Unauthorized'], 403);
        }
        $inventoryId = $_GET['inventory_id'] ?? null;
        if (!$inventoryId) ApiResponse::json(['message' => 'inventory_id required'], 422);
        $submissions = $inventoryModel->getSubmissions((int)$inventoryId);
        ApiResponse::json(['submissions' => $submissions], 200);
    }
    
    // Get all submissions for a company (for employer mobile review)
    if (isset($_GET['get_company_submissions'])) {
        if ($user['role'] !== 'employer' && $user['role'] !== 'admin') {
            ApiResponse::json(['message' => 'Unauthorized'], 403);
        }
        $companyId = $_GET['company_id'] ?? null;
        if (!$companyId) ApiResponse::json(['message' => 'company_id required'], 422);
        $submissions = $inventoryModel->getSubmissionsByCompany((int)$companyId);
        ApiResponse::json(['submissions' => $submissions], 200);
    }
    
    // Regular inventory list
    $companyId = $_GET['company_id'] ?? null;
    if (!$companyId) ApiResponse::json(['message'=>'company_id required'], 422);
    $list = $inventoryModel->getByCompany((int)$companyId);
    ApiResponse::json(['inventories'=>$list], 200);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Assign worker to company
    if (isset($input['action']) && $input['action'] === 'assign_worker') {
        if ($user['role'] !== 'employer' && $user['role'] !== 'admin') {
            ApiResponse::json(['message' => 'Unauthorized'], 403);
        }
        
        $workerId = $input['worker_id'] ?? null;
        $companyId = $input['company_id'] ?? null;
        
        if (!$workerId || !$companyId) {
            ApiResponse::json(['message' => 'worker_id and company_id required'], 422);
        }
        
        // Check if worker is already assigned
        $stmt = $db->prepare("SELECT * FROM company_user WHERE user_id = ?");
        $stmt->execute([$workerId]);
        if ($stmt->fetch()) {
            ApiResponse::json(['success' => false, 'message' => 'Worker already assigned'], 400);
        }
        
        // Assign worker
        $stmt = $db->prepare("INSERT INTO company_user (company_id, user_id) VALUES (?, ?)");
        $stmt->execute([$companyId, $workerId]);
        
        ApiResponse::json(['success' => true], 200);
    }
    
    // Remove worker assignment
    if (isset($input['action']) && $input['action'] === 'remove_worker') {
        if ($user['role'] !== 'employer' && $user['role'] !== 'admin') {
            ApiResponse::json(['message' => 'Unauthorized'], 403);
        }
        
        $workerId = $input['worker_id'] ?? null;
        
        if (!$workerId) {
            ApiResponse::json(['message' => 'worker_id required'], 422);
        }
        
        $stmt = $db->prepare("DELETE FROM company_user WHERE user_id = ?");
        $stmt->execute([$workerId]);
        
        ApiResponse::json(['success' => true], 200);
    }
    
    // Review submission (approve/reject)
    if (isset($input['action']) && $input['action'] === 'review_submission') {
        if ($user['role'] !== 'employer' && $user['role'] !== 'admin') {
            ApiResponse::json(['message' => 'Unauthorized'], 403);
        }
        
        $submissionId = $input['submission_id'] ?? null;
        $status = $input['status'] ?? null; // 'approved' or 'rejected'
        $message = $input['message'] ?? null; // optional feedback message
        
        if (!$submissionId || !$status) {
            ApiResponse::json(['message' => 'submission_id and status required'], 422);
        }
        
        if (!in_array($status, ['approved', 'rejected'])) {
            ApiResponse::json(['message' => 'status must be approved or rejected'], 422);
        }
        
        $success = $inventoryModel->setSubmissionStatus((int)$submissionId, $status);
        
        // Optionally add a response message
        if ($success && $message) {
            $inventoryModel->addSubmissionResponse((int)$submissionId, (int)$user['id'], $message);
        }
        
        ApiResponse::json(['success' => $success], 200);
    }
    
    // Regular inventory creation
    $companyId = $input['company_id'] ?? null;
    if (!$companyId) ApiResponse::json(['message'=>'company_id required'], 422);
    $name = $input['name'] ?? null;
    $startDate = $input['start_date'] ?? null;
    $id = $inventoryModel->create((int)$companyId, $name, 'scheduled', $startDate);
    ApiResponse::json(['id'=>$id], 201);
}

ApiResponse::json(['message'=>'Method not allowed'], 405);
