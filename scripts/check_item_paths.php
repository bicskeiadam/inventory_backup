<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
$db = (new Database())->getConnection();

$sql = "SELECT id, room_id, name, qr_code, image FROM items";
$stmt = $db->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$problems = [];
foreach ($rows as $r) {
    $img = $r['image'] ?? '';
    $qr = $r['qr_code'] ?? '';
    $imgBasename = basename($img);
    $qrBasename = basename($qr);
    $imgLooksLikeQr = preg_match('/^qr_.*\.png$/i', $imgBasename) || stripos($img, '/uploads/qr/') !== false;
    $qrLooksLikeImg = preg_match('/^item_.*\.(jpe?g|png)$/i', $qrBasename) || stripos($qr, '/uploads/items/') !== false;
    if ($imgLooksLikeQr || $qrLooksLikeImg) {
        $problems[] = $r;
    }
}

if (empty($problems)) {
    echo "No suspicious items found.\n";
    exit(0);
}

echo "Suspicious items (image looks like QR or qr_code looks like item image):\n";
foreach ($problems as $p) {
    echo "ID: {$p['id']} | name: {$p['name']} | room: {$p['room_id']}\n";
    echo "  image: {$p['image']}\n";
    echo "  qr_code: {$p['qr_code']}\n";
    echo "------------------------------------------------\n";
}

