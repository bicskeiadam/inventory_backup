<?php
require_once __DIR__ . '/../core/Model.php';

class Team extends Model {
    public function allByCompany(int $companyId) {
        $stmt = $this->pdo->prepare("SELECT * FROM teams WHERE company_id = ?");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id) {
        $stmt = $this->pdo->prepare("SELECT * FROM teams WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(int $companyId, string $name) {
        $stmt = $this->pdo->prepare("INSERT INTO teams (company_id, name) VALUES (?, ?)");
        $stmt->execute([$companyId, $name]);
        return $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name): bool {
        $stmt = $this->pdo->prepare("UPDATE teams SET name = ? WHERE id = ?");
        return $stmt->execute([$name, $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM teams WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // team members: simple mapping table teams_users (team_id, user_id)
    public function getMembers(int $teamId) {
        $stmt = $this->pdo->prepare("SELECT u.* FROM users u JOIN team_user tu ON u.id = tu.user_id WHERE tu.team_id = ?");
        $stmt->execute([$teamId]);
        return $stmt->fetchAll();
    }

    public function addMember(int $teamId, int $userId) {
        $stmt = $this->pdo->prepare("INSERT INTO team_user (team_id, user_id) VALUES (?, ?)");
        return $stmt->execute([$teamId, $userId]);
    }

    public function removeMember(int $teamId, int $userId) {
        $stmt = $this->pdo->prepare("DELETE FROM team_user WHERE team_id = ? AND user_id = ?");
        return $stmt->execute([$teamId, $userId]);
    }

    // assignment info for a team -> room mapping (teams_rooms table with info column)
    public function assignRoom(int $teamId, int $roomId, ?string $info = null) {
        $stmt = $this->pdo->prepare("REPLACE INTO team_room (team_id, room_id, info) VALUES (?, ?, ?)");
        return $stmt->execute([$teamId, $roomId, $info]);
    }

    public function getAssignment(int $teamId, int $roomId) {
        $stmt = $this->pdo->prepare("SELECT * FROM team_room WHERE team_id = ? AND room_id = ?");
        $stmt->execute([$teamId, $roomId]);
        return $stmt->fetch();
    }

    public function removeAssignment(int $teamId, int $roomId) {
        $stmt = $this->pdo->prepare("DELETE FROM team_room WHERE team_id = ? AND room_id = ?");
        return $stmt->execute([$teamId, $roomId]);
    }
}
