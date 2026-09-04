<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';
use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
use Tecnodata\CRM\Core\Security;
use Tecnodata\CRM\Services\ModularSyncService;

Auth::requireLogin();
if(!Auth::can('admin')){http_response_code(403);exit;}

$svc=new ModularSyncService();
$modules=$svc->modules();
$status=$svc->status();
$runs=DB::all("SELECT s.*,u.name FROM sync_runs s LEFT JOIN users u ON u.id=s.started_by ORDER BY s.id DESC LIMIT 30");
include '_layout.php';
?>
<div class="d-flex flex-wrap justify-content-between gap-3 align-items-end mb-4">
 <div><h1 class="h3 mb-1">Sincronização Omie</h1>
 <div class="text-secondary">Modular, retomável e sem manter uma requisição longa aberta.</div></div>
 <div class="small text-secondary"><i class="fa-solid fa-shield-halved me-1"></i>1 página por requisição • pausa automática • retry controlado</div>
</div>

<div class="alert alert-light border mb-4">
 <strong>Como funciona:</strong> escolha um módulo. O sistema processa uma página da Omie, grava no banco e só então solicita a próxima.
 Se o navegador fechar ou houver timeout, o progresso fica salvo e pode ser retomado.
</div>

<div class="row g-3 mb-4" id="syncModules">
<?php foreach($modules as $key=>$m):
 $s=$status['states'][$key];
 $pct=((int)$s['total_pages']>0)?min(100,((int)$s['current_page']/(int)$s['total_pages']*100)):0;
?>
<div class="col-md-6 col-xl-4">
 <div class="card h-100"><div class="card-body">
  <div class="d-flex justify-content-between gap-3">
   <div class="d-flex gap-3">
    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;background:#eef1f3"><i class="fa-solid <?=e($m['icon'])?>"></i></div>
    <div><h2 class="h6 mb-1"><?=e($m['label'])?></h2><div class="small text-secondary" id="state-<?=$key?>"><?=e($s['status'])?></div>
    <?php if($key==='financial'):?><div class="small text-secondary mt-1">Contas selecionadas • atrasados + pagamentos parciais • saldo aberto real • últimos 3 anos</div><?php endif;?><?php if($key==='orders'):?><div class="small text-secondary mt-1">Ontem + hoje • atualiza/inclui pedidos • sincroniza também as mudanças de etapa</div><?php endif;?><?php if($key==='services'):?><div class="small text-secondary mt-1">Somente ontem + hoje • atualiza existentes e inclui novos</div><?php endif;?>
    <?php if($key==='products'):?><div class="small text-secondary mt-1">Catálogo de produtos Omie • consulta oficial ListarProdutos</div><?php endif;?>
    <?php if($key==='categories'):?><div class="small text-secondary mt-1">Categorias de receita usadas no pedido Omie</div><?php endif;?></div>
   </div>
   <div class="small text-secondary" id="count-<?=$key?>"><?=number_format((int)$s['processed'],0,',','.')?></div>
  </div>
  <div class="td-progress mt-3"><span id="bar-<?=$key?>" style="width:<?=$pct?>%"></span></div>
  <div class="d-flex justify-content-between mt-2 small text-secondary">
   <span id="page-<?=$key?>"><?=((int)$s['total_pages']>0)?('Página '.$s['current_page'].'/'.$s['total_pages']):'Aguardando'?></span>
   <span><?=!empty($s['last_success_at'])?date('d/m H:i',strtotime($s['last_success_at'])):''?></span>
  </div>
  <?php if($s['last_error']):?><div class="small text-danger mt-2"><?=e($s['last_error'])?></div><?php endif;?>
  <div class="d-flex gap-2 mt-3">
   <button class="btn btn-dark btn-sm sync-start" data-module="<?=$key?>"><i class="fa-solid fa-play me-1"></i>Iniciar / retomar</button>
   <?php if($key==='financial'):?>
   <button class="btn btn-outline-secondary btn-sm sync-reset" data-module="<?=$key?>" title="Reconstruir financeiro com segurança"><i class="fa-solid fa-broom me-1"></i>Limpar e reconstruir</button>
   <?php else:?>
   <button class="btn btn-outline-secondary btn-sm sync-reset" data-module="<?=$key?>" title="Recomeçar este módulo"><i class="fa-solid fa-rotate-left"></i></button>
   <?php endif;?>
  </div>
 </div></div>
