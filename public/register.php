<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/core/Mailer.php';

$db = (new Database())->getConnection();
$userModel = new User($db);
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Érvénytelen email.";
    if (strlen($password) < 6) $errors[] = "Jelszó min. 6 karakter.";
    if (empty($first) || empty($last)) $errors[] = "Neved kötelező.";

    if (empty($errors)) {
        $token = $userModel->create($email, $password, $first, $last);
        if ($token) {

            $link = rtrim(APP_URL, '/') . '/inventory_backup/public/activate.php?token=' . urlencode($token);
            $body = "Kérjük aktiváld fiókodat: <a href='{$link}'>Aktiválás</a>";
            Mailer::send($email, "Leltár aktiválás", $body);
            $success = "Regisztráció sikeres! Kérjük ellenőrizd az emailed az aktivációs linkért.";
        } else {
            $errors[] = "Az email már foglalt.";
        }
    }
}
?>
<!doctype html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <title>Regisztráció</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="col-md-6 mx-auto card shadow p-4">
        <h3 class="mb-3 text-center">Regisztráció</h3>

        <?php if($success): ?>
            <div class="alert alert-success text-center"><?=htmlspecialchars($success)?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?=htmlspecialchars($e)?></li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">Keresztnév</label>
                    <input id="first_name" name="first_name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Vezetéknév</label>
                    <input id="last_name" name="last_name" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input id="email" name="email" type="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Jelszó</label>
                <input id="password" name="password" type="password" class="form-control" required>
            </div>

            <button class="btn btn-primary w-100">Regisztrálok</button>
        </form>

        <div class="text-center mt-3">
            <a href="login.php">Vissza a bejelentkezéshez</a>
        </div>
    </div>
</div>

</body>
</html>
