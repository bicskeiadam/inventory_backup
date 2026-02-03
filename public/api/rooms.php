<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/ApiResponse.php';
require_once __DIR__ . '/../../app/models/Room.php';

$db = (new Database())->getConnection();
$roomModel = new Room($db);

// token auth
$token = ApiResponse::getBearerToken();
if (!$token) ApiResponse::json(['message'=>'Unauthorized'], 401);
$stmt = $db->prepare("SELECT * FROM users WHERE api_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();
if (!$user) ApiResponse::json(['message'=>'Invalid token'], 401);

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $companyId = $_GET['company_id'] ?? null;
    if (!$companyId) ApiResponse::json(['message'=>'company_id required'], 422);
    $rooms = $roomModel->getByCompany((int)$companyId);
    ApiResponse::json(['rooms'=>$rooms], 200);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $companyId = $input['company_id'] ?? null;
    $name = $input['name'] ?? null;
    if (!$companyId || !$name) ApiResponse::json(['message'=>'company_id & name required'], 422);
    $id = $roomModel->create((int)$companyId, $name);
    ApiResponse::json(['id'=>$id], 201);
}

ApiResponse::json(['message'=>'Method not allowed'], 405);
