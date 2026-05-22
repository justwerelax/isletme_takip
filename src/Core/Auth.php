<?php
class Auth {
    public static function check(): bool {
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array {
        if (!self::check()) return null;
        if (isset($_SESSION['user_cache'])) return $_SESSION['user_cache'];
        $user = Database::fetch("SELECT * FROM users WHERE id = ? AND is_active = 1", [$_SESSION['user_id']]);
        if ($user) $_SESSION['user_cache'] = $user;
        return $user;
    }

    public static function isAdmin(): bool {
        $user = self::user();
        return $user && $user['role'] === 'admin';
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            header('Location: ?page=login');
            exit;
        }
    }

    public static function requireAdmin(): void {
        self::requireLogin();
        if (!self::isAdmin()) {
            $_SESSION['flash_error'] = 'Bu işlem için yetkiniz yok.';
            header('Location: ?page=dashboard');
            exit;
        }
    }

    public static function login(string $username, string $password): bool {
        $user = Database::fetch("SELECT * FROM users WHERE username = ? AND is_active = 1", [$username]);
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            unset($_SESSION['user_cache']);
            return true;
        }
        return false;
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
        }
        session_destroy();
    }

    public static function flash(string $type): ?string {
        $key = "flash_{$type}";
        $msg = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);
        return $msg;
    }
}
