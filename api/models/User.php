<?php
// api/models/User.php

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

    public function create(): bool
    {
        if ($this->existsByUsername($this->username) || $this->existsByEmail($this->email)) {
            return false;
        }

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
    }

    /* ========================
       READ
       ======================== */

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, password, created_at
             FROM {$this->table}
             WHERE id = :id
             LIMIT 1"
        );

        $stmt->execute(['id' => $id]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, password, created_at
             FROM {$this->table}
             WHERE username = :username
             LIMIT 1"
        );

        $stmt->execute(['username' => $username]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, password, created_at
             FROM {$this->table}
             WHERE email = :email
             LIMIT 1"
        );

        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /* ========================
       UPDATE
       ======================== */

    public function update(): bool
    {
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
    }

    /* ========================
       DELETE
       ======================== */

    public function delete(): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE id = :id"
        );

        return $stmt->execute(['id' => $this->id]);
    }

    /* ========================
       UTILITAIRES PRIVÉS
       ======================== */

    private function existsByUsername(string $username): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM {$this->table} WHERE username = :username LIMIT 1"
        );

        $stmt->execute(['username' => $username]);
        return (bool) $stmt->fetchColumn();
    }

    private function existsByEmail(string $email): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM {$this->table} WHERE email = :email LIMIT 1"
        );

        $stmt->execute(['email' => $email]);
        return (bool) $stmt->fetchColumn();
    }
}