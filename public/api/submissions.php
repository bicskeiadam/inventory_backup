<?php
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
}
$token = $m[1];
$stmt = $db->prepare("SELECT * FROM users WHERE api_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();
if (!$user) {
    ApiResponse::json(['message' => 'Invalid token'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
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
                            $note
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
    // Get submissions for an inventory
    $inventoryId = $_GET['inventory_id'] ?? null;
    
    if (!$inventoryId) {
        ApiResponse::json(['message' => 'inventory_id required'], 422);
    }

    $submissions = $inventoryModel->getSubmissions((int)$inventoryId);
    
    // Decode payload JSON for each submission
    foreach ($submissions as &$sub) {
        if (isset($sub['payload'])) {
            $sub['payload'] = json_decode($sub['payload'], true);
        }
    }
    
    ApiResponse::json(['submissions' => $submissions], 200);
}

ApiResponse::json(['message' => 'Method not allowed'], 405);
