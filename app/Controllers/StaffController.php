<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\Audit;

final class StaffController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $users=Database::all(
            "SELECT u.*,GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') roles
               FROM users u
               LEFT JOIN user_role_assignments ura ON ura.user_id=u.id AND ura.context_type='system'
               LEFT JOIN roles r ON r.id=ura.role_id
              WHERE u.user_type<>'student'
              GROUP BY u.id
              ORDER BY u.name"
        );

        $roles=Database::all(
            "SELECT id,slug,name FROM roles WHERE slug<>'student' ORDER BY name"
        );

        View::render('staff/index',['users'=>$users,'roles'=>$roles]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $name=trim((string)($_POST['name']??''));
        $email=trim((string)($_POST['email']??''));
        $password=(string)($_POST['password']??'');
        $roleId=(int)($_POST['role_id']??0);

        if($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<8){
            throw new \RuntimeException('Informe nome, e-mail válido e senha com pelo menos 8 caracteres.');
        }

        if(Database::fetch("SELECT 1 FROM users WHERE email=? OR username=?",[$email,$email])){
            throw new \RuntimeException('E-mail já cadastrado.');
        }

        Database::execute(
            "INSERT INTO users(user_type,username,name,email,password_hash,status,created_at,updated_at)
             VALUES('staff',?,?,?,?, 'active',?,?)",
            [$email,$name,$email,password_hash($password,PASSWORD_DEFAULT),Clock::sql(),Clock::sql()]
        );
        $id=Database::id();

        if($roleId>0){
            Database::execute(
                "INSERT INTO user_role_assignments(user_id,role_id,context_type,context_id,created_at)
                 VALUES(?,?,'system',NULL,?)",
                [$id,$roleId,Clock::sql()]
            );
        }

        Audit::log('staff.created','user',$id);
        redirect('/admin/staff');
    }

    public function status(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        if((int)$id===Auth::id()){
            throw new \RuntimeException('Não é permitido desativar o próprio usuário.');
        }

        $status=(string)($_POST['status']??'active');
        if(!in_array($status,['active','inactive'],true)){
            throw new \RuntimeException('Status inválido.');
        }

        Database::execute(
            "UPDATE users SET status=?,updated_at=? WHERE id=? AND user_type<>'student'",
            [$status,Clock::sql(),(int)$id]
        );
        Audit::log('staff.status_changed','user',(int)$id,['status'=>$status]);
        redirect('/admin/staff');
    }

    public function role(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $roleId=(int)($_POST['role_id']??0);
        $role=Database::fetch("SELECT * FROM roles WHERE id=? AND slug<>'student'",[$roleId]);
        if(!$role) throw new \RuntimeException('Papel inválido.');

        Database::execute(
            "DELETE ura FROM user_role_assignments ura
             JOIN roles r ON r.id=ura.role_id
             WHERE ura.user_id=? AND ura.context_type='system' AND r.slug<>'student'",
            [(int)$id]
        );

        Database::execute(
            "INSERT INTO user_role_assignments(user_id,role_id,context_type,context_id,created_at)
             VALUES(?,?,'system',NULL,?)",
            [(int)$id,$roleId,Clock::sql()]
        );

        Audit::log('staff.role_changed','user',(int)$id,['role'=>$role['slug']]);
        redirect('/admin/staff');
    }
}
