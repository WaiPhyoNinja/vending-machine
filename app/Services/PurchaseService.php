<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\ProductRepositoryInterface;
use App\Interfaces\PurchaseRepositoryInterface;
use App\Interfaces\TransactionRepositoryInterface;
use PDO;
use RuntimeException;
use Throwable;

final class PurchaseService implements PurchaseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ProductRepositoryInterface $products,
        private readonly TransactionRepositoryInterface $transactions,
    ) {
    }

    public function purchase(int $userId, int $productId, int $quantity): array
    {
        if ($quantity < 1 || $quantity > 100) {
            throw new RuntimeException('Please choose a quantity between 1 and 100.');
        }

        $this->pdo->beginTransaction();
        try {
            $product = $this->products->findForUpdate($productId);
            if ($product === null) {
                throw new RuntimeException('That product is no longer available.');
            }
            if (intval($product['quantity_available']) < $quantity) {
                throw new RuntimeException('There is not enough stock for that purchase.');
            }

            $total = number_format((float) $product['price'] * $quantity, 3, '.', '');
            $this->products->decrementQuantity($productId, $quantity);
            $transactionId = $this->transactions->record(
                $userId,
                $productId,
                $quantity,
                (string) $product['price'],
                $total,
            );
            $this->pdo->commit();

            return ['transaction_id' => $transactionId, 'total' => $total, 'product' => $product];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
