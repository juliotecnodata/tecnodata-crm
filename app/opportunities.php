<?php
declare(strict_types=1);

function sales_flow_enabled(): bool{
 try{
  $raw=DB::scalar("SELECT value_json FROM settings WHERE setting_key='sales_flow_enabled'");
  if(!$raw)return false;
  $v=json_decode((string)$raw,true);
  return is_array($v)?!empty($v['enabled']):(bool)$v;
 }catch(Throwable){return false;}
}

function ensure_sales_flow_tables(): void{
 DB::exec("CREATE TABLE IF NOT EXISTS pipeline_stages(
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL,
  name VARCHAR(100) NOT NULL,
  position INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  is_won TINYINT(1) NOT NULL DEFAULT 0,
  is_lost TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_pipeline_stage_code(code)
 )");
 DB::exec("CREATE TABLE IF NOT EXISTS sales_activity_types(
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(60) NOT NULL,
  name VARCHAR(100) NOT NULL,
  icon VARCHAR(60) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  position INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_sales_activity_type_code(code)
 )");
 DB::exec("CREATE TABLE IF NOT EXISTS opportunities(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NOT NULL,
  owner_user_id INT UNSIGNED NOT NULL,
  stage_id INT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  interest VARCHAR(255) NULL,
  estimated_value DECIMAL(15,2) NOT NULL DEFAULT 0,
  status ENUM('open','won','lost') NOT NULL DEFAULT 'open',
  next_action_type VARCHAR(60) NULL,
  next_action_at DATETIME NULL,
  next_action_note VARCHAR(255) NULL,
  lost_reason VARCHAR(180) NULL,
  won_order_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  closed_at DATETIME NULL,
  FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
  FOREIGN KEY(owner_user_id) REFERENCES users(id),
  FOREIGN KEY(stage_id) REFERENCES pipeline_stages(id),
  INDEX idx_opportunities_owner_status(owner_user_id,status),
  INDEX idx_opportunities_stage(stage_id,status),
  INDEX idx_opportunities_next_action(next_action_at,status)
 )");
 DB::exec("CREATE TABLE IF NOT EXISTS opportunity_history(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  opportunity_id BIGINT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  event_type VARCHAR(40) NOT NULL,
  description VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY(opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id),
  INDEX idx_opportunity_history_date(opportunity_id,created_at)
 )");
 DB::exec("CREATE TABLE IF NOT EXISTS calendar_preferences(
  user_id INT UNSIGNED PRIMARY KEY,
  provider VARCHAR(30) NOT NULL DEFAULT 'google',
  mode ENUM('disabled','export','sync') NOT NULL DEFAULT 'disabled',
  calendar_id VARCHAR(190) NULL,
  connected_at DATETIME NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
 )");

 $stages=[['entrada','Entrada',10],['contato','Contato',20],['retorno','Retorno',30],['proposta','Proposta',40],['fechamento','Fechamento',50]];
 foreach($stages as $s)DB::exec("INSERT IGNORE INTO pipeline_stages(code,name,position,active,is_won,is_lost,created_at,updated_at) VALUES(?,?,?,1,0,0,NOW(),NOW())",$s);
 $types=[['call','Ligação','fa-phone',10],['whatsapp','WhatsApp','fa-brands fa-whatsapp',20],['email','E-mail','fa-envelope',30],['meeting','Reunião','fa-users',40],['proposal','Enviar proposta','fa-file-signature',50],['followup','Retorno','fa-clock',60]];
 foreach($types as $t)DB::exec("INSERT IGNORE INTO sales_activity_types(code,name,icon,position,active,created_at,updated_at) VALUES(?,?,?,?,1,NOW(),NOW())",$t);
 try{
  $hasOpportunityTask=DB::one("SHOW COLUMNS FROM tasks LIKE 'opportunity_id'");
  if(!$hasOpportunityTask)DB::exec("ALTER TABLE tasks ADD COLUMN opportunity_id BIGINT UNSIGNED NULL AFTER client_id, ADD INDEX idx_tasks_opportunity(opportunity_id,status)");
 }catch(Throwable){}
}

