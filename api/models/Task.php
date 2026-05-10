<?php
// api/models/Task.php

class Task
{
    private PDO $db;
    private string $table = 'tasks';

    public ?int $id = null;
    public int $user_id;
    public string $name;
    public ?string $category = null;
    public string $status;
    public ?string $start_date = null;
    public ?string $due_date = null;
    public ?string $description = null;
    public ?string $created_at = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /* ========================
       RÈGLE MÉTIER
       ======================== */

    public function assignAutomaticStatus(
        ?string $startDate,
        ?string $dueDate,
        ?string $currentStatus = null
    ): string {
        if ($currentStatus === 'Terminée') {
            return 'Terminée';
        }

        $today = new DateTimeImmutable('today');

        if ($startDate !== null) {
            $start = new DateTimeImmutable($startDate);
            if ($today < $start) {
                return 'Prévue';
            }
        }

        if ($dueDate !== null) {
            $due = new DateTimeImmutable($dueDate);
            if ($today > $due) {
                return 'Dépassée';
            }
        }

        return 'En cours';
    }

    /* ========================
       CRUD
       ======================== */

    public function read(int $userId, array $filters = [], string $sort = 'created_at DESC'): array
    {
        $query = "
            SELECT id, user_id, name, category, status, start_date, due_date, description, created_at
            FROM {$this->table}
            WHERE user_id = :user_id
        ";

        $params = ['user_id' => $userId];

        if (!empty($filters['category'])) {
            $query .= " AND category = :category";
            $params['category'] = $filters['category'];
        }

        if (!empty($filters['status'])) {
            $query .= " AND LOWER(TRIM(status)) = LOWER(:status)";
            $params['status'] = trim($filters['status']);
        }

        $allowedSort = [
            'due_date ASC',
            'due_date DESC',
            'created_at ASC',
            'created_at DESC'
        ];

        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'created_at DESC';
        }

        $query .= " ORDER BY $sort";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(): bool
    {
        $query = "
            INSERT INTO {$this->table}
            (user_id, name, category, status, start_date, due_date, description)
            VALUES
            (:user_id, :name, :category, :status, :start_date, :due_date, :description)
        ";

        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            'user_id'     => $this->user_id,
            'name'        => $this->name,
            'category'    => $this->category,
            'status'      => $this->status,
            'start_date'  => $this->start_date,
            'due_date'    => $this->due_date,
            'description' => $this->description
        ]);
    }

    public function update(): bool
    {
        $query = "
            UPDATE {$this->table}
            SET
                name = :name,
                category = :category,
                status = :status,
                start_date = :start_date,
                due_date = :due_date,
                description = :description
            WHERE id = :id AND user_id = :user_id
        ";

        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'name'        => $this->name,
            'category'    => $this->category,
            'status'      => $this->status,
            'start_date'  => $this->start_date,
            'due_date'    => $this->due_date,
            'description' => $this->description
        ]);
    }

    public function delete(): bool
    {
        $query = "DELETE FROM {$this->table} WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            'id'      => $this->id,
            'user_id' => $this->user_id
        ]);
    }
}