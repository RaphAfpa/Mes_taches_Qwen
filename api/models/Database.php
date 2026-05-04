<?php
// api/models/Database.php

/**
 * Classe Database
 *
 * Gère la connexion à la base de données MySQL en utilisant PDO.
 * Son rôle est de fournir une instance de connexion à la base de données
 * qui peut être utilisée par d'autres modèles pour interagir avec les données.
 *
 * Pourquoi préférable :
 * - Centralise la logique de connexion à la base de données, facilitant la maintenance.
 * - Utilise PDO (PHP Data Objects), une interface légère et cohérente pour accéder aux bases de données.
 *   PDO est préférable aux extensions spécifiques comme `mysqli` car il supporte plusieurs types de bases de données
 *   et facilite l'utilisation de requêtes préparées, essentielles pour prévenir les injections SQL.
 * - Charge les identifiants de connexion via des variables d'environnement, ce qui est une bonne pratique de sécurité.
 *   Cela évite de coder en dur des informations sensibles dans le code source, rendant l'application plus sécurisée
 *   et plus flexible pour différents environnements (développement, production).
 */
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    /**
     * Constructeur de la classe Database.
     *
     * Initialise les propriétés de connexion à la base de données en récupérant
     * les valeurs des variables d'environnement. Si une variable n'est pas définie,
     * une valeur par défaut est utilisée (utile pour le développement local).
     *
     * Rôle : Préparer les informations nécessaires avant d'établir la connexion.
     * Pourquoi préférable : Permet une configuration flexible et sécurisée des identifiants.
     * Les variables d'environnement sont la méthode recommandée pour gérer les secrets
     * en production, car elles ne sont pas stockées dans le système de contrôle de version.
     */
    public function __construct() {
        // Charge les variables d'environnement pour la connexion à la base de données.
        // getenv() est utilisé pour récupérer les variables d'environnement définies au niveau du serveur.
        // L'opérateur '?:' (Elvis operator) fournit une valeur par défaut si la variable d'environnement n'est pas définie.
        $this->host = getenv('DB_HOST') ?: 'localhost'; // Hôte de la base de données
        $this->db_name = getenv('DB_NAME') ?: 'ezoi4288_mestaches'; // Nom de la base de données
        $this->username = getenv('DB_USER') ?: 'ezoi4288_mesTaches'; // Nom d'utilisateur de la base de données
        $this->password = getenv('DB_PASSWORD') ?: 'JpIKcFzP2tVbmhattG'; // Mot de passe de la base de données
    }

    /**
     * Établit et retourne une connexion à la base de données.
     *
     * Tente de créer une nouvelle instance PDO pour se connecter à la base de données.
     * En cas d'échec de la connexion, une exception PDOException est capturée.
     *
     * Rôle : Fournir une connexion active à la base de données aux autres classes (modèles).
     * Pourquoi préférable :
     * - Utilise un bloc try-catch pour gérer les erreurs de connexion de manière robuste.
     * - Définit l'encodage des caractères en UTF-8 (`set names utf8`) pour assurer la bonne gestion
     *   des caractères spéciaux et éviter les problèmes d'affichage.
     * - En cas d'erreur en production, l'erreur est loggée (`error_log`) plutôt que d'être affichée
     *   directement à l'utilisateur, ce qui est une bonne pratique de sécurité pour ne pas divulguer
     *   des informations sensibles sur l'infrastructure.
     */
    public function getConnection() {
        $this->conn = null; // Réinitialise la connexion à chaque appel pour s'assurer d'une nouvelle tentative si nécessaire.
        try {
            // Crée une nouvelle instance PDO pour la connexion.
            $this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->db_name, $this->username, $this->password);
            // Définit le mode d'erreur de PDO pour lancer des exceptions en cas d'erreur SQL.
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Définit l'encodage des caractères pour la connexion.
            $this->conn->exec('set names utf8');
        } catch(PDOException $exception) {
            // Log l'erreur de connexion pour le débogage interne sans l'exposer à l'utilisateur final.
            error_log('Database connection error: ' . $exception->getMessage());
            // Affiche un message d'erreur générique pour l'utilisateur (peut être retiré en production finale).
            echo 'Connection error: ' . $exception->getMessage();
        }
        return $this->conn; // Retourne l'objet de connexion PDO.
    }
}
?>