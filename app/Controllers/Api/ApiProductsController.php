<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Http\Route;
use App\Interfaces\ProductRepositoryInterface;
use App\Interfaces\PurchaseRepositoryInterface;
use RuntimeException;

final class ApiProductsController
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly PurchaseRepositoryInterface $purchases,
    ) {
    }

    #[Route('GET', '/api/products')]
    public function index(Request $request): Response
    {
        $page = max(1, intval($request->input('page', 1)));
        $result = $this->products->paginate(
            $page,
            20,
            (string) $request->input('sort', 'name'),
            (string) $request->input('direction', 'ASC'),
        );

        return Response::json([
            'data' => $result['items'],
            'page' => $page,
            'per_page' => 20,
            'total' => $result['total'],
        ]);
    }

    #[Route('GET', '/api/products/{id}')]
    public function show(Request $request, int $id): Response
    {
        $product = $this->products->find($id);
        return $product === null
            ? Response::json(['error' => 'Product not found.'], 404)
            : Response::json(['data' => $product]);
    }

    #[Route('POST', '/api/products/{id}/purchase', 'api')]
    public function purchase(Request $request, int $id): Response
    {
        $quantity = filter_var($request->input('quantity'), FILTER_VALIDATE_INT);
        if ($quantity === false || $quantity < 1 || $quantity > 100) {
            return Response::json(['error' => 'Quantity must be a whole number between 1 and 100.'], 422);
        }

        try {
            $result = $this->purchases->purchase(intval($request->identity['id']), $id, $quantity);
        } catch (RuntimeException $exception) {
            return Response::json(['error' => $exception->getMessage()], 422);
        }

        return Response::json([
            'message' => 'Purchase complete.',
            'transaction_id' => $result['transaction_id'],
            'total' => $result['total'],
        ], 201);
    }

    #[Route('POST', '/api/products', 'api', ['Admin'])]
    public function create(Request $request): Response
    {
        return $this->mutateProduct($request);
    }

    #[Route('PUT', '/api/products/{id}', 'api', ['Admin'])]
    public function update(Request $request, int $id): Response
    {
        $data = $this->validatedProduct($request);
        if (isset($data['error'])) {
            return Response::json(['error' => $data['error']], 422);
        }
        if ($this->products->find($id) === null) {
            return Response::json(['error' => 'Product not found.'], 404);
        }
        $this->products->update($id, $data);
        return Response::json(['data' => $this->products->find($id)]);
    }

    #[Route('DELETE', '/api/products/{id}', 'api', ['Admin'])]
    public function delete(Request $request, int $id): Response
    {
        if (!$this->products->delete($id)) {
            return Response::json(['error' => 'Product not found or it has already been sold.'], 404);
        }
        return Response::json(['message' => 'Product deleted.']);
    }

    private function mutateProduct(Request $request): Response
    {
        $data = $this->validatedProduct($request);
        if (isset($data['error'])) {
            return Response::json(['error' => $data['error']], 422);
        }
        $id = $this->products->create($data);

        return Response::json(['data' => $this->products->find($id)], 201);
    }

    private function validatedProduct(Request $request): array
    {
        $name = trim((string) $request->input('name', ''));
        $price = trim((string) $request->input('price', ''));
        $quantity = (string) $request->input('quantity_available', '');
        if ($name === '' || mb_strlen($name) > 120
            || !preg_match('/^(?:0|[0-9]+)(?:\.[0-9]{1,3})?$/', $price) || (float) $price <= 0
            || !preg_match('/^[0-9]+$/', $quantity) || intval($quantity) > 2147483647) {
            return ['error' => 'Provide a name, positive price (up to three decimals), and non-negative whole quantity.'];
        }

        return ['name' => $name, 'price' => $price, 'quantity_available' => intval($quantity)];
    }
}
