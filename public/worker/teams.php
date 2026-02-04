<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Team.php';
require_once __DIR__ . '/../../app/models/Company.php';
require_once __DIR__ . '/../../app/models/Room.php';
require_once __DIR__ . '/../../app/models/User.php';

session_start();
if (empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}
$user = $_SESSION['user'];
$user['role'] = strtolower(trim($user['role'] ?? ''));
if (!in_array($user['role'] ?? '', ['employer', 'admin'])) {
    echo 'Nincs jogosultság.';
    exit;
}

$db = (new Database())->getConnection();
$teamModel = new Team($db);
$companyModel = new Company($db);
$roomModel = new Room($db);
$userModel = new User($db);

$companyId = $_GET['company_id'] ?? null;
$teamId = $_GET['team_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_team'])) {
        $teamModel->create((int) $_POST['company_id'], trim($_POST['name']));
        header('Location: teams.php?company_id=' . urlencode($_POST['company_id']));
        exit;
    }
    if (isset($_POST['edit_team'])) {
        $teamModel->update((int) $_POST['id'], trim($_POST['name']));
        header('Location: teams.php?company_id=' . urlencode($_POST['company_id']));
        exit;
    }
    if (isset($_POST['delete_team'])) {
        $teamModel->delete((int) $_POST['id']);
        header('Location: teams.php?company_id=' . urlencode($_POST['company_id']));
        exit;
    }
    if (isset($_POST['add_member'])) {
        $teamModel->addMember((int) $_POST['team_id'], (int) $_POST['user_id']);
        header('Location: teams.php?company_id=' . urlencode($_POST['company_id']) . '&team_id=' . urlencode($_POST['team_id']));
        exit;
    }
    if (isset($_POST['remove_member'])) {
        $teamModel->removeMember((int) $_POST['team_id'], (int) $_POST['user_id']);
        header('Location: teams.php?company_id=' . urlencode($_POST['company_id']) . '&team_id=' . urlencode($_POST['team_id']));
        exit;
    }
    if (isset($_POST['assign_room'])) {
        $teamModel->assignRoom((int) $_POST['team_id'], (int) $_POST['room_id'], trim($_POST['info']));
        header('Location: teams.php?company_id=' . urlencode($_POST['company_id']) . '&team_id=' . urlencode($_POST['team_id']));
        exit;
    }
    if (isset($_POST['remove_assignment'])) {
        $teamModel->removeAssignment((int) $_POST['team_id'], (int) $_POST['room_id']);
        header('Location: teams.php?company_id=' . urlencode($_POST['company_id']) . '&team_id=' . urlencode($_POST['team_id']));
        exit;
    }
}

$companies = $companyModel->all();
$teams = $companyId ? $teamModel->allByCompany((int) $companyId) : [];
$rooms = $companyId ? $roomModel->getByCompany((int) $companyId) : [];
$users = $userModel->findById($user['id']) ? [$userModel->findById($user['id'])] : [];

?>
<!doctype html>
<html lang="hu">

