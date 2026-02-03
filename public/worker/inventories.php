<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Inventory.php';
require_once __DIR__ . '/../../app/models/Room.php';
require_once __DIR__ . '/../../app/models/Company.php';
require_once __DIR__ . '/../../app/models/Team.php';
require_once __DIR__ . '/../../app/core/Mailer.php';

session_start();
if (empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}
$user = $_SESSION['user'];
// normalize role to ensure checks work even if session was stored with different casing/whitespace
$user['role'] = strtolower(trim($user['role'] ?? ''));
// persist normalized role into session so other includes read the normalized value
$_SESSION['user']['role'] = $user['role'];

$db = (new Database())->getConnection();
$inventoryModel = new Inventory($db);
$roomModel = new Room($db);
$companyModel = new Company($db);
$teamModel = new Team($db);

// simple flow: select company, show inventories, allow create new inventory for company
$companyId = $_GET['company_id'] ?? null;

// Workers can only view/execute inventories, not create/start them
$isEmployerOrAdmin = in_array($user['role'] ?? '', ['employer', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only employers/admins can create and start inventories
    // Only employers/admins can create and start inventories
    // BUT workers can signal finish, so we allow that specific action
    if (!$isEmployerOrAdmin && !isset($_POST['signal_finish'])) {
        echo 'Nincs jogosultság ehhez a művelethez.';
        exit;
    }

    $companyId = $_POST['company_id'] ?? null;
    // create
    if (isset($_POST['create_inventory'])) {
        $name = $_POST['name'] ?? null;
        $startDate = $_POST['start_date'] ?? null;
        $targetType = $_POST['target_type'] ?? 'global';

        if ($companyId && $name) {
            $targets = [];
            if ($targetType === 'team' && !empty($_POST['target_team_id'])) {
                $targets[] = ['type' => 'team', 'id' => (int) $_POST['target_team_id']];
            } elseif ($targetType === 'room' && !empty($_POST['target_room_id'])) {
                $targets[] = ['type' => 'room', 'id' => (int) $_POST['target_room_id']];
            }

            if (!empty($targets)) {
                $invId = $inventoryModel->createWithTargets((int) $companyId, $name, $startDate ?: null, $targets);
            } else {
                $invId = $inventoryModel->create((int) $companyId, $name, 'scheduled', $startDate ?: null);
            }

            // Send email notifications to all workers in this company
            $stmt = $db->prepare("
                SELECT DISTINCT u.email, u.first_name, u.last_name 
                FROM users u
                JOIN company_user cu ON u.id = cu.user_id
                WHERE cu.company_id = ? AND u.role = 'worker' AND u.is_active = 1
            ");
            $stmt->execute([(int) $companyId]);
            $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($workers as $worker) {
                $workerName = trim(($worker['first_name'] ?? '') . ' ' . ($worker['last_name'] ?? '')) ?: $worker['email'];
                $subject = "Új leltár ütemezve: " . $name;
                $body = "
                    <h2>Új leltár ütemezve</h2>
                    <p>Kedves {$workerName}!</p>
                    <p>Egy új leltár lett ütemezve:</p>
                    <ul>
                        <li><strong>Név:</strong> {$name}</li>
                        <li><strong>Kezdés dátuma:</strong> " . ($startDate ?: 'Hamarosan') . "</li>
                    </ul>
                    <p>Kérjük, készüljön fel a leltározásra!</p>
                    <p><a href='" . APP_URL . "/worker/inventories.php?company_id={$companyId}'>Leltárak megtekintése</a></p>
                ";
                Mailer::send($worker['email'], $subject, $body);
            }

            header('Location: inventories.php?company_id=' . urlencode($companyId));
            exit;
        }
    }

    // start existing inventory
    if (isset($_POST['start_inventory'])) {
        $inventoryId = (int) ($_POST['inventory_id'] ?? 0);
        if ($inventoryId) {
            $inventoryModel->setStatus($inventoryId, 'active');

            // Get inventory details
            $stmt = $db->prepare("SELECT * FROM inventories WHERE id = ?");
            $stmt->execute([$inventoryId]);
            $inventory = $stmt->fetch();

            if ($inventory) {
                // Send email notifications to all workers in this company
                $stmt = $db->prepare("
                    SELECT DISTINCT u.email, u.first_name, u.last_name 
                    FROM users u
                    JOIN company_user cu ON u.id = cu.user_id
                    WHERE cu.company_id = ? AND u.role = 'worker' AND u.is_active = 1
                ");
                $stmt->execute([$inventory['company_id']]);
                $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($workers as $worker) {
                    $workerName = trim(($worker['first_name'] ?? '') . ' ' . ($worker['last_name'] ?? '')) ?: $worker['email'];
                    $subject = "Leltár elindult: " . ($inventory['name'] ?? 'Leltár #' . $inventoryId);
                    $body = "
                        <h2>Leltár elindult!</h2>
                        <p>Kedves {$workerName}!</p>
                        <p>A következő leltár most elindult:</p>
                        <ul>
                            <li><strong>Név:</strong> " . ($inventory['name'] ?? 'Leltár #' . $inventoryId) . "</li>
                            <li><strong>Kezdés:</strong> " . date('Y-m-d H:i') . "</li>
                        </ul>
                        <p>Kérjük, kezdje el a leltározást!</p>
                        <p><a href='" . APP_URL . "/worker/inventories.php?company_id={$inventory['company_id']}'>Leltározás kezdése</a></p>
                    ";
                    Mailer::send($worker['email'], $subject, $body);
                }
            }
        }
        header('Location: inventories.php?company_id=' . urlencode($companyId));
        exit;
    }

    // finish existing inventory
    if (isset($_POST['finish_inventory'])) {
        $inventoryId = (int) ($_POST['inventory_id'] ?? 0);
        if ($inventoryId) {
            $inventoryModel->finish($inventoryId);
            header('Location: inventory_summary.php?inventory_id=' . $inventoryId . '&company_id=' . urlencode($companyId));
            exit;
        }
    }


    // Worker signals finish
    if (isset($_POST['signal_finish'])) {
        $inventoryId = (int) ($_POST['inventory_id'] ?? 0);
        if ($inventoryId && !$isEmployerOrAdmin) {
            // Fetch actual items recorded by this user (or all items for this inventory if shared)
            // Ideally we only want items this user touched or responsible for. 
            // For now, let's fetch all items recorded in this inventory as a snapshot.
            $stmt = $db->prepare("SELECT item_id, is_present, note FROM inventory_items WHERE inventory_id = ?");
            $stmt->execute([$inventoryId]);
            $recordedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format items for payload
            $itemsPayload = [];
            foreach ($recordedItems as $rec) {
                $itemsPayload[] = [
                    'item_id' => $rec['item_id'],
                    'is_present' => (bool) $rec['is_present'],
                    'note' => $rec['note'] ?? ''
                ];
            }

            $payload = json_encode([
                'action' => 'finish_signal',
                'message' => 'A munkavállaló jelezte, hogy végzett a leltározással.',
                'timestamp' => date('Y-m-d H:i:s'),
                'items' => $itemsPayload,
                'stats' => [ // Add stats for quick view
                    'total' => count($itemsPayload),
                    'found' => count(array_filter($itemsPayload, fn($i) => $i['is_present'])),
                    'missing' => count(array_filter($itemsPayload, fn($i) => !$i['is_present']))
                ]
            ], JSON_UNESCAPED_UNICODE);

            $inventoryModel->createSubmission($inventoryId, $user['id'], $payload, 'pending');

            $_SESSION['flash_message'] = 'Sikeresen jelezted a leltár befejezését!';
            $_SESSION['flash_type'] = 'success';

            header('Location: inventories.php?company_id=' . urlencode($companyId) . '&inventory_id=' . $inventoryId);
            exit;
        }
    }
}

$companies = $companyModel->all();

// For workers, get all assigned companies
$workerCompanies = [];
if (!$isEmployerOrAdmin) {
    $stmt = $db->prepare("SELECT c.* FROM companies c JOIN company_user cu ON c.id = cu.company_id WHERE cu.user_id = ? ORDER BY c.name");
    $stmt->execute([(int) $user['id']]);
    $workerCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If companyId is not set, or invalid for this worker, default to first available
    $isValid = false;
    if ($companyId) {
        foreach ($workerCompanies as $wc) {
            if ($wc['id'] == $companyId) {
                $isValid = true;
                break;
            }
        }
    }

    if ((!$companyId || !$isValid) && !empty($workerCompanies)) {
        $companyId = $workerCompanies[0]['id'];
    }
}

$inventories = $companyId ? $inventoryModel->getByCompany((int) $companyId) : [];

// Filter out finished inventories from the main list
if (!empty($inventories)) {
    $inventories = array_filter($inventories, function ($inv) {
        return $inv['status'] !== 'finished';
    });
}
// For workers, filter to show only active inventories (already covered by keeping active logic if needed, but above filter applies globally to view)
if (!$isEmployerOrAdmin && !empty($inventories)) {
    $inventories = array_filter($inventories, function ($inv) {
        return in_array($inv['status'], ['active', 'running']);
    });
}

// Get company name for display
$companyName = '';
if ($companyId) {
    $companyData = $companyModel->findById((int) $companyId);
    $companyName = $companyData['name'] ?? '';
}

$rooms = $companyId ? $roomModel->getByCompany((int) $companyId) : [];
$teams = $companyId ? $teamModel->allByCompany((int) $companyId) : [];

// Determine active inventory Logic
$activeInventory = null;
if ($companyId && !empty($inventories)) {
    $activeInventories = array_filter($inventories, function ($inv) {
        return in_array($inv['status'], ['active', 'running']);
    });

    $selectedInventoryId = $_GET['inventory_id'] ?? null;

    if ($selectedInventoryId) {
        foreach ($activeInventories as $inv) {
            if ($inv['id'] == $selectedInventoryId) {
                $activeInventory = $inv;
                break;
            }
        }
    } elseif (count($activeInventories) === 1) {
        // Only one active? Auto-select it.
        $activeInventory = reset($activeInventories);
    }
}
?>
<!doctype html>
<html lang="hu">

<head>
    <meta charset="utf-8">
    <title>Leltárak</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/global-theme.css">
    <link rel="stylesheet" href="../css/inventories.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
</head>

<body>
    <?php include_once __DIR__ . '/dashboard_nav.php'; ?>
    <div class="page-container">
        <div class="col-md-9 mx-auto">
            <div class="card p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Leltárak</h4>
                    <?php if ($companyId): ?>
                        <a href="inventory_archive.php?company_id=<?= $companyId ?>" class="btn btn-secondary btn-sm">
                            <i class="bi bi-archive"></i> Archívum
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show">
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                    ?>
                <?php endif; ?>

                <?php if (!$isEmployerOrAdmin): ?>
                    <!-- Worker: Display assigned company or selector -->
                    <?php if (count($workerCompanies) > 1): ?>
                        <form method="get" class="mb-3 d-flex align-items-center gap-2 alert alert-secondary p-2">
                            <i class="bi bi-building"></i>
                            <label for="company_id" class="form-label mb-0 fw-bold">Válassz céget:</label>
                            <select id="company_id" name="company_id" class="form-select form-select-sm w-auto"
                                onchange="this.form.submit()">
                                <?php foreach ($workerCompanies as $wc): ?>
                                    <option value="<?= $wc['id'] ?>" <?= ($companyId == $wc['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($wc['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php elseif ($companyId && $companyName): ?>
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-building"></i> <strong>Hozzárendelt cég:</strong>
                            <?= htmlspecialchars($companyName) ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-triangle"></i> Még nincs céghez rendelve. Kérj meg egy munkáltatót, hogy
                            rendeljen hozzá!
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Employer/Admin: Show company selector -->
                    <form method="get" class="mb-3 d-flex gap-2 align-items-center">
                        <label for="company_id" class="visually-hidden">Cég</label>
                        <select id="company_id" name="company_id" class="form-select">
                            <option value="">-- Válassz céget --</option>
                            <?php foreach ($companies as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($companyId == $c['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-primary">Mutat</button>
                    </form>
                <?php endif; ?>

                <?php if ($companyId): ?>
                    <h5><?= $isEmployerOrAdmin ? 'Aktuális leltárak' : 'Aktív leltárak' ?></h5>
                    <?php if (empty($inventories)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <?= $isEmployerOrAdmin ? 'Még nincs leltár ehhez a céghez.' : 'Jelenleg nincs aktív leltár.' ?>
                        </div>
                    <?php else: ?>
                        <ul class="list-group mb-3">
                            <?php foreach ($inventories as $inv): ?>
                                <li
                                    class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                    <div class="w-100">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong><?= htmlspecialchars($inv['name'] ?? 'Leltár #' . $inv['id']) ?></strong>
                                            <span
                                                class="badge bg-light text-dark border d-md-none"><?= htmlspecialchars($inv['status']) ?></span>
                                        </div>
                                        <div class="small text-muted d-none d-md-block">Státusz:
                                            <?= htmlspecialchars($inv['status']) ?>
                                        </div>
                                        <?php if ($inv['start_date']): ?>
                                            <div class="small text-muted"><i class="bi bi-calendar"></i> Kezdés:
                                                <?= htmlspecialchars($inv['start_date']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="w-100 w-md-auto d-flex flex-column flex-md-row gap-2 align-items-center">
                                        <?php if ($inv['status'] === 'scheduled' && $isEmployerOrAdmin): ?>
                                            <form method="post" class="d-md-inline-block w-100">
                                                <input type="hidden" name="company_id" value="<?= htmlspecialchars($companyId) ?>">
                                                <input type="hidden" name="inventory_id" value="<?= $inv['id'] ?>">
                                                <button name="start_inventory"
                                                    class="btn btn-sm btn-success w-100 w-md-auto text-nowrap">Indítás</button>
                                            </form>
                                        <?php elseif ($inv['status'] === 'scheduled'): ?>
                                            <span class="badge bg-warning text-dark w-100 d-md-inline-block">Ütemezett</span>
                                        <?php elseif ($inv['status'] === 'running' || $inv['status'] === 'active'): ?>
                                            <span class="badge bg-success me-2 d-none d-md-inline-block">Aktív</span>

                                            <?php if (!$isEmployerOrAdmin): ?>
                                                <?php
                                                $isSelected = ($activeInventory['id'] ?? 0) == $inv['id'];
                                                $submission = $inventoryModel->getUserSubmission($inv['id'], $user['id']);
                                                $isPending = $submission && $submission['status'] === 'pending';
                                                $isAccepted = $submission && $submission['status'] === 'accepted';
                                                $isRejected = $submission && $submission['status'] === 'rejected';
                                                ?>

                                                <?php if (!$isPending && !$isAccepted): ?>
                                                    <a href="inventories.php?company_id=<?= $companyId ?>&inventory_id=<?= $inv['id'] ?>"
                                                        class="btn btn-sm <?= $isSelected ? 'btn-success' : 'btn-outline-primary' ?> w-100 w-md-auto text-center text-nowrap">
                                                        <?= $isSelected ? '<i class="bi bi-check-lg"></i> Kiválasztva' : 'Kiválasztás' ?>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($isSelected): ?>
                                                    <?php if ($isPending): ?>
                                                        <span class="badge bg-warning text-dark border p-2"><i class="bi bi-hourglass-split"></i>
                                                            Várakozás elfogadásra</span>
                                                    <?php elseif ($isAccepted): ?>
                                                        <span class="badge bg-success p-2"><i class="bi bi-check-circle-fill"></i> Elfogadva</span>
                                                    <?php elseif ($isRejected): ?>
                                                        <span class="badge bg-danger p-2"><i class="bi bi-x-circle"></i> Elutasítva:
                                                            <?= htmlspecialchars($submission['response_message'] ?? '') ?></span>

                                                        <!-- Allow resubmission if rejected -->
                                                        <form method="post" class="d-md-inline-block w-100"
                                                            onsubmit="return confirm('Biztosan jelzed újra, hogy végeztél?');">
                                                            <input type="hidden" name="company_id" value="<?= htmlspecialchars($companyId) ?>">
                                                            <input type="hidden" name="inventory_id" value="<?= $inv['id'] ?>">
                                                            <button name="signal_finish"
                                                                class="btn btn-sm btn-outline-success w-100 w-md-auto text-nowrap">
                                                                🏁 Újra kész
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="post" class="d-md-inline-block w-100"
                                                            onsubmit="return confirm('Biztosan jelzed, hogy végeztél?');">
                                                            <input type="hidden" name="company_id" value="<?= htmlspecialchars($companyId) ?>">
                                                            <input type="hidden" name="inventory_id" value="<?= $inv['id'] ?>">
                                                            <button name="signal_finish"
                                                                class="btn btn-sm btn-outline-success w-100 w-md-auto text-nowrap">
                                                                🏁 Kész
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <?php if ($isEmployerOrAdmin): ?>
                                                <form method="post" class="d-md-inline-block w-100">
                                                    <input type="hidden" name="company_id" value="<?= htmlspecialchars($companyId) ?>">
                                                    <input type="hidden" name="inventory_id" value="<?= $inv['id'] ?>">
                                                    <button name="finish_inventory"
                                                        class="btn btn-sm btn-primary w-100 w-md-auto text-nowrap">Befejezés</button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="inventory_summary.php?inventory_id=<?= $inv['id'] ?>&company_id=<?= $companyId ?>"
                                                class="btn btn-sm btn-outline-info w-100 w-md-auto text-center text-nowrap">Előnézet</a>
                                        <?php elseif ($inv['status'] === 'finished'): ?>
                                            <span class="badge bg-secondary me-2 d-none d-md-inline-block">Befejezve</span>
                                            <a href="inventory_summary.php?inventory_id=<?= $inv['id'] ?>&company_id=<?= $companyId ?>"
                                                class="btn btn-sm btn-outline-primary w-100 w-md-auto text-center">Összegzés</a>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if ($isEmployerOrAdmin): ?>
                        <h5>Új leltár indítása</h5>
                        <form method="post" class="mb-3">
                            <input type="hidden" name="company_id" value="<?= htmlspecialchars($companyId) ?>">
                            <div class="mb-3">
                                <label for="inv_name" class="form-label">Leltár neve</label>
                                <input id="inv_name" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Kezdés dátuma (opcionális)</label>
                                <input id="start_date" type="text" name="start_date" class="form-control"
                                    placeholder="Válassz dátumot és időt...">
                            </div>

                            <hr>
                            <h6>Hatókör (opcionális)</h6>
                            <div class="mb-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="target_type" id="scope_global"
                                        value="global" checked onclick="toggleScope('global')">
                                    <label class="form-check-label" for="scope_global">Teljes cég</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="target_type" id="scope_team" value="team"
                                        onclick="toggleScope('team')">
                                    <label class="form-check-label" for="scope_team">Csapat</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="target_type" id="scope_room" value="room"
                                        onclick="toggleScope('room')">
                                    <label class="form-check-label" for="scope_room">Helyiség</label>
                                </div>
                            </div>

                            <div class="mb-3 d-none" id="selector_team">
                                <label for="target_team_id" class="form-label">Válassz csapatot</label>
                                <select name="target_team_id" id="target_team_id" class="form-select">
                                    <option value="">-- Válassz --</option>
                                    <?php foreach ($teams as $t): ?>
                                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3 d-none" id="selector_room">
                                <label for="target_room_id" class="form-label">Válassz helyiséget</label>
                                <select name="target_room_id" id="target_room_id" class="form-select">
                                    <option value="">-- Válassz --</option>
                                    <?php foreach ($rooms as $r): ?>
                                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
                            <script src="https://npmcdn.com/flatpickr/dist/l10n/hu.js"></script>
                            <script>
                                function toggleScope(scope) {
                                    document.getElementById('selector_team').classList.add('d-none');
                                    document.getElementById('selector_room').classList.add('d-none');
                                    if (scope === 'team') document.getElementById('selector_team').classList.remove('d-none');
                                    if (scope === 'room') document.getElementById('selector_room').classList.remove('d-none');
                                }

                                flatpickr("#start_date", {
                                    enableTime: true,
                                    dateFormat: "Y-m-d H:i",
                                    time_24hr: true,
                                    locale: "hu",
                                    altInput: true,
                                    altFormat: "Y. F j. H:i",
                                    placeholder: "Válassz időpontot"
                                });
                            </script>

                            <button name="create_inventory" class="btn btn-success">Leltár indítása</button>
                        </form>
                    <?php endif; ?>

                    <h5>Helyiségek</h5>
                    <?php
                    // Active inventory logic moved to top
                    ?>
                    <?php if ($activeInventory): ?>
                        <div class="alert alert-info mb-3">
                            Aktív leltár: <strong><?= htmlspecialchars($activeInventory['name']) ?></strong>
                        </div>
                        <ul class="list-group">
                            <?php foreach ($rooms as $r): ?>
                                <li
                                    class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                                    <div class="fw-bold mb-1 mb-md-0">
                                        <i class="bi bi-door-open text-muted"></i> <?= htmlspecialchars($r['name']) ?>
                                    </div>
                                    <?php
                                    // Check submission status for active inventory again (variable scope might differ if not carefully handled, but $submission logic was inside loop above. Let's re-fetch or assume activeInventory context)
                                    // Better: pass the status down. But here we are in room loop.
                                    // Use simple Re-fetch for safety or check variable if available.
                                    // Since we only show this for activeInventory, we can fetch once.
                                    $actSubmission = $inventoryModel->getUserSubmission($activeInventory['id'], $user['id']);
                                    $actIsPending = $actSubmission && $actSubmission['status'] === 'pending';
                                    $actIsAccepted = $actSubmission && $actSubmission['status'] === 'accepted';
                                    ?>

                                    <?php if ($actIsPending): ?>
                                        <span class="badge bg-secondary">Zárolva (Beküldve)</span>
                                    <?php elseif ($actIsAccepted): ?>
                                        <span class="badge bg-success">Lezárva</span>
                                    <?php else: ?>
                                        <a href="inventory_perform.php?company_id=<?= $companyId ?>&room_id=<?= $r['id'] ?>&inventory_id=<?= $activeInventory['id'] ?>"
                                            class="btn btn-sm btn-primary w-100 w-md-auto text-center">Leltározás</a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            Nincs aktív leltár. Kérjük először indítson vagy ütemezzen egy leltárt.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-arrow-up"></i> Válassz egy céget a fenti listából a leltárak megjelenítéséhez.
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>

</html>