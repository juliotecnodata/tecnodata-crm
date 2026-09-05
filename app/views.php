<?php
function render(string $name,array $vars=[]): void{
 extract($vars,EXTR_SKIP);$u=Auth::user();ob_start();
 switch($name){
  case 'login':?>
   <div class="login-page"><div class="login-card"><div class="login-brand"><span>T</span><div><strong>Tecnodata</strong><small>CRM</small></div></div><h1>Acessar</h1><p>Carteira, pedidos, agenda e cobrança.</p><?php if(!empty($error)):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?><form method="post" action="<?=APP_URL?>/login"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><label>E-mail<span class="required-mark">*</span></label><input class="form-control" type="email" name="email" required autofocus><label>Senha</label><input class="form-control" type="password" name="password" required><button class="btn btn-primary w-100">Entrar</button></form></div></div>
  <?php break;
  case 'dashboard':
   $data=is_array($data??null)?$data:[];
   $data+=['sales'=>0.0,'debt'=>0.0,'clients'=>0,'late'=>0,'tasks'=>0,'worked'=>0,'recovered'=>0.0];
   ?>
   <div class="page-head"><div><span class="eyebrow">HOJE</span><h1><?=e(explode(' ',trim((string)$u['name']))[0]??'')?></h1><p>Somente o que precisa de atenção agora.</p></div></div>
   <?php if($u['role']==='seller'):?><div class="metric-row"><div><span>Vendas no mês</span><strong><?=money($data['sales'])?></strong></div><div><span>Clientes</span><strong><?=$data['clients']?></strong></div><div><span>Retornos</span><strong><?=$data['tasks']?></strong></div></div><div class="quick-actions"><a href="<?=APP_URL?>/orders/new"><i class="fa-solid fa-plus"></i><div><strong>Novo pedido</strong><small>Enviar para Omie</small></div></a><a href="<?=APP_URL?>/clients"><i class="fa-solid fa-users"></i><div><strong>Clientes</strong><small>Trabalhar carteira</small></div></a><a href="<?=APP_URL?>/agenda"><i class="fa-regular fa-calendar"></i><div><strong>Agenda</strong><small>Retornos</small></div></a></div>
   <?php elseif($u['role']==='collector'):?><div class="metric-row"><div><span>Saldo em cobrança</span><strong><?=money($data['debt'])?></strong></div><div><span>Trabalhados</span><strong><?=$data['worked']?></strong></div><div><span>Recuperado</span><strong><?=money($data['recovered'])?></strong></div></div><div class="quick-actions"><a href="<?=APP_URL?>/collection"><i class="fa-solid fa-hand-holding-dollar"></i><div><strong>Cobrança</strong><small>Priorizar carteira</small></div></a><a href="<?=APP_URL?>/agenda"><i class="fa-regular fa-calendar"></i><div><strong>Agenda</strong><small>Promessas</small></div></a></div>
   <?php else:?><div class="metric-row management-metrics"><div><span>Vendas no mês</span><strong><?=money($data['sales'])?></strong><small><?=number_format($data['sales_percent'],1,',','.')?>% da meta geral</small></div><div><span>Recuperado no mês</span><strong><?=money($data['recovered'])?></strong><small><?=number_format($data['collection_percent'],1,',','.')?>% da meta de cobrança</small></div><div><span>Saldo cobrança</span><strong><?=money($data['debt'])?></strong></div><div><span>Clientes</span><strong><?=$data['clients']?></strong><small><?=$data['late']?> retorno(s) atrasado(s)</small></div></div><div class="quick-actions"><a href="<?=APP_URL?>/result"><i class="fa-solid fa-chart-column"></i><div><strong>Resultados</strong><small>Equipe, metas e atingimento</small></div></a><a href="<?=APP_URL?>/orders/new"><i class="fa-solid fa-plus"></i><div><strong>Novo pedido</strong><small>Operação comercial</small></div></a><a href="<?=APP_URL?>/collection"><i class="fa-solid fa-hand-holding-dollar"></i><div><strong>Cobrança</strong><small>Carteira financeira</small></div></a></div><?php endif;?>
  <?php break;
  case 'clients':?>
   <div class="page-head"><div><span class="eyebrow">COMERCIAL</span><h1>Clientes</h1><p>Cadastro, consulta e manutenção da carteira sincronizada com a Omie.</p></div><div class="page-head-actions"><?php if(Auth::can('admin','supervisor')):?><form method="post" action="<?=APP_URL?>/clients/test-create"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="btn btn-outline-secondary" type="submit" data-confirm="Criar um cliente fictício somente no CRM para testar a integração com a Omie?"><i class="fa-solid fa-flask"></i>Cliente de teste</button></form><?php endif;?><a class="btn btn-outline-secondary" href="<?=APP_URL?>/orders/new"><i class="fa-solid fa-receipt"></i>Novo pedido</a><a class="btn btn-primary" href="<?=APP_URL?>/clients/new"><i class="fa-solid fa-user-plus"></i>Novo cliente</a></div></div>
   <?php if($flash):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>
   <form class="filter-bar" method="get"><input class="form-control" type="search" name="q" value="<?=e($q)?>" placeholder="Nome, documento ou cidade"><button class="btn btn-dark">Buscar</button></form>
   <div class="table-card"><table class="table align-middle"><thead><tr><th>Cliente</th><th>Local</th><th>Momento</th><th>Última compra</th><th>Receita 12m</th><th class="text-end">Ações</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><strong><?=e($r['name'])?></strong><small><?=e($r['document']??'')?></small></td><td><?=e(trim(($r['city']??'').' / '.($r['uf']??''),' /'))?></td><td><span class="cycle cycle-<?=e($r['cycle']['status'])?>"><?=e($r['cycle']['label'])?></span></td><td><?=brdate($r['last_purchase_at']??null)?></td><td><?=money($r['revenue_12m']??0)?></td><td class="text-end"><div class="table-actions"><a class="btn btn-sm btn-light" href="<?=APP_URL?>/clients/<?=$r['id']?>" title="Ver"><i class="fa-regular fa-eye"></i></a><a class="btn btn-sm btn-light" href="<?=APP_URL?>/clients/<?=$r['id']?>/edit" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a><?php if(Auth::can('admin','supervisor')):?><form method="post" action="<?=APP_URL?>/clients/<?=$r['id']?>/delete-local" class="d-inline"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="btn btn-sm btn-light" type="submit" title="Excluir local" data-confirm="Excluir somente do CRM local? Nenhuma chamada será feita à Omie."><i class="fa-solid fa-database-circle-xmark"></i></button></form><form method="post" action="<?=APP_URL?>/clients/<?=$r['id']?>/delete" class="d-inline"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="btn btn-sm btn-light danger" type="submit" title="Excluir CRM + Omie" data-confirm="Excluir este cliente na Omie e também no CRM?"><i class="fa-regular fa-trash-can"></i></button></form><?php endif;?></div></td></tr><?php endforeach;?></tbody></table></div>
  <?php break;
  case 'client_new':$editClient=$editClient??null;$editError=$editError??null;?>
   <div class="page-head client-editor-head">
    <div><span class="eyebrow"><?=$editClient?'CLIENTES / EDITAR':'CLIENTES / NOVO'?></span><h1><?=$editClient?'Editar cliente':'Cadastrar cliente'?></h1><p><?=$editClient?'Atualize somente o necessário. As alterações confirmadas seguem para a Omie.':'Cadastro direto, com consulta automática por CNPJ e CEP.'?></p></div>
    <a class="btn btn-outline-secondary" href="<?=$editClient?APP_URL.'/clients/'.(int)$editClient['id']:APP_URL.'/clients'?>"><i class="fa-solid fa-arrow-left"></i>Voltar</a>
   </div>

   <?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
   <?php if($editError):?><div class="alert alert-danger"><strong>Omie:</strong> <?=e($editError)?></div><?php endif;?>
   <?php if($createError):?><div class="alert alert-danger"><strong>Omie:</strong> <?=e($createError)?></div><?php endif;?>
   <?php if($createSuccess):?><div class="alert alert-success client-created-alert"><i class="fa-solid fa-circle-check"></i><div><strong>Cliente criado com sucesso.</strong><span><?=e((string)($createSuccess['client']['name']??''))?> • Omie <?=e((string)($createSuccess['client']['omie_code']??''))?></span></div><a class="btn btn-sm btn-outline-secondary" href="<?=APP_URL?>/clients/<?=e((string)($createSuccess['client']['id']??''))?>">Abrir cliente</a></div><?php endif;?>

   <form method="post" action="<?=$editClient?APP_URL.'/clients/'.(int)$editClient['id'].'/update':APP_URL.'/clients/save-local'?>" id="clientCreateForm" class="client-editor">
    <input type="hidden" name="_token" value="<?=CSRF::token()?>">

    <fieldset class="client-editor-card client-editor-group client-group-main">
     <legend class="client-editor-legend">
      <span class="legend-icon legend-blue"><i class="fa-solid fa-building"></i></span>
      <span class="legend-copy"><strong>Dados principais</strong><small>Identificação e contato do cliente</small></span>
      <?php if($editClient):?><span class="client-omie-code">Omie #<?=e((string)$editClient['omie_code'])?></span><?php endif;?>
     </legend>
     <div class="client-fields-grid">
      <div class="field span-4">
       <label>CPF / CNPJ<span class="required-mark">*</span></label>
       <div class="lookup-field"><input class="form-control" name="document" data-document value="<?=e((string)($old['document']??''))?>" inputmode="numeric" required><button class="lookup-action" type="button" data-cnpj-lookup title="Buscar CNPJ"><i class="fa-solid fa-magnifying-glass"></i></button></div>
       <small class="field-hint" data-cnpj-status>CNPJ completo busca e preenche os dados disponíveis.</small>
      </div>
      <div class="field span-5"><label>Razão social / Nome<span class="required-mark">*</span></label><input class="form-control" name="legal_name" value="<?=e((string)($old['legal_name']??''))?>" autocomplete="organization" required></div>
      <div class="field span-3"><label>Nome fantasia<span class="required-mark">*</span></label><input class="form-control" name="trade_name" value="<?=e((string)($old['trade_name']??''))?>" required></div>

      <div class="field span-5"><label>E-mail<span class="required-mark">*</span></label><input class="form-control" type="email" name="email" value="<?=e((string)($old['email']??''))?>" autocomplete="email" required></div>
      <div class="field span-5"><label>Nome do contato<span class="required-mark">*</span></label><input class="form-control" name="contact_name" value="<?=e((string)($old['contact_name']??''))?>" autocomplete="name" required></div>
      <div class="field span-2 client-phone-inline">
       <label>Telefone<span class="required-mark">*</span></label>
       <div class="client-phone-compact">
        <input class="form-control ddd" name="phone_ddd" data-phone-ddd value="<?=e((string)($old['phone_ddd']??''))?>" inputmode="numeric" maxlength="2" placeholder="DDD" required>
        <input class="form-control number" name="phone_number" data-phone-number value="<?=e((string)($old['phone_number']??''))?>" inputmode="numeric" maxlength="9" placeholder="Número" required>
       </div>
      </div>
     </div>
    </fieldset>

    <fieldset class="client-editor-card client-editor-group client-group-address">
     <legend class="client-editor-legend">
      <span class="legend-icon legend-green"><i class="fa-solid fa-location-dot"></i></span>
      <span class="legend-copy"><strong>Endereço</strong><small>Dados usados no cadastro e no faturamento</small></span>
     </legend>
     <div class="client-fields-grid">
      <div class="field span-2">
       <label>CEP<span class="required-mark">*</span></label>
       <div class="lookup-field"><input class="form-control" name="zip_code" data-cep value="<?=e((string)($old['zip_code']??''))?>" inputmode="numeric" autocomplete="postal-code" required><button class="lookup-action" type="button" data-cep-lookup title="Buscar CEP"><i class="fa-solid fa-location-crosshairs"></i></button></div>
       <small class="field-hint" data-cep-status>Busca automática ao completar.</small>
      </div>
      <div class="field span-5"><label>Endereço<span class="required-mark">*</span></label><input class="form-control" name="address" value="<?=e((string)($old['address']??''))?>" autocomplete="address-line1" required></div>
      <div class="field span-2"><label>Número<span class="required-mark">*</span></label><input class="form-control" name="address_number" value="<?=e((string)($old['address_number']??''))?>" required></div>
      <div class="field span-3"><label>Complemento</label><input class="form-control" name="complement" value="<?=e((string)($old['complement']??''))?>" placeholder="Opcional"></div>

      <div class="field span-4"><label>Bairro<span class="required-mark">*</span></label><input class="form-control" name="neighborhood" value="<?=e((string)($old['neighborhood']??''))?>" required></div>
      <div class="field span-6"><label>Cidade<span class="required-mark">*</span></label><input class="form-control" name="city" value="<?=e((string)($old['city']??''))?>" autocomplete="address-level2" required></div>
      <div class="field span-2"><label>UF<span class="required-mark">*</span></label><input class="form-control text-uppercase" name="uf" maxlength="2" value="<?=e((string)($old['uf']??''))?>" autocomplete="address-level1" required></div>
     </div>
    </fieldset>

    <fieldset class="client-editor-card client-editor-group client-group-commercial">
     <legend class="client-editor-legend">
      <span class="legend-icon legend-orange"><i class="fa-solid fa-user-tie"></i></span>
      <span class="legend-copy"><strong>Organização comercial</strong><small>Responsável, classificação e observações</small></span>
     </legend>
     <div class="client-fields-grid">
      <div class="field span-4">
       <label>Vendedor responsável</label>
       <?php if(Auth::can('admin','supervisor')):?><select class="form-select" name="seller_omie_code"><option value="">Selecione...</option><?php foreach($sellers as $seller):?><option value="<?=e($seller['omie_code'])?>" <?=($old['seller_omie_code']??'')===$seller['omie_code']?'selected':''?>><?=e($seller['name'])?></option><?php endforeach;?></select>
       <?php else:?><div class="client-fixed-seller"><?=e(Auth::user()['name']??'Vendedor')?></div><?php endif;?>
      </div>
      <div class="field span-8">
       <label>Tags<span class="required-mark">*</span></label>
       <div class="client-fixed-tags">
        <span><i class="fa-solid fa-user-check"></i>CLIENTE</span>
        <span><i class="fa-solid fa-car-side"></i>CFC</span>
       </div>
       <input type="hidden" name="tags" value="CLIENTE, CFC">
       <small class="field-hint">As tags CLIENTE e CFC serão enviadas automaticamente para a Omie.</small>
      </div>
      <div class="field span-12"><label>Observações</label><textarea class="form-control" name="notes" rows="4" placeholder="Informações úteis para o atendimento."><?=e((string)($old['notes']??''))?></textarea></div>
     </div>
    </fieldset>

    <?php if(!$editClient&&$preview):?>
    <fieldset class="client-editor-card client-editor-group client-preview-inline client-group-preview">
     <legend class="client-editor-legend">
      <span class="legend-icon legend-yellow"><i class="fa-solid fa-code"></i></span>
      <span class="legend-copy"><strong>Prévia da integração</strong><small>Payload validado, ainda não enviado</small></span>
      <span class="client-preview-badge">VALIDADO</span>
     </legend>
     <div class="client-preview-summary-inline"><div><span>Cliente</span><strong><?=e($preview['summary']['name'])?></strong></div><div><span>Documento</span><strong><?=e($preview['summary']['document'])?></strong></div><div><span>Vendedor</span><strong><?=e($preview['summary']['seller'])?></strong></div></div>
     <details class="client-payload-details"><summary>Ver payload técnico</summary><pre><?=e(json_encode($preview['payload'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?></pre></details>
    </fieldset>
    <?php endif;?>

    <div class="client-editor-actions">
     <div class="client-editor-actions-info"><i class="fa-solid fa-database"></i><span><?=$editClient?'Salvar atualiza os dados deste cliente.':'Primeiro salvamos no CRM. Depois você verifica e integra com a Omie.'?></span></div>
     <div class="client-editor-actions-buttons">
      <?php if($editClient):?>
       <?php if(Auth::can('admin','supervisor')):?><button class="btn btn-outline-danger" type="submit" formaction="<?=APP_URL?>/clients/<?=(int)$editClient['id']?>/delete" formnovalidate data-confirm="Excluir este cliente? Se ele já estiver integrado, o sistema excluirá primeiro na Omie e depois no CRM."><i class="fa-regular fa-trash-can"></i>Excluir cliente</button><?php endif;?>
       <button class="btn btn-primary" type="submit" data-confirm="Salvar estas alterações também na Omie?"><i class="fa-solid fa-check"></i>Salvar alterações</button>
      <?php else:?>
       <button class="btn btn-primary" type="submit" formaction="<?=APP_URL?>/clients/save-local"><i class="fa-solid fa-floppy-disk"></i>Salvar cliente</button>
      <?php endif;?>
     </div>
    </div>
   </form>
  <?php break;

  case 'client':
   $isLocal=str_starts_with((string)$client['omie_code'],'LOCAL-');
   $clientRaw=json_decode((string)($client['raw_json']??''),true);
   $omieStatus=is_array($clientRaw)?(string)($clientRaw['omie_status']??''):'';
   $pendingOmie=$isLocal||in_array($omieStatus,['pending','pending_update'],true);
   ?>
   <div class="page-head">
    <div><a class="back" href="<?=APP_URL?>/clients">← Clientes</a><h1><?=e($client['name'])?></h1><p><?=e(trim(($client['city']??'').' / '.($client['uf']??''),' /'))?> • <?=e($client['document']??'')?> • <?=$isLocal?'Somente local':'Omie '.e($client['omie_code'])?></p></div>
    <div class="page-head-actions"><a class="btn btn-outline-secondary" href="<?=APP_URL?>/clients/<?=$client['id']?>/edit"><i class="fa-regular fa-pen-to-square"></i>Editar</a><a class="btn btn-primary" href="<?=APP_URL?>/clients/new"><i class="fa-solid fa-user-plus"></i>Novo cadastro</a><?php if(!$isLocal):?><a class="btn btn-outline-secondary" href="<?=APP_URL?>/orders/new?client_id=<?=$client['id']?>"><i class="fa-solid fa-plus"></i>Novo pedido</a><?php endif;?><?php if(Auth::can('admin','supervisor')):?><form method="post" action="<?=APP_URL?>/clients/<?=$client['id']?>/delete-local" class="d-inline"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="btn btn-outline-secondary" type="submit" data-confirm="Excluir somente do CRM local? Nenhuma chamada será feita à Omie."><i class="fa-solid fa-database-circle-xmark"></i>Excluir local</button></form><form method="post" action="<?=APP_URL?>/clients/<?=$client['id']?>/delete"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="btn btn-outline-danger" type="submit" data-confirm="Excluir este cliente? Se ele já estiver integrado, o sistema excluirá primeiro na Omie e depois no CRM."><i class="fa-regular fa-trash-can"></i>Excluir cliente</button></form><?php endif;?></div>
   </div>
   <?php if($flash):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>
   <?php if($pendingOmie):?>
    <div class="client-omie-pending">
     <div class="client-omie-pending-icon"><i class="fa-solid fa-arrows-rotate"></i></div>
     <div>
      <strong><?=$isLocal?'Cliente salvo localmente':'Alterações salvas localmente'?></strong>
      <span><?=$isLocal?'Agora o CRM verificará o CPF/CNPJ na Omie. Se já existir, apenas vincula; se não existir, cria sem duplicidade.':'As alterações estão somente no CRM. Clique em “Sincronizar Omie” para enviar a versão local ao cadastro Omie já vinculado.'?></span>
     </div>
     <form method="post" action="<?=APP_URL?>/clients/<?=$client['id']?>/omie-sync"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="btn btn-primary" data-confirm="<?=$isLocal?'Verificar este CPF/CNPJ na Omie e integrar somente se necessário?':'Sincronizar agora as alterações locais com a Omie?'?>"><i class="fa-solid fa-arrows-rotate"></i>Sincronizar Omie</button></form>
    </div>
   <?php else:?>
    <div class="client-omie-linked"><i class="fa-solid fa-circle-check"></i><div><strong>Sincronizado com a Omie</strong><span>Cadastro vinculado ao código Omie <?=e((string)$client['omie_code'])?>. Não há alterações locais pendentes.</span></div></div>
   <?php endif;?>

   <div class="snapshot"><div><span>Momento</span><strong><span class="cycle cycle-<?=e($cycle['status'])?>"><?=e($cycle['label'])?></span></strong></div><div><span>Receita 12m</span><strong><?=money($client['revenue_12m']??0)?></strong></div><div><span>Última compra</span><strong><?=brdate($client['last_purchase_at']??null)?></strong></div><div><span>Pedidos 12m</span><strong><?=(int)($client['orders_12m']??0)?></strong></div></div>

   <div class="client-detail-grid">
    <section class="panel client-detail-card">
     <div class="client-section-title"><div class="client-section-icon"><i class="fa-solid fa-building"></i></div><div><span>Dados do cliente</span><small>Cadastro sincronizado com a Omie.</small></div></div>
     <div class="client-data-grid">
      <div><span>Razão social / Nome</span><strong><?=e($formData['legal_name']?:'—')?></strong></div>
      <div><span>Nome fantasia</span><strong><?=e($formData['trade_name']?:'—')?></strong></div>
      <div><span>CPF / CNPJ</span><strong><?=e($formData['document']?:'—')?></strong></div>
      <div><span>E-mail</span><strong><?=e($formData['email']?:'—')?></strong></div>
      <div><span>Telefone</span><strong><?=e(trim(($formData['phone_ddd']?:'').' '.($formData['phone_number']?:''))?:'—')?></strong></div>
      <div><span>Vendedor</span><strong><?=e($sellerName?:'—')?></strong></div>
      <div class="wide"><span>Endereço</span><strong><?=e(trim(($formData['address']??'').' '.($formData['address_number']??'').(($formData['complement']??'')?' • '.$formData['complement']:''))?:'—')?></strong><small><?=e(trim(($formData['neighborhood']??'').' • '.($formData['city']??'').' / '.($formData['uf']??''),' •/'))?><?=($formData['zip_code']??'')?' • CEP '.e($formData['zip_code']):''?></small></div>
      <div class="wide"><span>Tags</span><div class="client-tags-view"><?php foreach(array_filter(array_map('trim',explode(',',(string)($formData['tags']??'')))) as $tag):?><b><?=e($tag)?></b><?php endforeach;?><?php if(empty(trim((string)($formData['tags']??'')))):?><strong>—</strong><?php endif;?></div></div>
      <div class="wide"><span>Observações</span><strong class="normal-weight"><?=nl2br(e($formData['notes']?:'—'))?></strong></div>
     </div>
    </section>

    <section class="panel"><h2>Registrar contato</h2><form method="post" action="<?=APP_URL?>/clients/<?=$client['id']?>/activity"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><label>Canal</label><select class="form-select" name="channel"><option value="phone">Ligação</option><option value="whatsapp">WhatsApp</option><option value="email">E-mail</option></select><label>Resultado</label><select class="form-select" name="result"><option value="contact">Falou</option><option value="interested">Interessado</option><option value="agreement">Venda encaminhada</option><option value="no_answer">Não atendeu</option></select><label>Próximo retorno</label><input class="form-control" type="datetime-local" name="next_at"><label>Anotação</label><textarea class="form-control" name="notes" rows="4"></textarea><button class="btn btn-primary w-100 mt-2">Salvar contato</button></form></section>
   </div>

   <div class="management-columns mt-3">
    <div class="panel"><h2>Últimos contatos</h2><div class="timeline"><?php foreach($activities as $a):?><div><strong><?=e($a['result'])?></strong><small><?=e($a['user_name'])?> • <?=date('d/m/Y H:i',strtotime($a['created_at']))?></small><?php if($a['notes']):?><p><?=nl2br(e($a['notes']))?></p><?php endif;?></div><?php endforeach;?><?php if(!$activities):?><div class="empty-state-small">Nenhum contato registrado.</div><?php endif;?></div></div>
    <div class="panel"><h2>Últimos pedidos</h2><div class="simple-list"><?php foreach($orders as $o):?><div><span><strong><?=brdate($o['order_date'])?></strong><small><?=e($o['number']??$o['omie_code'])?></small></span><strong><?=money($o['total'])?></strong></div><?php endforeach;?><?php if(!$orders):?><div class="empty-state-small">Nenhum pedido encontrado.</div><?php endif;?></div></div>
   </div>
  <?php break;
  case 'orders':$success=$_SESSION['success']??null;unset($_SESSION['success']);?>
   <div class="page-head"><div><span class="eyebrow">COMERCIAL</span><h1>Pedidos</h1><p>Pedidos sincronizados da Omie.</p></div><a class="btn btn-primary" href="<?=APP_URL?>/orders/new"><i class="fa-solid fa-plus"></i>Novo pedido</a></div><?php if($success):?><div class="alert alert-success"><?=e($success)?></div><?php endif;?><div class="table-card"><table class="table"><thead><tr><th>Pedido</th><th>Cliente</th><th>Data</th><th>Etapa</th><th>Status</th><th class="text-end">Valor</th></tr></thead><tbody><?php foreach($orders as $o):?><tr><td><strong><?=e($o['number']??'—')?></strong><small><?=e($o['omie_code'])?></small></td><td><?=e($o['client_name']??$o['client_omie_code'])?></td><td><?=brdate($o['order_date'])?></td><td><?=e($o['stage_code']??'—')?></td><td><?=e($o['status']??'—')?></td><td class="text-end"><strong><?=money($o['total'])?></strong></td></tr><?php endforeach;?></tbody></table></div>
  <?php break;
  case 'services':?>
   <div class="page-head"><div><span class="eyebrow">SERVIÇOS • <?=e($month)?></span><h1>Ordens de serviço</h1><p>Serviços sincronizados da Omie e considerados no resultado comercial quando vinculados ao vendedor.</p></div><form method="get"><input class="form-control" type="month" name="month" value="<?=e($month)?>" onchange="this.form.submit()"></form></div>
   <div class="metric-row service-metrics"><div><span>Total válido no mês</span><strong><?=money($total)?></strong></div><div><span>Ordens</span><strong><?=count($rows)?></strong></div></div>
   <div class="table-card"><table class="table"><thead><tr><th>OS</th><th>Cliente</th><th>Vendedor</th><th>Data</th><th>Status</th><th class="text-end">Valor</th></tr></thead><tbody><?php foreach($rows as $row):?><tr><td><strong><?=e($row['omie_code'])?></strong></td><td><?=e($row['client_name']??$row['client_omie_code']??'—')?></td><td><?=e($row['seller_name']??$row['seller_omie_code']??'—')?></td><td><?=brdate($row['service_date'])?></td><td><?=e($row['status']??'—')?></td><td class="text-end"><strong><?=money($row['total'])?></strong></td></tr><?php endforeach;?></tbody></table></div>
  <?php break;
  case 'order_new':$error=$_SESSION['error']??null;$preview=$_SESSION['preview']??null;$old=$_SESSION['old']??[];unset($_SESSION['error'],$_SESSION['preview'],$_SESSION['old']);$d=$ready['defaults'];?>
   <div class="page-head"><div><span class="eyebrow">PEDIDO DE VENDA</span><h1>Novo pedido</h1><p>Fluxo inspirado no Omie: cabeçalho operacional fixo e conteúdo organizado por abas horizontais.</p></div><a class="btn btn-outline-secondary" href="<?=APP_URL?>/orders">Pedidos</a></div>
   <?php if(!$ready['ok']):?><div class="alert alert-warning">Configuração incompleta: <?=e(implode(', ',$ready['missing']))?>.</div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>

   <form method="post" action="<?=APP_URL?>/orders" id="orderForm" class="omie-order-form">
    <input type="hidden" name="_token" value="<?=CSRF::token()?>">
    <input type="hidden" name="request_token" value="<?=e((string)($old['request_token']??(date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,8)))))?>">
    <input type="hidden" name="client_id" id="clientId" value="<?=e((string)($old['client_id']??$prefill))?>">
    <input type="hidden" name="items_json" id="itemsJson" value="<?=e((string)($old['items_json']??'[]'))?>">

    <section class="panel omie-order-header">
     <div class="omie-order-header-grid">
      <div class="omie-client-block">
       <div class="omie-avatar"><i class="fa-solid fa-building"></i></div>
       <div class="omie-client-main">
        <label>Cliente</label>
        <div class="search-box"><input class="form-control" id="clientSearch" placeholder="Nome, documento ou código"><div class="search-results" id="clientResults"></div></div>
        <div id="clientSelected" class="selected-box"></div>
       </div>
      </div>
      <div>
       <label>Previsão de faturamento</label>
       <input class="form-control" type="date" name="forecast_date" min="<?=date('Y-m-d')?>" value="<?=e((string)($old['forecast_date']??date('Y-m-d')))?>">
      </div>
     </div>

     <div class="omie-order-kpis">
      <div><span>Total mercadorias</span><strong id="grandTotal">R$ 0,00</strong></div>
      <div><span>Desconto</span><strong id="discountTotal">R$ 0,00</strong></div>
      <div><span>Total financeiro</span><strong id="financialTotal">R$ 0,00</strong></div>
      <div><span>Total fiscal</span><strong id="fiscalTotal">R$ 0,00</strong></div>
      <div><span>Valor total do pedido</span><strong id="orderGrandTotal">R$ 0,00</strong></div>
     </div>

     <div class="omie-order-header-fields">
      <div>
       <label>Vendedor</label>
       <?php if(Auth::can('admin','supervisor')):?><select class="form-select" name="seller_omie_code" required><option value="">Selecione</option><?php foreach($sellers as $s):?><option value="<?=e($s['omie_code'])?>" <?=($old['seller_omie_code']??'')===$s['omie_code']?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select><?php else:?><div class="omie-readonly-field"><?=e(Auth::user()['name']??'Vendedor')?></div><?php endif;?>
      </div>
      <div>
       <label>Número de parcelas / condição</label>
       <select class="form-select" name="payment_term" required><option value="">Selecione</option><?php foreach($terms as $t):?><option value="<?=e($t['code'])?>" <?=($old['payment_term']??$d['payment_term']??'')===$t['code']?'selected':''?>><?=e($t['description'])?><?=$t['days_list']?' • '.e($t['days_list']):''?></option><?php endforeach;?></select>
      </div>
      <div>
       <label>Cenário fiscal</label>
       <select class="form-select" name="tax_scenario"><option value="">Padrão Omie</option><?php foreach($taxes as $r):?><option value="<?=e($r['omie_code'])?>" <?=($old['tax_scenario']??$d['tax_scenario']??'')===$r['omie_code']?'selected':''?>><?=e($r['name'])?></option><?php endforeach;?></select>
      </div>
     </div>
    </section>

    <section class="panel omie-order-body" data-order-tabs>
     <div class="omie-main-tabs" role="tablist" aria-label="Seções do pedido">
      <button type="button" class="active" data-order-tab="items">Itens da Venda</button>
      <button type="button" data-order-tab="departments">Departamentos</button>
      <button type="button" data-order-tab="freight">Frete e Outras Despesas</button>
      <button type="button" data-order-tab="additional">Informações Adicionais</button>
      <button type="button" data-order-tab="installments">Parcelas</button>
      <button type="button" data-order-tab="notes">Observações</button>
      <button type="button" data-order-tab="email">E-mail para o Cliente</button>
     </div>

     <div class="omie-tab-content">
      <section class="order-tab-panel active" data-order-panel="items">
       <div class="omie-tab-toolbar">
        <div>
         <label>Tipo de pedido</label>
         <select class="form-select" id="orderProfile"><?php foreach($profiles as $p):?><option value="<?=e($p['code'])?>" data-no-stock="<?=e($p['default_no_stock'])?>" data-no-finance="<?=e($p['default_no_finance'])?>" data-no-total="<?=e($p['default_no_total'])?>" data-reserve="<?=e($p['default_reserve_stock'])?>"><?=e($p['name'])?></option><?php endforeach;?></select>
        </div>
        <div class="omie-product-search">
         <label>Novo item</label>
         <div class="search-box"><input class="form-control" id="productSearch" placeholder="Digite produto, SKU ou código"><div class="search-results" id="productResults"></div></div>
        </div>
       </div>
       <div id="orderItems"></div>
      </section>

      <section class="order-tab-panel" data-order-panel="departments">
       <div class="omie-empty-tab"><i class="fa-solid fa-diagram-project"></i><strong>Departamentos</strong><span>A distribuição por departamentos ainda não faz parte do fluxo do CRM. A aba foi mantida para preservar a organização visual do pedido.</span></div>
      </section>

      <section class="order-tab-panel" data-order-panel="freight">
       <div class="omie-tab-grid freight-grid">
        <div><label>Transportadora</label><input class="form-control" name="carrier_code" value="<?=e((string)($old['carrier_code']??''))?>"></div>
        <div><label>Tipo do frete</label><select class="form-select" name="freight_mode"><?php foreach(['9'=>'Sem frete','0'=>'CIF • remetente','1'=>'FOB • destinatário','2'=>'Terceiros','3'=>'Próprio • remetente','4'=>'Próprio • destinatário'] as $k=>$v):?><option value="<?=$k?>" <?=($old['freight_mode']??$d['freight_mode']??'9')===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select></div>
        <div><label>Placa do veículo</label><input class="form-control" name="plate" value="<?=e((string)($old['plate']??''))?>"></div>
        <div><label>UF</label><input class="form-control" name="plate_state" maxlength="2" value="<?=e((string)($old['plate_state']??''))?>"></div>
        <div><label>RNTRC (ANTT)</label><input class="form-control" name="rntrc" value="<?=e((string)($old['rntrc']??''))?>"></div>
        <div><label>Quantidade de volumes</label><input class="form-control" type="number" name="volumes" value="<?=e((string)($old['volumes']??''))?>"></div>
        <div><label>Espécie dos volumes</label><input class="form-control" name="volume_type" value="<?=e((string)($old['volume_type']??''))?>"></div>
        <div><label>Marca dos volumes</label><input class="form-control" name="volume_brand" value="<?=e((string)($old['volume_brand']??''))?>"></div>
        <div><label>Numeração dos volumes</label><input class="form-control" name="volume_numbering" value="<?=e((string)($old['volume_numbering']??''))?>"></div>
        <div><label>Peso líquido (kg)</label><input class="form-control" name="net_weight" inputmode="decimal" value="<?=e((string)($old['net_weight']??''))?>"></div>
        <div><label>Peso bruto (kg)</label><input class="form-control" name="gross_weight" inputmode="decimal" value="<?=e((string)($old['gross_weight']??''))?>"></div>
        <div><label>Valor do frete</label><input class="form-control" name="freight_value" inputmode="decimal" value="<?=e((string)($old['freight_value']??''))?>"></div>
        <div><label>Valor do seguro</label><input class="form-control" name="insurance_value" inputmode="decimal" value="<?=e((string)($old['insurance_value']??''))?>"></div>
        <div><label>Outras despesas acessórias</label><input class="form-control" name="other_expenses" inputmode="decimal" value="<?=e((string)($old['other_expenses']??''))?>"></div>
        <div><label>Previsão de entrega</label><input class="form-control" type="date" name="delivery_date" value="<?=e((string)($old['delivery_date']??''))?>"></div>
        <div><label>Código de rastreio</label><input class="form-control" name="tracking_code" value="<?=e((string)($old['tracking_code']??''))?>"></div>
       </div>
       <label class="check mt-3"><input type="checkbox" name="own_vehicle" value="1" <?=!empty($old['own_vehicle'])?'checked':''?>> O transporte será realizado com veículo próprio</label>
       <div class="freight-api-placeholder"><i class="fa-solid fa-truck-fast"></i><div><strong>Cotação de frete</strong><span>Aqui entraremos depois com a API usando endereço do cliente, peso, valor da nota e volumes.</span></div><button type="button" class="btn btn-outline-secondary" disabled>Calcular frete</button></div>
      </section>

      <section class="order-tab-panel" data-order-panel="additional">
       <div class="omie-tab-grid additional-grid">
        <div><label>Categoria</label><select class="form-select" name="category" required><?php foreach($categories as $r):?><option value="<?=e($r['code'])?>" <?=($old['category']??$d['category']??'')===$r['code']?'selected':''?>><?=e($r['description'])?></option><?php endforeach;?></select></div>
        <div><label>Conta corrente</label><select class="form-select" name="account" required><?php foreach($accounts as $r):?><option value="<?=e($r['omie_code'])?>" <?=($old['account']??$d['account']??'')===$r['omie_code']?'selected':''?>><?=e($r['name'])?></option><?php endforeach;?></select></div>
        <div><label>Etapa</label><select class="form-select" name="stage" required><?php foreach($stages as $r):?><option value="<?=e($r['code'])?>" <?=($old['stage']??$d['stage']??'')===$r['code']?'selected':''?>><?=e($r['code'].' • '.$r['name'])?></option><?php endforeach;?></select></div>
        <div><label>Nº do pedido do cliente</label><input class="form-control" name="customer_order" value="<?=e((string)($old['customer_order']??''))?>"></div>
        <div><label>Nº do contrato de venda</label><input class="form-control" name="contract" value="<?=e((string)($old['contract']??''))?>"></div>
        <div><label>Contato</label><input class="form-control" name="contact" value="<?=e((string)($old['contact']??''))?>"></div>
        <div><label>Tipo de documento</label><select class="form-select" name="document_type"><option value="">Padrão Omie</option><?php foreach($documents as $r):?><option value="<?=e($r['code'])?>" <?=($old['document_type']??$d['document_type']??'')===$r['code']?'selected':''?>><?=e($r['description'])?></option><?php endforeach;?></select></div>
        <div><label>Local de estoque</label><select class="form-select" name="stock_location"><option value="">Padrão Omie</option><?php foreach($stocks as $r):?><option value="<?=e($r['omie_code'])?>" <?=($old['stock_location']??$d['stock_location']??'')===$r['omie_code']?'selected':''?>><?=e($r['name'])?></option><?php endforeach;?></select></div>
       </div>
       <label>Dados adicionais para a Nota Fiscal</label><textarea class="form-control" name="additional_nf" rows="4"><?=e((string)($old['additional_nf']??''))?></textarea>
       <input type="hidden" name="consumer_final" value="N">
       <label class="check mt-3"><input type="checkbox" name="consumer_final" value="S" <?=($old['consumer_final']??$d['consumer_final']??'S')==='S'?'checked':''?>> Nota Fiscal para Consumidor Final</label>
      </section>

      <section class="order-tab-panel" data-order-panel="installments">
       <div class="omie-tab-grid installments-grid">
        <div><label>Condição de pagamento</label><div class="omie-readonly-field" data-payment-summary>Definida no cabeçalho do pedido.</div></div>
        <div><label>Meio de pagamento</label><select class="form-select" name="payment_method"><option value="">Padrão Omie</option><?php foreach($methods as $m):?><option value="<?=e($m['code'])?>" <?=($old['payment_method']??$d['payment_method']??'')===$m['code']?'selected':''?>><?=e($m['description'])?></option><?php endforeach;?></select></div>
       </div>
       <div class="omie-empty-tab compact"><i class="fa-solid fa-calendar-days"></i><strong>Parcelamento</strong><span>A condição vem da Omie. Quando habilitarmos condição manual, as parcelas serão exibidas aqui.</span></div>
      </section>

      <section class="order-tab-panel" data-order-panel="notes">
       <label>Observação interna da venda</label><textarea class="form-control" name="notes" rows="7"><?=e((string)($old['notes']??''))?></textarea>
      </section>

      <section class="order-tab-panel" data-order-panel="email">
       <div class="omie-email-panel">
        <label>Utilizar os seguintes endereços de e-mail</label>
        <textarea class="form-control" rows="4" id="clientEmailPreview" readonly placeholder="Selecione um cliente para carregar o e-mail cadastrado."></textarea>
        <input type="hidden" name="send_email" value="N">
        <label class="check mt-3"><input type="checkbox" name="send_email" value="S" <?=($old['send_email']??$d['send_email']??'N')==='S'?'checked':''?>> Enviar documentos pela Omie ao cliente</label>
        <div class="omie-email-note"><i class="fa-solid fa-circle-info"></i>O envio efetivo segue as opções aceitas pela Omie no pedido.</div>
       </div>
      </section>
     </div>
    </section>

    <div class="omie-order-footer">
     <div class="omie-order-footer-total"><span>Total do pedido</span><strong id="footerGrandTotal">R$ 0,00</strong></div>
     <div class="omie-order-footer-actions"><button class="btn btn-outline-secondary" name="submit_mode" value="preview" <?=$ready['ok']?'':'disabled'?>>Validar sem enviar</button><button class="btn btn-primary" name="submit_mode" value="send" <?=$ready['ok']?'':'disabled'?>>Enviar para Omie</button></div>
    </div>
   </form>

   <?php if($preview):?><div class="panel mt-3"><h2>Payload validado — não enviado</h2><pre class="payload"><?=e(json_encode($preview,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))?></pre></div><?php endif;?>
   <script>
   window.ORDER_PREFILL_CLIENT=<?=json_encode((int)($old['client_id']??$prefill))?>;
   window.ORDER_OLD_ITEMS=<?=json_encode(json_decode((string)($old['items_json']??'[]'),true)?:[])?>;
   window.ORDER_META=<?=json_encode(['categories'=>$categories,'taxes'=>$taxes,'stocks'=>$stocks,'profiles'=>$profiles],JSON_UNESCAPED_UNICODE)?>;
   </script>
  <?php break;

  case 'collection':?>
   <div class="page-head"><div><span class="eyebrow">COBRANÇA</span><h1>Carteira</h1><p>Saldo, atraso e responsável.</p></div><div class="tabs"><a class="<?=$view==='open'?'active':''?>" href="<?=APP_URL?>/collection?view=open">Pendentes</a><a class="<?=$view==='settled'?'active':''?>" href="<?=APP_URL?>/collection?view=settled">Quitados</a></div></div><div class="table-card"><table class="table"><thead><tr><th>Cliente</th><th>UF</th><th>Atraso</th><th>Responsável</th><th class="text-end">Saldo</th><th></th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><strong><?=e($r['name'])?></strong><small><?=e($r['document']??'')?></small></td><td><?=e($r['uf']??'—')?></td><td><?=$r['max_overdue_days']?> dias</td><td><?=e($r['assigned_name']??'Não atribuído')?></td><td class="text-end"><strong><?=money($r['open_amount'])?></strong><?php if((float)$r['partial_paid']>0):?><small><?=money($r['partial_paid'])?> pago</small><?php endif;?></td><td><a class="btn btn-sm btn-outline-secondary" href="<?=APP_URL?>/collection/<?=$r['client_id']?>">Abrir</a></td></tr><?php endforeach;?></tbody></table></div>
  <?php break;
  case 'collection_case':?>
   <div class="page-head"><div><a class="back" href="<?=APP_URL?>/collection">← Cobrança</a><h1><?=e($case['name'])?></h1><p><?=money($case['open_amount'])?> em aberto • <?=$case['max_overdue_days']?> dias</p></div></div><?php if($collectors):?><div class="panel assignment-panel mb-3"><div><span class="eyebrow">RESPONSABILIDADE</span><strong><?=e($case['assigned_name']??'Não atribuído')?></strong><small>Transferir move histórico, meta e retornos pendentes para o novo responsável.</small></div><form method="post" action="<?=APP_URL?>/collection/<?=$case['client_id']?>/assign"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><select class="form-select" name="assigned_user_id" required><option value="">Novo responsável...</option><?php foreach($collectors as $c):?><option value="<?=$c['id']?>"><?=e($c['name'])?></option><?php endforeach;?></select><button class="btn btn-outline-secondary">Transferir tudo</button></form></div><?php endif;?><div class="two-col"><div class="panel"><h2>Registrar ação</h2><form method="post" action="<?=APP_URL?>/collection/<?=$case['client_id']?>/action"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><?php if($collectors):?><label>Responsável</label><select class="form-select" name="assigned_user_id"><option value="">Manter atual</option><?php foreach($collectors as $c):?><option value="<?=$c['id']?>" <?=(int)$case['assigned_user_id']===(int)$c['id']?'selected':''?>><?=e($c['name'])?></option><?php endforeach;?></select><?php endif;?><label>Canal</label><select class="form-select" name="channel"><option value="phone">Ligação</option><option value="whatsapp">WhatsApp</option><option value="email">E-mail</option></select><label>Resultado</label><select class="form-select" name="result"><option value="contact">Contato</option><option value="promise">Promessa</option><option value="agreement">Acordo</option><option value="payment">Pagamento</option><option value="no_answer">Não atendeu</option></select><label>Valor</label><input class="form-control" name="amount"><label>Data prometida</label><input class="form-control" type="date" name="promise_date"><label>Anotação</label><textarea class="form-control" name="notes" rows="4"></textarea><button class="btn btn-primary w-100">Salvar</button></form></div><div class="panel"><h2>Histórico</h2><div class="timeline"><?php foreach($actions as $a):?><div><strong><?=e($a['result'])?><?php if((float)$a['amount']>0):?> • <?=money($a['amount'])?><?php endif;?></strong><small>Feito por <?=e($a['author_name'])?> • responsável <?=e($a['assigned_name'])?> • <?=date('d/m/Y H:i',strtotime($a['created_at']))?></small><?php if($a['notes']):?><p><?=nl2br(e($a['notes']))?></p><?php endif;?></div><?php endforeach;?></div></div></div>
  <?php break;
  case 'agenda':?>
   <div class="page-head"><div><span class="eyebrow">AGENDA</span><h1>Retornos</h1><p>Em ordem de horário.</p></div></div><div class="agenda-list"><?php foreach($rows as $r):?><div class="agenda-item <?=strtotime($r['due_at'])<time()?'late':''?>"><div><strong><?=date('H:i',strtotime($r['due_at']))?></strong><small><?=date('d/m',strtotime($r['due_at']))?></small></div><div><strong><?=e($r['name'])?></strong><small><?=e($r['title'])?></small></div><a class="btn btn-sm btn-outline-secondary" href="<?=APP_URL?>/<?=$r['type']==='collection'?'collection':'clients'?>/<?=$r['client_id']?>">Abrir</a><form method="post" action="<?=APP_URL?>/agenda/<?=$r['id']?>/done"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="btn btn-sm btn-light">Concluir</button></form></div><?php endforeach;?></div>
  <?php break;
  case 'management_result':$g=$management['general_goal'];?>
   <div class="page-head"><div><span class="eyebrow">GESTÃO • <?=e($month)?></span><h1>Resultados da operação</h1><p>Admin e supervisor enxergam o consolidado e cada responsável que compõe a meta geral.</p></div><form method="get"><input class="form-control" type="month" name="month" value="<?=e($month)?>" onchange="this.form.submit()"></form></div>
   <div class="management-summary">
    <div class="management-kpi primary"><span>Vendas + serviços</span><strong><?=money($management['sales'])?></strong><small>pedidos <?=money($management['order_sales'])?> • serviços <?=money($management['service_sales'])?> • meta geral <?=money($management['effective_sales_goal'])?></small><div class="progress-line"><span style="width:<?=min(100,$management['sales_percent'])?>%"></span></div><b><?=number_format($management['sales_percent'],1,',','.')?>%</b></div>
    <div class="management-kpi"><span>Recuperado</span><strong><?=money($management['recovered'])?></strong><small>meta <?=money($management['effective_collection_goal'])?></small><div class="progress-line light"><span style="width:<?=min(100,$management['collection_percent'])?>%"></span></div><b><?=number_format($management['collection_percent'],1,',','.')?>%</b></div>
    <div class="management-kpi"><span>Contatos / ações</span><strong><?=$management['contacts']?></strong><small>meta <?=$management['effective_contact_goal']?></small><div class="progress-line light"><span style="width:<?=min(100,$management['contact_percent'])?>%"></span></div><b><?=number_format($management['contact_percent'],1,',','.')?>%</b></div>
   </div>
   <div class="management-columns mt-3">
    <div class="panel"><div class="panel-title-row"><div><span class="eyebrow">COMERCIAL</span><h2>Vendedores</h2></div><small>Somam na meta geral de vendas.</small></div><div class="result-ranking"><?php foreach($management['sellers'] as $row):$usr=$row['user'];?><div><span><strong><?=e($usr['name'])?></strong><small><?=money($row['sales'])?> de <?=money($row['goal']['sales_goal'])?></small></span><div><b><?=number_format($row['sales_percent'],1,',','.')?>%</b><div class="mini-progress"><span style="width:<?=min(100,$row['sales_percent'])?>%"></span></div></div></div><?php endforeach;?></div></div>
    <div class="panel"><div class="panel-title-row"><div><span class="eyebrow">COBRANÇA</span><h2>Recuperação</h2></div><small>Somam na meta geral de recuperação.</small></div><div class="result-ranking"><?php foreach($management['collectors'] as $row):$usr=$row['user'];?><div><span><strong><?=e($usr['name'])?></strong><small><?=money($row['recovered'])?> de <?=money($row['goal']['collection_goal'])?></small></span><div><b><?=number_format($row['collection_percent'],1,',','.')?>%</b><div class="mini-progress"><span style="width:<?=min(100,$row['collection_percent'])?>%"></span></div></div></div><?php endforeach;?></div></div>
   </div>
   <?php if(!empty($management['virtual_sellers'])):?><div class="panel mt-3"><div class="panel-title-row"><div><span class="eyebrow">VENDEDORES VIRTUAIS</span><h2>Canais que também fecham a meta</h2></div><small>EAD e Suporte Jumper entram no consolidado mesmo sem usuário operacional.</small></div><div class="result-ranking"><?php foreach($management['virtual_sellers'] as $row):?><div><span><strong><?=e($row['seller']['name'])?></strong><small>pedidos <?=money($row['orders'])?> • serviços <?=money($row['services'])?></small></span><div><b><?=money($row['sales'])?></b></div></div><?php endforeach;?></div></div><?php endif;?>
  <?php break;
  case 'result':$u=$result['user'];$g=$result['goal'];?>
   <div class="page-head"><div><span class="eyebrow">RESULTADO • <?=e((string)($g['month_ref']??date('Y-m')))?></span><h1>Meu resultado</h1><p>Meta e realizado sem relatórios desnecessários.</p></div></div>
   <?php if($u['role']==='seller'):?><div class="result-focus"><div class="result-main"><span>Vendas + serviços</span><strong><?=money($result['sales'])?></strong><small>pedidos <?=money($result['orders_sales']??0)?> • serviços <?=money($result['services_sales']??0)?> • meta <?=money($g['sales_goal'])?></small><div class="progress-line"><span style="width:<?=min(100,$result['sales_percent'])?>%"></span></div></div><div><span>Atingimento</span><strong><?=number_format($result['sales_percent'],1,',','.')?>%</strong></div><div><span>Contatos</span><strong><?=$result['contacts']?></strong><small>meta <?=(int)$g['contact_goal']?></small></div></div>
   <?php elseif($u['role']==='collector'):?><div class="result-focus"><div class="result-main"><span>Recuperado</span><strong><?=money($result['recovered'])?></strong><small>meta <?=money($g['collection_goal'])?></small><div class="progress-line"><span style="width:<?=min(100,$result['collection_percent'])?>%"></span></div></div><div><span>Atingimento</span><strong><?=number_format($result['collection_percent'],1,',','.')?>%</strong></div><div><span>Ações</span><strong><?=$result['contacts']?></strong><small>meta <?=(int)$g['contact_goal']?></small></div></div><?php endif;?>
  <?php break;
  case 'users':$editing=$edit??null;?>
   <div class="page-head"><div><span class="eyebrow">SISTEMA</span><h1>Usuários</h1><p>Perfis simples e vínculo do vendedor com a Omie.</p></div><?php if($editing):?><a class="btn btn-outline-secondary" href="<?=APP_URL?>/users">Novo usuário</a><?php endif;?></div>
   <div class="two-col users-layout"><div class="panel"><h2><?=$editing?'Editar usuário':'Novo usuário'?></h2><form method="post" action="<?=APP_URL?>/users"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="id" value="<?=(int)($editing['id']??0)?>"><label>Nome</label><input class="form-control" name="name" value="<?=e($editing['name']??'')?>" required><label>E-mail</label><input class="form-control" type="email" name="email" value="<?=e($editing['email']??'')?>" required><label>Perfil</label><select class="form-select" name="role" data-role-select><?php foreach(['seller'=>'Vendedor','collector'=>'Cobrança','supervisor'=>'Supervisor','admin'=>'Admin'] as $rv=>$rl):?><option value="<?=$rv?>" <?=($editing['role']??'seller')===$rv?'selected':''?>><?=$rl?></option><?php endforeach;?></select><div data-seller-field><label>Vendedor Omie</label><select class="form-select" name="seller_omie_code"><option value="">Selecione...</option><?php foreach($sellers as $s):?><option value="<?=e($s['omie_code'])?>" <?=($editing['seller_omie_code']??'')===$s['omie_code']?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select></div><label>Senha <?=$editing?'<small>(deixe vazia para manter)</small>':''?></label><input class="form-control" type="password" name="password" <?=$editing?'':'required'?>><label class="check mt-3"><input type="checkbox" name="active" value="1" <?=!$editing||$editing['active']?'checked':''?>> Ativo</label><button class="btn btn-primary w-100"><?=$editing?'Salvar alterações':'Criar usuário'?></button></form></div>
   <div class="table-card"><table class="table"><thead><tr><th>Usuário</th><th>Perfil</th><th>Vínculo</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($users as $row):?><tr><td><strong><?=e($row['name'])?></strong><small><?=e($row['email'])?></small></td><td><?=e($row['role'])?></td><td><?=e($row['seller_omie_code']??'—')?></td><td><?=$row['active']?'Ativo':'Inativo'?></td><td><a class="btn btn-sm btn-outline-secondary" href="<?=APP_URL?>/users?edit=<?=$row['id']?>">Editar</a></td></tr><?php endforeach;?></tbody></table></div></div>
  <?php break;
  case 'goals':$general=$management['general_goal'];?>
   <div class="page-head"><div><span class="eyebrow">GESTÃO</span><h1>Metas</h1><p>Meta geral da empresa e metas individuais que explicam sua composição.</p></div><form method="get"><input class="form-control" type="month" name="month" value="<?=e($month)?>" onchange="this.form.submit()"></form></div>
   <div class="management-summary mb-3">
    <div class="management-kpi primary"><span>Venda realizada</span><strong><?=money($management['sales'])?></strong><small>meta geral <?=money($management['effective_sales_goal'])?></small><b><?=number_format($management['sales_percent'],1,',','.')?>%</b></div>
    <div class="management-kpi"><span>Recuperado</span><strong><?=money($management['recovered'])?></strong><small>meta <?=money($management['effective_collection_goal'])?></small><b><?=number_format($management['collection_percent'],1,',','.')?>%</b></div>
    <div class="management-kpi"><span>Contatos / ações</span><strong><?=$management['contacts']?></strong><small>meta <?=$management['effective_contact_goal']?></small><b><?=number_format($management['contact_percent'],1,',','.')?>%</b></div>
   </div>
   <form class="panel general-goal mb-3" method="post" action="<?=APP_URL?>/goals/general"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="month" value="<?=e($month)?>"><div><span class="eyebrow">META GERAL</span><h2>Objetivo da operação</h2><small>Se um campo ficar zerado, o sistema usa automaticamente a soma das metas individuais.</small></div><div><label>Meta geral de vendas</label><input class="form-control" name="sales_goal" value="<?=e((string)$general['sales_goal'])?>"><small>Soma individual: <?=money($management['sales_goal_sum'])?></small></div><div><label>Meta geral de recuperação</label><input class="form-control" name="collection_goal" value="<?=e((string)$general['collection_goal'])?>"><small>Soma individual: <?=money($management['collection_goal_sum'])?></small></div><div><label>Meta geral de contatos</label><input class="form-control" type="number" name="contact_goal" value="<?=(int)$general['contact_goal']?>"><small>Soma individual: <?=$management['contact_goal_sum']?></small></div><button class="btn btn-primary">Salvar meta geral</button></form>
   <div class="goal-list"><?php foreach($rows as $row):$usr=$row['user'];$g=$row['goal'];?><form class="goal-row" method="post" action="<?=APP_URL?>/goals/<?=$usr['id']?>"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="month" value="<?=e($month)?>"><div><strong><?=e($usr['name'])?></strong><small><?=$usr['role']==='seller'?'Vendedor':'Cobrança'?></small></div><?php if($usr['role']==='seller'):?><div><label>Meta vendas</label><input class="form-control" name="sales_goal" value="<?=e((string)$g['sales_goal'])?>"><small>realizado <?=money($row['sales'])?> • <?=number_format($row['sales_percent'],1,',','.')?>%</small></div><?php else:?><div><label>Meta recuperação</label><input class="form-control" name="collection_goal" value="<?=e((string)$g['collection_goal'])?>"><small>recuperado <?=money($row['recovered'])?> • <?=number_format($row['collection_percent'],1,',','.')?>%</small></div><?php endif;?><div><label>Meta contatos</label><input class="form-control" type="number" name="contact_goal" value="<?=(int)$g['contact_goal']?>"><small>realizado <?=$row['contacts']?> • <?=number_format($row['contact_percent'],1,',','.')?>%</small></div><button class="btn btn-outline-secondary">Salvar</button></form><?php endforeach;?></div>
  <?php break;
  case 'settings':?>
   <div class="page-head"><div><span class="eyebrow">SISTEMA</span><h1>Configurações</h1><p>Padrões agilizam a operação, mas o vendedor pode ajustar os campos do pedido conforme o tipo de negócio.</p></div></div>
   <form method="post" action="<?=APP_URL?>/settings"><input type="hidden" name="_token" value="<?=CSRF::token()?>">
    <div class="panel mb-3"><h2>Padrões do pedido</h2><div class="settings-grid"><?php $fields=[['stage','Etapa',$stages,'code','name'],['category','Categoria',$categories,'code','description'],['account','Conta corrente',$accounts,'omie_code','name'],['payment_term','Condição',$terms,'code','description'],['payment_method','Meio de pagamento',$methods,'code','description'],['document_type','Tipo documento',$documents,'code','description'],['tax_scenario','Cenário fiscal',$taxes,'omie_code','name'],['stock_location','Local estoque',$stocks,'omie_code','name']];foreach($fields as [$key,$label,$list,$vk,$lk]):?><div><label><?=$label?></label><select class="form-select" name="<?=$key?>"><option value="">Selecione</option><?php foreach($list as $r):?><option value="<?=e($r[$vk])?>" <?=($defaults[$key]??'')===(string)$r[$vk]?'selected':''?>><?=e($r[$lk])?></option><?php endforeach;?></select></div><?php endforeach;?><div><label>Frete padrão</label><select class="form-select" name="freight_mode"><?php foreach(['9'=>'Sem frete','0'=>'CIF','1'=>'FOB','2'=>'Terceiros','3'=>'Próprio remetente','4'=>'Próprio destinatário'] as $k=>$v):?><option value="<?=$k?>" <?=($defaults['freight_mode']??'9')===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select></div><div><label>Consumidor final</label><select class="form-select" name="consumer_final"><option value="S">Sim</option><option value="N" <?=($defaults['consumer_final']??'S')==='N'?'selected':''?>>Não</option></select></div><div><label class="check"><input type="checkbox" name="send_email" value="1" <?=($defaults['send_email']??'N')==='S'?'checked':''?>> Enviar e-mail Omie</label></div></div></div>
    <div class="panel mb-3"><h2>Contas usadas na cobrança</h2><div class="account-checks"><?php foreach($accounts as $r):?><label><input type="checkbox" name="collection_accounts[]" value="<?=e($r['omie_code'])?>" <?=$r['selected']?'checked':''?>> <?=e($r['name'])?></label><?php endforeach;?></div></div>
    <button class="btn btn-primary">Salvar configurações</button>
   </form>
   <div class="panel mt-4"><div class="panel-title-row"><div><span class="eyebrow">TIPOS DE PEDIDO</span><h2>Perfis operacionais</h2></div><small>Mesmo conceito de tipo de documento: define comportamento inicial dos itens.</small></div>
    <div class="profile-list"><?php foreach($profiles as $p):?><div><span><strong><?=e($p['name'])?></strong><small><?=e($p['description']??'')?></small></span><div class="profile-flags"><?php if($p['default_no_stock']==='S'):?><b>Sem estoque</b><?php endif;?><?php if($p['default_no_finance']==='S'):?><b>Sem financeiro</b><?php endif;?><?php if($p['default_no_total']==='S'):?><b>Fora total NF-e</b><?php endif;?><?php if($p['default_reserve_stock']==='S'):?><b>Reserva</b><?php endif;?></div></div><?php endforeach;?></div>
    <form class="profile-form mt-3" method="post" action="<?=APP_URL?>/settings/order-profile"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><div><label>Código</label><input class="form-control" name="code" placeholder="EX: VENDA_ESPECIAL" required></div><div><label>Nome</label><input class="form-control" name="name" required></div><div class="profile-description"><label>Descrição</label><input class="form-control" name="description"></div><label class="check"><input type="checkbox" name="default_no_stock" value="1"> Não movimentar estoque</label><label class="check"><input type="checkbox" name="default_no_finance" value="1"> Não gerar financeiro</label><label class="check"><input type="checkbox" name="default_no_total" value="1"> Não somar na NF-e</label><label class="check"><input type="checkbox" name="default_reserve_stock" value="1"> Reservar estoque</label><input type="hidden" name="active" value="1"><button class="btn btn-outline-secondary">Criar tipo</button></form>
   </div>
  <?php break;
  case 'test_data':?>
   <div class="page-head">
    <div><span class="eyebrow">TESTE CONTROLADO</span><h1>Carga mínima da Omie</h1><p>Importe somente 1 cliente e até 2 produtos para validar o pedido sem carregar toda a base.</p></div>
    <a class="btn btn-outline-secondary" href="<?=APP_URL?>/orders/new">Ir para novo pedido</a>
   </div>
   <?php if($flash):?><div class="alert alert-<?=e($flash['type']??'info')?>"><?=e($flash['message']??'')?></div><?php endif;?>
   <div class="test-grid">
    <div class="panel">
     <div class="panel-title-row"><div><span class="eyebrow">PASSO 1</span><h2>Cliente e produtos de teste</h2></div></div>
     <p class="text-secondary">Use os códigos numéricos internos da Omie. O CRM chama ConsultarCliente e ConsultarProduto diretamente.</p>
     <form method="post" action="<?=APP_URL?>/test-data/import">
      <input type="hidden" name="_token" value="<?=CSRF::token()?>">
      <label>Código Omie do cliente</label><input class="form-control" name="client_omie_code" inputmode="numeric" required>
      <div class="mini-grid"><div><label>Código Omie do produto 1</label><input class="form-control" name="product_1_omie_code" inputmode="numeric" required></div><div><label>Código Omie do produto 2</label><input class="form-control" name="product_2_omie_code" inputmode="numeric"></div></div>
      <button class="btn btn-primary mt-3">Importar somente estes registros</button>
     </form>
     <?php if(!empty($flash['client'])):?><div class="test-result mt-3"><strong>Cliente importado</strong><span><?=e($flash['client']['name']??'')?></span></div><?php endif;?>
     <?php if(!empty($flash['products'])):foreach($flash['products'] as $p):?><div class="test-result"><strong>Produto importado</strong><span><?=e($p['description']??'')?> • <?=money($p['unit_price']??0)?></span></div><?php endforeach;endif;?>
    </div>

    <div class="panel">
     <div class="panel-title-row"><div><span class="eyebrow">PASSO 2</span><h2>Parâmetros auxiliares</h2></div></div>
     <p class="text-secondary">Sincroniza apenas cadastros pequenos necessários ao pedido: vendedores, categorias, contas, etapas, condições, cenários, locais, meios e tipos de documento.</p>
     <div class="snapshot test-snapshot">
      <div><span>Clientes</span><strong><?=$snapshot['clients']?></strong></div>
      <div><span>Produtos</span><strong><?=$snapshot['products']?></strong></div>
      <div><span>Vendedores</span><strong><?=$snapshot['sellers']?></strong></div>
      <div><span>Condições</span><strong><?=$snapshot['terms']?></strong></div>
     </div>
     <form method="post" action="<?=APP_URL?>/test-data/references">
      <input type="hidden" name="_token" value="<?=CSRF::token()?>">
      <button class="btn btn-outline-secondary">Preparar parâmetros do pedido</button>
     </form>
     <?php if(!empty($flash['references'])):?><div class="test-reference-list mt-3"><?php foreach($flash['references'] as $k=>$v):?><div><span><?=e($k)?></span><strong><?=(int)$v?></strong></div><?php endforeach;?></div><?php endif;?>
    </div>
   </div>
   <div class="panel mt-3"><div class="panel-title-row"><div><span class="eyebrow">FLUXO</span><h2>Teste recomendado</h2></div></div><p class="mb-0">1 cliente + 2 produtos → parâmetros auxiliares → Configurações → Novo pedido → Validar sem enviar → Enviar para Omie.</p></div>
  <?php break;
  case 'sync':$map=[];foreach($states as $s)$map[$s['module_key']]=$s;?>
   <div class="page-head"><div><span class="eyebrow">OMIE</span><h1>Sincronização</h1><p>Uma página por chamada.</p></div></div><div class="sync-grid"><?php foreach($modules as $key=>$label):$s=$map[$key]??null;?><div class="sync-card"><div><strong><?=e($label)?></strong><small><?=$s&&!empty($s['last_success_at'])?'Última: '.date('d/m H:i',strtotime($s['last_success_at'])):'Nunca'?></small></div><span id="sync-state-<?=$key?>">Aguardando</span><button class="btn btn-sm btn-outline-secondary" data-sync="<?=$key?>">Sincronizar</button></div><?php endforeach;?></div>
  <?php break;
 }
 $body=ob_get_clean();
 layout($body,$u);
}

function layout(string $body,?array $u): void{
 ?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($GLOBALS['config']['app']['name']??'Tecnodata CRM')?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.datatables.net/3.0.3/css/dataTables.bootstrap5.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" rel="stylesheet"><link rel="stylesheet" href="<?=APP_URL?>/assets/app.css?v=<?=is_file(APP_ROOT.'/public/assets/app.css')?filemtime(APP_ROOT.'/public/assets/app.css'):time()?>"></head><body><?php if(!$u){echo $body;}else{?><div class="app-shell"><aside class="sidebar"><a class="brand" href="<?=APP_URL?>/"><span>T</span><strong>Tecnodata<small>CRM</small></strong></a><nav><?php if($u['role']==='seller'):?><a href="<?=APP_URL?>/"><i class="fa-solid fa-bolt"></i>Hoje</a><a href="<?=APP_URL?>/clients"><i class="fa-solid fa-users"></i>Clientes</a><a href="<?=APP_URL?>/orders/new"><i class="fa-solid fa-plus"></i>Novo pedido</a><a href="<?=APP_URL?>/orders"><i class="fa-solid fa-receipt"></i>Pedidos</a><a href="<?=APP_URL?>/services"><i class="fa-solid fa-screwdriver-wrench"></i>Serviços</a><a href="<?=APP_URL?>/agenda"><i class="fa-regular fa-calendar"></i>Agenda</a><a href="<?=APP_URL?>/result"><i class="fa-solid fa-chart-line"></i>Resultado</a><?php elseif($u['role']==='collector'):?><a href="<?=APP_URL?>/"><i class="fa-solid fa-bolt"></i>Hoje</a><a href="<?=APP_URL?>/collection"><i class="fa-solid fa-hand-holding-dollar"></i>Cobrança</a><a href="<?=APP_URL?>/agenda"><i class="fa-regular fa-calendar"></i>Agenda</a><a href="<?=APP_URL?>/result"><i class="fa-solid fa-chart-line"></i>Resultado</a><?php else:?><a href="<?=APP_URL?>/"><i class="fa-solid fa-gauge-high"></i>Painel</a><a href="<?=APP_URL?>/clients"><i class="fa-solid fa-users"></i>Clientes</a><a href="<?=APP_URL?>/orders/new"><i class="fa-solid fa-plus"></i>Novo pedido</a><a href="<?=APP_URL?>/orders"><i class="fa-solid fa-receipt"></i>Pedidos</a><a href="<?=APP_URL?>/services"><i class="fa-solid fa-screwdriver-wrench"></i>Serviços</a><a href="<?=APP_URL?>/collection"><i class="fa-solid fa-hand-holding-dollar"></i>Cobrança</a><a href="<?=APP_URL?>/agenda"><i class="fa-regular fa-calendar"></i>Agenda</a><a href="<?=APP_URL?>/result"><i class="fa-solid fa-chart-column"></i>Resultados</a><a href="<?=APP_URL?>/goals"><i class="fa-solid fa-bullseye"></i>Metas</a><?php if($u['role']==='admin'):?><div class="nav-label">Sistema</div><a href="<?=APP_URL?>/users"><i class="fa-solid fa-user-shield"></i>Usuários</a><a href="<?=APP_URL?>/settings"><i class="fa-solid fa-sliders"></i>Configurações</a><a href="<?=APP_URL?>/sync"><i class="fa-solid fa-arrows-rotate"></i>Sincronização</a><a href="<?=APP_URL?>/test-data"><i class="fa-solid fa-flask"></i>Carga de teste</a><?php endif;?><?php endif;?></nav><form method="post" action="<?=APP_URL?>/logout" class="logout"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button><i class="fa-solid fa-right-from-bracket"></i>Sair</button></form></aside><main class="main"><header class="topbar"><button class="mobile-menu" data-menu><i class="fa-solid fa-bars"></i></button><div></div><div class="user-chip"><span><?=e(mb_strtoupper(mb_substr((string)$u['name'],0,1)))?></span><div><strong><?=e($u['name'])?></strong><small><?=e($u['role'])?></small></div></div></header><section class="content"><?=$body?></section></main></div><?php }?><script>window.APP_URL=<?=json_encode(APP_URL)?>;window.CSRF=<?=json_encode(CSRF::token())?>;</script><script src="https://cdn.datatables.net/3.0.3/js/dataTables.min.js"></script><script src="https://cdn.datatables.net/3.0.3/js/dataTables.bootstrap5.min.js"></script><script src="<?=APP_URL?>/assets/app.js?v=<?=is_file(APP_ROOT.'/public/assets/app.js')?filemtime(APP_ROOT.'/public/assets/app.js'):time()?>"></script></body></html><?php
}
