<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\ProductRepositoryInterface;
use App\Services\ProductService;

final class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(private readonly ProductService $service)
    {
    }

    public function paginate(int $page, int $perPage, string $sort, string $direction): array
    {
        return $this->service->paginate($page, $perPage, $sort, $direction);
    }

    public function find(int $id): ?array
    {
        return $this->service->find($id);
    }

    public function create(array $data): int
    {
        return $this->service->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->service->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->service->delete($id);
    }

    public function findForUpdate(int $id): ?array
    {
        return $this->service->findForUpdate($id);
    }

    public function decrementQuantity(int $id, int $quantity): void
    {
        $this->service->decrementQuantity($id, $quantity);
    }
}
