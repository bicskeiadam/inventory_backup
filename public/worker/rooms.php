<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Room.php';
require_once __DIR__ . '/../../app/models/Company.php';

session_start();
if (empty($_SESSION['user'])) { header('Location: ../login.php'); exit; }
$user = $_SESSION['user'];
$user['role'] = strtolower(trim($user['role'] ?? ''));
if (!in_array($user['role'] ?? '', ['employer','admin'])) { echo 'Nincs jogosultság.'; exit; }

$db = (new Database())->getConnection();
$roomModel = new Room($db);
$companyModel = new Company($db);

$companyId = $_GET['company_id'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_room'])) {
        $roomModel->create((int)$_POST['company_id'], trim($_POST['name']));
        header('Location: rooms.php?company_id=' . urlencode($_POST['company_id'])); exit;
    }
    if (isset($_POST['edit_room'])) {
        $roomModel->update((int)$_POST['id'], trim($_POST['name']));
        header('Location: rooms.php?company_id=' . urlencode($_POST['company_id'])); exit;
    }
    if (isset($_POST['delete_room'])) {
        $roomModel->delete((int)$_POST['id']);
        header('Location: rooms.php?company_id=' . urlencode($_POST['company_id'])); exit;
    }
}

$companies = $companyModel->all();
$rooms = $companyId ? $roomModel->getByCompany((int)$companyId) : [];
?>
<!doctype html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <title>Helyiségek</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/global-theme.css">
    <!-- <link rel="stylesheet" href="../css/rooms.css"> -->
    <style>
        /* Fix list-group for dark mode */
        .list-group-item {
            background: var(--bg-card) !important;
            color: var(--text-primary) !important;
            border-color: var(--border) !important;
        }
        .list-group-item:hover {
            background: var(--bg-surface) !important;
        }
    </style>
</head>
<body>
<?php include_once __DIR__ . '/dashboard_nav.php'; ?>
<div class="page-container">
    <div class="col-md-8 mx-auto">
        <div class="card p-3">
            <h4>Helyiségek</h4>
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
                <ul class="list-group mb-3">
                    <?php foreach ($rooms as $r): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?=htmlspecialchars($r['name'])?>
                            <div>
                                <a href="rooms.php?action=edit&id=<?=$r['id']?>&company_id=<?=$companyId?>" class="btn btn-sm btn-outline-primary">Szerkeszt</a>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="delete_room" value="1">
                                    <input type="hidden" name="id" value="<?=$r['id']?>">
                                    <input type="hidden" name="company_id" value="<?=$companyId?>">
                                    <button class="btn btn-sm btn-outline-danger">Töröl</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])):
                    $room = $roomModel->findById((int)$_GET['id']);
                ?>
                    <form method="post">
                        <input type="hidden" name="edit_room" value="1">
                        <input type="hidden" name="id" value="<?=htmlspecialchars($room['id'])?>">
                        <input type="hidden" name="company_id" value="<?=$companyId?>">
                        <div class="mb-3">
                            <label for="edit_room_name" class="form-label">Név</label>
                            <input id="edit_room_name" name="name" class="form-control" value="<?=htmlspecialchars($room['name'])?>">
                        </div>
                        <button class="btn btn-primary">Mentés</button>
                    </form>
                <?php else: ?>
                    <form method="post" class="d-flex gap-2">
                        <input type="hidden" name="create_room" value="1">
                        <input type="hidden" name="company_id" value="<?=$companyId?>">
                        <label for="create_room_name" class="visually-hidden">Új helyiség neve</label>
                        <input id="create_room_name" name="name" class="form-control" placeholder="Új helyiség neve">
                        <button class="btn btn-success">Hozzáad</button>
                    </form>
                <?php endif; ?>

            <?php else: ?>
                <p>Válassz egy céget a helyiségek kezeléséhez.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Auto-refresh page every 30 seconds (if user isn't typing in a form)
    setInterval(function() {
        var activeElement = document.activeElement;
        var isTyping = activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA' || activeElement.tagName === 'SELECT');
        
        if (!isTyping) {
            location.reload();
        }
    }, 30000);
</script>
</body>
</html>
