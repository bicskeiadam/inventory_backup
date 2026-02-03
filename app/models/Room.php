<?php
require_once __DIR__ . '/../core/Model.php';

class Room extends Model {
    public function getByCompany(int $companyId) {
        $stmt = $this->pdo->prepare("SELECT * FROM rooms WHERE company_id = ?");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }
    public function create(int $companyId, string $name) {
        $stmt = $this->pdo->prepare("INSERT INTO rooms (company_id, name) VALUES (?, ?)");
        $stmt->execute([$companyId, $name]);
        return $this->pdo->lastInsertId();
    }
    public function findById(int $id) {
        $stmt = $this->pdo->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    public function update(int $id, string $name): bool {
        $stmt = $this->pdo->prepare("UPDATE rooms SET name = ? WHERE id = ?");
        return $stmt->execute([$name, $id]);
    }
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM rooms WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
