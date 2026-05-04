<?php
// api/models/Task.php

/**
 * Classe Task
 *
 * Gère les opérations CRUD (Create, Read, Update, Delete) pour les tâches
 * dans la base de données. Chaque instance de cette classe représente une tâche
 * et interagit avec la table 'tasks'.
 *
 * Rôle : Fournir une interface orientée objet pour manipuler les données des tâches,
 * en assurant la sécurité et l'intégrité des données.
 *
 * Pourquoi préférable :
 * - Encapsule la logique d'accès aux données des tâches, séparant ainsi les préoccupations
 *   (modèle de données vs. logique métier/contrôleur).
 * - Utilise des requêtes préparées via PDO pour toutes les interactions avec la base de données,
 *   ce qui est la méthode standard et la plus efficace pour prévenir les injections SQL.
 * - Applique une sanitisation de base (`htmlspecialchars`, `strip_tags`) sur les données entrantes
 *   pour aider à prévenir les attaques XSS (Cross-Site Scripting) lors de l'affichage des données.
 *   Il est important de noter que cette sanitisation est principalement pour l'affichage et que
 *   les requêtes préparées protègent contre les injections SQL.
 */
class Task {
    private $conn;
    private $table_name = 'tasks';

    public $id;
    public $user_id;
    public $name;
    public $category;
    public $status;
    public $start_date; // Added start_date
    public $due_date;
    public $description; // Added description
    public $created_at;

    /**
     * Constructeur de la classe Task.
     *
     * @param PDO $db L'objet de connexion à la base de données (PDO).
     * Rôle : Injecter la dépendance de la connexion à la base de données,
     * permettant à la classe Task d'interagir avec la base de données.
     * Pourquoi préférable : Utilise l'injection de dépendances, ce qui rend la classe
     * plus testable, plus flexible et moins couplée à la manière dont la connexion
     * à la base de données est établie.
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Lit et retourne les tâches pour un utilisateur spécifique.
     *
     * Permet de filtrer les tâches par catégorie et statut, et de les trier.
     *
     * @param int $user_id L'ID de l'utilisateur dont on veut lire les tâches.
     * @param array $filters Tableau associatif de filtres (ex: ['category' => 'Travail', 'status' => 'En cours']).
     * @param string $sort_param Paramètre de tri (ex: 'date-asc', 'date-desc', 'created_at DESC').
     * @return PDOStatement Retourne un objet PDOStatement contenant les résultats de la requête.
     *
     * Rôle : Récupérer des données de tâches de manière flexible et sécurisée.
     * Pourquoi préférable :
     * - Utilise des requêtes préparées avec des paramètres nommés (`:user_id`, `:category`, `:status`)
     *   pour lier les valeurs, ce qui est crucial pour prévenir les injections SQL.
     * - Gère dynamiquement l'ajout de clauses WHERE pour les filtres, rendant la requête adaptable.
     * - Implémente un tri sécurisé en utilisant une liste blanche de colonnes de tri,
     *   empêchant ainsi les injections SQL via le paramètre de tri.
     * - Utilise `LOWER(TRIM(status))` pour une comparaison de statut insensible à la casse et aux espaces.
     */
    public function read($user_id, $filters = [], $sort_param = 'created_at DESC') {
        $query = "SELECT id, user_id, name, category, status, start_date, due_date, description, created_at
                FROM " . $this->table_name . "
                WHERE user_id = :user_id";

        // Ajoute les filtres à la requête si spécifiés
        if (isset($filters['category']) && !empty($filters['category'])) {
            $query .= " AND category = :category";
        }
        if (isset($filters['status']) && !empty($filters['status'])) {
            // Utilise LOWER() et TRIM() pour une comparaison insensible à la casse et aux espaces
            $query .= " AND LOWER(TRIM(status)) = LOWER(TRIM(:status))";
        }

        // Gère le tri de manière sécurisée
        $order_by = 'created_at DESC'; // Tri par défaut
        switch ($sort_param) {
            case 'date-asc':
                $order_by = 'due_date ASC';
                break;
            case 'date-desc':
                $order_by = 'due_date DESC';
                break;
            // Ajoutez d'autres options de tri si nécessaire ici
            default:
                // Si le paramètre de tri n'est pas reconnu, le tri par défaut est conservé.
                break;
        }
        $query .= " ORDER BY " . $order_by;

        $stmt = $this->conn->prepare($query);

        // Lie l'ID de l'utilisateur
        $stmt->bindParam(':user_id', $user_id);

        // Lie les filtres si présents
        if (isset($filters['category']) && !empty($filters['category'])) {
            $stmt->bindParam(':category', $filters['category']);
        }
        if (isset($filters['status']) && !empty($filters['status'])) {
            $trimmed_status = trim($filters['status']);
            $lower_trimmed_status = strtolower($trimmed_status);
            $stmt->bindParam(':status', $lower_trimmed_status);
        }

        $stmt->execute();

        return $stmt;
    }

