<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Inventory.php';
require_once __DIR__ . '/../../app/models/Company.php';

session_start();
if (empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}
$user = $_SESSION['user'];
$user['role'] = strtolower(trim($user['role'] ?? ''));

// Init DB first
$db = (new Database())->getConnection();
$inventoryModel = new Inventory($db);
$companyModel = new Company($db);

// Allow workers, employers, admins
if (!in_array($user['role'], ['worker', 'employer', 'admin'])) {
    die('Nincs jogosultság ehhez az oldalhoz.');
}

$companyId = $_GET['company_id'] ?? null;

// For workers, ensure they can only see their own company's archive if not specified (or enforce it)
if ($user['role'] === 'worker') {
    // Fetch worker's company
    $stmt = $db->prepare("SELECT company_id FROM company_user WHERE user_id = ? LIMIT 1");
    $stmt->execute([(int) $user['id']]);
    $workerCompany = $stmt->fetch();
    if ($workerCompany) {
        $companyId = $workerCompany['company_id'];
    }
}

if (!$companyId) {
    header('Location: inventories.php');
    exit;
}

$company = $companyModel->findById((int) $companyId);
$archivedInventories = $inventoryModel->getArchive((int) $companyId);

?>
<!doctype html>
<html lang="hu">

<head>
    <meta charset="utf-8">
    <title>Archív Leltárak</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/global-theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>

<body>
    <?php include_once __DIR__ . '/dashboard_nav.php'; ?>
    <div class="page-container">
        <div class="col-md-9 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <a href="inventories.php?company_id=<?= $companyId ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Vissza
                        </a>
                        <h4 class="mb-0">Archív Leltárak: <?= htmlspecialchars($company['name'] ?? '') ?></h4>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($archivedInventories)): ?>
                        <div class="alert alert-info">Nincs befejezett (archivált) leltár ebben a cégben.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Leltár Neve</th>
                                        <th>Indítva</th>
                                        <th>Státusz</th>
                                        <th>Műveletek</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($archivedInventories as $inv): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($inv['name']) ?></strong></td>
                                            <td>
                                                <?= $inv['start_date'] ? date('Y.m.d H:i', strtotime($inv['start_date'])) : '-' ?>
                                            </td>
                                            <td><span class="badge bg-secondary">Befejezve</span></td>
                                            <td>
                                                <a href="inventory_summary.php?inventory_id=<?= $inv['id'] ?>&company_id=<?= $companyId ?>"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="bi bi-file-earmark-text"></i> Összegzés
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>