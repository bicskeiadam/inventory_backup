<?php
require_once __DIR__ . '/../core/Model.php';

class User extends Model {
    public function findByEmail(string $email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findById(int $id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateProfile(int $id, string $first, string $last, ?string $phone = null): bool {
        $stmt = $this->pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
        return $stmt->execute([$first, $last, $phone, $id]);
    }

    public function changePassword(int $id, string $newPassword): bool {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hash, $id]);
    }

    public function create(string $email, string $password, string $first, string $last, ?string $phone = null, string $role = 'worker') {
        if ($this->findByEmail($email)) return false;
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $token = bin2hex(random_bytes(24));
        $stmt = $this->pdo->prepare("INSERT INTO users (email, password, first_name, last_name, phone, role, activation_token) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$email, $hash, $first, $last, $phone, $role, $token]);
        return $token;
    }

    public function activate(string $token): bool {
        $stmt = $this->pdo->prepare("UPDATE users SET is_active = 1, activation_token = NULL WHERE activation_token = ?");
        $stmt->execute([$token]);
        return $stmt->rowCount() > 0;
    }

    public function verifyCredentials(string $email, string $password) {
        $user = $this->findByEmail($email);
        if (!$user) return false;
        if (!$user['is_active'] || $user['is_blocked']) return false;
        if (!password_verify($password, $user['password'])) return false;
        return $user;
    }

    public function setApiToken(int $userId, string $token): bool {
        $stmt = $this->pdo->prepare("UPDATE users SET api_token = ? WHERE id = ?");
        return $stmt->execute([$token, $userId]);
    }

    public function setResetToken(string $email, string $token, string $expires): bool {
        $stmt = $this->pdo->prepare("UPDATE users SET reset_token = ?, token_expires = ? WHERE email = ?");
        return $stmt->execute([$token, $expires, $email]);
    }

    public function resetPassword(string $token, string $newPassword): bool {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND token_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if (!$user) return false;
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $upd = $this->pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expires = NULL WHERE id = ?");
        return $upd->execute([$hash, $user['id']]);
    }

    public function all(): array {
        $stmt = $this->pdo->query("SELECT id, email, first_name, last_name, phone, role, is_active, is_blocked, created_at FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function block(int $id): bool {
        $stmt = $this->pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function unblock(int $id): bool {
        $stmt = $this->pdo->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updateRole(int $id, string $role): bool {
        $stmt = $this->pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$role, $id]);
    }

    public function getEmployers(): array {
        $stmt = $this->pdo->query("SELECT id, email, first_name, last_name, phone, is_active, is_blocked FROM users WHERE role = 'employer' ORDER BY first_name, last_name");
        return $stmt->fetchAll();
    }

    public function assignCompany(int $userId, int $companyId): bool {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO company_user (company_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE company_id = company_id");
            return $stmt->execute([$companyId, $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function unassignCompany(int $userId, int $companyId): bool {
        $stmt = $this->pdo->prepare("DELETE FROM company_user WHERE company_id = ? AND user_id = ?");
        return $stmt->execute([$companyId, $userId]);
    }

    public function getCompaniesForUser(int $userId): array {
        $stmt = $this->pdo->prepare("
            SELECT c.id, c.name 
            FROM companies c
            INNER JOIN company_user cu ON c.id = cu.company_id
            WHERE cu.user_id = ?
            ORDER BY c.name
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
