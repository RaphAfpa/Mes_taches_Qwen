<?php
// api/controllers/UserController.php

class UserController
{
    private PDO $db;
    private User $user;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->user = new User($db);
    }

    /* ========================
       SESSION / UTILITAIRES
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

    /* ========================
       AUTHENTIFICATION
       ======================== */

    public function login(): void
    {
        $this->startSession();
        $data = $this->getJsonBody();

        if (empty($data['username']) || empty($data['password'])) {
            $this->sendResponse(false, null, 'Données incomplètes', 400);
        }

        $stmt = $this->db->prepare(
            'SELECT id, username, password FROM users WHERE username = :username LIMIT 1'
        );
        $stmt->bindParam(':username', $data['username']);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            $this->sendResponse(false, null, 'Identifiants invalides', 401);
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($data['password'], $user['password'])) {
            $this->sendResponse(false, null, 'Identifiants invalides', 401);
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        $this->sendResponse(true, [
            'id'       => $user['id'],
            'username' => $user['username']
        ], 'Connexion réussie');
    }

    public function logout(): void
    {
        $this->startSession();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        $this->sendResponse(true, null, 'Déconnexion réussie');
    }

    /* ========================
       PROFIL UTILISATEUR
       ======================== */

    public function getProfile(): void
    {
        $this->startSession();

        if (empty($_SESSION['user_id'])) {
            $this->sendResponse(false, null, 'Non autorisé', 401);
        }

        if (!$this->user->findById($_SESSION['user_id'])) {
            $this->sendResponse(false, null, 'Utilisateur introuvable', 404);
        }

        $this->sendResponse(true, [
            'id'       => $this->user->id,
            'username' => $this->user->username,
            'email'    => $this->user->email
        ]);
    }

    public function updateProfile(): void
    {
        $this->startSession();

        if (empty($_SESSION['user_id'])) {
            $this->sendResponse(false, null, 'Non autorisé', 401);
        }

        $data = $this->getJsonBody();

        if (empty($data['username']) || empty($data['email'])) {
            $this->sendResponse(false, null, 'Données incomplètes', 400);
        }

        $this->user->id = $_SESSION['user_id'];
        $this->user->username = $data['username'];
        $this->user->email = $data['email'];

        if (!empty($data['password'])) {
            $this->user->password = $data['password'];
        }

        if (!$this->user->update()) {
            $this->sendResponse(false, null, 'Échec de la mise à jour', 503);
        }

        $_SESSION['username'] = $this->user->username;

        $this->sendResponse(true, null, 'Profil mis à jour');
    }

    public function deleteAccount(): void
    {
        $this->startSession();

        if (empty($_SESSION['user_id'])) {
            $this->sendResponse(false, null, 'Non autorisé', 401);
        }

        $this->user->id = $_SESSION['user_id'];

        if (!$this->user->delete()) {
            $this->sendResponse(false, null, 'Échec de la suppression', 503);
        }

        $_SESSION = [];
        session_destroy();

        $this->sendResponse(true, null, 'Compte supprimé');
    }
}