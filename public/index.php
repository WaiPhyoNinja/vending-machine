<?php

declare(strict_types=1);

use App\Controllers\Api\ApiAuthController;
use App\Controllers\Api\ApiProductsController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\ProductsController;
use App\Database\Connection;
use App\Http\Request;
use App\Http\Router;
use App\Repositories\ProductRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Security\Auth;
use App\Security\Jwt;
use App\Services\ProductService;
use App\Services\PurchaseService;
use App\Services\TransactionService;
use App\Services\UserService;

if (PHP_SAPI === 'cli-server') {
    $requestedPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $publicRoot = realpath(__DIR__);
    $staticPath = realpath(__DIR__ . $requestedPath);
    if ($publicRoot !== false && $staticPath !== false
        && str_starts_with($staticPath, $publicRoot . DIRECTORY_SEPARATOR)
        && is_file($staticPath)) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        if (getenv(trim($name)) === false) {
            putenv(trim($name) . '=' . trim($value, " \t\n\r\0\x0B\"'"));
        }
    }
}

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_set_cookie_params([
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

$pdo = Connection::create();
$productService = new ProductService($pdo);
$userService = new UserService($pdo);
$transactionService = new TransactionService($pdo);
$productRepository = new ProductRepository($productService);
$userRepository = new UserRepository($userService);
$transactionRepository = new TransactionRepository($transactionService);
$auth = new Auth();
$jwt = new Jwt();
$purchaseService = new PurchaseService($pdo, $productRepository, $transactionRepository);

$purchaseRepository = new PurchaseRepository($purchaseService);

$router = new Router($auth, $jwt);
$router->register(new HomeController());
$router->register(new ProductsController($productRepository, $purchaseRepository, $transactionRepository, $auth));
$router->register(new AuthController($userRepository, $auth));
$router->register(new ApiAuthController($userRepository, $jwt));
$router->register(new ApiProductsController($productRepository, $purchaseRepository));
$router->dispatch(Request::fromGlobals())->send();
