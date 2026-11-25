<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\UserRepositoryInterface;
use App\Services\UserService;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly UserService $service)
    {
    }

    public function findByEmail(string $email): ?array
    {
        return $this->service->findByEmail($email);
    }

    public function create(string $name, string $email, string $passwordHash, string $role = 'User'): int
    {
        return $this->service->create($name, $email, $passwordHash, $role);
    }
}
