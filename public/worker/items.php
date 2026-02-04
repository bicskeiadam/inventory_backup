<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Item.php';
require_once __DIR__ . '/../../app/models/Room.php';
require_once __DIR__ . '/../../app/models/Company.php';
require_once __DIR__ . '/../../app/core/QRGenerator.php';

session_start();
if (empty($_SESSION['user'])) { header('Location: ../login.php'); exit; }
$user = $_SESSION['user'];
$user['role'] = strtolower(trim($user['role'] ?? ''));

// pop any upload error set by server-side validation
$uploadError = null;
if (!empty($_SESSION['item_upload_error'])) {
    $uploadError = $_SESSION['item_upload_error'];
    unset($_SESSION['item_upload_error']);
}

// helper: normalize stored path or filename to a web path under /uploads
function get_web_path(?string $stored): string {
    if (empty($stored)) return '';
    // already full URL
    if (preg_match('#^https?://#i', $stored)) return $stored;
    // already absolute web path
    if (strpos($stored, '/') === 0) return $stored;
    $s = trim($stored);
    // common filename heuristics
    if (preg_match('/^qr_.*\.png$/i', $s) || stripos($s, 'qr/') !== false || stripos($s, 'qr\\\\') !== false) {
        return '/uploads/qr/' . basename($s);
    }
    if (preg_match('/^item_.*\.(jpg|jpeg|png)$/i', $s) || stripos($s, 'items/') !== false || stripos($s, 'items\\\\') !== false) {
        return '/uploads/items/' . basename($s);
    }
    // fallback: return as-is
    return $stored;
}

// only allow employers/admins
if (!in_array($user['role'] ?? '', ['employer','admin'])) {
    // show access denied
    echo "<p>Nincs jogosultságod az eszközök kezeléséhez.</p>"; exit;
}

$db = (new Database())->getConnection();
$itemModel = new Item($db);
$roomModel = new Room($db);
$companyModel = new Company($db);

$action = $_GET['action'] ?? null;
$companyId = $_GET['company_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_item'])) {
        $room_id = (int)($_POST['room_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($room_id && $name) {
            // create QR payload and file
            $qrPayload = json_encode(['room'=>$room_id,'name'=>$name,'ts'=>time()]);
            $fileName = 'qr_'.time().'_'.bin2hex(random_bytes(6)).'.png';
            $dir = __DIR__ . '/../uploads/qr';
            if(!is_dir($dir)) mkdir($dir, 0777, true);
            $fullFilePath = $dir . '/' . $fileName;
            QRGenerator::generate($qrPayload, $fullFilePath);
            $qrWebPath = '../uploads/qr/' . $fileName;

            // handle optional image upload for the item
            $imageWebPath = null;
            if (!empty($_FILES['image']['name'])) {
                // server-side validation: max 5MB and only jpeg/png
                $maxBytes = 5 * 1024 * 1024; // 5MB
                $allowedMimes = ['image/jpeg', 'image/png'];
                $tmp = $_FILES['image']['tmp_name'] ?? null;
                $size = $_FILES['image']['size'] ?? 0;
                $finfoMime = '';
                if ($tmp && file_exists($tmp)) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $finfoMime = finfo_file($finfo, $tmp);
                    finfo_close($finfo);
                }
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if ($size > $maxBytes) {
                    $_SESSION['item_upload_error'] = 'A feltöltött kép mérete nem lehet nagyobb 5 MB-nál.';
                    header('Location: items.php?company_id=' . urlencode($_POST['company_id'] ?? ''));
                    exit;
                }
                if (!in_array($finfoMime, $allowedMimes) || !in_array($ext, ['jpg','jpeg','png'])) {
                    $_SESSION['item_upload_error'] = 'Csak JPG és PNG formátum engedélyezett.';
                    header('Location: items.php?company_id=' . urlencode($_POST['company_id'] ?? ''));
                    exit;
                }

                $imgDir = __DIR__ . '/../../public/uploads/items';
                if (!is_dir($imgDir)) mkdir($imgDir, 0777, true);
                $imgExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $imgFile = 'item_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $imgExt;
                $imgPath = $imgDir . '/' . $imgFile;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $imgPath)) {
                    $imageWebPath = '/uploads/items/' . $imgFile;
                }
            }
            // create item with optional image path
            $itemModel->create($room_id, $name, $qrWebPath, $imageWebPath);
        }
        header('Location: items.php?company_id=' . urlencode($_POST['company_id'] ?? '')); exit;
    }
    if (isset($_POST['edit_item'])) {
        $id = (int)$_POST['id'];
        $room_id = (int)($_POST['room_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($id && $room_id && $name) {
            // handle optional image upload on edit
            $imageWebPath = null;
            if (!empty($_FILES['image']['name'])) {
                // server-side validation: max 5MB and only jpeg/png
                $maxBytes = 5 * 1024 * 1024; // 5MB
                $allowedMimes = ['image/jpeg', 'image/png'];
                $tmp = $_FILES['image']['tmp_name'] ?? null;
                $size = $_FILES['image']['size'] ?? 0;
                $finfoMime = '';
                if ($tmp && file_exists($tmp)) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $finfoMime = finfo_file($finfo, $tmp);
                    finfo_close($finfo);
                }
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if ($size > $maxBytes) {
                    $_SESSION['item_upload_error'] = 'A feltöltött kép mérete nem lehet nagyobb 5 MB-nál.';
                    header('Location: items.php?action=edit&id=' . urlencode($id) . '&company_id=' . urlencode($_POST['company_id'] ?? ''));
                    exit;
                }
                if (!in_array($finfoMime, $allowedMimes) || !in_array($ext, ['jpg','jpeg','png'])) {
                    $_SESSION['item_upload_error'] = 'Csak JPG és PNG formátum engedélyezett.';
                    header('Location: items.php?action=edit&id=' . urlencode($id) . '&company_id=' . urlencode($_POST['company_id'] ?? ''));
                    exit;
                }

                $imgDir = __DIR__ . '/../../public/uploads/items';
                if (!is_dir($imgDir)) mkdir($imgDir, 0777, true);
                $imgExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $imgFile = 'item_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $imgExt;
                $imgPath = $imgDir . '/' . $imgFile;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $imgPath)) {
                    $imageWebPath = '/uploads/items/' . $imgFile;
                }
            }
            $itemModel->update($id, $room_id, $name, $imageWebPath);
        }
        header('Location: items.php?company_id=' . urlencode($_POST['company_id'] ?? '')); exit;
    }
    if (isset($_POST['delete_item'])) {
        $id = (int)$_POST['id'];
        if ($id) $itemModel->delete($id);
        header('Location: items.php?company_id=' . urlencode($_POST['company_id'] ?? '')); exit;
    }
}

