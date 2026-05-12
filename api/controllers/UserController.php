<?php
// api/controllers/UserController.php

/**
 * UserController
 *
 * Rôle :
 * - Gérer l’authentification (login / register / logout)
 * - Gérer le profil utilisateur
 * - Appliquer les règles de sécurité (sessions, CSRF)
 */

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

    /**
     * Vérifie le token CSRF (insensible à la casse des headers).
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
        $stmt->execute(['username' => $data['username']]);

        if ($stmt->rowCount() === 0) {
            $this->sendResponse(false, null, 'Identifiants invalides', 401);
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($data['password'], $user['password'])) {
            $this->sendResponse(false, null, 'Identifiants invalides', 401);
        }

        session_regenerate_id(true);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];

        $this->sendResponse(true, [
            'id'       => $user['id'],
            'username' => $user['username']
        ], 'Connexion réussie');
    }

    public function register(): void
    {
        $this->startSession();
        $data = $this->getJsonBody();

        if (
            empty($data['username']) ||
            empty($data['email']) ||
            empty($data['password'])
        ) {
            $this->sendResponse(false, null, 'Données incomplètes', 400);
        }

        if ($this->user->findByUsername($data['username'])) {
            $this->sendResponse(false, null, 'Nom d’utilisateur déjà utilisé', 409);
        }

        if ($this->user->findByEmail($data['email'])) {
            $this->sendResponse(false, null, 'Adresse email déjà utilisée', 409);
        }

        $this->user->username = $data['username'];
        $this->user->email    = $data['email'];
        $this->user->password = $data['password'];

        if (!$this->user->create()) {
            $this->sendResponse(false, null, 'Erreur serveur lors de la création du compte', 500);
        }

        $this->sendResponse(true, null, 'Compte créé avec succès');
    }

    /**
     * Déconnexion utilisateur (CSRF requis).
     */
    public function logout(): void
    {
        $this->requireCsrfToken();

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

    public function profile(): void
    {
        $this->startSession();

        if (empty($_SESSION['user_id'])) {
            $this->sendResponse(false, null, 'Non autorisé', 401);
        }

        $userData = $this->user->findById($_SESSION['user_id']);

        if (!$userData) {
            $this->sendResponse(false, null, 'Utilisateur introuvable', 404);
        }

        $this->sendResponse(true, [
            'id'       => $userData['id'],
            'username' => $userData['username'],
            'email'    => $userData['email'],
            'csrf'     => $_SESSION['csrf_token']
        ]);
    }

    public function updateProfile(): void
    {
        $this->requireCsrfToken();

        if (empty($_SESSION['user_id'])) {
            $this->sendResponse(false, null, 'Non autorisé', 401);
        }

        $data = $this->getJsonBody();

        $this->user->id = $_SESSION['user_id'];
        $this->user->username = $data['username'] ?? $this->user->username;
        $this->user->email    = $data['email'] ?? $this->user->email;

        if (!empty($data['password'])) {
            $this->user->password = $data['password'];
        }

        if (!$this->user->update()) {
            $this->sendResponse(false, null, 'Échec de la mise à jour', 500);
        }

        $this->sendResponse(true, null, 'Profil mis à jour');
    }

    public function deleteAccount(): void
    {
        $this->requireCsrfToken();

        if (empty($_SESSION['user_id'])) {
            $this->sendResponse(false, null, 'Non autorisé', 401);
        }

        $this->user->id = $_SESSION['user_id'];

        if (!$this->user->delete()) {
            $this->sendResponse(false, null, 'Échec de la suppression', 500);
        }

        $_SESSION = [];
        session_destroy();

        $this->sendResponse(true, null, 'Compte supprimé');
    }
}
