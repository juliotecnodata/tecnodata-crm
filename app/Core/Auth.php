<?php
namespace Tecnodata\Lms\Core;

final class Auth
{
    public static function attempt(string $identifier, string $password): bool
    {
        $digits = preg_replace('/\D+/', '', $identifier);
        $user = Database::fetch(
            "SELECT * FROM users WHERE status='active' AND (email = :id OR username = :id OR cpf = :cpf) LIMIT 1",
            ['id' => $identifier, 'cpf' => $digits]
        );
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        $_SESSION['user_id'] = (int) $user['id'];
        Database::execute("UPDATE users SET last_login_at = ? WHERE id = ?", [Clock::sql(), $user['id']]);
        return true;
    }

    public static function user(): ?array
    {
        $id = (int) ($_SESSION['user_id'] ?? 0);
        return $id ? Database::fetch("SELECT * FROM users WHERE id = ?", [$id]) : null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if (!$user) redirect('/login');
        return $user;
    }

    public static function isAdmin(?int $userId = null): bool
    {
        $userId ??= self::id();
        if (!$userId) return false;
        return (bool) Database::fetch(
            "SELECT 1 FROM user_role_assignments ura
             JOIN roles r ON r.id=ura.role_id
             WHERE ura.user_id=? AND r.slug IN ('super_admin','admin') LIMIT 1",
            [$userId]
        );
    }
}
