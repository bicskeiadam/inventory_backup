<?php
/**
 * Dashboard API - Returns statistics for the dashboard
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'] ?? 0;
$role = strtolower(trim($user['role'] ?? ''));

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';

try {
    $pdo = (new Database())->getConnection();
} catch (Exception $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$response = [
    'stats' => [],
    'charts' => [],
    'recent_submissions' => [],
];

try {
    // Get company IDs the user has access to
    if ($role === 'admin') {
        // Admin sees all companies
        $stmt = $pdo->query("SELECT id FROM companies");
        $companyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        // Employer sees only their companies
        $stmt = $pdo->prepare("SELECT company_id FROM company_user WHERE user_id = ?");
        $stmt->execute([$userId]);
        $companyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    if (empty($companyIds)) {
        $companyIds = [0]; // No companies
    }
    $placeholders = implode(',', array_fill(0, count($companyIds), '?'));

    // === STATS ===
    
    // Inventory counts by status
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count 
        FROM inventories 
        WHERE company_id IN ($placeholders) 
        GROUP BY status
    ");
    $stmt->execute($companyIds);
    $inventoryCounts = ['active' => 0, 'scheduled' => 0, 'finished' => 0];
    while ($row = $stmt->fetch()) {
        $inventoryCounts[$row['status']] = (int)$row['count'];
    }
    $response['stats']['inventories'] = $inventoryCounts;
    $response['stats']['total_inventories'] = array_sum($inventoryCounts);

    // Pending submissions count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM inventory_submissions s 
        JOIN inventories i ON s.inventory_id = i.id 
        WHERE i.company_id IN ($placeholders) AND s.status = 'pending'
    ");
    $stmt->execute($companyIds);
    $response['stats']['pending_submissions'] = (int)$stmt->fetch()['count'];

    // Total submissions by status
    $stmt = $pdo->prepare("
        SELECT s.status, COUNT(*) as count 
        FROM inventory_submissions s 
        JOIN inventories i ON s.inventory_id = i.id 
        WHERE i.company_id IN ($placeholders) 
        GROUP BY s.status
    ");
    $stmt->execute($companyIds);
    $submissionCounts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    while ($row = $stmt->fetch()) {
        $submissionCounts[$row['status']] = (int)$row['count'];
    }
    $response['stats']['submissions'] = $submissionCounts;

    // Total items count (items -> rooms -> companies)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM items i
        JOIN rooms r ON i.room_id = r.id
        WHERE r.company_id IN ($placeholders)
    ");
    $stmt->execute($companyIds);
    $response['stats']['total_items'] = (int)$stmt->fetch()['count'];

    // Workers count (for employer/admin)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT cu.user_id) as count 
        FROM company_user cu 
        JOIN users u ON cu.user_id = u.id 
        WHERE cu.company_id IN ($placeholders) AND u.role = 'worker'
    ");
    $stmt->execute($companyIds);
    $response['stats']['workers_count'] = (int)$stmt->fetch()['count'];

    // === CHARTS DATA ===
    
    // Submissions per day (last 7 days)
    $stmt = $pdo->prepare("
        SELECT DATE(s.created_at) as date, COUNT(*) as count 
        FROM inventory_submissions s 
        JOIN inventories i ON s.inventory_id = i.id 
        WHERE i.company_id IN ($placeholders) 
          AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(s.created_at) 
        ORDER BY date ASC
    ");
    $stmt->execute($companyIds);
    $submissionsPerDay = [];
    while ($row = $stmt->fetch()) {
        $submissionsPerDay[] = ['date' => $row['date'], 'count' => (int)$row['count']];
    }
    $response['charts']['submissions_per_day'] = $submissionsPerDay;

    // === RECENT SUBMISSIONS ===
    $stmt = $pdo->prepare("
        SELECT s.id, s.status, s.created_at, i.name as inventory_name, CONCAT(u.first_name, ' ', u.last_name) as worker_name
        FROM inventory_submissions s 
        JOIN inventories i ON s.inventory_id = i.id 
        JOIN users u ON s.user_id = u.id
        WHERE i.company_id IN ($placeholders) 
        ORDER BY s.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute($companyIds);
    $response['recent_submissions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
