<?php

namespace App\Core;

class SessionManager
{
    private const FLASH_KEY = '__flash_messages__';
    private const USER_KEY  = '__auth_user__';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                ini_set('session.use_strict_mode', '1');
                ini_set('session.use_only_cookies', '1');
                ini_set('session.cookie_httponly', '1');

                session_set_cookie_params([
                    'lifetime' => 0,
                    'path' => '/',
                    'domain' => '',
                    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }

            @session_start();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function clear(): void
    {
        self::start();
        $_SESSION = [];
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            if (ini_get("session.use_cookies") && !headers_sent()) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            @session_destroy();
        }
    }

    public static function regenerateId(bool $deleteOldSession = true): bool
    {
        self::start();
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            return session_regenerate_id($deleteOldSession);
        }
        return false;
    }

    public static function setFlash(string $type, string $message): void
    {
        self::start();
        if (!isset($_SESSION[self::FLASH_KEY])) {
            $_SESSION[self::FLASH_KEY] = [];
        }
        if (!isset($_SESSION[self::FLASH_KEY][$type])) {
            $_SESSION[self::FLASH_KEY][$type] = [];
        }
        $_SESSION[self::FLASH_KEY][$type][] = $message;
    }

    public static function getFlash(string $type, mixed $default = null): mixed
    {
        self::start();
        if (isset($_SESSION[self::FLASH_KEY][$type])) {
            $messages = $_SESSION[self::FLASH_KEY][$type];
            unset($_SESSION[self::FLASH_KEY][$type]);
            return $messages;
        }
        return $default;
    }

    public static function hasFlash(string $type): bool
    {
        self::start();
        return !empty($_SESSION[self::FLASH_KEY][$type]);
    }

    public static function getFlashes(): array
    {
        self::start();
        $flashes = $_SESSION[self::FLASH_KEY] ?? [];
        unset($_SESSION[self::FLASH_KEY]);
        return $flashes;
    }

    public static function setUser(mixed $user): void
    {
        self::set(self::USER_KEY, $user);
    }

    public static function getUser(): mixed
    {
        return self::get(self::USER_KEY);
    }

    public static function isLoggedIn(): bool
    {
        return self::has(self::USER_KEY) && self::get(self::USER_KEY) !== null;
    }

    public static function logout(): void
    {
        self::remove(self::USER_KEY);
        self::destroy();
    }
}
