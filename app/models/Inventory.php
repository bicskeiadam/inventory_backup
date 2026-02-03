<?php
require_once __DIR__ . '/../core/Model.php';

class Inventory extends Model
{
    public function create(int $companyId, ?string $name, string $status = 'scheduled', ?string $startDate = null)
    {
        $stmt = $this->pdo->prepare("INSERT INTO inventories (company_id, name, status, start_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$companyId, $name, $status, $startDate]);
        return $this->pdo->lastInsertId();
    }

    // Schedule targets: inventory_targets (inventory_id, target_type, target_id)
    public function addTargets(int $inventoryId, array $targets): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO inventory_targets (inventory_id, target_type, target_id) VALUES (?, ?, ?)");
        foreach ($targets as $t) {
            // each $t = ['type'=>'team'|'room','id'=>int]
            $type = $t['type'];
            $id = $t['id'];
            if (!$stmt->execute([$inventoryId, $type, $id]))
                return false;
        }
        return true;
    }

    public function getTargets(int $inventoryId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inventory_targets WHERE inventory_id = ?");
        $stmt->execute([$inventoryId]);
        return $stmt->fetchAll();
    }

    public function getByCompany(int $companyId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inventories WHERE company_id = ?");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }

    public function schedule(int $inventoryId, ?string $startDate): bool
    {
        $stmt = $this->pdo->prepare("UPDATE inventories SET start_date = ?, status = 'scheduled' WHERE id = ?");
        return $stmt->execute([$startDate, $inventoryId]);
    }

    public function setStatus(int $inventoryId, string $status): bool
    {
        $stmt = $this->pdo->prepare("UPDATE inventories SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $inventoryId]);
    }

    // submissions stored in inventory_submissions (inventory_id, user_id, payload, created_at)
    public function getSubmissions(int $inventoryId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inventory_submissions WHERE inventory_id = ? ORDER BY created_at DESC");
        $stmt->execute([$inventoryId]);
        return $stmt->fetchAll();
    }

    public function getArchive(int $companyId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inventories WHERE company_id = ? AND status = 'finished'");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }

    public function finish(int $inventoryId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE inventories SET status = 'finished' WHERE id = ?");
        return $stmt->execute([$inventoryId]);
    }

    // Schedule targets: inventory_schedules (inventory_id, target_type, target_id)
    public function addScheduleTargets(int $inventoryId, array $targets): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO inventory_schedules (inventory_id, target_type, target_id) VALUES (?, ?, ?)");
        foreach ($targets as $t) {
            // each $t = ['type'=>'team'|'room'|'all','id'=>int|null]
            $type = $t['type'];
            $id = $t['id'] ?? null;
            if (!$stmt->execute([$inventoryId, $type, $id]))
                return false;
        }
        return true;
    }

    // Convenience: create inventory and attach targets
    public function createWithTargets(int $companyId, ?string $name, ?string $startDate, array $targets)
    {
        $this->pdo->beginTransaction();
        try {
            $invId = $this->create($companyId, $name, 'scheduled', $startDate);
            if (!empty($targets)) {
                if (!$this->addTargets($invId, $targets))
                    throw new Exception('Failed to add targets');
            }
            $this->pdo->commit();
            return $invId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    // Responses for submissions
    public function addSubmissionResponse(int $submissionId, int $userId, string $message): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO inventory_submission_responses (submission_id, user_id, message) VALUES (?, ?, ?)");
        return $stmt->execute([$submissionId, $userId, $message]);
    }

    public function getResponses(int $submissionId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inventory_submission_responses WHERE submission_id = ? ORDER BY created_at ASC");
        $stmt->execute([$submissionId]);
        return $stmt->fetchAll();
    }

    // Update submission status (pending/approved/rejected)
    public function setSubmissionStatus(int $submissionId, string $status): bool
    {
        $validStatuses = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $validStatuses))
            return false;
        $stmt = $this->pdo->prepare("UPDATE inventory_submissions SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $submissionId]);
    }

    // Get all submissions for a specific user (for worker's "My Submissions" screen)
    public function getSubmissionsByUser(int $userId)
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, i.name as inventory_name, i.status as inventory_status,
                   (SELECT isr.message FROM inventory_submission_responses isr 
                    WHERE isr.submission_id = s.id 
                    ORDER BY isr.created_at DESC LIMIT 1) as employer_response
            FROM inventory_submissions s 
            JOIN inventories i ON s.inventory_id = i.id 
            WHERE s.user_id = ? 
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Get a single submission by ID
    public function getSubmissionById(int $submissionId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inventory_submissions WHERE id = ?");
        $stmt->execute([$submissionId]);
        return $stmt->fetch();
    }

    // Get all submissions for a company's inventories (for employer review)
    public function getSubmissionsByCompany(int $companyId)
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, i.name as inventory_name, CONCAT(u.first_name, ' ', u.last_name) as worker_name, u.email as worker_email
            FROM inventory_submissions s 
            JOIN inventories i ON s.inventory_id = i.id 
            JOIN users u ON s.user_id = u.id
            WHERE i.company_id = ? 
            ORDER BY 
                CASE s.status WHEN 'pending' THEN 0 ELSE 1 END,
                s.created_at DESC
        ");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }
    // Create a new submission (e.g. for worker signaling completion)
    public function createSubmission(int $inventoryId, int $userId, string $payload, string $status = 'pending'): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO inventory_submissions (inventory_id, user_id, payload, status) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$inventoryId, $userId, $payload, $status]);
    }

    // Get submission for specific user and inventory
    public function getUserSubmission(int $inventoryId, int $userId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inventory_submissions WHERE inventory_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$inventoryId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
