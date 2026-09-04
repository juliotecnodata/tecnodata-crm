<?php
namespace Tecnodata\CRM\Services;

use Tecnodata\CRM\Core\DB;
use RuntimeException;
use Throwable;

final class ModularSyncService {
    private OmieClient $omie;
    private int $pageSize;

    public function __construct() {
        $this->omie = new OmieClient();
        $this->pageSize = max(20, min(100, (int)($GLOBALS['config']['sync']['page_size'] ?? 50)));
    }

    public function modules(): array {
        return [
            'sellers' => ['label'=>'Vendedores', 'icon'=>'fa-user-tie'],
            'clients' => ['label'=>'Clientes', 'icon'=>'fa-building'],
            'orders' => ['label'=>'Pedidos', 'icon'=>'fa-cart-shopping'],
            'services' => ['label'=>'Ordens de Serviço', 'icon'=>'fa-graduation-cap'],
            'financial' => ['label'=>'Financeiro', 'icon'=>'fa-file-invoice-dollar'],
            'metrics' => ['label'=>'Indicadores', 'icon'=>'fa-chart-line'],
        ];
    }

    public function status(): array {
        $active = DB::fetch("SELECT *,TIMESTAMPDIFF(SECOND,COALESCE(heartbeat_at,started_at),NOW()) idle_seconds FROM sync_runs WHERE status='running' ORDER BY id DESC LIMIT 1");
        if ($active && $this->isStale($active)) {
            $this->interruptRun(
                $active,
                'Execução interrompida/expirada; liberada automaticamente.',
                'error'
            );
            $active = null;
        }

        $states = [];
        foreach (array_keys($this->modules()) as $module) {
            $states[$module] = DB::fetch("SELECT * FROM sync_states WHERE module_key=?", [$module]) ?: [
                'module_key'=>$module,'status'=>'idle','current_page'=>0,'total_pages'=>0,'processed'=>0,
                'last_error'=>null,'last_success_at'=>null,'context_json'=>null,'updated_at'=>null
            ];
        }
        return ['states'=>$states,'active'=>$active];
    }

