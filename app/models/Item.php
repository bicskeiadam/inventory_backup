<?php
require_once __DIR__ . '/../core/Model.php';

class Item extends Model {
    protected ?string $lastError = null;

    public function getLastError(): ?string {
        return $this->lastError;
    }

    public function getByRoom(int $roomId) {
        $stmt = $this->pdo->prepare("SELECT * FROM items WHERE room_id = ?");
        $stmt->execute([$roomId]);
        return $stmt->fetchAll();
    }

    public function create(int $roomId, string $name, string $qrCode, ?string $image = null) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO items (room_id, name, qr_code, image) VALUES (?, ?, ?, ?)");
            $res = $stmt->execute([$roomId, $name, $qrCode, $image]);
            if ($res) {
                return (int)$this->pdo->lastInsertId();
            }
            $info = $stmt->errorInfo();
            $this->lastError = isset($info[2]) ? $info[2] : 'Unknown error';
            error_log('Item create failed: ' . $this->lastError);
            return false;
        } catch (\PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log('Item create error: ' . $e->getMessage());
            return false;
        }
    }

    public function findById(int $id) {
        $stmt = $this->pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function update(int $id, int $roomId, string $name, ?string $image = null): bool {
        $stmt = $this->pdo->prepare("UPDATE items SET room_id = ?, name = ?, image = ? WHERE id = ?");
        return $stmt->execute([$roomId, $name, $image, $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM items WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getByCompany(int $companyId) {
        $stmt = $this->pdo->prepare("SELECT i.* FROM items i JOIN rooms r ON i.room_id = r.id WHERE r.company_id = ?");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }

    public function findByQr(string $qr) {
        $stmt = $this->pdo->prepare("SELECT * FROM items WHERE qr_code = ?");
        $stmt->execute([$qr]);
        return $stmt->fetch();
    }
}
