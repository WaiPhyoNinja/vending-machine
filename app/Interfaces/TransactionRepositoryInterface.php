<?php

declare(strict_types=1);

namespace App\Interfaces;

interface TransactionRepositoryInterface
{
    public function record(int $userId, int $productId, int $quantity, string $unitPrice, string $total): int;
    public function recent(int $limit = 50): array;
}
