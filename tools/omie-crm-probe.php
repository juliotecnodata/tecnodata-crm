<?php
declare(strict_types=1);

/**
 * Diagnóstico somente leitura da base CRM do Omie.
 *
 * Uso:
 *   php tools/omie-crm-probe.php
 *   php tools/omie-crm-probe.php --pages=3
 *
 * Não grava no Omie e não altera o banco local.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este diagnóstico deve ser executado via CLI.\n");
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/app/bootstrap.php';

$options = getopt('', ['pages::']);
$pagesToRead = max(1, min(20, (int)($options['pages'] ?? 1)));
$config = $GLOBALS['config']['omie'] ?? [];
$appKey = trim((string)($config['app_key'] ?? ''));
$appSecret = trim((string)($config['app_secret'] ?? ''));
$timeout = max(10, (int)($config['timeout'] ?? 60));

if ($appKey === '' || $appSecret === '' || str_contains($appKey, 'SUA_') || str_contains($appSecret, 'SEU_')) {
    fwrite(STDERR, "Credenciais da Omie não configuradas em config/config.php.\n");
    exit(1);
}

function omieCall(string $url, string $call, array $param, string $appKey, string $appSecret, int $timeout): array
{
    $payload = [
        'call' => $call,
        'app_key' => $appKey,
        'app_secret' => $appSecret,
        'param' => [$param],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Accept-Encoding: identity'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_ENCODING => 'identity',
    ]);

    $raw = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $error !== '') {
        throw new RuntimeException('Falha de comunicação com a Omie: ' . $error);
    }

    $data = json_decode((string)$raw, true);
    if (!is_array($data)) {
        throw new RuntimeException('Resposta inválida da Omie (HTTP ' . $http . ').');
    }

    if ($http >= 400 || isset($data['faultstring']) || isset($data['faultcode'])) {
        throw new RuntimeException((string)($data['faultstring'] ?? $data['description'] ?? ('Erro Omie HTTP ' . $http)));
    }

    return $data;
}

function digits(?string $value): string
{
    return preg_replace('/\D+/', '', (string)$value) ?: '';
}

function out(string $label, mixed $value = null): void
{
    if ($value === null) {
        echo "\n" . $label . "\n";
        echo str_repeat('=', mb_strlen($label)) . "\n";
        return;
    }
    echo str_pad($label, 38) . ': ' . $value . "\n";
}

$crmAccountsUrl = 'https://app.omie.com.br/api/v1/crm/contas/';
$crmUsersUrl = 'https://app.omie.com.br/api/v1/crm/usuarios/';

out('TECNODATA CRM — DIAGNÓSTICO OMIE CRM');
out('Modo', 'somente leitura');
out('Páginas de Contas solicitadas', $pagesToRead);
out('Registros por página', 50);

try {
    $users = [];
    $userPage = 1;
    do {
        $response = omieCall(
            $crmUsersUrl,
            'ListarUsuarios',
            [
                'pagina' => $userPage,
                'registros_por_pagina' => 50,
                'apenas_ativos' => 'S',
            ],
            $appKey,
            $appSecret,
            $timeout
        );
        foreach ((array)($response['cadastros'] ?? []) as $row) {
            $code = trim((string)($row['nCodigo'] ?? ''));
            if ($code !== '') {
                $users[$code] = [
                    'name' => trim((string)($row['cNome'] ?? '')),
                    'email' => trim((string)($row['cEmail'] ?? '')),
                ];
            }
        }
        $userTotalPages = max(1, (int)($response['total_de_paginas'] ?? 1));
        $userPage++;
    } while ($userPage <= $userTotalPages && $userPage <= 20);

    $accounts = [];
    $reportedTotal = 0;
    $reportedPages = 0;
    for ($page = 1; $page <= $pagesToRead; $page++) {
        $response = omieCall(
            $crmAccountsUrl,
            'ListarContas',
            [
                'pagina' => $page,
                'registros_por_pagina' => 50,
            ],
            $appKey,
            $appSecret,
            $timeout
        );

        $reportedTotal = max($reportedTotal, (int)($response['total_de_registros'] ?? 0));
        $reportedPages = max($reportedPages, (int)($response['total_de_paginas'] ?? 0));
        $rows = (array)($response['cadastros'] ?? []);
        foreach ($rows as $row) {
            if (is_array($row)) {
                $accounts[] = $row;
            }
        }

        if ($page >= $reportedPages || !$rows) {
            break;
        }
    }

    $localRows = DB::all(
        "SELECT id,omie_code,name,legal_name,document,seller_omie_code,omie_seller_code,active,crm_inactive
         FROM clients"
    );

    $localByDoc = [];
    $activeLocal = 0;
    $localWithDoc = 0;
    foreach ($localRows as $row) {
        if ((int)($row['active'] ?? 0) === 1 && (int)($row['crm_inactive'] ?? 0) === 0) {
            $activeLocal++;
        }
        $doc = digits((string)($row['document'] ?? ''));
        if ($doc === '') {
            continue;
        }
        $localWithDoc++;
        $localByDoc[$doc][] = $row;
    }

    $withDoc = 0;
    $withSeller = 0;
    $matched = 0;
    $matchedUnique = 0;
    $matchedDuplicate = 0;
    $missingDoc = [];
    $unmatched = [];
    $sellerCounts = [];
    $tagCounts = ['cfc' => 0, 'revendedor' => 0, 'ambos' => 0];

    foreach ($accounts as $account) {
        $ident = is_array($account['identificacao'] ?? null) ? $account['identificacao'] : [];
        $doc = digits((string)($ident['cDoc'] ?? ''));
        $sellerCode = trim((string)($ident['nCodVend'] ?? ''));
        $name = trim((string)($ident['cNomeFantasia'] ?? $ident['cNome'] ?? ''));
        $accountCode = trim((string)($ident['nCod'] ?? ''));

        $tags = [];
        foreach ((array)($account['tags'] ?? []) as $tagRow) {
            $tag = mb_strtolower(trim((string)(is_array($tagRow) ? ($tagRow['tag'] ?? '') : $tagRow)), 'UTF-8');
            if ($tag !== '') {
                $tags[$tag] = true;
            }
        }
        $isCfc = isset($tags['cfc']);
        $isReseller = isset($tags['revendedor']);
        if ($isCfc) $tagCounts['cfc']++;
        if ($isReseller) $tagCounts['revendedor']++;
        if ($isCfc && $isReseller) $tagCounts['ambos']++;

        if ($doc !== '') {
            $withDoc++;
        } else {
            if (count($missingDoc) < 15) {
                $missingDoc[] = [$accountCode, $name, $sellerCode];
            }
        }

        if ($sellerCode !== '') {
            $withSeller++;
            $sellerCounts[$sellerCode] = ($sellerCounts[$sellerCode] ?? 0) + 1;
        }

        if ($doc !== '' && isset($localByDoc[$doc])) {
            $matched++;
            if (count($localByDoc[$doc]) === 1) {
                $matchedUnique++;
            } else {
                $matchedDuplicate++;
            }
        } elseif (count($unmatched) < 20) {
            $unmatched[] = [$accountCode, $name, $doc, $sellerCode];
        }
    }

    arsort($sellerCounts);

    out('RESUMO DA API');
    out('Contas CRM reportadas pela Omie', $reportedTotal);
    out('Total de páginas reportadas', $reportedPages);
    out('Contas analisadas nesta execução', count($accounts));
    out('Usuários CRM ativos carregados', count($users));

    out('CRUZAMENTO COM A BASE LOCAL');
    out('Clientes locais (total)', count($localRows));
    out('Clientes locais ativos', $activeLocal);
    out('Clientes locais com CPF/CNPJ', $localWithDoc);
    out('Contas CRM com CPF/CNPJ na amostra', $withDoc);
    out('Contas CRM com responsável', $withSeller);
    out('Contas CRM encontradas localmente', $matched);
    out('Vínculo único por CPF/CNPJ', $matchedUnique);
    out('CPF/CNPJ local duplicado na amostra', $matchedDuplicate);
    $matchRate = count($accounts) > 0 ? round(($matched / count($accounts)) * 100, 1) : 0;
    out('Taxa de vínculo da amostra', number_format($matchRate, 1, ',', '.') . '%');

    out('TAGS ENCONTRADAS NA AMOSTRA');
    out('Contas com tag CFC', $tagCounts['cfc']);
    out('Contas com tag Revendedor', $tagCounts['revendedor']);
    out('Contas com ambas', $tagCounts['ambos']);

    out('DISTRIBUIÇÃO POR RESPONSÁVEL CRM');
    if (!$sellerCounts) {
        echo "Nenhum nCodVend encontrado na amostra.\n";
    } else {
        foreach (array_slice($sellerCounts, 0, 30, true) as $code => $count) {
            $meta = $users[$code] ?? ['name' => 'NÃO LOCALIZADO EM USUÁRIOS CRM', 'email' => ''];
            echo str_pad($code, 14)
                . str_pad((string)$count, 8)
                . ($meta['name'] ?: 'Sem nome')
                . ($meta['email'] !== '' ? ' <' . $meta['email'] . '>' : '')
                . "\n";
        }
    }

    out('AMOSTRA — CONTAS CRM SEM VÍNCULO LOCAL');
    if (!$unmatched) {
        echo "Nenhuma na amostra consultada.\n";
    } else {
        foreach ($unmatched as [$code, $name, $doc, $seller]) {
            echo "Conta={$code} | {$name} | doc=" . ($doc ?: 'SEM_DOC') . " | vendedor=" . ($seller ?: 'SEM_VENDEDOR') . "\n";
        }
    }

    out('AMOSTRA — CONTAS CRM SEM CPF/CNPJ');
    if (!$missingDoc) {
        echo "Nenhuma na amostra consultada.\n";
    } else {
        foreach ($missingDoc as [$code, $name, $seller]) {
            echo "Conta={$code} | {$name} | vendedor=" . ($seller ?: 'SEM_VENDEDOR') . "\n";
        }
    }

    echo "\nDiagnóstico concluído. Nenhum dado foi alterado.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "\nERRO: " . $e->getMessage() . "\n");
    exit(1);
}
