<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Inventory.php';
require_once __DIR__ . '/../../app/models/Company.php';
require_once __DIR__ . '/../../app/models/Item.php';
require_once __DIR__ . '/../../app/core/Mailer.php';

session_start();
if (empty($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}
$user = $_SESSION['user'];
$user['role'] = strtolower(trim($user['role'] ?? ''));

// Only employers/admins
if (!in_array($user['role'], ['employer', 'admin'])) {
    die('Nincs jogosultság ehhez az oldalhoz.');
}

$db = (new Database())->getConnection();
$inventoryModel = new Inventory($db);
$companyModel = new Company($db);
// Instantiate global Item model for use in the list/modals
$globalItemModel = new Item($db);

// Determine company_id
$companyId = $_GET['company_id'] ?? $_POST['company_id'] ?? null;

$companies = $companyModel->all(); 
$selectedCompanyId = $companyId;

// Handle Replies / Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reply_submission'])) {
        $submissionId = (int)$_POST['submission_id'];
        $message = trim($_POST['message'] ?? '');
        $status = $_POST['status'] ?? ''; // pending, approved, rejected

        if ($submissionId && $message) {
            $inventoryModel->addSubmissionResponse($submissionId, $user['id'], $message);
        }
        if ($submissionId && $status) {
            $inventoryModel->setSubmissionStatus($submissionId, $status);
        }
        
        // Redirect to avoid resubmission
        header("Location: inventory_submissions.php?company_id=$selectedCompanyId&success=1");
        exit;
    }
}

