<?php
namespace Tecnodata\CRM\Services;

use Tecnodata\CRM\Core\DB;
use Throwable;

final class SyncService {
    private OmieClient $omie;
    public function __construct() { $this->omie = new OmieClient(); }

    public function runAll(int $userId): array {
        $lock = DB::fetch("SELECT id, started_at FROM sync_runs WHERE status='running' ORDER BY id DESC LIMIT 1");
        if ($lock) {
            $mins = (time()-strtotime($lock['started_at']))/60;
            if ($mins < ($GLOBALS['config']['sync']['lock_minutes'] ?? 30)) {
                throw new \RuntimeException('Já existe uma sincronização em andamento.');
            }
        }
        DB::exec("INSERT INTO sync_runs (started_by,status,started_at) VALUES (?, 'running', NOW())", [$userId]);
        $runId = (int)DB::conn()->lastInsertId();
        $stats = ['sellers'=>0,'clients'=>0,'orders'=>0,'financial'=>0];

        try {
            $stats['sellers'] = $this->syncSellers();
            $stats['clients'] = $this->syncClients();
            $stats['orders'] = $this->syncOrders();
            $stats['financial'] = $this->syncFinancial();
            $this->rebuildMetrics();
            DB::exec("UPDATE sync_runs SET status='success', finished_at=NOW(), stats_json=? WHERE id=?",
                [json_encode($stats), $runId]);
            return $stats;
        } catch (Throwable $e) {
            DB::exec("UPDATE sync_runs SET status='error', finished_at=NOW(), error_message=? WHERE id=?",
                [mb_substr($e->getMessage(),0,2000), $runId]);
            throw $e;
        }
    }

    private function syncSellers(): int {
        $n=0;
        foreach ($this->omie->paginate('vendedores','ListarVendedores',[],['cadastro','vendedores','lista_vendedores']) as $r) {
            $code = (string)($r['codigo'] ?? $r['nCodVend'] ?? $r['codigo_vendedor'] ?? $r['codVend'] ?? '');
            if ($code==='') continue;
            $name = (string)($r['nome'] ?? $r['nome_vendedor'] ?? $r['cNome'] ?? ('Vendedor '.$code));
            $omieActive=mb_strtoupper(trim((string)($r['inativo']??'N')))!=='S'?1:0;
            DB::exec("INSERT INTO sellers (omie_code,name,email,active,omie_active,raw_json,updated_at)
                      VALUES (?,?,?,?,?,?,NOW())
                      ON DUPLICATE KEY UPDATE name=VALUES(name),email=VALUES(email),omie_active=VALUES(omie_active),raw_json=VALUES(raw_json),updated_at=NOW()",
                [$code,$name,$r['email']??null,$omieActive,$omieActive,json_encode($r,JSON_UNESCAPED_UNICODE)]);
            $n++;
        }
        return $n;
    }

    private function syncClients(): int {
        $n=0;
        foreach ($this->omie->paginate('clientes','ListarClientes',['apenas_importado_api'=>'N'],['clientes_cadastro','clientes','lista_clientes']) as $r) {
            $code=(string)($r['codigo_cliente_omie']??$r['codigo_cliente_integracao']??$r['codigo']??'');
            if ($code==='') continue;
            $name=(string)($r['nome_fantasia']??$r['razao_social']??$r['nome']??('Cliente '.$code));
            $uf=(string)($r['estado']??$r['uf']??'');
            $phone=(string)($r['telefone1_numero']??$r['telefone1_ddd']??'');
            if (!empty($r['telefone1_ddd']) && !empty($r['telefone1_numero'])) $phone='('.$r['telefone1_ddd'].') '.$r['telefone1_numero'];
            DB::exec("INSERT INTO clients (omie_code,name,legal_name,uf,city,phone,email,document,active,raw_json,updated_at)
                      VALUES (?,?,?,?,?,?,?,?,1,?,NOW())
                      ON DUPLICATE KEY UPDATE name=VALUES(name),legal_name=VALUES(legal_name),uf=VALUES(uf),city=VALUES(city),
                      phone=VALUES(phone),email=VALUES(email),document=VALUES(document),raw_json=VALUES(raw_json),updated_at=NOW()",
                [$code,$name,$r['razao_social']??null,$uf,$r['cidade']??null,$phone,$r['email']??null,$r['cnpj_cpf']??null,
                 json_encode($r,JSON_UNESCAPED_UNICODE)]);
            $clientId=(int)(DB::fetch("SELECT id FROM clients WHERE omie_code=?",[$code])['id']??0);
            if($clientId>0)$this->syncClientTags($clientId,$r);
            $n++;
        }
        return $n;
    }

