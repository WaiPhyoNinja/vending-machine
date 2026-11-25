<?php

declare(strict_types=1);

namespace App\Http;

use App\Security\Auth;
use App\Security\Jwt;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

final class Router
{
    /** @var list<array{method:string, regex:string, names:list<string>, controller:object, action:ReflectionMethod, route:Route}> */
    private array $routes = [];

    public function __construct(
        private readonly Auth $auth,
        private readonly Jwt $jwt,
    ) {
    }

    public function register(object $controller): void
    {
        $reflection = new ReflectionClass($controller);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(Route::class) as $attribute) {
                $route = $attribute->newInstance();
                preg_match_all('/\{([a-zA-Z][a-zA-Z0-9_]*)\}/', $route->path, $matches);
                $quotedPath = preg_quote($route->path, '~');
                $regex = str_replace(
                    ['\\{product\\}', '\\{oldProduct\\}', '\\{id\\}'],
                    ['([a-z0-9]+(?:-[a-z0-9]+)*-[0-9]+)', '([0-9]+-[a-z0-9-]+)', '([0-9]+)'],
                    $quotedPath,
                );
                $this->routes[] = [
                    'method' => strtoupper($route->method),
                    'regex' => '~^' . $regex . '$~',
                    'names' => $matches[1],
                    'controller' => $controller,
                    'action' => $method,
                    'route' => $route,
                ];
            }
        }
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $registered) {
            if ($registered['method'] !== $request->method || !preg_match($registered['regex'], $request->path, $matches)) {
                continue;
            }

            try {
                $identity = match ($registered['route']->auth) {
                    'web' => $this->auth->user(),
                    'api' => $this->jwt->authenticate($request->headers['authorization'] ?? ''),
                    default => null,
                };
                if ($registered['route']->auth !== 'none' && $identity === null) {
                    return $request->wantsJson()
                        ? Response::json(['error' => 'Authentication required.'], 401)
                        : redirect('/login');
                }
                if ($registered['route']->roles !== [] && !in_array($identity['role'] ?? null, $registered['route']->roles, true)) {
                    return $request->wantsJson()
                        ? Response::json(['error' => 'You are not allowed to do that.'], 403)
                        : Response::html('Forbidden', 403);
                }

                $routeRequest = new Request(
                    $request->method,
                    $request->path,
                    $request->query,
                    $request->body,
                    $request->headers,
                    $identity,
                );
                $arguments = [$routeRequest];
                foreach ($registered['names'] as $index => $name) {
                    $value = $matches[$index + 1];
                    $arguments[] = $name === 'product'
                        ? intval(substr($value, strrpos($value, '-') + 1))
                        : intval($value);
                }

                return $registered['action']->invokeArgs($registered['controller'], $arguments);
            } catch (Throwable $exception) {
                error_log($exception->__toString());
                return $request->wantsJson()
                    ? Response::json(['error' => 'The request could not be completed.'], 500)
                    : Response::html('Something went wrong. Please try again.', 500);
            }
        }

        return $request->wantsJson()
            ? Response::json(['error' => 'Not found.'], 404)
            : Response::html('Page not found.', 404);
    }
}
