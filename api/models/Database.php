<?php
// api/models/Database.php

/**
 * Classe Database
 *
 * Rôle :
 * - Fournir une connexion PDO unique et fiable
 * - Être compatible avec un hébergement mutualisé
 * - Ne jamais dépendre de variables d’environnement
 */

class Database
{
    /**
     * ========================
     * PARAMÈTRES DE CONNEXION
     * ========================
     *
     * ⚠️ À REMPLACER par TES valeurs réelles
     * (phpMyAdmin / panneau hébergeur)
     */
    private string $host     = 'localhost';
    private string $dbName   = 'ezoi4288_mestaches';
    private string $username = 'ezoi4288_MesTaches2';
    private string $password = 'M;Kx9V-%ur-TjuZK';

    private ?PDO $conn = null;

    /**
     * Retourne une connexion PDO active
     *
     * @throws RuntimeException si la connexion échoue
     */
    public function getConnection(): PDO
    {
        if ($this->conn instanceof PDO) {
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

            return $this->conn;

        } catch (PDOException $e) {
            // Log serveur uniquement
            error_log('[DATABASE ERROR] ' . $e->getMessage());

            // Message volontairement générique côté API
            throw new RuntimeException(
                'Connexion à la base de données impossible'
            );
        }
    }
}