<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/DeviceLog.php';
session_start();

$db = (new Database())->getConnection();
$userModel = new User($db);
$deviceLog = new DeviceLog($db);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    $user = $userModel->verifyCredentials($email, $pass);
    if ($user) {
        // normalize role to avoid subtle mismatches (trim + lowercase)
        $normalizedRole = strtolower(trim($user['role'] ?? 'worker'));
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $normalizedRole,
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? ''
        ];
        // log device
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $deviceLog->log($user['id'], $ua);
        // Redirect based on role (if you have a separate admin dashboard, put it here)
        $role = $normalizedRole ?? 'worker';
        if ($role === 'admin') {
            header('Location: ./worker/dashboard.php'); // change to admin/dashboard.php if you create one
        } else {
            header('Location: ./worker/dashboard.php');
        }
        exit;
    } else {
        $error = "Hibás adatok vagy fiók nem aktív.";
    }
}
?>
<!doctype html>
<html lang="hu">

<head>
    <meta charset="utf-8">
    <title>Bejelentkezés</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">

    <div class="container mt-5">
        <div class="col-md-5 mx-auto card shadow p-4">
            <h3 class="mb-3 text-center">Bejelentkezés</h3>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail cím</label>
                    <input id="email" name="email" type="email" class="form-control" required placeholder="Email">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Jelszó</label>
                    <input id="password" name="password" type="password" class="form-control" required
                        placeholder="Jelszó">
                </div>

                <button class="btn btn-primary w-100">Bejelentkezés</button>
            </form>

            <div class="text-center mt-3">
                <a href="register.php">Regisztráció</a> | <a href="reset_request_form.php">Elfelejtett jelszó</a>
            </div>
        </div>
    </div>

</body>

</html>