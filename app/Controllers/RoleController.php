<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\Audit;

final class RoleController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $roles=Database::all("SELECT * FROM roles ORDER BY id");
        $permissions=Database::all("SELECT * FROM permissions ORDER BY name");
        $grants=Database::all("SELECT role_id,permission_id FROM role_permissions");

        $matrix=[];
        foreach($grants as $g){
            $matrix[(int)$g['role_id']][(int)$g['permission_id']]=true;
        }

        View::render('roles/index',[
            'roles'=>$roles,
            'permissions'=>$permissions,
            'matrix'=>$matrix,
        ]);
    }

    public function save(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $role=Database::fetch("SELECT * FROM roles WHERE id=?",[(int)$id]);
        if(!$role) throw new \RuntimeException('Papel não encontrado.');
        if($role['slug']==='super_admin'){
            throw new \RuntimeException('As permissões do Super Administrador são permanentes.');
        }

        $ids=array_map('intval',(array)($_POST['permissions']??[]));

        $pdo=Database::pdo();
        $pdo->beginTransaction();
        try{
            Database::execute("DELETE FROM role_permissions WHERE role_id=?",[(int)$id]);
            foreach($ids as $pid){
                if($pid<=0) continue;
                Database::execute(
                    "INSERT IGNORE INTO role_permissions(role_id,permission_id) VALUES(?,?)",
                    [(int)$id,$pid]
                );
            }
            $pdo->commit();
        }catch(\Throwable $e){
            if($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        Audit::log('role.permissions_changed','role',(int)$id,['permissions'=>$ids]);
        redirect('/admin/roles');
    }
}
