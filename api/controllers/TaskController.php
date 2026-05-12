<?php
// api/controllers/TaskController.php

/**
 * TaskController
 *
 * Rôle :
 * - Gérer les actions API liées aux tâches
 * - Vérifier l’authentification de l’utilisateur
 * - Vérifier le token CSRF pour les actions mutatives
 * - Valider les données reçues avant de les transmettre au modèle
 *
 * Séparation des responsabilités :
 * - Le contrôleur valide les données utilisateur.
 * - Le modèle Task calcule le statut métier.
 * - Le modèle Task exécute les opérations SQL.
 */
class TaskController
{
    private Task $task;

    /**
     * Catégories autorisées dans l’application.
     *
     * Cette liste sert à éviter qu’un utilisateur envoie une catégorie
     * arbitraire depuis une requête modifiée manuellement.
     */
    private array $allowedCategories = [
        'Travail',
        'Bricolage',
        'Loisirs'
    ];

    public function __construct(PDO $db)
    {
        $this->task = new Task($db);
    }

    /* ========================
       UTILITAIRES
       ======================== */

    /**
     * Démarre la session PHP si elle n’est pas déjà active.
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Récupère et décode le corps JSON d’une requête.
     *
     * Si le JSON est invalide ou absent, retourne un tableau vide.
     */
    private function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Envoie une réponse JSON standardisée.
     *
     * Toutes les réponses API suivent la même structure :
     * - success : booléen
     * - data : données éventuelles
     * - message : message utilisateur ou technique contrôlé
     */
    private function sendResponse(
        bool $success,
        ?array $data = null,
        ?string $message = null,
        int $status = 200
    ): void {
        http_response_code($status);
        header('Content-Type: application/json');

        echo json_encode([
            'success' => $success,
            'data'    => $data,
            'message' => $message
        ]);

        exit;
    }

    /**
     * Vérifie que l’utilisateur est authentifié.
     *
     * @return int ID de l’utilisateur connecté.
     */
    private function requireAuth(): int
    {
        $this->startSession();

        if (empty($_SESSION['user_id'])) {
            $this->sendResponse(false, null, 'Non autorisé', 401);
        }

        return (int) $_SESSION['user_id'];
    }

    /**
     * Vérifie le token CSRF pour les actions mutatives.
     *
     * Important :
     * - Les en-têtes HTTP sont insensibles à la casse.
     * - On normalise donc les clés en minuscules avant lecture.
     */
    private function requireCsrfToken(): void
    {
        $this->startSession();

        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        $token = $headers['x-csrf-token'] ?? '';

        if (
            empty($_SESSION['csrf_token']) ||
            empty($token) ||
            !hash_equals($_SESSION['csrf_token'], $token)
        ) {
            $this->sendResponse(false, null, 'CSRF invalide', 403);
        }
    }

