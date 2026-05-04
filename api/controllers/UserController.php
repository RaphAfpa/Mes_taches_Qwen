<?php
// api/controllers/UserController.php

/**
 * Classe UserController
 *
 * Gère les requêtes API relatives à l'authentification des utilisateurs et à la gestion de leur profil.
 * Ce contrôleur agit comme un intermédiaire entre le routeur et le modèle `User`,
 * traitant les données de la requête, appelant les méthodes appropriées du modèle
 * et formatant les réponses JSON.
 *
 * Rôle : Orchestrer les opérations d'authentification (connexion, inscription, déconnexion)
 * et de gestion de profil (affichage, mise à jour, suppression de compte) en réponse aux requêtes HTTP,
 * en assurant la sécurité et la gestion des sessions.
 *
 * Pourquoi préférable :
 * - Sépare la logique de gestion des requêtes (contrôleur) de la logique d'accès aux données (modèle).
 * - Centralise la validation des entrées et la gestion des réponses HTTP pour les opérations utilisateur.
 * - Intègre la logique d'authentification et de session pour sécuriser l'accès aux fonctionnalités.
 * - Utilise des codes de statut HTTP appropriés pour chaque type de réponse (succès, erreur, non autorisé).
 */
class UserController {
    private $user; // Instance du modèle User

    /**
     * Constructeur de la classe UserController.
     *
     * @param PDO $db L'objet de connexion à la base de données (PDO).
     * Rôle : Initialiser le contrôleur avec une instance du modèle User,
     * lui permettant d'interagir avec la base de données via ce modèle.
     * Pourquoi préférable : Utilise l'injection de dépendances pour le modèle User,
     * ce qui rend le contrôleur plus flexible et testable.
     */
    public function __construct($db) {
        $this->user = new User($db);
    }

    /**
     * Gère la requête POST pour la connexion de l'utilisateur.
     *
     * Récupère le nom d'utilisateur et le mot de passe du corps de la requête JSON,
     * vérifie les identifiants et établit une session utilisateur en cas de succès.
     *
     * Rôle : Authentifier un utilisateur et démarrer une session.
     * Pourquoi préférable :
     * - Valide la présence des données de connexion.
     * - Utilise le modèle `User` pour vérifier les identifiants de manière sécurisée
     *   (recherche par nom d'utilisateur, vérification du mot de passe haché).
     * - Gère la session utilisateur (`$_SESSION`) pour maintenir l'état de connexion.
     * - Retourne des codes de statut HTTP appropriés (200 OK, 400 Bad Request, 401 Unauthorized).
     * - **Amélioration future :** Pour une sécurité accrue, il serait préférable d'appeler
     *   `session_regenerate_id(true);` après une connexion réussie pour prévenir la fixation de session.
     */
    public function login() {
        session_start(); // Démarre ou reprend la session
        $data = json_decode(file_get_contents("php://input")); // Récupère les données JSON de la requête

        // Vérifie si le nom d'utilisateur et le mot de passe sont fournis
        if (!empty($data->username) && !empty($data->password)) {
            $this->user->username = $data->username;

            // Tente de trouver l'utilisateur par nom d'utilisateur
            if ($this->user->findByUsername()) {
                // Vérifie le mot de passe fourni avec le mot de passe haché stocké
                if ($this->user->passwordVerify($data->password, $this->user->password)) {
                    $_SESSION['user_id'] = $this->user->id; // Stocke l'ID utilisateur en session
                    $_SESSION['username'] = $this->user->username; // Stocke le nom d'utilisateur en session
                    http_response_code(200); // OK
                    echo json_encode(array("message" => "Connexion réussie.", "user_id" => $this->user->id, "username" => $this->user->username));
                } else {
                    http_response_code(401); // Non autorisé
                    echo json_encode(array("message" => "Login ou mot de passe incorrect."));
                }
            } else {
                http_response_code(401); // Non autorisé
                echo json_encode(array("message" => "Login ou mot de passe incorrect."));
            }
        } else {
            http_response_code(400); // Mauvaise requête
            echo json_encode(array("message" => "Données incomplètes."));
        }
    }

