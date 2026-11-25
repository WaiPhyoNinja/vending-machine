<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\TransactionRepositoryInterface;
use PDO;

final class TransactionService implements TransactionRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function record(int $userId, int $productId, int $quantity, string $unitPrice, string $total): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO transactions (user_id, product_id, quantity, unit_price, total_amount)
             VALUES (:user_id, :product_id, :quantity, :unit_price, :total_amount)',
        );
        $statement->execute([
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $total,
        ]);

        return intval($this->pdo->lastInsertId());
    }

    public function recent(int $limit = 50): array
    {
        $statement = $this->pdo->prepare(
            'SELECT t.id, u.name AS user_name, p.name AS product_name, t.quantity, t.unit_price, t.total_amount, t.created_at
             FROM transactions t
             JOIN users u ON u.id = t.user_id
             JOIN products p ON p.id = t.product_id
             ORDER BY t.created_at DESC LIMIT :limit',
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
