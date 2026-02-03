<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Company.php';
require_once __DIR__ . '/../../app/models/User.php';

session_start();
if (empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}
$user = $_SESSION['user'];
$user['role'] = strtolower(trim($user['role'] ?? ''));
// only allow employers/admins
if (!in_array($user['role'] ?? '', ['employer', 'admin'])) {
    echo 'Nincs jogosultság.';
    exit;
}

$db = (new Database())->getConnection();
$companyModel = new Company($db);

$action = $_GET['action'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_name'])) {
        $companyModel->create(trim($_POST['create_name']));
        header('Location: companies.php');
        exit;
    }
    if (isset($_POST['edit_id']) && isset($_POST['edit_name'])) {
        $companyModel->update((int) $_POST['edit_id'], trim($_POST['edit_name']));
        header('Location: companies.php');
        exit;
    }
}

$companies = $companyModel->all();
?>
<!doctype html>
<html lang="hu">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vállalatok</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">
    <?php include_once __DIR__ . '/dashboard_nav.php'; ?>

    <div class="container mt-4">
        <div class="col-md-8 mx-auto">
            <div class="card p-3">
                <h4>Vállalatok</h4>
                <ul class="list-group mb-3">
                    <?php foreach ($companies as $c): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <?= htmlspecialchars($c['name']) ?>
                            <div>
                                <a href="companies.php?action=edit&id=<?= $c['id'] ?>"
                                    class="btn btn-sm btn-outline-primary">Szerkeszt</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($action === 'edit' && isset($_GET['id'])):
                    $comp = $companyModel->findById((int) $_GET['id']);
                    ?>
                    <form method="post">
                        <input type="hidden" name="edit_id" value="<?= htmlspecialchars($comp['id']) ?>">
                        <div class="mb-3">
                            <label class="form-label">Név</label>
                            <input name="edit_name" class="form-control" value="<?= htmlspecialchars($comp['name']) ?>">
                        </div>
                        <button class="btn btn-primary">Mentés</button>
                        <a href="companies.php" class="btn btn-link">Mégsem</a>
                    </form>
                <?php else: ?>
                    <form method="post" class="d-flex gap-2">
                        <label for="create_name" class="visually-hidden">Új cég neve</label>
                        <input id="create_name" name="create_name" class="form-control" placeholder="Új cég neve">
                        <button class="btn btn-success">Hozzáad</button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

</body>

</html>