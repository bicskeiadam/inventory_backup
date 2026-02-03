<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/ApiResponse.php';
require_once __DIR__ . '/../../app/models/User.php';

$db = (new Database())->getConnection();
$userModel = new User($db);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::json(['message'=>'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? null;
$pass = $input['password'] ?? null;

if (!$email || !$pass) ApiResponse::json(['message'=>'Missing credentials'], 422);

$user = $userModel->verifyCredentials($email, $pass);
if (!$user) ApiResponse::json(['message'=>'Invalid credentials or inactive account'], 401);

$token = bin2hex(random_bytes(32));
$userModel->setApiToken($user['id'], $token);

$normalizedRole = strtolower(trim($user['role'] ?? 'worker'));

// Fetch company_id for workers
$companyId = null;
if ($normalizedRole === 'worker') {
    $stmt = $db->prepare("SELECT company_id FROM company_user WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $companyRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $companyId = $companyRow ? (int)$companyRow['company_id'] : null;
}

// For employers/admins, get the first company they manage (or could be improved to get their own company)
if ($normalizedRole === 'employer' || $normalizedRole === 'admin') {
    $stmt = $db->prepare("SELECT id FROM companies ORDER BY id ASC LIMIT 1");
    $stmt->execute();
    $companyRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $companyId = $companyRow ? (int)$companyRow['id'] : null;
}

ApiResponse::json([
    'token' => $token, 
    'user' => [
        'id' => $user['id'],
        'email' => $user['email'],
        'role' => $normalizedRole,
        'name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['email'],
        'company_id' => $companyId
    ]
], 200);
