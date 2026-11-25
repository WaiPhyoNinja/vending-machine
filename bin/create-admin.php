<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repositories\UserRepository;
use App\Services\UserService;

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

$email = getenv('ADMIN_EMAIL') ?: '';
$password = getenv('ADMIN_PASSWORD') ?: '';
$name = getenv('ADMIN_NAME') ?: 'Administrator';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Set ADMIN_EMAIL and a 12+ character ADMIN_PASSWORD, then run this script.\n");
    exit(1);
}

$users = new UserRepository(new UserService(Connection::create()));
if ($users->findByEmail($email) !== null) {
    fwrite(STDERR, "An account with that email already exists.\n");
    exit(1);
}

$users->create($name, $email, password_hash($password, PASSWORD_DEFAULT), 'Admin');
fwrite(STDOUT, "Admin account created for {$email}.\n");
