<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\Security;
use Tecnodata\CRM\Services\OrderIntegrationHealthService;

Auth::requireLogin();
if(!Auth::can('admin')){http_response_code(403);exit('Sem acesso');}

$local=OrderIntegrationHealthService::local();
$api=null;
if($_SERVER['REQUEST_METHOD']==='POST' && Security::verifyCsrf($_POST['_token']??null)){
    $api=OrderIntegrationHealthService::api();
}

include '_layout.php';?>
<div class="page-heading">
 <div><div class="eyebrow">DIAGNÓSTICO</div><h1>Pedidos Omie</h1><p>Valida banco, cadastros e chamadas de leitura antes de permitir qualquer pedido real.</p></div>
 <a class="btn btn-outline-secondary" href="<?=APP_URL?>/configuracoes.php">Configurações</a>
</div>

<div class="panel-card mb-4">
 <div class="panel-header"><div><span>LOCAL</span><h2>Estrutura e cadastros</h2></div><span class="status-pill <?=$local['ok']?'status-paid':'status-partial'?>"><?=$local['ok']?'OK':'Pendente'?></span></div>
 <div class="panel-body">
  <div class="diag-grid">
   <?php foreach($local['checks'] as $check):?><div class="diag-row <?=$check['ok']?'is-ok':'is-error'?>"><i class="fa-solid <?=$check['ok']?'fa-circle-check':'fa-circle-xmark'?>"></i><div><strong><?=e($check['label'])?></strong><small><?=e($check['detail'])?></small></div></div><?php endforeach;?>
  </div>
 </div>
</div>

<div class="panel-card">
 <div class="panel-header"><div><span>OMIE</span><h2>Teste de leitura das APIs</h2></div></div>
 <div class="panel-body">
  <p class="text-secondary">Este teste não cria, altera ou exclui nada. Ele apenas chama as APIs auxiliares com 1 registro para confirmar endpoint, método e parâmetros.</p>
  <form method="post" class="mb-3"><input type="hidden" name="_token" value="<?=Security::csrf()?>"><button class="btn btn-primary"><i class="fa-solid fa-stethoscope"></i>Testar integração Omie</button></form>
  <?php if($api):?><div class="diag-grid">
   <?php foreach($api['checks'] as $check):?><div class="diag-row <?=$check['ok']?'is-ok':'is-error'?>"><i class="fa-solid <?=$check['ok']?'fa-circle-check':'fa-circle-xmark'?>"></i><div><strong><?=e($check['label'])?></strong><small><?=e($check['detail'])?></small></div></div><?php endforeach;?>
  </div><?php endif;?>
 </div>
</div>
<?php include '_footer.php';?>