    public function start(string $module, int $userId, bool $reset=false): array {
        if (!isset($this->modules()[$module])) throw new RuntimeException('Módulo inválido.');
        $financialAccounts=$module==='financial'?FinancialAccountService::selectedAll():[];
        if($module==='financial'&&!$financialAccounts) throw new RuntimeException('Selecione ao menos uma conta de cobrança em Configurações antes de sincronizar o financeiro.');

        $active = DB::fetch("SELECT *,TIMESTAMPDIFF(SECOND,COALESCE(heartbeat_at,started_at),NOW()) idle_seconds FROM sync_runs WHERE status='running' ORDER BY id DESC LIMIT 1");
        if ($active) {
            if ($this->isStale($active)) {
                $this->interruptRun($active, 'Execução anterior expirada.', 'error');
            } elseif ($reset && (string)($active['module_key'] ?? '') === $module) {
                $this->interruptRun($active, 'Execução anterior substituída por um reinício do zero.');
            } else {
                throw new RuntimeException('Já existe uma sincronização em andamento.');
            }
        }

        if ($reset) {
            DB::exec("DELETE FROM sync_states WHERE module_key=?",[$module]);
            if($module==='financial'){
                DB::exec("DELETE FROM financial_movements");
                DB::exec("UPDATE client_metrics SET open_amount=0,overdue_amount=0,max_overdue_days=0,updated_at=NOW()");
            }
            // Pedidos e serviços usam upsert. Reiniciar o módulo nunca apaga o histórico local.
        }

        $state = DB::fetch("SELECT * FROM sync_states WHERE module_key=?",[$module]);
        if (!$state) {
            DB::exec("INSERT INTO sync_states(module_key,status,current_page,total_pages,processed,created_at,updated_at)
                      VALUES (?,'idle',0,0,0,NOW(),NOW())",[$module]);
        } elseif ($state['status'] === 'success') {
            DB::exec("UPDATE sync_states SET status='idle',current_page=0,total_pages=0,processed=0,last_error=NULL,updated_at=NOW() WHERE module_key=?",[$module]);
        }

        if($module==='orders'){
            $context=['version'=>1,'phase'=>'orders','page'=>0];
            DB::exec("UPDATE sync_states SET status='idle',current_page=0,total_pages=0,processed=0,last_error=NULL,context_json=?,updated_at=NOW() WHERE module_key='orders'",
                [json_encode($context,JSON_UNESCAPED_UNICODE)]);
        }

        if($module==='financial'){
            $context=[
                'version'=>3,
                'accounts'=>array_map(fn($account)=>['code'=>(string)$account['omie_code'],'name'=>(string)$account['name']],$financialAccounts),
                'account_index'=>0,
                'statuses'=>['ATRASADO','PAGTOPARCIAL'],
                'status_index'=>0,
                'page'=>0,
            ];
            DB::exec("UPDATE sync_states SET status='idle',current_page=0,total_pages=0,processed=0,last_error=NULL,context_json=?,updated_at=NOW() WHERE module_key='financial'",
                [json_encode($context,JSON_UNESCAPED_UNICODE)]);
        }

        DB::exec("INSERT INTO sync_runs(started_by,module_key,status,started_at) VALUES (?,?,'running',NOW())",[$userId,$module]);
        $runId = (int)DB::conn()->lastInsertId();

        DB::exec("UPDATE sync_states SET status='running',last_error=NULL,updated_at=NOW() WHERE module_key=?",[$module]);

        return ['run_id'=>$runId,'module'=>$module];
    }

    public function stop(int $runId): array {
        $run = DB::fetch("SELECT * FROM sync_runs WHERE id=?",[$runId]);
        if (!$run) throw new RuntimeException('Execução não encontrada.');
        if ($run['status'] !== 'running') {
            return ['run_id'=>$runId,'stopped'=>false,'status'=>$run['status']];
        }

        $this->interruptRun($run, 'Sincronização pausada pelo usuário.');
        return ['run_id'=>$runId,'stopped'=>true,'status'=>'paused'];
    }

    public function step(int $runId): array {
        $run = DB::fetch("SELECT * FROM sync_runs WHERE id=?",[$runId]);
        if (!$run) throw new RuntimeException('Execução não encontrada.');
        if ($run['status'] !== 'running') {
            return ['done'=>true,'status'=>$run['status'],'message'=>$run['error_message'] ?? null];
        }

        $module = $run['module_key'];
        $state = DB::fetch("SELECT * FROM sync_states WHERE module_key=?",[$module]);
        if (!$state) throw new RuntimeException('Estado de sincronização não encontrado.');

        try {
            $result = match($module) {
                'sellers' => $this->stepSellers($state),
                'clients' => $this->stepClients($state),
                'orders' => $this->stepOrders($state),
                'services' => $this->stepServices($state),
                'financial' => $this->stepFinancial($state,$runId),
                'metrics' => $this->stepMetrics($state),
                default => throw new RuntimeException('Módulo inválido.')
            };

            DB::exec("UPDATE sync_runs SET heartbeat_at=NOW() WHERE id=?",[$runId]);

            if ($result['done']) {
                DB::exec("UPDATE sync_runs SET status='success',finished_at=NOW(),stats_json=? WHERE id=?",
                    [json_encode($result,JSON_UNESCAPED_UNICODE),$runId]);
                DB::exec("UPDATE sync_states SET status='success',last_success_at=NOW(),updated_at=NOW() WHERE module_key=?",[$module]);
            }
            return $result;
        } catch (Throwable $e) {
            DB::exec("UPDATE sync_runs SET status='error',finished_at=NOW(),error_message=? WHERE id=?",
                [mb_substr($e->getMessage(),0,2000),$runId]);
            DB::exec("UPDATE sync_states SET status='error',last_error=?,updated_at=NOW() WHERE module_key=?",
                [mb_substr($e->getMessage(),0,2000),$module]);
            throw $e;
        }
    }

    private function stepSellers(array $state): array {
        $page = max(1, (int)$state['current_page'] + 1);
        $data = $this->omie->call('vendedores','ListarVendedores',[
            'pagina'=>$page,'registros_por_pagina'=>$this->pageSize,'apenas_importado_api'=>'N'
        ]);
        $items = $this->pick($data,['cadastro','vendedores','lista_vendedores']);
        foreach ($items as $r) {
            $code=(string)($r['codigo']??$r['nCodVend']??$r['codigo_vendedor']??$r['codVend']??'');
            if($code==='') continue;
            $name=(string)($r['nome']??$r['nome_vendedor']??$r['cNome']??('Vendedor '.$code));
            $omieActive=mb_strtoupper(trim((string)($r['inativo']??'N')))!=='S'?1:0;
            DB::exec("INSERT INTO sellers(omie_code,name,email,active,omie_active,raw_json,updated_at) VALUES(?,?,?,?,?,?,NOW())
                      ON DUPLICATE KEY UPDATE name=VALUES(name),email=VALUES(email),omie_active=VALUES(omie_active),raw_json=VALUES(raw_json),updated_at=NOW()",
                [$code,$name,$r['email']??null,$omieActive,$omieActive,json_encode($r,JSON_UNESCAPED_UNICODE)]);
        }
        return $this->advancePage('sellers',$state,$data,count($items),$page);
    }

    private function stepClients(array $state): array {
        $page=max(1,(int)$state['current_page']+1);
        $data=$this->omie->call('clientes','ListarClientes',[
            'pagina'=>$page,'registros_por_pagina'=>$this->pageSize,'apenas_importado_api'=>'N'
        ]);
        $items=$this->pick($data,['clientes_cadastro','clientes','lista_clientes']);
        foreach($items as $r){
            $code=(string)($r['codigo_cliente_omie']??$r['codigo_cliente_integracao']??$r['codigo']??'');
            if($code==='') continue;
            $name=(string)($r['nome_fantasia']??$r['razao_social']??$r['nome']??('Cliente '.$code));
            $uf=(string)($r['estado']??$r['uf']??'');
            $phone='';
            if(!empty($r['telefone1_ddd'])||!empty($r['telefone1_numero'])) $phone=trim(($r['telefone1_ddd']??'').' '.($r['telefone1_numero']??''));
            DB::exec("INSERT INTO clients(omie_code,name,legal_name,uf,city,phone,email,document,active,raw_json,updated_at)
                      VALUES(?,?,?,?,?,?,?,?,1,?,NOW())
                      ON DUPLICATE KEY UPDATE name=VALUES(name),legal_name=VALUES(legal_name),uf=VALUES(uf),city=VALUES(city),
                      phone=VALUES(phone),email=VALUES(email),document=VALUES(document),raw_json=VALUES(raw_json),updated_at=NOW()",
                [$code,$name,$r['razao_social']??null,$uf,$r['cidade']??null,$phone,$r['email']??null,$r['cnpj_cpf']??null,json_encode($r,JSON_UNESCAPED_UNICODE)]);
            $client=DB::fetch("SELECT id FROM clients WHERE omie_code=?",[$code]);
            if($client)$this->syncClientTags((int)$client['id'],$r);
        }
        return $this->advancePage('clients',$state,$data,count($items),$page);
    }

    private function stepOrders(array $state): array {
        $context=json_decode((string)($state['context_json']??''),true);
        if(!is_array($context)||(int)($context['version']??0)!==1){
            $context=['version'=>1,'phase'=>'orders','page'=>0];
        }
        $phase=(string)($context['phase']??'orders');
        $start=date('d/m/Y',strtotime('-1 day'));
        $end=date('d/m/Y');

        if($phase==='orders'){
            $page=max(1,(int)($context['page']??0)+1);
            if($page===1)$this->syncOrderStageCatalog();

            $data=$this->omie->call('pedidos','ListarPedidos',[
                'pagina'=>$page,'registros_por_pagina'=>$this->pageSize,
                'filtrar_por_data_de'=>$start,'filtrar_por_data_ate'=>$end,
                'filtrar_apenas_inclusao'=>'N','apenas_resumo'=>'N'
            ]);
            $items=$this->pick($data,['pedido_venda_produto','pedidos','lista_pedidos']);

            foreach($items as $r){
                $cab=$r['cabecalho']??$r;
                $info=$r['infoCadastro']??[];
                $additional=is_array($r['informacoes_adicionais']??null)?$r['informacoes_adicionais']:[];
                $orderCode=(string)($cab['codigo_pedido']??$cab['codigo_pedido_integracao']??$cab['numero_pedido']??$r['codigo_pedido']??'');
                if($orderCode==='')$orderCode=sha1(json_encode($r));
                $client=(string)($cab['codigo_cliente']??$cab['codigo_cliente_omie']??$cab['codCli']??'');
                $seller=(string)($additional['codVend']??$additional['codigo_vendedor']??$cab['codigo_vendedor']??$cab['codVend']??$r['codVend']??'');
                $totals=is_array($r['total_pedido']??null)?$r['total_pedido']:[];
                $total=(float)($totals['valor_total_pedido']??$r['valor_total_pedido']??$cab['valor_total_pedido']??$cab['valor_total']??0);
                $date=$this->normalizeDate($info['dInc']??null);
                $stageCode=$this->normalizeOrderStageCode($cab['etapa']??null);
                $stageName=$this->orderStageName($stageCode);

                $status='ATIVO';
                if(mb_strtoupper((string)($info['cancelado']??'N'))==='S')$status='CANCELADO';
                elseif(mb_strtoupper((string)($info['devolvido']??'N'))==='S')$status='DEVOLVIDO';
                elseif(mb_strtoupper((string)($info['denegado']??'N'))==='S')$status='DENEGADO';

                DB::exec("INSERT INTO orders(omie_order_code,client_omie_code,seller_omie_code,order_date,total,status,stage_code,stage_name,raw_json,updated_at)
                          VALUES(?,?,?,?,?,?,?,?,?,NOW())
                          ON DUPLICATE KEY UPDATE client_omie_code=VALUES(client_omie_code),seller_omie_code=VALUES(seller_omie_code),
                          order_date=VALUES(order_date),total=VALUES(total),status=VALUES(status),stage_code=VALUES(stage_code),
                          stage_name=COALESCE(VALUES(stage_name),stage_name),raw_json=VALUES(raw_json),updated_at=NOW()",
                    [$orderCode,$client,$seller,$date,$total,$status,$stageCode,$stageName,json_encode($r,JSON_UNESCAPED_UNICODE)]);
            }

            $totalPages=max(1,(int)($data['total_de_paginas']??$data['nTotPaginas']??$page));
            $processed=(int)$state['processed']+count($items);

            if($page >= $totalPages){
                $context=['version'=>1,'phase'=>'stages','page'=>0];
                DB::exec("UPDATE sync_states SET status='running',current_page=0,total_pages=0,processed=?,context_json=?,updated_at=NOW() WHERE module_key='orders'",
                    [$processed,json_encode($context,JSON_UNESCAPED_UNICODE)]);
                return ['done'=>false,'module'=>'orders','phase'=>'pedidos','page'=>$page,'total_pages'=>$totalPages,'processed'=>$processed,'page_items'=>count($items),'from'=>$start,'to'=>$end];
            }

            $context['page']=$page;
            DB::exec("UPDATE sync_states SET status='running',current_page=?,total_pages=?,processed=?,context_json=?,updated_at=NOW() WHERE module_key='orders'",
                [$page,$totalPages,$processed,json_encode($context,JSON_UNESCAPED_UNICODE)]);
            return ['done'=>false,'module'=>'orders','phase'=>'pedidos','page'=>$page,'total_pages'=>$totalPages,'processed'=>$processed,'page_items'=>count($items),'from'=>$start,'to'=>$end];
        }

        $page=max(1,(int)($context['page']??0)+1);
        $data=$this->omie->call('pedido_etapas','ListarEtapasPedido',[
            'nPagina'=>$page,
            'nRegPorPagina'=>$this->pageSize,
            'cOrdenarPor'=>'DATAHORA',
            'cOrdemDecrescente'=>'N',
            'dDtInicial'=>$start,
            'dDtFinal'=>$end,
            'cHrInicial'=>'00:00:00',
            'cHrFinal'=>'23:59:59',
        ]);
        $items=$this->pick($data,['etapasPedido']);

        foreach($items as $r){
            $orderCode=(string)($r['nCodPed']??'');
            if($orderCode==='')continue;
            $stageCode=$this->normalizeOrderStageCode($r['cEtapa']??null);
            $stageName=$this->orderStageName($stageCode);
            $changedAt=null;
            if(!empty($r['dDtEtapa'])){
                $dateIso=$this->normalizeDate((string)$r['dDtEtapa']);
                if($dateIso)$changedAt=$dateIso.' '.((string)($r['cHrEtapa']??'00:00:00'));
            }
            DB::exec("UPDATE orders
                      SET stage_code=?,stage_name=COALESCE(?,stage_name),stage_changed_at=COALESCE(?,stage_changed_at),updated_at=NOW()
                      WHERE omie_order_code=?",
                [$stageCode,$stageName,$changedAt,$orderCode]);
        }

        $totalPages=max(1,(int)($data['nTotPaginas']??$page));
        $processed=(int)$state['processed']+count($items);
        $done=$page >= $totalPages;
        $context['page']=$page;

        DB::exec("UPDATE sync_states SET status=?,current_page=?,total_pages=?,processed=?,context_json=?,updated_at=NOW() WHERE module_key='orders'",
            [$done?'success':'running',$page,$totalPages,$processed,json_encode($context,JSON_UNESCAPED_UNICODE)]);

        return ['done'=>$done,'module'=>'orders','phase'=>'etapas','page'=>$page,'total_pages'=>$totalPages,'processed'=>$processed,'page_items'=>count($items),'from'=>$start,'to'=>$end];
    }

    private function syncOrderStageCatalog(): void {
        $data=$this->omie->call('etapas_faturamento','ListarEtapasFaturamento',[
            'pagina'=>1,'registros_por_pagina'=>100,'ordenar_por'=>'CODIGO','ordem_decrescente'=>'N'
        ]);
        foreach(($data['cadastros']??[]) as $group){
            foreach(($group['etapas']??[]) as $stage){
                $code=$this->normalizeOrderStageCode($stage['cCodigo']??null);
                if($code==='')continue;
                $name=trim((string)($stage['cDescricao']??''));
                $default=trim((string)($stage['cDescrPadrao']??''));
                if($name==='')$name=$default!==''?$default:('Etapa '.$code);
                $active=mb_strtoupper((string)($stage['cInativo']??'N'))==='S'?0:1;
                DB::exec("INSERT INTO order_stage_catalog(stage_code,stage_name,default_name,active,updated_at)
                          VALUES(?,?,?,?,NOW())
                          ON DUPLICATE KEY UPDATE stage_name=VALUES(stage_name),default_name=VALUES(default_name),active=VALUES(active),updated_at=NOW()",
                    [$code,$name,$default?:null,$active]);
            }
        }
    }

    private function normalizeOrderStageCode(mixed $code): string {
        $value=trim((string)$code);
        if($value==='')return '';
        return str_pad($value,2,'0',STR_PAD_LEFT);
    }

    private function orderStageName(string $code): ?string {
        if($code==='')return null;
        $row=DB::fetch("SELECT stage_name FROM order_stage_catalog WHERE stage_code=?",[$code]);
        return $row?(string)$row['stage_name']:('Etapa '.$code);
    }

    private function stepServices(array $state): array {
        $page=max(1,(int)$state['current_page']+1);
        $incremental=true;
        $start=date('d/m/Y',strtotime('-1 day'));
        $end=date('d/m/Y');
        $servicePageSize=max(100,min(500,(int)($GLOBALS['config']['sync']['service_page_size']??500)));
        $data=$this->omie->call('ordens_servico','ListarOS',[
            'pagina'=>$page,'registros_por_pagina'=>$servicePageSize,
            'filtrar_por_data_de'=>$start,'filtrar_por_data_ate'=>$end,
            'filtrar_apenas_inclusao'=>'N'
        ]);
        $items=$this->pick($data,['osCadastro']);
        foreach($items as $r){
            $cab=is_array($r['Cabecalho']??null)?$r['Cabecalho']:[];
            $info=is_array($r['InfoCadastro']??null)?$r['InfoCadastro']:[];
            $additional=is_array($r['InformacoesAdicionais']??null)?$r['InformacoesAdicionais']:[];
            $code=(string)($cab['nCodOS']??$cab['cCodIntOS']??'');
            if($code==='') continue;
            $descriptions=[];
            foreach(($r['ServicosPrestados']??[]) as $service){
                $description=trim((string)($service['cDescServ']??$service['cDadosAdicItem']??''));
                if($description!==''&&!in_array($description,$descriptions,true))$descriptions[]=$description;
            }
            $status='ABERTO';
            if(mb_strtoupper((string)($info['cCancelada']??'N'))==='S')$status='CANCELADO';
            elseif(mb_strtoupper((string)($info['cFaturada']??'N'))==='S')$status='FATURADO';
            DB::exec("INSERT INTO service_orders(omie_service_order_code,display_number,client_omie_code,seller_omie_code,inclusion_date,total,status,stage_code,service_description,external_reference,raw_json,updated_at)
                      VALUES(?,?,?,?,?,?,?,?,?,?,?,NOW())
                      ON DUPLICATE KEY UPDATE display_number=VALUES(display_number),client_omie_code=VALUES(client_omie_code),seller_omie_code=VALUES(seller_omie_code),
                      inclusion_date=VALUES(inclusion_date),total=VALUES(total),status=VALUES(status),stage_code=VALUES(stage_code),
                      service_description=VALUES(service_description),external_reference=VALUES(external_reference),raw_json=VALUES(raw_json),updated_at=NOW()",
                [$code,(string)($cab['cNumOS']??''),(string)($cab['nCodCli']??''),(string)($cab['nCodVend']??''),
                 $this->normalizeDate($info['dDtInc']??null),(float)($cab['nValorTotal']??0),$status,(string)($cab['cEtapa']??''),
                 implode(' • ',$descriptions),(string)($additional['cNumPedido']??$cab['cCodIntOS']??''),json_encode($r,JSON_UNESCAPED_UNICODE)]);
        }
        return $this->advancePage('services',$state,$data,count($items),$page)+['incremental'=>true,'from'=>$start,'to'=>$end,'mode'=>'yesterday_today'];
    }

    private function stepFinancial(array $state,int $runId): array {
        $context=json_decode((string)($state['context_json']??''),true);
        if(!is_array($context)||(int)($context['version']??0)!==3) throw new RuntimeException('Contexto financeiro antigo. Use Limpar e reconstruir no módulo Financeiro.');
        $statuses=$context['statuses']??['ATRASADO','PAGTOPARCIAL'];
        $statuses=array_values(array_unique(array_map(fn($v)=>mb_strtoupper((string)$v),$statuses)));
        $context['statuses']=$statuses;
        $accounts=$context['accounts']??[];
        $accountIndex=max(0,(int)($context['account_index']??0));
        $statusIndex=max(0,(int)($context['status_index']??0));
        $status=(string)($statuses[$statusIndex]??'');
        $account=$accounts[$accountIndex]??[];
        $accountCode=(string)($account['code']??'');
        if($status===''||$accountCode==='') throw new RuntimeException('Conta ou etapa financeira não configurada.');
        $page=max(1,(int)($context['page']??0)+1);
        $today=new \DateTimeImmutable('today');
        $years=max(1,(int)($GLOBALS['config']['sync']['financial_history_years']??3));
        $historyStart=$today->modify("-{$years} years");
        $params=[
            'nPagina'=>$page,
            'nRegPorPagina'=>$this->pageSize,
            'cOrdenarPor'=>'CODIGO',
            'lDadosCad'=>true,
            'cNatureza'=>'R',
            'cTpLancamento'=>'CR',
            'nCodCC'=>(int)$accountCode,
            'cStatus'=>$status,
            'dDtEmisDe'=>$historyStart->format('d/m/Y'),
            'dDtEmisAte'=>$today->format('d/m/Y'),
        ];
        $data=$this->omie->call('financeiro','ListarMovimentos',$params);
        $items=$this->pick($data,['movimentos','lista_movimentos','movimentos_financeiros']);
        foreach($items as $r){
            $details=isset($r['detalhes'])&&is_array($r['detalhes'])?$r['detalhes']:$r;
            $summary=isset($r['resumo'])&&is_array($r['resumo'])?$r['resumo']:[];
            $code=(string)($details['nCodTitulo']??$details['nCodMovCC']??$details['codigo_lancamento_omie']??$details['codigo']??'');
            if($code===''||$code==='0') continue;
            $client=(string)($details['nCodCliente']??$details['codigo_cliente_omie']??'');
            $due=$this->normalizeDate($details['dDtVenc']??$details['data_vencimento']??null);
            $pay=$this->normalizeDate($details['dDtPagamento']??$details['data_pagamento']??null);
            $openAmount=(float)($summary['nValAberto']??$details['nValorTitulo']??$details['nValorMovimento']??$details['valor_documento']??0);
            $paidAmount=(float)($summary['nValPago']??0);
            $originalAmount=(float)($details['nValorTitulo']??($openAmount+$paidAmount));
            $apiStatus=mb_strtoupper((string)($details['cStatus']??$details['status']??''));
            $movementStatus=$apiStatus==='PAGTOPARCIAL'?'PAGTO_PARCIAL':$apiStatus;

            // Regra robusta: se há valor pago e ainda existe saldo em aberto, é parcial,
            // mesmo quando a própria Omie devolve cStatus ATRASADO no detalhe.
            if($openAmount>0.009 && $paidAmount>0.009)$movementStatus='PAGTO_PARCIAL';
            if($openAmount<=0.009)continue;
            if(!in_array($movementStatus,['ATRASADO','PAGTO_PARCIAL'],true))continue;

            $seller=(string)($details['cCodVendedor']??$details['nCodVendedor']??'');
            DB::exec("INSERT INTO financial_movements
                      (omie_code,client_omie_code,due_date,payment_date,amount,original_amount,paid_amount,status,seller_omie_code,account_omie_code,last_seen_run_id,raw_json,updated_at)
                      VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW())
                      ON DUPLICATE KEY UPDATE client_omie_code=VALUES(client_omie_code),due_date=VALUES(due_date),payment_date=VALUES(payment_date),
                      amount=VALUES(amount),original_amount=VALUES(original_amount),paid_amount=VALUES(paid_amount),status=VALUES(status),
                      seller_omie_code=VALUES(seller_omie_code),account_omie_code=VALUES(account_omie_code),last_seen_run_id=VALUES(last_seen_run_id),
                      raw_json=VALUES(raw_json),updated_at=NOW()",
                [$code,$client,$due,$pay,$openAmount,$originalAmount,$paidAmount,$movementStatus,$seller?:null,$accountCode,$runId,json_encode($r,JSON_UNESCAPED_UNICODE)]);
        }
        $totalPages=max(1,(int)($data['nTotPaginas']??$page));
        $processed=(int)$state['processed']+count($items);
        $phaseDone=$page>=$totalPages;
        $lastStatus=$statusIndex>=count($statuses)-1;
        $lastAccount=$accountIndex>=count($accounts)-1;
        $done=$phaseDone&&$lastStatus&&$lastAccount;
        if($phaseDone&&!$done){
            if(!$lastStatus){$context['status_index']=$statusIndex+1;}
            else{$context['account_index']=$accountIndex+1;$context['status_index']=0;}
            $context['page']=0;
            DB::exec("UPDATE sync_states SET status='running',current_page=0,total_pages=0,processed=?,context_json=?,updated_at=NOW() WHERE module_key='financial'",
                [$processed,json_encode($context,JSON_UNESCAPED_UNICODE)]);
        }else{
            $context['page']=$page;
            DB::exec("UPDATE sync_states SET status=?,current_page=?,total_pages=?,processed=?,context_json=?,updated_at=NOW() WHERE module_key='financial'",
                [$done?'success':'running',$page,$totalPages,$processed,json_encode($context,JSON_UNESCAPED_UNICODE)]);
        }
        if($done){
            DB::exec("DELETE FROM financial_movements WHERE last_seen_run_id IS NULL OR last_seen_run_id<>?",[$runId]);
            CollectionService::reconcileAll();
        }
        return [
            'done'=>$done,'module'=>'financial','page'=>$page,'total_pages'=>$totalPages,'processed'=>$processed,
            'page_items'=>count($items),'phase'=>$status,'account'=>(string)($account['name']??$accountCode),
            'from'=>$historyStart->format('d/m/Y'),'to'=>$today->format('d/m/Y'),
        ];
    }

