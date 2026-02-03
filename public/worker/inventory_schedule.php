<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Inventory.php';
require_once __DIR__ . '/../../app/models/Company.php';
require_once __DIR__ . '/../../app/models/Team.php';
require_once __DIR__ . '/../../app/models/Room.php';

session_start(); if (empty($_SESSION['user'])) { header('Location: ../login.php'); exit; }
$user = $_SESSION['user'];
$user['role'] = strtolower(trim($user['role'] ?? ''));
$_SESSION['user']['role'] = $user['role'];
if (!in_array($user['role'] ?? '', ['employer','admin'])) { echo 'Nincs jogosultság.'; exit; }

$db = (new Database())->getConnection();
$invModel = new Inventory($db);
$companyModel = new Company($db);
$teamModel = new Team($db);
$roomModel = new Room($db);

$companyId = $_GET['company_id'] ?? null;
$inventoryId = $_GET['inventory_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // scheduling targets for existing inventory
    if (isset($_POST['schedule']) && isset($_POST['inventory_id'])) {
        $inventoryId = (int)$_POST['inventory_id'];
        $startDate = $_POST['start_date'] ?: null;
        $invModel->schedule($inventoryId, $startDate);
        $targets = [];
        if (!empty($_POST['target_all'])) {
            $targets[] = ['type'=>'all','id'=>null];
        }
        if (!empty($_POST['target_team']) && is_array($_POST['target_team'])) {
            foreach ($_POST['target_team'] as $t) $targets[] = ['type'=>'team','id'=> (int)$t];
        }
        if (!empty($_POST['target_room']) && is_array($_POST['target_room'])) {
            foreach ($_POST['target_room'] as $r) $targets[] = ['type'=>'room','id'=> (int)$r];
        }
        if (!empty($targets)) $invModel->addScheduleTargets($inventoryId, $targets);
        header('Location: inventory_schedule.php?company_id=' . urlencode($_POST['company_id'])); exit;
    }

    // create inventory and schedule immediately
    if (isset($_POST['create_and_schedule'])) {
        $name = trim($_POST['name'] ?? '');
        $startDate = $_POST['start_date'] ?: null;
        $targets = [];
        if (!empty($_POST['target_all'])) $targets[] = ['type'=>'all','id'=>null];
        if (!empty($_POST['target_team']) && is_array($_POST['target_team'])) {
            foreach ($_POST['target_team'] as $t) $targets[] = ['type'=>'team','id'=>(int)$t];
        }
        if (!empty($_POST['target_room']) && is_array($_POST['target_room'])) {
            foreach ($_POST['target_room'] as $r) $targets[] = ['type'=>'room','id'=>(int)$r];
        }
        if ($companyId && $name) {
            $invModel->createWithSchedule((int)$companyId, $name, $startDate, $targets);
        }
        header('Location: inventory_schedule.php?company_id=' . urlencode($_POST['company_id'] ?? $companyId)); exit;
    }
}

$companies = $companyModel->all();
$inventories = $companyId ? $invModel->getByCompany((int)$companyId) : [];
$teams = $companyId ? $teamModel->allByCompany((int)$companyId) : [];
$rooms = $companyId ? $roomModel->getByCompany((int)$companyId) : [];
?>
<!doctype html>
<html lang="hu">
<head><meta charset="utf-8"><title>Leltár ütemezés</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="../css/global-theme.css">
</head>
<!-- <link rel="stylesheet" href="../css/schedule.css"> -->
<body>
<?php include_once __DIR__ . '/dashboard_nav.php'; ?>
<div class="page-container">
    <div class="col-md-10 mx-auto card p-3">
        <h4>Leltár ütemezés</h4>
        <form method="get" class="mb-3 d-flex gap-2">
            <label for="company_id" class="visually-hidden">Cég</label>
            <select id="company_id" name="company_id" class="form-select">
                <option value="">-- Válassz céget --</option>
                <?php foreach ($companies as $c): ?>
                    <option value="<?=$c['id']?>" <?=($companyId == $c['id'])?'selected':''?>><?=htmlspecialchars($c['name'])?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-primary">Mutat</button>
        </form>

        <?php if ($companyId): ?>
            <div class="row">
                <div class="col-md-6">
                    <h5>Meglévő leltárak</h5>
                    <ul class="list-group mb-3">
                        <?php foreach ($inventories as $inv): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?=htmlspecialchars($inv['name'] ?? 'Leltár #'.$inv['id'])?> <small><?=htmlspecialchars($inv['status'])?></small>
                                <a href="inventory_schedule.php?company_id=<?=$companyId?>&inventory_id=<?=$inv['id']?>" class="btn btn-sm btn-outline-primary">Ütemez</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="col-md-6">
                    <h5>Ütemezési célok</h5>
                    <form method="post">
                        <input type="hidden" name="company_id" value="<?=$companyId?>">
                        <div class="mb-2">
                            <label class="form-label">Célok kiválasztása (csapatok/helyiségek)</label>
                            <div class="mb-2">
                                <label class="form-check">
                                    <input type="checkbox" name="target_all" value="1" class="form-check-input"> Minden
                                </label>
                            </div>
                            <div class="mb-2">
                                <strong>Csapatok</strong>
                                <?php foreach ($teams as $t): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_team[]" value="<?=$t['id']?>" id="team_<?=$t['id']?>">
                                        <label class="form-check-label" for="team_<?=$t['id']?>"><?=htmlspecialchars($t['name'])?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mb-2">
                                <strong>Helyiségek</strong>
                                <?php foreach ($rooms as $r): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_room[]" value="<?=$r['id']?>" id="room_<?=$r['id']?>">
                                        <label class="form-check-label" for="room_<?=$r['id']?>"><?=htmlspecialchars($r['name'])?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Kezdés (opcionális)</label>
                            <input type="datetime-local" name="start_date" class="form-control">
                        </div>

                        <?php if ($inventoryId): ?>
                            <input type="hidden" name="inventory_id" value="<?=htmlspecialchars($inventoryId)?>">
                            <button name="schedule" class="btn btn-primary">Ütemezés mentése</button>
                        <?php else: ?>
                            <div class="mb-2">
                                <label class="form-label">Új leltár neve (ha újat szeretnél létrehozni)</label>
                                <input name="name" class="form-control">
                            </div>
                            <button name="create_and_schedule" class="btn btn-success">Létrehozás és ütemezés</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
         <?php else: ?>
             <p>Válassz egy céget.</p>
         <?php endif; ?>
     </div>
 </div>
</body>
</html>
