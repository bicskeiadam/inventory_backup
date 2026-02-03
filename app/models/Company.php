<?php
require_once __DIR__ . '/../core/Model.php';

class Company extends Model {
    public function all(): array {
        $stmt = $this->pdo->query("SELECT * FROM companies ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    public function create(string $name) {
        $stmt = $this->pdo->prepare("INSERT INTO companies (name) VALUES (?)");
        $stmt->execute([$name]);
        return $this->pdo->lastInsertId();
    }
    public function findById(int $id) {
        $stmt = $this->pdo->prepare("SELECT * FROM companies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function update(int $id, string $name): bool {
        $stmt = $this->pdo->prepare("UPDATE companies SET name = ? WHERE id = ?");
        return $stmt->execute([$name, $id]);
    }
}
