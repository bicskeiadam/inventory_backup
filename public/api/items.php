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
if (!$auth || !preg_match('/Bearer\s(\S+)/', $auth, $m))
    ApiResponse::json(['message' => 'Unauthorized'], 401);
$token = $m[1];
$stmt = $db->prepare("SELECT * FROM users WHERE api_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();
if (!$user)
    ApiResponse::json(['message' => 'Invalid token'], 401);

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
        $items = $itemModel->getByRoom((int) $roomId);
        ApiResponse::json(['items' => $items], 200);
    }

    ApiResponse::json(['message' => 'room_id or inventory_id required'], 422);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    // Check for specific action
    if (isset($input['action'])) {
        // Handle Action based requests below
    } else {
        // Default: Create new Item (Legacy)
        $roomId = $input['room_id'] ?? null;
        $name = $input['name'] ?? null;
        if (!$roomId || !$name)
            ApiResponse::json(['message' => 'room_id and name required'], 422);
    }

    // Create item first to get the ID
    if (!isset($input['action'])) {
        $roomId = $input['room_id'] ?? null;
        $name = $input['name'] ?? null;
        if (!$roomId || !$name)
            ApiResponse::json(['message' => 'room_id and name required'], 422);

        // Create item first to get the ID
        $fileName = 'qr_' . time() . '_' . bin2hex(random_bytes(6)) . '.png';
        $qrWebPath = '/uploads/qr/' . $fileName;
        $id = $itemModel->create((int) $roomId, $name, '', $qrWebPath);

        // Now create QR code with item_id
        $qrPayload = "item_id={$id}";
        $dir = __DIR__ . '/../../public/uploads/qr';
        if (!is_dir($dir))
            mkdir($dir, 0777, true);
        $filePath = $dir . '/' . $fileName;
        require_once __DIR__ . '/../../app/core/QRGenerator.php';
        QRGenerator::generate($qrPayload, $filePath);

        // Update item with QR payload
        $stmt = $db->prepare("UPDATE items SET qr_code = ? WHERE id = ?");
        $stmt->execute([$qrWebPath, $id]);

        ApiResponse::json(['id' => $id, 'qr' => $qrWebPath], 201);
    }

    // Record inventory item (Mobile App Action)
    if ($input['action'] === 'record_item') {
        try {
            require_once __DIR__ . '/../../app/models/InventoryItem.php';
            $inventoryItemModel = new InventoryItem($db);

            $inventoryId = $input['inventory_id'] ?? null;
            $itemId = $input['item_id'] ?? null;
            $isPresent = isset($input['is_present']) ? (int) $input['is_present'] : 1;
            $note = $input['note'] ?? '';
            $photoBase64 = $input['photo'] ?? null;

            if (!$inventoryId || !$itemId) {
                throw new Exception('inventory_id and item_id required');
            }

            $photoPath = null;
            // Handle photo upload
            if ($photoBase64) {
                // Expect base64 string (maybe with data:image/jpeg;base64, prefix)
                if (preg_match('/^data:image\/(\w+);base64,/', $photoBase64, $type)) {
                    $photoBase64 = substr($photoBase64, strpos($photoBase64, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, gif
                    if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp']))
                        $type = 'jpg';

                    $photoBase64 = base64_decode($photoBase64);
                    if ($photoBase64) {
                        $fileName = 'photo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $type;
                        $dir = __DIR__ . '/../../public/uploads/photos';
                        if (!is_dir($dir)) {
                            if (!mkdir($dir, 0777, true)) {
                                throw new Exception("Failed to create directory: $dir");
                            }
                        }

                        if (file_put_contents($dir . '/' . $fileName, $photoBase64) === false) {
                            throw new Exception("Failed to write file to $dir");
                        }
                        $photoPath = '/uploads/photos/' . $fileName;
                    }
                }
            }

            $inventoryItemModel->record(
                (int) $inventoryId,
                (int) $itemId,
                (int) $user['id'],
                $isPresent,
                $note,
                $photoPath
            );

            ApiResponse::json(['success' => true, 'photo' => $photoPath], 200);

        } catch (Exception $e) {
            ApiResponse::json(['message' => 'Error recording item', 'error' => $e->getMessage()], 500);
        }
    }
}

if ($method === 'DELETE') {
    // expects ?id=#
    $id = $_GET['id'] ?? null;
    if (!$id)
        ApiResponse::json(['message' => 'id required'], 422);
    $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
    $stmt->execute([(int) $id]);
    ApiResponse::json(['message' => 'Deleted'], 204);
}

ApiResponse::json(['message' => 'Method not implemented'], 405);
