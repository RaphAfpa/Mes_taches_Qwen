<?php
// api/controllers/UserController.php

/**
 * Contrôleur responsable des fonctionnalités liées aux utilisateurs.
 *
 * Rôle dans l’architecture :
 * - Reçoit les requêtes HTTP (via le routeur)
 * - Vérifie l’authentification via la session
 * - Ordonne les appels au modèle User
 * - Retourne des réponses JSON standardisées
 *
 * Ce contrôleur ne contient PAS :
 * - de logique SQL
 * - de logique de routage
 * - de logique d’affichage HTML
 */
class UserController
{
    /**
     * Instance du modèle User.
     *
     * Le contrôleur manipule le modèle pour accéder aux données,
     * mais ne connaît jamais les détails SQL.
     */
    private User $user;

    /**
     * Le contrôleur reçoit la connexion PDO depuis l’extérieur
     * (injectée par index.php).
     *
     * Intérêt pédagogique :
     * - évite les dépendances cachées
     * - facilite les tests
     * - respecte l’injection de dépendances sans framework
     */
    public function __construct(PDO $db)
    {
        $this->user = new User($db);
    }

    /**
     * Méthode centrale de réponse JSON standardisée.
     *
     * Objectif :
     * - garantir une structure de réponse identique pour toute l’API
     * - éviter la duplication de `http_response_code` et `json_encode`
     *
     * Structure retournée :
     * {
     *   success: bool,
     *   data: mixed|null,
     *   message: string|null,
     *   errors: mixed|null
     * }
     *
     * `exit` est volontaire :
     * une fois la réponse envoyée, on stoppe toute exécution.
     */
    private function sendResponse(
        bool $success,
        $data = null,
        ?string $message = null,
        $errors = null,
        int $statusCode = 200
    ): void {
        header('Content-Type: application/json');
        http_response_code($statusCode);

        echo json_encode([
            'success' => $success,
            'data'    => $data,
            'message' => $message,
            'errors'  => $errors
        ]);

        exit;
    }

    /**
     * Connexion utilisateur (POST /login)
     *
     * Étapes :
     * 1. Ouverture de la session
     * 2. Lecture du JSON reçu
     * 3. Vérification des champs obligatoires
     * 4. Recherche utilisateur
     * 5. Vérification du mot de passe
     * 6. Initialisation de la session
     * 7. Réponse JSON
     */
    public function login(): void
    {
        session_start();

        // Lecture du corps JSON brut de la requête
        $data = json_decode(file_get_contents('php://input'));

        // Validation minimale des entrées
        if (empty($data->username) || empty($data->password)) {
            $this->sendResponse(false, null, 'Données incomplètes.', null, 400);
        }

        // Utilisation de requêtes préparées pour éviter les injections SQL
        $query = 'SELECT id, username, password FROM users WHERE username = :username LIMIT 1';
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $data->username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Vérification du mot de passe
            if (!password_verify($data->password, $row['password'])) {
                $this->sendResponse(false, null, 'Identifiants invalides.', null, 401);
            }

            // Initialisation explicite de la session utilisateur
            $_SESSION['user_id']  = $row['id'];
            $_SESSION['username'] = $row['username'];

            // Régénération de l'ID de session pour prévenir la fixation de session
            session_regenerate_id(true);

            // Réponse en cas de succès
            $this->sendResponse(
                true,
                [
                    'id'       => $row['id'],
                    'username' => $row['username']
                ],
                'Connexion réussie.'
            );
        } else {
            $this->sendResponse(false, null, 'Identifiants invalides.', null, 401);
        }
    }

    /**
     * Inscription d’un nouvel utilisateur (POST /register)
     */
    public function register(): void
    {
        $data = json_decode(file_get_contents('php://input'));

        if (
            empty($data->username) ||
            empty($data->email) ||
            empty($data->password)
        ) {
            $this->sendResponse(false, null, 'Données incomplètes.', null, 400);
        }

        // Hydratation du modèle
        $this->user->username = $data->username;
        $this->user->email    = $data->email;
        $this->user->password = $data->password;

        // Le modèle gère :
        // - les doublons
        // - le hachage du mot de passe
        if (!$this->user->create()) {
            $this->sendResponse(false, null, 'Utilisateur déjà existant.', null, 400);
        }

        // 201 = ressource créée
        $this->sendResponse(true, null, 'Compte créé avec succès.', null, 201);
    }

    /**
     * Récupération du profil utilisateur connecté (GET /profile)
     */
    public function getProfile(): void
    {
        session_start();

        // Vérification d’authentification
        if (!isset($_SESSION['user_id'])) {
            $this->sendResponse(false, null, 'Non autorisé.', null, 401);
        }

        $this->user->id = $_SESSION['user_id'];

        if (!$this->user->findById()) {
            $this->sendResponse(false, null, 'Utilisateur non trouvé.', null, 404);
        }

        // Les données retournées sont volontairement limitées
        $this->sendResponse(true, [
            'id'       => $this->user->id,
            'username' => $this->user->username,
            'email'    => $this->user->email
        ]);
    }

    /**
     * Mise à jour du profil utilisateur (PUT /profile)
     */
    public function updateProfile(): void
    {
        session_start();

        if (!isset($_SESSION['user_id'])) {
            $this->sendResponse(false, null, 'Non autorisé.', null, 401);
        }

        $data = json_decode(file_get_contents('php://input'));

        if (empty($data->username) || empty($data->email)) {
            $this->sendResponse(false, null, 'Données incomplètes.', null, 400);
        }

        $this->user->id       = $_SESSION['user_id'];
        $this->user->username = $data->username;
        $this->user->email    = $data->email;

        // Le mot de passe est optionnel
        if (!empty($data->password)) {
            $this->user->password = $data->password;
        }

        if (!$this->user->update()) {
            $this->sendResponse(false, null, 'Échec de la mise à jour.', null, 503);
        }

        // Synchronisation de la session
        $_SESSION['username'] = $this->user->username;

        $this->sendResponse(true, null, 'Profil mis à jour.');
    }

    /**
     * Suppression du compte utilisateur (DELETE /profile)
     */
    public function deleteAccount(): void
    {
        session_start();

        if (!isset($_SESSION['user_id'])) {
            $this->sendResponse(false, null, 'Non autorisé.', null, 401);
        }

        $this->user->id = $_SESSION['user_id'];

        if (!$this->user->delete()) {
            $this->sendResponse(false, null, 'Échec de la suppression.', null, 503);
        }

        // Destruction explicite de la session
        session_destroy();

        $this->sendResponse(true, null, 'Compte supprimé.');
    }
}
?>
