<?php
declare(strict_types=1);

require dirname(__DIR__).'/app/bootstrap.php';

function auditQuery(string $label, callable $query): void
{
    $started = microtime(true);
    $result = $query();
    $elapsed = (microtime(true) - $started) * 1000;
    echo $label.': '.number_format($elapsed, 1, '.', '')." ms\n";
    if ($result !== null) {
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
    }
}

$tables = ['clients','orders','service_orders','financial_movements','activities','tasks','collection_cases','collection_actions'];
foreach ($tables as $table) {
    auditQuery('count '.$table, static fn() => (int) DB::scalar('SELECT COUNT(*) FROM '.$table));
}

foreach (['clients','activities','tasks','collection_cases','collection_actions','orders','service_orders'] as $table) {
    $indexes = DB::all('SHOW INDEX FROM '.$table);
    $summary = [];
    foreach ($indexes as $index) {
        $name = (string) $index['Key_name'];
        $summary[$name][] = (string) $index['Column_name'];
    }
    echo 'indexes '.$table.': '.json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
}

$plans = [
    'latest activity by client' => "SELECT id FROM activities WHERE client_id=1 ORDER BY created_at DESC,id DESC LIMIT 1",
    'pending task by client' => "SELECT id FROM tasks WHERE client_id=1 AND type IN ('sales','collection') AND status='pending' ORDER BY due_at,id LIMIT 1",
    'admin overdue notifications' => "SELECT COUNT(*) FROM tasks WHERE status='pending' AND DATE(due_at)<CURDATE()",
    'seller overdue notifications' => "SELECT COUNT(*) FROM tasks WHERE assigned_user_id=1 AND status='pending' AND DATE(due_at)<CURDATE()",
];
foreach ($plans as $label => $sql) {
    $plan = DB::all('EXPLAIN '.$sql);
    $compact = array_map(static fn(array $row): array => [
        'table' => $row['table'] ?? null,
        'type' => $row['type'] ?? null,
        'key' => $row['key'] ?? null,
        'rows' => $row['rows'] ?? null,
        'extra' => $row['Extra'] ?? null,
    ], $plan);
    echo 'explain '.$label.': '.json_encode($compact, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
}

foreach (['max_connections','wait_timeout','interactive_timeout'] as $variable) {
    try {
        $row = DB::one("SHOW VARIABLES LIKE '".$variable."'");
        echo 'variable '.$variable.': '.json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
    } catch (Throwable $e) {
        echo 'variable '.$variable.": unavailable\n";
    }
}

foreach (['Threads_connected','Threads_running','Max_used_connections','Aborted_connects'] as $status) {
    try {
        $row = DB::one("SHOW GLOBAL STATUS LIKE '".$status."'");
        echo 'status '.$status.': '.json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
    } catch (Throwable $e) {
        echo 'status '.$status.": unavailable\n";
    }
}

$configured = DB::scalar("SELECT value_json FROM settings WHERE setting_key='contact_monitoring_users'");
$configured = $configured ? json_decode((string) $configured, true) : null;
$monitorIds = array_values(array_filter(array_map('intval', (array) ($configured['user_ids'] ?? []))));
$userWhere = "active=1 AND (role='collector' OR (role='seller' AND seller_omie_code IS NOT NULL AND TRIM(seller_omie_code)<>''))";
$userParams = [];
if ($monitorIds) {
    $userWhere .= ' AND id IN ('.implode(',', array_fill(0, count($monitorIds), '?')).')';
    $userParams = $monitorIds;
}
$participants = DB::all('SELECT id,role,seller_omie_code FROM users WHERE '.$userWhere, $userParams);
$sellerCodes = array_values(array_unique(array_filter(array_map(static fn(array $row): string => $row['role'] === 'seller' ? trim((string) $row['seller_omie_code']) : '', $participants))));
$collectorIds = array_values(array_map(static fn(array $row): int => (int) $row['id'], array_filter($participants, static fn(array $row): bool => $row['role'] === 'collector')));
$participantWhere = [];
$participantParams = [];
if ($sellerCodes) {
    $participantWhere[] = 'c.seller_omie_code IN ('.implode(',', array_fill(0, count($sellerCodes), '?')).')';
    array_push($participantParams, ...$sellerCodes);
}
if ($collectorIds) {
    $participantWhere[] = 'EXISTS (SELECT 1 FROM collection_cases cc WHERE cc.client_id=c.id AND cc.assigned_user_id IN ('.implode(',', array_fill(0, count($collectorIds), '?')).'))';
    array_push($participantParams, ...$collectorIds);
}
$scope = $participantWhere ? 'c.active=1 AND ('.implode(' OR ', $participantWhere).')' : '1=0';

auditQuery('contact monitoring current aggregate', static fn() => DB::one(
    "SELECT COUNT(*) total,
            SUM(CASE WHEN la.id IS NOT NULL OR lca.id IS NOT NULL THEN 1 ELSE 0 END) contacted,
            SUM(CASE WHEN nt.id IS NOT NULL THEN 1 ELSE 0 END) scheduled
     FROM clients c
     LEFT JOIN activities la ON la.id=(SELECT a2.id FROM activities a2 WHERE a2.client_id=c.id ORDER BY a2.created_at DESC,a2.id DESC LIMIT 1)
     LEFT JOIN collection_actions lca ON lca.id=(SELECT ca2.id FROM collection_actions ca2 WHERE ca2.client_id=c.id ORDER BY ca2.created_at DESC,ca2.id DESC LIMIT 1)
     LEFT JOIN tasks nt ON nt.id=(SELECT t2.id FROM tasks t2 WHERE t2.client_id=c.id AND t2.type IN ('sales','collection') AND t2.status='pending' ORDER BY t2.due_at,t2.id LIMIT 1)
     WHERE ".$scope,
    $participantParams
));

auditQuery('contact monitoring first 5', static fn() => DB::all(
    "SELECT c.id,
            (SELECT a2.id FROM activities a2 WHERE a2.client_id=c.id ORDER BY a2.created_at DESC,a2.id DESC LIMIT 1) last_activity_id,
            (SELECT ca2.id FROM collection_actions ca2 WHERE ca2.client_id=c.id ORDER BY ca2.created_at DESC,ca2.id DESC LIMIT 1) last_collection_action_id,
            (SELECT t2.id FROM tasks t2 WHERE t2.client_id=c.id AND t2.type IN ('sales','collection') AND t2.status='pending' ORDER BY t2.due_at,t2.id LIMIT 1) next_task_id
     FROM clients c WHERE ".$scope." ORDER BY c.name LIMIT 5",
    $participantParams
));

auditQuery('client tag catalog', static fn() => count(DB::all(
    "SELECT MIN(TRIM(client_tag.tag)) tag,COUNT(DISTINCT c.id) client_count
     FROM clients c JOIN JSON_TABLE(c.raw_json, '$.tags[*]' COLUMNS(tag VARCHAR(190) PATH '$.tag')) client_tag
     WHERE c.active=1 AND client_tag.tag IS NOT NULL AND TRIM(client_tag.tag)<>''
     GROUP BY LOWER(TRIM(client_tag.tag))"
)));