</div>
<?php endforeach;?>
</div>

<div class="card mb-4"><div class="card-body">
 <h2 class="h5 mb-2">Ordem recomendada</h2>
 <div class="text-secondary">Vendedores → Clientes → Produtos → Categorias → Condições de Pagamento → Cenários Fiscais → Locais de Estoque → Meios de Pagamento → Tipos de Documento → Pedidos → Serviços → Financeiro → Indicadores.</div>
 <div class="small text-secondary mt-2">Os vendedores continuam trabalhando normalmente enquanto a sincronização não estiver rodando. A navegação nunca chama a Omie.</div>
</div></div>

<div class="card"><div class="card-body p-0">
 <div class="p-3 border-bottom"><h2 class="h5 mb-0">Histórico</h2></div>
 <div class="table-responsive data-table-wrap"><table class="table modern-table data-table mb-0" data-entity="execuções" data-page-length="10" data-order-column="0"><thead><tr><th>Início</th><th>Módulo</th><th>Fim</th><th>Status</th><th>Usuário</th><th>Detalhes</th></tr></thead><tbody>
 <?php foreach($runs as $r):?>
 <tr><td><?=date('d/m/Y H:i',strtotime($r['started_at']))?></td><td><?=e($r['module_key']??'V1')?></td>
 <td><?=$r['finished_at']?date('d/m/Y H:i',strtotime($r['finished_at'])):'—'?></td><td><?=e($r['status'])?></td>
 <td><?=e($r['name']??'Sistema')?></td><td class="small"><?=e($r['error_message']??$r['stats_json']??'')?></td></tr>
 <?php endforeach;?></tbody></table></div>
</div></div>

<div class="modal fade" id="syncModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
<div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title">Sincronizando</h5></div>
<div class="modal-body">
 <div class="d-flex justify-content-between mb-2"><strong id="modalModule">Módulo</strong><span id="modalPage">Preparando…</span></div>
 <div class="progress" style="height:10px"><div class="progress-bar bg-dark" id="modalBar" style="width:0%"></div></div>
 <div class="small text-secondary mt-3" id="modalInfo">O sistema fará uma chamada por vez.</div>
</div>
<div class="modal-footer"><button class="btn btn-outline-secondary" id="stopSync">Parar após esta página</button></div>
</div></div></div>

<script>
const csrf = <?=json_encode(Security::csrf())?>;
const pauseMs = <?=json_encode((int)($GLOBALS['config']['sync']['request_pause_ms']??900))?>;
let stopRequested = false;
let modal;
let stopButton;

document.addEventListener('DOMContentLoaded',()=>{
  modal = new bootstrap.Modal(document.getElementById('syncModal'));
  stopButton = document.getElementById('stopSync');
  document.querySelectorAll('.sync-start').forEach(b=>b.addEventListener('click',()=>startSync(b.dataset.module,false)));
  document.querySelectorAll('.sync-reset').forEach(b=>b.addEventListener('click',()=>{
    const message=b.dataset.module==='financial'
      ? 'Reconstruir o financeiro? A carteira atual será mantida durante a consulta. Ao concluir 100%, o CRM removerá títulos que não existem mais e atualizará atrasados e pagamentos parciais com o saldo real da Omie.'
      : (b.dataset.module==='services'
        ? 'Reiniciar a sincronização de serviços? O histórico local não será apagado; o CRM consultará novamente somente ontem e hoje e fará atualização/inclusão.'
        : (b.dataset.module==='orders'
        ? 'Reiniciar a sincronização de pedidos? O histórico local não será apagado; o CRM consultará novamente somente ontem e hoje e fará atualização/inclusão.'
        : 'Recomeçar este módulo desde a primeira página? Os dados já gravados não serão apagados, apenas atualizados novamente.'));
    if(confirm(message))
      startSync(b.dataset.module,true);
  }));
  stopButton.addEventListener('click',()=>{
    if(stopButton.dataset.action==='close'){location.reload();return;}
    stopRequested=true;
    stopButton.disabled=true;
    document.getElementById('modalInfo').textContent='Parando após concluir a página atual…';
  });
});

