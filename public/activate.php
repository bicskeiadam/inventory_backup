<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/User.php';

$db = (new Database())->getConnection();
$userModel = new User($db);

$token = $_GET['token'] ?? null;
$success = false;
if ($token && $userModel->activate($token)) {
    $success = true;
}
?>
<!doctype html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <title>Fiók aktiválás</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-6 mx-auto card shadow p-4 text-center">
        <?php if ($success): ?>
            <h3 class="text-success">Sikeres aktiválás</h3>
            <p><a href="login.php">Bejelentkezés</a></p>
        <?php else: ?>
            <h3 class="text-danger">Hiba</h3>
            <p>Hibás vagy lejárt token.</p>
            <p><a href="register.php">Regisztráció</a> | <a href="reset_request_form.php">Jelszó visszaállítás</a></p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
