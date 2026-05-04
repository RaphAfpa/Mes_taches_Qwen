<?php
// api/controllers/TaskController.php

/**
 * Classe TaskController
 *
 * Gère les requêtes API relatives aux tâches. Ce contrôleur agit comme un intermédiaire
 * entre le routeur et le modèle `Task`, traitant les données de la requête,
 * appelant les méthodes appropriées du modèle et formatant les réponses JSON.
 *
 * Rôle : Orchestrer les opérations CRUD sur les tâches en réponse aux requêtes HTTP,
 * en assurant l'authentification de l'utilisateur et la logique métier spécifique aux tâches.
 *
 * Pourquoi préférable :
 * - Sépare la logique de gestion des requêtes (contrôleur) de la logique d'accès aux données (modèle).
 * - Centralise la validation des entrées et la gestion des réponses HTTP pour les tâches.
 * - Intègre la logique d'authentification basée sur la session pour s'assurer que seules
 *   les tâches de l'utilisateur connecté sont manipulées.
 * - Implémente une logique métier pour l'assignation automatique du statut des tâches.
 */
class TaskController {
    private $task; // Instance du modèle Task

    /**
     * Constructeur de la classe TaskController.
     *
     * @param PDO $db L'objet de connexion à la base de données (PDO).
     * Rôle : Initialiser le contrôleur avec une instance du modèle Task,
     * lui permettant d'interagir avec la base de données via ce modèle.
     * Pourquoi préférable : Utilise l'injection de dépendances pour le modèle Task,
     * ce qui rend le contrôleur plus flexible et testable.
     */
    public function __construct($db) {
        $this->task = new Task($db);
    }

    /**
     * Assignation automatique du statut d'une tâche basée sur ses dates de début et d'échéance.
     *
     * @param string|null $start_date Date de début de la tâche (format 'YYYY-MM-DD').
     * @param string|null $due_date Date d'échéance de la tâche (format 'YYYY-MM-DD').
     * @param string|null $current_status Statut actuel de la tâche. Si 'Terminée', il ne sera pas modifié.
     * @return string Le statut calculé ('Terminée', 'Dépassée', 'Prévue', 'En cours').
     *
     * Rôle : Appliquer la logique métier pour déterminer le statut d'une tâche.
     * Pourquoi préférable :
     * - Centralise la logique de détermination du statut, évitant la duplication de code.
     * - Utilise des objets `DateTime` pour des comparaisons de dates robustes et précises.
     * - Gère les cas spécifiques comme une tâche déjà 'Terminée' qui ne doit pas changer de statut automatiquement.
     * - Fournit une classification claire des tâches basée sur leur temporalité.
     */
    private function assignAutomaticStatus($start_date, $due_date, $current_status = null) {
        $today = new DateTime();
        $today->setTime(0, 0, 0); // Normalise à minuit pour des comparaisons de jour entier

        $startDateObj = $start_date ? new DateTime($start_date) : null;
        if ($startDateObj) $startDateObj->setTime(0, 0, 0);

        $dueDateObj = $due_date ? new DateTime($due_date) : null;
        if ($dueDateObj) $dueDateObj->setTime(0, 0, 0);

        // 1. Règle : Si le statut est déjà 'Terminée', ne le change pas automatiquement.
        if ($current_status === 'Terminée') {
            return 'Terminée';
        }

        // 2. Règle : 'Dépassée' (Date d'échéance < aujourd'hui)
        // La date d'échéance est passée.
        if ($dueDateObj && $dueDateObj < $today) {
            return 'Dépassée';
        }

        // 3. Règle : 'Prévue' (Date de début > aujourd'hui)
        // La tâche n'a pas encore commencé.
        if ($startDateObj && $startDateObj > $today) {
            return 'Prévue';
        }

        // 4. Règle : 'En cours'
        // Si une tâche n'est ni 'Terminée', ni 'Dépassée', ni 'Prévue', elle est 'En cours'.
        // Cela couvre :
        // - Les tâches avec date de début <= aujourd'hui et date d'échéance >= aujourd'hui (inclusif)
        // - Les tâches sans date de début mais avec date d'échéance >= aujourd'hui
        // - Les tâches avec date de début <= aujourd'hui et sans date d'échéance (en cours indéfiniment)
        return 'En cours';
    }