    /**
     * Crée une nouvelle tâche dans la base de données.
     *
     * Les propriétés de l'objet Task (user_id, name, category, status, start_date, due_date)
     * doivent être définies avant d'appeler cette méthode.
     *
     * @return bool Vrai si la tâche a été créée avec succès, faux sinon.
     *
     * Rôle : Persister une nouvelle tâche dans la base de données.
     * Pourquoi préférable :
     * - Utilise une requête préparée pour insérer les données, protégeant contre les injections SQL.
     * - **Note sur la sanitisation :** Les données sont stockées brutes en base de données.
     *   La sanitisation (ex: `htmlspecialchars`) doit être appliquée lors de l'affichage
     *   pour prévenir les attaques XSS, et non à l'insertion pour préserver l'intégrité des données.
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    user_id = :user_id,
                    name = :name,
                    category = :category,
                    status = :status,
                    start_date = :start_date,
                    due_date = :due_date,
                    description = :description";

        $stmt = $this->conn->prepare($query);

        // Lie les valeurs aux paramètres de la requête préparée
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':start_date', $this->start_date);
        $stmt->bindParam(':due_date', $this->due_date);
        $stmt->bindParam(':description', $this->description);

        // Exécute la requête
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    /**
     * Met à jour une tâche existante dans la base de données.
     *
     * Les propriétés de l'objet Task (id, user_id, name, category, status, start_date, due_date)
     * doivent être définies avant d'appeler cette méthode. L'ID de l'utilisateur est utilisé
     * pour s'assurer que seul le propriétaire de la tâche peut la modifier.
     *
     * @return bool Vrai si la tâche a été mise à jour avec succès, faux sinon.
     *
     * Rôle : Modifier les informations d'une tâche existante.
     * Pourquoi préférable :
     * - Utilise une requête préparée pour la mise à jour, garantissant la sécurité contre les injections SQL.
     * - Inclut `user_id` dans la clause WHERE pour une vérification d'autorisation au niveau de la base de données,
     *   empêchant un utilisateur de modifier les tâches d'un autre utilisateur.
     * - **Note sur la sanitisation :** Les données sont stockées brutes en base de données.
     *   La sanitisation (ex: `htmlspecialchars`) doit être appliquée lors de l'affichage
     *   pour prévenir les attaques XSS, et non à l'insertion pour préserver l'intégrité des données.
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    name = :name,
                    category = :category,
                    status = :status,
                    start_date = :start_date,
                    due_date = :due_date,
                    description = :description
                WHERE
                    id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Lie les valeurs aux paramètres de la requête préparée
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':start_date', $this->start_date);
        $stmt->bindParam(':due_date', $this->due_date);
        $stmt->bindParam(':description', $this->description);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    /**
     * Supprime une tâche de la base de données.
     *
     * Les propriétés de l'objet Task (id, user_id) doivent être définies
     * avant d'appeler cette méthode. L'ID de l'utilisateur est utilisé
     * pour s'assurer que seul le propriétaire de la tâche peut la supprimer.
     *
     * @return bool Vrai si la tâche a été supprimée avec succès, faux sinon.
     *
     * Rôle : Retirer une tâche de la base de données.
     * Pourquoi préférable :
     * - Utilise une requête préparée pour la suppression, garantissant la sécurité.
     * - Inclut `user_id` dans la clause WHERE pour une vérification d'autorisation,
     *   empêchant un utilisateur de supprimer les tâches d'un autre utilisateur.
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Lie les valeurs aux paramètres de la requête préparée
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }
}
?>