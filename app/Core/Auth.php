<?php
namespace Tecnodata\Lms\Core;

final class Auth
{
    public static function attempt(string $identifier,string $password): bool
    {
        $digits=preg_replace('/\D+/','',$identifier);
        $user=Database::fetch(
            "SELECT * FROM users WHERE status='active' AND (email=:id OR username=:id OR cpf=:cpf) LIMIT 1",
            ['id'=>$identifier,'cpf'=>$digits]
        );
        if(!$user||!password_verify($password,$user['password_hash']))return false;

        session_regenerate_id(true);
        $_SESSION['user_id']=(int)$user['id'];
        Database::execute("UPDATE users SET last_login_at=? WHERE id=?",[Clock::sql(),$user['id']]);
        return true;
    }

    public static function user(): ?array
    {
        $id=(int)($_SESSION['user_id']??0);
        return $id?Database::fetch("SELECT * FROM users WHERE id=?",[$id]):null;
    }

    public static function id(): ?int{return isset($_SESSION['user_id'])?(int)$_SESSION['user_id']:null;}

    public static function logout(): void
    {
        $_SESSION=[];
        if(session_status()===PHP_SESSION_ACTIVE)session_regenerate_id(true);
    }

    public static function requireLogin(): array
    {
        $u=self::user();
        if(!$u)redirect('/login');
        return $u;
    }

    public static function isAdmin(?int $userId=null): bool
    {
        $userId??=self::id();
        if(!$userId)return false;
        return (bool)Database::fetch(
            "SELECT 1 FROM user_role_assignments ura
             JOIN roles r ON r.id=ura.role_id
             WHERE ura.user_id=? AND ura.context_type='system'
               AND r.slug IN('super_admin','admin') LIMIT 1",
            [$userId]
        );
    }

    public static function requireAdmin(): array
    {
        $u=self::requireLogin();
        if(!self::isAdmin((int)$u['id'])){http_response_code(403);exit('Acesso negado.');}
        return $u;
    }

    public static function hasRole(array|string $roles,string $contextType='system',?int $contextId=null,?int $userId=null): bool
    {
        $userId??=self::id();
        if(!$userId)return false;
        $roles=(array)$roles;
        if(!$roles)return false;

        $placeholders=implode(',',array_fill(0,count($roles),'?'));
        $params=array_merge([$userId],$roles,[$contextType]);
        $sql="SELECT 1 FROM user_role_assignments ura
              JOIN roles r ON r.id=ura.role_id
              WHERE ura.user_id=? AND r.slug IN($placeholders)
                AND ura.context_type=?";
        if($contextId!==null){
            $sql.=" AND ura.context_id=?";
            $params[]=$contextId;
        }else{
            $sql.=" AND ura.context_id IS NULL";
        }
        $sql.=" LIMIT 1";
        return (bool)Database::fetch($sql,$params);
    }

    public static function can(string $permission,string $contextType='system',?int $contextId=null,?int $userId=null): bool
    {
        $userId??=self::id();
        if(!$userId)return false;
        if(self::isAdmin($userId))return true;

        $params=[$userId,$permission];
        $sql="SELECT 1
                FROM user_role_assignments ura
                JOIN roles r ON r.id=ura.role_id
                JOIN role_permissions rp ON rp.role_id=r.id
                JOIN permissions p ON p.id=rp.permission_id
               WHERE ura.user_id=? AND p.slug=? AND (ura.context_type='system'";
        if($contextId!==null){
            $sql.=" OR (ura.context_type=? AND ura.context_id=?)";
            $params[]=$contextType;
            $params[]=$contextId;
        }
        $sql.=") LIMIT 1";

        return (bool)Database::fetch($sql,$params);
    }

    public static function requirePermission(string $permission,string $contextType='system',?int $contextId=null): array
    {
        $u=self::requireLogin();
        if(!self::can($permission,$contextType,$contextId,(int)$u['id'])){
            http_response_code(403);
            exit('Você não possui permissão para esta operação.');
        }
        return $u;
    }

    public static function roles(?int $userId=null): array
    {
        $userId??=self::id();
        if(!$userId)return [];
        return Database::all(
            "SELECT r.slug,r.name,ura.context_type,ura.context_id
               FROM user_role_assignments ura
               JOIN roles r ON r.id=ura.role_id
              WHERE ura.user_id=? ORDER BY r.name",
            [$userId]
        );
    }
}
