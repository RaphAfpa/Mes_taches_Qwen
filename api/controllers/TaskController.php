<?php
// api/controllers/TaskController.php

class TaskController
{
    private Task $task;

    public function __construct(PDO $db)
    {
        $this->task = new Task($db);
    }

    /* ========================
       UTILITAIRES
       ======================== */

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

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

    private function requireAuth(): int
    {
        $this->startSession();

        if (empty($_SESSION['user_id'])) {
            $this->sendResponse(false, null, 'Non autorisé', 401);
        }

        return (int) $_SESSION['user_id'];
    }

    /* ========================
       TÂCHES
       ======================== */

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

        $stmt = $this->task->read($userId, $filters, $sort);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->sendResponse(true, $tasks, null, 200);
    }

    public function createTask(): void
    {
        $userId = $this->requireAuth();
        $data = $this->getJsonBody();

        if (empty($data['name'])) {
            $this->sendResponse(false, null, 'Nom de tâche requis', 400);
        }

        $this->task->user_id = $userId;
        $this->task->name = $data['name'];
        $this->task->category = $data['category'] ?? null;
        $this->task->start_date = $data['start_date'] ?? null;
        $this->task->due_date = $data['due_date'] ?? null;
        $this->task->description = $data['description'] ?? null;

        $this->task->status = $this->task->assignAutomaticStatus(
            $this->task->start_date,
            $this->task->due_date
        );

        if (!$this->task->create()) {
            $this->sendResponse(false, null, 'Erreur lors de la création', 503);
        }

        $this->sendResponse(true, null, 'Tâche créée', 201);
    }

    public function updateTask(): void
    {
        $userId = $this->requireAuth();
        $data = $this->getJsonBody();

        if (empty($data['id']) || empty($data['name'])) {
            $this->sendResponse(false, null, 'Données incomplètes', 400);
        }

        $this->task->id = $data['id'];
        $this->task->user_id = $userId;
        $this->task->name = $data['name'];
        $this->task->category = $data['category'] ?? null;
        $this->task->start_date = $data['start_date'] ?? null;
        $this->task->due_date = $data['due_date'] ?? null;
        $this->task->description = $data['description'] ?? null;

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

    public function deleteTask(): void
    {
        $userId = $this->requireAuth();
        $data = $this->getJsonBody();

        if (empty($data['id'])) {
            $this->sendResponse(false, null, 'ID requis pour la suppression', 400);
        }

        $this->task->id = $data['id'];
        $this->task->user_id = $userId;

        if (!$this->task->delete()) {
            $this->sendResponse(false, null, 'Erreur lors de la suppression', 503);
        }

        $this->sendResponse(true, null, 'Tâche supprimée');
    }
}