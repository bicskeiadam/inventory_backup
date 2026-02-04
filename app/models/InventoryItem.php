<?php
require_once __DIR__ . '/../core/Model.php';

class InventoryItem extends Model
{
    public function add(int $inventoryId, int $itemId, int $userId, int $isPresent = 1, ?string $note = null, ?string $recordedAt = null)
    {
        // Legacy add method wrapper
        return $this->record($inventoryId, $itemId, $userId, $isPresent, $note, null, $recordedAt);
    }

    public function record(int $inventoryId, int $itemId, int $userId, int $isPresent = 1, ?string $note = null, ?string $photoPath = null, ?string $recordedAt = null)
    {
        // Check if already exists for this inventory
        $stmt = $this->pdo->prepare("SELECT id FROM inventory_items WHERE inventory_id = ? AND item_id = ?");
        $stmt->execute([$inventoryId, $itemId]);
        if ($stmt->fetch()) {
            // Setup update or just return error? For api mostly we might want to update or ignore.
            // Let's UPDATE to allow correction
            $sql = "UPDATE inventory_items SET user_id = ?, is_present = ?, note = ?";
            $params = [$userId, $isPresent, $note];

            if ($photoPath) {
                $sql .= ", photo = ?";
                $params[] = $photoPath;
            }

            $sql .= " WHERE inventory_id = ? AND item_id = ?";
            $params[] = $inventoryId;
            $params[] = $itemId;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return true;
        }

        // Use provided timestamp or default to NOW()
        // Convert ISO 8601 format (from JavaScript) to MySQL datetime format
        if ($recordedAt) {
            try {
                $dt = new DateTime($recordedAt);
                $timestamp = $dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                $timestamp = date('Y-m-d H:i:s');
            }
        } else {
            $timestamp = date('Y-m-d H:i:s');
        }

        $stmt = $this->pdo->prepare("INSERT INTO inventory_items (inventory_id, item_id, user_id, is_present, note, photo, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$inventoryId, $itemId, $userId, $isPresent, $note, $photoPath, $timestamp]);
        return $this->pdo->lastInsertId();
    }
}
