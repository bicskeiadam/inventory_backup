<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/User.php';

session_start();
if (empty($_SESSION['user'])) { header('Location: ../login.php'); exit; }
$userSession = $_SESSION['user'];

$db = (new Database())->getConnection();
$userModel = new User($db);
$user = $userModel->findById((int)$userSession['id']);

$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $first = trim($_POST['first_name'] ?? '');
        $last = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($userModel->updateProfile($user['id'], $first, $last, $phone)) {
            $messages[] = ['type'=>'success','text'=>'Profil frissítve.'];
            // update session display name
            $_SESSION['user']['email'] = $user['email'];
        } else {
            $messages[] = ['type'=>'danger','text'=>'Hiba történt a frissítés során.'];
        }
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        // verify current
        if (!password_verify($current, $user['password'])) {
            $messages[] = ['type'=>'danger','text'=>'A jelenlegi jelszó helytelen.'];
        } elseif ($new !== $confirm) {
            $messages[] = ['type'=>'danger','text'=>'Az új jelszavak nem egyeznek.'];
        } else {
            if ($userModel->changePassword($user['id'], $new)) {
                $messages[] = ['type'=>'success','text'=>'Jelszó megváltoztatva.'];
            } else {
                $messages[] = ['type'=>'danger','text'=>'Hiba történt a jelszó módosításakor.'];
            }
        }
    }
    // reload user
    $user = $userModel->findById((int)$userSession['id']);
}

?>
<!doctype html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <title>Profil</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/global-theme.css">
</head>
<body>
<?php include_once __DIR__ . '/dashboard_nav.php'; ?>
<div class="page-container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <h4 class="mb-4">Profil beállítások</h4>
            
            <?php foreach ($messages as $m): ?>
                <div class="alert alert-<?=htmlspecialchars($m['type'])?> mb-4"><?=htmlspecialchars($m['text'])?></div>
            <?php endforeach; ?>

            <!-- Personal Info -->
            <div class="card p-4 mb-4">
                <h5 class="mb-3">Személyes adatok</h5>
                <form method="post">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">Keresztnév</label>
                            <input id="first_name" name="first_name" class="form-control" value="<?=htmlspecialchars($user['first_name'] ?? '')?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Vezetéknév</label>
                            <input id="last_name" name="last_name" class="form-control" value="<?=htmlspecialchars($user['last_name'] ?? '')?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Telefonszám</label>
                        <input id="phone" name="phone" class="form-control" value="<?=htmlspecialchars($user['phone'] ?? '')?>">
                    </div>
                    <button class="btn btn-primary">Mentés</button>
                </form>
            </div>

            <!-- Password -->
            <div class="card p-4 mb-4">
                <h5 class="mb-3">Jelszó módosítása</h5>
                <form method="post">
                    <input type="hidden" name="change_password" value="1">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Jelenlegi jelszó</label>
                        <input id="current_password" type="password" name="current_password" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Új jelszó</label>
                        <input id="new_password" type="password" name="new_password" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Új jelszó újra</label>
                        <input id="confirm_password" type="password" name="confirm_password" class="form-control">
                    </div>
                     <button class="btn btn-warning">Jelszó módosítása</button>
                 </form>
             </div>
             
             <!-- Appearance -->
             <div class="card p-4">
                <h5 class="mb-3">Megjelenés</h5>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Sötét mód</strong>
                        <p class="text-muted small mb-0">Váltás a világos és sötét téma között</p>
                    </div>
                    <button class="btn btn-outline-primary" onclick="toggleTheme()">
                        Téma váltása <span id="profile-theme-icon">🌓</span>
                    </button>
                </div>
             </div>
         </div>
     </div>
 </div>
 </body>
 </html>
