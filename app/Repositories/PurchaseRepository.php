<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\PurchaseRepositoryInterface;
use App\Services\PurchaseService;

final class PurchaseRepository implements PurchaseRepositoryInterface
{
    public function __construct(private readonly PurchaseService $service)
    {
    }

    public function purchase(int $userId, int $productId, int $quantity): array
    {
        return $this->service->purchase($userId, $productId, $quantity);
    }
}
