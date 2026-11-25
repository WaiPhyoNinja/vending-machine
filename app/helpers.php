<?php

declare(strict_types=1);

use App\Http\Response;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function view(string $template, array $data = [], string $layout = 'app'): Response
{
    extract($data, EXTR_SKIP);
    ob_start();
    require dirname(__DIR__) . '/resources/views/' . $template . '.php';
    $content = (string) ob_get_clean();

    ob_start();
    require dirname(__DIR__) . '/resources/views/layouts/' . $layout . '.php';

    return Response::html((string) ob_get_clean());
}

function redirect(string $path): Response
{
    return new Response('', 302, ['Location' => $path]);
}

function product_path(array $product, bool $purchase = false): string
{
    $name = strtolower(trim((string) $product['name']));
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $ascii === false ? $name : $ascii);
    $slug = trim((string) $slug, '-') ?: 'product';

    return '/products/' . ($product['slug'] ?? $slug) . '-' . intval($product['id']) . ($purchase ? '/purchase' : '');
}
