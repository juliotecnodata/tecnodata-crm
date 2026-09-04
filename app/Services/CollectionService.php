<?php
namespace Tecnodata\CRM\Services;

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;

final class CollectionService {
    public static function omieDebt(string $clientCode): float {
        $r=DB::fetch("SELECT COALESCE(SUM(fm.amount),0) total
            FROM financial_movements fm
            INNER JOIN financial_accounts fa ON fa.omie_code=fm.account_omie_code AND fa.selected=1 AND fa.active=1
            WHERE fm.client_omie_code=? AND UPPER(fm.status) IN('ATRASADO','PAGTO_PARCIAL')",[$clientCode]);
        return max(0,(float)($r['total']??0));
    }

    public static function debtState(int $clientId,string $clientCode): array {
        $omie=self::omieDebt($clientCode);
        $adj=DB::fetch("SELECT * FROM collection_client_adjustments WHERE client_id=?",[$clientId]);
        $pending=(float)($adj['pending_received']??0);
        $effective=max(0,$omie-$pending);
        $status=$effective<=0.009 ? ($pending>0?'settled_local':'settled_omie') : ($pending>0?'partial_local':'overdue');
        return ['omie_debt'=>$omie,'pending_received'=>$pending,'effective_debt'=>$effective,'status'=>$status,'adjustment'=>$adj];
    }

    public static function debtSellerCode(string $clientCode): ?string {
        $db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();
        $exists=(bool)DB::fetch("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='financial_movements' AND COLUMN_NAME='seller_omie_code' LIMIT 1",[$db]);
        if(!$exists)return null;
        $r=DB::fetch("SELECT COUNT(DISTINCT NULLIF(fm.seller_omie_code,'')) sellers,
                             MAX(NULLIF(fm.seller_omie_code,'')) seller_code
                      FROM financial_movements fm
                      INNER JOIN financial_accounts fa ON fa.omie_code=fm.account_omie_code AND fa.selected=1 AND fa.active=1
                      WHERE fm.client_omie_code=?
                        AND UPPER(fm.status) IN('ATRASADO','PAGTO_PARCIAL')",[$clientCode]);
        return (int)($r['sellers']??0)===1 ? (string)$r['seller_code'] : null;
    }

    private static function audit(int $id,string $operation,int $userId,?array $before,?array $after): void {
        DB::exec("INSERT INTO interaction_audit(entity_type,entity_id,operation,user_id,before_json,after_json,created_at)
                  VALUES('collection_action',?,?,?,?,?,NOW())",
            [$id,$operation,$userId,
             $before?json_encode($before,JSON_UNESCAPED_UNICODE):null,
             $after?json_encode($after,JSON_UNESCAPED_UNICODE):null]);
    }

    private static function canManage(array $row,int $userId): bool {
        return (int)$row['user_id']===$userId
            || (int)($row['assigned_user_id']??0)===$userId
            || Auth::can('supervisor','admin');
    }

    private static function setAdjustmentPending(int $clientId,float $pending,float $baseline): void {
        $pending=max(0,$pending);
        DB::exec("INSERT INTO collection_client_adjustments(client_id,pending_received,baseline_omie_debt,last_payment_at,updated_at)
                  VALUES(?,?,?,NOW(),NOW())
                  ON DUPLICATE KEY UPDATE pending_received=VALUES(pending_received),
                    baseline_omie_debt=VALUES(baseline_omie_debt),updated_at=NOW()",
            [$clientId,$pending,$baseline]);
    }

    public static function registerAction(int $clientId,int $userId,string $actionType,string $channel,string $result,float $amount,?string $promisedFor,?string $nextAt,string $notes): array {
        $client=DB::fetch("SELECT id,omie_code FROM clients WHERE id=?",[$clientId]);
        if(!$client)throw new \RuntimeException('Cliente não encontrado.');

        $before=self::debtState($clientId,(string)$client['omie_code']);
        $debtSeller=self::debtSellerCode((string)$client['omie_code']);
        $actionType=in_array($actionType,['contact','promise','agreement','payment'],true)?$actionType:'contact';
        $channel=in_array($channel,['ligacao','whatsapp','email','outro'],true)?$channel:'ligacao';
        $allowedResults=['falou','nao_atendeu','promessa','acordo','pagamento','sem_previsao'];
        $result=in_array($result,$allowedResults,true)?$result:'falou';

        if($actionType==='payment'&&$amount<=0)throw new \RuntimeException('Informe o valor recebido.');
        if($amount>$before['effective_debt']&&$before['effective_debt']>0)$amount=$before['effective_debt'];
        if($actionType!=='payment')$amount=0;
        $pendingAmount=$actionType==='payment'?$amount:0;
        $afterAmount=$actionType==='payment'?max(0,$before['effective_debt']-$amount):$before['effective_debt'];

        $pdo=DB::conn();$pdo->beginTransaction();
        try{
            DB::exec("INSERT INTO collection_actions
              (seller_omie_code,client_id,user_id,assigned_user_id,assigned_at,assigned_by,action_type,channel,result,amount,pending_amount,debt_before,debt_after,promised_for,notes,created_at)
              VALUES(?,?,?,?,NOW(),?,?,?,?,?,?,?,?,?,?,NOW())",
              [$debtSeller,$clientId,$userId,$userId,$userId,$actionType,$channel,$result,$amount,$pendingAmount,$before['effective_debt'],$afterAmount,$promisedFor,$notes]);
            $id=(int)$pdo->lastInsertId();

            DB::exec("UPDATE tasks SET status='done',completed_at=NOW()
                      WHERE client_id=? AND user_id=? AND status='pending'
                        AND title LIKE 'Cobrança:%' AND due_at<=DATE_ADD(NOW(),INTERVAL 30 MINUTE)",[$clientId,$userId]);

            if($actionType==='payment'){
                self::setAdjustmentPending($clientId,$before['pending_received']+$pendingAmount,$before['omie_debt']);
                if($afterAmount<=0.009){
                    DB::exec("UPDATE tasks SET status='done',completed_at=NOW()
                              WHERE client_id=? AND status='pending' AND title LIKE 'Cobrança:%'",[$clientId]);
                }
            }
            if($nextAt){
                DB::exec("INSERT INTO tasks(client_id,user_id,title,due_at,status,created_at)
                          VALUES(?,?,?,?,'pending',NOW())",[$clientId,$userId,'Cobrança: retorno',$nextAt]);
            }
            if($promisedFor&&in_array($actionType,['promise','agreement'],true)){
                $sameDay=$nextAt&&substr((string)$nextAt,0,10)===$promisedFor;
                if(!$sameDay){
                    DB::exec("INSERT INTO tasks(client_id,user_id,title,due_at,status,created_at)
                              VALUES(?,?,?,?,'pending',NOW())",
                        [$clientId,$userId,'Cobrança: promessa de pagamento',$promisedFor.' 09:00:00']);
                }
            }
            $afterRow=DB::fetch("SELECT * FROM collection_actions WHERE id=?",[$id]);
            self::audit($id,'create',$userId,null,$afterRow);
            $pdo->commit();
        }catch(\Throwable $e){
            if($pdo->inTransaction())$pdo->rollBack();
            throw $e;
        }
        return self::debtState($clientId,(string)$client['omie_code']);
    }

    public static function updateAction(int $id,int $userId,string $channel,string $result,float $amount,?string $promisedFor,string $notes,?int $assignedUserId=null): array {
        $row=DB::fetch("SELECT ca.*,c.omie_code FROM collection_actions ca JOIN clients c ON c.id=ca.client_id
                       WHERE ca.id=? AND ca.deleted_at IS NULL",[$id]);
        if(!$row)throw new \RuntimeException('Cobrança não encontrada.');
        if(!self::canManage($row,$userId))throw new \RuntimeException('Sem permissão para editar esta cobrança.');

        $channel=in_array($channel,['ligacao','whatsapp','email','outro'],true)?$channel:$row['channel'];
        $allowed=['falou','nao_atendeu','promessa','acordo','pagamento','sem_previsao'];
        $result=in_array($result,$allowed,true)?$result:$row['result'];
        $actionMap=['promessa'=>'promise','acordo'=>'agreement','pagamento'=>'payment'];
        $newType=$actionMap[$result]??'contact';

        $state=self::debtState((int)$row['client_id'],(string)$row['omie_code']);
        $oldPending=(float)$row['pending_amount'];
        $oldAmount=(float)$row['amount'];
        $reconciled=max(0,$oldAmount-$oldPending);

        if($newType==='payment'){
            if($amount<=0)throw new \RuntimeException('Informe o valor recebido.');
            // Permite corrigir o valor sem reintroduzir parte já conciliada.
            $newPending=max(0,$amount-$reconciled);
        }else{
            $amount=0;
            $newPending=0;
        }

        $newTotalPending=max(0,$state['pending_received']-$oldPending+$newPending);
        $newDebt=max(0,$state['omie_debt']-$newTotalPending);

        $currentAssigned=(int)($row['assigned_user_id']??$row['user_id']);
        $targetAssigned=$currentAssigned;
        if($assignedUserId!==null && $assignedUserId>0 && Auth::can('supervisor','admin')){
            $validAssignee=DB::fetch("SELECT id FROM users WHERE id=? AND active=1 AND role IN('collector','supervisor','admin')",[$assignedUserId]);
            if(!$validAssignee)throw new \RuntimeException('Responsável de cobrança inválido ou inativo.');
            $targetAssigned=(int)$validAssignee['id'];
        }

        $assignmentChanged=$targetAssigned!==$currentAssigned;
        $assignedAt=$assignmentChanged?date('Y-m-d H:i:s'):($row['assigned_at']??$row['created_at']);
        $assignedBy=$assignmentChanged?$userId:(int)($row['assigned_by']??$row['user_id']);

        $pdo=DB::conn();$pdo->beginTransaction();
        try{
            DB::exec("UPDATE collection_actions SET action_type=?,channel=?,result=?,amount=?,pending_amount=?,
                      debt_after=?,promised_for=?,notes=?,assigned_user_id=?,assigned_at=?,assigned_by=?,
                      updated_at=NOW(),updated_by=? WHERE id=?",
                [$newType,$channel,$result,$amount,$newPending,$newDebt,$promisedFor,$notes,
                 $targetAssigned,$assignedAt,$assignedBy,$userId,$id]);
            self::setAdjustmentPending((int)$row['client_id'],$newTotalPending,$state['omie_debt']);
            if($assignmentChanged){
                DB::exec("UPDATE tasks SET user_id=?
                          WHERE client_id=? AND user_id=? AND status='pending' AND title LIKE 'Cobrança:%'",
                    [$targetAssigned,(int)$row['client_id'],$currentAssigned]);
            }
            $after=DB::fetch("SELECT * FROM collection_actions WHERE id=?",[$id]);
            self::audit($id,'update',$userId,$row,$after);
            $pdo->commit();
            return $after;
        }catch(\Throwable $e){
            if($pdo->inTransaction())$pdo->rollBack();
            throw $e;
        }
    }

    public static function deleteAction(int $id,int $userId): void {
        $row=DB::fetch("SELECT ca.*,c.omie_code FROM collection_actions ca JOIN clients c ON c.id=ca.client_id
                       WHERE ca.id=? AND ca.deleted_at IS NULL",[$id]);
        if(!$row)throw new \RuntimeException('Cobrança não encontrada.');
        if(!self::canManage($row,$userId))throw new \RuntimeException('Sem permissão para excluir esta cobrança.');

        $state=self::debtState((int)$row['client_id'],(string)$row['omie_code']);
        $pending=max(0,$state['pending_received']-(float)$row['pending_amount']);

        $pdo=DB::conn();$pdo->beginTransaction();
        try{
            DB::exec("UPDATE collection_actions SET deleted_at=NOW(),deleted_by=? WHERE id=?",[$userId,$id]);
            self::setAdjustmentPending((int)$row['client_id'],$pending,$state['omie_debt']);
            self::audit($id,'delete',$userId,$row,null);
            $pdo->commit();
        }catch(\Throwable $e){
            if($pdo->inTransaction())$pdo->rollBack();
            throw $e;
        }
    }

    public static function reconcileAll(): void {
        $rows=DB::all("SELECT a.*,c.omie_code FROM collection_client_adjustments a
                      JOIN clients c ON c.id=a.client_id WHERE a.pending_received>0");
        foreach($rows as $r){
            $current=self::omieDebt((string)$r['omie_code']);
            $baseline=(float)$r['baseline_omie_debt'];
            $pending=(float)$r['pending_received'];
            $reflected=max(0,$baseline-$current);
            $toApply=min($pending,$reflected);

            if($toApply>0){
                $payments=DB::all("SELECT id,pending_amount FROM collection_actions
                                  WHERE client_id=? AND action_type='payment' AND deleted_at IS NULL
                                    AND pending_amount>0 ORDER BY created_at ASC,id ASC",[$r['client_id']]);
                foreach($payments as $p){
                    if($toApply<=0)break;
                    $take=min($toApply,(float)$p['pending_amount']);
                    DB::exec("UPDATE collection_actions SET pending_amount=GREATEST(0,pending_amount-?) WHERE id=?",[$take,$p['id']]);
                    $toApply-=$take;
                }
            }

            $remaining=(float)(DB::fetch("SELECT COALESCE(SUM(pending_amount),0) total FROM collection_actions
                                         WHERE client_id=? AND action_type='payment' AND deleted_at IS NULL",[$r['client_id']])['total']??0);
            if($current<=0.009)$remaining=0;
            DB::exec("UPDATE collection_client_adjustments SET pending_received=?,baseline_omie_debt=?,updated_at=NOW()
                      WHERE client_id=?",[$remaining,$current,$r['client_id']]);
        }
    }

    public static function monthForUser(int $userId,string $month): array {
        $goal=DB::fetch("SELECT * FROM collection_user_goals WHERE user_id=? AND month_ref=?",[$userId,$month]);
        $start=$month.'-01';$next=date('Y-m-d',strtotime($start.' +1 month'));
        $stats=DB::fetch("SELECT COALESCE(SUM(CASE WHEN action_type='payment' THEN amount ELSE 0 END),0) recovered,
            COUNT(DISTINCT client_id) worked, COUNT(*) actions,
            COUNT(DISTINCT CASE WHEN result='acordo' THEN client_id END) agreements,
            COUNT(DISTINCT CASE WHEN result='promessa' THEN client_id END) promises
            FROM collection_actions WHERE user_id=? AND deleted_at IS NULL AND created_at>=? AND created_at<?",
            [$userId,$start,$next])??[];
        $amountGoal=(float)($goal['amount_goal']??0);$contactGoal=(int)($goal['contact_goal']??0);
        $recovered=(float)($stats['recovered']??0);$worked=(int)($stats['worked']??0);
        return ['amount_goal'=>$amountGoal,'contact_goal'=>$contactGoal,'recovered'=>$recovered,'worked'=>$worked,
            'actions'=>(int)($stats['actions']??0),'agreements'=>(int)($stats['agreements']??0),
            'promises'=>(int)($stats['promises']??0),
            'amount_missing'=>max(0,$amountGoal-$recovered),
            'amount_percent'=>$amountGoal?$recovered/$amountGoal*100:0,
            'contact_percent'=>$contactGoal?$worked/$contactGoal*100:0];
    }

    public static function recoveredMonth(string $month): float {
        $start=$month.'-01';$next=date('Y-m-d',strtotime($start.' +1 month'));
        $r=DB::fetch("SELECT COALESCE(SUM(amount),0) total FROM collection_actions
                     WHERE action_type='payment' AND deleted_at IS NULL AND created_at>=? AND created_at<?",[$start,$next]);
        return (float)($r['total']??0);
    }

    public static function portfolioSummary(): array {
        $r=DB::fetch("SELECT COUNT(*) clients,
            SUM(effective_debt>0.009) open_clients,
            SUM(effective_debt<=0.009) settled_local,
            SUM(partial_titles>0) partial_clients,
            COALESCE(SUM(partial_paid),0) partial_paid,
            COALESCE(SUM(effective_debt),0) amount
          FROM (
            SELECT c.id,
                   GREATEST(0,COALESCE(SUM(fm.amount),0)-COALESCE(adj.pending_received,0)) effective_debt,
                   SUM(fm.status='PAGTO_PARCIAL') partial_titles,
                   COALESCE(SUM(CASE WHEN fm.status='PAGTO_PARCIAL' THEN fm.paid_amount ELSE 0 END),0) partial_paid
            FROM clients c
            INNER JOIN financial_movements fm ON fm.client_omie_code=c.omie_code
              AND fm.status IN('ATRASADO','PAGTO_PARCIAL')
            INNER JOIN financial_accounts fa ON fa.omie_code=fm.account_omie_code
              AND fa.selected=1 AND fa.active=1
            LEFT JOIN collection_client_adjustments adj ON adj.client_id=c.id
            GROUP BY c.id,adj.pending_received
          ) x")??[];
        return [
            'clients'=>(int)($r['clients']??0),
            'open_clients'=>(int)($r['open_clients']??0),
            'settled_local'=>(int)($r['settled_local']??0),
            'partial_clients'=>(int)($r['partial_clients']??0),
            'partial_paid'=>(float)($r['partial_paid']??0),
            'amount'=>(float)($r['amount']??0)
        ];
    }
}
