<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/DeviceLog.php';

session_start();
if (empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}
$user = $_SESSION['user'];
// only allow admin
if (($user['role'] ?? '') !== 'admin') {
    echo 'Nincs jogosultság.';
    exit;
}

$db = (new Database())->getConnection();
$deviceLogModel = new DeviceLog($db);

// Fetch last 100 logs
$logs = $deviceLogModel->getAll(100);
?>
<!doctype html>
<html lang="hu">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Eszköz Napló</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>

<body class="bg-light">
    <?php include_once __DIR__ . '/dashboard_nav.php'; ?>

    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h4 class="mb-0"><i class="bi bi-phone"></i> Eszköz Bejelentkezési Napló</h4>
                <span class="badge bg-secondary">Utolsó 100 bejegyzés</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Időpont</th>
                                <th>Felhasználó</th>
                                <th>Eszköz</th>
                                <th class="d-none d-md-table-cell">Böngésző</th>
                                <th>IP Cím</th>
                                <th class="d-none d-md-table-cell">Hely (ISP)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="text-nowrap">
                                        <?= htmlspecialchars($log['created_at']) ?>
                                    </td>
                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($log['last_name'] . ' ' . $log['first_name']) ?>
                                        </strong><br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($log['email']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php
                                        $icon = 'bi-question-circle';
                                        if ($log['device_type'] == 'mobile')
                                            $icon = 'bi-phone';
                                        elseif ($log['device_type'] == 'desktop')
                                            $icon = 'bi-pc-display';
                                        elseif ($log['device_type'] == 'tablet')
                                            $icon = 'bi-tablet';
                                        ?>
                                        <i class="bi <?= $icon ?>"></i>
                                        <?= htmlspecialchars($log['os'] ?: 'Ismeretlen') ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?= htmlspecialchars($log['browser'] ?: '-') ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($log['ip_address']) ?></code></td>
                                    <td class="d-none d-md-table-cell">
                                        <?php if ($log['country']): ?>
                                            <?= htmlspecialchars($log['country']) ?>,
                                            <?= htmlspecialchars($log['city']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>

</html>