function sales_flow_stages(bool $activeOnly=true): array{
 ensure_sales_flow_tables();
 return DB::all("SELECT * FROM pipeline_stages".($activeOnly?" WHERE active=1":"")." ORDER BY position,id");
}
function sales_activity_types(bool $activeOnly=true): array{
 ensure_sales_flow_tables();
 return DB::all("SELECT * FROM sales_activity_types".($activeOnly?" WHERE active=1":"")." ORDER BY position,id");
}
function sales_flow_require_enabled(): void{
 if(!sales_flow_enabled()){http_response_code(404);exit('Fluxo comercial não está ativado.');}
}
function opportunity_scope_where(array $u,string $alias='o'): array{
 if(($u['role']??'')==='seller')return ["{$alias}.owner_user_id=?",[(int)$u['id']]];
 return ['1=1',[]];
}

function opportunity_sync_task(array $opp,string $title): void{
 if(empty($opp['next_action_at']))return;
 try{
  DB::exec("UPDATE tasks SET status='cancelled' WHERE opportunity_id=? AND status='pending'",[(int)$opp['id']]);
  DB::exec("INSERT INTO tasks(client_id,opportunity_id,assigned_user_id,type,title,due_at,status,created_at) VALUES(?, ?, ?, 'sales', ?, ?, 'pending', NOW())",[(int)$opp['client_id'],(int)$opp['id'],(int)$opp['owner_user_id'],$title,(string)$opp['next_action_at']]);
 }catch(Throwable){}
}