<head>
    <meta charset="utf-8">
    <title>Csapatok</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/global-theme.css">
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
        <div class="card p-4">
            <h4>Csapatok</h4>
            <form method="get" class="mb-3 d-flex gap-2 align-items-center">
                <label for="company_id" class="visually-hidden">Cég</label>
                <select id="company_id" name="company_id" class="form-select">
                    <option value="">-- Válassz céget --</option>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($companyId == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary">Mutat</button>
            </form>

            <?php if ($companyId): ?>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h5 class="mb-3">Csapatok</h5>
                        <ul class="list-group mb-4">
                            <?php foreach ($teams as $t): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($t['name']) ?>
                                    <div>
                                        <a href="teams.php?company_id=<?= $companyId ?>&team_id=<?= $t['id'] ?>"
                                            class="btn btn-sm btn-outline-primary">Megnyit</a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <h6>Új csapat</h6>
                        <form method="post" class="d-flex gap-2">
                            <input type="hidden" name="create_team" value="1">
                            <input type="hidden" name="company_id" value="<?= $companyId ?>">
                            <label for="create_team_name" class="visually-hidden">Csapat neve</label>
                            <input id="create_team_name" name="name" class="form-control" placeholder="Csapat neve">
                            <button class="btn btn-success">Hozzáad</button>
                        </form>
                    </div>

                    <div class="col-md-6">
                        <?php if ($teamId):
                            $team = $teamModel->findById((int) $teamId);
                            $members = $teamModel->getMembers((int) $teamId);
                            ?>
                            <div class="card p-3 mb-3 border-secondary">
                                <h5 class="mb-3">Csapat: <?= htmlspecialchars($team['name']) ?></h5>

                                <h6 class="mb-2">Tagok</h6>
                                <ul class="list-group mb-3">
                                    <?php foreach ($members as $m): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= htmlspecialchars($m['email']) ?>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="remove_member" value="1">
                                                <input type="hidden" name="team_id" value="<?= $teamId ?>">
                                                <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
                                                <input type="hidden" name="company_id" value="<?= $companyId ?>">
                                                <button class="btn btn-sm btn-outline-danger">Eltávolít</button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <h6 class="mb-2">Tag hozzáadása</h6>
                                <form method="post" class="d-flex gap-2 mb-4">
                                    <input type="hidden" name="add_member" value="1">
                                    <input type="hidden" name="team_id" value="<?= $teamId ?>">
                                    <input type="hidden" name="company_id" value="<?= $companyId ?>">
                                    <label for="add_member_user" class="visually-hidden">Felhasználó</label>
                                    <select id="add_member_user" name="user_id" class="form-select">
                                        <?php
                                        // Select only workers belonging to this company
                                        $stmtUsers = $db->prepare("
                                            SELECT u.id, u.email, u.first_name, u.last_name 
                                            FROM users u 
                                            JOIN company_user cu ON u.id = cu.user_id 
                                            WHERE cu.company_id = ? AND u.role = 'worker'
                                            ORDER BY u.last_name ASC, u.first_name ASC
                                        ");
                                        $stmtUsers->execute([$companyId]);
                                        $companyWorkers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($companyWorkers as $au):
                                            $displayName = trim(($au['last_name'] ?? '') . ' ' . ($au['first_name'] ?? ''));
                                            if (!$displayName)
                                                $displayName = $au['email'];
                                            else
                                                $displayName .= " ({$au['email']})";
                                            ?>
                                            <option value="<?= $au['id'] ?>"><?= htmlspecialchars($displayName) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-primary">Hozzáad</button>
                                </form>

                                <hr>

                                <h6 class="mb-2">Helyiség hozzárendelés</h6>
                                <form method="post" class="mb-3">
                                    <input type="hidden" name="assign_room" value="1">
                                    <input type="hidden" name="team_id" value="<?= $teamId ?>">
                                    <input type="hidden" name="company_id" value="<?= $companyId ?>">
                                    <div class="mb-2">
                                        <label for="assign_room_id" class="visually-hidden">Helyiség</label>
                                        <select id="assign_room_id" name="room_id" class="form-select">
                                            <?php foreach ($rooms as $r): ?>
                                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label for="assign_info" class="form-label">Info</label>
                                        <input id="assign_info" name="info" class="form-control"
                                            placeholder="Pl. feladatok, megjegyzés">
                                    </div>
                                    <button class="btn btn-success w-100">Hozzárendel</button>
                                </form>

                                <h6 class="mb-2">Aktuális hozzárendelés</h6>
                                <?php foreach ($rooms as $r):
                                    $ass = $teamModel->getAssignment((int) $teamId, (int) $r['id']);
                                    if ($ass): ?>
                                        <div class="card bg-surface p-3 mb-2 shadow-sm">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong><?= htmlspecialchars($r['name']) ?></strong>
                                                    <p class="mb-1 small text-muted"><?= htmlspecialchars($ass['info']) ?></p>
                                                </div>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="remove_assignment" value="1">
                                                    <input type="hidden" name="team_id" value="<?= $teamId ?>">
                                                    <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                                                    <input type="hidden" name="company_id" value="<?= $companyId ?>">
                                                    <button class="btn btn-sm btn-danger py-1 px-2">
                                                        <small>Törlés</small>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endif;
                                endforeach; ?>
                            </div>

                        <?php else: ?>
                            <div class="alert alert-info">Válassz egy csapatot a részletekhez.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted">Válassz egy céget a csapatok kezeléséhez.</p>
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