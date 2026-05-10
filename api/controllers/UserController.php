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

    /**
     * Vérifie le token CSRF pour les actions mutatives.
     */
    private function requireCsrfToken(): void
    {
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';

        if (
            empty($_SESSION['csrf_token']) ||
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
        $stmt->bindParam(':username', $data['username']);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            $this->sendResponse(false, null, 'Identifiants invalides', 401);
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($data['password'], $user['password'])) {
            $this->sendResponse(false, null, 'Identifiants invalides', 401);
        }

        // Sécurité session
        session_regenerate_id(true);

        // Génération du token CSRF (une fois par session)
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

    public function logout(): void
    {
        $this->startSession();
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

    public function getProfile(): void
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
