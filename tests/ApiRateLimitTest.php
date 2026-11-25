<?php

declare(strict_types=1);

namespace Tests;

use App\Http\Request;
use App\Http\Response;
use App\Http\Route;
use App\Http\Router;
use App\Security\Auth;
use App\Security\Jwt;
use App\Security\RateLimiter;
use PHPUnit\Framework\TestCase;

final class ApiRateLimitTest extends TestCase
{
    public function testOnlyApiRequestsAreLimited(): void
    {
        $directory = sys_get_temp_dir() . '/vending-rate-' . bin2hex(random_bytes(6));
        $router = new Router(new Auth(), new Jwt(), new RateLimiter($directory));
        $router->register(new class {
            #[Route('POST', '/api/token')]
            #[Route('POST', '/login')]
            public function ok(Request $request): Response
            {
                return Response::json(['ok' => true]);
            }
        });

        try {
            $api = new Request('POST', '/api/token', [], [], [], null, '192.0.2.10');
            for ($attempt = 0; $attempt < 5; $attempt++) {
                self::assertSame(200, $this->responseStatus($router->dispatch($api)));
            }
            $blocked = $router->dispatch($api);
            self::assertSame(429, $this->responseStatus($blocked));
            self::assertArrayHasKey('Retry-After', (new \ReflectionProperty($blocked, 'headers'))->getValue($blocked));

            $web = new Request('POST', '/login', [], [], [], null, '192.0.2.10');
            for ($attempt = 0; $attempt < 6; $attempt++) {
                self::assertSame(200, $this->responseStatus($router->dispatch($web)));
            }
        } finally {
            foreach (glob($directory . '/*.json') ?: [] as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }

    private function responseStatus(Response $response): int
    {
        return (new \ReflectionProperty($response, 'status'))->getValue($response);
    }
}