    private function syncOrders(): int {
        $n=0; $start=date('d/m/Y', strtotime($GLOBALS['config']['omie']['history_start'] ?? '-2 years'));
        $base=['filtrar_por_data_de'=>$start,'filtrar_por_data_ate'=>date('d/m/Y')];
        foreach ($this->omie->paginate('pedidos','ListarPedidos',$base,['pedido_venda_produto','pedidos','lista_pedidos']) as $r) {
            $cab=$r['cabecalho']??$r;
            $info=$r['infoCadastro']??[];
            $orderCode=(string)($cab['codigo_pedido']??$cab['codigo_pedido_integracao']??$cab['numero_pedido']??$r['codigo_pedido']??'');
            if ($orderCode==='') $orderCode=sha1(json_encode($r));
            $client=(string)($cab['codigo_cliente']??$cab['codigo_cliente_omie']??$cab['codCli']??'');
            $seller=(string)($cab['codigo_vendedor']??$cab['codVend']??$r['codVend']??'');
            $total=(float)($r['frete']['valor_frete']??0);
            $total=(float)($r['total_pedido']??$r['valor_total_pedido']??$cab['valor_total_pedido']??$cab['valor_total']??$total);
            $date=$info['dInc']??$cab['data_previsao']??$cab['data_pedido']??null;
            $date=$this->normalizeDate($date);
            $status=(string)($info['cStat']??$r['status']??$cab['status']??'');
            DB::exec("INSERT INTO orders (omie_order_code,client_omie_code,seller_omie_code,order_date,total,status,raw_json,updated_at)
                      VALUES (?,?,?,?,?,?,?,NOW())
                      ON DUPLICATE KEY UPDATE client_omie_code=VALUES(client_omie_code),seller_omie_code=VALUES(seller_omie_code),
                      order_date=VALUES(order_date),total=VALUES(total),status=VALUES(status),raw_json=VALUES(raw_json),updated_at=NOW()",
                [$orderCode,$client,$seller,$date,$total,$status,json_encode($r,JSON_UNESCAPED_UNICODE)]);
            $n++;
        }
        return $n;
    }

    private function syncFinancial(): int {
        $n=0;
        // API de movimentos financeiros possui estrutura diferente de paginação em algumas contas.
        // Tentamos a estrutura documentada e persistimos os campos relevantes de forma tolerante.
        $page=1; $per=(int)($GLOBALS['config']['omie']['per_page']??100);
        do {
            $data=$this->omie->call('financeiro','ListarMovimentos',['nPagina'=>$page,'nRegPorPagina'=>$per]);
            $items=$data['movimentos']??$data['lista_movimentos']??$data['movimentos_financeiros']??[];
            foreach ($items as $r) {
                $code=(string)($r['nCodMovCC']??$r['codigo_lancamento_omie']??$r['codigo']??sha1(json_encode($r)));
                $client=(string)($r['nCodCliente']??$r['codigo_cliente_omie']??'');
                $due=$this->normalizeDate($r['dDtVenc']??$r['data_vencimento']??null);
                $pay=$this->normalizeDate($r['dDtPagamento']??$r['data_pagamento']??null);
                $amount=(float)($r['nValorTitulo']??$r['nValorMovimento']??$r['valor_documento']??0);
                $status=(string)($r['cStatus']??$r['status']??'');
                $seller=$this->extractFinancialSellerCode($r);
                DB::exec("INSERT INTO financial_movements (omie_code,client_omie_code,due_date,payment_date,amount,status,seller_omie_code,raw_json,updated_at)
                          VALUES (?,?,?,?,?,?,?,?,NOW())
                          ON DUPLICATE KEY UPDATE client_omie_code=VALUES(client_omie_code),due_date=VALUES(due_date),payment_date=VALUES(payment_date),
                          amount=VALUES(amount),status=VALUES(status),seller_omie_code=VALUES(seller_omie_code),raw_json=VALUES(raw_json),updated_at=NOW()",
                    [$code,$client,$due,$pay,$amount,$status,$seller,json_encode($r,JSON_UNESCAPED_UNICODE)]);
                $n++;
            }
            $pages=(int)($data['nTotPaginas']??$data['total_de_paginas']??$page);
            $page++;
        } while($page <= $pages);
        return $n;
    }

