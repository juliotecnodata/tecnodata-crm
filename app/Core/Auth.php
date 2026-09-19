<?php
namespace Tecnodata\Lms\Core;

final class Auth
{
    public static function attempt(string $identifier, string $password): bool
    {
        $digits = preg_replace('/\D+/', '', $identifier);
        $user = Database::fetch(
            "SELECT * FROM users
             WHERE status='active'
               AND (email = :id OR username = :id OR cpf = :cpf)
             LIMIT 1",
            ['id' => $identifier, 'cpf' => $digits]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];

        Database::execute(
            "UPDATE users SET last_login_at = ? WHERE id = ?",
            [Clock::sql(), $user['id']]
        );

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
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
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
            "SELECT 1
               FROM user_role_assignments ura
               JOIN roles r ON r.id=ura.role_id
              WHERE ura.user_id=?
                AND r.slug IN ('super_admin','admin')
              LIMIT 1",
            [$userId]
        );
    }

    public static function requireAdmin(): array
    {
        $user = self::requireLogin();
        if (!self::isAdmin((int) $user['id'])) {
            http_response_code(403);
            exit('Acesso negado.');
        }
        return $user;
    }

    public static function can(
        string $permission,
        string $contextType = 'system',
        ?int $contextId = null,
        ?int $userId = null
    ): bool {
        $userId ??= self::id();
        if (!$userId) return false;
        if (self::isAdmin($userId)) return true;

        $sql = "SELECT 1
                  FROM user_role_assignments ura
                  JOIN roles r ON r.id=ura.role_id
                  JOIN role_permissions rp ON rp.role_id=r.id
                  JOIN permissions p ON p.id=rp.permission_id
                 WHERE ura.user_id=?
                   AND p.slug=?
                   AND (
                        ura.context_type='system'
                        OR (ura.context_type=? AND ura.context_id=?)
                   )
                 LIMIT 1";

        return (bool) Database::fetch($sql, [$userId, $permission, $contextType, $contextId]);
    }

    public static function roles(?int $userId = null): array
    {
        $userId ??= self::id();
        if (!$userId) return [];

        return Database::all(
            "SELECT r.slug,r.name,ura.context_type,ura.context_id
               FROM user_role_assignments ura
               JOIN roles r ON r.id=ura.role_id
              WHERE ura.user_id=?
              ORDER BY r.name",
            [$userId]
        );
    }
}
