<?php

declare(strict_types=1);

namespace App\Security;

use RuntimeException;

final class Jwt
{
    public function issue(array $user): string
    {
        $secret = getenv('JWT_SECRET') ?: '';
        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET must be set to at least 32 characters.');
        }

        $now = time();
        $ttl = max(60, intval(getenv('JWT_TTL') ?: 3600));
        $header = $this->encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_THROW_ON_ERROR));
        $payload = $this->encode(json_encode([
            'sub' => intval($user['id']),
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
            'iat' => $now,
            'exp' => $now + $ttl,
        ], JSON_THROW_ON_ERROR));
        $signature = $this->encode(hash_hmac('sha256', $header . '.' . $payload, $secret, true));

        return $header . '.' . $payload . '.' . $signature;
    }

    public function authenticate(string $authorization): ?array
    {
        $secret = getenv('JWT_SECRET') ?: '';
        if (strlen($secret) < 32 || !preg_match('/^Bearer\s+([A-Za-z0-9._-]+)$/i', $authorization, $matches)) {
            return null;
        }

        $parts = explode('.', $matches[1]);
        if (count($parts) !== 3) {
            return null;
        }
        [$header, $payload, $signature] = $parts;
        $decodedHeader = json_decode($this->decode($header), true);
        $claims = json_decode($this->decode($payload), true);
        if (!is_array($decodedHeader) || ($decodedHeader['alg'] ?? null) !== 'HS256' || !is_array($claims)) {
            return null;
        }

        $expected = $this->encode(hash_hmac('sha256', $header . '.' . $payload, $secret, true));
        if (!hash_equals($expected, $signature) || intval($claims['exp'] ?? 0) <= time() || intval($claims['sub'] ?? 0) < 1) {
            return null;
        }

        return [
            'id' => intval($claims['sub']),
            'name' => (string) ($claims['name'] ?? ''),
            'email' => (string) ($claims['email'] ?? ''),
            'role' => (string) ($claims['role'] ?? ''),
        ];
    }

    private function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function decode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }
}
