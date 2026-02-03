<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/Company.php';

session_start();
if (empty($_SESSION['user'])) { header('Location: ../login.php'); exit; }
$user = $_SESSION['user'];
$user['role'] = strtolower(trim($user['role'] ?? ''));
if (!in_array($user['role'] ?? '', ['employer','admin'])) { echo 'Nincs jogosultság.'; exit; }

$db = (new Database())->getConnection();
$userModel = new User($db);
$companyModel = new Company($db);

$message = '';
$messageType = '';

// Handle worker assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_worker'])) {
        $workerId = (int)$_POST['worker_id'];
        $companyId = (int)$_POST['company_id'];
        
        // Verify that the employer has access to this company
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM company_user WHERE user_id = ? AND company_id = ?");
        $stmt->execute([(int)$user['id'], $companyId]);
        $hasAccess = $stmt->fetch()['count'] > 0;
        
        if (!$hasAccess && $user['role'] !== 'admin') {
            $message = 'Nincs jogosultságod ehhez a céghez.';
            $messageType = 'danger';
        } else {
            // Remove worker from any existing company (worker can only be in one company)
            $stmt = $db->prepare("DELETE FROM company_user WHERE user_id = ?");
            $stmt->execute([$workerId]);
            
            // Assign to new company
            if ($userModel->assignCompany($workerId, $companyId)) {
                $message = 'Munkás sikeresen hozzárendelve a céghez.';
                $messageType = 'success';
            } else {
                $message = 'Hiba történt a hozzárendelés során.';
                $messageType = 'danger';
            }
        }
    }
    
    if (isset($_POST['remove_worker'])) {
        $workerId = (int)$_POST['worker_id'];
        $companyId = (int)$_POST['company_id'];
        
        // Verify employer has access to this company
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM company_user WHERE user_id = ? AND company_id = ?");
        $stmt->execute([(int)$user['id'], $companyId]);
        $hasAccess = $stmt->fetch()['count'] > 0;
        
        if (!$hasAccess && $user['role'] !== 'admin') {
            $message = 'Nincs jogosultságod ehhez a céghez.';
            $messageType = 'danger';
        } else {
            if ($userModel->unassignCompany($workerId, $companyId)) {
                $message = 'Munkás eltávolítva a cégtől.';
                $messageType = 'success';
            } else {
                $message = 'Hiba történt az eltávolítás során.';
                $messageType = 'danger';
            }
        }
    }
}

// Get companies accessible to this employer
if ($user['role'] === 'admin') {
    $myCompanies = $companyModel->all();
} else {
    $myCompanies = $userModel->getCompaniesForUser((int)$user['id']);
}