async function post(url, body){
  const r = await fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
  const txt = await r.text();
  let data; try{data=JSON.parse(txt)}catch(e){throw new Error('Resposta inválida do servidor: '+txt.slice(0,300))}
  if(!r.ok || !data.ok) throw new Error(data.error || 'Falha na sincronização');
  return data;
}
function sleep(ms){return new Promise(resolve=>setTimeout(resolve,ms));}

async function startSync(module, reset){
  stopRequested=false;
  stopButton.disabled=false;
  stopButton.dataset.action='stop';
  stopButton.textContent='Parar após esta página';
  document.getElementById('modalModule').textContent=module;
  document.getElementById('modalPage').textContent='Iniciando…';
  document.getElementById('modalBar').style.width='0%';
  document.getElementById('modalInfo').textContent='Uma página será processada por vez.';
  modal.show();

  try{
    const start=await post('api/sync-start.php',{_token:csrf,module,reset});
    let done=false;
    while(!done && !stopRequested){
      const data=await post('api/sync-step.php',{_token:csrf,run_id:start.run_id});
      if(data.status && data.status!=='running' && data.status!=='success')
        throw new Error(data.message || 'A sincronização foi interrompida.');
      done=!!data.done;
      const total=Number(data.total_pages||0), page=Number(data.page||0);
      const pct=total?Math.min(100,page/total*100):0;

      document.getElementById('modalPage').textContent= total ? `Página ${page}/${total}` : `Página ${page}`;
      document.getElementById('modalBar').style.width=pct+'%';
      let phase='';
      if(data.phase){
        if(data.phase==='ATRASADO') phase='Atrasados • ';
        else if(data.phase==='PAGTO_PARCIAL') phase='Pagamentos parciais • ';
        else if(data.phase==='pedidos') phase='Pedidos • ';
        else if(data.phase==='etapas') phase='Mudanças de etapa • ';
        else phase=data.phase+' • ';
      }
      const account=data.account ? data.account+' • ' : '';
      document.getElementById('modalInfo').textContent=`${account}${phase}${Number(data.processed||0).toLocaleString('pt-BR')} registros processados`;

      const state=document.getElementById('state-'+module);
      const count=document.getElementById('count-'+module);
      const pg=document.getElementById('page-'+module);
      const bar=document.getElementById('bar-'+module);
      if(state) state.textContent=done?'success':'running';
      if(count) count.textContent=Number(data.processed||0).toLocaleString('pt-BR');
      if(pg) pg.textContent=total?`Página ${page}/${total}`:`Página ${page}`;
      if(bar) bar.style.width=pct+'%';

      if(!done) await sleep(pauseMs);
    }

    if(done){
      document.getElementById('modalInfo').textContent='Módulo concluído com sucesso.';
      document.getElementById('modalBar').style.width='100%';
      setTimeout(()=>location.reload(),700);
    }else{
      await post('api/sync-stop.php',{_token:csrf,run_id:start.run_id});
      document.getElementById('modalInfo').textContent='Sincronização pausada. Você poderá retomar deste ponto.';
      setTimeout(()=>{modal.hide();location.reload();},800);
    }
  }catch(e){
    document.getElementById('modalInfo').innerHTML='<span class="text-danger"></span>';
    document.getElementById('modalInfo').querySelector('span').textContent=e.message;
    stopButton.disabled=false;
    stopButton.dataset.action='close';
    stopButton.textContent='Fechar';
  }
}
</script>
<?php include '_footer.php';?>
