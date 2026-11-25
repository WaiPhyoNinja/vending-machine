<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\TransactionRepositoryInterface;
use App\Services\TransactionService;

final class TransactionRepository implements TransactionRepositoryInterface
{
    public function __construct(private readonly TransactionService $service)
    {
    }

    public function record(int $userId, int $productId, int $quantity, string $unitPrice, string $total): int
    {
        return $this->service->record($userId, $productId, $quantity, $unitPrice, $total);
    }

    public function recent(int $limit = 50): array
    {
        return $this->service->recent($limit);
    }
}
