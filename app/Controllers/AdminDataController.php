<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Database;

final class AdminDataController
{
    public function students(): void
    {
        Auth::requireAdmin();

        $draw=max(0,(int)($_GET['draw']??0));
        $start=max(0,(int)($_GET['start']??0));
        $length=min(200,max(10,(int)($_GET['length']??25)));
        $search=trim((string)($_GET['search']['value']??''));

        $columns=['u.id','u.name','u.cpf','u.email','enrollments','active_enrollments','u.status'];
        $orderIndex=(int)($_GET['order'][0]['column']??0);
        $order=$columns[$orderIndex]??'u.id';
        $dir=strtolower((string)($_GET['order'][0]['dir']??'desc'))==='asc'?'ASC':'DESC';

        $where="u.user_type='student'";
        $params=[];
        if($search!==''){
            $where.=" AND (u.name LIKE ? OR u.cpf LIKE ? OR u.email LIKE ? OR CAST(u.id AS CHAR) LIKE ?)";
            $like='%'.$search.'%';
            $params=[$like,$like,$like,$like];
        }

        $total=(int)(Database::fetch("SELECT COUNT(*) c FROM users u WHERE u.user_type='student'")['c']??0);
        $filtered=(int)(Database::fetch("SELECT COUNT(*) c FROM users u WHERE {$where}",$params)['c']??0);

        $rows=Database::all(
            "SELECT u.id,u.name,u.cpf,u.email,u.status,u.last_login_at,
                    COUNT(e.id) enrollments,
                    SUM(e.status='active') active_enrollments
               FROM users u
               LEFT JOIN enrollments e ON e.user_id=u.id
              WHERE {$where}
              GROUP BY u.id
              ORDER BY {$order} {$dir}
              LIMIT {$length} OFFSET {$start}",
            $params
        );

        foreach($rows as &$r){
            $r['enrollments']=(int)$r['enrollments'];
            $r['active_enrollments']=(int)($r['active_enrollments']??0);
            $r['actions']='<a class="btn btn-sm btn-outline-secondary" href="/admin/students/'.(int)$r['id'].'">Abrir</a>';
        }
        unset($r);

        $this->json([
            'draw'=>$draw,
            'recordsTotal'=>$total,
            'recordsFiltered'=>$filtered,
            'data'=>$rows,
        ]);
    }

    public function enrollments(): void
    {
        Auth::requireAdmin();

        $draw=max(0,(int)($_GET['draw']??0));
        $start=max(0,(int)($_GET['start']??0));
        $length=min(200,max(10,(int)($_GET['length']??25)));
        $search=trim((string)($_GET['search']['value']??''));

        $columns=['e.id','u.name','u.cpf','c.name','e.progress_percent','e.started_at','e.expires_at','e.status'];
        $orderIndex=(int)($_GET['order'][0]['column']??0);
        $order=$columns[$orderIndex]??'e.id';
        $dir=strtolower((string)($_GET['order'][0]['dir']??'desc'))==='asc'?'ASC':'DESC';

        $where='1=1';
        $params=[];
        if($search!==''){
            $where.=" AND (u.name LIKE ? OR u.cpf LIKE ? OR c.name LIKE ? OR c.code LIKE ? OR e.status LIKE ? OR CAST(e.id AS CHAR) LIKE ?)";
            $like='%'.$search.'%';
            $params=[$like,$like,$like,$like,$like,$like];
        }

        $total=(int)(Database::fetch("SELECT COUNT(*) c FROM enrollments")['c']??0);
        $filtered=(int)(Database::fetch(
            "SELECT COUNT(*) c
               FROM enrollments e
               JOIN users u ON u.id=e.user_id
               JOIN courses c ON c.id=e.course_id
              WHERE {$where}",
            $params
        )['c']??0);

        $rows=Database::all(
            "SELECT e.id,e.progress_percent,e.started_at,e.expires_at,e.completed_at,e.status,
                    u.name student,u.cpf,c.name course,c.code course_code
               FROM enrollments e
               JOIN users u ON u.id=e.user_id
               JOIN courses c ON c.id=e.course_id
              WHERE {$where}
              ORDER BY {$order} {$dir}
              LIMIT {$length} OFFSET {$start}",
            $params
        );

        foreach($rows as &$r){
            $r['actions']='<a class="btn btn-sm btn-outline-secondary" href="/admin/enrollments/'.(int)$r['id'].'">Abrir</a>';
        }
        unset($r);

        $this->json([
            'draw'=>$draw,
            'recordsTotal'=>$total,
            'recordsFiltered'=>$filtered,
            'data'=>$rows,
        ]);
    }

    private function json(array $data): never
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit;
    }
}
