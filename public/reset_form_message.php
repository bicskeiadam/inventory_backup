<?php
// reset_form_message.php
require_once __DIR__ . '/../app/config/config.php';

$rf = isset($_GET['rf']) ? intval($_GET['rf']) : null;

$messages = [
    0  => "Érvénytelen vagy hiányzó token.",
    12 => "A jelszó-visszaállító e-mail elküldve!",
    13 => "A fiók inaktív.",
    14 => "Ilyen e-mail cím nem található.",
    15 => "A token érvénytelen vagy lejárt.",
    16 => "A jelszó sikeresen megváltoztatva!",
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
    <div class="col-md-6 mx-auto card shadow p-4 text-center">

        <?php if (isset($messages[$rf])): ?>
            <h4><?= htmlspecialchars($messages[$rf]) ?></h4>
        <?php else: ?>
            <h4>Ismeretlen üzenet.</h4>
        <?php endif; ?>

        <a href="login.php" class="btn btn-primary mt-3">Vissza a bejelentkezéshez</a>
    </div>
</div>

</body>
</html>
