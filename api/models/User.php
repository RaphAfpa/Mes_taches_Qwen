<?php
// api/models/User.php

/**
 * Classe User
 *
 * Gère les opérations CRUD (Create, Read, Update, Delete) pour les utilisateurs
 * dans la base de données. Chaque instance de cette classe représente un utilisateur
 * et interagit avec la table 'users'.
 *
 * Rôle : Fournir une interface orientée objet pour manipuler les données des utilisateurs,
 * en assurant la sécurité des informations sensibles comme les mots de passe.
 *
 * Pourquoi préférable :
 * - Encapsule la logique d'accès aux données des utilisateurs, séparant les préoccupations.
 * - Utilise des requêtes préparées via PDO pour toutes les interactions avec la base de données,
 *   prévenant ainsi les injections SQL.
 * - Implémente des pratiques de sécurité robustes pour la gestion des mots de passe (hachage).
 * - Fournit des méthodes pour rechercher des utilisateurs par différents critères (ID, nom d'utilisateur, email).
 */
class User {
    private $conn;
    private $table_name = 'users';

    public $id;
    public $username;
    public $email;
    public $password;
    public $created_at;

    /**
     * Constructeur de la classe User.
     *
     * @param PDO $db L'objet de connexion à la base de données (PDO).
     * Rôle : Injecter la dépendance de la connexion à la base de données,
     * permettant à la classe User d'interagir avec la base de données.
     * Pourquoi préférable : Utilise l'injection de dépendances, ce qui rend la classe
     * plus testable, plus flexible et moins couplée à la manière dont la connexion
     * à la base de données est établie.
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Crée un nouvel enregistrement utilisateur dans la base de données.
     *
     * Vérifie d'abord si le nom d'utilisateur ou l'email existe déjà pour éviter les doublons.
     * Hache le mot de passe avant de l'insérer dans la base de données.
     *
     * @return bool Vrai si l'utilisateur a été créé avec succès, faux sinon (ex: doublon).
     *
     * Rôle : Enregistrer de nouveaux utilisateurs de manière sécurisée.
     * Pourquoi préférable :
     * - Prévention des doublons : Vérifie l'existence du nom d'utilisateur et de l'email avant l'insertion.
     * - Sécurité des mots de passe : Utilise `password_hash()` avec l'algorithme `PASSWORD_BCRYPT`.
     *   C'est la méthode recommandée pour stocker les mots de passe de manière sécurisée, car elle génère
     *   un hachage fort et résistant aux attaques par force brute et aux tables arc-en-ciel.
     * - Requêtes préparées : Protège contre les injections SQL lors de l'insertion des données.
     * - Sanitisation : Applique `htmlspecialchars` et `strip_tags` pour nettoyer les données
     *   avant l'insertion, ce qui est une bonne pratique pour la prévention XSS.
     */
    public function create() {
        // Vérifie si le nom d'utilisateur ou l'email existe déjà
        if ($this->findByUsername() || $this->findByEmail()) {
            return false; // L'utilisateur existe déjà
        }

        $query = "INSERT INTO " . $this->table_name . "
                SET
                    username = :username,
                    email = :email,
                    password = :password";

        $stmt = $this->conn->prepare($query);

        // Sanitize data (nettoyage des données)
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password)); // Le mot de passe sera haché ensuite

        // Lie les valeurs aux paramètres de la requête préparée
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);

        // Hache le mot de passe avant de l'enregistrer dans la base de données
        // PASSWORD_BCRYPT est un algorithme de hachage fort et recommandé.
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        $stmt->bindParam(':password', $password_hash);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    /**
     * Recherche un utilisateur par son nom d'utilisateur.
     *
     * Les propriétés de l'objet User sont remplies avec les données de l'utilisateur trouvé.
     *
     * @return bool Vrai si un utilisateur est trouvé, faux sinon.
     *
     * Rôle : Récupérer les informations d'un utilisateur pour l'authentification ou la vérification.
     * Pourquoi préférable :
     * - Utilise une requête préparée (`WHERE username = ?`) pour une recherche sécurisée,
     *   prévenant les injections SQL.
     * - Limite le résultat à 1 (`LIMIT 0,1`) pour optimiser la performance et s'assurer
     *   de ne récupérer qu'un seul utilisateur.
     */
    public function findByUsername() {
        $query = "SELECT id, username, email, password, created_at
                FROM " . $this->table_name . "
                WHERE username = ?
                LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->username); // Lie le nom d'utilisateur au paramètre de la requête
        $stmt->execute();

        $num = $stmt->rowCount();

        if ($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            // Remplit les propriétés de l'objet avec les données de l'utilisateur trouvé
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password = $row['password']; // Mot de passe haché
            $this->created_at = $row['created_at'];
            return true;
        }

        return false;
    }

    /**
     * Recherche un utilisateur par son ID.
     *
     * Les propriétés de l'objet User sont remplies avec les données de l'utilisateur trouvé.
     *
     * @return bool Vrai si un utilisateur est trouvé, faux sinon.
     *
     * Rôle : Récupérer les informations d'un utilisateur via son identifiant unique.
     * Pourquoi préférable :
     * - Utilise une requête préparée (`WHERE id = ?`) pour une recherche sécurisée.
     * - Optimisé pour récupérer un seul enregistrement.
     */
    public function findById() {
        $query = "SELECT id, username, email, password, created_at
                FROM " . $this->table_name . "
                WHERE id = ?
                LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id); // Lie l'ID au paramètre de la requête
        $stmt->execute();

        $num = $stmt->rowCount();

        if ($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            // Remplit les propriétés de l'objet avec les données de l'utilisateur trouvé
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password = $row['password']; // Mot de passe haché
            $this->created_at = $row['created_at'];
            return true;
        }

        return false;
    }

    /**
     * Recherche un utilisateur par son adresse email.
     *
     * Les propriétés de l'objet User sont remplies avec les données de l'utilisateur trouvé.
     *
     * @return bool Vrai si un utilisateur est trouvé, faux sinon.
     *
     * Rôle : Vérifier l'unicité de l'email lors de l'inscription ou récupérer un utilisateur par email.
     * Pourquoi préférable :
     * - Utilise une requête préparée (`WHERE email = ?`) pour une recherche sécurisée.
     * - Optimisé pour récupérer un seul enregistrement.
     */
    public function findByEmail() {
        $query = "SELECT id, username, email, password, created_at
                FROM " . $this->table_name . "
                WHERE email = ?
                LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email); // Lie l'email au paramètre de la requête
        $stmt->execute();

        $num = $stmt->rowCount();

        if ($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            // Remplit les propriétés de l'objet avec les données de l'utilisateur trouvé
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password = $row['password']; // Mot de passe haché
            $this->created_at = $row['created_at'];
            return true;
        }

        return false;
    }

    /**
     * Met à jour un enregistrement utilisateur existant dans la base de données.
     *
     * Les propriétés de l'objet User (id, username, email) doivent être définies.
     * Le mot de passe est mis à jour uniquement s'il est fourni (non vide).
     *
     * @return bool Vrai si l'utilisateur a été mis à jour avec succès, faux sinon.
     *
     * Rôle : Modifier les informations d'un utilisateur.
     * Pourquoi préférable :
     * - Requêtes préparées : Protège contre les injections SQL.
     * - Mise à jour conditionnelle du mot de passe : Permet de modifier d'autres informations
     *   sans exiger un nouveau mot de passe, et hache le nouveau mot de passe s'il est fourni.
     * - Sanitisation : Applique `htmlspecialchars` et `strip_tags` pour nettoyer les données.
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    username = :username,
                    email = :email";

        // Si un nouveau mot de passe est fourni, il est inclus dans la mise à jour
        if (!empty($this->password)) {
            $query .= ", password = :password";
        }

        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize data (nettoyage des données)
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Lie les valeurs aux paramètres de la requête préparée
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':id', $this->id);

        // Hache et lie le nouveau mot de passe si fourni
        if (!empty($this->password)) {
            $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
            $stmt->bindParam(':password', $password_hash);
        }

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    /**
     * Supprime un enregistrement utilisateur de la base de données.
     *
     * La propriété `id` de l'objet User doit être définie avant d'appeler cette méthode.
     *
     * @return bool Vrai si l'utilisateur a été supprimé avec succès, faux sinon.
     *
     * Rôle : Retirer un utilisateur de la base de données.
     * Pourquoi préférable :
     * - Requête préparée : Protège contre les injections SQL.
     * - Sanitisation : Applique `htmlspecialchars` et `strip_tags` pour nettoyer l'ID.
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id)); // Nettoie l'ID avant de le lier
        $stmt->bindParam(1, $this->id); // Lie l'ID au paramètre de la requête

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si un mot de passe en texte clair correspond à un mot de passe haché.
     *
     * @param string $password Le mot de passe en texte clair fourni par l'utilisateur.
     * @param string $hashed_password Le mot de passe haché stocké dans la base de données.
     * @return bool Vrai si les mots de passe correspondent, faux sinon.
     *
     * Rôle : Valider les identifiants de connexion de l'utilisateur.
     * Pourquoi préférable :
     * - Utilise `password_verify()`, la fonction PHP recommandée pour vérifier les mots de passe hachés.
     *   Cette fonction est sécurisée car elle gère automatiquement le sel et l'algorithme de hachage
     *   utilisés lors de la création du hachage, et elle est conçue pour être résistante aux attaques
     *   par timing. Ne jamais utiliser `md5()` ou `sha1()` pour les mots de passe.
     */
    public function passwordVerify($password, $hashed_password) {
        return password_verify($password, $hashed_password);
    }
}
?>