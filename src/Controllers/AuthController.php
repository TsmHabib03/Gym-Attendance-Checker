<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Validator;
use App\Core\View;
use InvalidArgumentException;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }

        View::render('auth/login', [
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function login(): void
    {
        Csrf::assertValid((string) Request::input('_csrf'));

        try {
            $username = Validator::requiredString(Request::input('username'), 'Username', 60);
            $password = Validator::requiredString(Request::input('password'), 'Password', 120);
        } catch (InvalidArgumentException $exception) {
            $_SESSION['_old']['username'] = (string) Request::input('username', '');
            flash('error', $exception->getMessage());
            redirect('/login');
        }

        $ip = Request::ip();

        $rate = RateLimiter::hit(
            'login',
            $ip . '|' . strtolower($username),
            Config::int('LOGIN_RATE_LIMIT_MAX_ATTEMPTS', 5),
            Config::int('LOGIN_RATE_LIMIT_WINDOW_SECONDS', 300)
        );

        if (!$rate['allowed']) {
            flash('error', 'Too many login attempts. Please wait ' . (int) $rate['retry_after'] . ' seconds.');
            redirect('/login');
        }

        if (!Auth::attemptLogin($username, $password)) {
            $_SESSION['_old']['username'] = $username;
            flash('error', 'Invalid credentials.');
            Logger::audit('login_failed', null, ['username' => $username, 'ip' => $ip]);
            redirect('/login');
        }

        Logger::audit('login_success', Auth::id(), ['username' => $username, 'ip' => $ip]);
        redirect('/dashboard');
    }

    public function logout(): void
    {
        Csrf::assertValid((string) Request::input('_csrf'));
        Logger::audit('logout', Auth::id(), ['ip' => Request::ip()]);
        Auth::logout();
        redirect('/login');
    }
}
