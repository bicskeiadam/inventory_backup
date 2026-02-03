<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Inventory.php';

session_start();
if (empty($_SESSION['user'])) { 
    header('Location: ../login.php'); 
    exit; 
}
$user = $_SESSION['user'];

$inventoryId = $_GET['inventory_id'] ?? null;
$companyId = $_GET['company_id'] ?? null;
$format = $_GET['format'] ?? 'html'; // html or pdf

if (!$inventoryId) {
    header('Location: inventories.php');
    exit;
}

$db = (new Database())->getConnection();

// Get inventory details
$stmt = $db->prepare("SELECT * FROM inventories WHERE id = ?");
$stmt->execute([(int)$inventoryId]);
$inventory = $stmt->fetch();

if (!$inventory) {
    header('Location: inventories.php');
    exit;
}

// Get missing items
$stmt = $db->prepare("
    SELECT i.id, i.name, i.qr_code, r.name as room_name,
           ii.note, ii.created_at, u.email as reporter, u.first_name, u.last_name
    FROM inventory_items ii
    JOIN items i ON ii.item_id = i.id
    JOIN rooms r ON i.room_id = r.id
    JOIN users u ON ii.user_id = u.id
    WHERE ii.inventory_id = ? AND ii.is_present = 0
    ORDER BY r.name, i.name
");
$stmt->execute([(int)$inventoryId]);
$missingItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get damaged/problem items (items with notes containing keywords)
$stmt = $db->prepare("
    SELECT i.id, i.name, i.qr_code, r.name as room_name,
           ii.note, ii.created_at, u.email as reporter, u.first_name, u.last_name
    FROM inventory_items ii
    JOIN items i ON ii.item_id = i.id
    JOIN rooms r ON i.room_id = r.id
    JOIN users u ON ii.user_id = u.id
    WHERE ii.inventory_id = ? AND ii.is_present = 1 
          AND (ii.note LIKE '%sérült%' OR ii.note LIKE '%hibás%' OR ii.note LIKE '%törött%' 
               OR ii.note LIKE '%meghibásodott%' OR ii.note LIKE '%probléma%')
    ORDER BY r.name, i.name
");
$stmt->execute([(int)$inventoryId]);
$problemItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group items by room
function groupByRoom($items) {
    $grouped = [];
    foreach ($items as $item) {
        $roomName = $item['room_name'];
        if (!isset($grouped[$roomName])) {
            $grouped[$roomName] = [];
        }
        $grouped[$roomName][] = $item;
    }
    return $grouped;
}

$missingByRoom = groupByRoom($missingItems);
$problemByRoom = groupByRoom($problemItems);

// If PDF format requested, set headers for download
if ($format === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="hiany-problema-lista-' . $inventoryId . '.pdf"');
    // Note: This is a simple HTML-to-PDF approach using browser print
    // For production, consider using a library like TCPDF or DomPDF
    echo '<script>window.print();</script>';
}

?>
<!doctype html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hiány és Probléma Lista - <?=htmlspecialchars($inventory['name'] ?? 'Leltár')?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/global-theme.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
            .card { 
                border: 1px solid #ddd; 
                page-break-inside: avoid; 
                background: white !important; 
                color: black !important; 
            }
            .card-header {
                background: #f8f9fa !important;
                color: #212529 !important;
            }
            .table { color: black !important; }
            .page-break { page-break-after: always; }
        }
        .item-row { 
            border-bottom: 1px solid var(--border); 
            padding: 10px 0; 
        }
        .item-row:last-child { border-bottom: none; }
        .severity-high { 
            border-left: 4px solid var(--danger); 
            padding-left: 10px; 
        }
        .severity-medium { 
            border-left: 4px solid var(--warning); 
            padding-left: 10px; 
        }
    </style>
</head>
<body>

<?php if ($format !== 'pdf'): ?>
<?php include_once __DIR__ . '/dashboard_nav.php'; ?>
<?php endif; ?>

<div class="page-container">
    <div class="col-lg-10 mx-auto">
        <!-- Header -->
        <div class="card mb-3">
            <div class="card-header bg-danger text-white">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="mb-0">🚨 Hiány és Probléma Lista</h3>
                        <p class="mb-0">Leltár: <?=htmlspecialchars($inventory['name'] ?? 'Leltár #'.$inventoryId)?></p>
                        <small>Generálva: <?=date('Y-m-d H:i:s')?></small>
                    </div>
                    <div class="col-auto no-print">
                        <button onclick="window.print()" class="btn btn-light me-2">🖨️ Nyomtatás</button>
                        <a href="?inventory_id=<?=$inventoryId?>&company_id=<?=$companyId?>&format=pdf" 
                           class="btn btn-light" target="_blank">📄 PDF Export</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-6">
                        <h4 class="text-danger"><?=count($missingItems)?></h4>
                        <p class="text-muted mb-0">Hiányzó Eszköz</p>
                    </div>
                    <div class="col-md-6">
                        <h4 class="text-warning"><?=count($problemItems)?></h4>
                        <p class="text-muted mb-0">Problémás Eszköz</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Missing Items Section -->
        <?php if (!empty($missingItems)): ?>
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0">❌ Hiányzó Eszközök (<?=count($missingItems)?>)</h4>
            </div>
            <div class="card-body">
                <?php foreach ($missingByRoom as $roomName => $items): ?>
                    <div class="mb-4">
                        <h5 class="text-danger">📍 <?=htmlspecialchars($roomName)?></h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Eszköz Neve</th>
                                        <th>QR Kód</th>
                                        <th>Megjegyzés</th>
                                        <th>Jelentő</th>
                                        <th>Dátum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): 
                                        $qrCode = $item['qr_code'];
                                        $isImagePath = (strpos($qrCode, '.png') !== false || strpos($qrCode, '/uploads/qr/') !== false);
                                    ?>
                                        <tr>
                                            <td><strong><?=htmlspecialchars($item['name'])?></strong></td>
                                            <td>
                                                <?php if ($isImagePath): ?>
                                                    <img src="<?=htmlspecialchars($qrCode)?>" alt="QR Code" style="width: 50px; height: 50px;">
                                                <?php else: ?>
                                                    <small><?=htmlspecialchars($qrCode)?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?=htmlspecialchars($item['note'] ?? '')?></td>
                                            <td>
                                                <?php 
                                                    $reporter = trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? ''));
                                                    echo htmlspecialchars($reporter ?: $item['reporter']);
                                                ?>
                                            </td>
                                            <td><?=date('Y-m-d H:i', strtotime($item['created_at']))?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-success">
            <strong>✅ Kiváló!</strong> Nincs hiányzó eszköz ebben a leltárban.
        </div>
        <?php endif; ?>

        <div class="page-break"></div>

        <!-- Problem Items Section -->
        <?php if (!empty($problemItems)): ?>
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h4 class="mb-0">⚠️ Problémás Eszközök (<?=count($problemItems)?>)</h4>
            </div>
            <div class="card-body">
                <?php foreach ($problemByRoom as $roomName => $items): ?>
                    <div class="mb-4">
                        <h5 class="text-warning">📍 <?=htmlspecialchars($roomName)?></h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Eszköz Neve</th>
                                        <th>QR Kód</th>
                                        <th>Probléma Leírása</th>
                                        <th>Jelentő</th>
                                        <th>Dátum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): 
                                        $qrCode = $item['qr_code'];
                                        $isImagePath = (strpos($qrCode, '.png') !== false || strpos($qrCode, '/uploads/qr/') !== false);
                                    ?>
                                        <tr>
                                            <td><strong><?=htmlspecialchars($item['name'])?></strong></td>
                                            <td>
                                                <?php if ($isImagePath): ?>
                                                    <img src="<?=htmlspecialchars($qrCode)?>" alt="QR Code" style="width: 50px; height: 50px;">
                                                <?php else: ?>
                                                    <small><?=htmlspecialchars($qrCode)?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark">
                                                    <?=htmlspecialchars($item['note'] ?? '')?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                    $reporter = trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? ''));
                                                    echo htmlspecialchars($reporter ?: $item['reporter']);
                                                ?>
                                            </td>
                                            <td><?=date('Y-m-d H:i', strtotime($item['created_at']))?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-success">
            <strong>✅ Kiváló!</strong> Nincs problémás eszköz ebben a leltárban.
        </div>
        <?php endif; ?>

        <!-- Action Recommendations -->
        <?php if (!empty($missingItems) || !empty($problemItems)): ?>
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">💡 Javasolt Intézkedések</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <?php if (!empty($missingItems)): ?>
                        <li><strong>Hiányzó eszközök:</strong> Vizsgálja meg, hogy az eszközök áthelyezésre kerültek-e más helyiségekbe, vagy elvesztek.</li>
                        <li>Ellenőrizze a biztonsági kamerákat és belépési naplókat.</li>
                        <li>Készítsen jelentést a menedzsmentnek.</li>
                    <?php endif; ?>
                    <?php if (!empty($problemItems)): ?>
                        <li><strong>Problémás eszközök:</strong> Ütemezze be a javítást vagy cserét.</li>
                        <li>Értesítse a karbantartási csapatot.</li>
                        <li>Dokumentálja a sérülések okait a jövőbeni megelőzés érdekében.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer Actions -->
        <div class="no-print d-flex justify-content-between">
            <a href="inventory_summary.php?inventory_id=<?=$inventoryId?>&company_id=<?=$companyId?>" 
               class="btn btn-secondary">
                ← Vissza az Összegzéshez
            </a>
            <a href="inventories.php?company_id=<?=$companyId?>" class="btn btn-primary">
                Leltárak Listája →
            </a>
        </div>
    </div>
</div>

<?php if ($format !== 'pdf'): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>
</body>
</html>
