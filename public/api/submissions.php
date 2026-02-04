<?php
// CRITICAL: Must be at very start before anything else
// These might not work if already past limits - check php.ini directly
@ini_set('post_max_size', '100M');
@ini_set('upload_max_filesize', '100M');
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '300');
@ini_set('max_input_vars', '10000');

// Log current limits
error_log("PHP Limits - post_max_size: " . ini_get('post_max_size') . 
          ", memory_limit: " . ini_get('memory_limit') . 
          ", max_input_vars: " . ini_get('max_input_vars'));

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/ApiResponse.php';
require_once __DIR__ . '/../../app/models/Inventory.php';
require_once __DIR__ . '/../../app/models/InventoryItem.php';
require_once __DIR__ . '/../../app/models/User.php';

$db = (new Database())->getConnection();
$inventoryModel = new Inventory($db);
$inventoryItemModel = new InventoryItem($db);
$userModel = new User($db);

// Token authentication
$headers = getallheaders();
$auth = $headers['Authorization'] ?? ($headers['authorization'] ?? null);
if (!$auth || !preg_match('/Bearer\s(\S+)/', $auth, $m)) {
    ApiResponse::json(['message' => 'Unauthorized'], 401);
    exit;
}
$token = $m[1];
$stmt = $db->prepare("SELECT * FROM users WHERE api_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();
if (!$user) {
    ApiResponse::json(['message' => 'Invalid token'], 401);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Debug: Log all request info FIRST
error_log("=== SUBMISSIONS REQUEST ===");
error_log("Method: " . $method);
error_log("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'none'));
error_log("Query string: " . ($_SERVER['QUERY_STRING'] ?? 'none'));
error_log("GET params: " . json_encode($_GET));

if ($method === 'POST') {
    // Check if this is a photo upload request - multiple ways to detect
    $isPhotoUpload = (isset($_GET['upload_photo']) && $_GET['upload_photo'] == '1') || 
                     strpos($_SERVER['REQUEST_URI'] ?? '', 'upload_photo=1') !== false;
    
    error_log("Is photo upload check: " . ($isPhotoUpload ? 'YES' : 'NO'));
    
    if ($isPhotoUpload) {
        error_log("Entering photo upload handler");
        $rawInput = file_get_contents('php://input');
        error_log("Raw input length: " . strlen($rawInput));
        $input = json_decode($rawInput, true);
        
        if ($input === null) {
            error_log("JSON decode failed: " . json_last_error_msg());
            ApiResponse::json(['message' => 'Invalid JSON', 'error' => json_last_error_msg()], 422);
        }
        
        // Debug logging
        error_log("Photo upload request received");
        error_log("Input keys: " . print_r(array_keys($input ?? []), true));
        
        $photo = $input['photo'] ?? null;
        $itemId = $input['item_id'] ?? null;
        $inventoryId = $input['inventory_id'] ?? null;
        
        if (!$photo) {
            error_log("Photo upload failed: no photo data");
            ApiResponse::json(['message' => 'photo required', 'received_keys' => array_keys($input ?? [])], 422);
            exit;
        }
        if (!$itemId) {
            error_log("Photo upload failed: no item_id");
            ApiResponse::json(['message' => 'item_id required'], 422);
            exit;
        }
        if (!$inventoryId) {
            error_log("Photo upload failed: no inventory_id");
            ApiResponse::json(['message' => 'inventory_id required'], 422);
            exit;
        }
        
        try {
            // Remove data URL prefix if present
            $base64Data = $photo;
            if (strpos($photo, 'base64,') !== false) {
                $base64Data = explode('base64,', $photo)[1];
            }
            
            // Decode base64
            $imageData = base64_decode($base64Data);
            if ($imageData === false) {
                ApiResponse::json(['message' => 'Invalid base64 image'], 422);
                exit;
            }
            
            // Generate unique filename
            $filename = 'damage_' . $inventoryId . '_' . $itemId . '_' . time() . '_' . uniqid() . '.jpg';
            $uploadDir = __DIR__ . '/../uploads/damage/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $filePath = $uploadDir . $filename;
            
            // Save image
            if (file_put_contents($filePath, $imageData) === false) {
                ApiResponse::json(['message' => 'Failed to save image'], 500);
                exit;
            }
            
            // Return the relative path
            $relativePath = 'uploads/damage/' . $filename;
            
            ApiResponse::json([
                'message' => 'Photo uploaded successfully',
                'photo_path' => $relativePath
            ], 201);
            exit;
            
        } catch (Exception $e) {
            error_log("Photo upload error: " . $e->getMessage());
            ApiResponse::json(['message' => 'Photo upload failed', 'error' => $e->getMessage()], 500);
            exit;
        }
    }
    
    // Regular submission handling
    $input = json_decode(file_get_contents('php://input'), true);
    $inventoryId = $input['inventory_id'] ?? null;
    $payload = $input['payload'] ?? null;

    if (!$inventoryId || !$payload) {
        ApiResponse::json(['message' => 'inventory_id and payload required'], 422);
    }

    // Validate inventory exists and is active
    $stmt = $db->prepare("SELECT * FROM inventories WHERE id = ?");
    $stmt->execute([(int)$inventoryId]);
    $inventory = $stmt->fetch();
    
    if (!$inventory) {
        ApiResponse::json(['message' => 'Inventory not found'], 404);
    }

    if ($inventory['status'] !== 'active' && $inventory['status'] !== 'scheduled') {
        ApiResponse::json(['message' => 'Inventory is not active'], 400);
    }

    try {
        $db->beginTransaction();

        // Store the submission in inventory_submissions table
        $payloadJson = json_encode($payload);
        $stmt = $db->prepare("INSERT INTO inventory_submissions (inventory_id, user_id, payload, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([(int)$inventoryId, $user['id'], $payloadJson]);
        $submissionId = $db->lastInsertId();

        // Process each item in the payload and add to inventory_items
        if (isset($payload['items']) && is_array($payload['items'])) {
            foreach ($payload['items'] as $item) {
                $itemId = $item['item_id'] ?? null;
                $isPresent = $item['is_present'] ?? 1;
                $note = $item['note'] ?? null;
                $recordedAt = $item['recorded_at'] ?? null; // Timestamp when item was scanned on mobile
                $photo = $item['photo'] ?? null;

                if ($itemId) {
                    // Check if this item was already recorded in this inventory by this user
                    $checkStmt = $db->prepare("SELECT id FROM inventory_items WHERE inventory_id = ? AND item_id = ? AND user_id = ?");
                    $checkStmt->execute([(int)$inventoryId, (int)$itemId, $user['id']]);
                    
                    if (!$checkStmt->fetch()) {
                        // Only add if not already recorded
                        $inventoryItemModel->add(
                            (int)$inventoryId,
                            (int)$itemId,
                            $user['id'],
                            (int)$isPresent,
                            $note,
                            $recordedAt // Pass the original recording time
                        );
                    }
                }
            }
        }

        $db->commit();

        ApiResponse::json([
            'message' => 'Submission successful',
            'submission_id' => $submissionId,
            'items_processed' => count($payload['items'] ?? [])
        ], 201);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Submission error: " . $e->getMessage());
        ApiResponse::json(['message' => 'Submission failed', 'error' => $e->getMessage()], 500);
    }
}

if ($method === 'GET') {
    // Handle different GET request types
    
    // 1. Worker requesting their own submissions
    if (isset($_GET['my_submissions']) && $_GET['my_submissions'] == '1') {
        $stmt = $db->prepare("
            SELECT s.*, i.name as inventory_name
            FROM inventory_submissions s
            JOIN inventories i ON s.inventory_id = i.id
            WHERE s.user_id = ?
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode payload JSON for each submission
        foreach ($submissions as &$sub) {
            if (isset($sub['payload'])) {
                $sub['payload'] = json_decode($sub['payload'], true);
            }
        }
        
        ApiResponse::json(['submissions' => $submissions], 200);
        exit;
    }
    
    // 2. Employer/Admin requesting submissions for a company
    if (isset($_GET['company_id'])) {
        $companyId = (int)$_GET['company_id'];
        
        // Verify user has access to this company
        if ($user['role'] !== 'admin') {
            $stmt = $db->prepare("SELECT 1 FROM company_user WHERE user_id = ? AND company_id = ?");
            $stmt->execute([$user['id'], $companyId]);
            if (!$stmt->fetch()) {
                ApiResponse::json(['message' => 'Access denied to this company'], 403);
                exit;
            }
        }
        
        $stmt = $db->prepare("
            SELECT s.*, i.name as inventory_name, 
                   CONCAT(u.first_name, ' ', u.last_name) as worker_name,
                   u.email as worker_email
            FROM inventory_submissions s
            JOIN inventories i ON s.inventory_id = i.id
            JOIN users u ON s.user_id = u.id
            WHERE i.company_id = ?
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$companyId]);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode payload JSON for each submission
        foreach ($submissions as &$sub) {
            if (isset($sub['payload'])) {
                $sub['payload'] = json_decode($sub['payload'], true);
            }
        }
        
        ApiResponse::json(['submissions' => $submissions], 200);
        exit;
    }
    
    // 3. Get submissions for a specific inventory
    $inventoryId = $_GET['inventory_id'] ?? null;
    
    if (!$inventoryId) {
        ApiResponse::json(['message' => 'inventory_id, company_id, or my_submissions required'], 422);
        exit;
    }

    $submissions = $inventoryModel->getSubmissions((int)$inventoryId);
    
    // Decode payload JSON for each submission
    foreach ($submissions as &$sub) {
        if (isset($sub['payload'])) {
            $sub['payload'] = json_decode($sub['payload'], true);
        }
    }
    
    ApiResponse::json(['submissions' => $submissions], 200);
    exit;
}

ApiResponse::json(['message' => 'Method not allowed'], 405);