// Get free workers (not assigned to any company)
$stmt = $db->query("
    SELECT u.id, u.email, u.first_name, u.last_name, u.phone, u.is_active, u.created_at
    FROM users u
    WHERE u.role = 'worker' 
    AND u.is_active = 1
    AND u.is_blocked = 0
    AND u.id NOT IN (SELECT user_id FROM company_user)
    ORDER BY u.created_at DESC
");
$freeWorkers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get workers assigned to my companies
$assignedWorkers = [];
if (!empty($myCompanies)) {
    $companyIds = array_column($myCompanies, 'id');
    $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
    $stmt = $db->prepare("
        SELECT u.id, u.email, u.first_name, u.last_name, u.phone, 
               c.id as company_id, c.name as company_name, u.created_at as assigned_at
        FROM users u
        INNER JOIN company_user cu ON u.id = cu.user_id
        INNER JOIN companies c ON cu.company_id = c.id
        WHERE u.role = 'worker' 
        AND c.id IN ($placeholders)
        ORDER BY c.name, u.first_name, u.last_name
    ");
    $stmt->execute($companyIds);
    $assignedWorkers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Munkások Hozzárendelése</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .worker-card {
            transition: all 0.2s;
            border-left: 4px solid #0dcaf0;
        }
        .worker-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .free-worker-badge {
            background-color: #198754;
            color: white;
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .assigned-badge {
            background-color: #0d6efd;
            color: white;
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-light">
<?php include_once __DIR__ . '/dashboard_nav.php'; ?>

<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-people-fill"></i> Munkások Hozzárendelése</h2>
                <div>
                    <span class="badge bg-success me-2"><?= count($freeWorkers) ?> szabad munkás</span>
                    <span class="badge bg-primary"><?= count($assignedWorkers) ?> hozzárendelt</span>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($myCompanies)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> Nincs cég hozzárendelve. Kérj meg egy adminisztrátort, hogy rendeljen hozzá cégeket!
                </div>
            <?php else: ?>

                <!-- Free Workers Section -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-person-plus"></i> 
                            Szabad Munkások (<?= count($freeWorkers) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($freeWorkers)): ?>
                            <p class="text-muted">Jelenleg nincs hozzá nem rendelt munkás a rendszerben.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($freeWorkers as $worker): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card worker-card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <i class="bi bi-person-circle"></i>
                                                            <?= htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']) ?>
                                                        </h6>
                                                        <small class="text-muted d-block"><?= htmlspecialchars($worker['email']) ?></small>
                                                        <?php if ($worker['phone']): ?>
                                                            <small class="text-muted d-block">
                                                                <i class="bi bi-telephone"></i> <?= htmlspecialchars($worker['phone']) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="free-worker-badge">SZABAD</span>
                                                </div>
                                                <small class="text-muted d-block mb-2">
                                                    Regisztráció: <?= date('Y-m-d', strtotime($worker['created_at'])) ?>
                                                </small>
                                                <form method="post" class="d-flex gap-2">
                                                    <input type="hidden" name="worker_id" value="<?= $worker['id'] ?>">
                                                    <select name="company_id" class="form-select form-select-sm" required>
                                                        <option value="">-- Válassz céget --</option>
                                                        <?php foreach ($myCompanies as $company): ?>
                                                            <option value="<?= $company['id'] ?>">
                                                                <?= htmlspecialchars($company['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" name="assign_worker" class="btn btn-sm btn-success">
                                                        <i class="bi bi-plus-circle"></i> Hozzárendel
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Assigned Workers Section -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-people"></i> 
                            Hozzárendelt Munkások (<?= count($assignedWorkers) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assignedWorkers)): ?>
                            <p class="text-muted">Még nincs munkás hozzárendelve a cégeidhez.</p>
                        <?php else: ?>
                            <?php
                            // Group by company
                            $workersByCompany = [];
                            foreach ($assignedWorkers as $worker) {
                                $workersByCompany[$worker['company_name']][] = $worker;
                            }
                            ?>
                            <?php foreach ($workersByCompany as $companyName => $workers): ?>
                                <h6 class="mb-3 mt-3">
                                    <i class="bi bi-building"></i> <?= htmlspecialchars($companyName) ?>
                                    <span class="badge bg-secondary"><?= count($workers) ?> munkás</span>
                                </h6>
                                <div class="row">
                                    <?php foreach ($workers as $worker): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <h6 class="mb-1">
                                                                <i class="bi bi-person-check"></i>
                                                                <?= htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']) ?>
                                                            </h6>
                                                            <small class="text-muted d-block"><?= htmlspecialchars($worker['email']) ?></small>
                                                            <?php if ($worker['phone']): ?>
                                                                <small class="text-muted d-block">
                                                                    <i class="bi bi-telephone"></i> <?= htmlspecialchars($worker['phone']) ?>
                                                                </small>
                                                            <?php endif; ?>
                                                        </div>
                                                        <span class="assigned-badge">AKTÍV</span>
                                                    </div>
                                                    <small class="text-muted d-block mb-2">
                                                        Hozzárendelve: <?= date('Y-m-d', strtotime($worker['assigned_at'])) ?>
                                                    </small>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="worker_id" value="<?= $worker['id'] ?>">
                                                        <input type="hidden" name="company_id" value="<?= $worker['company_id'] ?>">
                                                        <button type="submit" name="remove_worker" class="btn btn-sm btn-outline-danger" 
                                                                onclick="return confirm('Biztosan eltávolítod ezt a munkást a cégtől?')">
                                                            <i class="bi bi-x-circle"></i> Eltávolít
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
