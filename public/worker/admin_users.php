<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/User.php';

session_start();
if (empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}
$user = $_SESSION['user'];
$user['role'] = strtolower(trim($user['role'] ?? ''));
// Only admin can access
if ($user['role'] !== 'admin') {
    echo 'Nincs jogosultság. Csak adminisztrátor használhatja.';
    exit;
}

$db = (new Database())->getConnection();
$userModel = new User($db);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['block_user'])) {
        $userId = (int) $_POST['user_id'];
        if ($userModel->block($userId)) {
            $message = 'Felhasználó sikeresen letiltva.';
            $messageType = 'success';
        } else {
            $message = 'Hiba történt a tiltás során.';
            $messageType = 'danger';
        }
    }
    if (isset($_POST['unblock_user'])) {
        $userId = (int) $_POST['user_id'];
        if ($userModel->unblock($userId)) {
            $message = 'Felhasználó sikeresen engedélyezve.';
            $messageType = 'success';
        } else {
            $message = 'Hiba történt az engedélyezés során.';
            $messageType = 'danger';
        }
    }
    if (isset($_POST['change_role'])) {
        $userId = (int) $_POST['user_id'];
        $newRole = $_POST['new_role'];
        if (in_array($newRole, ['worker', 'employer', 'admin'])) {
            if ($userModel->updateRole($userId, $newRole)) {
                $message = 'Felhasználó szerepköre sikeresen módosítva.';
                $messageType = 'success';
            } else {
                $message = 'Hiba történt a szerepkör módosítása során.';
                $messageType = 'danger';
            }
        }
    }
}

$users = $userModel->all();
?>
<!doctype html>
<html lang="hu">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Felhasználók Kezelése - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .user-card {
            transition: box-shadow 0.2s;
        }

        .user-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            font-size: 0.75rem;
        }
    </style>
    <link rel="stylesheet" href="../css/global-theme.css">
</head>

<body>
    <?php include_once __DIR__ . '/dashboard_nav.php'; ?>

    <div class="page-container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h2><i class="bi bi-people-fill"></i> Felhasználók Kezelése</h2>
                    <span class="badge bg-secondary">Összesen: <?= count($users) ?> felhasználó</span>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="usersTable" class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Név</th>
                                        <th>Email</th>
                                        <th>Telefon</th>
                                        <th>Szerepkör</th>
                                        <th>Státusz</th>
                                        <th>Regisztráció</th>
                                        <th>Műveletek</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr class="<?= $u['is_blocked'] ? 'table-danger' : '' ?>">
                                            <td><?= $u['id'] ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($u['email']) ?></td>
                                            <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                                            <td>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="change_role" value="1">
                                                    <select name="new_role" class="form-select form-select-sm"
                                                        style="width: auto; display: inline-block;"
                                                        onchange="if(confirm('Biztosan módosítod a szerepkört?')) this.form.submit();">
                                                        <option value="worker" <?= $u['role'] === 'worker' ? 'selected' : '' ?>>Munkás</option>
                                                        <option value="employer" <?= $u['role'] === 'employer' ? 'selected' : '' ?>>Munkáltató</option>
                                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>
                                                            Admin</option>
                                                    </select>
                                                    <button type="submit" name="change_role"
                                                        class="btn btn-sm btn-outline-primary d-none">Mentés</button>
                                                </form>
                                            </td>
                                            <td>
                                                <?php if (!$u['is_active']): ?>
                                                    <span class="badge bg-warning text-dark status-badge">Inaktív</span>
                                                <?php elseif ($u['is_blocked']): ?>
                                                    <span class="badge bg-danger status-badge">Letiltva</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success status-badge">Aktív</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
                                            <td>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <?php if ($u['is_blocked']): ?>
                                                        <button type="submit" name="unblock_user" class="btn btn-sm btn-success"
                                                            onclick="return confirm('Biztosan engedélyezed ezt a felhasználót?')">
                                                            <i class="bi bi-check-circle"></i> Engedélyez
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" name="block_user" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Biztosan letiltod ezt a felhasználót?')">
                                                            <i class="bi bi-x-circle"></i> Letilt
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="card-title">Munkások</h5>
                                <h2 class="text-primary">
                                    <?= count(array_filter($users, fn($u) => $u['role'] === 'worker')) ?>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="card-title">Munkáltatók</h5>
                                <h2 class="text-info">
                                    <?= count(array_filter($users, fn($u) => $u['role'] === 'employer')) ?>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="card-title">Adminok</h5>
                                <h2 class="text-success">
                                    <?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?>
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery & DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#usersTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/hu.json'
                },
                columnDefs: [
                    { orderable: false, targets: -1 } // Disable sorting on the Actions column (last one)
                ]
            });
        });
    </script>
</body>

</html>