function opportunity_render(string $name,array $vars=[]): void{
 extract($vars,EXTR_SKIP);
 $u=Auth::user();ob_start();
 if($name==='opportunities'){
  $stages=$stages??[];$rows=$rows??[];$activityTypes=$activityTypes??[];
  $byStage=[];foreach($stages as $s)$byStage[(int)$s['id']]=[];
  foreach($rows as $r)$byStage[(int)$r['stage_id']][]=$r;
  $stageTotals=[];foreach($stages as $st){$stageTotals[(int)$st['id']]=0.0;foreach($byStage[(int)$st['id']]??[] as $or)$stageTotals[(int)$st['id']]+=(float)$or['estimated_value'];}
  ?>
  <section class="tdopp-page">
   <header class="tdopp-head">
    <div><span class="tdopp-kicker">COMERCIAL / OPORTUNIDADES</span><h1>Pipeline de vendas</h1><p>Um fluxo curto: interesse, próxima ação e fechamento. Sem burocracia para o vendedor.</p></div>
    <div class="tdopp-head-actions"><?php if(Auth::can('admin','supervisor')):?><a class="tdopp-btn" href="<?=APP_URL?>/sales-flow-settings"><i class="fa-solid fa-sliders"></i>Configurar fluxo</a><?php endif;?><button class="tdopp-btn primary" type="button" data-opp-new><i class="fa-solid fa-plus"></i>Nova oportunidade</button></div>
   </header>

   <div class="tdopp-help"><i class="fa-solid fa-bolt"></i><div><strong>Regra simples</strong><span>Toda oportunidade aberta deve ter uma próxima ação. O CRM lembra o vendedor; o vendedor só informa o próximo passo.</span></div></div>

   <div class="tdopp-kpis"><article><i class="fa-solid fa-chart-line"></i><div><strong><?=number_format(count($rows),0,',','.')?></strong><span>Oportunidades</span></div></article><article><i class="fa-solid fa-clock"></i><div><strong><?=number_format((int)($stats['due']??0),0,',','.')?></strong><span>Retornos hoje/atrasados</span></div></article><article><i class="fa-solid fa-trophy"></i><div><strong><?=number_format((int)($stats['won']??0),0,',','.')?></strong><span>Ganhas no mês</span></div></article><article><i class="fa-solid fa-sack-dollar"></i><div><strong><?=money($stats['pipeline']??0)?></strong><span>Valor em aberto</span></div></article></div>

   <div class="tdopp-board">
    <?php foreach($stages as $stage):?>
     <section class="tdopp-column"><header><div><strong><?=e($stage['name'])?></strong><small><?=money($stageTotals[(int)$stage['id']]??0)?></small></div><span><?=count($byStage[(int)$stage['id']]??[])?></span></header><div class="tdopp-column-body">
      <?php foreach($byStage[(int)$stage['id']]??[] as $opp):?>
       <article class="tdopp-card">
        <div class="tdopp-card-top"><div><strong><?=e($opp['client_name'])?></strong><small><?=e($opp['title'])?></small></div><a href="<?=APP_URL?>/opportunities/<?=$opp['id']?>" aria-label="Abrir"><i class="fa-solid fa-ellipsis-vertical"></i></a></div>
        <?php if(!empty($opp['interest'])):?><p><?=e($opp['interest'])?></p><?php endif;?>
        <div class="tdopp-next <?=$opp['next_action_at']&&strtotime($opp['next_action_at'])<time()?'late':''?>"><small>Próxima ação</small><strong><i class="fa-regular fa-calendar"></i><?=e($opp['next_action_label']??'Definir ação')?><?=!empty($opp['next_action_at'])?' · '.date('d/m H:i',strtotime($opp['next_action_at'])):''?></strong></div>
        <footer><span><?=money($opp['estimated_value'])?></span><small><?=e($opp['owner_name'])?></small></footer>
       </article>
      <?php endforeach;?>
      <?php if(empty($byStage[(int)$stage['id']])):?><div class="tdopp-empty">Nenhuma oportunidade nesta etapa.</div><?php endif;?>
     </div></section>
    <?php endforeach;?>
   </div>

   <dialog class="tdopp-modal" data-opp-modal><form method="post" action="<?=APP_URL?>/opportunities/create"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button type="button" class="tdopp-modal-close" data-opp-close>&times;</button><span class="tdopp-kicker">REGISTRO RÁPIDO</span><h2>Nova oportunidade</h2><p>Preencha só o necessário. O restante vem do cliente e da carteira.</p><label>Cliente<div class="tdopp-client-search"><input class="form-control" type="search" placeholder="Digite nome, CNPJ/CPF ou código..." autocomplete="off" data-opp-client-search data-api="<?=APP_URL?>/api/clients" required><input type="hidden" name="client_id" data-opp-client-id required><div class="tdopp-client-results" data-opp-client-results hidden></div></div></label><label>Interesse<input class="form-control" name="interest" maxlength="255" placeholder="Ex.: renovação, material, curso..."></label><div class="tdopp-form-grid"><label>Valor estimado<input class="form-control" name="estimated_value" inputmode="decimal" placeholder="0,00"></label><label>Próxima ação<select class="form-select" name="next_action_type" required><?php foreach($activityTypes as $type):?><option value="<?=e($type['code'])?>"><?=e($type['name'])?></option><?php endforeach;?></select></label></div><label>Quando<input class="form-control" type="datetime-local" name="next_action_at" required></label><button class="tdopp-btn primary wide" type="submit"><i class="fa-solid fa-check"></i>Criar oportunidade</button></form></dialog>
  </section>
  <?php
 }elseif($name==='opportunity_detail'){
  ?>
  <section class="tdopp-page"><a class="tdopp-back" href="<?=APP_URL?>/opportunities"><i class="fa-solid fa-arrow-left"></i>Voltar ao funil</a><header class="tdopp-head"><div><span class="tdopp-kicker">OPORTUNIDADE #<?=$opp['id']?></span><h1><?=e($opp['client_name'])?></h1><p><?=e($opp['title'])?></p></div><span class="tdopp-status <?=e($opp['status'])?>"><?=e(mb_strtoupper($opp['status']))?></span></header>
   <div class="tdopp-detail-grid"><section class="tdopp-panel"><h3>Resumo</h3><dl><div><dt>Interesse</dt><dd><?=e($opp['interest']?:'Não informado')?></dd></div><div><dt>Valor estimado</dt><dd><?=money($opp['estimated_value'])?></dd></div><div><dt>Responsável</dt><dd><?=e($opp['owner_name'])?></dd></div><div><dt>Etapa</dt><dd><form class="tdopp-stage-form" method="post" action="<?=APP_URL?>/opportunities/<?=$opp['id']?>/stage"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><select class="form-select" name="stage_id" onchange="this.form.submit()"><?php foreach($stages as $stage):?><option value="<?=$stage['id']?>" <?=$opp['stage_id']==$stage['id']?'selected':''?>><?=e($stage['name'])?></option><?php endforeach;?></select></form></dd></div></dl></section>
   <section class="tdopp-panel"><h3>Próxima ação</h3><form method="post" action="<?=APP_URL?>/opportunities/<?=$opp['id']?>/action"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><label>Tipo<select class="form-select" name="next_action_type"><?php foreach($activityTypes as $type):?><option value="<?=e($type['code'])?>" <?=$opp['next_action_type']===$type['code']?'selected':''?>><?=e($type['name'])?></option><?php endforeach;?></select></label><label>Quando<input class="form-control" type="datetime-local" name="next_action_at" value="<?=!empty($opp['next_action_at'])?date('Y-m-d\TH:i',strtotime($opp['next_action_at'])):''?>" required></label><label>Nota<input class="form-control" name="next_action_note" value="<?=e($opp['next_action_note']??'')?>" placeholder="Opcional"></label><button class="tdopp-btn primary" type="submit">Salvar próxima ação</button></form></section></div>
   <?php if($opp['status']==='open'):?><div class="tdopp-close-grid"><form method="post" action="<?=APP_URL?>/opportunities/<?=$opp['id']?>/close"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="status" value="won"><button class="tdopp-close won"><i class="fa-solid fa-trophy"></i><span><strong>Ganhou</strong><small>Fechar e seguir para o pedido</small></span></button></form><form method="post" action="<?=APP_URL?>/opportunities/<?=$opp['id']?>/close"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="status" value="lost"><label>Motivo da perda<input class="form-control" name="lost_reason" maxlength="180" placeholder="Ex.: preço, concorrente, adiou compra" required></label><button class="tdopp-close lost"><i class="fa-solid fa-xmark"></i><span><strong>Não converteu</strong><small>Encerrar mantendo o histórico</small></span></button></form></div><?php elseif($opp['status']==='won'):?><div class="tdopp-won-box"><i class="fa-solid fa-circle-check"></i><div><strong>Oportunidade ganha</strong><span>Agora transforme a venda em pedido sem redigitar o cliente.</span></div><a class="tdopp-btn primary" href="<?=APP_URL?>/orders/new?client_id=<?=$opp['client_id']?>&opportunity_id=<?=$opp['id']?>">Gerar pedido</a></div><?php endif;?>
   <section class="tdopp-panel"><h3>Histórico</h3><div class="tdopp-history"><?php foreach($history??[] as $h):?><div><i></i><span><strong><?=e($h['description'])?></strong><small><?=e($h['user_name'])?> · <?=date('d/m/Y H:i',strtotime($h['created_at']))?></small></span></div><?php endforeach;?></div></section>
  </section>
  <?php
 }elseif($name==='sales_flow_settings'){
  ?>
  <section class="tdopp-page"><header class="tdopp-head"><div><span class="tdopp-kicker">CONFIGURAÇÃO / COMERCIAL</span><h1>Fluxo comercial simplificado</h1><p>Você escolhe se a equipe usa o novo fluxo. Desativado, o CRM continua funcionando como hoje.</p></div></header>
   <?php if(!empty($flash)):?><div class="alert alert-<?=e($flash['type']??'info')?>"><?=e($flash['message']??'')?></div><?php endif;?>
   <section class="tdopp-setting-hero"><div><i class="fa-solid fa-toggle-on"></i><span><strong>Ativar oportunidades e funil</strong><small>Adiciona Oportunidades, Funil e próxima ação ao comercial. Não altera pedidos, clientes ou cobrança existentes.</small></span></div><form method="post" action="<?=APP_URL?>/sales-flow-settings/toggle"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="enabled" value="<?=$enabled?'0':'1'?>"><button class="tdopp-btn <?=$enabled?'danger':'primary'?>" type="submit"><?=$enabled?'Desativar fluxo':'Ativar fluxo'?></button></form></section>
   <div class="tdopp-settings-grid">
   <section class="tdopp-panel tdopp-stage-settings"><h3>Etapas do funil</h3><p class="hint">Mantenha poucas etapas. Elas aparecem da esquerda para a direita pela ordem configurada.</p><form class="tdopp-inline-form stage" method="post" action="<?=APP_URL?>/sales-flow-settings/stage"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input class="form-control" name="name" maxlength="100" placeholder="Nova etapa" required><input class="form-control position" type="number" name="position" min="1" max="999" value="60" title="Ordem"><button class="tdopp-btn primary">Adicionar</button></form><div class="tdopp-stage-setting-list"><?php foreach($stages??[] as $stage):?><form method="post" action="<?=APP_URL?>/sales-flow-settings/stage/<?=$stage['id']?>/update"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><span class="drag"><i class="fa-solid fa-grip-vertical"></i></span><input class="form-control" name="name" maxlength="100" value="<?=e($stage['name'])?>" required><input class="form-control position" type="number" name="position" min="1" max="999" value="<?=(int)$stage['position']?>" title="Ordem"><button class="tdopp-mini save" type="submit">Salvar</button><button class="tdopp-mini <?=$stage['active']?'on':'off'?>" type="submit" formaction="<?=APP_URL?>/sales-flow-settings/stage/<?=$stage['id']?>/toggle"><?=$stage['active']?'Ativa':'Inativa'?></button></form><?php endforeach;?></div></section>
   <section class="tdopp-panel"><h3>Tipos de próxima ação</h3><p class="hint">O supervisor escolhe o que aparece para o vendedor. Desative o que não fizer sentido.</p><form class="tdopp-inline-form" method="post" action="<?=APP_URL?>/sales-flow-settings/activity-type"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input class="form-control" name="name" placeholder="Nova atividade" required><button class="tdopp-btn primary">Adicionar</button></form><?php foreach($activityTypes as $type):?><div class="tdopp-setting-row"><span><i class="fa-solid fa-check-circle"></i><strong><?=e($type['name'])?></strong></span><form method="post" action="<?=APP_URL?>/sales-flow-settings/activity-type/<?=$type['id']?>/toggle"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="tdopp-mini <?=$type['active']?'on':'off'?>"><?=$type['active']?'Ativo':'Inativo'?></button></form></div><?php endforeach;?></section>
   <section class="tdopp-panel"><h3>Google Agenda</h3><p class="hint">Integração será opcional por vendedor. O CRM já guarda a preferência individual sem obrigar ninguém a conectar o Gmail.</p><div class="tdopp-google-plan"><div><i class="fa-brands fa-google"></i><span><strong>3 modos planejados</strong><small>Não usar · enviar tarefas do CRM · sincronização bidirecional</small></span></div><p>Para ativar a conexão real usaremos OAuth 2.0 do Google Calendar. Cada vendedor autoriza a própria conta e pode desconectar quando quiser.</p></div></section></div>
  </section>
  <?php
 }
 $body=ob_get_clean();layout($body,$u);
}

