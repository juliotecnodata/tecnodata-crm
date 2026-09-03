<?php
namespace Tecnodata\CRM\Core;

final class Auth {
    public static function check(): bool { return !empty($_SESSION['user_id']); }
    public static function id(): ?int { return self::check() ? (int)$_SESSION['user_id'] : null; }
    public static function user(): ?array {
        if (!self::check()) return null;
        return DB::fetch("SELECT * FROM users WHERE id=? AND active=1", [self::id()]);
    }
    public static function login(string $email, string $password): bool {
        $u = DB::fetch("SELECT * FROM users WHERE email=? AND active=1 LIMIT 1", [mb_strtolower(trim($email))]);
        if (!$u || !password_verify($password, $u['password_hash'])) return false;
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$u['id'];
        DB::exec("UPDATE users SET last_login_at=NOW() WHERE id=?", [$u['id']]);
        return true;
    }
    public static function logout(): void {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    }
    public static function requireLogin(): void {
        if (!self::check()) { header('Location: ' . APP_URL . '/login.php'); exit; }
    }
    public static function can(string ...$roles): bool {
        $u = self::user(); return $u && in_array($u['role'], $roles, true);
    }
}
