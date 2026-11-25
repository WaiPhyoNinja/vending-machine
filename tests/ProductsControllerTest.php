<?php

declare(strict_types=1);

namespace Tests;

use App\Controllers\ProductsController;
use App\Http\Request;
use App\Interfaces\ProductRepositoryInterface;
use App\Interfaces\PurchaseRepositoryInterface;
use App\Interfaces\TransactionRepositoryInterface;
use App\Security\Auth;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ProductsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = ['_csrf' => 'test-token'];
    }

    public function testProductListShowsProducts(): void
    {
        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->method('paginate')->willReturn([
            'items' => [['id' => 1, 'name' => 'Coke', 'price' => '3.990', 'quantity_available' => 10]],
            'total' => 1,
        ]);

        $response = $this->controller($products)->index(new Request('GET', '/products', [], [], []));

        self::assertSame(200, $this->responseStatus($response));
        self::assertStringContainsString('Coke', $this->body($response));
    }

    public function testAdminCanAddProduct(): void
    {
        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->expects(self::once())->method('create')->with([
            'name' => 'Water', 'price' => '0.500', 'quantity_available' => 10,
        ])->willReturn(2);

        $response = $this->controller($products)->store(new Request('POST', '/admin/products', [], [
            '_csrf' => 'test-token', 'name' => 'Water', 'price' => '0.500', 'quantity_available' => '10',
        ], []));

        self::assertSame('/admin', $this->headers($response)['Location']);
    }

    public function testInvalidProductIsNotSaved(): void
    {
        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->expects(self::never())->method('create');

        $response = $this->controller($products)->store(new Request('POST', '/admin/products', [], [
            '_csrf' => 'test-token', 'name' => '', 'price' => '0', 'quantity_available' => '-1',
        ], []));

        self::assertSame(200, $this->responseStatus($response));
        self::assertStringContainsString('positive number', $this->body($response));
    }

    public function testAdminCanUpdateProduct(): void
    {
        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->method('find')->with(2)->willReturn([
            'id' => 2, 'name' => 'Water', 'price' => '0.500', 'quantity_available' => 10,
        ]);
        $products->expects(self::once())->method('update')->with(2, [
            'name' => 'Water', 'price' => '0.750', 'quantity_available' => 8,
        ])->willReturn(true);

        $response = $this->controller($products)->update(new Request('POST', '/admin/products/2', [], [
            '_csrf' => 'test-token', 'name' => 'Water', 'price' => '0.750', 'quantity_available' => '8',
        ], []), 2);

        self::assertSame('/admin', $this->headers($response)['Location']);
    }

    public function testProductWithSalesCannotBeDeleted(): void
    {
        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->method('delete')->with(2)->willReturn(false);

        $response = $this->controller($products)->delete(new Request('POST', '/admin/products/2/delete', [], [
            '_csrf' => 'test-token',
        ], []), 2);

        self::assertSame(404, $this->responseStatus($response));
    }

    public function testPurchasePassesUserProductAndQuantity(): void
    {
        $_SESSION['user'] = ['id' => 7, 'name' => 'Sam', 'email' => 'sam@example.test', 'role' => 'User'];
        $purchases = $this->createMock(PurchaseRepositoryInterface::class);
        $purchases->expects(self::once())->method('purchase')->with(7, 2, 2)->willReturn([
            'transaction_id' => 12,
            'total' => '1.000',
            'product' => ['id' => 2, 'name' => 'Water', 'price' => '0.500', 'quantity_available' => 8],
        ]);

        $response = $this->controller(null, $purchases)->purchase(new Request('POST', '/products/water-2/purchase', [], [
            '_csrf' => 'test-token', 'quantity' => '2',
        ], []), 2);

        self::assertSame('/products/water-2', $this->headers($response)['Location']);
    }

    public function testPurchaseShowsErrorWhenStockIsTooLow(): void
    {
        $_SESSION['user'] = ['id' => 7, 'name' => 'Sam', 'email' => 'sam@example.test', 'role' => 'User'];
        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->method('find')->with(2)->willReturn([
            'id' => 2, 'name' => 'Water', 'price' => '0.500', 'quantity_available' => 1,
        ]);
        $purchases = $this->createMock(PurchaseRepositoryInterface::class);
        $purchases->method('purchase')->willThrowException(new RuntimeException('Not enough stock.'));

        $response = $this->controller($products, $purchases)->purchase(new Request('POST', '/products/water-2/purchase', [], [
            '_csrf' => 'test-token', 'quantity' => '2',
        ], []), 2);

        self::assertStringContainsString('Not enough stock.', $this->body($response));
    }

    private function controller(
        ?ProductRepositoryInterface $products = null,
        ?PurchaseRepositoryInterface $purchases = null,
    ): ProductsController {
        return new ProductsController(
            $products ?? $this->createMock(ProductRepositoryInterface::class),
            $purchases ?? $this->createMock(PurchaseRepositoryInterface::class),
            $this->createMock(TransactionRepositoryInterface::class),
            new Auth(),
        );
    }

    private function responseStatus(object $response): int
    {
        return (new \ReflectionProperty($response, 'status'))->getValue($response);
    }

    private function body(object $response): string
    {
        return (new \ReflectionProperty($response, 'content'))->getValue($response);
    }

    private function headers(object $response): array
    {
        return (new \ReflectionProperty($response, 'headers'))->getValue($response);
    }
}