function register_opportunity_routes(Router $router): void{
 $router->get('/opportunities',function(){
  Auth::requireRole('admin','supervisor','seller');sales_flow_require_enabled();ensure_sales_flow_tables();$u=Auth::user();
  [$scope,$params]=opportunity_scope_where($u);
  $types=sales_activity_types();
  $labels=[];foreach($types as $t)$labels[$t['code']]=$t['name'];
  $rows=DB::all("SELECT o.*,c.name client_name,u.name owner_name,s.name stage_name FROM opportunities o JOIN clients c ON c.id=o.client_id JOIN users u ON u.id=o.owner_user_id JOIN pipeline_stages s ON s.id=o.stage_id WHERE o.status='open' AND ".$scope." ORDER BY s.position,o.next_action_at IS NULL,o.next_action_at,o.updated_at DESC",$params);
  foreach($rows as &$r)$r['next_action_label']=$labels[$r['next_action_type']]??'Definir ação';unset($r);
  $clientWhere="c.active=1";$clientParams=[];if($u['role']==='seller'){$clientWhere.=" AND c.seller_omie_code=?";$clientParams[]=(string)($u['seller_omie_code']??'');}
  $clients=DB::all("SELECT c.id,c.name,c.uf FROM clients c WHERE ".$clientWhere." ORDER BY c.name LIMIT 250",$clientParams);
  $month=date('Y-m-01');$next=date('Y-m-01',strtotime('+1 month'));
  $stats=['due'=>(int)(DB::scalar("SELECT COUNT(*) FROM opportunities o WHERE o.status='open' AND ".$scope." AND o.next_action_at IS NOT NULL AND o.next_action_at<=NOW()", $params)??0),'won'=>(int)(DB::scalar("SELECT COUNT(*) FROM opportunities o WHERE o.status='won' AND ".$scope." AND o.closed_at>=? AND o.closed_at<?",array_merge($params,[$month,$next]))??0),'pipeline'=>(float)(DB::scalar("SELECT COALESCE(SUM(o.estimated_value),0) FROM opportunities o WHERE o.status='open' AND ".$scope,$params)??0)];
  opportunity_render('opportunities',['rows'=>$rows,'stages'=>sales_flow_stages(),'activityTypes'=>$types,'clients'=>$clients,'stats'=>$stats]);
 });
 $router->post('/opportunities/create',function(){
  Auth::requireRole('admin','supervisor','seller');sales_flow_require_enabled();CSRF::require($_POST['_token']??null);ensure_sales_flow_tables();$u=Auth::user();
  $clientId=(int)($_POST['client_id']??0);$client=DB::one("SELECT * FROM clients WHERE id=?",[$clientId]);if(!$client)throw new RuntimeException('Cliente inválido.');
  if($u['role']==='seller'&&(string)$client['seller_omie_code']!==(string)($u['seller_omie_code']??'')){http_response_code(403);exit('Cliente fora da sua carteira.');}
  $stage=DB::one("SELECT * FROM pipeline_stages WHERE active=1 ORDER BY position,id LIMIT 1");if(!$stage)throw new RuntimeException('Nenhuma etapa ativa.');
  $type=(string)($_POST['next_action_type']??'');$valid=array_column(sales_activity_types(),'code');if(!in_array($type,$valid,true))throw new RuntimeException('Tipo de ação inválido.');
  $at=trim((string)($_POST['next_action_at']??''));if($at==='')throw new RuntimeException('Defina a próxima ação.');
  $interest=trim((string)($_POST['interest']??''));$value=(float)str_replace(',','.',preg_replace('/[^0-9,.-]/','',(string)($_POST['estimated_value']??'0')));
  DB::exec("INSERT INTO opportunities(client_id,owner_user_id,stage_id,title,interest,estimated_value,status,next_action_type,next_action_at,created_at,updated_at) VALUES(?,?,?,?,?,?,'open',?,?,NOW(),NOW())",[$clientId,(int)$u['id'],(int)$stage['id'],'Oportunidade · '.$client['name'],$interest,max(0,$value),$type,date('Y-m-d H:i:s',strtotime($at))]);
  $id=(int)DB::scalar("SELECT LAST_INSERT_ID()");DB::exec("INSERT INTO opportunity_history(opportunity_id,user_id,event_type,description,created_at) VALUES(?,?, 'created','Oportunidade criada',NOW())",[$id,(int)$u['id']]);
  opportunity_sync_task(['id'=>$id,'client_id'=>$clientId,'owner_user_id'=>(int)$u['id'],'next_action_at'=>date('Y-m-d H:i:s',strtotime($at))],'Oportunidade · '.$client['name']);
  redirect('/opportunities/'.$id);
 });
 $router->get('/opportunities/{id}',function($p){
  Auth::requireRole('admin','supervisor','seller');sales_flow_require_enabled();ensure_sales_flow_tables();$u=Auth::user();$id=(int)$p['id'];
  [$scope,$params]=opportunity_scope_where($u);
  $opp=DB::one("SELECT o.*,c.name client_name,u.name owner_name,s.name stage_name FROM opportunities o JOIN clients c ON c.id=o.client_id JOIN users u ON u.id=o.owner_user_id JOIN pipeline_stages s ON s.id=o.stage_id WHERE o.id=? AND ".$scope,array_merge([$id],$params));if(!$opp){http_response_code(404);exit('Oportunidade não encontrada.');}
  $history=DB::all("SELECT h.*,u.name user_name FROM opportunity_history h JOIN users u ON u.id=h.user_id WHERE h.opportunity_id=? ORDER BY h.created_at DESC,h.id DESC",[$id]);
  opportunity_render('opportunity_detail',['opp'=>$opp,'history'=>$history,'activityTypes'=>sales_activity_types(),'stages'=>sales_flow_stages()]);
 });
 $router->post('/opportunities/{id}/stage',function($p){
  Auth::requireRole('admin','supervisor','seller');sales_flow_require_enabled();CSRF::require($_POST['_token']??null);ensure_sales_flow_tables();$u=Auth::user();$id=(int)$p['id'];[$scope,$params]=opportunity_scope_where($u);$opp=DB::one("SELECT * FROM opportunities o WHERE o.id=? AND ".$scope,array_merge([$id],$params));if(!$opp){http_response_code(404);exit('Oportunidade não encontrada.');}
  $stageId=(int)($_POST['stage_id']??0);$stage=DB::one("SELECT * FROM pipeline_stages WHERE id=? AND active=1",[$stageId]);if(!$stage)throw new RuntimeException('Etapa inválida.');
  DB::exec("UPDATE opportunities SET stage_id=?,updated_at=NOW() WHERE id=?",[$stageId,$id]);
  DB::exec("INSERT INTO opportunity_history(opportunity_id,user_id,event_type,description,created_at) VALUES(?,?, 'stage',?,NOW())",[$id,(int)$u['id'],'Etapa alterada para '.$stage['name']]);
  redirect('/opportunities/'.$id);
 });
 $router->post('/opportunities/{id}/action',function($p){
  Auth::requireRole('admin','supervisor','seller');sales_flow_require_enabled();CSRF::require($_POST['_token']??null);ensure_sales_flow_tables();$u=Auth::user();$id=(int)$p['id'];[$scope,$params]=opportunity_scope_where($u);$opp=DB::one("SELECT * FROM opportunities o WHERE o.id=? AND ".$scope,array_merge([$id],$params));if(!$opp){http_response_code(404);exit('Oportunidade não encontrada.');}
  $type=(string)($_POST['next_action_type']??'');$at=trim((string)($_POST['next_action_at']??''));if($at==='')throw new RuntimeException('Informe quando será a próxima ação.');
  $label=$type;foreach(sales_activity_types() as $t)if($t['code']===$type)$label=$t['name'];
  DB::exec("UPDATE opportunities SET next_action_type=?,next_action_at=?,next_action_note=?,updated_at=NOW() WHERE id=?",[$type,date('Y-m-d H:i:s',strtotime($at)),trim((string)($_POST['next_action_note']??'')),$id]);
  DB::exec("INSERT INTO opportunity_history(opportunity_id,user_id,event_type,description,created_at) VALUES(?,?, 'action',?,NOW())",[$id,(int)$u['id'],'Próxima ação: '.$label.' em '.date('d/m/Y H:i',strtotime($at))]);
  $opp['next_action_at']=date('Y-m-d H:i:s',strtotime($at));opportunity_sync_task($opp,'Oportunidade · '.$label);
  redirect('/opportunities/'.$id);
 });
 $router->post('/opportunities/{id}/close',function($p){
  Auth::requireRole('admin','supervisor','seller');sales_flow_require_enabled();CSRF::require($_POST['_token']??null);ensure_sales_flow_tables();$u=Auth::user();$id=(int)$p['id'];[$scope,$params]=opportunity_scope_where($u);$opp=DB::one("SELECT * FROM opportunities o WHERE o.id=? AND ".$scope,array_merge([$id],$params));if(!$opp){http_response_code(404);exit('Oportunidade não encontrada.');}
  $status=(string)($_POST['status']??'');if(!in_array($status,['won','lost'],true))throw new RuntimeException('Status inválido.');$reason=trim((string)($_POST['lost_reason']??''));if($status==='lost'&&$reason==='')throw new RuntimeException('Informe o motivo da perda.');
  DB::exec("UPDATE opportunities SET status=?,lost_reason=?,closed_at=NOW(),updated_at=NOW() WHERE id=?",[$status,$status==='lost'?$reason:null,$id]);try{DB::exec("UPDATE tasks SET status='cancelled' WHERE opportunity_id=? AND status='pending'",[$id]);}catch(Throwable){}
  DB::exec("INSERT INTO opportunity_history(opportunity_id,user_id,event_type,description,created_at) VALUES(?,?, 'closed',?,NOW())",[$id,(int)$u['id'],$status==='won'?'Oportunidade marcada como ganha':'Oportunidade perdida: '.$reason]);
  redirect('/opportunities/'.$id);
 });
 $router->get('/sales-flow-settings',function(){Auth::requireRole('admin','supervisor');ensure_sales_flow_tables();$flash=$_SESSION['sales_flow_flash']??null;unset($_SESSION['sales_flow_flash']);opportunity_render('sales_flow_settings',['enabled'=>sales_flow_enabled(),'activityTypes'=>sales_activity_types(false),'stages'=>sales_flow_stages(false),'flash'=>$flash]);});
 $router->post('/sales-flow-settings/toggle',function(){Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);$enabled=!empty($_POST['enabled']);DB::exec("INSERT INTO settings(setting_key,value_json,updated_at) VALUES('sales_flow_enabled',?,NOW()) ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=NOW()",[json_encode(['enabled'=>$enabled])]);$_SESSION['sales_flow_flash']=['type'=>'success','message'=>$enabled?'Fluxo comercial ativado.':'Fluxo comercial desativado. O CRM voltou ao modo atual.'];redirect('/sales-flow-settings');});
 $router->post('/sales-flow-settings/stage',function(){
  Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);ensure_sales_flow_tables();
  $name=trim((string)($_POST['name']??''));$position=max(1,(int)($_POST['position']??60));if($name==='')throw new RuntimeException('Informe o nome da etapa.');
  $code='custom_'.substr(hash('sha256',$name.microtime(true)),0,12);
  DB::exec("INSERT INTO pipeline_stages(code,name,position,active,is_won,is_lost,created_at,updated_at) VALUES(?,?,?,1,0,0,NOW(),NOW())",[$code,$name,$position]);
  redirect('/sales-flow-settings');
 });
 $router->post('/sales-flow-settings/stage/{id}/update',function($p){
  Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);ensure_sales_flow_tables();
  $id=(int)$p['id'];$name=trim((string)($_POST['name']??''));$position=max(1,(int)($_POST['position']??0));if($name==='')throw new RuntimeException('Informe o nome da etapa.');
  DB::exec("UPDATE pipeline_stages SET name=?,position=?,updated_at=NOW() WHERE id=?",[$name,$position,$id]);redirect('/sales-flow-settings');
 });
 $router->post('/sales-flow-settings/stage/{id}/toggle',function($p){
  Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);ensure_sales_flow_tables();$id=(int)$p['id'];
  $stage=DB::one("SELECT id,active FROM pipeline_stages WHERE id=?",[$id]);if(!$stage)throw new RuntimeException('Etapa inválida.');
  if((int)$stage['active']===1){$active=(int)(DB::scalar("SELECT COUNT(*) FROM pipeline_stages WHERE active=1")??0);if($active<=1)throw new RuntimeException('O funil precisa manter pelo menos uma etapa ativa.');}
  DB::exec("UPDATE pipeline_stages SET active=IF(active=1,0,1),updated_at=NOW() WHERE id=?",[$id]);redirect('/sales-flow-settings');
 });
 $router->post('/sales-flow-settings/activity-type',function(){Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);ensure_sales_flow_tables();$name=trim((string)($_POST['name']??''));if($name==='')throw new RuntimeException('Informe o nome.');$code='custom_'.substr(hash('sha256',$name.microtime(true)),0,12);DB::exec("INSERT INTO sales_activity_types(code,name,active,position,created_at,updated_at) VALUES(?,?,1,999,NOW(),NOW())",[$code,$name]);redirect('/sales-flow-settings');});
 $router->post('/sales-flow-settings/activity-type/{id}/toggle',function($p){Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);ensure_sales_flow_tables();DB::exec("UPDATE sales_activity_types SET active=IF(active=1,0,1),updated_at=NOW() WHERE id=?",[(int)$p['id']]);redirect('/sales-flow-settings');});
}
