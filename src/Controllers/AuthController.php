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

        $ip = Request::ip();
        $username = '';

        try {
            $username = Validator::requiredString(Request::input('username'), 'Username', 60);
            $password = Validator::requiredString(Request::input('password'), 'Password', 120);
        } catch (InvalidArgumentException $exception) {
            $_SESSION['_old']['username'] = is_string(Request::input('username'))
                ? Validator::string(Request::input('username'), 60)
                : '';
            // Generic message to avoid hinting which field was the problem.
            flash('error', 'Invalid credentials.');
            redirect('/login');
        }

        // Rate limit by IP+username AND by IP alone — the latter caps total
        // password guesses from one source even if the attacker rotates users.
        $rate = RateLimiter::hit(
            'login',
            $ip . '|' . strtolower($username),
            Config::int('LOGIN_RATE_LIMIT_MAX_ATTEMPTS', 5),
            Config::int('LOGIN_RATE_LIMIT_WINDOW_SECONDS', 300)
        );

        $ipRate = RateLimiter::hit(
            'login_ip',
            $ip,
            Config::int('LOGIN_RATE_LIMIT_MAX_ATTEMPTS', 5) * 4,
            Config::int('LOGIN_RATE_LIMIT_WINDOW_SECONDS', 300)
        );

        if (!$rate['allowed'] || !$ipRate['allowed']) {
            $retry = max(
                (int) ($rate['retry_after'] ?? 0),
                (int) ($ipRate['retry_after'] ?? 0)
            );
            Logger::audit('login_rate_limited', null, [
                'username' => $username,
                'ip' => $ip,
                'retry_after' => $retry,
            ]);
            // RFC 6585 §4 — send Retry-After so clients / proxies know when
            // to try again, and so automated tools back off correctly.
            if (!headers_sent()) {
                http_response_code(429);
                header('Retry-After: ' . $retry);
            }
            flash('error', 'Too many login attempts. Please wait ' . $retry . ' seconds.');
            redirect('/login');
        }

        if (!Auth::attemptLogin($username, $password)) {
            $_SESSION['_old']['username'] = $username;
            // Constant generic message — never leak whether the username exists.
            flash('error', 'Invalid credentials.');
            Logger::audit('login_failed', null, [
                'username' => $username,
                'ip' => $ip,
            ]);
            redirect('/login');
        }

        // Successful login — purge "old" form values so the username doesn't leak.
        unset($_SESSION['_old']);
        Logger::audit('login_success', Auth::id(), [
            'username' => $username,
            'ip' => $ip,
        ]);
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
