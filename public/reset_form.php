<?php
// reset_form.php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : (isset($_POST['token']) ? trim($_POST['token']) : "");

$rf = isset($_GET['rf']) ? intval($_GET['rf']) : null;

// üzenetek
$messages = [
    7  => "A két jelszó nem egyezik!",
    8  => "Az e-mail mező üres.",
    9  => "A jelszó mező üres.",
    10 => "A jelszónak tartalmaznia kell kis/nagy betűt, számot és speciális karaktert.",
    15 => "Érvénytelen vagy lejárt visszaállító link.",
];
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Jelszó visszaállítása</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="col-md-5 mx-auto card shadow p-4">
        <h3 class="mb-3 text-center">Jelszó visszaállítása</h3>

        <?php if ($rf && isset($messages[$rf])): ?>
            <div class="alert alert-danger text-center">
                <?= htmlspecialchars($messages[$rf]) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="reset_password.php">

            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="mb-3">
                <label for="resetEmail" class="form-label">E-mail cím</label>
                <input id="resetEmail" type="email" name="resetEmail" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="resetPassword" class="form-label">Új jelszó</label>
                <input id="resetPassword" type="password" name="resetPassword" class="form-control" required>
                <small class="text-muted">8-20 karakter, kisbetű, nagybetű, szám és speciális karakter kötelező.</small>
            </div>

            <div class="mb-3">
                <label for="resetPasswordConfirm" class="form-label">Jelszó újra</label>
                <input id="resetPasswordConfirm" type="password" name="resetPasswordConfirm" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-success w-100">Jelszó módosítása</button>

            <div class="text-center mt-3">
                <a href="login.php">Vissza a bejelentkezéshez</a>
            </div>

        </form>

    </div>
</div>

</body>
</html>
