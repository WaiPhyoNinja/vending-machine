<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class UserService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = :email');
        $statement->execute(['email' => strtolower(trim($email))]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function create(string $name, string $email, string $passwordHash, string $role = 'User'): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)',
        );
        $statement->execute([
            'name' => trim($name),
            'email' => strtolower(trim($email)),
            'password_hash' => $passwordHash,
            'role' => $role,
        ]);

        return intval($this->pdo->lastInsertId());
    }
}
