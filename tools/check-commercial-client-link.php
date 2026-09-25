<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Execute via CLI.\n");
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

CommercialSchema::ensure();
$account = DB::one("SELECT a.omie_code FROM crm_accounts a LEFT JOIN crm_account_links l ON l.crm_account_code=a.omie_code WHERE a.active=1 AND l.crm_account_code IS NULL ORDER BY a.omie_code LIMIT 1");
$client = DB::one("SELECT id FROM clients WHERE active=1 AND crm_inactive=0 ORDER BY id LIMIT 1");
$actor = (int)(DB::scalar("SELECT id FROM users WHERE active=1 AND role IN('admin','supervisor') ORDER BY id LIMIT 1") ?? 0);

if (!$account || !$client || $actor <= 0) {
    fwrite(STDERR, "Base sem Conta CRM livre, Cliente Geral ativo ou gestor para o teste.\n");
    exit(2);
}

$code = (string)$account['omie_code'];
$clientId = (int)$client['id'];
$pdo = DB::conn();
$pdo->beginTransaction();
try {
    $linked = CommercialAccountService::linkClient($code, $clientId, $actor, true, 'Teste automatizado com rollback');
    if ((int)($linked['client_id'] ?? 0) !== $clientId) throw new RuntimeException('O vínculo não foi persistido.');
    $audit = (int)(DB::scalar("SELECT COUNT(*) FROM crm_account_link_audit WHERE crm_account_code=? AND client_id=?", [$code, $clientId]) ?? 0);
    if ($audit < 1) throw new RuntimeException('A auditoria do vínculo não foi registrada.');
    CommercialAccountService::unlinkClient($code, $actor, 'Teste automatizado com rollback');
    if ((int)(DB::scalar("SELECT COUNT(*) FROM crm_account_links WHERE crm_account_code=?", [$code]) ?? 0) !== 0) throw new RuntimeException('O desvínculo não foi aplicado.');
    $pdo->rollBack();
    echo "Vínculo, auditoria e desvínculo: OK (rollback)\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "Falha: " . $e->getMessage() . "\n");
    exit(1);
}
