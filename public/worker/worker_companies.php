<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Company.php';
require_once __DIR__ . '/../../app/models/Inventory.php';

session_start();
if (empty($_SESSION['user'])) { header('Location: ../login.php'); exit; }
$user = $_SESSION['user'];
$user['role'] = strtolower(trim($user['role'] ?? ''));

$db = (new Database())->getConnection();
$companyModel = new Company($db);
$inventoryModel = new Inventory($db);

// Get companies assigned to this worker
$stmt = $db->prepare("
    SELECT c.id, c.name 
    FROM companies c
    INNER JOIN company_user cu ON c.id = cu.company_id
    WHERE cu.user_id = ?
    ORDER BY c.name
");
$stmt->execute([(int)$user['id']]);
$myCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get active/scheduled inventories for my companies
$companyIds = array_column($myCompanies, 'id');
$activeInventories = [];
if (!empty($companyIds)) {
    $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
    $stmt = $db->prepare("
        SELECT i.*, c.name as company_name,
               (SELECT COUNT(*) FROM inventory_schedules WHERE inventory_id = i.id) as has_schedule
        FROM inventories i
        INNER JOIN companies c ON i.company_id = c.id
        WHERE i.company_id IN ($placeholders) 
        AND i.status IN ('active', 'scheduled')
        ORDER BY 
            CASE WHEN i.status = 'active' THEN 1 ELSE 2 END,
            i.start_date ASC
    ");
    $stmt->execute($companyIds);
    $activeInventories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get my assigned rooms/tasks via team_rooms
$myTasks = [];
if (!empty($companyIds)) {
    $stmt = $db->prepare("
        SELECT DISTINCT 
            r.id as room_id,
            r.name as room_name,
            c.id as company_id,
            c.name as company_name,
            t.name as team_name,
            tr.info as task_info
        FROM team_user tu
        INNER JOIN teams t ON tu.team_id = t.id
        INNER JOIN team_room tr ON t.id = tr.team_id
        INNER JOIN rooms r ON tr.room_id = r.id
        INNER JOIN companies c ON r.company_id = c.id
        WHERE tu.user_id = ?
        AND c.id IN ($placeholders)
        ORDER BY c.name, r.name
    ");
    $params = array_merge([(int)$user['id']], $companyIds);
    $stmt->execute($params);
    $myTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cégeim és Feladataim</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .company-card {
            transition: all 0.2s;
            border-left: 4px solid #0d6efd;
            background: var(--bg-card);
        }
        .company-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .task-item {
            border-left: 3px solid #198754;
            padding-left: 12px;
            background: rgba(25, 135, 84, 0.05);
            padding: 8px 12px;
            border-radius: 4px;
        }
        .inventory-active {
            background-color: rgba(25, 135, 84, 0.15);
            border-left: 4px solid #198754;
        }
        .inventory-scheduled {
            background-color: rgba(255, 193, 7, 0.15);
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
<?php include_once __DIR__ . '/dashboard_nav.php'; ?>

<div class="page-container">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="bi bi-briefcase-fill"></i> Cégeim és Feladataim</h2>

            <?php if (empty($myCompanies)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> 
                    Még nincs céghez rendelve. Kérj meg egy munkáltatót vagy adminisztrátort, hogy rendeljen hozzá egy céghez!
                </div>
            <?php else: ?>
                
                <!-- My Companies -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-building"></i> Cégeim (<?= count($myCompanies) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($myCompanies as $company): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card company-card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="bi bi-building"></i>
                                                <?= htmlspecialchars($company['name']) ?>
                                            </h5>
                                            <a href="inventories.php?company_id=<?= $company['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-list-check"></i> Leltárak megtekintése
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Active Inventories -->
                <?php if (!empty($activeInventories)): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Aktív és Ütemezett Leltárak</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($activeInventories as $inv): ?>
                                <div class="card mb-3 <?= $inv['status'] === 'active' ? 'inventory-active' : 'inventory-scheduled' ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?php if ($inv['status'] === 'active'): ?>
                                                        <span class="badge bg-success">AKTÍV</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">ÜTEMEZETT</span>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($inv['name'] ?? 'Leltár #' . $inv['id']) ?>
                                                </h6>
                                                <p class="mb-2 text-muted">
                                                    <i class="bi bi-building"></i> <?= htmlspecialchars($inv['company_name']) ?>
                                                    <?php if ($inv['start_date']): ?>
                                                        | <i class="bi bi-calendar3"></i> <?= date('Y-m-d H:i', strtotime($inv['start_date'])) ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div>
                                                <?php if ($inv['status'] === 'active'): ?>
                                                    <a href="inventories.php?company_id=<?= $inv['company_id'] ?>" class="btn btn-success btn-sm">
                                                        <i class="bi bi-play-fill"></i> Leltározás indítása
                                                    </a>
                                                <?php else: ?>
                                                    <a href="inventories.php?company_id=<?= $inv['company_id'] ?>" class="btn btn-warning btn-sm">
                                                        <i class="bi bi-eye"></i> Részletek
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Jelenleg nincs aktív vagy ütemezett leltár.
                    </div>
                <?php endif; ?>

                <!-- My Assigned Tasks/Rooms -->
                <?php if (!empty($myTasks)): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-list-task"></i> Kijelölt Feladataim</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Csapatodon keresztül hozzárendelt helyiségek:</p>
                            <?php 
                            $tasksByCompany = [];
                            foreach ($myTasks as $task) {
                                $tasksByCompany[$task['company_name']][] = $task;
                            }
                            ?>
                            <?php foreach ($tasksByCompany as $companyName => $tasks): ?>
                                <h6 class="mb-3"><i class="bi bi-building"></i> <?= htmlspecialchars($companyName) ?></h6>
                                <div class="row mb-4">
                                    <?php foreach ($tasks as $task): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="task-item">
                                                <strong><i class="bi bi-door-open"></i> <?= htmlspecialchars($task['room_name']) ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    Csapat: <?= htmlspecialchars($task['team_name']) ?>
                                                    <?php if ($task['task_info']): ?>
                                                        | <?= htmlspecialchars($task['task_info']) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary">
                        <i class="bi bi-info-circle"></i> Még nincs konkrét helyiség hozzád rendelve csapatokon keresztül.
                    </div>
                <?php endif; ?>

            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning-fill"></i> Gyors Műveletek</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <a href="dashboard.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-house-door"></i> Vissza a Dashboard-ra
                            </a>
                        </div>
                        <div class="col-md-4 mb-2">
                            <a href="profile.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-person-circle"></i> Profil Szerkesztése
                            </a>
                        </div>
                        <?php if (!empty($myCompanies)): ?>
                            <div class="col-md-4 mb-2">
                                <a href="inventories.php?company_id=<?= $myCompanies[0]['id'] ?>" class="btn btn-outline-success w-100">
                                    <i class="bi bi-clipboard-check"></i> Leltárak
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
