<?php
// api/models/User.php

/**
 * Modèle User
 *
 * Rôle :
 * - Encapsuler tous les accès à la table `users`
 * - Ne jamais déclencher d’erreur fatale PHP
 * - Retourner true / false selon le succès des opérations
 */

class User
{
    private PDO $db;
    private string $table = 'users';

    public ?int $id = null;
    public string $username;
    public string $email;
    public string $password;
    public ?string $created_at = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /* ========================
       CREATE
       ======================== */

    /**
     * Crée un nouvel utilisateur.
     * Retourne true si succès, false sinon.
     * Toute erreur SQL est capturée pour éviter une erreur 500.
     */
    public function create(): bool
    {
        try {
            $query = "
                INSERT INTO {$this->table} (username, email, password)
                VALUES (:username, :email, :password)
            ";

            $stmt = $this->db->prepare($query);

            return $stmt->execute([
                'username' => $this->username,
                'email'    => $this->email,
                'password' => password_hash($this->password, PASSWORD_BCRYPT),
            ]);

        } catch (PDOException $e) {
            // Journalisation uniquement (ne jamais afficher à l'utilisateur)
            error_log('[USER CREATE ERROR] ' . $e->getMessage());
            return false;
        }
    }

    /* ========================
       READ
       ======================== */

    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, username, email, password, created_at
                 FROM {$this->table}
                 WHERE id = :id
                 LIMIT 1"
            );

            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ?: null;

        } catch (PDOException $e) {
            error_log('[USER FIND BY ID ERROR] ' . $e->getMessage());
            return null;
        }
    }

    public function findByUsername(string $username): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, username, email, password, created_at
                 FROM {$this->table}
                 WHERE username = :username
                 LIMIT 1"
            );

            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ?: null;

        } catch (PDOException $e) {
            error_log('[USER FIND BY USERNAME ERROR] ' . $e->getMessage());
            return null;
        }
    }

    public function findByEmail(string $email): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, username, email, password, created_at
                 FROM {$this->table}
                 WHERE email = :email
                 LIMIT 1"
            );

            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ?: null;

        } catch (PDOException $e) {
            error_log('[USER FIND BY EMAIL ERROR] ' . $e->getMessage());
            return null;
        }
    }

    /* ========================
       UPDATE
       ======================== */

    public function update(): bool
    {
        try {
            $fields = [
                'username' => $this->username,
                'email'    => $this->email,
                'id'       => $this->id
            ];

            $query = "
                UPDATE {$this->table}
                SET username = :username, email = :email
            ";

            if (!empty($this->password)) {
                $query .= ", password = :password";
                $fields['password'] = password_hash($this->password, PASSWORD_BCRYPT);
            }

            $query .= " WHERE id = :id";

            $stmt = $this->db->prepare($query);
            return $stmt->execute($fields);

        } catch (PDOException $e) {
            error_log('[USER UPDATE ERROR] ' . $e->getMessage());
            return false;
        }
    }

    /* ========================
       DELETE
       ======================== */

    public function delete(): bool
    {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM {$this->table} WHERE id = :id"
            );

            return $stmt->execute(['id' => $this->id]);

        } catch (PDOException $e) {
            error_log('[USER DELETE ERROR] ' . $e->getMessage());
            return false;
        }
    }
}
