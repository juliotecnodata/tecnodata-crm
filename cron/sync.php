<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Tecnodata\CRM\Core\DB;
use Tecnodata\CRM\Services\ModularSyncService;

try{
    $admin=DB::fetch("SELECT id FROM users WHERE role='admin' AND active=1 ORDER BY id LIMIT 1");
    if(!$admin) throw new RuntimeException('Nenhum administrador ativo.');

    $svc=new ModularSyncService();
    $requested=(string)($argv[1]??'financial');
    $reset=in_array('--reset',$argv,true);
    $modules=$requested==='all'?array_keys($svc->modules()):array_values(array_filter(explode(',',$requested),fn($module)=>isset($svc->modules()[$module])));
    if(!$modules) throw new RuntimeException('Módulo inválido. Use financial, sellers, clients, orders, services, metrics ou all.');
    foreach($modules as $module){
        $run=$svc->start($module,(int)$admin['id'],$reset);
        do{
            $r=$svc->step((int)$run['run_id']);
            echo date('c')." {$module} ".json_encode($r,JSON_UNESCAPED_UNICODE).PHP_EOL;
            if(!$r['done']) usleep((int)($GLOBALS['config']['sync']['request_pause_ms']??900)*1000);
        }while(!$r['done']);
        sleep(2);
    }
    echo date('c').' OK '.implode(',',$modules).PHP_EOL;
}catch(Throwable $e){
    echo date('c').' ERROR '.$e->getMessage().PHP_EOL;
    exit(1);
}
