<?php

declare(strict_types=1);

namespace App\Services;

final class Auth
{
    public const ROLES = ['Administrator', 'Officer', 'Technician', 'Executive', 'Employee'];

    /** @var array<string, mixed>|null */
    private static ?array $user = null;

    /** @return array<string, mixed>|null */
    public static function attempt(string $login, string $password): ?array
    {
        $user = Database::first(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'Active' LIMIT 1",
            [$login, $login]
        );

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        session_regenerate_id(true);
        unset($_SESSION['_csrf_token']); // ออก token ใหม่หลังเปลี่ยนสิทธิ์ของ session
        $_SESSION['user_id'] = $user['id'];
        self::$user = $user;

        return $user;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::$user = null;
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$user === null && isset($_SESSION['user_id'])) {
            self::$user = Database::first(
                "SELECT u.*, d.name AS department_name FROM users u
                 LEFT JOIN departments d ON d.id = u.department_id
                 WHERE u.id = ? AND u.status = 'Active'",
                [$_SESSION['user_id']]
            );
        }

        return self::$user;
    }

    public static function id(): ?string
    {
        return self::user()['id'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function hasRole(string ...$roles): bool
    {
        return in_array(self::user()['role'] ?? null, $roles, true);
    }
}