$companies = $companyModel->all();
$rooms = $companyId ? $roomModel->getByCompany((int)$companyId) : [];
$items = $companyId ? $itemModel->getByCompany((int)$companyId) : [];
?>
<!doctype html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <title>Eszközök kezelése</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/global-theme.css">
    <link rel="stylesheet" href="../css/items.css">
</head>
<body>

<?php include_once __DIR__ . '/dashboard_nav.php'; ?>

<div class="page-container">
    <div class="card shadow p-4">

        <h2 class="title mb-4">Eszközök kezelése</h2>

        <!-- Cégek szűrő -->
        <form method="get" class="row g-2 mb-4">
            <div class="col-md-6">
                <label for="company_id" class="visually-hidden">Cég</label>
                <select id="company_id" name="company_id" class="form-select filter-select">
                    <option value="">-- Válassz céget --</option>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?=$c['id']?>" <?=($companyId == $c['id'])?'selected':''?>>
                            <?=htmlspecialchars($c['name'])?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100 filter-btn">Mutat</button>
            </div>
        </form>

        <?php if ($companyId): ?>

            <h4 class="section-title mt-4">Eszközök listája</h4>

            <!-- Táblázat -->
            <div class="table-responsive mb-4">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>Név</th>
                        <th>Helyiség</th>
                        <th>Kép</th>
                        <th class="text-end">Műveletek</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="4" class="text-center text-muted p-4">Nincs megjeleníthető eszköz.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($items as $it):
                        $room = $roomModel->findById((int)$it['room_id']);
                        $imgSrc = get_web_path($it['image'] ?? '');
                        $qrSrc = get_web_path($it['qr_code'] ?? '');
                        // Check if valid image
                        $isImage = $imgSrc && preg_match('/\.(jpe?g|png)$/i', $imgSrc) && strpos($imgSrc, '/uploads/items/') !== false;
                        // Check if valid QR
                        $isQr = $qrSrc && preg_match('/\.png$/i', $qrSrc);
                         ?>
                        <tr>
                            <td class="fw-semibold"><?=htmlspecialchars($it['name'])?></td>
                            <td><?=htmlspecialchars($room['name'] ?? '')?></td>
                            <td>
                                <?php if ($isImage): ?>
                                    <button type="button" class="btn btn-sm btn-outline-info btn-view-img" data-img="<?=htmlspecialchars($imgSrc)?>">
                                        <i class="bi bi-image"></i> Megtekintés
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="items.php?action=edit&id=<?=$it['id']?>&company_id=<?=$companyId?>"
                                   class="btn btn-sm btn-outline-primary action-btn">
                                    Szerkesztés
                                </a>

                                <?php if ($isQr): ?>
                                    <button class="btn btn-sm btn-outline-info action-btn btn-show-qr" data-qr="<?=htmlspecialchars($qrSrc)?>">QR kód</button>
                                <?php endif; ?>

                                <form method="post" class="d-inline" onsubmit="return confirm('Biztosan törölni szeretnéd?');">
                                    <input type="hidden" name="delete_item" value="1">
                                    <input type="hidden" name="id" value="<?=$it['id']?>">
                                    <input type="hidden" name="company_id" value="<?=$companyId?>">
                                    <button class="btn btn-sm btn-outline-danger action-btn">Törlés</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($action === 'edit' && isset($_GET['id'])):
                $item = $itemModel->findById((int)$_GET['id']);
                ?>
                <!-- Szerkesztő doboz -->
                <div class="card p-4 mb-4">
                    <h4 class="section-title mb-3">Eszköz szerkesztése</h4>

                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="edit_item" value="1">
                        <input type="hidden" name="id" value="<?=htmlspecialchars($item['id'])?>">
                        <input type="hidden" name="company_id" value="<?=$companyId?>">

                        <div class="mb-3">
                            <label for="edit_item_name" class="form-label">Eszköz neve</label>
                            <input id="edit_item_name" name="name" class="form-control"
                                   value="<?=htmlspecialchars($item['name'])?>">
                        </div>

                        <div class="mb-3">
                            <label for="edit_room_id" class="form-label">Helyiség</label>
                            <select id="edit_room_id" name="room_id" class="form-select">
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?=$r['id']?>" <?=($item['room_id'] == $r['id'])?'selected':''?>>
                                        <?=htmlspecialchars($r['name'])?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kép feltöltése (opcionális)</label>
                            <input type="file" name="image" accept="image/*" class="form-control mb-2">
                            <?php if (!empty($item['image'])): ?>
                                <?php $editImg = get_web_path($item['image'] ?? ''); ?>
                                <?php if ($editImg): ?>
                                    <button type="button" class="btn btn-sm btn-outline-light btn-view-img" data-img="<?=htmlspecialchars($editImg)?>">Jelenlegi kép megtekintése</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary">Mentés</button>
                            <a href="items.php?company_id=<?=$companyId?>" class="btn btn-secondary">Mégse</a>
                        </div>
                    </form>
                </div>

            <?php else: ?>

                <!-- Új eszköz doboz -->
                <div class="card p-4 mt-4">
                    <h4 class="section-title mb-3">Új eszköz hozzáadása</h4>

                    <form method="post" class="row g-3" enctype="multipart/form-data">
                        <input type="hidden" name="create_item" value="1">
                        <input type="hidden" name="company_id" value="<?=$companyId?>">

                        <div class="col-md-5">
                            <label for="create_room_id" class="form-label">Helyiség</label>
                            <select id="create_room_id" name="room_id" class="form-select" required>
                                <option value="">-- Helyiség --</option>
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?=$r['id']?>"><?=htmlspecialchars($r['name'])?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-5">
                            <label for="create_item_name" class="form-label">Eszköz neve</label>
                            <input id="create_item_name" name="name" class="form-control" placeholder="Eszköz neve" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Kép feltöltése (opcionális)</label>
                            <input type="file" name="image" accept="image/*" class="form-control">
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-success w-100">Hozzáad</button>
                        </div>
                    </form>
                </div>

            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-info mt-3">
                <i class="bi bi-info-circle"></i> Válassz egy céget az eszközök kezeléséhez.
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Bootstrap Modal for Image/QR Preview -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Előnézet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img id="previewModalImg" src="" alt="Preview" class="img-fluid rounded shadow-sm" style="max-height: 400px;">
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // We expect paths to be like /uploads/items/xxx.jpg
    // Since we are in /public/worker/, we need to go up to /public/ for root relative paths
    // BUT, if the server is running at localhost/inventory_backup/, then /uploads means localhost/uploads which is WRONG
    // So we need to prepend the project root.
    
    // Simple robust solution: find the 'public' segment in current URL and build from there
    function buildPath(path) {
        if (!path) return '';
        if (path.startsWith('http')) return path;
        
        // If path starts with /, remove it to make it clean relative
        const cleanPath = path.startsWith('/') ? path.substring(1) : path;
        
        // We are in /public/worker/items.php
        // The images are in /public/uploads/
        // So we need ../../public/uploads/ => wait, no.
        // /public/worker/ -> ../ is /public/
        // So correct relative path from here is ../ + path (if path is like uploads/items/...)
        
        // Let's assume path is "uploads/items/file.jpg"
        return '../' + cleanPath;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const previewModalElement = document.getElementById('previewModal');
        const previewModal = new bootstrap.Modal(previewModalElement);
        const modalImg = document.getElementById('previewModalImg');
        const modalTitle = document.getElementById('previewModalLabel');

        // Event delegation
        document.body.addEventListener('click', function(e) {
            const imgBtn = e.target.closest('.btn-view-img');
            const qrBtn = e.target.closest('.btn-show-qr');
            
            if (imgBtn || qrBtn) {
                e.preventDefault();
                const btn = imgBtn || qrBtn;
                let src = btn.getAttribute(imgBtn ? 'data-img' : 'data-qr');
                const title = imgBtn ? 'Kép megtekintése' : 'QR kód';
                
                if (src) {
                    modalTitle.textContent = title;
                    modalImg.src = buildPath(src);
                    console.log('Loading image:', modalImg.src); // Debug
                    previewModal.show();
                }
            }
        });
    });

    // client-side validation for image inputs (max 5MB and jpg/png)
    (function(){
        var maxBytes = 5 * 1024 * 1024;
        var allowed = ['image/jpeg','image/png'];

        function showClientError(msg) {
            // create temporary modal if not exists
            var existing = document.getElementById('clientUploadError');
            if (existing) existing.parentNode.removeChild(existing);
            var div = document.createElement('div');
            div.id = 'clientUploadError';
            div.style.position = 'fixed';
            div.style.right = '20px';
            div.style.top = '20px';
            div.style.zIndex = 2000;
            div.innerHTML = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' + msg + '<button type="button" class="btn-close" id="clientUploadErrorClose"></button></div>';
            document.body.appendChild(div);
            var close = document.getElementById('clientUploadErrorClose');
            close && close.addEventListener('click', function(){ div.style.display='none'; });
            setTimeout(function(){ if (div) div.style.display='none'; }, 4000);
        }

        function validateFileInput(fileInput) {
            if (!fileInput || !fileInput.files || !fileInput.files.length) return true;
            var f = fileInput.files[0];
            if (f.size > maxBytes) { showClientError('A kép túl nagy (max 5 MB).'); return false; }
            if (allowed.indexOf(f.type) === -1) { showClientError('Csak JPG és PNG formátum engedélyezett.'); return false; }
            return true;
        }

        var createForm = document.querySelector('form[method="post"][enctype]');
        if (createForm) {
            // restrict accept attribute
            var fileInputs = createForm.querySelectorAll('input[type=file]');
            fileInputs.forEach(function(fi){ fi.setAttribute('accept','image/png,image/jpeg'); });
            createForm.addEventListener('submit', function(e){
                var ok = true;
                fileInputs.forEach(function(fi){ if (!validateFileInput(fi)) ok = false; });
                if (!ok) e.preventDefault();
            });
        }

        // also handle edit form if present
        var editForm = document.querySelector('form[enctype][action=""]');
        // fallback: find second form with enctype
        var forms = document.querySelectorAll('form[enctype]');
        if (forms.length > 1) editForm = forms[1];
        if (editForm) {
            var editFiles = editForm.querySelectorAll('input[type=file]');
            editFiles.forEach(function(fi){ fi.setAttribute('accept','image/png,image/jpeg'); });
            editForm.addEventListener('submit', function(e){
                var ok = true;
                editFiles.forEach(function(fi){ if (!validateFileInput(fi)) ok = false; });
                if (!ok) e.preventDefault();
            });
        }

        // auto-hide server-side modal (if present)
        var srvModal = document.getElementById('uploadErrorModal');
        if (srvModal) {
            setTimeout(function(){ srvModal.style.display = 'none'; }, 4000);
            var closeBtn = document.getElementById('uploadErrorClose');
            if (closeBtn) closeBtn.addEventListener('click', function(){ srvModal.style.display = 'none'; });
        }
        
        // Auto-refresh page every 30 seconds (if no modal is open and user isn't typing)
        setInterval(function() {
            var openModals = document.querySelectorAll('.modal.show');
            var activeElement = document.activeElement;
            var isTyping = activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA' || activeElement.tagName === 'SELECT');
            
            if (openModals.length === 0 && !isTyping) {
                location.reload();
            }
        }, 30000);
    })();
</script>

</body>
</html>