    /**
     * Gère la requête GET pour récupérer les tâches de l'utilisateur authentifié.
     *
     * Applique des filtres optionnels (catégorie, statut) et un tri.
     * Retourne une liste de tâches au format JSON ou un message d'erreur/absence de tâches.
     *
     * Rôle : Fournir les tâches de l'utilisateur via l'API.
     * Pourquoi préférable :
     * - Vérifie l'authentification de l'utilisateur via la session avant de traiter la requête.
     * - Utilise le modèle `Task` pour interagir avec la base de données, respectant la séparation des préoccupations.
     * - Gère les paramètres de requête GET pour le filtrage et le tri, offrant de la flexibilité.
     * - Retourne des codes de statut HTTP appropriés (200 OK, 401 Unauthorized, 404 Not Found).
     */
    public function getTasks() {
        session_start(); // Démarre ou reprend la session
        // Vérifie si l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); // Non autorisé
            echo json_encode(array("message" => "Non autorisé. Veuillez vous connecter."));
            return;
        }

        $user_id = $_SESSION['user_id'];
        $filters = [];
        $sort = 'created_at DESC'; // Tri par défaut

        // Récupère les filtres et le tri des paramètres GET
        if (isset($_GET['category']) && $_GET['category'] !== 'all') {
            $filters['category'] = $_GET['category'];
        }
        if (isset($_GET['status']) && $_GET['status'] !== 'all') {
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }

