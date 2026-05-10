<?php
// api/models/User.php

class User
{
    private PDO $conn;
    private string $table_name = 'users';

    public ?int $id = null;
    public ?string $username = null;
    public ?string $email = null;
    public ?string $password = null;
    public ?string $created_at = null;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function create(): bool
    {
        if ($this->findByUsername() || $this->findByEmail()) {
            return false;
        }

        $query = "INSERT INTO {$this->table_name} (username, email, password)
                  VALUES (:username, :email, :password)";

        $stmt = $this->conn->prepare($query);

        $this->username = trim($this->username);
        $this->email    = trim($this->email);

        $passwordHash = password_hash($this->password, PASSWORD_BCRYPT);

        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $passwordHash);

        return $stmt->execute();
    }

    public function findByUsername(): bool
    {
        if (empty($this->username)) {
            return false;
        }

        $query = "SELECT id, username, email, password, created_at
                  FROM {$this->table_name}
                  WHERE username = :username
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $this->username);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->hydrate($row);
            return true;
        }

        return false;
    }

    public function findByEmail(): bool
    {
        if (empty($this->email)) {
            return false;
        }

        $query = "SELECT id, username, email, password, created_at
                  FROM {$this->table_name}
                  WHERE email = :email
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->hydrate($row);
            return true;
        }

        return false;
    }

    public function findById(): bool
    {
        if (empty($this->id)) {
            return false;
        }

        $query = "SELECT id, username, email, password, created_at
                  FROM {$this->table_name}
                  WHERE id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->hydrate($row);
            return true;
        }

        return false;
    }

    public function update(): bool
    {
        $fields = ['username = :username', 'email = :email'];

        if (!empty($this->password)) {
            $fields[] = 'password = :password';
        }

        $sql = 'UPDATE ' . $this->table_name . ' SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if (!empty($this->password)) {
            $hash = password_hash($this->password, PASSWORD_BCRYPT);
            $stmt->bindParam(':password', $hash);
        }

        return $stmt->execute();
    }

    public function delete(): bool
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM {$this->table_name} WHERE id = :id"
        );
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function hydrate(array $row): void
    {
        $this->id         = (int)$row['id'];
        $this->username   = $row['username'];
        $this->email      = $row['email'];
        $this->password   = $row['password'];
        $this->created_at = $row['created_at'];
    }
}