    private function stepMetrics(array $state): array {
        $batch=max(20,(int)($GLOBALS['config']['sync']['metrics_batch_size']??100));
        $offset=(int)$state['processed'];
        $clients=DB::all("SELECT * FROM clients WHERE active=1 ORDER BY id LIMIT {$batch} OFFSET {$offset}");
        $total=(int)(DB::fetch("SELECT COUNT(*) n FROM clients WHERE active=1")['n']??0);

        foreach($clients as $c) $this->rebuildClientMetric($c);

        $processed=$offset+count($clients);
        $done=$processed >= $total || !$clients;
        DB::exec("UPDATE sync_states SET status=?,processed=?,current_page=?,total_pages=?,updated_at=NOW() WHERE module_key='metrics'",
            [$done?'success':'running',$processed,(int)ceil($processed/$batch),(int)ceil(max(1,$total)/$batch)]);

        return [
            'done'=>$done,'module'=>'metrics','page'=>(int)ceil($processed/$batch),
            'total_pages'=>(int)ceil(max(1,$total)/$batch),'processed'=>$processed,'total_items'=>$total
        ];
    }

    private function rebuildClientMetric(array $c): void {
        $orders=DB::all("SELECT * FROM orders WHERE client_omie_code=? ORDER BY order_date ASC",[$c['omie_code']]);
        $valid=array_values(array_filter($orders,fn($o)=>GoalService::isOrderCounted($o)));
        $last=$valid?end($valid):null;$first=$valid[0]??null;
        $days=null;
        if($last && $last['order_date']) $days=(int)(new \DateTime($last['order_date']))->diff(new \DateTime('today'))->format('%a');
        $yearAgo=(new \DateTime('today'))->modify('-12 months')->format('Y-m-d');
        $orders12=array_values(array_filter($valid,fn($o)=>$o['order_date'] && $o['order_date'] >= $yearAgo));
        $rev12=array_sum(array_map(fn($o)=>(float)$o['total'],$orders12));
        $ticket=count($orders12)?$rev12/count($orders12):0;

        $interval=null;
        if(count($valid)>=2){
            $diffs=[];
            for($i=1;$i<count($valid);$i++){
                if($valid[$i-1]['order_date']&&$valid[$i]['order_date']){
                    $diffs[]=(int)(new \DateTime($valid[$i-1]['order_date']))->diff(new \DateTime($valid[$i]['order_date']))->format('%a');
                }
            }
            if($diffs)$interval=array_sum($diffs)/count($diffs);
        }

        $fin=DB::all("SELECT fm.* FROM financial_movements fm INNER JOIN financial_accounts fa ON fa.omie_code=fm.account_omie_code AND fa.selected=1 WHERE fm.client_omie_code=?",[$c['omie_code']]);
        $open=0;$overdue=0;$maxDelay=0;
        foreach($fin as $f){
            $st=mb_strtoupper((string)$f['status']);
            if(in_array($st,['EMABERTO','ATRASADO','AVENCER','VENCEHOJE','PAGTO_PARCIAL'],true))$open+=(float)$f['amount'];
            if($st==='ATRASADO'||(!$f['payment_date']&&$f['due_date']&&$f['due_date']<date('Y-m-d'))){
                $overdue+=(float)$f['amount'];
                $dd=(int)(new \DateTime($f['due_date']))->diff(new \DateTime('today'))->format('%a');
                $maxDelay=max($maxDelay,$dd);
            }
        }

        $status='normal';
        if($days===null||$days>=($GLOBALS['config']['commercial']['reactivate_days']??181))$status='reactivate';
        elseif($days>=($GLOBALS['config']['commercial']['attention_days']??61))$status='attention';
        if($interval&&$days!==null&&$days>($interval*1.6))$status='attention';
        $seller=$last['seller_omie_code']??null;

        DB::exec("INSERT INTO client_metrics(client_id,seller_omie_code,first_purchase_at,last_purchase_at,days_without_purchase,avg_interval_days,
                 orders_12m,revenue_12m,avg_ticket_12m,open_amount,overdue_amount,max_overdue_days,commercial_status,updated_at)
                 VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())
                 ON DUPLICATE KEY UPDATE seller_omie_code=VALUES(seller_omie_code),first_purchase_at=VALUES(first_purchase_at),
                 last_purchase_at=VALUES(last_purchase_at),days_without_purchase=VALUES(days_without_purchase),avg_interval_days=VALUES(avg_interval_days),
                 orders_12m=VALUES(orders_12m),revenue_12m=VALUES(revenue_12m),avg_ticket_12m=VALUES(avg_ticket_12m),
                 open_amount=VALUES(open_amount),overdue_amount=VALUES(overdue_amount),max_overdue_days=VALUES(max_overdue_days),
                 commercial_status=VALUES(commercial_status),updated_at=NOW()",
            [$c['id'],$seller,$first['order_date']??null,$last['order_date']??null,$days,$interval,count($orders12),$rev12,$ticket,$open,$overdue,$maxDelay,$status]);
    }

    private function syncClientTags(int $clientId,array $payload): void {
        $tags=$payload['tags']??$payload['tag']??$payload['tags_cliente']??[];
        if(!is_array($tags))$tags=[$tags];
        $normalized=[];
        foreach($tags as $item){
            $value='';
            if(is_string($item)||is_numeric($item))$value=trim((string)$item);
            elseif(is_array($item)){
                foreach(['tag','nome','descricao','cTag','label'] as $key){
                    if(isset($item[$key])&&trim((string)$item[$key])!==''){$value=trim((string)$item[$key]);break;}
                }
            }
            if($value!=='')$normalized[mb_strtolower($value)]=$value;
        }
        DB::exec("DELETE FROM client_tags WHERE client_id=?",[$clientId]);
        foreach($normalized as $value){
            DB::exec("INSERT IGNORE INTO client_tags(client_id,tag,created_at) VALUES(?,?,NOW())",[$clientId,$value]);
        }
    }

    private function advancePage(string $module,array $state,array $data,int $count,int $page,array $extraTotalKeys=[]): array {
        $totalPages=0;
        foreach(array_merge(['total_de_paginas','nTotPaginas'],$extraTotalKeys) as $k){
            if(isset($data[$k])){$totalPages=(int)$data[$k];break;}
        }
        if($totalPages<=0)$totalPages=$page;
        $processed=(int)$state['processed']+$count;
        $done=$page >= $totalPages;
        DB::exec("UPDATE sync_states SET status=?,current_page=?,total_pages=?,processed=?,updated_at=NOW() WHERE module_key=?",
            [$done?'success':'running',$page,$totalPages,$processed,$module]);
        return ['done'=>$done,'module'=>$module,'page'=>$page,'total_pages'=>$totalPages,'processed'=>$processed,'page_items'=>$count];
    }

    private function pick(array $data,array $keys): array {
        foreach($keys as $k) if(isset($data[$k])&&is_array($data[$k])) return $data[$k];
        return [];
    }

    private function normalizeDate(?string $d): ?string {
        if(!$d)return null;
        foreach(['d/m/Y','Y-m-d','d/m/y'] as $f){
            $dt=\DateTime::createFromFormat($f,$d);
            if($dt)return $dt->format('Y-m-d');
        }
        $ts=strtotime($d);return $ts?date('Y-m-d',$ts):null;
    }

    private function interruptRun(array $run,string $message,string $stateStatus='idle'): void {
        $updated=DB::exec(
            "UPDATE sync_runs SET status='error',finished_at=NOW(),error_message=? WHERE id=? AND status='running'",
            [$message,$run['id']]
        );
        $module=(string)($run['module_key']??'');
        if(!$updated || $module==='' || !isset($this->modules()[$module])) return;

        if($stateStatus==='error'){
            DB::exec("UPDATE sync_states SET status='error',last_error=?,updated_at=NOW() WHERE module_key=? AND status='running'",[$message,$module]);
            return;
        }
        DB::exec("UPDATE sync_states SET status='idle',last_error=NULL,updated_at=NOW() WHERE module_key=? AND status='running'",[$module]);
    }

    private function isStale(array $run): bool {
        if(array_key_exists('idle_seconds',$run)){
            return (int)$run['idle_seconds'] > ((int)($GLOBALS['config']['sync']['lock_minutes']??15)*60);
        }
        $lastActivity=(string)($run['heartbeat_at']??$run['started_at']??'');
        $timestamp=strtotime($lastActivity);
        if(!$timestamp) return true;
        $minutes=(time()-$timestamp)/60;
        return $minutes > (int)($GLOBALS['config']['sync']['lock_minutes']??15);
    }
}
