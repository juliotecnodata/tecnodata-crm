<?php
namespace Tecnodata\CRM\Services;

use RuntimeException;
use Tecnodata\CRM\Core\DB;

final class FinancialAccountService {
    public static function all(): array {
        return DB::all('SELECT * FROM financial_accounts ORDER BY selected DESC,active DESC,name');
    }

    public static function selected(): ?array {
        return DB::fetch('SELECT * FROM financial_accounts WHERE selected=1 AND active=1 ORDER BY name LIMIT 1');
    }

    public static function selectedAll(): array {
        return DB::all('SELECT * FROM financial_accounts WHERE selected=1 AND active=1 ORDER BY name');
    }

    public static function select(string $code): void {
        self::selectMany([$code]);
    }

    public static function selectMany(array $codes): void {
        $codes=array_values(array_unique(array_filter(array_map('strval',$codes),fn($code)=>$code!=='')));
        if(!$codes) throw new RuntimeException('Selecione pelo menos uma conta financeira.');
        $placeholders=implode(',',array_fill(0,count($codes),'?'));
        $valid=DB::all("SELECT omie_code FROM financial_accounts WHERE active=1 AND omie_code IN ({$placeholders})",$codes);
        if(count($valid)!==count($codes)) throw new RuntimeException('Uma das contas selecionadas é inválida ou está inativa.');
        $pdo=DB::conn();
        $pdo->beginTransaction();
        try{
            DB::exec('UPDATE financial_accounts SET selected=0 WHERE selected=1');
            DB::exec("UPDATE financial_accounts SET selected=1,updated_at=NOW() WHERE omie_code IN ({$placeholders})",$codes);
            DB::exec("DELETE FROM sync_states WHERE module_key='financial'");
            // Não apaga a carteira financeira ao trocar o escopo. As telas já filtram pelas contas selecionadas
            // e a próxima sincronização reconcilia os títulos com segurança.
            $pdo->commit();
        }catch(\Throwable $e){
            if($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function refresh(): int {
        $client=new OmieClient();
        $page=1;$count=0;
        do{
            $data=$client->call('contas_correntes','ListarContasCorrentes',[
                'pagina'=>$page,
                'registros_por_pagina'=>100,
                'apenas_importado_api'=>'N',
            ]);
            $items=$data['ListarContasCorrentes']??$data['conta_corrente_lista']??[];
            foreach($items as $item){
                $code=(string)($item['nCodCC']??$item['codigo']??'');
                if($code==='') continue;
                $active=mb_strtoupper((string)($item['inativo']??'N'))!=='S'?1:0;
                DB::exec("INSERT INTO financial_accounts(omie_code,name,account_type,active,selected,updated_at)
                          VALUES(?,?,?,?,0,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),account_type=VALUES(account_type),active=VALUES(active),updated_at=NOW()",
                    [$code,(string)($item['descricao']??('Conta '.$code)),(string)($item['tipo_conta_corrente']??$item['tipo']??''),$active]);
                $count++;
            }
            $pages=max(1,(int)($data['total_de_paginas']??1));
            $page++;
        }while($page<=$pages);
        return $count;
    }
}
