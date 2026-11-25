<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\ProductRepositoryInterface;
use PDO;
use PDOException;
use RuntimeException;

final class ProductService implements ProductRepositoryInterface
{
    private const SORT_COLUMNS = [
        'name' => 'name',
        'price' => 'price',
        'quantity' => 'quantity_available',
        'id' => 'id',
    ];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function paginate(int $page, int $perPage, string $sort, string $direction): array
    {
        $column = self::SORT_COLUMNS[$sort] ?? 'name';
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $offset = ($page - 1) * $perPage;

        $statement = $this->pdo->prepare(
            "SELECT id, name, slug, price, quantity_available FROM products ORDER BY {$column} {$direction} LIMIT :limit OFFSET :offset",
        );
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'total' => intval($this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn()),
        ];
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, name, slug, price, quantity_available FROM products WHERE id = :id');
        $statement->execute(['id' => $id]);
        $product = $statement->fetch();

        return $product ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO products (name, slug, price, quantity_available) VALUES (:name, :slug, :price, :quantity)',
        );
        $statement->execute([
            'name' => $data['name'],
            'slug' => $this->availableSlug($data['name']),
            'price' => $data['price'],
            'quantity' => $data['quantity_available'],
        ]);

        return intval($this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE products SET name = :name, slug = :slug, price = :price, quantity_available = :quantity WHERE id = :id',
        );
        $statement->execute([
            'id' => $id,
            'name' => $data['name'],
            'slug' => $this->availableSlug($data['name'], $id),
            'price' => $data['price'],
            'quantity' => $data['quantity_available'],
        ]);

        return $statement->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM products WHERE id = :id');
        try {
            $statement->execute(['id' => $id]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                return false;
            }
            throw $exception;
        }

        return $statement->rowCount() > 0;
    }

    public function findForUpdate(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, slug, price, quantity_available FROM products WHERE id = :id FOR UPDATE',
        );
        $statement->execute(['id' => $id]);
        $product = $statement->fetch();

        return $product ?: null;
    }

    public function decrementQuantity(int $id, int $quantity): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE products SET quantity_available = quantity_available - :quantity
             WHERE id = :id AND quantity_available >= :required',
        );
        $statement->execute(['id' => $id, 'quantity' => $quantity, 'required' => $quantity]);
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('There is not enough stock for that purchase.');
        }
    }

    private function availableSlug(string $name, ?int $exceptId = null): string
    {
        $base = strtolower(trim($name));
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', $ascii === false ? $base : $ascii), '-') ?: 'product';
        $slug = $base;
        $number = 2;
        $query = $this->pdo->prepare('SELECT id FROM products WHERE slug = :slug');
        while (true) {
            $query->execute(['slug' => $slug]);
            $existingId = $query->fetchColumn();
            if ($existingId === false || intval($existingId) === $exceptId) {
                return $slug;
            }
            $slug = $base . '-' . $number++;
        }
    }
}
