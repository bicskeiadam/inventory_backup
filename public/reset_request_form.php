<?php
// reset_request_form.php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';

$rf = isset($_GET['rf']) ? intval($_GET['rf']) : null;

// üzenet reset email elküldéshez
$messages = [
    8  => "Kérjük, add meg az e-mail címedet!",
    12 => "A jelszó-visszaállító linket elküldtük az e-mail címedre!",
    13 => "A fiók inaktív, nem lehet jelszót visszaállítani.",
    14 => "A megadott e-mail cím nem létezik.",
    15 => "Hiba történt a folyamat közben.",
];
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Elfelejtett jelszó</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="col-md-5 mx-auto card shadow p-4">
        <h3 class="mb-3 text-center">Elfelejtett jelszó</h3>

        <?php if ($rf && isset($messages[$rf])): ?>
            <div class="alert alert-info text-center">
                <?= htmlspecialchars($messages[$rf]) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="reset_request.php">
            <div class="mb-3">
                <label for="resetEmail" class="form-label">E-mail cím</label>
                <input id="resetEmail" type="email" name="resetEmail" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Jelszó visszaállítás kérése</button>

            <div class="text-center mt-3">
                <a href="login.php">Vissza a bejelentkezéshez</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
