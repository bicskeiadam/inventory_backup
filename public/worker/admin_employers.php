<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/Company.php';

session_start();
if (empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}
$user = $_SESSION['user'];
$user['role'] = strtolower(trim($user['role'] ?? ''));
// Only admin can access
if ($user['role'] !== 'admin') {
    echo 'Nincs jogosultság. Csak adminisztrátor használhatja.';
    exit;
}

$db = (new Database())->getConnection();
$userModel = new User($db);
$companyModel = new Company($db);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_company'])) {
        $userId = (int) $_POST['user_id'];
        $companyId = (int) $_POST['company_id'];
        if ($userModel->assignCompany($userId, $companyId)) {
            $message = 'Cég sikeresen hozzárendelve a munkáltatóhoz.';
            $messageType = 'success';
        } else {
            $message = 'Hiba történt a hozzárendelés során.';
            $messageType = 'danger';
        }
    }
    if (isset($_POST['unassign_company'])) {
        $userId = (int) $_POST['user_id'];
        $companyId = (int) $_POST['company_id'];
        if ($userModel->unassignCompany($userId, $companyId)) {
            $message = 'Cég sikeresen eltávolítva a munkáltatótól.';
            $messageType = 'success';
        } else {
            $message = 'Hiba történt az eltávolítás során.';
            $messageType = 'danger';
        }
    }
}

$employers = $userModel->getEmployers();
$companies = $companyModel->all();

// Build employer-company mapping
$employerCompanies = [];
foreach ($employers as $emp) {
    $employerCompanies[$emp['id']] = $userModel->getCompaniesForUser($emp['id']);
}
?>
<!doctype html>
<html lang="hu">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Munkáltatók & Cégek - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .employer-card {
            transition: all 0.2s;
            border-left: 4px solid #0d6efd;
        }

        .employer-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .company-badge {
            margin: 2px;
        }
    </style>
    <link rel="stylesheet" href="../css/global-theme.css">
</head>

<body>
    <?php include_once __DIR__ . '/dashboard_nav.php'; ?>

    <div class="page-container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h2><i class="bi bi-building"></i> Munkáltatók & Cégek Kezelése</h2>
                    <div>
                        <span class="badge bg-info me-2"><?= count($employers) ?> munkáltató</span>
                        <span class="badge bg-secondary"><?= count($companies) ?> cég</span>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($employers)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Nincs még munkáltató a rendszerben. Hozz létre felhasználókat és
                        állítsd be a szerepkörüket "employer"-re.
                    </div>
                <?php endif; ?>

                <div class="row">
                    <?php foreach ($employers as $emp): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card employer-card">
                                <div class="card-header bg-primary text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0">
                                                <i class="bi bi-person-badge"></i>
                                                <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                            </h5>
                                            <small><?= htmlspecialchars($emp['email']) ?></small>
                                        </div>
                                        <div>
                                            <?php if ($emp['is_blocked']): ?>
                                                <span class="badge bg-danger">Letiltva</span>
                                            <?php elseif (!$emp['is_active']): ?>
                                                <span class="badge bg-warning text-dark">Inaktív</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Aktív</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="mb-3">
                                        <i class="bi bi-buildings"></i> Hozzárendelt Cégek
                                        <span
                                            class="badge bg-secondary"><?= count($employerCompanies[$emp['id']] ?? []) ?></span>
                                    </h6>

                                    <?php if (!empty($employerCompanies[$emp['id']])): ?>
                                        <div class="mb-3">
                                            <?php foreach ($employerCompanies[$emp['id']] as $comp): ?>
                                                <span class="badge bg-info company-badge">
                                                    <?= htmlspecialchars($comp['name']) ?>
                                                    <form method="post" class="d-inline" style="margin-left: 5px;">
                                                        <input type="hidden" name="user_id" value="<?= $emp['id'] ?>">
                                                        <input type="hidden" name="company_id" value="<?= $comp['id'] ?>">
                                                        <button type="submit" name="unassign_company"
                                                            class="btn-close btn-close-white" style="font-size: 0.6rem;"
                                                            onclick="return confirm('Biztosan eltávolítod ezt a céget?')"></button>
                                                    </form>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted small mb-3">Nincs hozzárendelt cég.</p>
                                    <?php endif; ?>

                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="user_id" value="<?= $emp['id'] ?>">
                                        <select name="company_id" class="form-select form-select-sm" required>
                                            <option value="">-- Válassz céget --</option>
                                            <?php
                                            $assignedIds = array_column($employerCompanies[$emp['id']] ?? [], 'id');
                                            foreach ($companies as $c):
                                                if (!in_array($c['id'], $assignedIds)):
                                                    ?>
                                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                                <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </select>
                                        <button type="submit" name="assign_company" class="btn btn-sm btn-primary">
                                            <i class="bi bi-plus-circle"></i> Hozzáad
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Összes Cég</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($companies as $c): ?>
                                <div class="col-md-4 mb-2">
                                    <div class="border rounded p-2">
                                        <strong><?= htmlspecialchars($c['name']) ?></strong>
                                        <?php
                                        // Count how many employers have this company
                                        $count = 0;
                                        foreach ($employerCompanies as $empComps) {
                                            if (in_array($c['id'], array_column($empComps, 'id'))) {
                                                $count++;
                                            }
                                        }
                                        ?>
                                        <small class="text-muted d-block"><?= $count ?> munkáltató</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>