        // Lit les tâches via le modèle Task
        $stmt = $this->task->read($user_id, $filters, $sort);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $tasks_arr = array();
            // Récupère chaque ligne de résultat et la formate en tableau associatif
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row); // Extrait les variables du tableau $row (ex: $id, $name)
                $task_item = array(
                    "id" => $id,
                    "user_id" => $user_id,
                    "name" => $name,
                    "category" => $category,
                    "status" => $status,
                    "start_date" => $start_date,
                    "due_date" => $due_date,
                    "description" => $description,
                    "created_at" => $created_at
                );
                array_push($tasks_arr, $task_item);
            }
            http_response_code(200); // OK
            echo json_encode(array("tasks" => $tasks_arr));
        } else {
            // Retourne 200 OK avec un tableau vide et un message lorsque aucune tâche n'est trouvée.
            // C'est une meilleure pratique pour les API que de retourner un 404 pour une requête valide sans résultat.
            http_response_code(200); // OK
            echo json_encode(array("message" => "Aucune tâche trouvée.", "tasks" => []));
        }
    }

    /**
     * Gère la requête POST pour créer une nouvelle tâche.
     *
     * Récupère les données de la tâche depuis le corps de la requête JSON,
     * assigne automatiquement le statut et tente de créer la tâche via le modèle.
     *
     * Rôle : Permettre aux utilisateurs authentifiés de créer de nouvelles tâches.
     * Pourquoi préférable :
     * - Vérifie l'authentification de l'utilisateur.
     * - Valide la présence du nom de la tâche, qui est obligatoire.
     * - Utilise `json_decode(file_get_contents("php://input"))` pour lire les données JSON du corps de la requête,
     *   ce qui est standard pour les API RESTful.
     * - Intègre la logique d'assignation automatique du statut, simplifiant la logique côté client.
     * - Retourne des codes de statut HTTP appropriés (201 Created, 400 Bad Request, 401 Unauthorized, 503 Service Unavailable).
     */
    public function createTask() {
        session_start(); // Démarre ou reprend la session
        // Vérifie si l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); // Non autorisé
            echo json_encode(array("message" => "Non autorisé. Veuillez vous connecter."));
            return;
        }

        $data = json_decode(file_get_contents("php://input")); // Récupère les données JSON de la requête

        // Vérifie si le nom de la tâche est fourni
        if (!empty($data->name)) {
            $this->task->user_id = $_SESSION['user_id'];
            $this->task->name = $data->name;
            $this->task->category = isset($data->category) ? $data->category : null;
            $this->task->start_date = isset($data->start_date) ? $data->start_date : null;
            $this->task->due_date = isset($data->due_date) ? $data->due_date : null;
            $this->task->description = isset($data->description) ? $data->description : null;

            // Assigne le statut automatiquement en fonction des dates
            $this->task->status = $this->assignAutomaticStatus($this->task->start_date, $this->task->due_date);

            // Tente de créer la tâche via le modèle
            if ($this->task->create()) {
                http_response_code(201); // Créé
                echo json_encode(array("message" => "Tâche créée avec succès."));
            } else {
                http_response_code(503); // Service indisponible (erreur serveur)
                echo json_encode(array("message" => "Impossible de créer la tâche."));
            }
        } else {
            http_response_code(400); // Mauvaise requête
            echo json_encode(array("message" => "Nom de tâche manquant."));
        }
    }

    /**
     * Gère la requête PUT pour mettre à jour une tâche existante.
     *
     * Récupère les données de la tâche depuis le corps de la requête JSON,
     * met à jour le statut (en respectant le statut 'Terminée' si fourni)
     * et tente de modifier la tâche via le modèle.
     *
     * Rôle : Permettre aux utilisateurs authentifiés de modifier leurs tâches.
     * Pourquoi préférable :
     * - Vérifie l'authentification de l'utilisateur et l'appartenance de la tâche.
     * - Gère les erreurs de parsing JSON.
     * - Permet de mettre à jour le statut manuellement à 'Terminée' ou de le laisser
     *   être assigné automatiquement.
     * - Retourne des codes de statut HTTP appropriés (200 OK, 400 Bad Request, 401 Unauthorized, 503 Service Unavailable).
     */
    public function updateTask() {
        session_start(); // Démarre ou reprend la session
        // Vérifie si l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); // Non autorisé
            echo json_encode(array("message" => "Non autorisé. Veuillez vous connecter."));
            return;
        }

        $data = json_decode(file_get_contents("php://input")); // Récupère les données JSON de la requête
        // Vérifie les erreurs de parsing JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400); // Mauvaise requête
            echo json_encode(array("message" => "Erreur de parsing JSON."));
            return;
        }

        // Vérifie si l'ID et le nom de la tâche sont fournis
        if (!empty($data->id) && !empty($data->name)) {
            $this->task->id = $data->id;
            $this->task->user_id = $_SESSION['user_id']; // S'assure que l'utilisateur possède la tâche
            $this->task->name = $data->name;
            $this->task->category = isset($data->category) ? $data->category : null;
            $this->task->start_date = isset($data->start_date) ? $data->start_date : null;
            $this->task->due_date = isset($data->due_date) ? $data->due_date : null;
            $this->task->description = isset($data->description) ? $data->description : null;

            // Détermine le statut : si le statut entrant est 'Terminée', le conserve. Sinon, l'assigne automatiquement.
            $incoming_status = isset($data->status) ? $data->status : null;
            if ($incoming_status === 'Terminée') {
                $this->task->status = 'Terminée';
            } else {
                $this->task->status = $this->assignAutomaticStatus($this->task->start_date, $this->task->due_date, $incoming_status);
            }

            // Tente de mettre à jour la tâche via le modèle
            if ($this->task->update()) {
                $response = array("message" => "Tâche mise à jour avec succès.");
                http_response_code(200); // OK
                echo json_encode($response);
            } else {
                $response = array("message" => "Impossible de mettre à jour la tâche. Assurez-vous que la tâche existe et vous appartient.");
                http_response_code(503); // Service indisponible
                echo json_encode($response);
            }
        } else {
            http_response_code(400); // Mauvaise requête
            echo json_encode(array("message" => "Données incomplètes (ID ou nom de tâche manquant)."));
        }
    }

    /**
     * Gère la requête DELETE pour supprimer une tâche.
     *
     * Récupère l'ID de la tâche depuis les paramètres GET ou le corps de la requête JSON,
     * et tente de supprimer la tâche via le modèle.
     *
     * Rôle : Permettre aux utilisateurs authentifiés de supprimer leurs tâches.
     * Pourquoi préférable :
     * - Vérifie l'authentification de l'utilisateur et l'appartenance de la tâche.
     * - Gère la récupération de l'ID de manière flexible (GET ou corps de requête).
     * - Retourne des codes de statut HTTP appropriés (200 OK, 400 Bad Request, 401 Unauthorized, 503 Service Unavailable).
     */
    public function deleteTask() {
        session_start(); // Démarre ou reprend la session
        // Vérifie si l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); // Non autorisé
            echo json_encode(array("message" => "Non autorisé. Veuillez vous connecter."));
            return;
        }

        // Récupère l'ID de la tâche depuis l'URL (GET) ou le corps de la requête (JSON)
        $id = isset($_GET['id']) ? $_GET['id'] : (json_decode(file_get_contents("php://input"))->id ?? null);

        // Vérifie si l'ID est fourni
        if (!empty($id)) {
            $this->task->id = $id;
            $this->task->user_id = $_SESSION['user_id']; // S'assure que l'utilisateur possède la tâche

            // Tente de supprimer la tâche via le modèle
            if ($this->task->delete()) {
                http_response_code(200); // OK
                echo json_encode(array("message" => "Tâche supprimée avec succès."));
            } else {
                http_response_code(503); // Service indisponible
                echo json_encode(array("message" => "Impossible de supprimer la tâche. Assurez-vous que la tâche existe et vous appartient."));
            }
        } else {
            http_response_code(400); // Mauvaise requête
            echo json_encode(array("message" => "ID de tâche manquant."));
        }
    }
}
?>