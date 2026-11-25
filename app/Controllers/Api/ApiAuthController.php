<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Http\Route;
use App\Interfaces\UserRepositoryInterface;
use App\Security\Jwt;

final class ApiAuthController
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly Jwt $jwt,
    ) {
    }

    #[Route('POST', '/api/token')]
    public function token(Request $request): Response
    {
        $user = $this->users->findByEmail((string) $request->input('email', ''));
        if ($user === null || !password_verify((string) $request->input('password', ''), $user['password_hash'])) {
            return Response::json(['error' => 'Invalid credentials.'], 401);
        }

        return Response::json([
            'access_token' => $this->jwt->issue($user),
            'token_type' => 'Bearer',
            'expires_in' => max(60, intval(getenv('JWT_TTL') ?: 3600)),
        ]);
    }
}
