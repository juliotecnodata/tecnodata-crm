<?php
namespace Tecnodata\CRM\Services;

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;

final class InteractionService {
    private static function canManage(array $row, int $userId): bool {
        return (int)$row['user_id'] === $userId || Auth::can('supervisor','admin');
    }

    private static function audit(string $type,int $id,string $operation,int $userId,?array $before,?array $after): void {
        DB::exec("INSERT INTO interaction_audit(entity_type,entity_id,operation,user_id,before_json,after_json,created_at)
                  VALUES(?,?,?,?,?,?,NOW())",
            [$type,$id,$operation,$userId,
             $before?json_encode($before,JSON_UNESCAPED_UNICODE):null,
             $after?json_encode($after,JSON_UNESCAPED_UNICODE):null]);
    }

    public static function registerSales(int $clientId,int $userId,string $type,string $result,string $notes,?string $nextAt): int {
        $types=['ligacao','whatsapp','email','visita'];
        $results=['falou','nao_atendeu','interessado','sem_interesse','acordo'];
        if(!in_array($type,$types,true))$type='ligacao';
        if(!in_array($result,$results,true))$result='falou';

        DB::exec("INSERT INTO activities(client_id,user_id,type,result,notes,created_at) VALUES(?,?,?,?,?,NOW())",
            [$clientId,$userId,$type,$result,$notes]);
        $id=(int)DB::conn()->lastInsertId();
        self::audit('sales_activity',$id,'create',$userId,null,DB::fetch("SELECT * FROM activities WHERE id=?",[$id]));

        DB::exec("UPDATE tasks SET status='done',completed_at=NOW()
                  WHERE client_id=? AND user_id=? AND status='pending'
                    AND title='Retorno comercial' AND due_at<=DATE_ADD(NOW(),INTERVAL 30 MINUTE)",
            [$clientId,$userId]);

        if($nextAt){
            DB::exec("INSERT INTO tasks(client_id,user_id,title,due_at,status,created_at)
                      VALUES(?,?,?,?,'pending',NOW())",[$clientId,$userId,'Retorno comercial',$nextAt]);
        }
        return $id;
    }

    public static function updateSales(int $id,int $userId,string $type,string $result,string $notes): array {
        $row=DB::fetch("SELECT * FROM activities WHERE id=? AND deleted_at IS NULL",[$id]);
        if(!$row)throw new \RuntimeException('Atendimento não encontrado.');
        if(!self::canManage($row,$userId))throw new \RuntimeException('Sem permissão para editar este atendimento.');

        $types=['ligacao','whatsapp','email','visita'];
        $results=['falou','nao_atendeu','interessado','sem_interesse','acordo'];
        if(!in_array($type,$types,true))$type=$row['type'];
        if(!in_array($result,$results,true))$result=$row['result'];

        DB::exec("UPDATE activities SET type=?,result=?,notes=?,updated_at=NOW(),updated_by=? WHERE id=?",
            [$type,$result,$notes,$userId,$id]);
        $after=DB::fetch("SELECT * FROM activities WHERE id=?",[$id]);
        self::audit('sales_activity',$id,'update',$userId,$row,$after);
        return $after;
    }

    public static function deleteSales(int $id,int $userId): void {
        $row=DB::fetch("SELECT * FROM activities WHERE id=? AND deleted_at IS NULL",[$id]);
        if(!$row)throw new \RuntimeException('Atendimento não encontrado.');
        if(!self::canManage($row,$userId))throw new \RuntimeException('Sem permissão para excluir este atendimento.');
        DB::exec("UPDATE activities SET deleted_at=NOW(),deleted_by=? WHERE id=?",[$userId,$id]);
        self::audit('sales_activity',$id,'delete',$userId,$row,null);
    }
}
