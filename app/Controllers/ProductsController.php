<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Http\Route;
use App\Interfaces\ProductRepositoryInterface;
use App\Interfaces\PurchaseRepositoryInterface;
use App\Interfaces\TransactionRepositoryInterface;
use App\Security\Auth;
use App\Security\Csrf;
use RuntimeException;

final class ProductsController
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly PurchaseRepositoryInterface $purchases,
        private readonly TransactionRepositoryInterface $transactions,
        private readonly Auth $auth,
    ) {
    }

    #[Route('GET', '/', 'none')]
    #[Route('GET', '/products', 'none')]
    public function index(Request $request): Response
    {
        $page = max(1, intval($request->query['page'] ?? 1));
        $perPage = 6;
        $sort = (string) ($request->query['sort'] ?? 'name');
        $direction = strtoupper((string) ($request->query['direction'] ?? 'ASC'));
        $result = $this->products->paginate($page, $perPage, $sort, $direction);
        $pages = max(1, intval(ceil($result['total'] / $perPage)));
        $page = min($page, $pages);
        if ($page !== max(1, intval($request->query['page'] ?? 1))) {
            $result = $this->products->paginate($page, $perPage, $sort, $direction);
        }

        return view('products/index', [
            'products' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => $pages,
            'sort' => $sort,
            'direction' => $direction === 'DESC' ? 'DESC' : 'ASC',
            'user' => $this->auth->user(),
        ]);
    }

    #[Route('GET', '/products/{product}', 'none')]
    #[Route('GET', '/products/{oldProduct}', 'none')]
    #[Route('GET', '/products/{id}', 'none')]
    public function show(Request $request, int $id): Response
    {
        $product = $this->products->find($id);
        if ($product === null) {
            return Response::html('Product not found.', 404);
        }

        if ($request->path !== product_path($product)) {
            return new Response('', 301, ['Location' => product_path($product)]);
        }

        return view('products/show', ['product' => $product, 'user' => $this->auth->user()]);
    }

    #[Route('GET', '/admin', 'web', ['Admin'])]
    public function dashboard(Request $request): Response
    {
        $result = $this->products->paginate(1, 100, 'name', 'ASC');

        return view('admin/dashboard', [
            'products' => $result['items'],
            'total' => $result['total'],
            'recentSales' => $this->transactions->recent(5),
        ], 'admin');
    }

    #[Route('GET', '/admin/products/create', 'web', ['Admin'])]
    public function create(Request $request): Response
    {
        return view('products/form', [
            'product' => ['name' => '', 'price' => '', 'quantity_available' => ''],
            'errors' => [],
            'csrf' => Csrf::token(),
            'editing' => false,
        ], 'admin');
    }

    #[Route('POST', '/admin/products', 'web', ['Admin'])]
    public function store(Request $request): Response
    {
        if (!Csrf::valid($request->input('_csrf'))) {
            return Response::html('Invalid form token. Reload the page and try again.', 419);
        }
        $data = $this->validatedProduct($request);
        if ($data['errors'] !== []) {
            return view('products/form', [
                'product' => $data['product'],
                'errors' => $data['errors'],
                'csrf' => Csrf::token(),
                'editing' => false,
            ], 'admin');
        }

        $this->products->create($data['clean']);
        $_SESSION['flash'] = 'Product added.';
        return redirect('/admin');
    }

    #[Route('GET', '/admin/products/{id}/edit', 'web', ['Admin'])]
    public function edit(Request $request, int $id): Response
    {
        $product = $this->products->find($id);
        if ($product === null) {
            return Response::html('Product not found.', 404);
        }

        return view('products/form', [
            'product' => $product,
            'errors' => [],
            'csrf' => Csrf::token(),
            'editing' => true,
        ], 'admin');
    }

    #[Route('POST', '/admin/products/{id}', 'web', ['Admin'])]
    public function update(Request $request, int $id): Response
    {
        if (!Csrf::valid($request->input('_csrf'))) {
            return Response::html('Invalid form token. Reload the page and try again.', 419);
        }
        if ($this->products->find($id) === null) {
            return Response::html('Product not found.', 404);
        }

        $data = $this->validatedProduct($request);
        if ($data['errors'] !== []) {
            return view('products/form', [
                'product' => array_merge($data['product'], ['id' => $id]),
                'errors' => $data['errors'],
                'csrf' => Csrf::token(),
                'editing' => true,
            ], 'admin');
        }

        $this->products->update($id, $data['clean']);
        $_SESSION['flash'] = 'Product updated.';
        return redirect('/admin');
    }

    #[Route('POST', '/admin/products/{id}/delete', 'web', ['Admin'])]
    public function delete(Request $request, int $id): Response
    {
        if (!Csrf::valid($request->input('_csrf'))) {
            return Response::html('Invalid form token. Reload the page and try again.', 419);
        }
        if (!$this->products->delete($id)) {
            return Response::html('Product not found or it has already been sold.', 404);
        }

        $_SESSION['flash'] = 'Product deleted.';
        return redirect('/admin');
    }

    #[Route('GET', '/products/{product}/purchase', 'web')]
    #[Route('GET', '/products/{oldProduct}/purchase', 'web')]
    #[Route('GET', '/products/{id}/purchase', 'web')]
    public function purchaseForm(Request $request, int $id): Response
    {
        $product = $this->products->find($id);
        if ($product === null) {
            return Response::html('Product not found.', 404);
        }
        if ($this->auth->user() === null) {
            return redirect('/login');
        }

        if ($request->path !== product_path($product, true)) {
            return new Response('', 301, ['Location' => product_path($product, true)]);
        }

        return view('products/purchase', [
            'product' => $product,
            'errors' => [],
            'csrf' => Csrf::token(),
        ]);
    }

    #[Route('POST', '/products/{product}/purchase', 'web')]
    #[Route('POST', '/products/{oldProduct}/purchase', 'web')]
    #[Route('POST', '/products/{id}/purchase', 'web')]
    public function purchase(Request $request, int $id): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return redirect('/login');
        }
        if (!Csrf::valid($request->input('_csrf'))) {
            return Response::html('Invalid form token. Reload the page and try again.', 419);
        }

        $quantity = filter_var($request->input('quantity'), FILTER_VALIDATE_INT);
        if ($quantity === false || $quantity < 1 || $quantity > 100) {
            return $this->purchaseError($id, 'Choose a quantity between 1 and 100.');
        }

        try {
            $result = $this->purchases->purchase(intval($user['id']), $id, $quantity);
        } catch (RuntimeException $exception) {
            return $this->purchaseError($id, $exception->getMessage());
        }

        $_SESSION['flash'] = 'Purchase complete. Total: $' . $result['total'];
        return redirect(product_path($result['product']));
    }

    #[Route('GET', '/admin/transactions', 'web', ['Admin'])]
    public function transactions(Request $request): Response
    {
        return view('products/transactions', ['transactions' => $this->transactions->recent()], 'admin');
    }

    private function purchaseError(int $id, string $message): Response
    {
        $product = $this->products->find($id);
        if ($product === null) {
            return Response::html('Product not found.', 404);
        }
        return view('products/purchase', [
            'product' => $product,
            'errors' => [$message],
            'csrf' => Csrf::token(),
        ]);
    }

    private function validatedProduct(Request $request): array
    {
        $product = [
            'name' => trim((string) $request->input('name', '')),
            'price' => trim((string) $request->input('price', '')),
            'quantity_available' => trim((string) $request->input('quantity_available', '')),
        ];
        $errors = [];
        if ($product['name'] === '' || mb_strlen($product['name']) > 120) {
            $errors[] = 'Enter a product name no longer than 120 characters.';
        }
        if (!preg_match('/^(?:0|[0-9]+)(?:\.[0-9]{1,3})?$/', $product['price']) || (float) $product['price'] <= 0) {
            $errors[] = 'Price must be a positive number with up to three decimal places.';
        }
        if (!preg_match('/^[0-9]+$/', $product['quantity_available']) || intval($product['quantity_available']) > 2147483647) {
            $errors[] = 'Available quantity must be a non-negative whole number.';
        }

        return [
            'product' => $product,
            'errors' => $errors,
            'clean' => [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity_available' => intval($product['quantity_available']),
            ],
        ];
    }
}
