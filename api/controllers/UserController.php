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

    /**
     * POST /api/login
     */
    public function login(): void
    {
        $this->startSession();

        $data = $this->getJsonInput();

        if (empty($data['username']) || empty($data['password'])) {
            $this->jsonResponse(false, 'Champs requis manquants', 400);
            return;
        }

        if (!$this->user->findByUsername($data['username'])) {
            $this->jsonResponse(false, 'Identifiants invalides', 401);
            return;
        }

        if (!password_verify($data['password'], $this->user->password)) {
            $this->jsonResponse(false, 'Identifiants invalides', 401);
            return;
        }

        // ✅ Protection contre la fixation de session
        session_regenerate_id(true);

        $_SESSION['user_id'] = $this->user->id;
        $_SESSION['username'] = $this->user->username;

        $this->jsonResponse(true, 'Connexion réussie');
    }

    /**
     * POST /api/register
     */
    public function register(): void
    {
        $data = $this->getJsonInput();

        if (
            empty($data['username']) ||
            empty($data['email']) ||
            empty($data['password'])
        ) {
            $this->jsonResponse(false, 'Champs requis manquants', 400);
            return;
        }

        if ($this->user->findByUsername($data['username'])) {
            $this->jsonResponse(false, 'Nom d’utilisateur déjà utilisé', 409);
            return;
        }

        if ($this->user->findByEmail($data['email'])) {
            $this->jsonResponse(false, 'Email déjà utilisé', 409);
            return;
        }

        $this->user->username = $data['username'];
        $this->user->email = $data['email'];
        $this->user->password = password_hash($data['password'], PASSWORD_DEFAULT);

        if (!$this->user->create()) {
            $this->jsonResponse(false, 'Erreur lors de la création du compte', 500);
            return;
        }

        $this->jsonResponse(true, 'Compte créé avec succès', 201);
    }

    /**
     * GET /api/profile
     */
    public function profile(): void
    {
        $this->startSession();

        if (empty($_SESSION['user_id'])) {
            $this->jsonResponse(false, 'Non authentifié', 401);
            return;
        }

        if (!$this->user->findById($_SESSION['user_id'])) {
            $this->jsonResponse(false, 'Utilisateur introuvable', 404);
            return;
        }

        $this->jsonResponse(true, 'Profil chargé', 200, [
            'id'       => $this->user->id,
            'username' => $this->user->username,
            'email'    => $this->user->email
        ]);
    }

    /**
     * GET /api/logout
     * ✅ LOGOUT CORRECT ET DÉFINITIF
     */
    public function logout(): void
    {
        $this->startSession();

        // ✅ Vider complètement la session
        $_SESSION = [];

        // ✅ Supprimer le cookie de session
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

        // ✅ Détruire côté serveur
        session_destroy();

        $this->jsonResponse(true, 'Déconnexion réussie');
    }

    /* =======================================================
       Méthodes utilitaires privées
       ======================================================= */

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    private function jsonResponse(
        bool $success,
        string $message,
        int $status = 200,
        array $data = []
    ): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'user'    => $data ?: null
        ]);
        exit;
    }
}