<?php

declare(strict_types=1);

namespace App\Interfaces;

interface PurchaseRepositoryInterface
{
    public function purchase(int $userId, int $productId, int $quantity): array;
}
