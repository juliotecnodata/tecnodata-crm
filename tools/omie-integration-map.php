<?php
declare(strict_types=1);

/**
 * Mapa somente leitura: Omie Vendas x Omie CRM x Tecnodata CRM.
 *
 * Uso:
 *   php tools/omie-integration-map.php
 *   php tools/omie-integration-map.php --account-pages=3 --opportunity-pages=3
 *
 * Não grava no Omie nem no banco local.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Execute este diagnóstico via CLI.\n");
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/app/bootstrap.php';

$options = getopt('', ['account-pages::', 'opportunity-pages::']);
$accountPages = max(1, min(50, (int)($options['account-pages'] ?? 3)));
$opportunityPages = max(1, min(50, (int)($options['opportunity-pages'] ?? 3)));

$cfg = $GLOBALS['config']['omie'] ?? [];
$appKey = trim((string)($cfg['app_key'] ?? ''));
$appSecret = trim((string)($cfg['app_secret'] ?? ''));
$timeout = max(10, (int)($cfg['timeout'] ?? 60));

if ($appKey === '' || $appSecret === '' || str_contains($appKey, 'SUA_') || str_contains($appSecret, 'SEU_')) {
    fwrite(STDERR, "Credenciais Omie ausentes em config/config.php.\n");
    exit(1);
}

function omieMapCall(string $url, string $call, array $param, string $appKey, string $appSecret, int $timeout): array
{
    $payload = ['call'=>$call,'app_key'=>$appKey,'app_secret'=>$appSecret,'param'=>[$param]];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_POST=>true,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: application/json','Accept-Encoding: identity'],
        CURLOPT_POSTFIELDS=>json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        CURLOPT_CONNECTTIMEOUT=>15,
        CURLOPT_TIMEOUT=>$timeout,
        CURLOPT_ENCODING=>'identity',
    ]);
    $raw = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($raw === false || $error !== '') throw new RuntimeException('Falha Omie: '.$error);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) throw new RuntimeException('Resposta Omie inválida HTTP '.$http);
    if ($http >= 400 || isset($data['faultstring']) || isset($data['faultcode'])) {
        throw new RuntimeException((string)($data['faultstring'] ?? $data['description'] ?? ('Erro Omie HTTP '.$http)));
    }
    return $data;
}

function onlyDigitsMap(mixed $value): string
{
    return preg_replace('/\D+/', '', (string)$value) ?: '';
}

function normMap(mixed $value): string
{
    $value = mb_strtolower(trim((string)$value), 'UTF-8');
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    return preg_replace('/[^a-z0-9@._+-]+/', ' ', $value) ?: '';
}

function sectionMap(string $title): void
{
    echo "\n".$title."\n".str_repeat('=', mb_strlen($title))."\n";
}

function rowMap(string $label, mixed $value): void
{
    echo str_pad($label, 44).': '.$value."\n";
}

function fetchPagedMap(
    string $url,
    string $call,
    string $rowsKey,
    int $maxPages,
    string $appKey,
    string $appSecret,
    int $timeout,
    array $extra = []
): array {
    $rows = [];
    $reportedTotal = 0;
    $reportedPages = 0;
    for ($page=1; $page<=$maxPages; $page++) {
        $response = omieMapCall($url, $call, array_merge([
            'pagina'=>$page,
            'registros_por_pagina'=>50,
        ], $extra), $appKey, $appSecret, $timeout);
        $reportedTotal = max($reportedTotal, (int)($response['total_de_registros'] ?? 0));
        $reportedPages = max($reportedPages, (int)($response['total_de_paginas'] ?? 0));
        $batch = (array)($response[$rowsKey] ?? []);
        foreach ($batch as $item) if (is_array($item)) $rows[] = $item;
        if (!$batch || ($reportedPages > 0 && $page >= $reportedPages)) break;
    }
    return ['rows'=>$rows,'total'=>$reportedTotal,'pages'=>$reportedPages];
}

$urls = [
    'crm_accounts'=>'https://app.omie.com.br/api/v1/crm/contas/',
    'crm_users'=>'https://app.omie.com.br/api/v1/crm/usuarios/',
    'crm_opportunities'=>'https://app.omie.com.br/api/v1/crm/oportunidades/',
    'crm_phases'=>'https://app.omie.com.br/api/v1/crm/fases/',
    'crm_status'=>'https://app.omie.com.br/api/v1/crm/status/',
    'sales_sellers'=>'https://app.omie.com.br/api/v1/geral/vendedores/',
];

sectionMap('TECNODATA — MAPA OMIE VENDAS x CRM');
rowMap('Modo', 'somente leitura');
rowMap('Páginas de Contas a analisar', $accountPages);
rowMap('Páginas de Oportunidades a analisar', $opportunityPages);

try {
    $crmUsersResult = fetchPagedMap($urls['crm_users'], 'ListarUsuarios', 'cadastros', 20, $appKey, $appSecret, $timeout, ['apenas_ativos'=>'S']);
    $salesSellersResult = fetchPagedMap($urls['sales_sellers'], 'ListarVendedores', 'cadastro', 20, $appKey, $appSecret, $timeout);
    $accountsResult = fetchPagedMap($urls['crm_accounts'], 'ListarContas', 'cadastros', $accountPages, $appKey, $appSecret, $timeout);
    $opportunitiesResult = fetchPagedMap($urls['crm_opportunities'], 'ListarOportunidades', 'cadastros', $opportunityPages, $appKey, $appSecret, $timeout, ['exibir_detalhes'=>'S','exibir_obs'=>'S']);
    $phasesResult = fetchPagedMap($urls['crm_phases'], 'ListarFases', 'cadastros', 10, $appKey, $appSecret, $timeout);
    $statusResult = fetchPagedMap($urls['crm_status'], 'ListarStatus', 'cadastros', 10, $appKey, $appSecret, $timeout);

    $crmUsers = $crmUsersResult['rows'];
    $salesSellers = $salesSellersResult['rows'];
    $accounts = $accountsResult['rows'];
    $opportunities = $opportunitiesResult['rows'];

    $crmUserByCode=[];$crmUserByEmail=[];$crmUserByName=[];
    foreach ($crmUsers as $u) {
        $code=trim((string)($u['nCodigo']??''));
        $email=normMap($u['cEmail']??'');
        $name=normMap($u['cNome']??'');
        if($code!=='')$crmUserByCode[$code]=$u;
        if($email!=='')$crmUserByEmail[$email][]=$u;
        if($name!=='')$crmUserByName[$name][]=$u;
    }

    $sellerExactCode=0;$sellerEmail=0;$sellerName=0;$sellerNoMatch=[];
    foreach ($salesSellers as $s) {
        $code=trim((string)($s['codigo']??''));
        $email=normMap($s['email']??'');
        $name=normMap($s['nome']??'');
        if($code!==''&&isset($crmUserByCode[$code])){$sellerExactCode++;continue;}
        if($email!==''&&isset($crmUserByEmail[$email])){$sellerEmail++;continue;}
        if($name!==''&&isset($crmUserByName[$name])){$sellerName++;continue;}
        if(count($sellerNoMatch)<20)$sellerNoMatch[]=$s;
    }

    $localClients=DB::all("SELECT id,omie_code,name,legal_name,document,seller_omie_code,omie_seller_code,raw_json,active,crm_inactive FROM clients");
    $localUsers=DB::all("SELECT id,name,email,role,seller_omie_code,active FROM users");
    $localByDoc=[];$localUserByEmail=[];$localUserBySellerCode=[];
    foreach($localClients as $c){
        $doc=onlyDigitsMap($c['document']??'');
        if($doc!=='')$localByDoc[$doc][]=$c;
    }
    foreach($localUsers as $u){
        $email=normMap($u['email']??'');
        if($email!=='')$localUserByEmail[$email][]=$u;
        $code=trim((string)($u['seller_omie_code']??''));
        if($code!=='')$localUserBySellerCode[$code][]=$u;
    }

    $accountByCode=[];$accountByDoc=[];
    $accountMatchedLocal=0;$accountUniqueLocal=0;$accountSellerDiff=0;$accountIntEqualsGeneralInt=0;$accountIntEqualsGeneralOmie=0;
    $accountMismatchSamples=[];
    foreach($accounts as $account){
        $ident=is_array($account['identificacao']??null)?$account['identificacao']:[];
        $accountCode=trim((string)($ident['nCod']??''));
        $accountInt=trim((string)($ident['cCodInt']??''));
        $doc=onlyDigitsMap($ident['cDoc']??'');
        $crmSeller=trim((string)($ident['nCodVend']??''));
        if($accountCode!=='')$accountByCode[$accountCode]=$account;
        if($doc!=='')$accountByDoc[$doc][]=$account;
        if($doc===''||!isset($localByDoc[$doc]))continue;
        $accountMatchedLocal++;
        if(count($localByDoc[$doc])===1)$accountUniqueLocal++;
        $local=$localByDoc[$doc][0];
        $raw=json_decode((string)($local['raw_json']??''),true);if(!is_array($raw))$raw=[];
        $generalInt=trim((string)($raw['codigo_cliente_integracao']??$raw['request']['codigo_cliente_integracao']??''));
        if($accountInt!==''&&$generalInt!==''&&$accountInt===$generalInt)$accountIntEqualsGeneralInt++;
        if($accountInt!==''&&$accountInt===trim((string)($local['omie_code']??'')))$accountIntEqualsGeneralOmie++;
        $generalSeller=trim((string)($local['omie_seller_code']??$local['seller_omie_code']??''));
        if($crmSeller!==''&&$generalSeller!==''&&$crmSeller!==$generalSeller){
            $accountSellerDiff++;
            if(count($accountMismatchSamples)<20)$accountMismatchSamples[]=[
                'name'=>$ident['cNomeFantasia']??$ident['cNome']??$local['name']??'',
                'doc'=>$doc,'sales_seller'=>$generalSeller,'crm_seller'=>$crmSeller
            ];
        }
    }

    $oppLinkedAccount=0;$oppLinkedLocal=0;$oppSellerKnown=0;$oppSellerLocalUser=0;$oppTasks=0;$oppStatus=[];
    $oppUnlinkedSamples=[];
    foreach($opportunities as $opp){
        $ident=is_array($opp['identificacao']??null)?$opp['identificacao']:[];
        $accountCode=trim((string)($ident['nCodConta']??''));
        $sellerCode=trim((string)($ident['nCodVendedor']??''));
        $statusCode=trim((string)($opp['fasesStatus']['nCodStatus']??$opp['status']??''));
        if($statusCode!=='')$oppStatus[$statusCode]=($oppStatus[$statusCode]??0)+1;
        $account=$accountCode!==''?($accountByCode[$accountCode]??null):null;
        if($account){
            $oppLinkedAccount++;
            $accIdent=is_array($account['identificacao']??null)?$account['identificacao']:[];
            $doc=onlyDigitsMap($accIdent['cDoc']??'');
            if($doc!==''&&isset($localByDoc[$doc]))$oppLinkedLocal++;
        }elseif(count($oppUnlinkedSamples)<20){
            $oppUnlinkedSamples[]=[
                'nCodOp'=>$ident['nCodOp']??'',
                'cDesOp'=>$ident['cDesOp']??'',
                'nCodConta'=>$accountCode,
                'nCodVendedor'=>$sellerCode
            ];
        }
        if($sellerCode!==''&&isset($crmUserByCode[$sellerCode])){
            $oppSellerKnown++;
            $crmUser=$crmUserByCode[$sellerCode];
            $email=normMap($crmUser['cEmail']??'');
            if(($email!==''&&isset($localUserByEmail[$email]))||isset($localUserBySellerCode[$sellerCode]))$oppSellerLocalUser++;
        }
        $tasks=is_array($opp['tarefas']??null)?$opp['tarefas']:[];
        $oppTasks+=count($tasks);
    }
    ksort($oppStatus);

    $localOppCount=0;$localOppOpen=0;
    try{
        $localOppCount=(int)(DB::scalar("SELECT COUNT(*) FROM opportunities")??0);
        $localOppOpen=(int)(DB::scalar("SELECT COUNT(*) FROM opportunities WHERE status='open'")??0);
    }catch(Throwable $e){}

    sectionMap('1. VENDEDORES — VENDAS x CRM');
    rowMap('Vendedores em Vendas', count($salesSellers));
    rowMap('Usuários/vendedores ativos no CRM', count($crmUsers));
    rowMap('Mesmo código nos dois módulos', $sellerExactCode);
    rowMap('Vínculo adicional por e-mail', $sellerEmail);
    rowMap('Vínculo adicional por nome', $sellerName);
    rowMap('Sem correspondência na amostra', count($sellerNoMatch));

    if($sellerNoMatch){
        echo "\nAmostra sem correspondência:\n";
        foreach($sellerNoMatch as $s)echo '- '.($s['codigo']??'').' | '.($s['nome']??'').' | '.($s['email']??'')."\n";
    }

    sectionMap('2. CLIENTE GERAL x CONTA CRM');
    rowMap('Contas CRM reportadas', $accountsResult['total']);
    rowMap('Contas CRM analisadas', count($accounts));
    rowMap('Contas vinculadas ao local por CPF/CNPJ', $accountMatchedLocal);
    rowMap('Vínculo único por CPF/CNPJ', $accountUniqueLocal);
    rowMap('cCodInt CRM = código integração Geral', $accountIntEqualsGeneralInt);
    rowMap('cCodInt CRM = codigo_cliente_omie', $accountIntEqualsGeneralOmie);
    rowMap('Responsável CRM difere de Vendas', $accountSellerDiff);

    if($accountMismatchSamples){
        echo "\nAmostra de responsáveis diferentes (isso é importante):\n";
        foreach($accountMismatchSamples as $m){
            echo '- '.$m['name'].' | '.$m['doc'].' | Vendas='.$m['sales_seller'].' | CRM='.$m['crm_seller']."\n";
        }
    }

    sectionMap('3. OPORTUNIDADES CRM');
    rowMap('Oportunidades CRM reportadas', $opportunitiesResult['total']);
    rowMap('Oportunidades CRM analisadas', count($opportunities));
    rowMap('Ligadas a Conta CRM carregada', $oppLinkedAccount);
    rowMap('Ligadas a cliente local via Conta/CPF-CNPJ', $oppLinkedLocal);
    rowMap('Vendedor reconhecido no CRM', $oppSellerKnown);
    rowMap('Vendedor também ligado a usuário local', $oppSellerLocalUser);
    rowMap('Tarefas embutidas nas oportunidades', $oppTasks);
    rowMap('Oportunidades locais existentes', $localOppCount);
    rowMap('Oportunidades locais abertas', $localOppOpen);

    echo "\nStatus/fases encontrados na amostra de oportunidades:\n";
    if(!$oppStatus)echo "- nenhum status identificado\n";
    foreach($oppStatus as $code=>$qty)echo "- {$code}: {$qty}\n";

    if($oppUnlinkedSamples){
        echo "\nAmostra de oportunidades cujo nCodConta não estava nas páginas de Contas carregadas:\n";
        foreach($oppUnlinkedSamples as $o){
            echo '- Op='.$o['nCodOp'].' | Conta='.$o['nCodConta'].' | Vend='.$o['nCodVendedor'].' | '.$o['cDesOp']."\n";
        }
    }

    sectionMap('4. CATÁLOGOS CRM PARA MAPEAMENTO');
    echo "Fases:\n";
    foreach($phasesResult['rows'] as $p){
        echo '- '.($p['nCodigo']??'').' | '.($p['cDescrUsuario']??$p['cDescrPadrao']??'')."\n";
    }
    echo "\nStatus:\n";
    foreach($statusResult['rows'] as $s){
        echo '- '.($s['nCodigo']??'').' | '.($s['cDescricao']??'')."\n";
    }

    sectionMap('5. MAPEAMENTO PROPOSTO — TECNODATA -> OMIE CRM');
    echo "clients.id/document          -> Conta CRM nCod / cDoc\n";
    echo "responsável da carteira      -> Conta CRM nCodVend\n";
    echo "opportunities.id             -> cCodIntOp = TDOP-{id}\n";
    echo "opportunities.title          -> Oportunidade cDesOp\n";
    echo "opportunities.owner_user_id  -> Oportunidade nCodVendedor\n";
    echo "opportunities.stage_id       -> Oportunidade nCodFase (tabela de mapeamento)\n";
    echo "opportunities.estimated_value-> ticket.nProdutos/nServicos conforme tipo\n";
    echo "opportunities.interest       -> observacoes.cObs / descrição\n";
    echo "tasks.id                     -> cCodInt = TDT-{id}\n";
    echo "tasks.due_at                 -> Tarefa dData + cHora\n";
    echo "tasks.assigned_user_id       -> Tarefa nCodUsuario\n";
    echo "tasks.status done            -> Tarefa cRealizada=S\n";
    echo "activities                   -> Tarefa/Nota ou histórico local, conforme regra definida\n";

    echo "\nDiagnóstico concluído. Nenhum dado foi alterado.\n";
} catch(Throwable $e){
    fwrite(STDERR, "\nERRO: ".$e->getMessage()."\n");
    exit(1);
}
