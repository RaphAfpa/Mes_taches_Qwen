<?php
// api/models/Task.php

/**
 * Modèle Task
 *
 * Rôle :
 * - Représenter une tâche appartenant à un utilisateur
 * - Encapsuler les opérations CRUD liées à la table `tasks`
 * - Centraliser la règle métier de calcul automatique du statut
 *
 * Principe important :
 * - Le contrôleur valide les données reçues depuis l’utilisateur
 * - Le modèle calcule le statut à partir de dates déjà validées
 * - Le modèle ne décide pas si une requête utilisateur est valide ou non
 */
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

    /**
     * Calcule automatiquement le statut d’une tâche à partir des dates.
     *
     * Règles appliquées :
     * 1. Si le statut courant est "Terminée", il est conservé.
     *    Raison : "Terminée" est une action manuelle de l’utilisateur,
     *    pas un état calculé automatiquement.
     *
     * 2. Si la date d’échéance est dépassée, le statut devient "Dépassée".
     *    Raison : une tâche en retard doit être prioritaire sur les autres états,
     *    même si aucune date de début n’est renseignée.
     *
     * 3. Si la date de début est absente, le statut devient "À planifier".
     *    Raison : la tâche a une échéance, mais aucun début n’a été défini.
     *
     * 4. Si la date de début est dans le futur, le statut devient "Prévue".
     *    Raison : la tâche est planifiée pour commencer plus tard.
     *
     * 5. Dans les autres cas, le statut devient "En cours".
     *    Raison : aujourd’hui est compris dans la période active de la tâche.
     *
     * Important :
     * - Les comparaisons se font uniquement sur les dates, sans les heures.
     * - Le contrôleur doit avoir validé les formats avant d’appeler cette méthode.
     * - La date d’échéance est obligatoire côté contrôleur pour la création.
     *
     * @param string|null $startDate     Date de début au format YYYY-MM-DD, ou null.
     * @param string|null $dueDate       Date d’échéance au format YYYY-MM-DD.
     * @param string|null $currentStatus Statut courant éventuel.
     *
     * @return string Statut calculé.
     */
    public function assignAutomaticStatus(
        ?string $startDate,
        ?string $dueDate,
        ?string $currentStatus = null
    ): string {
        if ($currentStatus === 'Terminée') {
            return 'Terminée';
        }

        $today = new DateTimeImmutable('today');

        /*
         * Sécurité défensive :
         * même si le contrôleur doit déjà transformer une date de début vide en null,
         * cette normalisation évite qu’une chaîne vide soit interprétée comme une date.
         */
        $startDate = $startDate !== '' ? $startDate : null;
        $dueDate = $dueDate !== '' ? $dueDate : null;

        /*
         * Priorité 1 après "Terminée" :
         * si l’échéance est dépassée, la tâche est "Dépassée",
         * même si la date de début est absente.
         */
        if ($dueDate !== null) {
            $due = new DateTimeImmutable($dueDate);

            if ($today > $due) {
                return 'Dépassée';
            }
        }

        /*
         * Si aucune date de début n’est renseignée,
         * la tâche est considérée comme "À planifier".
         */
        if ($startDate === null) {
            return 'À planifier';
        }

        /*
         * Si la date de début est dans le futur,
         * la tâche est prévue mais pas encore active.
         */
        $start = new DateTimeImmutable($startDate);

        if ($today < $start) {
            return 'Prévue';
        }

        /*
         * À ce stade :
         * - la tâche n’est pas terminée ;
         * - l’échéance n’est pas dépassée ;
         * - une date de début existe ;
         * - la date de début est aujourd’hui ou dans le passé.
         *
         * Le statut est donc "En cours".
         */
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