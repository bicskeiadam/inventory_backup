<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Item.php';
require_once __DIR__ . '/../../app/models/Room.php';
require_once __DIR__ . '/../../app/models/Inventory.php';
require_once __DIR__ . '/../../app/models/InventoryItem.php';

session_start();
if (empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}
$user = $_SESSION['user'];

$inventoryId = $_GET['inventory_id'] ?? null;
$roomId = $_GET['room_id'] ?? null;
$companyId = $_GET['company_id'] ?? null;

$db = (new Database())->getConnection();
$itemModel = new Item($db);
$roomModel = new Room($db);
$inventoryModel = new Inventory($db);
$inventoryItemModel = new InventoryItem($db);

if (!$roomId || !$inventoryId) {
    header('Location: inventories.php');
    exit;
}

$items = $itemModel->getByRoom((int) $roomId);
$room = $roomModel->findById((int) $roomId);

// Get inventory details
$stmt = $db->prepare("SELECT * FROM inventories WHERE id = ?");
$stmt->execute([(int) $inventoryId]);
$inventory = $stmt->fetch();

if (!$inventory) {
    header('Location: inventories.php');
    exit;
}

// Get already recorded items for this inventory and room
$stmt = $db->prepare("
    SELECT ii.*, i.name as item_name 
    FROM inventory_items ii 
    JOIN items i ON ii.item_id = i.id 
    WHERE ii.inventory_id = ? AND i.room_id = ?
");
$stmt->execute([(int) $inventoryId, (int) $roomId]);
$recordedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create a map for quick lookup
$recordedMap = [];
foreach ($recordedItems as $rec) {
    $recordedMap[$rec['item_id']] = $rec;
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'record_item') {
        $itemId = $_POST['item_id'] ?? null;
        $isPresent = $_POST['is_present'] ?? 1;
        $note = $_POST['note'] ?? '';

        if ($itemId) {
            // Check if already recorded
            if (!isset($recordedMap[$itemId])) {
                $inventoryItemModel->add(
                    (int) $inventoryId,
                    (int) $itemId,
                    $user['id'],
                    (int) $isPresent,
                    $note
                );
                $message = 'Eszköz sikeresen rögzítve!';
                $messageType = 'success';

                // Refresh recorded items
                header("Location: inventory_perform.php?inventory_id=$inventoryId&room_id=$roomId&company_id=$companyId");
                exit;
            } else {
                $message = 'Ez az eszköz már rögzítve van!';
                $messageType = 'warning';
            }
        }
    } elseif ($action === 'finish_room') {
        // Mark room as finished (could add room completion tracking later)
        $message = 'Helyiség leltározása befejezve!';
        $messageType = 'success';
        header("Location: inventories.php?company_id=$companyId");
        exit;
    }
}

?>
<!doctype html>
<html lang="hu">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Leltározás - <?= htmlspecialchars($room['name'] ?? 'Szoba') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/global-theme.css">
    <style>
        .item-recorded {
            background-color: rgba(25, 135, 84, 0.1) !important;
            border: 1px solid var(--secondary) !important;
        }

        .item-missing {
            background-color: rgba(220, 53, 69, 0.1) !important;
            border: 1px solid var(--danger) !important;
        }


    </style>
</head>

<body>
    <?php include_once __DIR__ . '/dashboard_nav.php'; ?>
    <div class="page-container">
        <div class="col-lg-10 mx-auto">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Leltározás:
                        <?= htmlspecialchars($inventory['name'] ?? 'Leltár #' . $inventoryId) ?>
                    </h4>
                    <small>Helyiség: <?= htmlspecialchars($room['name'] ?? 'Szoba #' . $roomId) ?></small>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="badge bg-info text-dark fs-6">
                                Összes eszköz: <?= count($items) ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="badge bg-success fs-6">
                                Rögzítve: <?= count($recordedItems) ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="badge bg-warning text-dark fs-6">
                                Hátra: <?= count($items) - count($recordedItems) ?>
                            </div>
                        </div>
                    </div>



                    <!-- Items List -->
                    <h5 class="mb-3">Eszközök listája</h5>
                    <div class="list-group mb-3">
                        <?php if (empty($items)): ?>
                            <div class="alert alert-info">Nincs eszköz ebben a helyiségben.</div>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <?php
                                $isRecorded = isset($recordedMap[$item['id']]);
                                $recordData = $recordedMap[$item['id']] ?? null;
                                $isMissing = $recordData && $recordData['is_present'] == 0;
                                $listClass = $isRecorded ? ($isMissing ? 'item-missing' : 'item-recorded') : '';
                                ?>
                                <div class="list-group-item <?= $listClass ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                                            <?php if (!empty($item['qr_code'])): ?>
                                                <div class="mt-2">
                                                    <img src="<?= htmlspecialchars($item['qr_code']) ?>" alt="QR Code"
                                                        style="max-width: 80px; height: auto; border: 1px solid #ddd; padding: 4px; background: white;">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <?php if ($isRecorded): ?>
                                                <span class="badge bg-<?= $isMissing ? 'danger' : 'success' ?>">
                                                    <?= $isMissing ? '❌ Hiányzik' : '✅ Megtalálva' ?>
                                                </span>
                                                <?php if ($recordData['note']): ?>
                                                    <br><small><?= htmlspecialchars($recordData['note']) ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-success me-1"
                                                    onclick="recordItem(<?= $item['id'] ?>, 1)">
                                                    ✅ Megvan
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="recordItem(<?= $item['id'] ?>, 0)">
                                                    ❌ Hiányzik
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="inventories.php?company_id=<?= $companyId ?>" class="btn btn-secondary">
                            ← Vissza
                        </a>
                        <?php if (count($recordedItems) === count($items) && count($items) > 0): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="finish_room">
                                <button type="submit" class="btn btn-success">
                                    ✅ Helyiség befejezése
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for recording items -->
    <form id="recordForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="record_item">
        <input type="hidden" name="item_id" id="record_item_id">
        <input type="hidden" name="is_present" id="record_is_present">
        <input type="hidden" name="note" id="record_note">
    </form>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>


        function recordItem(itemId, isPresent) {
            let note = '';
            if (isPresent === 0) {
                note = prompt('Megjegyzés a hiányzó eszközhöz (opcionális):');
                if (note === null) return; // User cancelled
            }

            document.getElementById('record_item_id').value = itemId;
            document.getElementById('record_is_present').value = isPresent;
            document.getElementById('record_note').value = note || (isPresent ? 'Megtalálva' : 'Hiányzik');
            document.getElementById('recordForm').submit();
        }
    </script>
</body>

</html>