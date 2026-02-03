<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/ApiResponse.php';
require_once __DIR__ . '/../../app/models/Item.php';
require_once __DIR__ . '/../../app/models/User.php';

$db = (new Database())->getConnection();
$itemModel = new Item($db);
$userModel = new User($db);

// simple token auth
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
    // Support both room_id and inventory_id
    $roomId = $_GET['room_id'] ?? null;
    $inventoryId = $_GET['inventory_id'] ?? null;
    
    if ($inventoryId) {
        // Get all items for an inventory (across all rooms in that inventory)
        $stmt = $db->prepare("
            SELECT DISTINCT i.* 
            FROM items i
            JOIN rooms r ON i.room_id = r.id
            JOIN inventories inv ON r.company_id = inv.company_id
            WHERE inv.id = ?
            ORDER BY i.name
        ");
        $stmt->execute([$inventoryId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::json(['items' => $items], 200);
    }
    
    if ($roomId) {
        $items = $itemModel->getByRoom((int)$roomId);
        ApiResponse::json(['items'=>$items], 200);
    }
    
    ApiResponse::json(['message'=>'room_id or inventory_id required'], 422);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $roomId = $input['room_id'] ?? null;
    $name = $input['name'] ?? null;
    if (!$roomId || !$name) ApiResponse::json(['message'=>'room_id and name required'], 422);
    
    // Create item first to get the ID
    $fileName = 'qr_'.time().'_'.bin2hex(random_bytes(6)).'.png';
    $qrWebPath = '/uploads/qr/' . $fileName;
    $id = $itemModel->create((int)$roomId, $name, '', $qrWebPath);
    
    // Now create QR code with item_id
    $qrPayload = "item_id={$id}";
    $dir = __DIR__ . '/../../public/uploads/qr';
    if(!is_dir($dir)) mkdir($dir, 0777, true);
    $filePath = $dir . '/' . $fileName;
    require_once __DIR__ . '/../../app/core/QRGenerator.php';
    QRGenerator::generate($qrPayload, $filePath);
    
    // Update item with QR payload
    $stmt = $db->prepare("UPDATE items SET qr_code = ? WHERE id = ?");
    $stmt->execute([$qrWebPath, $id]);
    
    ApiResponse::json(['id'=>$id, 'qr'=>$qrWebPath], 201);
}

if ($method === 'DELETE') {
    // expects ?id=#
    $id = $_GET['id'] ?? null;
    if (!$id) ApiResponse::json(['message'=>'id required'], 422);
    $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
    $stmt->execute([(int)$id]);
    ApiResponse::json(['message'=>'Deleted'], 204);
}

ApiResponse::json(['message'=>'Method not implemented'], 405);