    public function rebuildMetrics(): void {
        DB::exec("TRUNCATE TABLE client_metrics");
        $ignored=$GLOBALS['config']['omie']['ignored_order_statuses']??[];
        $clients=DB::all("SELECT * FROM clients WHERE active=1");
        foreach($clients as $c){
            $orders=DB::all("SELECT * FROM orders WHERE client_omie_code=? ORDER BY order_date ASC",[$c['omie_code']]);
            $valid=array_values(array_filter($orders,function($o) use($ignored){
                return !in_array(mb_strtoupper((string)$o['status']),$ignored,true);
            }));
            $last=$valid ? end($valid) : null;
            $first=$valid[0]??null;
            $now=new \DateTime('today');
            $lastDate=$last && $last['order_date'] ? new \DateTime($last['order_date']) : null;
            $days=$lastDate ? (int)$lastDate->diff($now)->format('%a') : null;
            $yearAgo=(new \DateTime('today'))->modify('-12 months')->format('Y-m-d');
            $orders12=array_values(array_filter($valid,fn($o)=>$o['order_date'] && $o['order_date'] >= $yearAgo));
            $rev12=array_sum(array_map(fn($o)=>(float)$o['total'],$orders12));
            $ticket=count($orders12)?$rev12/count($orders12):0;
            $interval=null;
            if(count($valid)>=2){
                $diffs=[];
                for($i=1;$i<count($valid);$i++){
                    if($valid[$i-1]['order_date'] && $valid[$i]['order_date']){
                        $a=new \DateTime($valid[$i-1]['order_date']); $b=new \DateTime($valid[$i]['order_date']);
                        $diffs[]=(int)$a->diff($b)->format('%a');
                    }
                }
                if($diffs) $interval=array_sum($diffs)/count($diffs);
            }
            $fin=DB::all("SELECT * FROM financial_movements WHERE client_omie_code=?",[$c['omie_code']]);
            $open=0; $overdue=0; $maxDelay=0;
            foreach($fin as $f){
                $st=mb_strtoupper((string)$f['status']);
                if(in_array($st,['EMABERTO','ATRASADO','AVENCER','VENCEHOJE','PAGTO_PARCIAL'],true)) $open+=(float)$f['amount'];
                if($st==='ATRASADO' || (!$f['payment_date'] && $f['due_date'] && $f['due_date']<date('Y-m-d'))){
                    $overdue+=(float)$f['amount'];
                    $dd=(int)(new \DateTime($f['due_date']))->diff(new \DateTime('today'))->format('%a');
                    $maxDelay=max($maxDelay,$dd);
                }
            }
            $status='normal';
            if($days===null || $days>=($GLOBALS['config']['commercial']['reactivate_days']??181)) $status='reactivate';
            elseif($days>=($GLOBALS['config']['commercial']['attention_days']??61)) $status='attention';
            if($interval && $days!==null && $days > ($interval*1.6)) $status='attention';
            $seller=$last['seller_omie_code']??null;
            DB::exec("INSERT INTO client_metrics
                (client_id,seller_omie_code,first_purchase_at,last_purchase_at,days_without_purchase,avg_interval_days,
                 orders_12m,revenue_12m,avg_ticket_12m,open_amount,overdue_amount,max_overdue_days,commercial_status,updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
                [$c['id'],$seller,$first['order_date']??null,$last['order_date']??null,$days,$interval,count($orders12),$rev12,$ticket,$open,$overdue,$maxDelay,$status]);
        }
    }

    private function syncClientTags(int $clientId,array $payload): void {
        $tags=$this->extractClientTags($payload);
        DB::exec("DELETE FROM client_tags WHERE client_id=?",[$clientId]);
        foreach($tags as $tag){
            DB::exec("INSERT IGNORE INTO client_tags(client_id,tag,created_at) VALUES(?,?,NOW())",[$clientId,$tag]);
        }
    }

    private function extractClientTags(array $payload): array {
        $out=[];
        $sources=[];
        foreach(['tags','tag','tags_cliente','clientes_tags'] as $key){
            if(array_key_exists($key,$payload))$sources[]=$payload[$key];
        }
        $walk=function($value) use (&$walk,&$out): void {
            if(is_string($value)){
                $value=trim($value);
                if($value!=='')$out[mb_strtolower($value)]=$value;
                return;
            }
            if(!is_array($value))return;
            foreach($value as $k=>$v){
                $key=mb_strtolower((string)$k);
                if(in_array($key,['tag','ctag','nome','descricao','nome_tag'],true) && is_scalar($v)){
                    $tag=trim((string)$v);
                    if($tag!=='')$out[mb_strtolower($tag)]=$tag;
                }elseif(is_array($v)){
                    $walk($v);
                }
            }
        };
        foreach($sources as $source)$walk($source);
        ksort($out,SORT_NATURAL|SORT_FLAG_CASE);
        return array_values($out);
    }

    private function extractFinancialSellerCode(array $payload): ?string {
        foreach(['cCodVendedor','nCodVendedor','codigo_vendedor','codigo_vendedor_omie','nCodigoVendedor','codVendedor','vendedor_omie_code'] as $key){
            if(isset($payload[$key]) && trim((string)$payload[$key])!=='') return trim((string)$payload[$key]);
        }
        foreach(['detalhes','cabecalho','dadosTitulo','titulo','origem'] as $group){
            if(!isset($payload[$group]) || !is_array($payload[$group])) continue;
            foreach(['cCodVendedor','nCodVendedor','codigo_vendedor','codigo_vendedor_omie','nCodigoVendedor','codVendedor','vendedor_omie_code'] as $key){
                if(isset($payload[$group][$key]) && trim((string)$payload[$group][$key])!=='') return trim((string)$payload[$group][$key]);
            }
        }
        return null;
    }

    private function normalizeDate(?string $d): ?string {
        if(!$d) return null;
        foreach(['d/m/Y','Y-m-d','d/m/y'] as $f){
            $dt=\DateTime::createFromFormat($f,$d);
            if($dt) return $dt->format('Y-m-d');
        }
        $ts=strtotime($d); return $ts?date('Y-m-d',$ts):null;
    }
}
