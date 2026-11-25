<?php

declare(strict_types=1);

namespace App\Security;

final class Auth
{
    public function user(): ?array
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    public function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => intval($user['id']),
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
    }
}
