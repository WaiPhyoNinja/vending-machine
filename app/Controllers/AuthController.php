<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Http\Route;
use App\Interfaces\UserRepositoryInterface;
use App\Security\Auth;
use App\Security\Csrf;

final class AuthController
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly Auth $auth,
    ) {
    }

    #[Route('GET', '/login')]
    public function loginForm(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user !== null) {
            return redirect(($user['role'] ?? '') === 'Admin' ? '/admin' : '/products');
        }
        return view('auth/login', ['errors' => [], 'csrf' => Csrf::token()]);
    }

    #[Route('POST', '/login')]
    public function login(Request $request): Response
    {
        if (!Csrf::valid($request->input('_csrf'))) {
            return Response::html('Invalid form token. Reload the page and try again.', 419);
        }
        $user = $this->users->findByEmail((string) $request->input('email', ''));
        if ($user === null || !password_verify((string) $request->input('password', ''), $user['password_hash'])) {
            return view('auth/login', ['errors' => ['Email or password is incorrect.'], 'csrf' => Csrf::token()]);
        }

        $this->auth->login($user);
        return redirect($user['role'] === 'Admin' ? '/admin' : '/products');
    }

    #[Route('GET', '/register')]
    public function registerForm(Request $request): Response
    {
        return view('auth/register', ['errors' => [], 'csrf' => Csrf::token(), 'values' => []]);
    }

    #[Route('POST', '/register')]
    public function register(Request $request): Response
    {
        $name = trim((string) $request->input('name', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $errors = [];
        if (!Csrf::valid($request->input('_csrf'))) {
            return Response::html('Invalid form token. Reload the page and try again.', 419);
        }
        if ($name === '' || mb_strlen($name) > 120) {
            $errors[] = 'Enter your name (up to 120 characters).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        } elseif ($this->users->findByEmail($email) !== null) {
            $errors[] = 'An account with that email already exists.';
        }
        if (strlen($password) < 12) {
            $errors[] = 'Use a password with at least 12 characters.';
        }
        if ($errors !== []) {
            return view('auth/register', [
                'errors' => $errors,
                'csrf' => Csrf::token(),
                'values' => ['name' => $name, 'email' => $email],
            ]);
        }

        $this->users->create($name, $email, password_hash($password, PASSWORD_DEFAULT), 'User');
        $_SESSION['flash'] = 'Account created. You can now sign in.';
        return redirect('/login');
    }

    #[Route('POST', '/logout', 'web')]
    public function logout(Request $request): Response
    {
        if (!Csrf::valid($request->input('_csrf'))) {
            return Response::html('Invalid form token. Reload the page and try again.', 419);
        }
        $this->auth->logout();
        return redirect('/products');
    }
}
