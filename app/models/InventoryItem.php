<?php
require_once __DIR__ . '/../core/Model.php';

class InventoryItem extends Model {
    public function add(int $inventoryId, int $itemId, int $userId, int $isPresent = 1, ?string $note = null) {
        $stmt = $this->pdo->prepare("INSERT INTO inventory_items (inventory_id, item_id, user_id, is_present, note) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$inventoryId, $itemId, $userId, $isPresent, $note]);
        return $this->pdo->lastInsertId();
    }
}