    /**
     * Gère la requête POST pour l'inscription d'un nouvel utilisateur.
     *
     * Récupère le nom d'utilisateur, l'email et le mot de passe du corps de la requête JSON,
     * et tente de créer un nouvel utilisateur via le modèle.
     *
     * Rôle : Permettre aux nouveaux utilisateurs de s'inscrire.
     * Pourquoi préférable :
     * - Valide la présence des données d'inscription requises.
     * - Utilise le modèle `User` pour gérer la création de l'utilisateur, y compris
     *   la vérification des doublons et le hachage sécurisé du mot de passe.
     * - Retourne des codes de statut HTTP appropriés (201 Created, 400 Bad Request).
     */
    public function register() {
        $data = json_decode(file_get_contents("php://input")); // Récupère les données JSON de la requête

        // Vérifie si toutes les données requises sont fournies
        if (!empty($data->username) && !empty($data->email) && !empty($data->password)) {
            $this->user->username = $data->username;
            $this->user->email = $data->email;
            $this->user->password = $data->password;

            // Tente de créer l'utilisateur via le modèle
            if ($this->user->create()) {
                http_response_code(201); // Créé
                echo json_encode(array("message" => "Compte créé avec succès."));
            } else {
                http_response_code(400); // Mauvaise requête (ex: nom d'utilisateur/email déjà existant)
                echo json_encode(array("message" => "Impossible de créer le compte. Le nom d'utilisateur ou l'email existe déjà."));
            }
        } else {
            http_response_code(400); // Mauvaise requête
            echo json_encode(array("message" => "Données incomplètes."));
        }
    }

    /**
     * Gère la requête GET pour la déconnexion de l'utilisateur.
     *
     * Détruit la session utilisateur, mettant fin à la connexion.
     *
     * Rôle : Déconnecter un utilisateur.
     * Pourquoi préférable :
     * - Utilise les fonctions PHP standard `session_unset()` et `session_destroy()`
     *   pour nettoyer et terminer correctement la session, assurant que les données
     *   de session de l'utilisateur ne sont plus valides.
     * - Retourne un code de statut HTTP 200 OK.
     */
    public function logout() {
        session_start(); // Démarre ou reprend la session
        session_unset(); // Supprime toutes les variables de session
        session_destroy(); // Détruit la session
        http_response_code(200); // OK
        echo json_encode(array("message" => "Déconnexion réussie."));
    }

    /**
     * Gère la requête GET pour récupérer le profil de l'utilisateur authentifié.
     *
     * Récupère l'ID utilisateur de la session et utilise le modèle pour trouver
     * et retourner les informations du profil.
     *
     * Rôle : Fournir les informations du profil de l'utilisateur connecté.
     * Pourquoi préférable :
     * - Vérifie l'authentification de l'utilisateur via la session.
     * - Utilise le modèle `User` pour récupérer les données du profil de manière sécurisée.
     * - Retourne des codes de statut HTTP appropriés (200 OK, 401 Unauthorized, 404 Not Found).
     */
    public function getProfile() {
        session_start(); // Démarre ou reprend la session
        // Vérifie si l'utilisateur est connecté
        if (isset($_SESSION['user_id'])) {
            $this->user->id = $_SESSION['user_id'];
            // Tente de trouver l'utilisateur par ID
            if ($this->user->findById()) {
                http_response_code(200); // OK
                echo json_encode(array(
                    "id" => $this->user->id,
                    "username" => $this->user->username,
                    "email" => $this->user->email
                ));
            } else {
                http_response_code(404); // Non trouvé
                echo json_encode(array("message" => "Utilisateur non trouvé."));
            }
        }
        else {
            http_response_code(401); // Non autorisé
            echo json_encode(array("message" => "Non autorisé. Veuillez vous connecter."));
        }
    }