// Fetch submissions
$submissions = [];
if ($selectedCompanyId) {
    $submissions = $inventoryModel->getSubmissionsByCompany((int)$selectedCompanyId);
}
?>
<!doctype html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <title>Leltár Beküldések</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/global-theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Specific overrides for this page if needed */
        .modal-content {
            background-color: var(--bg-card);
            color: var(--text-primary);
            border-color: var(--border);
        }
        .modal-header, .modal-footer {
            border-color: var(--border);
        }
        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        [data-theme="light"] .btn-close {
            filter: none;
        }
        pre {
            color: var(--text-primary);
        }
        /* Fix table header background in dark mode if needed */
        thead th {
            background-color: var(--bg-surface) !important;
            color: var(--text-primary) !important;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/dashboard_nav.php'; ?>
    <div class="page-container">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Beküldött Leltárak</h4>
                        
                        <!-- Company Selector -->
                        <form method="get" class="d-flex gap-2">
                            <select name="company_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 200px;">
                                <option value="">-- Válassz céget --</option>
                                <?php foreach ($companies as $c): ?>
                                    <option value="<?=$c['id']?>" <?= ($selectedCompanyId == $c['id']) ? 'selected' : '' ?>>
                                        <?=htmlspecialchars($c['name'])?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <div class="card-body">
                        <?php if (!$selectedCompanyId): ?>
                            <div class="alert alert-info">Válassz egy céget a beküldések megtekintéséhez.</div>
                        <?php elseif (empty($submissions)): ?>
                            <div class="alert alert-warning">Nincs megjeleníthető beküldés ehhez a céghez.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Leltár</th>
                                            <th>Munkatárs</th>
                                            <th>Dátum</th>
                                            <th>Státusz</th>
                                            <th>Műveletek</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($submissions as $sub): ?>
                                            <tr>
                                                <td>
                                                    <strong><?=htmlspecialchars($sub['inventory_name'])?></strong>
                                                </td>
                                                <td>
                                                    <div><?=htmlspecialchars($sub['worker_name'])?></div>
                                                    <small class="text-muted"><?=htmlspecialchars($sub['worker_email'])?></small>
                                                </td>
                                                <td><?=date('Y.m.d H:i', strtotime($sub['created_at']))?></td>
                                                <td>
                                                    <?php 
                                                    $badgeClass = match($sub['status']) {
                                                        'approved' => 'bg-success',
                                                        'rejected' => 'bg-danger',
                                                        default => 'bg-warning text-dark'
                                                    };
                                                    $statusLabel = match($sub['status']) {
                                                        'approved' => 'Elfogadva',
                                                        'rejected' => 'Elutasítva',
                                                        default => 'Függőben'
                                                    };
                                                    ?>
                                                    <span class="badge <?=$badgeClass?>"><?=$statusLabel?></span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#submissionModal_<?=$sub['id']?>">
                                                        <i class="bi bi-eye"></i> Részletek
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Modals Section - Placed outside the table to prevent layout breakage -->
                            <?php foreach ($submissions as $sub): ?>
                                <div class="modal fade" id="submissionModal_<?=$sub['id']?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Beküldés részletei #<?=$sub['id']?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <h6>Leltár adatok:</h6>
                                                <div class="mb-3">
                                                    <div><strong>Leltár:</strong> <?=htmlspecialchars($sub['inventory_name'])?></div>
                                                    <div><strong>Beküldő:</strong> <?=htmlspecialchars($sub['worker_name'])?></div>
                                                    <div><strong>Időpont:</strong> <?=date('Y.m.d H:i', strtotime($sub['created_at']))?></div>
                                                </div>
                                                
                                                <hr>
                                                <h6>Beküldött tartalom (Adatok):</h6>
                                                <?php
                                                    $decoded = json_decode($sub['payload'], true);
                                                    // Check if it's the expected item list format
                                                    if ($decoded && isset($decoded['items']) && is_array($decoded['items'])):
                                                ?>
                                                    <div class="table-responsive mb-3" style="max-height: 300px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px;">
                                                        <table class="table table-sm table-striped m-0" style="font-size: 0.9rem;">
                                                            <thead style="position: sticky; top: 0; background: var(--bg-surface);">
                                                                <tr>
                                                                    <th>Tétel</th>
                                                                    <th>Állapot</th>
                                                                    <th>Megjegyzés</th>
                                                                    <th>Fotó</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php 
                                                                foreach ($decoded['items'] as $item): 
                                                                    $itemData = $globalItemModel->findById((int)$item['item_id']);
                                                                    $itemName = $itemData ? $itemData['name'] : 'Ismeretlen (ID: '.$item['item_id'].')';
                                                                    $isPresent = !empty($item['is_present']);
                                                                ?>
                                                                <tr>
                                                                    <td>
                                                                        <strong><?=htmlspecialchars($itemName)?></strong><br>
                                                                        <span class="text-muted small">ID: <?=htmlspecialchars($item['item_id'])?></span>
                                                                    </td>
                                                                    <td>
                                                                        <?php if($isPresent): ?>
                                                                            <span class="badge bg-success">Megvan</span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-danger">Hiányzik</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td><?=htmlspecialchars($item['note'] ?? '-')?></td>
                                                                    <td>
                                                                        <?php if(!empty($item['photo'])): ?>
                                                                            <a href="#" onclick="alert('Fotó megjelenítés nem implementált (Base64 vagy URL?)')">📷 Fotó</a>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">-</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php elseif (isset($decoded['action']) && $decoded['action'] === 'finish_signal'): ?>
                                                    <div class="alert alert-success d-flex align-items-start gap-3">
                                                        <i class="bi bi-check-circle-fill fs-4 mt-1"></i>
                                                        <div>
                                                            <h6 class="alert-heading fw-bold mb-1">Leltár befejezve</h6>
                                                            <p class="mb-1"><?= htmlspecialchars($decoded['message'] ?? 'A munkavállaló jelezte a befejezést.') ?></p>
                                                            <small class="opacity-75"><i class="bi bi-clock"></i> <?= htmlspecialchars($decoded['timestamp'] ?? '-') ?></small>
                                                        </div>
                                                    </div>
                                                <?php else: 
                                                    // Fallback for raw JSON
                                                    $prettyJson = $decoded ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $sub['payload'];
                                                ?>
                                                    <div class="p-3 rounded mb-3" style="background-color: var(--bg-surface); max-height: 300px; overflow-y: auto;">
                                                        <pre class="m-0" style="font-size: 0.85rem;"><?=htmlspecialchars($prettyJson)?></pre>
                                                    </div>
                                                <?php endif; ?>

                                                <hr>
                                                <h6>Korábbi válaszok:</h6>
                                                <?php 
                                                $responses = $inventoryModel->getResponses($sub['id']);
                                                foreach ($responses as $resp): 
                                                    $isMe = $resp['user_id'] == $user['id'];
                                                ?>
                                                    <div class="d-flex mb-2 <?=$isMe ? 'justify-content-end' : ''?>">
                                                        <div class="p-2 rounded <?=$isMe ? 'bg-primary text-white' : ''?>" 
                                                             style="max-width: 80%; <?=$isMe ? '' : 'background-color: var(--bg-surface); color: var(--text-primary); border: 1px solid var(--border);'?>">
                                                            <small class="d-block opacity-75 mb-1"><?=date('H:i', strtotime($resp['created_at']))?></small>
                                                            <?=nl2br(htmlspecialchars($resp['message']))?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if(empty($responses)) echo '<p class="text-muted fst-italic">Nincs még válasz.</p>'; ?>
                                                
                                                <hr>
                                                <h6>Válasz írása / Státusz módosítása</h6>
                                                <form method="post">
                                                    <input type="hidden" name="company_id" value="<?=$selectedCompanyId?>">
                                                    <input type="hidden" name="submission_id" value="<?=$sub['id']?>">
                                                    <input type="hidden" name="reply_submission" value="1">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Üzenet a munkatársnak:</label>
                                                        <textarea name="message" class="form-control" rows="3" placeholder="Írj visszajelzést..."></textarea>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Státusz:</label>
                                                        <select name="status" class="form-select">
                                                            <option value="pending" <?=($sub['status']=='pending')?'selected':''?>>Függőben</option>
                                                            <option value="approved" <?=($sub['status']=='approved')?'selected':''?>>Elfogadva</option>
                                                            <option value="rejected" <?=($sub['status']=='rejected')?'selected':''?>>Elutasítva (Újraellenőrzés szükséges)</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-end gap-2">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bezárás</button>
                                                        <button type="submit" class="btn btn-primary">Mentés és Küldés</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>