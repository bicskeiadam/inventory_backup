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

if (!$inventoryId) {
    header('Location: inventories.php');
    exit;
}

$db = (new Database())->getConnection();
$inventoryModel = new Inventory($db);

// Get inventory details
$stmt = $db->prepare("SELECT * FROM inventories WHERE id = ?");
$stmt->execute([(int)$inventoryId]);
$inventory = $stmt->fetch();

if (!$inventory) {
    header('Location: inventories.php');
    exit;
}

// Get inventory statistics
$stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT ii.item_id) as total_items_scanned,
        SUM(CASE WHEN ii.is_present = 1 THEN 1 ELSE 0 END) as items_found,
        SUM(CASE WHEN ii.is_present = 0 THEN 1 ELSE 0 END) as items_missing,
        COUNT(DISTINCT ii.user_id) as participants,
        MIN(ii.created_at) as start_time,
        MAX(ii.created_at) as end_time
    FROM inventory_items ii
    WHERE ii.inventory_id = ?
");
$stmt->execute([(int)$inventoryId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all items in the company to calculate total expected
$stmt = $db->prepare("
    SELECT COUNT(*) as total_company_items
    FROM items i
    JOIN rooms r ON i.room_id = r.id
    WHERE r.company_id = ?
");
$stmt->execute([$inventory['company_id']]);
$companyStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get participants details
$stmt = $db->prepare("
    SELECT DISTINCT u.id, u.email, u.first_name, u.last_name, 
           COUNT(ii.id) as items_scanned
    FROM inventory_items ii
    JOIN users u ON ii.user_id = u.id
    WHERE ii.inventory_id = ?
    GROUP BY u.id
    ORDER BY items_scanned DESC
");
$stmt->execute([(int)$inventoryId]);
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get missing items details
$stmt = $db->prepare("
    SELECT i.name, i.qr_code, r.name as room_name, ii.note, ii.created_at, u.email as reporter
    FROM inventory_items ii
    JOIN items i ON ii.item_id = i.id
    JOIN rooms r ON i.room_id = r.id
    JOIN users u ON ii.user_id = u.id
    WHERE ii.inventory_id = ? AND ii.is_present = 0
    ORDER BY r.name, i.name
");
$stmt->execute([(int)$inventoryId]);
$missingItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all logged items
$stmt = $db->prepare("
    SELECT i.name, i.qr_code, r.name as room_name, 
           ii.is_present, ii.note, ii.created_at, 
           u.email as logged_by, u.first_name, u.last_name
    FROM inventory_items ii
    JOIN items i ON ii.item_id = i.id
    JOIN rooms r ON i.room_id = r.id
    JOIN users u ON ii.user_id = u.id
    WHERE ii.inventory_id = ?
    ORDER BY ii.created_at DESC, r.name, i.name
");
$stmt->execute([(int)$inventoryId]);
$allLoggedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate duration
$duration = 'N/A';
if ($stats['start_time'] && $stats['end_time']) {
    $start = new DateTime($stats['start_time']);
    $end = new DateTime($stats['end_time']);
    $interval = $start->diff($end);
    
    if ($interval->days > 0) {
        $duration = $interval->days . ' nap ' . $interval->h . ' óra';
    } elseif ($interval->h > 0) {
        $duration = $interval->h . ' óra ' . $interval->i . ' perc';
    } else {
        $duration = $interval->i . ' perc ' . $interval->s . ' másodperc';
    }
}

// Calculate percentages
$totalScanned = $stats['total_items_scanned'] ?? 0;
$itemsFound = $stats['items_found'] ?? 0;
$itemsMissing = $stats['items_missing'] ?? 0;
$totalExpected = $companyStats['total_company_items'] ?? 1;

$completionPercentage = $totalExpected > 0 ? round(($totalScanned / $totalExpected) * 100, 1) : 0;
$foundPercentage = $totalScanned > 0 ? round(($itemsFound / $totalScanned) * 100, 1) : 0;
$missingPercentage = $totalScanned > 0 ? round(($itemsMissing / $totalScanned) * 100, 1) : 0;

?>
<!doctype html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Leltár Összegzés</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/global-theme.css">
    <style>
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card.primary { border-left-color: var(--primary); }
        .stat-card.success { border-left-color: var(--secondary); }
        .stat-card.danger { border-left-color: var(--danger); }
        .stat-card.info { border-left-color: #3b82f6; }
        .stat-card.warning { border-left-color: var(--warning); }
        .stat-card:hover { transform: translateY(-2px); }
        
        /* Print styles */
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
            .card { 
                background: white !important; 
                border: 1px solid #ddd !important;
                box-shadow: none !important;
                page-break-inside: avoid;
            }
            .card-header { 
                background: #f8f9fa !important; 
                color: #212529 !important;
                border-bottom: 2px solid #dee2e6 !important;
            }
            .table { color: black !important; }
            .table thead th { 
                background: #f8f9fa !important; 
                color: black !important; 
            }
            .table tbody td { 
                background: white !important; 
                color: black !important; 
            }
            .text-success { color: #198754 !important; }
            .text-danger { color: #dc3545 !important; }
            .text-muted { color: #6c757d !important; }
            .navbar { display: none !important; }
            .page-container { padding: 0 !important; }
        }
    </style>
</head>
<body>
<?php include_once __DIR__ . '/dashboard_nav.php'; ?>

<div class="page-container">
    <div class="col-lg-10 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0">📊 Leltár Összegzés</h3>
                    <p class="mb-0"><?=htmlspecialchars($inventory['name'] ?? 'Leltár #'.$inventoryId)?></p>
                </div>
                <div class="no-print">
                    <button onclick="window.print()" class="btn btn-light">🖨️ Nyomtatás</button>
                </div>
            </div>
            <div class="card-body">
                <!-- Key Statistics -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card primary h-100">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">Leltározott Eszközök</h6>
                                <h2 class="mb-2"><?=$totalScanned?></h2>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-primary" style="width: <?=$completionPercentage?>%"></div>
                                </div>
                                <small class="text-muted">
                                    <?=$completionPercentage?>% (<?=$totalScanned?> / <?=$totalExpected?>)
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card stat-card success h-100">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">✅ Megtalált Eszközök</h6>
                                <h2 class="mb-2 text-success"><?=$itemsFound?></h2>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-success" style="width: <?=$foundPercentage?>%"></div>
                                </div>
                                <small class="text-muted"><?=$foundPercentage?>% a leltározott eszközökből</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card stat-card danger h-100">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">❌ Hiányzó Eszközök</h6>
                                <h2 class="mb-2 text-danger"><?=$itemsMissing?></h2>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-danger" style="width: <?=$missingPercentage?>%"></div>
                                </div>
                                <small class="text-muted"><?=$missingPercentage?>% a leltározott eszközökből</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Statistics -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card stat-card info h-100">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">👥 Résztvevők Száma</h6>
                                <h2 class="mb-0"><?=$stats['participants'] ?? 0?></h2>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card stat-card warning h-100">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">⏱️ Leltár Időtartama</h6>
                                <h2 class="mb-0"><?=$duration?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <?php if ($stats['start_time'] && $stats['end_time']): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">📅 Idővonalas</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Kezdés:</strong> <?=date('Y-m-d H:i:s', strtotime($stats['start_time']))?>
                            </div>
                            <div class="col-md-6">
                                <strong>Befejezés:</strong> <?=date('Y-m-d H:i:s', strtotime($stats['end_time']))?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Participants Table -->
                <?php if (!empty($participants)): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">👥 Résztvevők Teljesítménye</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Név</th>
                                        <th>Email</th>
                                        <th class="text-end">Leltározott Eszközök</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($participants as $p): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                    $name = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
                                                    echo htmlspecialchars($name ?: 'N/A');
                                                ?>
                                            </td>
                                            <td><?=htmlspecialchars($p['email'])?></td>
                                            <td class="text-end">
                                                <span class="badge bg-primary"><?=$p['items_scanned']?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- All Logged Items Table -->
                <?php if (!empty($allLoggedItems)): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">📋 Összes Leltározott Eszköz</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Helyiség</th>
                                        <th>Eszköz Neve</th>
                                        <th>QR Kód</th>
                                        <th>Státusz</th>
                                        <th>Megjegyzés</th>
                                        <th>Leltározta</th>
                                        <th>Időpont</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allLoggedItems as $item): 
                                        $userName = trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? ''));
                                        $userName = $userName ?: $item['logged_by'];
                                    ?>
                                        <tr>
                                            <td><?=htmlspecialchars($item['room_name'])?></td>
                                            <td><strong><?=htmlspecialchars($item['name'])?></strong></td>
                                            <td>
                                                <?php 
                                                $qrCode = $item['qr_code'];
                                                // Check if qr_code is a file path (starts with ../ or / or contains .png)
                                                $isImagePath = (strpos($qrCode, '.png') !== false || strpos($qrCode, '/uploads/qr/') !== false);
                                                ?>
                                                <?php if ($isImagePath): ?>
                                                    <img src="<?=htmlspecialchars($qrCode)?>" 
                                                         alt="QR Code" 
                                                         style="width: 60px; height: 60px; display: block;"
                                                         title="<?=htmlspecialchars($qrCode)?>">
                                                <?php else: ?>
                                                    <code><?=htmlspecialchars($qrCode)?></code>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['is_present']): ?>
                                                    <span class="badge bg-success">✅ Megtalálva</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">❌ Hiányzik</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?=htmlspecialchars($item['note'] ?? '-')?></td>
                                            <td><?=htmlspecialchars($userName)?></td>
                                            <td><?=date('Y-m-d H:i', strtotime($item['created_at']))?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Missing Items Table -->
                <?php if (!empty($missingItems)): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title text-danger">❌ Hiányzó Eszközök Részletei</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Helyiség</th>
                                        <th>Eszköz Neve</th>
                                        <th>Megjegyzés</th>
                                        <th>Jelentő</th>
                                        <th>Dátum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($missingItems as $item): ?>
                                        <tr>
                                            <td><?=htmlspecialchars($item['room_name'])?></td>
                                            <td><strong><?=htmlspecialchars($item['name'])?></strong></td>
                                            <td><?=htmlspecialchars($item['note'] ?? '')?></td>
                                            <td><?=htmlspecialchars($item['reporter'])?></td>
                                            <td><?=date('Y-m-d H:i', strtotime($item['created_at']))?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <strong>🎉 Kiváló!</strong> Minden eszköz megtalálható volt a leltár során!
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between no-print">
                    <a href="inventories.php?company_id=<?=$companyId?>" class="btn btn-secondary">
                        ← Vissza a Leltárakhoz
                    </a>
                    <div>
                        <?php if (!empty($missingItems) || ($stats['items_missing'] ?? 0) > 0): ?>
                            <a href="inventory_problems.php?inventory_id=<?=$inventoryId?>&company_id=<?=$companyId?>" 
                               class="btn btn-danger me-2">
                                🚨 Hiány és Probléma Lista
                            </a>
                        <?php endif; ?>
                        <a href="inventory_archive.php?company_id=<?=$companyId?>" class="btn btn-primary">
                            Archívum Megtekintése →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