    /**
     * Calcule la longueur d’une chaîne.
     *
     * Utilise mb_strlen si disponible pour mieux gérer les caractères accentués.
     */
    private function textLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }

    /**
     * Vérifie qu’une date respecte le format YYYY-MM-DD
     * et correspond réellement à une date valide.
     */
    private function isValidDate(?string $date): bool
    {
        if ($date === null || $date === '') {
            return false;
        }

        $parsedDate = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        $errors = DateTimeImmutable::getLastErrors();

        return $parsedDate !== false
            && $errors['warning_count'] === 0
            && $errors['error_count'] === 0
            && $parsedDate->format('Y-m-d') === $date;
    }

    /**
     * Normalise et valide les données d’une tâche.
     *
     * Rôle :
     * - Centraliser les règles de validation serveur
     * - Éviter de dupliquer la logique entre createTask() et updateTask()
     *
     * @param array $data Données reçues depuis le JSON.
     * @param bool $requireId Indique si l’ID est obligatoire (cas update).
     *
     * @return array Données validées et normalisées.
     */
    private function validateTaskData(array $data, bool $requireId = false): array
    {
        if ($requireId && empty($data['id'])) {
            $this->sendResponse(false, null, 'ID de tâche requis', 400);
        }

        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            $this->sendResponse(false, null, 'Nom de tâche requis', 400);
        }

        if ($this->textLength($name) < 3) {
            $this->sendResponse(false, null, 'Le nom de la tâche doit contenir au moins 3 caractères', 400);
        }

        if ($this->textLength($name) > 50) {
            $this->sendResponse(false, null, 'Le nom de la tâche ne doit pas dépasser 50 caractères', 400);
        }

        $category = trim((string) ($data['category'] ?? ''));

        if ($category === '') {
            $this->sendResponse(false, null, 'Catégorie requise', 400);
        }

        if (!in_array($category, $this->allowedCategories, true)) {
            $this->sendResponse(false, null, 'Catégorie non autorisée', 400);
        }

        $startDate = trim((string) ($data['start_date'] ?? ''));
        $dueDate = trim((string) ($data['due_date'] ?? ''));

        /*
         * La date de début est facultative.
         * Une valeur vide est normalisée en null.
         */
        $normalizedStartDate = $startDate === '' ? null : $startDate;

        if ($normalizedStartDate !== null && !$this->isValidDate($normalizedStartDate)) {
            $this->sendResponse(false, null, 'Date de début invalide', 400);
        }

        /*
         * La date d’échéance est obligatoire.
         * Sans elle, le statut automatique ne peut pas être calculé correctement.
         */
        if ($dueDate === '') {
            $this->sendResponse(false, null, 'Date d’échéance requise', 400);
        }

        if (!$this->isValidDate($dueDate)) {
            $this->sendResponse(false, null, 'Date d’échéance invalide', 400);
        }

        /*
         * Si une date de début est renseignée, elle ne peut pas être postérieure
         * à la date d’échéance.
         */
        if ($normalizedStartDate !== null) {
            $start = new DateTimeImmutable($normalizedStartDate);
            $due = new DateTimeImmutable($dueDate);

            if ($start > $due) {
                $this->sendResponse(
                    false,
                    null,
                    'La date de début ne peut pas être postérieure à la date d’échéance',
                    400
                );
            }
        }

        /*
         * La description est facultative.
         * Une description vide devient une chaîne vide.
         */
        $description = trim((string) ($data['description'] ?? ''));

        if ($this->textLength($description) > 500) {
            $this->sendResponse(false, null, 'La description ne doit pas dépasser 500 caractères', 400);
        }

        return [
            'id'          => $requireId ? (int) $data['id'] : null,
            'name'        => $name,
            'category'    => $category,
            'start_date'  => $normalizedStartDate,
            'due_date'    => $dueDate,
            'description' => $description
        ];
    }

    /* ========================
       TÂCHES
       ======================== */

    /**
     * Récupère les tâches de l’utilisateur connecté.
     */
    public function getTasks(): void
    {
        $userId = $this->requireAuth();

        $filters = [];
        $sort = $_GET['sort'] ?? 'created_at DESC';

        if (!empty($_GET['category']) && $_GET['category'] !== 'all') {
            $filters['category'] = $_GET['category'];
        }

        if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
            $filters['status'] = $_GET['status'];
        }

        $tasks = $this->task->read($userId, $filters, $sort);

        $this->sendResponse(true, $tasks);
    }

    /**
     * Crée une nouvelle tâche.
     *
     * Validation :
     * - effectuée côté serveur ;
     * - indépendante de la validation HTML / JavaScript ;
     * - indispensable pour garantir l’intégrité des données.
     */
    public function createTask(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrfToken();

        $data = $this->getJsonBody();
        $validatedData = $this->validateTaskData($data);

        $this->task->user_id = $userId;
        $this->task->name = $validatedData['name'];
        $this->task->category = $validatedData['category'];
        $this->task->start_date = $validatedData['start_date'];
        $this->task->due_date = $validatedData['due_date'];
        $this->task->description = $validatedData['description'];

        $this->task->status = $this->task->assignAutomaticStatus(
            $this->task->start_date,
            $this->task->due_date
        );

        if (!$this->task->create()) {
            $this->sendResponse(false, null, 'Erreur lors de la création', 503);
        }

        $this->sendResponse(true, null, 'Tâche créée', 201);
    }

    /**
     * Met à jour une tâche existante.
     *
     * Si le statut reçu est "Terminée", il est conservé comme statut manuel.
     * Sinon, le statut est recalculé automatiquement à partir des dates validées.
     */
    public function updateTask(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrfToken();

        $data = $this->getJsonBody();
        $validatedData = $this->validateTaskData($data, true);

        $this->task->id = $validatedData['id'];
        $this->task->user_id = $userId;
        $this->task->name = $validatedData['name'];
        $this->task->category = $validatedData['category'];
        $this->task->start_date = $validatedData['start_date'];
        $this->task->due_date = $validatedData['due_date'];
        $this->task->description = $validatedData['description'];

        if (($data['status'] ?? '') === 'Terminée') {
            $this->task->status = 'Terminée';
        } else {
            $this->task->status = $this->task->assignAutomaticStatus(
                $this->task->start_date,
                $this->task->due_date,
                $data['status'] ?? null
            );
        }

        if (!$this->task->update()) {
            $this->sendResponse(false, null, 'Erreur lors de la mise à jour', 503);
        }

        $this->sendResponse(true, null, 'Tâche mise à jour');
    }

    /**
     * Supprime une tâche appartenant à l’utilisateur connecté.
     */
    public function deleteTask(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrfToken();

        $data = $this->getJsonBody();

        if (empty($data['id'])) {
            $this->sendResponse(false, null, 'ID requis pour la suppression', 400);
        }

        $this->task->id = (int) $data['id'];
        $this->task->user_id = $userId;

        if (!$this->task->delete()) {
            $this->sendResponse(false, null, 'Erreur lors de la suppression', 503);
        }

        $this->sendResponse(true, null, 'Tâche supprimée');
    }
}