    /**
     * Gère la requête PUT pour mettre à jour le profil de l'utilisateur authentifié.
     *
     * Récupère les données du profil (nom d'utilisateur, email, et optionnellement mot de passe)
     * du corps de la requête JSON et tente de mettre à jour le profil via le modèle.
     *
     * Rôle : Permettre aux utilisateurs de modifier leurs informations de profil.
     * Pourquoi préférable :
     * - Vérifie l'authentification de l'utilisateur.
     * - Valide la présence des données requises pour la mise à jour.
     * - Permet la mise à jour conditionnelle du mot de passe (s'il est fourni, il est haché).
     * - Utilise le modèle `User` pour gérer la mise à jour de manière sécurisée.
     * - Retourne des codes de statut HTTP appropriés (200 OK, 400 Bad Request, 401 Unauthorized, 503 Service Unavailable).
     */
    public function updateProfile() {
        session_start(); // Démarre ou reprend la session
        // Vérifie si l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); // Non autorisé
            echo json_encode(array("message" => "Non autorisé. Veuillez vous connecter."));
            return;
        }

        $data = json_decode(file_get_contents("php://input")); // Récupère les données JSON de la requête

        // Vérifie si le nom d'utilisateur et l'email sont fournis
        if (!empty($data->username) && !empty($data->email)) {
            $this->user->id = $_SESSION['user_id']; // Assure que seul l'utilisateur connecté peut modifier son profil
            $this->user->username = $data->username;
            $this->user->email = $data->email;

            // Met à jour le mot de passe uniquement s'il est fourni dans la requête
            if (!empty($data->password)) {
                $this->user->password = $data->password;
            }

            // Tente de mettre à jour le profil via le modèle
            if ($this->user->update()) {
                $_SESSION['username'] = $this->user->username; // Met à jour le nom d'utilisateur en session si modifié
                http_response_code(200); // OK
                echo json_encode(array("message" => "Profil mis à jour avec succès."));
            } else {
                http_response_code(503); // Service indisponible
                echo json_encode(array("message" => "Impossible de mettre à jour le profil."));
            }
        } else {
            http_response_code(400); // Mauvaise requête
            echo json_encode(array("message" => "Données incomplètes."));
        }
    }

    /**
     * Gère la requête DELETE pour supprimer le compte de l'utilisateur authentifié.
     *
     * Récupère l'ID utilisateur de la session et tente de supprimer le compte via le modèle.
     * Détruit la session après la suppression réussie du compte.
     *
     * Rôle : Permettre aux utilisateurs de supprimer leur propre compte.
     * Pourquoi préférable :
     * - Vérifie l'authentification de l'utilisateur.
     * - Utilise le modèle `User` pour gérer la suppression du compte de manière sécurisée.
     * - Détruit la session après la suppression du compte pour s'assurer que l'utilisateur est déconnecté.
     * - Retourne des codes de statut HTTP appropriés (200 OK, 401 Unauthorized, 503 Service Unavailable).
     */
    public function deleteAccount() {
        session_start(); // Démarre ou reprend la session
        // Vérifie si l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); // Non autorisé
            echo json_encode(array("message" => "Non autorisé. Veuillez vous connecter."));
            return;
        }

        $this->user->id = $_SESSION['user_id']; // Assure que seul l'utilisateur connecté peut supprimer son compte

        // Tente de supprimer le compte via le modèle
        if ($this->user->delete()) {
            session_unset(); // Supprime toutes les variables de session
            session_destroy(); // Détruit la session
            http_response_code(200); // OK
            echo json_encode(array("message" => "Compte supprimé avec succès."));
        } else {
            http_response_code(503); // Service indisponible
            echo json_encode(array("message" => "Impossible de supprimer le compte."));
        }
    }
}
?>