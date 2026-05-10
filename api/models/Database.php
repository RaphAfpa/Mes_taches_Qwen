<?php
// api/models/Database.php

class Database
{
    private string $host;
    private string $dbName;
    private string $username;
    private string $password;

    private ?PDO $conn = null;

    public function __construct()
    {
        // ❗ AUCUN fallback de secret en dur
        $this->host     = getenv('DB_HOST') ?: 'localhost';
        $this->dbName   = getenv('DB_NAME') ?: '';
        $this->username = getenv('DB_USER') ?: '';
        $this->password = getenv('DB_PASSWORD') ?: '';

        if (
            empty($this->dbName) ||
            empty($this->username) ||
            empty($this->password)
        ) {
            throw new RuntimeException(
                'Configuration de base de données manquante (variables d’environnement)'
            );
        }
    }

    public function getConnection(): PDO
    {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                $this->host,
                $this->dbName
            );

            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            // ✅ log serveur uniquement
            error_log('[DB ERROR] ' . $e->getMessage());

            // ❌ aucune info sensible à l’utilisateur
            throw new RuntimeException('Erreur de connexion à la base de données.');
        }

        return $this->conn;
    }
}