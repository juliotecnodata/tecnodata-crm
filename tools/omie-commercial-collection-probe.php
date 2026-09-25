<?php
declare(strict_types=1);

/**
 * Diagnóstico somente leitura: Contas a Receber x Vendas x CRM x cobrança local.
 * Uso: php tools/omie-commercial-collection-probe.php --pages=3
 */
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Execute via CLI.\n"); exit(1); }
require dirname(__DIR__) . '/app/bootstrap.php';

$options = getopt('', ['pages::']);
$maxPages = max(1, min(50, (int)($options['pages'] ?? 3)));
$omie = new OmieClient();
$titles = [];$reported = 0;$reportedPages = 0;

for ($page = 1; $page <= $maxPages; $page++) {
    $response = $omie->call('receivables', 'ListarContasReceber', [
        'pagina'=>$page,
        'registros_por_pagina'=>100,
        'apenas_importado_api'=>'N',
        'filtrar_apenas_titulos_em_aberto'=>'S',
        'exibir_obs'=>'N',
    ]);
    $reported = max($reported, (int)($response['total_de_registros'] ?? 0));
    $reportedPages = max($reportedPages, (int)($response['total_de_paginas'] ?? 0));
    foreach ((array)($response['conta_receber_cadastro'] ?? []) as $row) if (is_array($row)) $titles[] = $row;
    if ($reportedPages > 0 && $page >= $reportedPages) break;
}

$clientCodes = [];$orderCodes = [];$serviceCodes = [];
foreach (DB::all("SELECT omie_code FROM clients WHERE active=1") as $row) $clientCodes[(string)$row['omie_code']] = true;
foreach (DB::all("SELECT omie_code FROM orders") as $row) $orderCodes[(string)$row['omie_code']] = true;
foreach (DB::all("SELECT omie_code FROM service_orders") as $row) $serviceCodes[(string)$row['omie_code']] = true;

$stats = ['client_local'=>0,'client_crm_link'=>0,'with_order'=>0,'order_local'=>0,'with_service'=>0,'service_local'=>0,'without_origin'=>0];
$statuses = [];$origins = [];$samples = [];
foreach ($titles as $title) {
    $clientCode = trim((string)($title['codigo_cliente_fornecedor'] ?? ''));
    $orderCode = trim((string)($title['nCodPedido'] ?? ''));
    $serviceCode = trim((string)($title['nCodOS'] ?? ''));
    $status = trim((string)($title['status_titulo'] ?? 'SEM_STATUS')) ?: 'SEM_STATUS';
    $origin = trim((string)($title['id_origem'] ?? 'SEM_ORIGEM')) ?: 'SEM_ORIGEM';
    $statuses[$status] = ($statuses[$status] ?? 0) + 1;
    $origins[$origin] = ($origins[$origin] ?? 0) + 1;
    if ($clientCode !== '' && isset($clientCodes[$clientCode])) {
        $stats['client_local']++;
        $hasLink = (int)(DB::scalar("SELECT COUNT(*) FROM clients c JOIN crm_account_links l ON l.client_id=c.id WHERE c.omie_code=?", [$clientCode]) ?? 0) > 0;
        if ($hasLink) $stats['client_crm_link']++;
    } elseif (count($samples) < 12) $samples[] = ['title'=>$title['codigo_lancamento_omie'] ?? '', 'client'=>$clientCode, 'status'=>$status];
    if ($orderCode !== '' && $orderCode !== '0') { $stats['with_order']++; if (isset($orderCodes[$orderCode])) $stats['order_local']++; }
    if ($serviceCode !== '' && $serviceCode !== '0') { $stats['with_service']++; if (isset($serviceCodes[$serviceCode])) $stats['service_local']++; }
    if (($orderCode === '' || $orderCode === '0') && ($serviceCode === '' || $serviceCode === '0')) $stats['without_origin']++;
}
arsort($statuses);arsort($origins);

echo "OMIE — MAPA COMERCIAL + COBRANÇA (SOMENTE LEITURA)\n";
echo str_repeat('=', 55) . "\n";
printf("Títulos abertos reportados: %d\n", $reported);
printf("Títulos analisados: %d em até %d página(s)\n", count($titles), $maxPages);
printf("Cliente Geral presente no CRM local: %d/%d\n", $stats['client_local'], count($titles));
printf("Cliente também ligado a Conta CRM: %d/%d\n", $stats['client_crm_link'], count($titles));
printf("Com Pedido Omie: %d (pedido local: %d)\n", $stats['with_order'], $stats['order_local']);
printf("Com OS Omie: %d (OS local: %d)\n", $stats['with_service'], $stats['service_local']);
printf("Sem Pedido/OS explícito: %d\n", $stats['without_origin']);
printf("Movimentos financeiros locais: %d\n", (int)(DB::scalar("SELECT COUNT(*) FROM financial_movements") ?? 0));
printf("Casos de cobrança locais abertos: %d\n", (int)(DB::scalar("SELECT COUNT(*) FROM collection_cases WHERE status='open'") ?? 0));

echo "\nStatus dos títulos na amostra:\n";
foreach ($statuses as $name=>$count) echo "- {$name}: {$count}\n";
echo "\nOrigens na amostra:\n";
foreach ($origins as $name=>$count) echo "- {$name}: {$count}\n";
if ($samples) {
    echo "\nAmostra de Clientes Omie ainda ausentes da base local:\n";
    foreach ($samples as $sample) echo '- título '.$sample['title'].' | cliente '.$sample['client'].' | '.$sample['status']."\n";
}
echo "\nNenhum dado foi alterado.\n";
