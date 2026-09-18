<?php
function render(string $name,array $vars=[]): void{
 extract($vars,EXTR_SKIP);$u=Auth::user();ob_start();
 switch($name){
  case 'login':?>
   <main class="tdlogin-page">
    <section class="tdlogin-hero">
     <div class="tdlogin-hero-orb one"></div><div class="tdlogin-hero-orb two"></div>
     <div class="tdlogin-hero-inner">
      <header class="tdlogin-brand"><span class="tdlogin-brand-icon">TD</span><div><strong>Tecnodata <b>CRM</b></strong><small>Inteligência comercial em movimento</small></div></header>
      <div class="tdlogin-copy">
       <span class="tdlogin-eyebrow"><i></i> OPERAÇÃO CONECTADA</span>
       <h1>Relacionamentos melhores começam com <span>informação clara.</span></h1>
       <p>Clientes, pedidos, cobrança e agenda trabalhando juntos para sua equipe decidir melhor e agir mais rápido.</p>
       <div class="tdlogin-capabilities"><span><i class="fa-solid fa-users"></i>Clientes</span><span><i class="fa-solid fa-cart-shopping"></i>Pedidos</span><span><i class="fa-solid fa-hand-holding-dollar"></i>Cobrança</span><span><i class="fa-regular fa-calendar-check"></i>Agenda</span></div>
      </div>
      <div class="tdlogin-overview" aria-hidden="true">
       <div class="tdlogin-overview-head"><div><span></span><span></span><span></span></div><small>VISÃO COMERCIAL</small><b><i class="fa-solid fa-circle"></i> Sincronizado</b></div>
       <div class="tdlogin-overview-body">
        <div class="tdlogin-overview-chart"><small>EVOLUÇÃO DA OPERAÇÃO</small><div><i style="height:38%"></i><i style="height:54%"></i><i style="height:47%"></i><i style="height:69%"></i><i style="height:64%"></i><i style="height:86%"></i><i style="height:100%"></i></div></div>
        <div class="tdlogin-overview-feed"><article><span class="blue"><i class="fa-solid fa-user-check"></i></span><div><strong>Carteira organizada</strong><small>Clientes prontos para atendimento</small></div><b>Agora</b></article><article><span class="green"><i class="fa-solid fa-file-circle-check"></i></span><div><strong>Pedido acompanhado</strong><small>Do rascunho à integração</small></div><b>CRM</b></article><article><span class="amber"><i class="fa-solid fa-bell"></i></span><div><strong>Próximas ações</strong><small>Agenda e retornos centralizados</small></div><b>Hoje</b></article></div>
       </div>
      </div>
      <footer class="tdlogin-hero-foot"><span><i class="fa-solid fa-shield-halved"></i>Ambiente corporativo protegido</span><small>© <?=date('Y')?> Tecnodata Educacional</small></footer>
     </div>
    </section>
    <section class="tdlogin-access">
     <div class="tdlogin-card">
      <div class="tdlogin-card-status"><i class="fa-solid fa-shield-halved"></i> Acesso corporativo</div>
      <div class="tdlogin-card-mark"><span>TD</span></div><span class="tdlogin-card-kicker">TECNODATA CRM</span><h2>Bem-vindo de volta</h2>
      <p class="tdlogin-subtitle"><?=!empty($googleAvailable)?'Um clique para entrar. Sem digitar e-mail ou senha.':'Use suas credenciais para continuar.'?></p>
      <?php if(!empty($error)):?><div class="alert alert-danger tdlogin-alert"><i class="fa-solid fa-circle-exclamation"></i><span><?=e($error)?></span></div><?php endif;?>
      <?php if(!empty($googleAvailable)):?>
       <a class="tdlogin-google" href="<?=APP_URL?>/auth/google" aria-label="Entrar agora com a conta Google corporativa"><svg aria-hidden="true" viewBox="0 0 24 24"><path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.91h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.33 2.98-7.4Z"/><path fill="#34A853" d="M12 22c2.7 0 4.97-.9 6.62-2.43l-3.24-2.53c-.9.6-2.05.96-3.38.96-2.6 0-4.81-1.76-5.6-4.13H3.06v2.61A10 10 0 0 0 12 22Z"/><path fill="#FBBC05" d="M6.4 13.87A6.01 6.01 0 0 1 6.09 12c0-.65.11-1.28.31-1.87V7.52H3.06A10 10 0 0 0 2 12c0 1.61.38 3.14 1.06 4.48l3.34-2.61Z"/><path fill="#EA4335" d="M12 6c1.47 0 2.79.51 3.82 1.5l2.87-2.87A9.63 9.63 0 0 0 12 2a10 10 0 0 0-8.94 5.52l3.34 2.61C7.19 7.76 9.4 6 12 6Z"/></svg><span>Entrar agora com Google</span><i class="fa-solid fa-arrow-right"></i></a>
       <p class="tdlogin-google-note"><i class="fa-solid fa-circle-check"></i>Use a conta já aberta no navegador — nenhuma senha será pedida pelo CRM.</p>
       <div class="tdlogin-sso-benefits"><span><i class="fa-solid fa-bolt"></i>Entrada rápida</span><span><i class="fa-solid fa-building"></i>Conta da empresa</span><span><i class="fa-solid fa-lock"></i>Acesso protegido</span></div>
      <?php endif;?>
      <?php if(!empty($googleAvailable)):?><details class="tdlogin-password-access"><summary><span class="tdlogin-password-icon"><i class="fa-solid fa-key"></i></span><span><strong>Entrar com e-mail e senha</strong><small>Use seu acesso tradicional do CRM</small></span></summary><div class="tdlogin-password-panel"><?php endif;?>
      <form method="post" action="<?=APP_URL?>/login" class="tdlogin-form" autocomplete="on">
       <input type="hidden" name="_token" value="<?=CSRF::token()?>"><label for="tdlogin-email">E-mail</label><div class="tdlogin-input"><i class="fa-regular fa-envelope"></i><input id="tdlogin-email" class="form-control" type="email" name="email" placeholder="nome@empresa.com.br" required <?=empty($googleAvailable)?'autofocus':''?> autocomplete="username"></div>
       <label for="tdlogin-password">Senha</label><div class="tdlogin-input"><i class="fa-solid fa-lock"></i><input id="tdlogin-password" class="form-control" type="password" name="password" placeholder="Digite sua senha" required autocomplete="current-password"><button type="button" class="tdlogin-password-toggle" data-password-toggle aria-label="Mostrar senha" title="Mostrar senha"><i class="fa-regular fa-eye"></i></button></div>
       <div class="tdlogin-options"><label class="tdlogin-remember"><input type="checkbox" name="remember_access" value="1"><span><i class="fa-solid fa-check"></i></span>Manter conectado</label><span class="tdlogin-help" title="A redefinição de senha é administrada internamente.">Precisa de ajuda?</span></div>
       <button class="tdlogin-submit" type="submit"><span>Entrar com senha</span><i class="fa-solid fa-arrow-right"></i></button>
      </form>
      <?php if(!empty($googleAvailable)):?></div></details><?php endif;?>
      <div class="tdlogin-secure"><i class="fa-solid fa-lock"></i><span>Conexão segura e acesso controlado pela empresa.</span></div>
     </div>
     <footer class="tdlogin-footer"><strong>Tecnodata CRM</strong><span>Dados que aproximam. Tecnologia que transforma.</span></footer>
    </section>
   </main>
  <?php break;
  case 'dashboard':
   $data=is_array($data??null)?$data:[];
   $resultData=is_array($result??null)?$result:[];
   $mg=is_array($management??null)?$management:[];
   $firstName=e(explode(' ',trim((string)$u['name']))[0]??'');
   ?>
   <section class="tdd-page tdd-unified">
    <header class="tdd-head">
     <div class="tdd-head-main">
      <span class="tdd-head-icon"><i class="fa-solid fa-chart-line"></i></span>
      <div><span class="tdd-kicker">VISÃO GERAL</span><h1><?=$u['role']==='seller'||$u['role']==='collector'?'Olá, '.$firstName:'Dashboard'?></h1><p>Acompanhe os principais números da operação, metas e prioridades do período.</p></div>
     </div>
     <div class="tdd-head-tools">
      <div class="tdd-date"><i class="fa-regular fa-calendar"></i><div><strong><?=date('d/m/Y')?></strong><small><?=date('H:i')?> · atualização atual</small></div></div>
      <form class="tdd-period-select" method="get"><input type="hidden" name="result_model" value="<?=e($resultModel??'executive')?>"><input type="hidden" name="result_area" value="<?=e($resultArea??'commercial')?>"><?php if(!empty($resultSeller)):?><input type="hidden" name="seller" value="<?=e($resultSeller)?>"><?php endif;?><label><span>Mês de referência</span><input class="form-control" type="month" name="month" value="<?=e($month)?>" onchange="this.form.submit()"></label><?php foreach($selectedDays as $selectedDay):?><input type="hidden" name="days[]" value="<?=$selectedDay?>"><?php endforeach;?></form>
     </div>
    </header>

    <details class="tdd-period-panel" <?=$selectedDays?'open':''?>>
     <summary><span><i class="fa-regular fa-calendar-days"></i><b>Período analisado</b><small><?=e($periodLabel)?></small></span><i class="fa-solid fa-chevron-down"></i></summary>
    <form class="tdr-filter tdd-result-filter" method="get" data-result-days>
     <input type="hidden" name="month" value="<?=e($month)?>"><input type="hidden" name="result_model" value="<?=e($resultModel??'executive')?>"><input type="hidden" name="result_area" value="<?=e($resultArea??'commercial')?>"><?php if(!empty($resultSeller)):?><input type="hidden" name="seller" value="<?=e($resultSeller)?>"><?php endif;?>
     <div class="tdr-filter-head">
      <div class="tdr-filter-title"><span><i class="fa-regular fa-calendar-days"></i></span><div><small>PERÍODO ANALISADO</small><strong><?=e($periodLabel)?></strong><p data-result-day-count><?=$selectedDays?(count($selectedDays)===1?'1 dia selecionado':count($selectedDays).' dias selecionados'):'Mês inteiro selecionado'?></p></div></div>
      <div class="tdr-filter-actions"><a class="tdr-btn" href="<?=APP_URL?>/?<?=e(http_build_query(array_filter(['month'=>$month,'result_model'=>$resultModel??'executive','result_area'=>$resultArea??'commercial','seller'=>$resultSeller??''])))?>"><i class="fa-solid fa-calendar-check"></i>Mês inteiro</a><button class="tdr-btn tdr-btn-primary" type="submit"><i class="fa-solid fa-filter"></i>Aplicar dias</button></div>
     </div>
     <div class="tdr-day-grid"><?php for($resultDay=1;$resultDay<=31;$resultDay++):$available=$resultDay<=$daysInMonth;?><label class="<?=$available?'':'unavailable'?>"><input type="checkbox" name="days[]" value="<?=$resultDay?>" <?=in_array($resultDay,$selectedDays,true)?'checked':''?> <?=$available?'':'disabled'?>><span><?=str_pad((string)$resultDay,2,'0',STR_PAD_LEFT)?></span></label><?php endfor;?></div>
    </form>
    </details>

    <?php if($u['role']==='seller'):
      $g=$resultData['goal']??['sales_goal'=>0,'contact_goal'=>0];
    ?>
     <div class="tdd-info-strip"><i class="fa-solid fa-circle-info"></i><span>O resultado comercial soma Pedidos OK + Serviços válidos. Pedidos consideram o total com frete; cancelados, PDV e propostas/orçamentos não entram.</span></div>
     <div class="tdd-kpis">
      <article class="tdd-kpi green"><span class="tdd-kpi-icon"><i class="fa-solid fa-sack-dollar"></i></span><div><small>Resultado comercial</small><strong><?=money($resultData['sales']??0)?></strong><span class="note ok"><?=number_format((float)($resultData['sales_percent']??0),1,',','.')?>% da meta</span></div></article>
      <article class="tdd-kpi blue"><span class="tdd-kpi-icon"><i class="fa-solid fa-receipt"></i></span><div><small>Pedidos OK</small><strong><?=money($resultData['orders_sales']??0)?></strong><span class="note">Sem frete: <?=money($resultData['orders_without_freight']??0)?></span></div></article>
      <article class="tdd-kpi yellow"><span class="tdd-kpi-icon"><i class="fa-solid fa-bullseye"></i></span><div><small>Meta comercial</small><strong><?=money($g['sales_goal']??0)?></strong><span class="note"><?=e($resultData['goal_scope']??'Meta mensal')?></span></div></article>
      <article class="tdd-kpi orange"><span class="tdd-kpi-icon"><i class="fa-solid fa-screwdriver-wrench"></i></span><div><small>Serviços</small><strong><?=money($resultData['services_sales']??0)?></strong><span class="note">Somam no resultado</span></div></article>
     </div>

     <div class="tdd-performance single">
      <section class="tdd-card">
       <div class="tdd-card-head"><div class="tdd-card-title"><span class="green"><i class="fa-solid fa-chart-line"></i></span><div><strong>Atingimento comercial</strong><small><?=e($periodLabel)?></small></div></div><span class="tdd-percent"><?=number_format((float)($resultData['sales_percent']??0),1,',','.')?>%</span></div>
       <div class="tdd-card-body"><div class="tdd-value"><strong><?=money($resultData['sales']??0)?></strong><span>de <?=money($g['sales_goal']??0)?></span></div><div class="tdd-progress"><span style="width:<?=min(100,(float)($resultData['sales_percent']??0))?>%"></span></div><div class="tdd-card-foot"><span>Pedidos <strong><?=money($resultData['orders_sales']??0)?></strong></span><span>Serviços <strong><?=money($resultData['services_sales']??0)?></strong></span><span>Contatos <strong><?=number_format((int)($resultData['contacts']??0),0,',','.')?></strong></span></div></div>
      </section>
     </div>

    <?php elseif($u['role']==='collector'):
      $g=$resultData['goal']??['collection_goal'=>0,'contact_goal'=>0];
    ?>
     <div class="tdd-kpis">
      <article class="tdd-kpi green"><span class="tdd-kpi-icon"><i class="fa-solid fa-money-bill-trend-up"></i></span><div><small>Recuperado</small><strong><?=money($resultData['recovered']??0)?></strong><span class="note ok"><?=number_format((float)($resultData['collection_percent']??0),1,',','.')?>% da meta</span></div></article>
      <article class="tdd-kpi red"><span class="tdd-kpi-icon"><i class="fa-solid fa-circle-dollar-to-slot"></i></span><div><small>Saldo em cobrança</small><strong><?=money($data['debt']??0)?></strong><span class="note">Carteira aberta</span></div></article>
      <article class="tdd-kpi yellow"><span class="tdd-kpi-icon"><i class="fa-solid fa-bullseye"></i></span><div><small>Meta de recuperação</small><strong><?=money($g['collection_goal']??0)?></strong><span class="note"><?=e($resultData['goal_scope']??'Meta mensal')?></span></div></article>
      <article class="tdd-kpi blue"><span class="tdd-kpi-icon"><i class="fa-solid fa-phone"></i></span><div><small>Ações / contatos</small><strong><?=number_format((int)($resultData['contacts']??0),0,',','.')?></strong><span class="note">Meta <?=number_format((int)($g['contact_goal']??0),0,',','.')?></span></div></article>
     </div>
     <div class="tdd-performance single"><section class="tdd-card"><div class="tdd-card-head"><div class="tdd-card-title"><span class="blue"><i class="fa-solid fa-hand-holding-dollar"></i></span><div><strong>Atingimento da cobrança</strong><small><?=e($periodLabel)?></small></div></div><span class="tdd-percent"><?=number_format((float)($resultData['collection_percent']??0),1,',','.')?>%</span></div><div class="tdd-card-body"><div class="tdd-value"><strong><?=money($resultData['recovered']??0)?></strong><span>de <?=money($g['collection_goal']??0)?></span></div><div class="tdd-progress blue"><span style="width:<?=min(100,(float)($resultData['collection_percent']??0))?>%"></span></div><div class="tdd-card-foot"><span>Clientes trabalhados <strong><?=number_format((int)($data['worked']??0),0,',','.')?></strong></span><span>Ações no período <strong><?=number_format((int)($resultData['contacts']??0),0,',','.')?></strong></span></div></div></section></div>

    <?php else:
      $area=$resultArea??'commercial';
      $collectionMode=$area==='collection';
      $teamRows=[];
      if($collectionMode){
       foreach(($mg['collectors']??[]) as $row){
        $goal=(float)($row['goal']['collection_goal']??0);
        $recovered=(float)($row['recovered']??0);
        $teamRows[]=[
         'key'=>'u:'.(int)($row['user']['id']??0),
         'name'=>(string)($row['user']['name']??'Cobrança'),
         'subtitle'=>'Cobrança',
         'virtual'=>false,
         'sales'=>$recovered,
         'goal'=>$goal,
         'percent'=>(float)($row['collection_percent']??0),
         'orders'=>0.0,
         'services'=>0.0,
         'contacts'=>(int)($row['contacts']??0),
         'remaining'=>max(0,$goal-$recovered)
        ];
       }
      }else{
       foreach(($mg['sellers']??[]) as $row){
        $goal=(float)($row['goal']['sales_goal']??0);
        $teamRows[]=[
         'key'=>'u:'.(int)($row['user']['id']??0),
         'name'=>(string)($row['user']['name']??'Vendedor'),
         'subtitle'=>'Vendedor',
         'virtual'=>false,
         'sales'=>(float)($row['sales']??0),
         'goal'=>$goal,
         'percent'=>(float)($row['sales_percent']??0),
         'orders'=>(float)($row['orders_sales']??0),
         'services'=>(float)($row['services_sales']??0),
         'contacts'=>(int)($row['contacts']??0),
         'remaining'=>max(0,$goal-(float)($row['sales']??0))
        ];
       }
       foreach(($mg['virtual_sellers']??[]) as $row){
        $goal=(float)($row['goal']['sales_goal']??0);
        $teamRows[]=[
         'key'=>'v:'.(string)($row['seller']['omie_code']??''),
         'name'=>(string)($row['seller']['name']??'Vendedor virtual'),
         'subtitle'=>!empty($row['ead_reciclagem'])?'Vendas online':'Canal automático',
         'virtual'=>true,
         'ead'=>!empty($row['ead_reciclagem']),
         'sales'=>(float)($row['sales']??0),
         'goal'=>$goal,
         'percent'=>(float)($row['sales_percent']??0),
         'orders'=>(float)($row['orders']??0),
         'services'=>(float)($row['services']??0),
         'contacts'=>0,
         'remaining'=>max(0,$goal-(float)($row['sales']??0))
        ];
       }
      }
      usort($teamRows,static fn($a,$b)=>$b['sales']<=>$a['sales']);
      $model=$resultModel??'executive';
      $resultBase=['month'=>$month,'result_model'=>$model,'result_area'=>$area];
      if($selectedDays)$resultBase['days']=$selectedDays;
      $selectedKey=$resultSeller??'';
      $selectedSeller=null;
      foreach($teamRows as $candidate)if($candidate['key']===$selectedKey){$selectedSeller=$candidate;break;}
      if(!$selectedSeller&&$teamRows)$selectedSeller=$teamRows[0];
      $statusFor=static function(float $percent): array{
       if($percent>=100)return ['class'=>'success','label'=>'Acima da meta','icon'=>'fa-trophy'];
       if($percent>=90)return ['class'=>'near','label'=>'Muito próximo','icon'=>'fa-bullseye'];
       if($percent>=75)return ['class'=>'progress','label'=>'Em evolução','icon'=>'fa-chart-line'];
       return ['class'=>'danger','label'=>'Atenção','icon'=>'fa-triangle-exclamation'];
      };
    ?>
     <div class="tdd-info-strip"><i class="fa-solid fa-circle-info"></i><span>O resultado comercial considera Pedidos OK + Serviços válidos. Pedidos entram com frete; cancelados, PDV e propostas/orçamentos ficam fora do resultado.</span></div>
     <div class="tdd-kpis">
      <article class="tdd-kpi green"><span class="tdd-kpi-icon"><i class="fa-solid fa-chart-column"></i></span><div><small>Resultado comercial</small><strong><?=money($mg['sales']??0)?></strong><span class="note ok"><?=number_format((float)($mg['sales_percent']??0),1,',','.')?>% da meta geral</span></div></article>
      <article class="tdd-kpi blue"><span class="tdd-kpi-icon"><i class="fa-solid fa-hand-holding-dollar"></i></span><div><small>Recuperado</small><strong><?=money($mg['recovered']??0)?></strong><span class="note"><?=number_format((float)($mg['collection_percent']??0),1,',','.')?>% da meta de cobrança</span></div></article>
      <article class="tdd-kpi red"><span class="tdd-kpi-icon"><i class="fa-solid fa-circle-dollar-to-slot"></i></span><div><small>Saldo em cobrança</small><strong><?=money($data['debt']??0)?></strong><span class="note">Financeiro em aberto</span></div></article>
      <article class="tdd-kpi yellow"><span class="tdd-kpi-icon"><i class="fa-solid fa-users"></i></span><div><small>Clientes ativos</small><strong><?=number_format((int)($data['clients']??0),0,',','.')?></strong><span class="note"><?=number_format((int)($data['late']??0),0,',','.')?> retorno(s) atrasado(s)</span></div></article>
     </div>

     <div class="tdd-performance">
      <section class="tdd-card">
       <div class="tdd-card-head"><div class="tdd-card-title"><span class="green"><i class="fa-solid fa-bullseye"></i></span><div><strong>Meta comercial</strong><small><?=e($periodLabel)?></small></div></div><span class="tdd-percent"><?=number_format((float)($mg['sales_percent']??0),1,',','.')?>%</span></div>
       <div class="tdd-card-body"><div class="tdd-value"><strong><?=money($mg['sales']??0)?></strong><span>de <?=money($mg['effective_sales_goal']??0)?></span></div><div class="tdd-progress"><span style="width:<?=min(100,(float)($mg['sales_percent']??0))?>%"></span></div><div class="tdd-card-foot"><span>Pedidos <strong><?=money($mg['order_sales']??0)?></strong></span><span>Serviços <strong><?=money($mg['service_sales']??0)?></strong></span><span>Contatos <strong><?=number_format((int)($mg['contacts']??0),0,',','.')?></strong></span></div></div>
      </section>
      <section class="tdd-card">
       <div class="tdd-card-head"><div class="tdd-card-title"><span class="blue"><i class="fa-solid fa-hand-holding-dollar"></i></span><div><strong>Meta de cobrança</strong><small><?=e($periodLabel)?></small></div></div><span class="tdd-percent"><?=number_format((float)($mg['collection_percent']??0),1,',','.')?>%</span></div>
       <div class="tdd-card-body"><div class="tdd-value"><strong><?=money($mg['recovered']??0)?></strong><span>de <?=money($mg['effective_collection_goal']??0)?></span></div><div class="tdd-progress blue"><span style="width:<?=min(100,(float)($mg['collection_percent']??0))?>%"></span></div><div class="tdd-card-foot"><span>Meta de contatos <strong><?=number_format((int)($mg['effective_contact_goal']??0),0,',','.')?></strong></span><span>Realizados <strong><?=number_format((int)($mg['contacts']??0),0,',','.')?></strong></span></div></div>
      </section>
     </div>

     <div class="tdd-section-head tdd-analysis-head"><div><span class="tdd-kicker">ANÁLISE DETALHADA</span><h2>Resultados da equipe</h2><p>Alterne entre Comercial e Cobrança sem perder a visão executiva do dashboard.</p></div></div>
     <section class="tdres" id="resultados">
      <div class="tdres-toolbar">
       <div><span class="tdd-kicker">GESTÃO / RESULTADOS</span><h2><?=$collectionMode?'Resultados de cobrança':'Resultados comerciais'?></h2><p><?=$collectionMode?'Acompanhe recuperação, metas e desempenho da equipe de cobrança.':'Analise a equipe comercial em diferentes formatos usando os mesmos dados do período.'?></p></div>
       <div class="tdres-toolbar-actions">
        <div class="tdres-nav-group">
         <span class="tdres-nav-label">Área</span>
         <nav class="tdres-area-tabs" aria-label="Área de resultados">
          <?php $qCommercial=['month'=>$month,'result_area'=>'commercial','result_model'=>$model];$qCollection=['month'=>$month,'result_area'=>'collection'];if($selectedDays){$qCommercial['days']=$selectedDays;$qCollection['days']=$selectedDays;}?>
          <a class="<?=!$collectionMode?'active':''?>" href="<?=APP_URL?>/?<?=e(http_build_query($qCommercial))?>#resultados"><i class="fa-solid fa-chart-line"></i>Comercial</a>
          <a class="<?=$collectionMode?'active':''?>" href="<?=APP_URL?>/?<?=e(http_build_query($qCollection))?>#resultados"><i class="fa-solid fa-hand-holding-dollar"></i>Cobrança</a>
         </nav>
        </div>
        <?php if(!$collectionMode):?>
        <div class="tdres-nav-group secondary">
         <span class="tdres-nav-label">Visualização</span>
         <nav class="tdres-models" aria-label="Modelos de visualização">
          <?php foreach(['executive'=>['fa-table-list','Executivo'],'cards'=>['fa-grip','Cards'],'compare'=>['fa-chart-column','Comparativo'],'detail'=>['fa-address-card','Detalhe']] as $mode=>$meta):$query=['month'=>$month,'result_area'=>'commercial','result_model'=>$mode];if($selectedDays)$query['days']=$selectedDays;?>
           <a class="<?=$model===$mode?'active':''?>" href="<?=APP_URL?>/?<?=e(http_build_query($query))?>#resultados"><i class="fa-solid <?=$meta[0]?>"></i><span><?=$meta[1]?></span></a>
          <?php endforeach;?>
         </nav>
        </div>
        <?php endif;?>
       </div>
      </div>

      <div class="tdres-kpis">
       <?php if($collectionMode):?>
        <article><span class="green"><i class="fa-solid fa-bullseye"></i></span><div><small>Meta de recuperação</small><strong><?=money($mg['effective_collection_goal']??0)?></strong><em><?=e($periodLabel)?></em></div></article>
        <article><span class="green"><i class="fa-solid fa-money-bill-trend-up"></i></span><div><small>Recuperado</small><strong><?=money($mg['recovered']??0)?></strong><em>Pagamentos registrados</em></div></article>
        <article><span class="orange"><i class="fa-solid fa-circle-dollar-to-slot"></i></span><div><small>Saldo em aberto</small><strong><?=money($data['debt']??0)?></strong><em>Carteira atual</em></div></article>
        <article><span class="blue"><i class="fa-solid fa-headset"></i></span><div><small>Ações de cobrança</small><strong><?=number_format((int)($mg['contacts']??0),0,',','.')?></strong><em>Contatos no período</em></div></article>
        <article class="achievement"><div class="tdres-ring" style="--p:<?=min(100,max(0,(float)($mg['collection_percent']??0)))?>"><b><?=number_format((float)($mg['collection_percent']??0),1,',','.')?>%</b></div><div><small>Atingimento</small><strong><?=number_format((float)($mg['collection_percent']??0),1,',','.')?>%</strong><em><?=((float)($mg['effective_collection_goal']??0)>(float)($mg['recovered']??0))?'Faltam '.money((float)$mg['effective_collection_goal']-(float)$mg['recovered']):'Meta atingida'?></em></div></article>
       <?php else:?>
        <article><span class="green"><i class="fa-solid fa-bullseye"></i></span><div><small>Meta geral</small><strong><?=money($mg['effective_sales_goal']??0)?></strong><em><?=e($periodLabel)?></em></div></article>
        <article><span class="green"><i class="fa-solid fa-chart-column"></i></span><div><small>Resultado comercial</small><strong><?=money($mg['sales']??0)?></strong><em>Pedidos + serviços</em></div></article>
        <article><span class="blue"><i class="fa-regular fa-file-lines"></i></span><div><small>Pedidos</small><strong><?=money($mg['order_sales']??0)?></strong><em>Pedidos OK</em></div></article>
        <article><span class="orange"><i class="fa-solid fa-screwdriver-wrench"></i></span><div><small>Serviços</small><strong><?=money($mg['service_sales']??0)?></strong><em>Serviços válidos</em></div></article>
        <article class="achievement"><div class="tdres-ring" style="--p:<?=min(100,max(0,(float)($mg['sales_percent']??0)))?>"><b><?=number_format((float)($mg['sales_percent']??0),1,',','.')?>%</b></div><div><small>Atingimento</small><strong><?=number_format((float)($mg['sales_percent']??0),1,',','.')?>%</strong><em><?=((float)($mg['effective_sales_goal']??0)>(float)($mg['sales']??0))?'Faltam '.money((float)$mg['effective_sales_goal']-(float)$mg['sales']):'Meta atingida'?></em></div></article>
       <?php endif;?>
      </div>

      <?php if($collectionMode):?>
       <?php if(!$teamRows):?>
        <div class="tdres-empty"><i class="fa-solid fa-user-slash"></i><strong>Nenhum cobrador com meta ou resultado no período.</strong></div>
       <?php else:?>
        <section class="tdres-collection-overview">
         <div class="tdres-collection-cards">
          <?php foreach($teamRows as $row):$status=$statusFor($row['percent']);?>
           <article>
            <div class="head"><span><?=e(mb_strtoupper(mb_substr($row['name'],0,1)))?></span><div><strong><?=e($row['name'])?></strong><small>Equipe de cobrança</small></div><b class="tdres-status <?=$status['class']?>"><?=$status['label']?></b></div>
            <div class="money"><small>Recuperado</small><strong><?=money($row['sales'])?></strong><span>Meta <?=money($row['goal'])?></span></div>
            <div class="tdres-progress"><span style="width:<?=min(100,$row['percent'])?>%"></span></div>
            <footer><span><small>Atingimento</small><b><?=number_format($row['percent'],1,',','.')?>%</b></span><span><small>Falta</small><b><?=money($row['remaining'])?></b></span><span><small>Ações</small><b><?=number_format($row['contacts'],0,',','.')?></b></span></footer>
           </article>
          <?php endforeach;?>
         </div>
        </section>

        <div class="tdres-bottom-grid">
         <section class="tdres-panel">
          <header><div><i class="fa-solid fa-list-check"></i><span><strong>Desempenho da cobrança</strong><small>Meta, recuperação e produtividade por responsável.</small></span></div></header>
          <div class="tdres-table-wrap"><table class="tdres-table"><thead><tr><th>Cobrador</th><th>Meta</th><th>Recuperado</th><th>Falta</th><th>Ações</th><th>Atingimento</th><th>Status</th></tr></thead><tbody>
          <?php foreach($teamRows as $row):$status=$statusFor($row['percent']);?>
           <tr><td><div class="tdres-person"><span><?=e(mb_strtoupper(mb_substr($row['name'],0,1)))?></span><div><strong><?=e($row['name'])?></strong><small>Cobrança</small></div></div></td><td><?=money($row['goal'])?></td><td><strong class="tdres-money"><?=money($row['sales'])?></strong></td><td><?=money($row['remaining'])?></td><td><?=number_format($row['contacts'],0,',','.')?></td><td><div class="tdres-progress"><span style="width:<?=min(100,$row['percent'])?>%"></span></div><b><?=number_format($row['percent'],1,',','.')?>%</b></td><td><span class="tdres-status <?=$status['class']?>"><i class="fa-solid <?=$status['icon']?>"></i><?=$status['label']?></span></td></tr>
          <?php endforeach;?>
          </tbody></table></div>
         </section>

         <section class="tdres-panel attention">
          <header><div><i class="fa-solid fa-triangle-exclamation"></i><span><strong>Pontos de atenção</strong><small>Responsáveis abaixo de 85% da meta de recuperação.</small></span></div></header>
          <div class="tdres-attention-list"><?php $attention=array_values(array_filter($teamRows,static fn($row)=>$row['percent']<85));foreach(array_slice($attention,0,8) as $row):?><div><strong><?=e($row['name'])?></strong><span><?=number_format($row['percent'],1,',','.')?>%</span><em>Faltam <?=money($row['remaining'])?></em></div><?php endforeach;?><?php if(!$attention):?><div class="empty">Nenhum cobrador abaixo de 85%.</div><?php endif;?></div>
         </section>
        </div>
       <?php endif;?>
      <?php else:?>
      <?php if(!$teamRows):?>
       <div class="tdres-empty"><i class="fa-solid fa-users-slash"></i><strong>Nenhum vendedor com resultado no período.</strong></div>
      <?php elseif($model==='executive'):?>
       <section class="tdres-panel">
        <header><div><i class="fa-solid fa-users"></i><span><strong>Desempenho dos vendedores</strong><small>Visão executiva consolidada da equipe.</small></span></div></header>
        <div class="tdres-table-wrap"><table class="tdres-table"><thead><tr><th>#</th><th>Vendedor</th><th>Meta</th><th>Realizado</th><th>Pedidos</th><th>Serviços</th><th>Contatos</th><th>Atingimento</th><th>Status</th></tr></thead><tbody>
        <?php foreach($teamRows as $idx=>$row):$status=$statusFor($row['percent']);?>
         <tr><td><?=($idx+1)?></td><td><div class="tdres-person"><span class="<?=$row['virtual']?'virtual':''?>"><?=e(mb_strtoupper(mb_substr($row['name'],0,1)))?></span><div><strong><?=e($row['name'])?></strong><small><?=e($row['subtitle'])?></small></div></div></td><td><?=money($row['goal'])?></td><td><strong class="tdres-money"><?=$row['sales']>=$row['goal']&&$row['goal']>0?'':''?><?=money($row['sales'])?></strong></td><td><?=money($row['orders'])?></td><td><?=money($row['services'])?></td><td><?=number_format($row['contacts'],0,',','.')?></td><td><div class="tdres-progress"><span style="width:<?=min(100,$row['percent'])?>%"></span></div><b><?=number_format($row['percent'],1,',','.')?>%</b></td><td><span class="tdres-status <?=$status['class']?>"><i class="fa-solid <?=$status['icon']?>"></i><?=$status['label']?></span></td></tr>
        <?php endforeach;?>
        </tbody></table></div>
       </section>

       <div class="tdres-bottom-grid">
        <section class="tdres-panel attention"><header><div><i class="fa-solid fa-triangle-exclamation"></i><span><strong>Pontos de atenção</strong><small>Vendedores abaixo de 85% da meta.</small></span></div></header><div class="tdres-attention-list">
        <?php $attention=array_values(array_filter($teamRows,static fn($row)=>$row['percent']<85));foreach(array_slice($attention,0,5) as $row):?>
         <div><strong><?=e($row['name'])?></strong><span><?=number_format($row['percent'],1,',','.')?>%</span><em>Faltam <?=money(max(0,$row['goal']-$row['sales']))?></em></div>
        <?php endforeach;?><?php if(!$attention):?><div class="empty">Nenhum vendedor abaixo de 85%.</div><?php endif;?>
        </div></section>
        <section class="tdres-panel"><header><div><i class="fa-solid fa-ranking-star"></i><span><strong>Participação no resultado</strong><small>Distribuição do realizado entre os vendedores.</small></span></div></header><div class="tdres-share">
        <?php $totalSales=max(0.01,(float)($mg['sales']??0));foreach(array_slice($teamRows,0,6) as $row):$share=$row['sales']/$totalSales*100;?>
         <div><span><?=e($row['name'])?></span><div><i style="width:<?=min(100,$share)?>%"></i></div><strong><?=number_format($share,1,',','.')?>%</strong></div>
        <?php endforeach;?>
        </div></section>
       </div>

      <?php elseif($model==='cards'):?>
       <section class="tdres-panel">
        <header><div><i class="fa-solid fa-chart-simple"></i><span><strong>Painel comercial da equipe</strong><small>Desempenho individual no período selecionado.</small></span></div></header>
        <div class="tdres-card-grid">
        <?php foreach($teamRows as $row):$status=$statusFor($row['percent']);?>
         <article class="tdres-seller-card">
          <div class="tdres-seller-head"><span class="avatar <?=$row['virtual']?'virtual':''?>"><?=e(mb_strtoupper(mb_substr($row['name'],0,1)))?></span><div><strong><?=e($row['name'])?></strong><small><?=e($row['subtitle'])?></small></div><i class="state"></i></div>
          <div class="tdres-seller-main"><div class="tdres-ring large" style="--p:<?=min(100,max(0,$row['percent']))?>"><b><?=number_format($row['percent'],0,',','.')?>%</b><small>Atingimento</small></div><div><small>Meta</small><strong><?=money($row['goal'])?></strong><small>Realizado</small><strong class="green"><?=money($row['sales'])?></strong></div></div>
          <div class="tdres-seller-stats"><span><i class="fa-regular fa-file-lines"></i><small>Pedidos</small><strong><?=money($row['orders'])?></strong></span><span><i class="fa-solid fa-screwdriver-wrench"></i><small>Serviços</small><strong><?=money($row['services'])?></strong></span><span><i class="fa-solid fa-users"></i><small>Contatos</small><strong><?=number_format($row['contacts'],0,',','.')?></strong></span></div>
          <div class="tdres-progress"><span style="width:<?=min(100,$row['percent'])?>%"></span></div>
         </article>
        <?php endforeach;?>
        </div>
       </section>
       <div class="tdres-bottom-grid">
        <section class="tdres-panel"><header><div><i class="fa-solid fa-chart-line"></i><span><strong>Evolução do período</strong><small>Distribuição visual do resultado da equipe.</small></span></div></header><div class="tdres-bars"><?php foreach(array_slice($teamRows,0,10) as $row):?><div title="<?=e($row['name'])?>"><i style="height:<?=max(12,min(100,$row['percent']))?>%"></i><span><?=e(mb_substr($row['name'],0,8))?></span></div><?php endforeach;?></div></section>
        <section class="tdres-panel"><header><div><i class="fa-solid fa-chart-pie"></i><span><strong>Distribuição do resultado</strong><small>Participação no faturamento do período.</small></span></div></header><div class="tdres-share"><?php $totalSales=max(0.01,(float)($mg['sales']??0));foreach(array_slice($teamRows,0,6) as $row):$share=$row['sales']/$totalSales*100;?><div><span><?=e($row['name'])?></span><div><i style="width:<?=min(100,$share)?>%"></i></div><strong><?=number_format($share,1,',','.')?>%</strong></div><?php endforeach;?></div></section>
       </div>

      <?php elseif($model==='compare'):?>
       <section class="tdres-panel">
        <header><div><i class="fa-solid fa-chart-column"></i><span><strong>Resultado por vendedor</strong><small>Cards resumidos para comparação rápida.</small></span></div></header>
        <div class="tdres-compare-cards">
        <?php foreach($teamRows as $row):?>
         <article><div class="top"><span><?=e(mb_strtoupper(mb_substr($row['name'],0,1)))?></span><div><strong><?=e($row['name'])?></strong><small><?=e($row['subtitle'])?></small></div><b><?=number_format($row['percent'],1,',','.')?>%</b></div><div class="numbers"><span>Meta <strong><?=money($row['goal'])?></strong></span><span>Realizado <strong><?=money($row['sales'])?></strong></span></div><div class="tdres-progress"><span style="width:<?=min(100,$row['percent'])?>%"></span></div><footer><span>Pedidos <b><?=money($row['orders'])?></b></span><span>Serviços <b><?=money($row['services'])?></b></span><span>Contatos <b><?=number_format($row['contacts'],0,',','.')?></b></span></footer></article>
        <?php endforeach;?>
        </div>
       </section>
       <section class="tdres-panel">
        <header><div><i class="fa-regular fa-table"></i><span><strong>Comparativo de vendedores</strong><small>Visão consolidada dos resultados no período.</small></span></div></header>
        <div class="tdres-table-wrap"><table class="tdres-table"><thead><tr><th>Vendedor</th><th>Meta</th><th>Realizado</th><th>Atingimento</th><th>Pedidos</th><th>Serviços</th><th>Contatos</th></tr></thead><tbody><?php foreach($teamRows as $row):?><tr><td><strong><?=e($row['name'])?></strong></td><td><?=money($row['goal'])?></td><td><strong><?=money($row['sales'])?></strong></td><td><div class="tdres-progress"><span style="width:<?=min(100,$row['percent'])?>%"></span></div><b><?=number_format($row['percent'],1,',','.')?>%</b></td><td><?=money($row['orders'])?></td><td><?=money($row['services'])?></td><td><?=number_format($row['contacts'],0,',','.')?></td></tr><?php endforeach;?></tbody><tfoot><tr><th>Total da equipe</th><th><?=money($mg['effective_sales_goal']??0)?></th><th><?=money($mg['sales']??0)?></th><th><?=number_format((float)($mg['sales_percent']??0),1,',','.')?>%</th><th><?=money($mg['order_sales']??0)?></th><th><?=money($mg['service_sales']??0)?></th><th><?=number_format((int)($mg['contacts']??0),0,',','.')?></th></tr></tfoot></table></div>
       </section>

      <?php else:?>
       <div class="tdres-detail-layout">
        <aside class="tdres-seller-list"><header><strong>Vendedores</strong><small>Selecione para analisar</small></header><?php foreach($teamRows as $row):$q=['month'=>$month,'result_area'=>'commercial','result_model'=>'detail','seller'=>$row['key']];if($selectedDays)$q['days']=$selectedDays;?><a class="<?=$selectedSeller&&$selectedSeller['key']===$row['key']?'active':''?>" href="<?=APP_URL?>/?<?=e(http_build_query($q))?>#resultados"><span><?=e(mb_strtoupper(mb_substr($row['name'],0,1)))?></span><div><strong><?=e($row['name'])?></strong><b><?=money($row['sales'])?></b><small>Meta: <?=money($row['goal'])?></small></div><em><?=number_format($row['percent'],0,',','.')?>%</em></a><?php endforeach;?></aside>
        <?php if($selectedSeller):$status=$statusFor($selectedSeller['percent']);?>
        <section class="tdres-detail">
         <header><div class="person"><span><?=e(mb_strtoupper(mb_substr($selectedSeller['name'],0,1)))?></span><div><strong><?=e($selectedSeller['name'])?></strong><small><?=e($selectedSeller['subtitle'])?></small></div><b class="tdres-status <?=$status['class']?>"><?=$status['label']?></b></div><small><?=e($periodLabel)?></small></header>
         <div class="tdres-detail-kpis"><article class="primary"><small>Resultado total</small><strong><?=money($selectedSeller['sales'])?></strong><em><?=number_format($selectedSeller['percent'],1,',','.')?>% da meta</em></article><article><i class="fa-regular fa-file-lines"></i><small>Pedidos</small><strong><?=money($selectedSeller['orders'])?></strong></article><article><i class="fa-solid fa-screwdriver-wrench"></i><small>Serviços</small><strong><?=money($selectedSeller['services'])?></strong></article><article><i class="fa-solid fa-users"></i><small>Contatos</small><strong><?=number_format($selectedSeller['contacts'],0,',','.')?></strong></article></div>
         <div class="tdres-detail-bottom"><article><small>Atingimento da meta</small><strong><?=number_format($selectedSeller['percent'],1,',','.')?>%</strong><div class="tdres-progress"><span style="width:<?=min(100,$selectedSeller['percent'])?>%"></span></div><p><b><?=money($selectedSeller['sales'])?></b> de <?=money($selectedSeller['goal'])?></p></article><article><small>Composição do resultado</small><div class="tdres-composition"><span><b>Pedidos</b><strong><?=money($selectedSeller['orders'])?></strong><i style="width:<?=$selectedSeller['sales']>0?min(100,$selectedSeller['orders']/$selectedSeller['sales']*100):0?>%"></i></span><span><b>Serviços</b><strong><?=money($selectedSeller['services'])?></strong><i style="width:<?=$selectedSeller['sales']>0?min(100,$selectedSeller['services']/$selectedSeller['sales']*100):0?>%"></i></span></div></article></div>
        </section>
        <?php endif;?>
       </div>
      <?php endif;?>
      <?php endif;?>
     </section>
    <?php endif;?>   <div class="tdd-section-head"><div><span class="tdd-kicker">OPERAÇÃO</span><h2>Acessos rápidos</h2><p>As rotinas continuam separadas; somente Dashboard e Resultados foram unificados.</p></div></div>
    <div class="tdd-actions">
     <?php if($u['role']==='seller'):?><a class="tdd-action green" href="<?=APP_URL?>/orders/new"><span><i class="fa-solid fa-plus"></i></span><div><strong>Novo pedido</strong><small>Criar e enviar para Omie</small></div><i class="fa-solid fa-arrow-right"></i></a><a class="tdd-action blue" href="<?=APP_URL?>/my-portfolio"><span><i class="fa-solid fa-briefcase"></i></span><div><strong>Minha Carteira</strong><small>Clientes vinculados a você</small></div><i class="fa-solid fa-arrow-right"></i></a><a class="tdd-action yellow" href="<?=APP_URL?>/agenda"><span><i class="fa-regular fa-calendar"></i></span><div><strong>Agenda</strong><small>Retornos e compromissos</small></div><i class="fa-solid fa-arrow-right"></i></a>
     <?php elseif($u['role']==='collector'):?><a class="tdd-action red" href="<?=APP_URL?>/collection"><span><i class="fa-solid fa-hand-holding-dollar"></i></span><div><strong>Cobrança</strong><small>Priorizar devedores</small></div><i class="fa-solid fa-arrow-right"></i></a><a class="tdd-action yellow" href="<?=APP_URL?>/agenda"><span><i class="fa-regular fa-calendar"></i></span><div><strong>Agenda</strong><small>Promessas e retornos</small></div><i class="fa-solid fa-arrow-right"></i></a>
     <?php else:?><a class="tdd-action blue" href="<?=APP_URL?>/orders"><span><i class="fa-solid fa-receipt"></i></span><div><strong>Pedidos</strong><small>Produção comercial</small></div><i class="fa-solid fa-arrow-right"></i></a><a class="tdd-action yellow" href="<?=APP_URL?>/services"><span><i class="fa-solid fa-screwdriver-wrench"></i></span><div><strong>Serviços</strong><small>Ordens de serviço</small></div><i class="fa-solid fa-arrow-right"></i></a><a class="tdd-action red" href="<?=APP_URL?>/collection"><span><i class="fa-solid fa-hand-holding-dollar"></i></span><div><strong>Cobrança</strong><small>Carteira financeira</small></div><i class="fa-solid fa-arrow-right"></i></a><a class="tdd-action green" href="<?=APP_URL?>/goals"><span><i class="fa-solid fa-bullseye"></i></span><div><strong>Metas</strong><small>Configurar objetivos</small></div><i class="fa-solid fa-arrow-right"></i></a><?php endif;?>
    </div>
   </section>
  <?php break;
  case 'client_sync':
   $syncRows=$syncRows??[];$syncStats=$syncStats??[];$syncStatus=$syncStatus??'all';$syncQuery=$syncQuery??'';$syncPagination=$syncPagination??['page'=>1,'pages'=>1,'total'=>0,'from'=>0,'to'=>0];
   $syncUrl=static function(array $changes=[])use($syncStatus,$syncQuery){$params=['status'=>$syncStatus,'q'=>$syncQuery];foreach($changes as $key=>$value){if($value===null||$value==='')unset($params[$key]);else $params[$key]=$value;}return APP_URL.'/clients-sync?'.http_build_query($params);};
   $syncLabels=['all'=>'Todos','pending'=>'Pendentes','local'=>'Somente CRM','divergent'=>'CRM ≠ Omie','error'=>'Com erro'];
   ?>
   <section class="tdc-page tdc-sync-center" data-client-sync-hub data-endpoint="<?=APP_URL?>/api/clients-sync/bulk" data-csrf="<?=CSRF::token()?>" data-total="<?=(int)$syncPagination['total']?>" data-status="<?=e($syncStatus)?>" data-query="<?=e($syncQuery)?>">
    <nav class="tdc-hub-nav" aria-label="Central de clientes">
     <a href="<?=APP_URL?>/clients"><i class="fa-solid fa-layer-group"></i><span><strong>Todos</strong><small>Base ativa completa</small></span></a>
     <a href="<?=APP_URL?>/clients?segment=general"><i class="fa-solid fa-briefcase"></i><span><strong>Comercial</strong><small>Clientes comerciais</small></span></a>
     <a href="<?=APP_URL?>/clients?segment=ead_reciclagem"><i class="fa-solid fa-graduation-cap"></i><span><strong>EAD Reciclagem</strong><small>Operação virtual</small></span></a>
     <a href="<?=APP_URL?>/clients?segment=suporte_pet"><i class="fa-solid fa-paw"></i><span><strong>Suporte PET</strong><small>Operação virtual</small></span></a>
     <a class="operational supplier" href="<?=APP_URL?>/clients?segment=supplier"><i class="fa-solid fa-boxes-packing"></i><span><strong>Fornecedores</strong><small>Tag Fornecedor</small></span></a>
     <a class="operational carrier" href="<?=APP_URL?>/clients?segment=carrier"><i class="fa-solid fa-truck-fast"></i><span><strong>Transportadoras</strong><small>Tag Transportadora</small></span></a>
     <a class="active" href="<?=APP_URL?>/clients-sync"><i class="fa-solid fa-cloud-arrow-up"></i><span><strong>Sincronização</strong><small>Pendências e envios</small></span></a>
     <a href="<?=APP_URL?>/clients-audit"><i class="fa-solid fa-user-shield"></i><span><strong>Auditoria</strong><small>Qualidade e consistência</small></span></a>
    </nav>
    <header class="tdc-head">
     <div class="tdc-head-main"><span class="tdc-head-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span><div><span class="tdc-kicker">CLIENTES / OMIE</span><h1>Central de sincronização</h1><p>Veja o que ainda está diferente entre o CRM e a Omie, trate os erros e envie as correções com controle.</p></div></div>
     <div class="tdc-head-actions"><a class="tdc-btn" href="<?=e($syncUrl(['page'=>1]))?>"><i class="fa-solid fa-rotate-right"></i>Atualizar</a><a class="tdc-btn" href="<?=APP_URL?>/clients"><i class="fa-solid fa-arrow-left"></i>Base de clientes</a></div>
    </header>
    <?php if($flash):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>
    <div class="tdc-sync-kpis">
     <a class="<?=$syncStatus==='all'?'active':''?>" href="<?=e($syncUrl(['status'=>'all','page'=>1]))?>"><span><i class="fa-solid fa-layer-group"></i></span><div><small>Total para revisar</small><strong><?=number_format((int)($syncStats['total']??0),0,',','.')?></strong></div></a>
     <a class="<?=$syncStatus==='pending'?'active':''?>" href="<?=e($syncUrl(['status'=>'pending','page'=>1]))?>"><span><i class="fa-regular fa-clock"></i></span><div><small>Pendentes</small><strong><?=number_format((int)($syncStats['pending']??0),0,',','.')?></strong></div></a>
     <a class="<?=$syncStatus==='local'?'active':''?>" href="<?=e($syncUrl(['status'=>'local','page'=>1]))?>"><span><i class="fa-solid fa-database"></i></span><div><small>Somente CRM</small><strong><?=number_format((int)($syncStats['local']??0),0,',','.')?></strong></div></a>
     <a class="<?=$syncStatus==='divergent'?'active':''?>" href="<?=e($syncUrl(['status'=>'divergent','page'=>1]))?>"><span><i class="fa-solid fa-code-compare"></i></span><div><small>CRM ≠ Omie</small><strong><?=number_format((int)($syncStats['divergent']??0),0,',','.')?></strong></div></a>
     <a class="<?=$syncStatus==='error'?'active':''?>" href="<?=e($syncUrl(['status'=>'error','page'=>1]))?>"><span><i class="fa-solid fa-triangle-exclamation"></i></span><div><small>Com erro</small><strong><?=number_format((int)($syncStats['errors']??0),0,',','.')?></strong></div></a>
    </div>
    <section class="tdc-card tdc-sync-filter">
     <form method="get" action="<?=APP_URL?>/clients-sync"><label><span>Tipo de pendência</span><select class="form-select" name="status"><?php foreach($syncLabels as $code=>$label):?><option value="<?=e($code)?>" <?=$syncStatus===$code?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></label><label class="grow"><span>Localizar cliente</span><input class="form-control" name="q" value="<?=e($syncQuery)?>" placeholder="Nome, CPF/CNPJ, código Omie ou vendedor"></label><button class="tdc-btn tdc-btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i>Filtrar</button><?php if($syncQuery!==''||$syncStatus!=='all'):?><a class="tdc-btn" href="<?=APP_URL?>/clients-sync"><i class="fa-solid fa-xmark"></i>Limpar</a><?php endif;?></form>
    </section>
    <section class="tdc-card tdc-sync-bulk">
     <div class="tdc-card-head"><div class="tdc-card-title"><span><i class="fa-solid fa-list-check"></i></span><div><strong>Fila de tratamento</strong><small><?=number_format((int)$syncPagination['total'],0,',','.')?> registro(s) neste filtro. Selecione alguns ou envie todos os resultados filtrados.</small></div></div><div class="tdc-sync-selection"><span data-sync-count>0 selecionados</span><button class="tdc-btn" type="button" data-sync-select-filtered <?=!(int)$syncPagination['total']?'disabled':''?>>Selecionar todos os <?=number_format((int)$syncPagination['total'],0,',','.')?> filtrados</button><button class="tdc-btn" type="button" data-sync-clear hidden>Limpar</button><button class="tdc-btn tdc-btn-primary" type="button" data-sync-run disabled><i class="fa-solid fa-cloud-arrow-up"></i>Enviar selecionados</button></div></div>
     <div class="tdc-sync-progress" data-sync-progress hidden><span><i></i></span><strong data-sync-progress-text>Preparando envio...</strong></div>
     <div class="tdc-table-wrap"><table class="table tdc-sync-table"><thead><tr><th class="tdc-select-column"><label class="tdc-row-check"><input type="checkbox" data-sync-page><span></span></label></th><th>Cliente</th><th>Segmento</th><th>Vendedor principal CRM</th><th>Vendedor na Omie</th><th>Situação</th><th>Última alteração</th><th class="text-end">Ações</th></tr></thead><tbody>
     <?php foreach($syncRows as $row):$kind=(string)($row['sync_kind']??'pending');$statusLabel=$kind==='error'?'Erro no envio':($kind==='local'?'Somente no CRM':($kind==='divergent'?'CRM ≠ Omie':'Pendente'));?>
      <tr><td><label class="tdc-row-check"><input type="checkbox" data-sync-select value="<?=(int)$row['id']?>"><span></span></label></td>
       <td><div class="tdc-client-cell"><span class="tdc-avatar"><?=e(mb_strtoupper(mb_substr((string)$row['name'],0,1)))?></span><div><a href="<?=APP_URL?>/clients/<?=(int)$row['id']?>"><strong><?=e($row['name'])?></strong></a><small><?=e($row['document']?:'Documento não informado')?> · <?=e((string)$row['omie_code'])?></small></div></div></td>
       <td><span class="tdc-sync-segment"><?=e((string)$row['segment_label'])?></span></td><td><strong><?=e($row['principal_seller_name']?:($row['seller_omie_code']?:'Sem vendedor'))?></strong><small><?=e((string)($row['seller_omie_code']??''))?></small></td><td><strong><?=e($row['omie_seller_name']?:($row['omie_seller_code']?:'Sem vendedor'))?></strong><small><?=e((string)($row['omie_seller_code']??''))?></small></td>
       <td><div class="tdc-sync-status <?=$kind?>"><span><i class="fa-solid <?=$kind==='error'?'fa-triangle-exclamation':($kind==='local'?'fa-database':($kind==='divergent'?'fa-code-compare':'fa-clock'))?>"></i><?=e($statusLabel)?></span><?php if($kind==='error'&&!empty($row['sync_error'])):?><small title="<?=e((string)$row['sync_error'])?>"><?=e(mb_strimwidth((string)$row['sync_error'],0,115,'…'))?></small><?php elseif(!empty($row['sync_source'])):?><small><?=e((string)$row['sync_source'])?></small><?php endif;?></div></td><td><strong><?=!empty($row['updated_at'])?date('d/m/Y',strtotime((string)$row['updated_at'])):'—'?></strong><small><?=!empty($row['updated_at'])?date('H:i',strtotime((string)$row['updated_at'])):''?></small></td>
       <td><div class="tdc-actions"><a class="tdc-icon-btn view" href="<?=APP_URL?>/clients/<?=(int)$row['id']?>" title="Revisar cliente"><i class="fa-regular fa-eye"></i></a><a class="tdc-icon-btn edit" href="<?=APP_URL?>/clients/<?=(int)$row['id']?>/edit" title="Editar antes de enviar"><i class="fa-regular fa-pen-to-square"></i></a><button class="tdc-icon-btn sync" type="button" data-sync-one="<?=(int)$row['id']?>" title="Sincronizar este cliente agora"><i class="fa-solid fa-cloud-arrow-up"></i></button></div></td></tr>
     <?php endforeach;?><?php if(!$syncRows):?><tr><td colspan="8"><div class="tdc-sync-empty"><i class="fa-solid fa-circle-check"></i><strong>Nenhuma pendência neste filtro</strong><p>Os clientes deste recorte não precisam de ação de sincronização.</p></div></td></tr><?php endif;?>
     </tbody></table></div>
     <?php if((int)$syncPagination['pages']>1):?><nav class="tdc-sync-pager"><a class="tdc-btn <?=(int)$syncPagination['page']<=1?'disabled':''?>" href="<?=e($syncUrl(['page'=>max(1,(int)$syncPagination['page']-1)]))?>"><i class="fa-solid fa-chevron-left"></i>Anterior</a><span>Mostrando <?=number_format((int)$syncPagination['from'],0,',','.')?>–<?=number_format((int)$syncPagination['to'],0,',','.')?> de <?=number_format((int)$syncPagination['total'],0,',','.')?> · página <?=(int)$syncPagination['page']?> de <?=(int)$syncPagination['pages']?></span><a class="tdc-btn <?=(int)$syncPagination['page']>=(int)$syncPagination['pages']?'disabled':''?>" href="<?=e($syncUrl(['page'=>min((int)$syncPagination['pages'],(int)$syncPagination['page']+1)]))?>">Próxima<i class="fa-solid fa-chevron-right"></i></a></nav><?php endif;?>
    </section>
   </section>
  <?php break;
  case 'client_audit':
   $auditStats=$auditStats??[];$auditGroups=$auditGroups??[];$auditRowsByDocument=$auditRowsByDocument??[];$auditInactiveRows=$auditInactiveRows??[];$auditResponsibilityRows=$auditResponsibilityRows??[];$auditPagination=$auditPagination??['page'=>1,'pages'=>1,'total'=>0,'from'=>0,'to'=>0];
   $auditTab=$auditTab??'duplicates';$auditQuery=$auditQuery??'';$auditConflict=$auditConflict??'all';$auditMonth=$auditMonth??date('Y-m');$auditMonthLabel=date('m/Y',strtotime($auditMonth.'-01'));
   $auditUrl=static function(array $changes=[])use($auditTab,$auditQuery,$auditConflict,$auditMonth){$params=['tab'=>$auditTab,'q'=>$auditQuery,'conflict'=>$auditConflict,'month'=>$auditMonth];foreach($changes as $key=>$value){if($value===null||$value==='')unset($params[$key]);else $params[$key]=$value;}return APP_URL.'/clients-audit?'.http_build_query($params);};
   ?>
   <section class="tdaudit-page">
    <nav class="tdc-hub-nav" aria-label="Central de clientes">
     <a href="<?=APP_URL?>/clients"><i class="fa-solid fa-layer-group"></i><span><strong>Todos</strong><small>Base ativa completa</small></span></a>
     <a href="<?=APP_URL?>/clients?segment=general"><i class="fa-solid fa-briefcase"></i><span><strong>Comercial</strong><small>Clientes comerciais</small></span></a>
     <a href="<?=APP_URL?>/clients?segment=ead_reciclagem"><i class="fa-solid fa-graduation-cap"></i><span><strong>EAD Reciclagem</strong><small>Operação virtual</small></span></a>
     <a href="<?=APP_URL?>/clients?segment=suporte_pet"><i class="fa-solid fa-paw"></i><span><strong>Suporte PET</strong><small>Operação virtual</small></span></a>
     <a class="operational supplier" href="<?=APP_URL?>/clients?segment=supplier"><i class="fa-solid fa-boxes-packing"></i><span><strong>Fornecedores</strong><small>Tag Fornecedor</small></span></a>
     <a class="operational carrier" href="<?=APP_URL?>/clients?segment=carrier"><i class="fa-solid fa-truck-fast"></i><span><strong>Transportadoras</strong><small>Tag Transportadora</small></span></a>
     <a href="<?=APP_URL?>/clients-sync"><i class="fa-solid fa-cloud-arrow-up"></i><span><strong>Sincronização</strong><small>Pendências e envios</small></span></a>
     <a class="active" href="<?=APP_URL?>/clients-audit"><i class="fa-solid fa-user-shield"></i><span><strong>Auditoria</strong><small>Qualidade e consistência</small></span></a>
    </nav>
    <header class="tdaudit-head">
     <div class="tdaudit-head-main"><span class="tdaudit-head-icon"><i class="fa-solid fa-user-shield"></i></span><div><span class="tdaudit-kicker">QUALIDADE DA BASE / CLIENTES</span><h1>Auditoria de cadastros</h1><p>Revise duplicidades, responsabilidade comercial, divergências com a Omie e registros preservados.</p></div></div>
     <div class="tdaudit-head-actions"><a class="tdaudit-btn" href="<?=APP_URL?>/clients"><i class="fa-solid fa-arrow-left"></i>Voltar aos clientes</a><a class="tdaudit-btn primary" href="<?=e($auditUrl(['page'=>1]))?>"><i class="fa-solid fa-rotate-right"></i>Atualizar análise</a></div>
    </header>

    <div class="tdaudit-note"><span><i class="fa-solid fa-shield-halved"></i></span><div><strong>Tela segura para conferência</strong><p>Nenhum cadastro é alterado ou excluído aqui. Primeiro identificamos o código correto; a correção será feita em uma etapa separada e controlada.</p></div></div>

    <div class="tdaudit-kpis">
     <article><span class="blue"><i class="fa-solid fa-address-book"></i></span><div><small>Total no CRM</small><strong><?=number_format((int)($auditStats['total_clients']??0),0,',','.')?></strong><p><?=number_format((int)($auditStats['active_clients']??0),0,',','.')?> ativos</p></div></article>
     <article><span class="orange"><i class="fa-solid fa-clone"></i></span><div><small>Grupos duplicados</small><strong><?=number_format((int)($auditStats['duplicate_groups']??0),0,',','.')?></strong><p><?=number_format((int)($auditStats['duplicate_rows']??0),0,',','.')?> cadastros envolvidos</p></div></article>
     <article><span class="red"><i class="fa-solid fa-code-compare"></i></span><div><small>CRM × Omie</small><strong><?=number_format((int)($auditStats['seller_divergences']??0),0,',','.')?></strong><p>vínculos para conferir</p></div></article>
     <article><span class="gray"><i class="fa-solid fa-box-archive"></i></span><div><small>Inativos preservados</small><strong><?=number_format((int)($auditStats['inactive_clients']??0),0,',','.')?></strong><p>histórico mantido no CRM</p></div></article>
    </div>

    <nav class="tdaudit-tabs" aria-label="Tipos de auditoria">
     <a class="<?=$auditTab==='duplicates'?'active':''?>" href="<?=e($auditUrl(['tab'=>'duplicates','page'=>1]))?>"><span><i class="fa-solid fa-clone"></i><b>Cadastros duplicados</b></span><em><?=number_format((int)($auditStats['duplicate_groups']??0),0,',','.')?> grupos</em></a>
     <a class="<?=$auditTab==='responsibility'?'active':''?>" href="<?=e($auditUrl(['tab'=>'responsibility','page'=>1]))?>"><span><i class="fa-solid fa-right-left"></i><b>Responsabilidade comercial</b></span><em><?=number_format((int)($auditStats['seller_divergences']??0),0,',','.')?> divergências</em></a>
     <a class="<?=$auditTab==='inactive'?'active':''?>" href="<?=e($auditUrl(['tab'=>'inactive','page'=>1]))?>"><span><i class="fa-solid fa-box-archive"></i><b>Registros preservados</b></span><em><?=number_format((int)($auditStats['inactive_clients']??0),0,',','.')?> inativos</em></a>
    </nav>

    <form class="tdaudit-filter" method="get" action="<?=APP_URL?>/clients-audit">
     <input type="hidden" name="tab" value="<?=e($auditTab)?>">
     <label class="tdaudit-search"><span>Buscar cadastro</span><div><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" value="<?=e($auditQuery)?>" placeholder="Nome, CPF/CNPJ ou código Omie"></div></label>
     <?php if($auditTab==='duplicates'):?><label><span>Tipo de conflito</span><select name="conflict" class="form-select"><option value="all">Todos os conflitos</option><option value="active" <?=$auditConflict==='active'?'selected':''?>>Múltiplos ativos</option><option value="mixed" <?=$auditConflict==='mixed'?'selected':''?>>Ativo + inativo</option><option value="invalid" <?=$auditConflict==='invalid'?'selected':''?>>Documento inválido</option></select></label><?php endif;?>
     <?php if($auditTab==='responsibility'):?><label><span>Mês da carteira</span><input class="form-control" type="month" name="month" value="<?=e($auditMonth)?>"></label><?php else:?><input type="hidden" name="month" value="<?=e($auditMonth)?>"><?php endif;?>
     <button class="tdaudit-btn primary" type="submit"><i class="fa-solid fa-filter"></i>Aplicar filtros</button>
     <?php if($auditQuery!==''||$auditConflict!=='all'):?><a class="tdaudit-btn" href="<?=APP_URL?>/clients-audit?<?=e(http_build_query(['tab'=>$auditTab,'month'=>$auditMonth]))?>"><i class="fa-solid fa-xmark"></i>Limpar</a><?php endif;?>
    </form>

    <?php if($auditTab==='duplicates'):?>
     <div class="tdaudit-section-head"><div><span>DUPLICIDADES ENCONTRADAS</span><h2>Compare os registros do mesmo documento</h2><p>Exibindo <?=$auditPagination['from']?>–<?=$auditPagination['to']?> de <?=number_format((int)$auditPagination['total'],0,',','.')?> grupos filtrados.</p></div><div class="tdaudit-legend"><span><i class="active"></i>Ativo</span><span><i class="inactive"></i>Inativo</span><span><i class="local"></i>Cadastro local</span></div></div>
     <div class="tdaudit-groups">
      <?php foreach($auditGroups as $group):$groupRows=$auditRowsByDocument[(string)$group['document_digits']]??[];$conflictLabel=$group['conflict_type']==='active'?'Múltiplos ativos':($group['conflict_type']==='mixed'?'Ativo + inativo':'Múltiplos inativos');?>
       <article class="tdaudit-group">
        <header>
         <div class="tdaudit-document"><span class="<?=!empty($group['document_valid'])?'valid':'invalid'?>"><i class="fa-solid <?=!empty($group['document_valid'])?'fa-id-card':'fa-circle-exclamation'?>"></i></span><div><small><?=e($group['document_type'])?> · GRUPO DUPLICADO</small><strong><?=e($group['document_formatted'])?></strong></div></div>
         <div class="tdaudit-group-badges"><span class="conflict <?=$group['conflict_type']?>"><?=e($conflictLabel)?></span><?php if(empty($group['document_valid'])):?><span class="invalid">Documento inválido</span><?php endif;?><span><?=count($groupRows)?> registros</span></div>
        </header>
        <div class="tdaudit-table-wrap"><table class="tdaudit-table"><thead><tr><th>Status</th><th>Cliente</th><th>Código Omie</th><th>Vendedor</th><th>Localização</th><th>Histórico</th><th>Atualizado</th><th>Ação</th></tr></thead><tbody>
         <?php foreach($groupRows as $row):$history=[];if(!empty($row['orders_history']))$history[]='Pedidos';if(!empty($row['services_history']))$history[]='Serviços';if(!empty($row['financial_history']))$history[]='Financeiro';if(!empty($row['crm_history']))$history[]='CRM';$isLocal=str_starts_with((string)$row['omie_code'],'LOCAL-');?>
          <tr class="<?=$row['active']?'is-active':'is-inactive'?>">
           <td><span class="tdaudit-status <?=$row['active']?'active':'inactive'?>"><i></i><?=$row['active']?'Ativo':'Inativo'?></span><?php if($isLocal):?><small class="tdaudit-origin local">Somente CRM</small><?php else:?><small class="tdaudit-origin">Omie</small><?php endif;?></td>
           <td><div class="tdaudit-client"><span><?=e(mb_strtoupper(mb_substr((string)$row['name'],0,1)))?></span><div><strong><?=e($row['name'])?></strong><small><?=e($row['legal_name']?:'Razão social não informada')?></small></div></div></td>
           <td><strong class="tdaudit-code"><?=e($row['omie_code'])?></strong><?php if(!empty($row['duplicate_of'])):?><small>Marcado como duplicado de <?=e($row['duplicate_of'])?></small><?php endif;?></td>
           <td><strong><?=e($row['seller_name']?:'Sem vendedor')?></strong><small><?=e($row['seller_omie_code']?:'Não vinculado')?></small></td>
           <td><strong><?=e($row['city']?:'—')?></strong><small><?=e($row['uf']?:'UF não informada')?></small></td>
           <td><?php if($history):?><div class="tdaudit-history"><?php foreach($history as $item):?><span><?=e($item)?></span><?php endforeach;?></div><?php else:?><span class="tdaudit-no-history">Sem histórico</span><?php endif;?></td>
           <td><strong><?=!empty($row['updated_at'])?date('d/m/Y',strtotime((string)$row['updated_at'])):'—'?></strong><small><?=!empty($row['updated_at'])?date('H:i',strtotime((string)$row['updated_at'])):''?></small></td>
           <td><a class="tdaudit-open" href="<?=APP_URL?>/clients/<?=(int)$row['id']?>" title="Abrir cadastro"><i class="fa-regular fa-folder-open"></i><span>Abrir</span></a></td>
          </tr>
         <?php endforeach;?></tbody></table></div>
       </article>
      <?php endforeach;?>
      <?php if(!$auditGroups):?><div class="tdaudit-empty"><span><i class="fa-solid fa-circle-check"></i></span><div><strong>Nenhum grupo encontrado</strong><p>Ajuste os filtros ou faça uma nova busca.</p></div></div><?php endif;?>
     </div>
     <?php if((int)$auditPagination['pages']>1):?><nav class="tdaudit-pager"><a class="tdaudit-btn <?=(int)$auditPagination['page']<=1?'disabled':''?>" href="<?=e($auditUrl(['page'=>max(1,(int)$auditPagination['page']-1)]))?>"><i class="fa-solid fa-chevron-left"></i>Anterior</a><span>Página <strong><?=(int)$auditPagination['page']?></strong> de <strong><?=(int)$auditPagination['pages']?></strong></span><a class="tdaudit-btn <?=(int)$auditPagination['page']>=(int)$auditPagination['pages']?'disabled':''?>" href="<?=e($auditUrl(['page'=>min((int)$auditPagination['pages'],(int)$auditPagination['page']+1)]))?>">Próxima<i class="fa-solid fa-chevron-right"></i></a></nav><?php endif;?>
    <?php elseif($auditTab==='responsibility'):?>
     <div class="tdaudit-section-head"><div><span>RESPONSABILIDADE COMERCIAL</span><h2>Principal, carteira <?=$auditMonthLabel?> e Omie</h2><p>A carteira do mês é provisória; o vendedor principal é definitivo no CRM e pode ser reconciliado com a Omie quando necessário.</p></div><div class="tdaudit-legend"><span><i class="active"></i>Alinhado</span><span><i class="local"></i>Carteira provisória</span><span><i class="inactive"></i>CRM ≠ Omie</span></div></div>
     <div class="tdaudit-inactive-card"><div class="tdaudit-table-wrap"><table class="tdaudit-table responsibility-list"><thead><tr><th>Cliente</th><th>Principal CRM</th><th>Carteira <?=$auditMonthLabel?></th><th>Omie</th><th>Situação</th><th>Atualizado</th><th>Ação</th></tr></thead><tbody>
      <?php foreach($auditResponsibilityRows as $row):$isDivergent=trim((string)($row['seller_omie_code']??''))!==trim((string)($row['omie_seller_code']??''));$hasMonthly=!empty($row['portfolio_assignment_id']);?>
       <tr>
        <td><div class="tdaudit-client"><span><?=e(mb_strtoupper(mb_substr((string)$row['name'],0,1)))?></span><div><strong><?=e($row['name'])?></strong><small><?=e($row['document']?:'Documento não informado')?> · <?=e(trim(($row['city']??'').' / '.($row['uf']??''),' /'))?></small></div></div></td>
        <td><strong><?=e($row['principal_seller_name']?:($row['seller_omie_code']?:'Sem vendedor'))?></strong><small>Vínculo definitivo no CRM</small></td>
        <td><strong><?=e($row['effective_seller_name']?:'Sem responsável')?></strong><small><?=$hasMonthly?'Exceção provisória':'Usando o principal'?></small></td>
        <td><strong><?=e($row['omie_seller_name']?:($row['omie_seller_code']?:'Sem vendedor'))?></strong><small><?=e($row['omie_seller_code']?:'Não informado')?></small></td>
        <td><div class="tdaudit-responsibility-status"><?php if($isDivergent):?><span class="danger"><i class="fa-solid fa-code-compare"></i> CRM ≠ Omie</span><?php else:?><span class="ok"><i class="fa-solid fa-check"></i> Alinhado</span><?php endif;?><?php if($hasMonthly):?><span class="monthly"><i class="fa-regular fa-calendar"></i> Carteira provisória</span><?php endif;?></div></td>
        <td><strong><?=!empty($row['updated_at'])?date('d/m/Y',strtotime((string)$row['updated_at'])):'—'?></strong><small><?=!empty($row['updated_at'])?date('H:i',strtotime((string)$row['updated_at'])):''?></small></td>
        <td><a class="tdaudit-open" href="<?=APP_URL?>/clients/<?=(int)$row['id']?>"><i class="fa-regular fa-folder-open"></i><span>Revisar</span></a></td>
       </tr>
      <?php endforeach;?>
     </tbody></table><?php if(!$auditResponsibilityRows):?><div class="tdaudit-empty inline"><span><i class="fa-solid fa-circle-check"></i></span><div><strong>Responsabilidades alinhadas</strong><p>Nenhuma divergência ou exceção mensal encontrada neste recorte.</p></div></div><?php endif;?></div></div>
     <?php if((int)$auditPagination['pages']>1):?><nav class="tdaudit-pager"><a class="tdaudit-btn <?=(int)$auditPagination['page']<=1?'disabled':''?>" href="<?=e($auditUrl(['page'=>max(1,(int)$auditPagination['page']-1)]))?>"><i class="fa-solid fa-chevron-left"></i>Anterior</a><span>Mostrando <?=number_format((int)$auditPagination['from'],0,',','.')?>–<?=number_format((int)$auditPagination['to'],0,',','.')?> de <?=number_format((int)$auditPagination['total'],0,',','.')?> · página <strong><?=(int)$auditPagination['page']?></strong> de <strong><?=(int)$auditPagination['pages']?></strong></span><a class="tdaudit-btn <?=(int)$auditPagination['page']>=(int)$auditPagination['pages']?'disabled':''?>" href="<?=e($auditUrl(['page'=>min((int)$auditPagination['pages'],(int)$auditPagination['page']+1)]))?>">Próxima<i class="fa-solid fa-chevron-right"></i></a></nav><?php endif;?>
    <?php else:?>
     <div class="tdaudit-section-head"><div><span>CONTROLE DE INTEGRIDADE</span><h2>Registros inativos preservados</h2><p>Clientes inativados na Omie permanecem no CRM quando necessário para preservar agenda, tarefas, atendimentos e histórico. Revise esses registros antes de qualquer exclusão.</p></div></div>
     <div class="tdaudit-inactive-card"><div class="tdaudit-table-wrap"><table class="tdaudit-table inactive-list"><thead><tr><th>Cliente</th><th>CPF/CNPJ</th><th>Código Omie</th><th>Vendedor</th><th>Histórico encontrado</th><th>Cadastro ativo correspondente</th><th>Atualizado</th><th>Ação</th></tr></thead><tbody>
      <?php foreach($auditInactiveRows as $row):$history=[];if(!empty($row['orders_history']))$history[]='Pedidos';if(!empty($row['services_history']))$history[]='Serviços';if(!empty($row['financial_history']))$history[]='Financeiro';if(!empty($row['crm_history']))$history[]='CRM';if(!$history&&!empty($row['history_preserved']))$history[]='Registro legado';$digits=preg_replace('/\D+/','',(string)$row['document']);?>
       <tr><td><div class="tdaudit-client"><span><?=e(mb_strtoupper(mb_substr((string)$row['name'],0,1)))?></span><div><strong><?=e($row['name'])?></strong><small><?=e(trim((string)($row['city']??'').(!empty($row['uf'])?' / '.$row['uf']:''))?:'Localização não informada')?></small></div></div></td><td><strong><?=e(client_audit_document_format($digits))?></strong><small><?=client_audit_document_valid($digits)?'Documento válido':'Documento inválido'?></small></td><td><strong class="tdaudit-code"><?=e($row['omie_code'])?></strong><small><span class="tdaudit-status inactive"><i></i>Inativo</span></small></td><td><strong><?=e($row['seller_name']?:'Sem vendedor')?></strong><small><?=e($row['seller_omie_code']?:'Não vinculado')?></small></td><td><?php if($history):?><div class="tdaudit-history"><?php foreach($history as $item):?><span><?=e($item)?></span><?php endforeach;?></div><?php else:?><span class="tdaudit-no-history">Sem histórico</span><?php endif;?></td><td><?php if(!empty($row['active_matches'])):?><span class="tdaudit-match"><?=e($row['active_matches'])?></span><?php else:?><span class="tdaudit-no-match">Nenhum ativo com o mesmo documento</span><?php endif;?></td><td><strong><?=!empty($row['updated_at'])?date('d/m/Y',strtotime((string)$row['updated_at'])):'—'?></strong><small><?=!empty($row['updated_at'])?date('H:i',strtotime((string)$row['updated_at'])):''?></small></td><td><a class="tdaudit-open" href="<?=APP_URL?>/clients/<?=(int)$row['id']?>"><i class="fa-regular fa-folder-open"></i><span>Abrir</span></a></td></tr>
      <?php endforeach;?>
     </tbody></table><?php if(!$auditInactiveRows):?><div class="tdaudit-empty inline"><span><i class="fa-solid fa-circle-check"></i></span><div><strong>Nenhum cliente inativo preservado</strong><p>Não há cadastros inativados na Omie aguardando revisão.</p></div></div><?php endif;?></div></div>
     <?php if((int)$auditPagination['pages']>1):?><nav class="tdaudit-pager"><a class="tdaudit-btn <?=(int)$auditPagination['page']<=1?'disabled':''?>" href="<?=e($auditUrl(['page'=>max(1,(int)$auditPagination['page']-1)]))?>"><i class="fa-solid fa-chevron-left"></i>Anterior</a><span>Mostrando <?=number_format((int)$auditPagination['from'],0,',','.')?>–<?=number_format((int)$auditPagination['to'],0,',','.')?> de <?=number_format((int)$auditPagination['total'],0,',','.')?> · página <strong><?=(int)$auditPagination['page']?></strong> de <strong><?=(int)$auditPagination['pages']?></strong></span><a class="tdaudit-btn <?=(int)$auditPagination['page']>=(int)$auditPagination['pages']?'disabled':''?>" href="<?=e($auditUrl(['page'=>min((int)$auditPagination['pages'],(int)$auditPagination['page']+1)]))?>">Próxima<i class="fa-solid fa-chevron-right"></i></a></nav><?php endif;?>
    <?php endif;?>
   </section>
  <?php break;
  case 'clients':?>
   <?php $clientStats=$clientStats??['total'=>count($rows),'revenue'=>0,'orders'=>0,'without_seller'=>0,'seller_divergences'=>0,'monthly_overrides'=>0,'pending_sync'=>0];$baseCounts=$baseCounts??['all'=>0,'general'=>0,'ead_reciclagem'=>0,'suporte_pet'=>0,'supplier'=>0,'carrier'=>0,'inactive'=>0,'stored'=>0];$portfolioMode=!empty($portfolioMode);$clientSegment=$clientSegment??(Auth::can('admin','supervisor')?'all':'general');$clientSegmentCatalog=$clientSegmentCatalog??client_segment_catalog();$clientSegmentLabel=$clientSegmentLabel??'Todos os clientes';$clientSegmentDescription=$clientSegmentDescription??'Base ativa completa.';$clientBasePath=$clientBasePath??'clients';$sellerFilter=$sellerFilter??'';$portfolioMonth=$portfolioMonth??date('Y-m');$portfolioMonthLabel=date('m/Y',strtotime($portfolioMonth.'-01'));$clientCentralUrl=APP_URL.'/clients'.($clientSegment!=='all'?'?'.http_build_query(['segment'=>$clientSegment]):'');?>
   <section class="tdc-page <?=$portfolioMode?'tdc-portfolio-page':''?>">
    <header class="tdc-head">
     <div class="tdc-head-main">
      <span class="tdc-head-icon"><i class="fa-solid <?=$portfolioMode?'fa-briefcase':'fa-users'?>"></i></span>
      <div><span class="tdc-kicker"><?=$portfolioMode?'COMERCIAL / MINHA CARTEIRA':'RELACIONAMENTO / CLIENTES'?></span><h1><?=$portfolioMode?'Minha Carteira':'Central de clientes'?></h1><p><?=$portfolioMode?'Seus clientes vinculados, organizados para facilitar contatos, pedidos e acompanhamento comercial.':'Uma única base para segmentação, carteiras, saneamento cadastral e integração com a Omie.'?></p></div>
     </div>
     <div class="tdc-head-actions"><a class="tdc-btn" href="<?=$portfolioMode?APP_URL.'/my-portfolio':e($clientCentralUrl)?>"><i class="fa-solid fa-rotate-right"></i>Atualizar</a><?php if($portfolioMode||in_array($clientSegment,['all','general'],true)):?><a class="tdc-btn tdc-btn-primary" href="<?=APP_URL?>/clients/new"><i class="fa-solid fa-user-plus"></i>Novo cliente</a><?php endif;?></div>
    </header>

    <?php if(!$portfolioMode&&Auth::can('admin','supervisor')):?>
    <nav class="tdc-hub-nav" aria-label="Central de clientes">
     <a class="<?=$clientSegment==='all'?'active':''?>" href="<?=APP_URL?>/clients"><i class="fa-solid fa-layer-group"></i><span><strong>Todos</strong><small><?=number_format((int)($baseCounts['all']??0),0,',','.')?> ativos</small></span></a>
     <a class="<?=$clientSegment==='general'?'active':''?>" href="<?=APP_URL?>/clients?segment=general"><i class="fa-solid fa-briefcase"></i><span><strong>Comercial</strong><small><?=number_format((int)($baseCounts['general']??0),0,',','.')?> ativos</small></span></a>
     <a class="<?=$clientSegment==='ead_reciclagem'?'active':''?>" href="<?=APP_URL?>/clients?segment=ead_reciclagem"><i class="fa-solid fa-graduation-cap"></i><span><strong>EAD Reciclagem</strong><small><?=number_format((int)($baseCounts['ead_reciclagem']??0),0,',','.')?> ativos</small></span></a>
     <a class="<?=$clientSegment==='suporte_pet'?'active':''?>" href="<?=APP_URL?>/clients?segment=suporte_pet"><i class="fa-solid fa-paw"></i><span><strong>Suporte PET</strong><small><?=number_format((int)($baseCounts['suporte_pet']??0),0,',','.')?> ativos</small></span></a>
     <a class="<?=$clientSegment==='supplier'?'active':''?> operational supplier" href="<?=APP_URL?>/clients?segment=supplier"><i class="fa-solid fa-boxes-packing"></i><span><strong>Fornecedores</strong><small><?=number_format((int)($baseCounts['supplier']??0),0,',','.')?> ativos</small></span></a>
     <a class="<?=$clientSegment==='carrier'?'active':''?> operational carrier" href="<?=APP_URL?>/clients?segment=carrier"><i class="fa-solid fa-truck-fast"></i><span><strong>Transportadoras</strong><small><?=number_format((int)($baseCounts['carrier']??0),0,',','.')?> ativos</small></span></a>
     <a href="<?=APP_URL?>/clients-sync"><i class="fa-solid fa-cloud-arrow-up"></i><span><strong>Sincronização</strong><small>Pendências e envios</small></span></a>
     <a href="<?=APP_URL?>/clients-audit"><i class="fa-solid fa-user-shield"></i><span><strong>Auditoria</strong><small>Qualidade e consistência</small></span></a>
    </nav>
    <?php endif;?>

    <?php if($flash):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>

    <div class="tdc-kpis">
     <article class="tdc-kpi green"><span class="tdc-kpi-icon"><i class="fa-solid fa-address-book"></i></span><div><small><?=$portfolioMode?'Clientes vinculados':($clientSegment==='all'?'Ativos na base completa':'Clientes nesta visão')?></small><strong><?=number_format((int)$clientStats['total'],0,',','.')?></strong><p><?php if($portfolioMode):?>Somente sua responsabilidade<?php elseif(!empty($clientTagsSelected)||!empty($clientUfs)||$q!==''||$sellerFilter!==''):?>Filtro atual · base ativa total <?=number_format((int)($baseCounts['all']??0),0,',','.')?><?php elseif($clientSegment==='all'):?><?=number_format((int)($baseCounts['stored']??0),0,',','.')?> armazenados · <?=number_format((int)($baseCounts['inactive']??0),0,',','.')?> inativos preservados<?php else:?>De <?=number_format((int)($baseCounts['all']??0),0,',','.')?> clientes ativos na base completa<?php endif;?></p></div></article>
     <article class="tdc-kpi blue"><span class="tdc-kpi-icon"><i class="fa-solid fa-chart-line"></i></span><div><small>Receita em 12 meses</small><strong><?=money($clientStats['revenue'])?></strong><p>Produção acumulada da carteira</p></div></article>
     <article class="tdc-kpi yellow"><span class="tdc-kpi-icon"><i class="fa-solid fa-cart-shopping"></i></span><div><small>Pedidos em 12 meses</small><strong><?=number_format((int)$clientStats['orders'],0,',','.')?></strong><p>Volume comercial recente</p></div></article>
     <?php if($portfolioMode):?><article class="tdc-kpi orange owner"><span class="tdc-kpi-icon"><i class="fa-solid fa-user-tie"></i></span><div><small>Responsável pela carteira</small><strong><?=e(Auth::user()['name']??'Vendedor')?></strong><p>Vínculo exclusivo do seu usuário</p></div></article><?php else:?><article class="tdc-kpi orange"><span class="tdc-kpi-icon"><i class="fa-solid fa-user-tag"></i></span><div><small>Sem vendedor</small><strong><?=number_format((int)$clientStats['without_seller'],0,',','.')?></strong><p>Clientes para distribuição</p></div></article><?php endif;?>
    </div>

    <?php if(Auth::can('admin','supervisor')&&in_array($clientSegment,['all','general'],true)):?>
    <section class="tdc-intelligence" aria-label="Saúde da base de clientes">
     <div class="tdc-intelligence-head"><span><i class="fa-solid fa-brain"></i></span><div><strong>Controle inteligente da base</strong><small>Separe correção definitiva, carteira provisória e sincronização com a Omie.</small></div></div>
     <div class="tdc-intelligence-items">
      <a href="<?=APP_URL?>/clients-audit?tab=responsibility&month=<?=e($portfolioMonth)?>"><b><?=number_format((int)($clientStats['seller_divergences']??0),0,',','.')?></b><span>Divergências CRM × Omie</span><small>Vendedor principal diferente da origem Omie</small></a>
      <div><b><?=number_format((int)($clientStats['monthly_overrides']??0),0,',','.')?></b><span>Ajustes em <?=$portfolioMonthLabel?></span><small>Carteiras provisórias deste mês</small></div>
      <a href="<?=APP_URL?>/clients-sync?status=pending"><b><?=number_format((int)($clientStats['pending_sync']??0),0,',','.')?></b><span>Pendentes de sincronização</span><small>Alterações locais aguardando Omie</small></a>
     </div>
    </section>

    <?php if($clientSegment==='general'):?>
    <section class="tdc-card">
     <div class="tdc-card-head"><div class="tdc-card-title"><span><i class="fa-solid fa-users-gear"></i></span><div><strong>Carteira provisória do mês</strong><small>Redistribua o atendimento sem alterar o vendedor principal nem a Omie. Sem exceção mensal, vale o vendedor principal.</small></div></div><span class="tdc-badge"><i class="fa-solid fa-shield-halved"></i> Provisória</span></div>
     <form method="post" action="<?=APP_URL?>/clients/portfolio/assign" class="tdc-portfolio-form">
      <input type="hidden" name="_token" value="<?=CSRF::token()?>">
      <div class="tdc-field"><label>Mês da carteira</label><input class="form-control" type="month" name="month" value="<?=e($portfolioMonth)?>" required></div>
      <div class="tdc-field"><label>Estado</label><select class="form-select" name="uf" required data-client-state-filter><option value="">Selecione</option><?php foreach($portfolioStates??[] as $state):?><option value="<?=e($state['uf'])?>" <?=$uf===$state['uf']?'selected':''?>><?=e($state['uf'])?></option><?php endforeach;?></select></div>
      <div class="tdc-ddd-picker clients-ddd-picker"><span>DDDs da região</span><div><?php foreach(($portfolioDddMap[$uf]??[]) as $ddd):?><label><input type="checkbox" name="ddds[]" value="<?=e($ddd)?>" <?=in_array($ddd,$ddds??[],true)?'checked':''?>><b><?=e($ddd)?></b></label><?php endforeach;?><?php if($uf===''):?><small>Selecione primeiro o estado.</small><?php endif;?></div><?php if($uf!==''):?><button class="tdc-btn" type="button" data-client-ddd-apply><i class="fa-solid fa-filter"></i>Filtrar tabela</button><?php endif;?></div>
      <div class="tdc-field"><label>Responsável em <?=$portfolioMonthLabel?></label><select class="form-select" name="source_seller" required><option value="__unassigned__">Somente sem responsável</option><option value="__all__">Todos dos DDDs</option><?php foreach($portfolioSourceSellers??$portfolioSellers??[] as $seller):?><option value="<?=e($seller['omie_code'])?>"><?=e($seller['name'])?><?=isset($seller['active'])&&!(int)$seller['active']?' (inativo)':''?></option><?php endforeach;?></select></div>
      <div class="tdc-field"><label>Destino provisório</label><select class="form-select" name="target_seller" required><option value="">Selecione</option><option value="__principal__">↩ Voltar ao vendedor principal</option><?php foreach($portfolioSellers??[] as $seller):?><option value="<?=e($seller['omie_code'])?>"><?=e($seller['name'])?></option><?php endforeach;?></select></div>
      <button class="tdc-btn tdc-btn-primary" type="submit" data-submit-loading="Atualizando carteira..." data-confirm="Confirmar a carteira provisória deste mês? O vendedor principal e a Omie não serão alterados."><i class="fa-solid fa-arrow-right-arrow-left"></i>Aplicar carteira</button>
     </form>
    </section>
    <?php endif;?>
    <?php endif;?>

    <?php if(Auth::can('seller')&&!$portfolioMode):?>
    <nav class="tdc-scope">
     <a class="<?=!$portfolioMode&&($clientScope??'all')==='all'?'active':''?>" href="<?=APP_URL?>/clients"><i class="fa-solid fa-users"></i><span><strong>Todos os clientes</strong><small>Visão geral da base comercial</small></span><i class="fa-solid fa-chevron-right"></i></a>
     <a class="<?=$portfolioMode?'active':''?>" href="<?=APP_URL?>/my-portfolio"><i class="fa-solid fa-briefcase"></i><span><strong>Minha carteira</strong><small>Somente os vinculados a você</small></span><i class="fa-solid fa-chevron-right"></i></a>
     <a class="<?=!$portfolioMode&&($clientScope??'all')==='unassigned'?'active':''?>" href="<?=APP_URL?>/clients?scope=unassigned"><i class="fa-solid fa-user-plus"></i><span><strong>Sem vendedor</strong><small><?=number_format((int)($availableClients??0),0,',','.')?> disponíveis</small></span><i class="fa-solid fa-chevron-right"></i></a>
    </nav>
    <?php endif;?>

    <section class="tdc-table-card">
     <div class="tdc-table-top"><div><span class="tdc-kicker"><?=$portfolioMode?'ATENDIMENTO PRIORITÁRIO':'SEGMENTO ATUAL'?></span><h2><?=$portfolioMode?'Clientes da minha carteira':e($clientSegmentLabel)?></h2><p><?=$portfolioMode?'Busque somente entre os clientes vinculados ao seu vendedor.':e($clientSegmentDescription).' · '.(in_array($clientSegment,['supplier','carrier'],true)?'Classificação originada das tags sincronizadas da Omie.':'Busque por nome, documento, cidade ou responsável.')?></p></div><div class="tdc-table-legend"><span class="view"><i class="fa-regular fa-eye"></i>Visualizar</span><span class="edit"><i class="fa-regular fa-pen-to-square"></i>Editar</span><?php if(Auth::can('admin','supervisor')):?><span class="local"><i class="fa-solid fa-database"></i>CRM</span><span class="delete"><i class="fa-regular fa-trash-can"></i>Excluir</span><?php endif;?></div></div>
     <div class="tdc-list-filterbar">
      <div class="tdc-filter-title"><span><i class="fa-solid fa-sliders"></i></span><div><strong>Filtros da consulta</strong><small>Combine estado, tag e vendedor para encontrar exatamente os clientes que deseja revisar.</small></div></div>
      <form method="get" action="<?=APP_URL?>/<?=$portfolioMode?'my-portfolio':'clients'?>">
       <?php if(!$portfolioMode&&Auth::can('admin','supervisor')&&$clientSegment!=='all'):?><input type="hidden" name="segment" value="<?=e($clientSegment)?>"><?php endif;?>
       <?php if(Auth::can('admin','supervisor')&&$clientSegment==='general'):?><label><span><i class="fa-regular fa-calendar"></i> Mês da carteira</span><input class="form-control" type="month" name="month" value="<?=e($portfolioMonth)?>" onchange="this.form.submit()"></label><?php else:?><input type="hidden" name="month" value="<?=e($portfolioMonth)?>"><?php endif;?>
       <?php if(!$portfolioMode&&Auth::can('seller')&&($clientScope??'all')==='unassigned'):?><input type="hidden" name="scope" value="unassigned"><?php endif;?>
       <?php $clientUfs=$clientUfs??[];$ufFilterLabel=!$clientUfs?'Todos os estados':(count($clientUfs)===1?$clientUfs[0]:count($clientUfs).' UFs selecionadas');?>
       <details class="tdc-uf-filter" data-client-uf-filter>
        <summary><span class="tdc-uf-filter-copy"><small><i class="fa-solid fa-map-location-dot"></i> Estados</small><strong><?=e($ufFilterLabel)?></strong></span><i class="fa-solid fa-chevron-down"></i></summary>
        <div class="tdc-uf-filter-panel">
         <div class="tdc-uf-filter-grid"><?php foreach($clientStates??[] as $state):$stateUf=(string)$state['uf'];?><label><input type="checkbox" name="ufs[]" value="<?=e($stateUf)?>" <?=in_array($stateUf,$clientUfs,true)?'checked':''?>><span><?=e($stateUf)?></span></label><?php endforeach;?></div>
         <div class="tdc-uf-filter-actions"><button class="tdc-btn" type="button" data-client-uf-clear>Limpar</button><button class="tdc-btn tdc-btn-primary" type="submit"><i class="fa-solid fa-filter"></i>Aplicar UFs</button></div>
        </div>
       </details>
       <?php $clientTagsSelected=$clientTagsSelected??[];$clientTagFilterLabel=!$clientTagsSelected?'Todas as tags':(count($clientTagsSelected)===1?$clientTagsSelected[0]:count($clientTagsSelected).' tags selecionadas');?>
       <details class="tdc-tag-filter" data-client-tag-multi>
        <summary><span class="tdc-uf-filter-copy"><small><i class="fa-solid fa-tags"></i> Tags</small><strong><?=e($clientTagFilterLabel)?></strong></span><i class="fa-solid fa-chevron-down"></i></summary>
        <div class="tdc-tag-filter-panel">
         <div class="tdc-tag-filter-grid"><?php foreach($clientTags??[] as $clientTag):$tagName=(string)$clientTag['tag'];?><label><input type="checkbox" name="tags[]" value="<?=e($tagName)?>" <?=in_array($tagName,$clientTagsSelected,true)?'checked':''?>><span><?=e($tagName)?></span><small><?=number_format((int)$clientTag['client_count'],0,',','.')?></small></label><?php endforeach;?></div>
         <div class="tdc-uf-filter-actions"><button class="tdc-btn" type="button" data-client-tags-clear>Limpar</button><button class="tdc-btn tdc-btn-primary" type="submit"><i class="fa-solid fa-filter"></i>Aplicar tags</button></div>
        </div>
       </details>
       <?php if(!$portfolioMode):?>
        <label><span><i class="fa-solid fa-user-tie"></i> Responsável em <?=$portfolioMonthLabel?></span><select class="form-select" name="seller_filter" onchange="this.form.submit()"><option value="">Todos os responsáveis</option><option value="__none__" <?=$sellerFilter==='__none__'?'selected':''?>>⚠ Sem responsável</option><?php foreach($clientSellerFilters??[] as $filterSeller):?><option value="<?=e($filterSeller['omie_code'])?>" <?=$sellerFilter===(string)$filterSeller['omie_code']?'selected':''?>><?=e($filterSeller['name'])?><?=empty($filterSeller['active'])?' (inativo)':''?></option><?php endforeach;?></select></label>
       <?php endif;?>
       <?php if(!empty($clientUfs)||!empty($clientTagsSelected)||(!$portfolioMode&&$sellerFilter!=='')):?><?php $clearParams=[];if(!$portfolioMode&&Auth::can('admin','supervisor')&&$clientSegment!=='all')$clearParams['segment']=$clientSegment;if(!$portfolioMode&&Auth::can('seller')&&($clientScope??'all')==='unassigned')$clearParams['scope']='unassigned';?><a class="tdc-btn" href="<?=APP_URL?>/<?=$portfolioMode?'my-portfolio':'clients'?><?=$clearParams?'?'.e(http_build_query($clearParams)):''?>"><i class="fa-solid fa-xmark"></i>Limpar filtros</a><?php endif;?>
      </form>
      <small class="tdc-filter-meta"><i class="fa-solid fa-circle-info"></i> <?=$portfolioMode?'A carteira permanece limitada aos seus clientes; Estados e tags apenas refinam a visualização.':count($clientTags??[]).' tags mapeadas nos cadastros ativos'?></small>
     </div>
     <?php if(Auth::can('admin','supervisor')):?>
     <section class="tdc-bulk" data-client-bulk data-endpoint="<?=APP_URL?>/api/clients/bulk" data-csrf="<?=CSRF::token()?>" data-segment="<?=e($clientSegment)?>">
      <div class="tdc-bulk-head">
       <div class="tdc-bulk-title"><span><i class="fa-solid fa-wand-magic-sparkles"></i></span><div><strong>Edição em massa</strong><small>1. Selecione os clientes · 2. Configure a alteração · 3. Escolha onde salvar</small></div></div>
       <div class="tdc-bulk-selection"><span class="tdc-bulk-count" data-client-selected-count>0 selecionados</span><button type="button" data-client-select-filtered hidden>Selecionar todos os resultados filtrados</button><button type="button" data-client-clear-selection hidden>Limpar seleção</button></div>
      </div>
      <?php $bulkSellerSegmentLabels=[];foreach($clientSegmentCatalog as $segmentKey=>$segmentMeta){if($segmentKey==='general')continue;foreach((array)($segmentMeta['seller_codes']??[]) as $segmentSellerCode)$bulkSellerSegmentLabels[(string)$segmentSellerCode]=(string)($segmentMeta['label']??$segmentKey);}?>
      <div class="tdc-bulk-controls">
       <label><span><b>1</b> Alterar vendedor principal</span><select class="form-select" data-client-bulk-seller><option value="">Não alterar principal</option><option value="__none__">Deixar principal sem vendedor</option><?php foreach($bulkSellers??[] as $seller):$sellerCode=(string)$seller['omie_code'];$segmentLabel=$bulkSellerSegmentLabels[$sellerCode]??null;?><option value="<?=e($sellerCode)?>"><?=e($seller['name'])?><?=$segmentLabel?' — '.e($segmentLabel):' — Comercial'?></option><?php endforeach;?></select><small>Alteração definitiva. Ao escolher um vendedor virtual, o cliente muda de segmento no CRM; a Omie pode ser sincronizada agora ou depois.</small></label>
       <label><span><b>2</b> Operação nas tags</span><select class="form-select" data-client-tag-operation><option value="none">Não alterar tags</option><option value="add">Adicionar tags</option><option value="remove">Remover tags</option><option value="replace">Substituir todas</option></select><small>Adicionar e remover preservam as outras tags.</small></label>
       <label class="tdc-bulk-tags"><span><b>3</b> Tags da operação</span><input class="form-control" data-client-bulk-tags list="client-bulk-tag-list" placeholder="Ex.: PRIORIDADE, REVENDA"><datalist id="client-bulk-tag-list"><?php foreach($clientTags??[] as $clientTag):?><option value="<?=e($clientTag['tag'])?>"><?php endforeach;?></datalist><small>Separe várias tags usando vírgula.</small></label>
      </div>
      <div class="tdc-bulk-action-panel">
       <div><strong>Quando deseja alinhar com a Omie?</strong><small>Você pode organizar primeiro o CRM e sincronizar a Omie depois, ou fazer os dois na mesma operação.</small></div>
       <div class="tdc-bulk-actions">
        <button class="tdc-bulk-action primary" type="button" data-client-bulk-run="apply_sync"><i class="fa-solid fa-cloud-arrow-up"></i><span><strong>CRM + Omie agora</strong><small>Troca o vendedor principal e sincroniza imediatamente</small></span></button>
        <button class="tdc-bulk-action pending" type="button" data-client-bulk-run="sync"><i class="fa-solid fa-arrows-rotate"></i><span><strong>Sincronizar pendentes</strong><small>Envia depois as correções que já estão no CRM</small></span></button>
        <button class="tdc-bulk-action local" type="button" data-client-bulk-run="apply" title="Organiza o CRM agora e deixa a Omie pendente para depois"><i class="fa-solid fa-database"></i><span><strong>Somente CRM agora</strong><small>Corrige a base e mantém a Omie como pendente</small></span></button>
       </div>
      </div>
      <div class="tdc-bulk-progress" data-client-bulk-progress hidden><span><i></i></span><strong data-client-bulk-progress-text>Preparando...</strong></div>
     </section>
     <?php endif;?>
     <div class="table-card tdc-table-wrap">
      <?php $clientDataParams=['ufs'=>$clientUfs,'segment'=>$clientSegment,'month'=>$portfolioMonth];if($ddds)$clientDataParams['ddds']=$ddds;if($clientTagsSelected)$clientDataParams['tags']=$clientTagsSelected;if($sellerFilter!=='')$clientDataParams['seller_filter']=$sellerFilter;if($portfolioMode)$clientDataParams['portfolio']='mine';elseif(Auth::can('seller')&&($clientScope??'all')==='unassigned')$clientDataParams['scope']='unassigned';?>
      <table class="table tdc-table clients-datatable" data-server-url="<?=APP_URL?>/api/clients/datatable?<?=e(http_build_query($clientDataParams))?>" data-search="<?=e($q)?>" data-page-length="5" data-length-change="1" data-order-column="<?=Auth::can('admin','supervisor')?1:0?>" data-order-direction="asc">
       <thead><tr><?php if(Auth::can('admin','supervisor')):?><th class="tdc-select-column" data-dt-order="disable"><label class="tdc-row-check" title="Selecionar página"><input type="checkbox" data-client-select-page><span></span></label></th><?php endif;?><th>Cliente</th><th>Localização</th><th>Responsabilidade</th><th data-dt-order="disable">Tags</th><th>Ciclo</th><th>Dias sem contato</th><th>Última compra</th><th class="text-end">Receita 12m</th><th class="text-end" data-dt-order="disable">Ações</th></tr></thead>
       <tbody><?php foreach($rows as $r):?><tr>
        <?php if(Auth::can('admin','supervisor')):?><td><label class="tdc-row-check"><input type="checkbox" data-client-select value="<?=(int)$r['id']?>"><span></span></label></td><?php endif;?><td><?php $loggedSellerCode=trim((string)(Auth::user()['seller_omie_code']??''));$rowEffectiveCode=trim((string)($r['effective_seller_code']??$r['seller_omie_code']??''));$rowSellerOwned=Auth::can('admin','supervisor')||(Auth::can('seller')&&$loggedSellerCode!==''&&$rowEffectiveCode===$loggedSellerCode);$rowSellerUnassigned=$rowEffectiveCode==='';?><div class="tdc-client-cell"><span class="tdc-avatar"><?=e(mb_strtoupper(mb_substr((string)$r['name'],0,1)))?></span><div><?php if($rowSellerOwned||$rowSellerUnassigned):?><a href="<?=APP_URL?>/clients/<?=$r['id']?>"><strong><?=e($r['name'])?></strong></a><?php else:?><strong><?=e($r['name'])?></strong><?php endif;?><small><?=e($r['document']?:'Documento não informado')?></small></div></div></td>
        <td><span><i class="fa-solid fa-location-dot"></i> <?=e(trim(($r['city']??'').' / '.($r['uf']??''),' /')?:'Não informado')?></span></td>
        <td><?php $hasMonthOverride=!empty($r['portfolio_assignment_id']);$principalCode=trim((string)($r['seller_omie_code']??''));$remoteCode=trim((string)($r['omie_seller_code']??''));if($rowEffectiveCode!==''):?><div class="tdc-seller-cell <?=$hasMonthOverride?'monthly':''?> <?=$principalCode!==$remoteCode?'divergent':''?>"><i class="fa-solid <?=$hasMonthOverride?'fa-calendar-check':'fa-user-tie'?>"></i><div><strong><?=e($r['effective_seller_name']??$rowEffectiveCode)?></strong><small><?=$hasMonthOverride?'Carteira '.$portfolioMonthLabel.' · principal '.e($r['seller_name']??$principalCode):($principalCode!==$remoteCode?'Principal · Omie '.e($r['omie_seller_name']??($remoteCode?:'sem vendedor')):'Principal alinhado com a Omie')?></small></div></div><?php else:?><div class="tdc-seller-cell empty"><i class="fa-solid fa-user-slash"></i><div><strong>Sem responsável</strong><small><?=$hasMonthOverride?'Carteira provisória sem vendedor':(Auth::can('seller')?'Atendimento compartilhado':'Disponível para vincular')?></small></div></div><?php endif;?></td>
        <td><?php $rowTags=client_tags_from_raw($r['raw_json']??null);?><div class="client-tag-list" title="<?=e(implode(', ',$rowTags))?>"><?php foreach(array_slice($rowTags,0,3) as $rowTag):?><span><?=e($rowTag)?></span><?php endforeach;?><?php if(count($rowTags)>3):?><b>+<?=count($rowTags)-3?></b><?php endif;?><?php if(!$rowTags):?><small>Sem tag</small><?php endif;?></div></td>
        <td><span class="tdc-cycle"><?=e($r['cycle']['label'])?></span></td>
        <td><?php $lastContactAt=trim((string)($r['last_contact_at']??''));if($lastContactAt===''):?><span class="tdc-contact-days never"><strong>Nunca</strong></span><?php else:$contactDays=max(0,(int)floor((strtotime(date('Y-m-d'))-strtotime(date('Y-m-d',strtotime($lastContactAt))))/86400));$contactClass=$contactDays<=30?'ok':($contactDays<=60?'warning':'late');?><span class="tdc-contact-days <?=$contactClass?>"><strong><?=$contactDays?></strong></span><?php endif;?></td>
        <td><strong><?=brdate($r['last_purchase_at']??null)?></strong><small><?=($r['orders_12m']??0)>0?(int)$r['orders_12m'].' pedido(s) em 12 meses':'Sem pedidos recentes'?></small></td>
        <td class="text-end"><strong><?=money($r['revenue_12m']??0)?></strong></td>
        <td><div class="tdc-actions"><a class="tdc-icon-btn view" href="<?=APP_URL?>/clients/<?=$r['id']?>" title="Visualizar"><i class="fa-regular fa-eye"></i></a><a class="tdc-icon-btn edit" href="<?=APP_URL?>/clients/<?=$r['id']?>/edit" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a><?php if(Auth::can('admin','supervisor')):?><button class="tdc-icon-btn sync" type="button" data-client-omie-one="<?=(int)$r['id']?>" title="Atualizar este cadastro na Omie"><i class="fa-solid fa-cloud-arrow-up"></i></button><?php if(str_starts_with((string)$r['omie_code'],'LOCAL-')):?><form method="post" action="<?=APP_URL?>/clients/<?=$r['id']?>/delete-local"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="tdc-icon-btn local" type="submit" title="Remover somente do CRM" data-confirm="Excluir este cadastro local? A ação será bloqueada se houver histórico relacionado."><i class="fa-solid fa-database"></i></button></form><?php endif;?><form method="post" action="<?=APP_URL?>/clients/<?=$r['id']?>/delete"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="tdc-icon-btn delete" type="submit" title="Excluir ou arquivar preservando histórico" data-confirm="Excluir este cliente? Se houver histórico no CRM, ele será preservado em arquivo."><i class="fa-regular fa-trash-can"></i></button></form><?php endif;?></div></td>
       </tr><?php endforeach;?></tbody>
      </table>
     </div>
    </section>
   </section>
  <?php break;

  case 'client_new':$editClient=$editClient??null;$editError=$editError??null;?>
   <section class="tdc-page">
    <header class="tdc-head"><div class="tdc-head-main"><span class="tdc-head-icon"><i class="fa-solid <?=$editClient?'fa-user-pen':'fa-user-plus'?>"></i></span><div><span class="tdc-kicker"><?=$editClient?'CLIENTES / EDITAR':'CLIENTES / NOVO'?></span><h1><?=$editClient?'Editar cliente':'Cadastrar cliente'?></h1><p><?=$editClient?'Atualize as informações e mantenha o cadastro alinhado com a Omie.':'Cadastre o cliente com CNPJ e CEP inteligentes e organização comercial completa.'?></p></div></div><div class="tdc-head-actions"><a class="tdc-btn" href="<?=$editClient?APP_URL.'/clients/'.(int)$editClient['id']:APP_URL.'/clients'?>"><i class="fa-solid fa-arrow-left"></i>Voltar</a></div></header>

    <?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
    <?php if($editError):?><div class="alert alert-danger"><strong>Omie:</strong> <?=e($editError)?></div><?php endif;?>
    <?php if($createError):?><div class="alert alert-danger"><strong>Omie:</strong> <?=e($createError)?></div><?php endif;?>
    <?php if($createSuccess):?><div class="alert alert-success"><strong>Cliente criado com sucesso.</strong> <?=e((string)($createSuccess['client']['name']??''))?> · Omie <?=e((string)($createSuccess['client']['omie_code']??''))?></div><?php endif;?>

    <div class="tdc-form-layout">
     <aside class="tdc-steps">
      <div class="tdc-steps-head"><span>CADASTRO</span><strong><?=$editClient?'Editar cliente':'Novo cliente'?></strong><small>Preencha os blocos ao lado. Todos os campos são obrigatórios, exceto complemento, vendedor e observações.</small></div>
      <nav class="tdc-step-list"><a class="active" href="#tdc-identificacao"><b>1</b><span><strong>Identificação</strong><small>Documento e contato</small></span></a><a href="#tdc-endereco"><b>2</b><span><strong>Endereço</strong><small>Localização</small></span></a><a href="#tdc-comercial"><b>3</b><span><strong>Comercial</strong><small>Vendedor e tags</small></span></a><a href="#tdc-revisao"><b>4</b><span><strong>Conferência</strong><small>Salvar cadastro</small></span></a></nav>
     </aside>
     <main>
      <form method="post" action="<?=$editClient?APP_URL.'/clients/'.(int)$editClient['id'].'/update':APP_URL.'/clients/save-local'?>" id="clientCreateForm" class="tdc-form">
       <input type="hidden" name="_token" value="<?=CSRF::token()?>">

       <section class="tdc-section" id="tdc-identificacao">
        <div class="tdc-section-head"><span class="tdc-section-icon blue"><i class="fa-solid fa-building"></i></span><div><strong>Dados principais</strong><small>Identificação e contato do cliente.</small></div><?php if($editClient):?><span class="tdc-badge">Omie #<?=e((string)$editClient['omie_code'])?></span><?php endif;?></div>
        <div class="tdc-fields">
         <div class="field span-4"><label>CPF / CNPJ<span class="tdc-required">*</span></label><div class="tdc-lookup"><input class="form-control" name="document" data-document value="<?=e((string)($old['document']??''))?>" inputmode="numeric" required><button type="button" data-cnpj-lookup><i class="fa-solid fa-magnifying-glass"></i></button></div><small class="tdc-hint" data-cnpj-status>CNPJ completo busca os dados disponíveis.</small></div>
         <div class="field span-5"><label>Razão social / Nome<span class="tdc-required">*</span></label><input class="form-control" name="legal_name" value="<?=e((string)($old['legal_name']??''))?>" required></div>
         <div class="field span-3"><label>Nome fantasia<span class="tdc-required">*</span></label><input class="form-control" name="trade_name" value="<?=e((string)($old['trade_name']??''))?>" required></div>
         <div class="field span-4"><label>E-mail<span class="tdc-required">*</span></label><input class="form-control" type="email" name="email" value="<?=e((string)($old['email']??''))?>" required></div>
         <div class="field span-4"><label>Nome do contato<span class="tdc-required">*</span></label><input class="form-control" name="contact_name" value="<?=e((string)($old['contact_name']??''))?>" required></div>
         <div class="field span-4"><label>Telefone<span class="tdc-required">*</span></label><div class="tdc-phone"><input class="form-control" name="phone_ddd" data-phone-ddd value="<?=e((string)($old['phone_ddd']??''))?>" maxlength="2" placeholder="DDD" required><input class="form-control" name="phone_number" data-phone-number value="<?=e((string)($old['phone_number']??''))?>" maxlength="9" placeholder="Número" required></div></div>
        </div>
       </section>

       <section class="tdc-section" id="tdc-endereco">
        <div class="tdc-section-head"><span class="tdc-section-icon green"><i class="fa-solid fa-location-dot"></i></span><div><strong>Endereço</strong><small>Localização completa para cadastro e faturamento.</small></div></div>
        <div class="tdc-fields">
         <div class="field span-2"><label>CEP<span class="tdc-required">*</span></label><div class="tdc-lookup"><input class="form-control" name="zip_code" data-cep value="<?=e((string)($old['zip_code']??''))?>" required><button type="button" data-cep-lookup><i class="fa-solid fa-location-crosshairs"></i></button></div><small class="tdc-hint" data-cep-status>Busca automática ao completar.</small></div>
         <div class="field span-5"><label>Endereço<span class="tdc-required">*</span></label><input class="form-control" name="address" value="<?=e((string)($old['address']??''))?>" required></div>
         <div class="field span-2"><label>Número<span class="tdc-required">*</span></label><input class="form-control" name="address_number" value="<?=e((string)($old['address_number']??''))?>" required></div>
         <div class="field span-3"><label>Complemento</label><input class="form-control" name="complement" value="<?=e((string)($old['complement']??''))?>" placeholder="Opcional"></div>
         <div class="field span-4"><label>Bairro<span class="tdc-required">*</span></label><input class="form-control" name="neighborhood" value="<?=e((string)($old['neighborhood']??''))?>" required></div>
         <div class="field span-6"><label>Cidade<span class="tdc-required">*</span></label><input class="form-control" name="city" value="<?=e((string)($old['city']??''))?>" required></div>
         <div class="field span-2"><label>UF<span class="tdc-required">*</span></label><input class="form-control text-uppercase" name="uf" maxlength="2" value="<?=e((string)($old['uf']??''))?>" required></div>
        </div>
       </section>

       <section class="tdc-section" id="tdc-comercial">
        <div class="tdc-section-head"><span class="tdc-section-icon orange"><i class="fa-solid fa-user-tie"></i></span><div><strong>Organização comercial</strong><small>Vendedor, classificação e informações úteis.</small></div></div>
        <div class="tdc-fields">
         <div class="field span-4"><label>Vendedor principal</label><?php if(Auth::can('admin','supervisor')||$editClient):?><select class="form-select" name="seller_omie_code"><option value="">Sem vendedor</option><?php foreach($sellers as $seller):?><option value="<?=e($seller['omie_code'])?>" <?=($old['seller_omie_code']??'')===$seller['omie_code']?'selected':''?>><?=e($seller['name'])?></option><?php endforeach;?></select><?php if($editClient&&Auth::can('seller')):?><small class="tdc-hint">Você pode corrigir o vendedor principal deste cliente. A carteira mensal continua independente.</small><?php endif;?><?php else:?><div class="tdc-fixed"><?=e(Auth::user()['name']??'Vendedor')?></div><?php endif;?></div>
         <div class="field span-8"><label>Tags<span class="tdc-required">*</span></label><input class="form-control" name="tags" value="<?=e((string)($old['tags']??'CLIENTE, CFC'))?>" placeholder="CLIENTE, CFC" required><small class="tdc-hint"><?=$editClient?'As tags atuais foram preservadas. Separe várias tags por vírgula; só altere quando necessário.':'O novo cliente começa com CLIENTE e CFC. Separe tags adicionais por vírgula.'?></small></div>
         <div class="field span-12"><label>Observações</label><textarea class="form-control" name="notes" rows="4" placeholder="Informações úteis para o atendimento."><?=e((string)($old['notes']??''))?></textarea></div>
        </div>
       </section>

       <?php if(!$editClient&&$preview):?>
       <section class="tdc-section" id="tdc-revisao">
        <div class="tdc-section-head"><span class="tdc-section-icon yellow"><i class="fa-solid fa-code"></i></span><div><strong>Prévia da integração</strong><small>Payload validado antes do envio.</small></div><span class="tdc-badge">VALIDADO</span></div>
        <div class="tdc-fields"><div class="field span-4"><label>Cliente</label><div class="tdc-fixed"><?=e($preview['summary']['name'])?></div></div><div class="field span-4"><label>Documento</label><div class="tdc-fixed"><?=e($preview['summary']['document'])?></div></div><div class="field span-4"><label>Vendedor</label><div class="tdc-fixed"><?=e($preview['summary']['seller'])?></div></div></div>
       </section>
       <?php else:?><span id="tdc-revisao"></span><?php endif;?>

       <footer class="tdc-form-actions"><div><i class="fa-solid fa-circle-info"></i><span><?=$editClient?'Salvar atualiza primeiro o CRM; se houver divergência, a sincronização com a Omie será feita de forma explícita.':'O cliente será salvo no CRM e poderá ser sincronizado com a Omie.'?></span></div><div class="actions"><?php if($editClient&&Auth::can('admin','supervisor')):?><button class="tdc-btn tdc-btn-danger" type="submit" formaction="<?=APP_URL?>/clients/<?=(int)$editClient['id']?>/delete" formnovalidate data-confirm="Excluir este cliente?"><i class="fa-regular fa-trash-can"></i>Excluir</button><?php endif;?><a class="tdc-btn" href="<?=$editClient?APP_URL.'/clients/'.(int)$editClient['id']:APP_URL.'/clients'?>">Cancelar</a><button class="tdc-btn tdc-btn-primary" type="submit" <?=$editClient?'data-confirm="Salvar estas alterações no CRM? A Omie só será atualizada quando você sincronizar."':''?>><i class="fa-solid <?=$editClient?'fa-check':'fa-floppy-disk'?>"></i><?=$editClient?'Salvar alterações':'Salvar cliente'?></button></div></footer>
      </form>
     </main>
    </div>
   </section>
  <?php break;

  case 'client':
   $isLocal=str_starts_with((string)$client['omie_code'],'LOCAL-');
   $clientRaw=json_decode((string)($client['raw_json']??''),true);
   $omieStatus=is_array($clientRaw)?(string)($clientRaw['omie_status']??''):'';
   $pendingOmie=$isLocal||in_array($omieStatus,['pending','pending_update'],true);
   $loggedUser=Auth::user();$loggedSellerCode=trim((string)($loggedUser['seller_omie_code']??''));$effectiveSellerCode=$effectiveSellerCode??trim((string)($client['seller_omie_code']??''));
   $operationalAccess=($loggedUser['role']??'')!=='seller'||$effectiveSellerCode===''||$effectiveSellerCode===$loggedSellerCode;
   $principalSellerName=$sellerName?:($client['seller_omie_code']??'Sem vendedor');$effectiveSellerName=$effectiveSellerName?:'Sem responsável';$omieSellerName=$omieSellerName?:'Sem vendedor';
   ?>
   <section class="tdc-page">
    <header class="tdc-head tdc-detail-head">
     <div class="tdc-head-main"><span class="tdc-detail-avatar"><?=e(mb_strtoupper(mb_substr((string)$client['name'],0,1)))?></span><div><a class="tdc-back" href="<?=APP_URL?>/clients<?=!empty($sharedUnassigned)?'?scope=unassigned':''?>"><i class="fa-solid fa-arrow-left"></i>Carteira de clientes</a><h1><?=e($client['name'])?></h1><div class="tdc-meta"><span><i class="fa-solid fa-location-dot"></i><?=e(trim(($client['city']??'').' / '.($client['uf']??''),' /')?:'Localização não informada')?></span><span><i class="fa-regular fa-id-card"></i><?=e($client['document']?:'Documento não informado')?></span><span><i class="fa-solid fa-cloud"></i><?=$isLocal?'Somente local':'Omie '.e($client['omie_code'])?></span></div></div></div>
     <div class="tdc-head-actions"><?php if(!empty($scheduleConsultants)):?><button class="tdc-btn tdc-btn-schedule" type="button" data-client-consultant-schedule><i class="fa-regular fa-calendar-plus"></i>Agendar para consultor</button><?php endif;?><?php if(empty($sharedUnassigned)):?><a class="tdc-btn" href="<?=APP_URL?>/clients/<?=$client['id']?>/edit"><i class="fa-regular fa-pen-to-square"></i>Editar</a><?php endif;?><?php if($operationalAccess&&sales_flow_enabled()&&!$isLocal):?><a class="tdc-btn" href="<?=APP_URL?>/opportunities?client_id=<?=$client['id']?>"><i class="fa-solid fa-chart-column"></i>Oportunidade</a><?php endif;?><?php if($operationalAccess&&!$isLocal):?><a class="tdc-btn tdc-btn-primary" href="<?=APP_URL?>/orders/new?client_id=<?=$client['id']?>"><i class="fa-solid fa-plus"></i>Novo pedido</a><?php endif;?></div>
    </header>
    <?php if($flash):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>

    <div class="tdc-responsibility">
     <div><span>Principal no CRM</span><strong><?=e((string)$principalSellerName)?></strong><small>Responsabilidade definitiva do cadastro</small></div>
     <div class="<?=!empty($portfolioAssignment)?'monthly':''?>"><span>Carteira <?=$portfolioMonth?date('m/Y',strtotime($portfolioMonth.'-01')):date('m/Y')?></span><strong><?=e((string)$effectiveSellerName)?></strong><small><?=!empty($portfolioAssignment)?'Exceção provisória do mês':'Usando automaticamente o vendedor principal'?></small></div>
     <div class="<?=trim((string)($client['seller_omie_code']??''))!==trim((string)($client['omie_seller_code']??''))?'divergent':''?>"><span>Vendedor na Omie</span><strong><?=e((string)$omieSellerName)?></strong><small><?=trim((string)($client['seller_omie_code']??''))!==trim((string)($client['omie_seller_code']??''))?'Divergente do principal; pode ser sincronizado':'Alinhado com o cadastro principal'?></small></div>
    </div>
    <?php if(($loggedUser['role']??'')==='seller'&&!$operationalAccess):?><div class="tdc-sync shared"><span class="icon"><i class="fa-solid fa-user-lock"></i></span><div><strong>Cadastro liberado para correção</strong><p>Você pode revisar e corrigir este cliente, mas os contatos e pedidos deste mês pertencem a <?=e((string)$effectiveSellerName)?>.</p></div></div><?php endif;?>

    <?php if(!empty($sharedUnassigned)):?>
     <div class="tdc-sync shared"><span class="icon"><i class="fa-solid fa-users"></i></span><div><strong>Cliente compartilhado, sem vendedor</strong><p>Qualquer vendedor pode atender, registrar contatos e criar pedidos. O vínculo de carteira é feito por admin ou supervisor.</p></div></div>
    <?php elseif($pendingOmie):?>
     <div class="tdc-sync pending"><span class="icon"><i class="fa-solid fa-arrows-rotate"></i></span><div><strong><?=$isLocal?'Cliente salvo localmente':'Alterações salvas localmente'?></strong><span><?=$isLocal?'O CRM verificará o CPF/CNPJ na Omie antes de criar para evitar duplicidade.':'Existem alterações locais pendentes de sincronização.'?></span></div><form method="post" action="<?=APP_URL?>/clients/<?=$client['id']?>/omie-sync"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="tdc-btn tdc-btn-primary" data-confirm="Sincronizar agora este cliente com a Omie?"><i class="fa-solid fa-arrows-rotate"></i>Sincronizar Omie</button></form></div>
    <?php else:?>
     <div class="tdc-sync"><i class="fa-solid fa-circle-check"></i><div><strong>Sincronizado com a Omie</strong><span>Cadastro vinculado ao código Omie <?=e((string)$client['omie_code'])?>. Não há alterações locais pendentes.</span></div></div>
    <?php endif;?>

    <nav class="tdc-tabs"><a class="active" href="#tdc-overview"><i class="fa-solid fa-table-columns"></i>Visão geral</a><?php if(sales_flow_enabled()):?><a href="<?=APP_URL?>/opportunities?client_id=<?=$client['id']?>"><i class="fa-solid fa-chart-column"></i>Oportunidades</a><?php endif;?><a href="#tdc-history"><i class="fa-regular fa-comments"></i>Histórico</a><a href="#tdc-orders"><i class="fa-solid fa-receipt"></i>Pedidos</a></nav>

    <div class="tdc-detail-kpis" id="tdc-overview">
     <article class="tdc-detail-kpi"><span class="icon green"><i class="fa-solid fa-wave-square"></i></span><small>Momento</small><strong><?=e($cycle['label'])?></strong></article>
     <article class="tdc-detail-kpi"><span class="icon blue"><i class="fa-solid fa-chart-line"></i></span><small>Receita 12 meses</small><strong><?=money($client['revenue_12m']??0)?></strong></article>
     <article class="tdc-detail-kpi"><span class="icon yellow"><i class="fa-regular fa-calendar-check"></i></span><small>Última compra</small><strong><?=brdate($client['last_purchase_at']??null)?></strong></article>
     <article class="tdc-detail-kpi"><span class="icon orange"><i class="fa-solid fa-cart-shopping"></i></span><small>Pedidos 12 meses</small><strong><?=(int)($client['orders_12m']??0)?></strong></article>
    </div>

    <div class="tdc-detail-grid">
     <section class="tdc-card">
      <div class="tdc-card-head"><div class="tdc-card-title"><span><i class="fa-solid fa-building"></i></span><div><strong>Dados do cliente</strong><small>Cadastro principal e informações comerciais.</small></div></div></div>
      <div class="tdc-info-grid">
       <div><span>Razão social / Nome</span><strong><?=e($formData['legal_name']?:'—')?></strong></div><div><span>Nome fantasia</span><strong><?=e($formData['trade_name']?:'—')?></strong></div>
       <div><span>CPF / CNPJ</span><strong><?=e($formData['document']?:'—')?></strong></div><div><span>E-mail</span><strong><?=e($formData['email']?:'—')?></strong></div>
       <div><span>Telefone</span><strong><?=e(trim(($formData['phone_ddd']?:'').' '.($formData['phone_number']?:''))?:'—')?></strong></div><div><span>Vendedor principal</span><strong><?=e((string)$principalSellerName)?></strong><small>Carteira efetiva: <?=e((string)$effectiveSellerName)?></small></div>
       <div class="wide"><span>Endereço</span><strong><?=e(trim(($formData['address']??'').' '.($formData['address_number']??'').(($formData['complement']??'')?' • '.$formData['complement']:''))?:'—')?></strong><small><?=e(trim(($formData['neighborhood']??'').' • '.($formData['city']??'').' / '.($formData['uf']??''),' •/'))?><?=($formData['zip_code']??'')?' • CEP '.e($formData['zip_code']):''?></small></div>
       <div class="wide"><span>Tags</span><div class="tdc-tags"><?php foreach(array_filter(array_map('trim',explode(',',(string)($formData['tags']??'')))) as $tag):?><span><?=e($tag)?></span><?php endforeach;?></div></div>
       <div class="wide"><span>Observações</span><strong><?=nl2br(e($formData['notes']?:'—'))?></strong></div>
      </div>
     </section>
     <section class="tdc-card">
      <div class="tdc-card-head"><div class="tdc-card-title"><span><i class="fa-solid fa-headset"></i></span><div><strong>Registrar contato</strong><small>Atualize o relacionamento e programe o próximo passo.</small></div></div></div>
      <?php if($operationalAccess):?>
       <form class="tdc-contact-form" method="post" action="<?=APP_URL?>/clients/<?=$client['id']?>/activity"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><label>Canal utilizado</label><select class="form-select" name="channel"><option value="phone">Ligação</option><option value="whatsapp">WhatsApp</option><option value="email">E-mail</option></select><label>Resultado do contato</label><select class="form-select" name="result"><?php foreach($taskResults??[] as $resultOption):?><option value="<?=e($resultOption['code'])?>"><?=e($resultOption['label'])?></option><?php endforeach;?></select><label>Próximo retorno</label><input class="form-control" type="datetime-local" name="next_at"><label>Anotação</label><textarea class="form-control" name="notes" rows="4"></textarea><button class="tdc-btn tdc-btn-primary w-100 mt-2"><i class="fa-solid fa-check"></i>Salvar contato</button></form>
       <div class="tdc-directed-action"><div><strong>Precisa passar este retorno para outro consultor?</strong><small>Crie apenas o compromisso. A carteira permanece com o responsável atual.</small></div><button class="tdc-btn tdc-btn-schedule" type="button" data-client-consultant-schedule><i class="fa-regular fa-calendar-plus"></i>Agendar para outro consultor</button></div>
      <?php else:?>
       <div class="tdc-contact-locked"><i class="fa-solid fa-user-lock"></i><strong>Atendimento de outra carteira</strong><p>O cliente continua pertencendo a <?=e((string)$effectiveSellerName)?>, mas você pode direcionar um retorno para outro consultor sem alterar a carteira.</p><button class="tdc-btn tdc-btn-schedule" type="button" data-client-consultant-schedule><i class="fa-regular fa-calendar-plus"></i>Agendar para consultor</button></div>
      <?php endif;?>
     </section>
    </div>

    <div class="tdc-history-grid" id="tdc-history">
     <section class="tdc-card"><div class="tdc-card-head"><div class="tdc-card-title"><span><i class="fa-regular fa-comments"></i></span><div><strong>Últimos contatos</strong><small>Histórico de relacionamento.</small></div></div></div><div class="tdc-list"><?php $resultLabels=$taskResultLabels??[];foreach($activities as $a):?><div><strong><?=e($resultLabels[$a['result']]??$a['result'])?></strong><small><?=e($a['user_name'])?> • <?=date('d/m/Y H:i',strtotime($a['created_at']))?></small><?php if($a['notes']):?><p><?=nl2br(e($a['notes']))?></p><?php endif;?></div><?php endforeach;?><?php if(!$activities):?><div>Nenhum contato registrado.</div><?php endif;?></div></section>
     <section class="tdc-card" id="tdc-orders"><div class="tdc-card-head"><div class="tdc-card-title"><span><i class="fa-solid fa-receipt"></i></span><div><strong>Últimos pedidos</strong><small>Compras mais recentes do cliente.</small></div></div></div><div class="tdc-list"><?php foreach($orders as $o):?><div><a href="<?=APP_URL?>/orders/<?=(int)$o['id']?>"><strong><?=brdate($o['order_date'])?> · <?=e($o['number']??$o['omie_code'])?></strong></a><small><?=money($o['total'])?></small></div><?php endforeach;?><?php if(!$orders):?><div>Nenhum pedido encontrado.</div><?php endif;?></div></section>
    </div>
    <?php if(Auth::can('admin','supervisor')&&!empty($sellerAudit)):?>
    <section class="tdc-card tdc-seller-audit">
     <div class="tdc-card-head"><div class="tdc-card-title"><span><i class="fa-solid fa-clock-rotate-left"></i></span><div><strong>Histórico de responsabilidade</strong><small>Alterações do vendedor principal, carteira mensal e alinhamento com a Omie.</small></div></div></div>
     <div class="tdc-seller-audit-list">
      <?php foreach($sellerAudit as $change):$type=(string)($change['change_type']??'');$monthLabel=!empty($change['month_ref'])?date('m/Y',strtotime($change['month_ref'].'-01')):null;$previous=$change['previous_seller_name']??$change['previous_seller_omie_code']??'Sem vendedor';$next=$change['new_seller_name']??$change['new_seller_omie_code']??'Sem vendedor';?>
       <div><span class="icon"><i class="fa-solid <?=$type==='monthly_assignment'?'fa-calendar-check':($type==='monthly_reset'?'fa-rotate-left':'fa-user-pen')?>"></i></span><div><strong><?=$type==='monthly_assignment'?'Carteira provisória alterada':($type==='monthly_reset'?'Retorno ao vendedor principal':'Vendedor principal alterado')?></strong><p><?=e((string)$previous)?> <i class="fa-solid fa-arrow-right"></i> <?=e((string)$next)?><?=$monthLabel?' · '.$monthLabel:''?></p><small><?=e($change['actor_name']?:'Sistema')?> · <?=date('d/m/Y H:i',strtotime((string)$change['created_at']))?><?=!empty($change['notes'])?' · '.e($change['notes']):''?></small></div></div>
      <?php endforeach;?>
     </div>
    </section>
    <?php endif;?>
    <?php if(!empty($scheduleConsultants)):?>
    <dialog class="tdc-schedule-dialog" data-client-consultant-dialog>
     <form method="post" action="<?=APP_URL?>/clients/<?=$client['id']?>/schedule-consultant" data-client-consultant-form>
      <input type="hidden" name="_token" value="<?=CSRF::token()?>">
      <header><span><i class="fa-regular fa-calendar-plus"></i></span><div><small>AGENDAMENTO DIRECIONADO</small><strong>Agendar para consultor</strong><p><?=e($client['name'])?> · carteira atual: <?=e((string)$effectiveSellerName)?></p></div><button type="button" data-client-consultant-close aria-label="Fechar"><i class="fa-solid fa-xmark"></i></button></header>
      <div class="tdc-schedule-body">
       <label><span>Consultor que receberá o retorno</span><select class="form-select" name="assigned_user_id" required><option value="">Selecione o consultor</option><?php foreach($scheduleConsultants as $consultant):?><option value="<?=(int)$consultant['id']?>"><?=e($consultant['name'])?></option><?php endforeach;?></select></label>
       <label><span>Data e hora</span><input class="form-control" type="datetime-local" name="due_at" required data-client-consultant-due></label>
       <label class="wide"><span>Motivo / orientação para o consultor</span><input class="form-control" name="title" maxlength="180" placeholder="Ex.: Cliente ligou e pediu retorno sobre proposta" required></label>
       <div class="tdc-schedule-note wide"><i class="fa-solid fa-circle-info"></i><span>Esse agendamento <strong>não altera o vendedor principal nem a carteira mensal</strong>. Apenas cria uma tarefa na agenda do consultor escolhido.</span></div>
      </div>
      <footer><button class="tdc-btn" type="button" data-client-consultant-close>Cancelar</button><button class="tdc-btn tdc-btn-primary" type="submit"><i class="fa-solid fa-calendar-check"></i>Criar agendamento</button></footer>
     </form>
    </dialog>
    <?php endif;?>
   </section>
  <?php break;

  case 'products':
   $productStats=$productStats??['total'=>0,'active'=>0,'inactive'=>0,'priced'=>0];
   $lastProductSync=$productSyncState['last_success_at']??null;$productSyncError=trim((string)($productSyncState['last_error']??''));
   $productDataParams=['status'=>$productStatus??'active'];if(!empty($productUnit))$productDataParams['unit']=$productUnit;
   ?>
   <section class="tdp-page">
    <header class="tdp-head">
     <div class="tdp-head-main"><span class="tdp-head-icon"><i class="fa-solid fa-boxes-stacked"></i></span><div><span class="tdp-kicker">COMERCIAL / CATÁLOGO</span><h1>Produtos</h1><p>Consulte preços, estoque e dados comerciais usando a cópia local sincronizada da Omie.</p></div></div>
     <div class="tdp-head-actions"><a class="tdp-btn" href="<?=APP_URL?>/products"><i class="fa-solid fa-rotate-right"></i>Atualizar tela</a><?php if(Auth::can('admin')):?><a class="tdp-btn sync" href="<?=APP_URL?>/sync"><i class="fa-solid fa-cloud-arrow-down"></i>Sincronizar Omie</a><?php endif;?><a class="tdp-btn primary" href="<?=APP_URL?>/orders/new"><i class="fa-solid fa-cart-plus"></i>Novo pedido</a></div>
    </header>

    <?php if($productSyncError!==''):?><div class="tdp-alert error"><i class="fa-solid fa-triangle-exclamation"></i><div><strong>Última sincronização com atenção</strong><span><?=e($productSyncError)?></span></div><?php if(Auth::can('admin')):?><a href="<?=APP_URL?>/sync">Revisar</a><?php endif;?></div><?php endif;?>

    <div class="tdp-kpis">
     <article><span class="blue"><i class="fa-solid fa-boxes-stacked"></i></span><div><small>Total no catálogo</small><strong><?=number_format((int)$productStats['total'],0,',','.')?></strong><em>Produtos locais</em></div></article>
     <article><span class="green"><i class="fa-solid fa-circle-check"></i></span><div><small>Ativos</small><strong><?=number_format((int)$productStats['active'],0,',','.')?></strong><em>Disponíveis para pedidos</em></div></article>
     <article><span class="yellow"><i class="fa-solid fa-tag"></i></span><div><small>Com preço</small><strong><?=number_format((int)$productStats['priced'],0,',','.')?></strong><em>Ativos com valor de venda</em></div></article>
     <article><span class="slate"><i class="fa-solid fa-clock-rotate-left"></i></span><div><small>Última sincronização</small><strong><?=$lastProductSync?date('d/m/Y H:i',strtotime((string)$lastProductSync)):'Nunca'?></strong><em><?=$productSyncError!==''?'Requer atenção':'Catálogo local'?></em></div></article>
    </div>

    <section class="tdp-panel">
     <header><div><span><i class="fa-solid fa-table-list"></i></span><div><strong>Catálogo de produtos</strong><small>A busca acontece no CRM e não gera consultas extras na Omie.</small></div></div><b><?=number_format((int)$productStats['inactive'],0,',','.')?> inativo(s)</b></header>
     <form class="tdp-filters" method="get" action="<?=APP_URL?>/products">
      <label><span>Situação</span><select class="form-select" name="status" onchange="this.form.submit()"><?php foreach(['active'=>'Somente ativos','inactive'=>'Somente inativos','all'=>'Todos os produtos'] as $value=>$label):?><option value="<?=$value?>" <?=($productStatus??'active')===$value?'selected':''?>><?=$label?></option><?php endforeach;?></select></label>
      <label><span>Unidade</span><select class="form-select" name="unit" onchange="this.form.submit()"><option value="">Todas as unidades</option><?php foreach($productUnits??[] as $row):?><option value="<?=e(mb_strtoupper((string)$row['unit']))?>" <?=($productUnit??'')===mb_strtoupper((string)$row['unit'])?'selected':''?>><?=e((string)$row['unit'])?> (<?=number_format((int)$row['total'],0,',','.')?>)</option><?php endforeach;?></select></label>
      <?php if(($productStatus??'active')!=='active'||!empty($productUnit)):?><a href="<?=APP_URL?>/products"><i class="fa-solid fa-rotate-left"></i>Limpar filtros</a><?php endif;?>
     </form>
     <div class="table-card tdp-table-wrap">
      <table class="table tdp-table" data-server-url="<?=e(APP_URL.'/api/products/datatable?'.http_build_query($productDataParams))?>" data-page-length="5" data-length-change="1" data-order-column="0" data-order-direction="asc">
       <thead><tr><th>Produto</th><th>SKU</th><th>Unidade</th><th class="text-end">Preço</th><th class="text-end">Estoque</th><th data-dt-order="disable">Pesos</th><th>NCM</th><th>Situação</th><th>Atualizado em</th><th data-dt-order="disable">Ações</th></tr></thead><tbody></tbody>
      </table>
     </div>
    </section>
   </section>
  <?php break;

  case 'contact_monitoring':
   $monitorStats=$monitorStats??['total'=>0,'contacted'=>0,'scheduled'=>0,'overdue'=>0,'without_next'=>0];
   $resultLabels=[];foreach(task_result_catalog() as $resultItem)$resultLabels[(string)$resultItem['code']]=(string)$resultItem['label'];
   $contactRate=(int)$monitorStats['total']>0?round(((int)$monitorStats['contacted']/(int)$monitorStats['total'])*100,1):0;
   $attentionRows=$monitorAttentionRows??[];$agendaRows=$monitorAgendaRows??[];
   foreach($rows as $monRow){
    if(empty($monRow['last_contact_at'])||empty($monRow['next_due_at'])||strtotime((string)$monRow['next_due_at'])<time())$attentionRows[]=$monRow;
    if(!empty($monRow['next_due_at']))$agendaRows[]=$monRow;
   }
   usort($agendaRows,static fn($a,$b)=>strtotime((string)$a['next_due_at'])<=>strtotime((string)$b['next_due_at']));
   ?>
   <section class="tdcontact4-page">
    <header class="tdcontact4-head"><div><span class="tdcontact4-kicker">GESTÃO / RELACIONAMENTO</span><h1>Acompanhamento</h1><p>Monitore contatos, acompanhe o progresso da equipe e mantenha o relacionamento ativo com os clientes.</p></div><a class="tdcontact4-link" href="<?=APP_URL?>/agenda"><i class="fa-regular fa-calendar-check"></i>Agenda da equipe</a></header>
    <?php if(!empty($flash)):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>

    <section class="tdcontact4-filters"><form method="get"><label><span>Responsável</span><select class="form-select" name="seller_id"><option value="0">Toda a equipe monitorada</option><?php foreach($monitorSellers as $seller):?><option value="<?=(int)$seller['id']?>" <?=(int)$monitorSellerId===(int)$seller['id']?'selected':''?>><?=e($seller['name'])?> · <?=$seller['role']==='collector'?'Cobrança':'Vendas'?></option><?php endforeach;?></select></label><label><span>Situação</span><select class="form-select" name="status"><?php foreach(['all'=>'Todos os clientes','contacted'=>'Já contatados','never'=>'Nunca contatados','scheduled'=>'Com próximo agendado','overdue'=>'Retornos atrasados','without_next'=>'Sem próximo contato'] as $value=>$label):?><option value="<?=$value?>" <?=$monitorStatus===$value?'selected':''?>><?=$label?></option><?php endforeach;?></select></label><a class="tdcontact4-clear" href="<?=APP_URL?>/contact-monitoring"><i class="fa-solid fa-rotate-left"></i>Limpar filtros</a><button class="tdcontact4-primary" type="submit"><i class="fa-solid fa-filter"></i>Aplicar filtros</button></form></section>

    <div class="tdcontact4-kpis">
     <article><span class="green"><i class="fa-solid fa-users"></i></span><div><small>Clientes monitorados</small><strong><?=number_format((int)$monitorStats['total'],0,',','.')?></strong><em>Na visão atual</em></div></article>
     <article><span class="blue"><i class="fa-solid fa-phone"></i></span><div><small>Contatos realizados</small><strong><?=number_format((int)$monitorStats['contacted'],0,',','.')?></strong><em>Com histórico registrado</em></div></article>
     <article><span class="yellow"><i class="fa-regular fa-clock"></i></span><div><small>Sem próxima ação</small><strong><?=number_format((int)$monitorStats['without_next'],0,',','.')?></strong><em><?=number_format((int)$monitorStats['overdue'],0,',','.')?> retorno(s) atrasado(s)</em></div></article>
     <article><span class="red"><i class="fa-solid fa-bullseye"></i></span><div><small>Taxa de contato</small><strong><?=number_format((float)$contactRate,1,',','.')?>%</strong><em>Clientes já trabalhados</em></div></article>
    </div>

    <section class="tdcontact4-panel"><header><div><span><i class="fa-solid fa-chart-simple"></i></span><div><strong>Acompanhamento da equipe</strong><small>Contato, próxima ação e status por cliente.</small></div></div><b><?=number_format((int)$monitorStats['total'],0,',','.')?> clientes</b></header><div class="table-card tdcontact4-table-wrap"><table class="table tdcontact4-table" data-page-length="5" data-length-change="1" data-order-column="3" data-order-direction="desc" data-server-url="<?=e(APP_URL.'/api/contact-monitoring/datatable?'.http_build_query(['seller_id'=>(int)$monitorSellerId,'status'=>(string)$monitorStatus]))?>"><thead><tr><th>Cliente</th><th>Responsável</th><th>Último contato</th><th>Dias sem contato</th><th>Próxima ação</th><th>Resultado</th><th>Status</th><th data-dt-order="disable">Ações</th></tr></thead><tbody></tbody></table></div></section>

    <div class="tdcontact4-bottom">
     <section class="tdcontact4-panel compact"><header><div><span class="attention"><i class="fa-solid fa-triangle-exclamation"></i></span><div><strong>Pontos de atenção</strong><small>Clientes que exigem acompanhamento.</small></div></div><a href="<?=APP_URL?>/contact-monitoring?status=overdue">Ver atrasados</a></header><div class="tdcontact4-list"><?php foreach(array_slice($attentionRows,0,5) as $row):$lt=!empty($row['last_contact_at'])?strtotime((string)$row['last_contact_at']):null;$d=$lt?(int)floor((time()-$lt)/86400):null;?><a href="<?=APP_URL?>/clients/<?=(int)$row['id']?>"><strong><?=e($row['name'])?></strong><span><?=e($row['next_user_name']??$row['portfolio_user_name']??$row['collection_user_name']??'Sem responsável')?></span><b><?=$d===null?'Nunca':$d.' dias'?></b></a><?php endforeach;?><?php if(!$attentionRows):?><div class="tdcontact4-empty">Nenhum ponto crítico nos filtros atuais.</div><?php endif;?></div></section>
     <section class="tdcontact4-panel compact"><header><div><span class="agenda"><i class="fa-regular fa-calendar-check"></i></span><div><strong>Agenda do acompanhamento</strong><small>Próximos contatos agendados.</small></div></div><a href="<?=APP_URL?>/agenda">Agenda completa</a></header><div class="tdcontact4-list agenda"><?php foreach(array_slice($agendaRows,0,5) as $row):$due=strtotime((string)$row['next_due_at']);?><a href="<?=APP_URL?>/clients/<?=(int)$row['id']?>"><strong><?=date('d/m H:i',$due)?> · <?=e($row['name'])?></strong><span><?=e($row['next_user_name']??'Sem responsável')?></span><b><?=e($row['next_title']??'Próximo contato')?></b></a><?php endforeach;?><?php if(!$agendaRows):?><div class="tdcontact4-empty">Nenhum próximo contato agendado.</div><?php endif;?></div></section>
    </div>

    <dialog class="tdcontact-dialog" data-contact-dialog><form method="post" data-contact-schedule-form><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="task_id" value=""><input type="hidden" name="seller_filter" value="<?=(int)$monitorSellerId?>"><input type="hidden" name="status_filter" value="<?=e($monitorStatus)?>"><header><span><i class="fa-regular fa-calendar-plus"></i></span><div><strong data-contact-dialog-title>Agendar próximo contato</strong><small data-contact-dialog-client></small></div><button type="button" data-contact-dialog-close aria-label="Fechar"><i class="fa-solid fa-xmark"></i></button></header><div class="tdcontact-dialog-body"><label>Responsável<select class="form-select" name="assigned_user_id" required><option value="">Selecione o responsável</option><?php foreach($monitorSellers as $seller):?><option value="<?=(int)$seller['id']?>"><?=e($seller['name'])?> · <?=$seller['role']==='collector'?'Cobrança':'Vendas'?></option><?php endforeach;?></select></label><label>Descrição<input class="form-control" name="title" maxlength="180" value="Próximo contato" required></label><label>Data e hora<input class="form-control" type="datetime-local" name="due_at" min="<?=date('Y-m-d\TH:i')?>" required></label></div><footer><button class="tdcontact-btn" type="button" data-contact-dialog-close>Cancelar</button><button class="tdcontact-btn tdcontact-btn-schedule" type="submit"><i class="fa-solid fa-calendar-check"></i>Salvar agendamento</button></footer></form></dialog>
   </section>
  <?php break;

  case 'orders':$success=$_SESSION['success']??null;$error=$_SESSION['error']??null;$periodQuery=(string)($period['query']??'');unset($_SESSION['success'],$_SESSION['error']);?>
   <section class="tdo-page">
    <header class="tdo-head">
     <div class="tdo-head-main">
      <span class="tdo-head-icon"><i class="fa-solid fa-receipt"></i></span>
      <div><span class="tdo-kicker">COMERCIAL / PEDIDOS</span><h1>Pedidos</h1><p>Acompanhe pedidos sincronizados, propostas, etapas, vendedores e valores do período.</p></div>
     </div>
     <div class="tdo-head-actions">
      <div class="tdo-period"><i class="fa-regular fa-calendar-check"></i><div><small>Período</small><strong><?=e((string)($period['label']??''))?></strong></div></div>
      <details class="tdo-period-picker"><summary class="tdo-btn"><i class="fa-regular fa-calendar"></i>Escolher período<i class="fa-solid fa-chevron-down"></i></summary><form method="get" class="tdo-period-popover"><input type="hidden" name="view" value="<?=e($view)?>"><input type="hidden" name="stage" value="<?=e($stageFilter??'')?>"><div class="grid"><label><span>Data inicial</span><input class="form-control" type="date" name="date_from" value="<?=e((string)($period['from']??date('Y-m-01')))?>" required></label><label><span>Data final</span><input class="form-control" type="date" name="date_to" value="<?=e((string)($period['to']??date('Y-m-t')))?>" required></label></div><div class="actions"><button class="tdo-btn tdo-btn-primary" type="submit">Aplicar</button><a class="tdo-btn" href="<?=APP_URL?>/orders?view=<?=e($view)?>&stage=<?=e($stageFilter??'')?>">Mês atual</a><a class="tdo-btn" href="<?=APP_URL?>/orders?period=all&view=<?=e($view)?>&stage=<?=e($stageFilter??'')?>">Todos</a></div></form></details>
      <a class="tdo-btn tdo-btn-primary" href="<?=APP_URL?>/orders/new"><i class="fa-solid fa-plus"></i>Novo pedido</a>
     </div>
    </header>

    <?php if($success):?><div class="alert alert-success"><?=e($success)?></div><?php endif;?>
    <?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>

    <?php if(!empty($drafts)):?>
    <section class="tdo-drafts">
     <div class="tdo-drafts-head"><div class="tdo-drafts-title"><span><i class="fa-regular fa-floppy-disk"></i></span><div><strong>Rascunhos locais</strong><small>Pedidos ainda não enviados para a Omie.</small></div></div><b><?=count($drafts)?></b></div>
     <?php foreach($drafts as $d):?><div class="tdo-draft-row"><div class="tdo-draft-main"><strong><?=e($d['client_name']??'Pedido sem cliente definido')?></strong><small>Salvo por <?=e($d['author_name']??'—')?> · atualizado <?=date('d/m/Y H:i',strtotime($d['updated_at']))?></small></div><div><span>Vendedor</span><strong><?=e($d['seller_name']??($d['seller_omie_code']??'Não definido'))?></strong></div><div><span>Total estimado</span><strong><?=money($d['total'])?></strong></div><div class="tdo-draft-actions"><a class="tdo-btn" href="<?=APP_URL?>/orders/new?draft_id=<?=(int)$d['id']?>"><i class="fa-regular fa-pen-to-square"></i>Continuar</a><a class="tdo-btn" href="<?=APP_URL?>/orders/drafts/<?=(int)$d['id']?>/pdf" target="_blank" rel="noopener"><i class="fa-regular fa-file-pdf"></i>PDF</a><form method="post" action="<?=APP_URL?>/orders/drafts/<?=(int)$d['id']?>/delete"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="tdo-icon-btn danger" type="submit" data-confirm="Excluir este rascunho local?"><i class="fa-regular fa-trash-can"></i></button></form></div></div><?php endforeach;?>
    </section>
    <?php endif;?>

    <div class="tdo-kpis">
     <article class="tdo-kpi blue"><div class="tdo-kpi-top"><span class="tdo-kpi-icon"><i class="fa-solid fa-receipt"></i></span></div><small><?=$view==='budget'?'Orçamentos em análise':'Pedidos encontrados'?></small><strong><?=number_format((int)$totalRows,0,',','.')?></strong><p><?=e((string)($period['label']??''))?></p></article>
     <article class="tdo-kpi green"><div class="tdo-kpi-top"><span class="tdo-kpi-icon"><i class="fa-solid fa-sack-dollar"></i></span></div><small><?=$view==='budget'?'Valor em orçamento':'Pedidos OK'?></small><strong><?=money($view==='budget'?$budgetValue:$total)?></strong><p><?=$view==='budget'?'Fora das métricas oficiais':'Somente pedidos válidos'?></p></article>
     <article class="tdo-kpi teal"><div class="tdo-kpi-top"><span class="tdo-kpi-icon"><i class="fa-solid fa-circle-check"></i></span></div><small>Faturados</small><strong><?=(int)$billed?></strong><p>Pedidos já faturados</p></article>
     <article class="tdo-kpi yellow"><div class="tdo-kpi-top"><span class="tdo-kpi-icon"><i class="fa-solid fa-clock"></i></span></div><small>Em andamento</small><strong><?=(int)$active?></strong><p>Ativos no período</p></article>
     <article class="tdo-kpi red"><div class="tdo-kpi-top"><span class="tdo-kpi-icon"><i class="fa-solid fa-ban"></i></span></div><small>Cancelados</small><strong><?=(int)$cancelled?></strong><p>Fora do resultado</p></article>
    </div>

    <section class="tdo-shell">
     <div class="tdo-shell-top">
      <nav class="tdo-tabs" aria-label="Tipos de pedido">
       <a class="tdo-tab <?=$view==='all'?'active':''?>" href="<?=APP_URL?>/orders?<?=e($periodQuery)?>&view=all"><span class="tdo-tab-icon"><i class="fa-solid fa-circle-check"></i></span><span><strong>Pedidos confirmados</strong><small>Pedidos OK</small></span><b><?=number_format((int)$allOrders,0,',','.')?></b></a>
       <a class="tdo-tab budget <?=$view==='budget'?'active':''?>" href="<?=APP_URL?>/orders?<?=e($periodQuery)?>&view=budget"><span class="tdo-tab-icon"><i class="fa-solid fa-file-signature"></i></span><span><strong>Em orçamento</strong><small>Propostas</small></span><span class="tdo-tab-metric"><b><?=number_format((int)$budgetOrders,0,',','.')?></b><small><?=money($budgetValue)?></small></span></a>
      </nav>
      <form class="tdo-stage-filter" method="get"><?php if(!empty($period['all'])):?><input type="hidden" name="period" value="all"><?php else:?><input type="hidden" name="date_from" value="<?=e((string)$period['from'])?>"><input type="hidden" name="date_to" value="<?=e((string)$period['to'])?>"><?php endif;?><input type="hidden" name="view" value="<?=e($view)?>"><span><i class="fa-solid fa-filter"></i>Filtrar por etapa</span><div class="tdo-stage-radios"><label class="<?=($stageFilter??'')===''?'active':''?>"><input type="radio" name="stage" value="" <?=($stageFilter??'')===''?'checked':''?> onchange="this.form.submit()"><span>Todas</span></label><?php foreach($stages??[] as $stage):$code=(string)$stage['code'];$stageIsBudget=in_array($code,$budgetCodes??['00','10'],true);if(($view==='budget'&&!$stageIsBudget)||($view==='all'&&$stageIsBudget))continue;?><label class="<?=($stageFilter??'')===$code?'active':''?>"><input type="radio" name="stage" value="<?=e($code)?>" <?=($stageFilter??'')===$code?'checked':''?> onchange="this.form.submit()"><span><b><?=e($code)?></b><?=e($stage['name'])?></span></label><?php endforeach;?></div></form>
     </div>

     <?php $orderDataParams=['view'=>$view];if(!empty($period['all']))$orderDataParams['period']='all';else{$orderDataParams['date_from']=$period['from'];$orderDataParams['date_to']=$period['to'];}if(($stageFilter??'')!=='')$orderDataParams['stage']=$stageFilter;?>
     <div class="tdo-table-head"><div class="tdo-table-title"><span><i class="fa-solid <?=$view==='budget'?'fa-magnifying-glass-chart':'fa-table-list'?>"></i></span><div><strong><?=$view==='budget'?'Fila de propostas':'Pedidos sincronizados'?></strong><small><?=$view==='budget'?'Revise cliente, vendedor, etapa, data e valor.':'Use a busca para localizar pedido, cliente, vendedor, etapa ou status.'?></small></div></div><?php if($withoutSeller>0):?><div class="tdo-warning"><i class="fa-solid fa-triangle-exclamation"></i><?=$withoutSeller?> pedido(s) sem vendedor</div><?php endif;?></div>
     <div class="table-card tdo-table-wrap">
      <table class="table orders-datatable tdo-table" data-server-url="<?=APP_URL?>/api/orders/datatable?<?=e(http_build_query($orderDataParams))?>" data-page-length="10" data-length-change="1" data-order-column="3" data-order-direction="desc">
       <thead><tr><th>Pedido</th><th>Cliente</th><th>Vendedor</th><th>Data</th><th>Etapa</th><th>Status</th><th class="text-end">Valor</th><th class="text-end" data-dt-order="disable">Ações</th></tr></thead>
       <tbody></tbody>
      </table>
     </div>
    </section>
   </section>
  <?php break;
  case 'order_detail':
   $order=$detail['order'];$raw=$detail['raw'];$items=$detail['items'];$header=(array)($raw['cabecalho']??[]);$info=(array)($raw['informacoes_adicionais']??[]);$freight=(array)($raw['frete']??[]);$registration=(array)($raw['infoCadastro']??[]);$installments=(array)($raw['lista_parcelas']['parcela']??[]);$status=(string)($order['status']??'ATIVO');$upper=mb_strtoupper($status);$isBudget=in_array((string)($order['stage_code']??''),OrderPolicy::budgetStageCodes(),true);$statusClass=str_contains($upper,'CANCEL')?'cancelled':(str_contains($upper,'FATUR')||($registration['faturado']??'N')==='S'?'billed':($isBudget?'budget':'active'));$actionError=$_SESSION['error']??null;$actionSuccess=$_SESSION['success']??null;unset($_SESSION['error'],$_SESSION['success']);
   $freightValue=(float)($freight['valor_frete']??0);$withoutFreight=max(0,(float)$order['total']-$freightValue);
   ?>
   <section class="tdo-page tdo-detail">
    <header class="tdo-head">
     <div class="tdo-head-main"><span class="tdo-head-icon"><i class="fa-solid fa-receipt"></i></span><div><a class="tdo-kicker" href="<?=APP_URL?>/orders"><i class="fa-solid fa-arrow-left"></i> PEDIDOS / DETALHE</a><h1>Pedido <?=e($order['number']??$order['omie_code'])?></h1><p><?=e($order['client_name']??'Cliente não identificado')?> · <?=e($order['seller_name']??$order['seller_omie_code']??'Sem vendedor')?> · <?=brdate($order['order_date'])?></p></div></div>
     <div class="tdo-head-actions"><?php if($statusClass==='budget'):?><a class="tdo-btn tdo-btn-primary" href="<?=APP_URL?>/orders/<?=(int)$order['id']?>/edit"><i class="fa-regular fa-pen-to-square"></i>Editar proposta</a><?php endif;?><a class="tdo-btn" href="<?=APP_URL?>/orders/<?=(int)$order['id']?>/pdf" target="_blank" rel="noopener"><i class="fa-regular fa-file-pdf"></i>Gerar PDF</a><a class="tdo-btn" href="<?=APP_URL?>/orders/<?=(int)$order['id']?>/duplicate"><i class="fa-regular fa-copy"></i>Duplicar</a><?php if(Auth::can('admin')):?><form method="post" action="<?=APP_URL?>/orders/<?=(int)$order['id']?>/delete"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="tdo-btn tdo-btn-danger" type="submit" data-confirm="Excluir definitivamente este pedido da Omie e do CRM?"><i class="fa-regular fa-trash-can"></i>Excluir</button></form><?php endif;?></div>
    </header>

    <?php if($actionSuccess):?><div class="alert alert-success"><?=e($actionSuccess)?></div><?php endif;?>
    <?php if($actionError):?><div class="alert alert-danger"><?=e($actionError)?></div><?php endif;?>

    <div class="tdo-detail-summary">
     <div><span>Etapa atual</span><strong><?=e($order['stage_name']??'Etapa não identificada')?></strong><small>Código <?=e($order['stage_code']??'—')?><?=$isBudget?' · proposta fora da meta':''?></small></div>
     <div><span>Status</span><strong><span class="tdo-status <?=$statusClass?>"><?=e($status)?></span></strong><small><?=($registration['faturado']??'N')==='S'?'Faturado na Omie':'Atualizado '.date('d/m/Y H:i',strtotime($order['updated_at']))?></small></div>
     <div><span>Omie</span><strong><?=e($order['omie_code'])?></strong><small>Identificador sincronizado</small></div>
    </div>

    <div class="tdo-detail-kpis">
     <article><span><i class="fa-solid fa-sack-dollar"></i></span><small>Com frete</small><strong><?=money($order['total'])?></strong></article>
     <article><span><i class="fa-solid fa-box-open"></i></span><small>Sem frete</small><strong><?=money($withoutFreight)?></strong></article>
     <article><span><i class="fa-solid fa-truck"></i></span><small>Frete</small><strong><?=money($freightValue)?></strong></article>
     <article><span><i class="fa-solid fa-boxes-stacked"></i></span><small>Itens</small><strong><?=number_format(count($items),0,',','.')?></strong></article>
     <article><span><i class="fa-regular fa-calendar-check"></i></span><small>Previsão</small><strong><?=brdate($order['forecast_date'])?></strong></article>
    </div>

    <section class="tdo-detail-card">
     <div class="tdo-detail-card-head"><div><span class="tdo-section-icon blue"><i class="fa-solid fa-boxes-stacked"></i></span><div><strong>Itens do pedido</strong><small>Produtos e valores registrados na Omie.</small></div></div></div>
     <div class="tdo-detail-items">
      <div class="tdo-detail-item head"><span>Produto</span><span>Qtd.</span><span>Unitário</span><span>Desconto</span><span>Total</span></div>
      <?php foreach($items as $row):$p=(array)$row['product'];?>
      <div class="tdo-detail-item"><span><strong><?=e($p['descricao']??'Produto')?></strong><small><?=e($p['codigo']??$p['codigo_produto']??'')?></small></span><span><?=number_format((float)($p['quantidade']??0),2,',','.')?> <?=e($p['unidade']??'')?></span><span><?=money($p['valor_unitario']??0)?></span><span><?=(float)($p['valor_desconto']??0)>0?money($p['valor_desconto']):number_format((float)($p['percentual_desconto']??0),2,',','.').'%'?></span><span><strong><?=money($p['valor_total']??0)?></strong></span></div>
      <?php endforeach;?>
     </div>
    </section>

    <div class="tdo-detail-grid">
     <section class="tdo-detail-card"><div class="tdo-detail-card-head"><div><span class="tdo-section-icon green"><i class="fa-solid fa-briefcase"></i></span><div><strong>Condições comerciais</strong><small>Parâmetros do pedido.</small></div></div></div><dl class="tdo-detail-data"><div><dt>Categoria</dt><dd><?=e($info['codigo_categoria']??'—')?></dd></div><div><dt>Conta corrente</dt><dd><?=e($info['codigo_conta_corrente']??'—')?></dd></div><div><dt>Condição de pagamento</dt><dd><?=e($header['codigo_parcela']??'—')?></dd></div><div><dt>Consumidor final</dt><dd><?=($info['consumidor_final']??'N')==='S'?'Sim':'Não'?></dd></div><div><dt>Contato</dt><dd><?=e($info['contato']??'—')?></dd></div><div><dt>Pedido do cliente</dt><dd><?=e($info['numero_pedido_cliente']??'—')?></dd></div></dl></section>
     <section class="tdo-detail-card"><div class="tdo-detail-card-head"><div><span class="tdo-section-icon orange"><i class="fa-solid fa-truck"></i></span><div><strong>Frete e entrega</strong><small>Informações logísticas.</small></div></div></div><dl class="tdo-detail-data"><div><dt>Modalidade</dt><dd><?=e($freight['modalidade']??'—')?></dd></div><div><dt>Transportadora</dt><dd><?=e($freight['codigo_transportadora']??'—')?></dd></div><div><dt>Valor do frete</dt><dd><?=money($freightValue)?></dd></div><div><dt>Peso bruto</dt><dd><?=number_format((float)($freight['peso_bruto']??0),3,',','.')?> kg</dd></div><div><dt>Volumes</dt><dd><?=e($freight['quantidade_volumes']??'—')?></dd></div><div><dt>Rastreio</dt><dd><?=e($freight['codigo_rastreio']??'—')?></dd></div></dl></section>
    </div>

    <?php if($installments):?><section class="tdo-detail-card"><div class="tdo-detail-card-head"><div><span class="tdo-section-icon blue"><i class="fa-regular fa-credit-card"></i></span><div><strong>Parcelas</strong><small>Condição financeira sincronizada.</small></div></div></div><div class="tdo-installments"><?php foreach($installments as $parcel):?><div><span>Parcela <?=number_format((int)($parcel['numero_parcela']??0),0,',','.')?></span><strong><?=money($parcel['valor']??0)?></strong><small>Vencimento <?=e($parcel['data_vencimento']??'—')?></small></div><?php endforeach;?></div></section><?php endif;?>

    <?php if(!empty($raw['observacoes']['obs_venda'])):?><section class="tdo-detail-card tdo-notes"><div class="tdo-detail-card-head"><div><span class="tdo-section-icon yellow"><i class="fa-regular fa-note-sticky"></i></span><div><strong>Observações</strong><small>Informações registradas na venda.</small></div></div></div><p><?=nl2br(e((string)$raw['observacoes']['obs_venda']))?></p></section><?php endif;?>
   </section>
  <?php break;
  case 'services':?>
   <section class="tds-page">
    <header class="tds-head">
     <div class="tds-head-main">
      <span class="tds-head-icon"><i class="fa-solid fa-screwdriver-wrench"></i></span>
      <div><span class="tds-kicker">COMERCIAL / SERVIÇOS</span><h1>Ordens de serviço</h1><p>Acompanhe as OS sincronizadas da Omie. Serviços são operacionais e não compõem o relatório de Pedidos OK.</p></div>
     </div>
     <div class="tds-head-actions">
      <div class="tds-period"><i class="fa-regular fa-calendar-check"></i><div><small>Período</small><strong><?=e((string)($period['label']??''))?></strong></div></div>
      <details class="tds-period-picker"><summary class="tds-btn"><i class="fa-regular fa-calendar"></i>Escolher período</summary><form method="get" class="tds-period-popover"><div class="grid"><label><span>Data inicial</span><input class="form-control" type="date" name="date_from" value="<?=e((string)($period['from']??date('Y-m-01')))?>" required></label><label><span>Data final</span><input class="form-control" type="date" name="date_to" value="<?=e((string)($period['to']??date('Y-m-t')))?>" required></label></div><div class="actions"><button class="tds-btn tds-btn-primary" type="submit">Aplicar</button><a class="tds-btn" href="<?=APP_URL?>/services">Mês atual</a><a class="tds-btn" href="<?=APP_URL?>/services?period=all">Todos</a></div></form></details>
     </div>
    </header>

    <?php if(!empty($serviceError)):?><div class="alert alert-danger"><strong>Erro ao carregar Serviços:</strong> <?=e($serviceError)?></div><?php elseif(!empty($serviceSchema)):?><div class="alert alert-info"><strong>Estrutura detectada:</strong> código <code><?=e($serviceSchema['code'])?></code> · data <code><?=e($serviceSchema['date'])?></code></div><?php endif;?>

    <?php if(!empty($health)):?><div class="tds-health"><div><span>Base local</span><strong><?=number_format((int)($health['total_table']??0),0,',','.')?></strong></div><div><span>Sem data</span><strong><?=number_format((int)($health['null_dates']??0),0,',','.')?></strong></div><div><span>Sem vendedor</span><strong><?=number_format((int)($health['null_sellers']??0),0,',','.')?></strong></div></div><?php endif;?>

    <div class="tds-kpis">
     <article class="tds-kpi blue"><span class="tds-kpi-icon"><i class="fa-solid fa-screwdriver-wrench"></i></span><small>Ordens encontradas</small><strong><?=number_format((int)($totalRows??count($rows)),0,',','.')?></strong><p><?=e((string)($period['label']??''))?></p></article>
     <article class="tds-kpi green"><span class="tds-kpi-icon"><i class="fa-solid fa-sack-dollar"></i></span><small>Total válido</small><strong><?=money($total)?></strong><p>Desconsiderando canceladas</p></article>
     <article class="tds-kpi slate"><span class="tds-kpi-icon"><i class="fa-solid fa-circle-check"></i></span><small>Válidas</small><strong><?=(int)$valid?></strong><p>Ativas ou faturadas</p></article>
     <article class="tds-kpi red"><span class="tds-kpi-icon"><i class="fa-solid fa-ban"></i></span><small>Canceladas</small><strong><?=(int)$cancelled?></strong><p>Fora do resultado</p></article>
     <article class="tds-kpi yellow"><span class="tds-kpi-icon"><i class="fa-solid fa-user-slash"></i></span><small>Sem vendedor</small><strong><?=(int)$withoutSeller?></strong><p>Não entram no resultado individual</p></article>
    </div>

    <section class="tds-shell">
     <div class="tds-shell-head"><div class="tds-shell-title"><span><i class="fa-solid fa-table-list"></i></span><div><strong>Base sincronizada</strong><small>Localize OS, cliente, vendedor ou status.</small></div></div><?php if($withoutSeller>0):?><div class="tds-warning"><i class="fa-solid fa-triangle-exclamation"></i><?=$withoutSeller?> OS sem vendedor vinculado</div><?php endif;?></div>
     <?php $serviceDataParams=[];if(!empty($period['all']))$serviceDataParams['period']='all';else{$serviceDataParams['date_from']=$period['from'];$serviceDataParams['date_to']=$period['to'];}?>
     <div class="table-card tds-table-wrap">
      <table class="table services-datatable tds-table" data-server-url="<?=APP_URL?>/api/services/datatable?<?=e(http_build_query($serviceDataParams))?>" data-page-length="10" data-length-change="1" data-order-column="3" data-order-direction="desc">
       <thead><tr><th>OS</th><th>Cliente</th><th>Vendedor</th><th>Data</th><th>Status</th><th class="text-end">Valor</th></tr></thead>
       <tbody></tbody>
      </table>
     </div>
    </section>
   </section>
  <?php break;
  case 'order_new':$error=$_SESSION['error']??null;$success=$_SESSION['success']??null;$old=$_SESSION['old']??[];unset($_SESSION['error'],$_SESSION['preview'],$_SESSION['success'],$_SESSION['old']);$d=$ready['defaults'];$draftId=(int)($draft['id']??$old['draft_id']??0);$editOrderId=(int)($old['edit_order_id']??$editOrder['source']['id']??0);$selectedFreightMode=trim((string)($old['freight_mode']??''));if(!in_array($selectedFreightMode,['0','1','2','3','4','9'],true))$selectedFreightMode=(string)($d['freight_mode']??'9');?>
   <div class="order-create-experience">
   <div class="order-new-topbar-tools" data-topbar-tools>
    <a class="order-new-topbar-back" href="<?=APP_URL?>/orders<?=$editOrderId>0?'/'.$editOrderId:''?>"><i class="fa-solid fa-arrow-left"></i><span><?=$editOrderId>0?'Voltar ao orçamento':'Voltar aos pedidos'?></span></a>
    <?php if($editOrderId>0):?><span class="order-new-draft-badge order-new-omie-edit-badge"><i class="fa-solid fa-cloud"></i>Editando orçamento Omie <?=e((string)($old['editing_order_label']??''))?></span><?php elseif($draftId>0):?><span class="order-new-draft-badge"><i class="fa-regular fa-pen-to-square"></i>Editando rascunho</span><?php endif;?>
    <div class="order-new-topbar-actions">
     <?php if($editOrderId<=0):?><button class="btn btn-light order-save-draft" type="submit" form="orderForm" name="submit_mode" value="draft" title="<?=$draftId>0?'Salvar alterações':'Salvar rascunho'?>"><i class="fa-regular fa-floppy-disk"></i><span><?=$draftId>0?'Salvar alterações':'Salvar rascunho'?></span></button><?php endif;?>
     <button class="btn btn-light order-pdf-button" type="submit" form="orderForm" name="submit_mode" value="pdf" formtarget="_blank" title="Gerar proposta em PDF sem finalizar o pedido"><i class="fa-regular fa-file-pdf"></i><span>Gerar PDF</span></button>
     <button class="btn btn-primary" type="submit" form="orderForm" name="submit_mode" value="send" title="<?=$editOrderId>0?'Atualizar na Omie':'Enviar para Omie'?>" data-confirm="<?=$editOrderId>0?'Atualizar este orçamento existente na Omie com os dados revisados?':'Enviar e integrar este pedido na Omie? Depois da confirmação ele deixará de ser rascunho.'?>" data-submit-loading="<?=$editOrderId>0?'Atualizando na Omie...':'Integrando na Omie...'?>" <?=$ready['ok']?'':'disabled'?>> <i class="fa-solid fa-cloud-arrow-up"></i><span><?=$editOrderId>0?'Atualizar na Omie':'Enviar para Omie'?></span></button>
    </div>
   </div>

   <header class="order-new-page-head">
    <div class="order-new-page-head-main">
     <span class="order-new-page-icon"><i class="fa-solid fa-cart-plus"></i></span>
     <div><span class="order-new-page-kicker">COMERCIAL / PEDIDOS</span><h1><?=$editOrderId>0?'Editar proposta':'Novo pedido'?></h1><p><?=$editOrderId>0?'Revise os dados abaixo e atualize a proposta existente na Omie.':'Monte o pedido completo, revise itens, condições, frete e financeiro antes de enviar para a Omie.'?></p></div>
    </div>
    <?php if($draftId>0):?><span class="order-new-page-status"><i class="fa-regular fa-floppy-disk"></i>Rascunho #<?=$draftId?></span><?php elseif($editOrderId>0):?><span class="order-new-page-status omie"><i class="fa-solid fa-cloud"></i>Orçamento Omie</span><?php else:?><span class="order-new-page-status"><i class="fa-solid fa-circle-check"></i>Novo cadastro</span><?php endif;?>
   </header>

  <?php if($success):?><div class="alert alert-success"><?=e($success)?></div><?php endif;?>
   <?php if($editOrderId>0):?><div class="alert alert-info"><i class="fa-solid fa-circle-info"></i><span>Você está alterando um orçamento já existente. Nada será modificado até clicar em <strong>Atualizar na Omie</strong>; depois da confirmação, o CRM também será atualizado.</span></div><?php elseif(!empty($old['duplicated_from'])):?><div class="alert alert-info"><i class="fa-regular fa-copy"></i><span>Este novo pedido foi preenchido a partir do pedido <strong><?=e((string)$old['duplicated_from'])?></strong>. Revise a etapa, a previsão e os valores antes de enviar.</span></div><?php endif;?>
   <?php if(!$ready['ok']):$missingLabels=['stage'=>'Etapas','category'=>'Categorias','account'=>'Contas correntes','payment_term'=>'Condições de pagamento','products'=>'Produtos com preço','payment_terms'=>'Condições de pagamento'];$missingText=array_map(fn($k)=>$missingLabels[$k]??$k,$ready['missing']);?><div class="alert alert-warning"><strong>Faltam dados para criar pedidos:</strong> <?=e(implode(', ',$missingText))?>. <a href="<?=APP_URL?>/sync">Abra a Central de Sincronização</a> e sincronize somente os módulos indicados.</div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>

   <nav class="order-flow-steps" aria-label="Etapas do pedido"><span class="active"><b>1</b><i class="fa-solid fa-boxes-stacked"></i>Itens</span><span><b>2</b><i class="fa-solid fa-building"></i>Cliente</span><span><b>3</b><i class="fa-solid fa-truck-fast"></i>Frete</span><span><b>4</b><i class="fa-regular fa-credit-card"></i>Financeiro</span><span><b>5</b><i class="fa-solid fa-check-double"></i>Revisão</span></nav>
   <form method="post" action="<?=APP_URL?>/orders" id="orderForm" class="omie-order-form">
    <input type="hidden" name="_token" value="<?=CSRF::token()?>">
    <input type="hidden" name="draft_id" value="<?=$draftId?>">
    <input type="hidden" name="edit_order_id" value="<?=$editOrderId?>">
    <input type="hidden" name="request_token" value="<?=e((string)($old['request_token']??(date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,8)))))?>">
    <input type="hidden" name="client_id" id="clientId" value="<?=e((string)($old['client_id']??$prefill))?>">
    <input type="hidden" name="items_json" id="itemsJson" value="<?=e((string)($old['items_json']??'[]'))?>">
    <input type="hidden" name="departments_json" id="departmentsJson" value="<?=e((string)($old['departments_json']??''))?>">
    <input type="hidden" name="installments_json" id="installmentsJson" value="<?=e((string)($old['installments_json']??'[]'))?>">
    <input type="hidden" name="custom_installments" id="customInstallments" value="<?=($old['custom_installments']??'N')==='S'?'S':'N'?>">

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
       <input class="form-control" type="date" name="forecast_date" id="orderForecastDate" min="<?=date('Y-m-d')?>" value="<?=e((string)($old['forecast_date']??date('Y-m-d')))?>">
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
       <select class="form-select" name="payment_term" id="orderPaymentTerm" required><option value="">Selecione</option><?php foreach($terms as $t):?><option value="<?=e($t['code'])?>" data-installments="<?=(int)($t['installments']??0)?>" data-days="<?=e((string)($t['days_list']??''))?>" <?=($old['payment_term']??$d['payment_term']??'')===$t['code']?'selected':''?>><?=e($t['description'])?><?=$t['days_list']?' • '.e($t['days_list']):''?></option><?php endforeach;?></select>
      </div>
      <div>
       <label>Cenário fiscal</label>
       <select class="form-select" name="tax_scenario"><option value="">Padrão Omie</option><?php foreach($taxes as $r):?><option value="<?=e($r['omie_code'])?>" <?=($old['tax_scenario']??$d['tax_scenario']??'')===$r['omie_code']?'selected':''?>><?=e($r['name'])?></option><?php endforeach;?></select>
      </div>
     </div>
    </section>

    <section class="panel omie-order-body" data-order-tabs>
     <div class="omie-main-tabs" role="tablist" aria-label="Seções do pedido">
      <button type="button" class="active" data-order-tab="items"><i class="fa-solid fa-boxes-stacked"></i>Itens da venda</button>
      <button type="button" data-order-tab="departments"><i class="fa-solid fa-sitemap"></i>Departamentos</button>
      <button type="button" data-order-tab="freight"><i class="fa-solid fa-truck-fast"></i>Frete</button>
      <button type="button" data-order-tab="additional"><i class="fa-solid fa-sliders"></i>Dados adicionais</button>
      <button type="button" data-order-tab="installments"><i class="fa-regular fa-credit-card"></i>Parcelas</button>
      <button type="button" data-order-tab="notes"><i class="fa-regular fa-note-sticky"></i>Observações</button>
      <button type="button" data-order-tab="email"><i class="fa-regular fa-envelope"></i>E-mail</button>
     </div>

     <div class="omie-tab-content">
      <section class="order-tab-panel active" data-order-panel="items">
       <select hidden id="orderProfile"><?php foreach($profiles as $p):?><option value="<?=e($p['code'])?>" data-no-stock="<?=e($p['default_no_stock'])?>" data-no-finance="<?=e($p['default_no_finance'])?>" data-no-total="<?=e($p['default_no_total'])?>" data-reserve="<?=e($p['default_reserve_stock'])?>"><?=e($p['name'])?></option><?php endforeach;?></select>
       <div class="order-items-bulk" id="orderItemsBulk" hidden>
        <label class="order-items-select-all"><input type="checkbox" id="selectAllOrderItems"><span><strong id="selectedOrderItemsCount">0 selecionados</strong><small>Marque os itens que deseja alterar juntos</small></span></label>
        <div class="order-bulk-group"><span>Estoque</span><button type="button" data-bulk-field="no_stock" data-bulk-value="0"><i class="fa-solid fa-box"></i>Movimentar</button><button type="button" data-bulk-field="no_stock" data-bulk-value="1"><i class="fa-solid fa-box-open"></i>Não movimentar</button></div>
        <div class="order-bulk-group"><span>Financeiro</span><button type="button" data-bulk-field="no_finance" data-bulk-value="0"><i class="fa-solid fa-sack-dollar"></i>Gerar</button><button type="button" data-bulk-field="no_finance" data-bulk-value="1"><i class="fa-solid fa-ban"></i>Não gerar</button></div>
       </div>
       <div id="orderItems"></div>
      </section>

      <section class="order-tab-panel" data-order-panel="departments">
       <div class="departments-panel-head">
        <div><span class="eyebrow">RATEIO DO PEDIDO</span><h3>Departamentos</h3><p>Distribua o pedido entre os departamentos sincronizados da Omie. O total precisa fechar em 100%.</p></div>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="addDepartment"><i class="fa-solid fa-plus"></i>Adicionar departamento</button>
       </div>
       <?php if(!$departments):?>
        <div class="alert alert-warning mb-0"><strong>Nenhum departamento sincronizado.</strong> Vá em Sincronização → Departamentos antes de enviar pedidos.</div>
       <?php else:?>
        <div class="department-distribution" id="departmentDistribution"></div>
        <div class="department-total-line">
         <span>Total do rateio</span>
         <strong id="departmentTotal">0,00%</strong>
        </div>
       <?php endif;?>
      </section>

      <section class="order-tab-panel" data-order-panel="freight">
       <div class="omie-tab-grid freight-grid">
        <div class="freight-field-carrier"><label>Transportadora</label><select class="form-select" name="carrier_code" id="orderCarrier"><option value="">Sem transportadora</option><?php foreach($carriers??[] as $carrier):?><option value="<?=e((string)$carrier['omie_code'])?>" <?=((string)($old['carrier_code']??''))===(string)$carrier['omie_code']?'selected':''?>><?=e((string)$carrier['name'])?><?=!empty($carrier['city'])?' • '.e((string)$carrier['city']).(!empty($carrier['uf'])?' / '.e((string)$carrier['uf']):''):''?></option><?php endforeach;?></select><?php if(empty($carriers)):?><small class="field-hint">Nenhuma transportadora habilitada. Configure em Configurações.</small><?php endif;?></div>
        <div class="freight-field-mode"><label>Tipo do frete</label><select class="form-select" name="freight_mode" id="orderFreightMode"><?php foreach(['9'=>'Sem frete','0'=>'CIF • remetente','1'=>'FOB • destinatário','2'=>'Terceiros','3'=>'Próprio • remetente','4'=>'Próprio • destinatário'] as $k=>$v):?><option value="<?=$k?>" <?=$selectedFreightMode===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select></div>
        <div class="freight-field-volumes"><label>Quantidade de volumes</label><input class="form-control" type="number" min="1" step="1" name="volumes" id="orderVolumes" value="<?=e((string)($old['volumes']??'1'))?>"></div>
        <div class="freight-field-weight"><label>Peso líquido (kg) <small>somado dos itens</small></label><input class="form-control" id="orderNetWeight" name="net_weight" inputmode="decimal" placeholder="0,000" data-manual="<?=isset($old['net_weight'])&&$old['net_weight']!==''?'1':'0'?>" value="<?=e((string)($old['net_weight']??''))?>"></div>
        <div class="freight-field-weight"><label>Peso bruto (kg) <small>somado dos itens</small></label><input class="form-control" id="orderGrossWeight" name="gross_weight" inputmode="decimal" placeholder="0,000" data-manual="<?=isset($old['gross_weight'])&&$old['gross_weight']!==''?'1':'0'?>" value="<?=e((string)($old['gross_weight']??''))?>"></div>
        <div class="freight-field-value"><label>Valor do frete</label><div class="freight-value-control"><input class="form-control" id="orderFreightValue" name="freight_value" inputmode="decimal" placeholder="0,00" value="<?=e((string)($old['freight_value']??''))?>"><button type="button" id="openFreightQuote" title="Calcular frete" aria-label="Calcular frete"><i class="fa-solid fa-cart-shopping"></i></button></div><small class="field-hint" id="freightSelectedSummary">Use o carrinho para comparar as transportadoras.</small></div>
       </div>
       <dialog class="freight-quote-modal" id="freightQuoteModal"><div class="freight-quote-shell"><header><span><i class="fa-solid fa-truck-fast"></i></span><div><small>COTAÇÃO MULTITRANSPORTADORAS</small><strong>Compare preço e prazo</strong><p>Escolha uma opção para preencher transportadora, frete e observações do pedido.</p></div><em><i class="fa-solid fa-shield-halved"></i> Consulta segura</em><button type="button" id="closeFreightQuote" aria-label="Fechar"><i class="fa-solid fa-xmark"></i></button></header><div class="freight-quote-context" id="freightQuoteContext"></div><div class="freight-quote-providers" id="freightQuoteProviders"></div><div class="freight-quote-results" id="freightQuoteResults"><div class="freight-quote-empty"><i class="fa-solid fa-cart-flatbed"></i><span>As cotações aparecerão aqui.</span></div></div><footer><i class="fa-solid fa-circle-info"></i><span>Os valores dependem da disponibilidade do serviço para o CEP, peso e dimensões informados.</span></footer></div></dialog>
      </section>

      <section class="order-tab-panel" data-order-panel="additional">
       <div class="omie-tab-grid additional-grid">
        <div><label>Categoria</label><select class="form-select" name="category" required><?php foreach($categories as $r):?><option value="<?=e($r['code'])?>" <?=($old['category']??$d['category']??'')===$r['code']?'selected':''?>><?=e($r['description'])?></option><?php endforeach;?></select></div>
        <div><label>Conta corrente</label><select class="form-select" name="account" required><?php foreach($accounts as $r):?><option value="<?=e($r['omie_code'])?>" <?=($old['account']??$d['account']??'')===$r['omie_code']?'selected':''?>><?=e($r['name'])?></option><?php endforeach;?></select></div>
        <div><label>Etapa</label><select class="form-select" name="stage" required><?php foreach($stages as $r):?><option value="<?=e($r['code'])?>" <?=($old['stage']??$d['stage']??'')===$r['code']?'selected':''?>><?=e($r['code'].' • '.$r['name'])?></option><?php endforeach;?></select></div>
       </div>
       <input type="hidden" name="consumer_final" value="N">
       <label class="check mt-3"><input type="checkbox" name="consumer_final" value="S" <?=($old['consumer_final']??$d['consumer_final']??'S')==='S'?'checked':''?>> Nota Fiscal para Consumidor Final</label>
      </section>

      <section class="order-tab-panel" data-order-panel="installments">
       <input type="hidden" name="payment_method" id="installmentPaymentMethod" value="<?=e((string)($old['payment_method']??$d['payment_method']??''))?>">
       <div class="omie-installments-head">
        <div><strong>Contas a receber</strong><span>Parcelas e vencimentos previstos para esta venda.</span></div>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="rebuildInstallments"><i class="fa-solid fa-rotate"></i>Refazer parcelas</button>
       </div>
       <div class="omie-installments-wrap">
        <table class="omie-installments-table">
         <thead><tr><th>Situação</th><th>Parcela</th><th>Vencimento</th><th class="numeric">Valor a receber</th><th class="numeric">Percentual</th><th>Tipo de documento</th><th>Meio de pagamento</th><th class="center">Gerar boleto</th></tr></thead>
         <tbody id="installmentRows"><tr><td colspan="8" class="installments-empty">Selecione a condição de pagamento e inclua os itens do pedido.</td></tr></tbody>
         <tfoot><tr><td colspan="3"><strong>Total das parcelas</strong><span id="installmentMode">Automático pela condição</span></td><td class="numeric"><strong id="installmentTotal">R$ 0,00</strong></td><td class="numeric"><strong id="installmentPercent">0,00%</strong></td><td colspan="3"><span id="installmentBalance"></span></td></tr></tfoot>
        </table>
       </div>
       <p class="installments-note"><i class="fa-solid fa-circle-info"></i>Altere valor, vencimento, meio de pagamento ou boleto diretamente na grade. Parcelas personalizadas serão enviadas à Omie pela condição 999.</p>
      </section>

      <section class="order-tab-panel" data-order-panel="notes">
       <label>Observação interna da venda</label><textarea class="form-control" name="notes" id="orderNotes" rows="7"><?=e((string)($old['notes']??''))?></textarea>
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

   </form>
   </div>
   <script>
   window.ORDER_PREFILL_CLIENT=<?=json_encode((int)($old['client_id']??$prefill))?>;
   window.ORDER_OLD_ITEMS=<?=json_encode(json_decode((string)($old['items_json']??'[]'),true)?:[])?>;
   window.ORDER_DEPARTMENTS=<?=json_encode(array_values(array_map(fn($r)=>['code'=>(string)$r['code'],'description'=>(string)$r['description']],$departments??[])),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
window.ORDER_META=<?=json_encode(['categories'=>$categories,'taxes'=>$taxes,'stocks'=>$stocks,'profiles'=>$profiles,'paymentMethods'=>$methods,'documentTypes'=>$documents,'defaults'=>$d],JSON_UNESCAPED_UNICODE)?>;
   </script>
  <?php break;

  case 'collection':
   $collectionStats=is_array($collectionStats??null)?$collectionStats:[];
   $collectionTotal=(int)($collectionStats['total']??0);$collectionAmount=(float)($collectionStats['available_amount']??0);
   $collectionOverdue=(int)($collectionStats['overdue_count']??0);$collectionCritical=(int)($collectionStats['critical_count']??0);$collectionCurrent=(int)($collectionStats['current_count']??0);
   $topAttention=$collectionTopAttention??[];
   $collectionTagsSelected=$collectionTagsSelected??[];$collectionTagFilterLabel=!$collectionTagsSelected?'Todas as tags':(count($collectionTagsSelected)===1?$collectionTagsSelected[0]:count($collectionTagsSelected).' tags selecionadas');
   $collectionDataParams=['view'=>$view,'delay'=>$collectionDelay??'all'];
   if((int)($collectionAssigned??0)>0)$collectionDataParams['assigned_user_id']=(int)$collectionAssigned;
   if(!empty($collectionUf))$collectionDataParams['uf']=$collectionUf;
   if($collectionTagsSelected)$collectionDataParams['tags']=$collectionTagsSelected;
   ?>
   <section class="tdcob4-page">
    <header class="tdcob4-head"><div><span class="tdcob4-kicker">FINANCEIRO / COBRANÇA</span><h1>Carteira de cobrança</h1><p>Acompanhe todos os clientes devedores, organize ações e aumente a recuperação.</p></div><div class="tdcob4-head-actions"><a class="tdcob4-secondary" href="<?=APP_URL?>/collection/report"><i class="fa-solid fa-chart-column"></i>Relatório de cobranças</a><?php if(Auth::can('admin','supervisor')):?><a class="tdcob4-primary" href="<?=APP_URL?>/collection/recoveries"><i class="fa-solid fa-money-bill-transfer"></i>Lançar recuperação</a><?php endif;?></div></header>
    <?php if(!empty($flash)):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>

    <section class="tdcob4-filters"><form method="get">
     <input type="hidden" name="view" value="<?=e($view)?>">
     <?php if($u['role']!=='collector'):?><label><span>Responsável pela cobrança</span><select class="form-select" name="assigned_user_id"><option value="0">Todos os responsáveis</option><?php foreach($collectionCollectors??[] as $collector):?><option value="<?=(int)$collector['id']?>" <?=((int)($collectionAssigned??0)===(int)$collector['id'])?'selected':''?>><?=e($collector['name'])?></option><?php endforeach;?></select></label><?php endif;?>
     <label><span>Faixa de atraso</span><select class="form-select" name="delay"><?php foreach(['all'=>'Todos','current'=>'Em dia','1_30'=>'1 a 30 dias','31_60'=>'31 a 60 dias','60_plus'=>'Acima de 60 dias'] as $key=>$label):?><option value="<?=$key?>" <?=($collectionDelay??'all')===$key?'selected':''?>><?=$label?></option><?php endforeach;?></select></label>
     <label><span>UF</span><select class="form-select" name="uf"><option value="">Todas</option><?php foreach($collectionUfs??[] as $ufRow):?><option value="<?=e($ufRow['uf'])?>" <?=($collectionUf??'')===$ufRow['uf']?'selected':''?>><?=e($ufRow['uf'])?></option><?php endforeach;?></select></label>
     <details class="tdcob4-tag-filter" data-collection-tag-filter>
      <summary><span><i class="fa-solid fa-tags"></i> Tags</span><strong><?=e($collectionTagFilterLabel)?></strong><i class="fa-solid fa-chevron-down"></i></summary>
      <div class="tdcob4-tag-panel"><div><?php foreach($collectionTags??[] as $clientTag):$tagName=(string)$clientTag['tag'];?><label><input type="checkbox" name="tags[]" value="<?=e($tagName)?>" <?=in_array($tagName,$collectionTagsSelected,true)?'checked':''?>><span><?=e($tagName)?></span></label><?php endforeach;?></div><footer><button type="button" class="tdcob4-clear" data-collection-tags-clear>Limpar tags</button></footer></div>
     </details>
     <a class="tdcob4-clear" href="<?=APP_URL?>/collection?view=<?=e($view)?>">Limpar filtros</a>
     <button class="tdcob4-primary" type="submit"><i class="fa-solid fa-filter"></i>Aplicar filtros</button>
    </form></section>

    <div class="tdcob4-kpis">
     <article><span class="green"><i class="fa-solid fa-dollar-sign"></i></span><div><small>Saldo disponível</small><strong><?=money($collectionAmount)?></strong><em>Omie menos baixas locais pendentes</em></div></article>
     <article><span class="red"><i class="fa-regular fa-clock"></i></span><div><small>Títulos em atraso</small><strong><?=number_format($collectionOverdue,0,',','.')?></strong><em><?=number_format($collectionCritical,0,',','.')?> acima de 60 dias</em></div></article>
     <article><span class="blue"><i class="fa-solid fa-chart-column"></i></span><div><small>Recuperado no mês</small><strong><?=money($collectionRecovered??0)?></strong><em>Pagamentos registrados</em></div></article>
     <article><span class="yellow"><i class="fa-regular fa-calendar"></i></span><div><small>Promessas de pagamento</small><strong><?=number_format((int)($collectionPromises??0),0,',','.')?></strong><em>Promessas futuras</em></div></article>
    </div>

    <section class="tdcob4-panel">
     <header><div><span><i class="fa-solid fa-table-list"></i></span><div><strong><?=$view==='settled'?'Clientes quitados':'Títulos em cobrança'?></strong><small>Saldo disponível considera as baixas lançadas localmente até a conciliação com a Omie.</small></div></div><nav><a class="<?=$view==='open'?'active':''?>" href="<?=APP_URL?>/collection?view=open">Pendentes</a><a class="<?=$view==='settled'?'active':''?>" href="<?=APP_URL?>/collection?view=settled">Quitados</a></nav></header>
     <div class="table-card tdcob4-table-wrap"><table class="table tdcob4-table" data-server-url="<?=e(APP_URL.'/api/collection/datatable?'.http_build_query($collectionDataParams))?>" data-page-length="10" data-length-change="1" data-order-column="4" data-order-direction="desc"><thead><tr><th>Cliente / CFC</th><th>Vendedor</th><th>Resp. cobrança</th><th>Dias em atraso</th><th>Saldo disponível</th><th>Último contato</th><th>Próxima ação</th><th>Status</th><th data-dt-order="disable">Ações</th></tr></thead><tbody></tbody></table></div>
    </section>

    <div class="tdcob4-bottom">
     <section class="tdcob4-radar"><header><span><i class="fa-solid fa-bullseye"></i></span><div><strong>Radar da cobrança</strong><small>Distribuição da carteira por nível de atenção.</small></div></header><div class="tdcob4-radar-body"><div class="tdcob4-ring" style="--p:<?=$collectionTotal?round($collectionOverdue/$collectionTotal*100):0?>"><strong><?=$collectionTotal?round($collectionOverdue/$collectionTotal*100):0?>%</strong><small>em atraso</small></div><div class="tdcob4-legend"><span><i class="danger"></i>Crítico (>60 dias)<b><?=$collectionCritical?></b></span><span><i class="warning"></i>Atenção (1–60 dias)<b><?=max(0,$collectionOverdue-$collectionCritical)?></b></span><span><i class="ok"></i>Em dia<b><?=$collectionCurrent?></b></span></div></div></section>
     <section class="tdcob4-attention"><header><span><i class="fa-solid fa-triangle-exclamation"></i></span><div><strong>Clientes que exigem atenção</strong><small>Maiores atrasos da carteira atual.</small></div></header><div><?php foreach(array_slice($topAttention,0,5) as $idx=>$row):?><a href="<?=APP_URL?>/collection/<?=$row['client_id']?>"><span><?=($idx+1)?></span><strong><?=e($row['name'])?></strong><b><?=money($row['open_amount'])?></b><em><?=(int)$row['max_overdue_days']?> dias</em></a><?php endforeach;?><?php if(!$topAttention):?><div class="tdcob4-empty">Nenhum cliente exige atenção neste filtro.</div><?php endif;?></div></section>
    </div>
   </section>
  <?php break;

  case 'collection_report':
   $reportSummary=$collectionReportSummary??[];$reportMonth=(string)($collectionReportMonth??date('Y-m'));$reportStatus=(string)($collectionReportStatus??'all');$reportChannels=['phone'=>'Ligação','whatsapp'=>'WhatsApp','email'=>'E-mail','manual'=>'Lançamento manual'];?>
   <section class="tdcob4-page tdcob4-report-page">
    <header class="tdcob4-head"><div><a class="tdcob4-kicker" href="<?=APP_URL?>/collection"><i class="fa-solid fa-arrow-left"></i> COBRANÇA / RELATÓRIO</a><h1>Relatório da carteira de cobrança</h1><p>Todos os devedores ficam disponíveis para a equipe, com o responsável indicado apenas para organização.</p></div></header>
    <section class="tdcob4-filters tdcob4-report-filters <?=Auth::can('admin','supervisor')?'':'single-owner'?>"><form method="get"><label><span>Mês analisado</span><input class="form-control" type="month" name="month" value="<?=e($reportMonth)?>"></label><?php if(Auth::can('admin','supervisor')):?><label><span>Responsável</span><select class="form-select" name="assigned_user_id"><option value="0">Toda a equipe</option><?php foreach($collectionCollectors??[] as $collector):?><option value="<?=(int)$collector['id']?>" <?=((int)($collectionReportAssigned??0)===(int)$collector['id'])?'selected':''?>><?=e($collector['name'])?></option><?php endforeach;?></select></label><?php endif;?><fieldset class="tdcob4-status-filter"><legend>Situação no mês</legend><div><?php foreach(['all'=>'Todos','paid'=>'Pagos','waiting'=>'Aguardando','uncontacted'=>'Não cobrados'] as $statusValue=>$statusLabel):$statusId='report-status-'.$statusValue;?><input id="<?=$statusId?>" type="radio" name="payment_status" value="<?=$statusValue?>" <?=$reportStatus===$statusValue?'checked':''?>><label for="<?=$statusId?>"><?=$statusLabel?></label><?php endforeach;?></div></fieldset><a class="tdcob4-clear" href="<?=APP_URL?>/collection/report">Limpar</a><button class="tdcob4-primary" type="submit"><i class="fa-solid fa-filter"></i>Aplicar</button></form></section>
    <div class="tdcob4-kpis tdcob4-report-kpis">
     <article><span class="blue"><i class="fa-solid fa-users"></i></span><div><small>Clientes na cobrança</small><strong><?=number_format((int)($reportSummary['portfolio']??0),0,',','.')?></strong><em><?=number_format((int)($reportSummary['clients']??0),0,',','.')?> cobrados no mês</em></div></article>
     <article><span class="yellow"><i class="fa-regular fa-clock"></i></span><div><small>Aguardando pagamento</small><strong><?=number_format((int)($reportSummary['waiting']??0),0,',','.')?></strong><em>Cobrados sem pagamento no mês</em></div></article>
     <article><span class="green"><i class="fa-solid fa-circle-check"></i></span><div><small>Clientes que pagaram</small><strong><?=number_format((int)($reportSummary['paid']??0),0,',','.')?></strong><em>Com pagamento registrado no mês</em></div></article>
     <article><span class="green"><i class="fa-solid fa-money-bill-trend-up"></i></span><div><small>Valor recuperado</small><strong><?=money((float)($reportSummary['recovered']??0))?></strong><em>Total pago no filtro selecionado</em></div></article>
     <article><span class="red"><i class="fa-solid fa-file-invoice-dollar"></i></span><div><small>Saldo disponível</small><strong><?=money((float)($reportSummary['open']??0))?></strong><em>Saldo Omie menos baixas locais pendentes</em></div></article>
    </div>
    <section class="tdcob4-panel tdcob4-report-panel">
     <header><div><span><i class="fa-solid fa-table-list"></i></span><div><strong>Detalhamento da carteira</strong><small>Data do pagamento é quando o valor foi recebido; lançamento no CRM é quando o registro foi criado.</small></div></div></header>
     <div class="table-card tdcob4-table-wrap"><table class="table tdcob4-table" data-page-length="5" data-length-change="1" data-order-column="2" data-order-direction="desc">
      <thead><tr><th>Cliente</th><th>Responsável</th><th>Última ação registrada</th><th>Pagamento no mês</th><th class="text-end">Saldo disponível</th><th>Situação</th><th data-dt-order="disable">Abrir</th></tr></thead>
      <tbody><?php foreach($collectionReportRows??[] as $row):$paid=(float)$row['recovered']>0;$contacted=(int)$row['action_count']>0;$hasRecord=!empty($row['last_action_at']);?>
       <tr>
        <td><div class="tdcob4-client"><span><?=e(mb_strtoupper(mb_substr((string)$row['name'],0,1)))?></span><div><strong><?=e($row['name'])?></strong><small><?=e($row['document']??'')?></small></div></div></td>
        <td><strong><?=e($row['assigned_name']??'Sem responsável')?></strong></td>
        <td class="tdcob4-report-event" data-order="<?=$hasRecord?strtotime((string)$row['last_action_at']):0?>"><?php if($hasRecord):?><strong><?=date('d/m/Y H:i',strtotime((string)$row['last_action_at']))?></strong><small><?=e($reportChannels[$row['last_channel']]??$row['last_channel'])?> · <?=e(task_result_label((string)$row['last_result']))?><?php if((int)$row['action_count']>0):?> · <?=number_format((int)$row['action_count'],0,',','.')?> contato(s) no mês<?php endif;?></small><?php else:?><span class="tdcob4-report-empty">Sem ação registrada no mês</span><?php endif;?></td>
        <td class="tdcob4-report-payment" data-order="<?=!empty($row['last_payment_at'])?strtotime((string)$row['last_payment_at']):0?>"><?php if($paid):?><strong><?=money((float)$row['recovered'])?></strong><small><b>Último pagamento:</b> <?=date('d/m/Y H:i',strtotime((string)$row['last_payment_at']))?></small><small><b>Lançado no CRM:</b> <?=date('d/m/Y H:i',strtotime((string)($row['last_payment_recorded_at']??$row['last_payment_at'])))?></small><?php else:?><span class="tdcob4-report-empty">Nenhum pagamento no mês</span><?php endif;?></td>
        <td class="text-end" data-order="<?=e((string)($row['available_amount']??0))?>"><strong><?=money((float)($row['available_amount']??0))?></strong><?php if((float)($row['pending_local']??0)>0):?><small>Omie <?=money((float)$row['open_amount'])?> · baixa local <?=money((float)$row['pending_local'])?></small><?php endif;?></td>
        <td><span class="tdcob4-status <?=$paid?'ok':($contacted?'warning':'neutral')?>"><i></i><?=$paid?'Pagamento registrado':($contacted?'Aguardando pagamento':'Ainda não cobrado')?></span></td>
        <td><a class="tdcob4-open" href="<?=APP_URL?>/collection/<?=(int)$row['client_id']?>" title="Abrir cobrança"><i class="fa-regular fa-folder-open"></i></a></td>
       </tr>
      <?php endforeach;?></tbody>
     </table></div>
    </section>
   </section>
  <?php break;

  case 'collection_recoveries':
   $recoveryDate=(string)($old['recovery_date']??$defaults['recovery_date']??date('Y-m-d'));$assignedDefault=(int)($old['assigned_user_id']??$defaults['assigned_user_id']??0);$oldClientId=(int)($old['client_id']??0);
   $recoveryDataParams=[];if(!empty($period['all']))$recoveryDataParams['period']='all';else{$recoveryDataParams['date_from']=$period['from'];$recoveryDataParams['date_to']=$period['to'];}
   ?>
   <section class="tdcob-page tdcob4-recovery-page">
    <header class="tdcob-head">
     <div class="tdcob-head-main"><span class="tdcob-head-icon"><i class="fa-solid fa-money-bill-transfer"></i></span><div><a class="tdcob-kicker" href="<?=APP_URL?>/collection"><i class="fa-solid fa-arrow-left"></i> COBRANÇA / RECUPERAÇÕES</a><h1>Lançar valor recuperado</h1><p>Registre o recebimento na data real para refletir corretamente na meta do responsável.</p></div></div>
     <div class="tdcob-recovery-total"><small>Total do período</small><strong><?=money($total)?></strong><span><?=e($period['label'])?></span></div>
    </header>
    <?php if($flash):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>

    <div class="tdcob-recovery-grid">
     <section class="tdcob-card">
      <div class="tdcob-card-head"><span><i class="fa-solid fa-plus"></i></span><div><strong>Nova recuperação</strong><small>Crédito manual na meta de cobrança.</small></div></div>
      <form method="post" action="<?=APP_URL?>/collection/recoveries" class="tdcob-recovery-form" data-collection-recovery-form>
       <input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="client_id" value="<?=$oldClientId?>" data-recovery-client-id>
       <label>Cliente<div class="search-box"><input class="form-control" autocomplete="off" placeholder="Código, nome ou CPF/CNPJ" data-recovery-client-search><div class="search-results" data-recovery-client-results></div></div><div class="selected-box" data-recovery-client-selected></div></label>
       <label>Valor recuperado<input class="form-control" name="amount" inputmode="decimal" placeholder="0,00" value="<?=e((string)($old['amount']??''))?>" required></label>
       <label>Data da recuperação<input class="form-control" type="date" name="recovery_date" max="<?=date('Y-m-d')?>" value="<?=e($recoveryDate)?>" required></label>
       <label>Responsável pela meta<select class="form-select" name="assigned_user_id" required><option value="">Selecione</option><?php foreach($collectors as $collector):?><option value="<?=(int)$collector['id']?>" <?=$assignedDefault===(int)$collector['id']?'selected':''?>><?=e($collector['name'])?></option><?php endforeach;?></select></label>
       <label>Observação<input class="form-control" name="notes" maxlength="500" placeholder="Opcional" value="<?=e((string)($old['notes']??''))?>"></label>
       <button class="tdcob-btn tdcob-btn-primary w-100" type="submit" data-submit-loading="Lançando recuperação..." data-confirm="Confirmar este valor na data informada?"><i class="fa-solid fa-check"></i>Lançar na meta</button>
      </form>
     </section>

     <section class="tdcob-shell">
      <div class="tdcob-shell-head"><div class="tdcob-title"><span><i class="fa-solid fa-clock-rotate-left"></i></span><div><strong>Histórico de recuperações</strong><small>A data abaixo é a data considerada nos resultados.</small></div></div></div>
      <div class="table-card tdcob-table-wrap"><table class="table tdcob-table collection-recoveries-datatable" data-server-url="<?=e(APP_URL.'/api/collection/recoveries/datatable?'.http_build_query($recoveryDataParams))?>" data-page-length="10" data-length-change="1" data-order-column="3" data-order-direction="desc"><thead><tr><th>Código</th><th>Cliente</th><th class="text-end">Valor</th><th>Data do pagamento</th><th>Data lançada</th><th>Meta de</th><th>Incluído por</th><th data-dt-order="disable">Ações</th></tr></thead><tbody></tbody></table></div>
     </section>
    </div>
   </section>
   <script>window.COLLECTION_RECOVERY_OLD_CLIENT=<?=$oldClientId?>;</script>
  <?php break;

  case 'collection_case':?>
   <section class="tdcob-page tdcob4-case-page">
    <header class="tdcob-head">
     <div class="tdcob-head-main"><span class="tdcob-head-icon"><i class="fa-solid fa-file-invoice-dollar"></i></span><div><a class="tdcob-kicker" href="<?=APP_URL?>/collection"><i class="fa-solid fa-arrow-left"></i> COBRANÇA / CLIENTE</a><h1><?=e($case['name'])?></h1><p><?=money($case['available_amount'])?> disponíveis · <?=$case['max_overdue_days']?> dias de atraso</p></div></div>
    </header>

    <?php if(!empty($flash)):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>

    <div class="tdcob-case-summary">
     <div class="available"><span>Saldo disponível</span><strong><?=money($case['available_amount'])?></strong></div>
     <div><span>Saldo informado pela Omie</span><strong><?=money($case['open_amount'])?></strong></div>
     <div class="local"><span>Baixa local pendente</span><strong><?=money($case['pending_local'])?></strong></div>
     <div class="agreement"><span>Último acordo</span><strong><?=($case['agreement_amount']??0)>0?money($case['agreement_amount']):'Nenhum'?></strong><?php if(!empty($case['agreement_date'])):?><small><?=date('d/m/Y H:i',strtotime($case['agreement_date']))?></small><?php endif;?></div>
    </div>

    <?php if($collectors):?><div class="tdcob-assign"><div><span class="tdcob-kicker">RESPONSABILIDADE</span><strong><?=e($case['assigned_name']??'Não atribuído')?></strong><small>Transferir move histórico, meta e retornos pendentes.</small></div><form method="post" action="<?=APP_URL?>/collection/<?=$case['client_id']?>/assign"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><select class="form-select" name="assigned_user_id" required><option value="">Novo responsável...</option><?php foreach($collectors as $c):?><option value="<?=$c['id']?>"><?=e($c['name'])?></option><?php endforeach;?></select><button class="tdcob-btn">Transferir tudo</button></form></div><?php endif;?>

    <section class="tdcob-card tdcob-action-card">
     <div class="tdcob-card-head"><span><i class="fa-solid fa-headset"></i></span><div><strong>Nova movimentação</strong><small>Registre o contato, acordo, pagamento ou próximo retorno sem sair da tela.</small></div></div>
     <form method="post" action="<?=APP_URL?>/collection/<?=$case['client_id']?>/action" class="tdcob-form tdcob-action-form">
      <input type="hidden" name="_token" value="<?=CSRF::token()?>">
      <?php if($collectors):?><label class="owner">Responsável<select class="form-select" name="assigned_user_id"><option value="">Manter atual</option><?php foreach($collectors as $c):?><option value="<?=$c['id']?>" <?=(int)$case['assigned_user_id']===(int)$c['id']?'selected':''?>><?=e($c['name'])?></option><?php endforeach;?></select></label><?php endif;?>
      <label>Canal<select class="form-select" name="channel"><option value="phone">Ligação</option><option value="whatsapp">WhatsApp</option><option value="email">E-mail</option></select></label>
      <label>Resultado<select class="form-select" name="result"><?php foreach($taskResults??[] as $resultOption):?><option value="<?=e($resultOption['code'])?>"><?=e($resultOption['label'])?></option><?php endforeach;?></select></label>
      <label>Valor do acordo ou pagamento<input class="form-control" name="amount" inputmode="decimal" placeholder="0,00"></label>
      <label>Próximo retorno<input class="form-control" type="datetime-local" name="promise_at"></label>
      <label class="notes">Anotação<textarea class="form-control" name="notes" rows="2" placeholder="Resumo do contato ou condição negociada"></textarea></label>
      <div class="tdcob-action-help"><i class="fa-solid fa-circle-info"></i><span><b>Acordo</b> apenas sinaliza. <b>Pagamento</b> reduz o saldo disponível.</span></div>
      <button class="tdcob-btn tdcob-btn-primary tdcob-action-save"><i class="fa-solid fa-check"></i>Salvar movimentação</button>
     </form>
    </section>

    <?php $resultLabels=$taskResultLabels??[];$eligibleCount=0;foreach($actions as $historyAction){if((float)$historyAction['amount']>0)$eligibleCount++;}?>
    <section class="tdcob-card tdcob-history-card">
     <form method="post" action="<?=APP_URL?>/collection/<?=$case['client_id']?>/actions/delete" data-collection-bulk-delete>
      <input type="hidden" name="_token" value="<?=CSRF::token()?>">
      <div class="tdcob-history-toolbar">
       <div class="tdcob-card-head"><span><i class="fa-solid fa-clock-rotate-left"></i></span><div><strong>Histórico do cliente</strong><small><?=count($actions)?> movimentação(ões) registrada(s)</small></div></div>
       <?php if($eligibleCount>0):?><div class="tdcob-bulk-actions"><label><input type="checkbox" data-collection-select-all><span>Selecionar todos os valores</span></label><b data-collection-selected-count>0 selecionados</b><button class="tdcob-bulk-delete" type="submit" data-collection-bulk-submit data-confirm="Excluir definitivamente todos os valores selecionados? Esta ação não poderá ser desfeita." disabled><i class="fa-regular fa-trash-can"></i>Excluir selecionados</button></div><?php endif;?>
      </div>
      <div class="tdcob-history-list">
       <?php foreach($actions as $a):$canDelete=(float)$a['amount']>0;$isPayment=(string)$a['result']==='payment';$isAgreement=(string)$a['result']==='agreement';?>
        <article class="tdcob-history-item <?=$isPayment?'payment':($isAgreement?'agreement':'contact')?>">
         <div class="tdcob-history-select"><?php if($canDelete):?><input type="checkbox" name="action_ids[]" value="<?=(int)$a['id']?>" data-collection-action-check aria-label="Selecionar <?=e($resultLabels[$a['result']]??$a['result'])?> de <?=money($a['amount'])?>"><?php else:?><span></span><?php endif;?></div>
         <div class="tdcob-history-icon"><i class="fa-solid <?=$isPayment?'fa-money-bill-wave':($isAgreement?'fa-handshake':'fa-headset')?>"></i></div>
         <div class="tdcob-history-content"><div class="tdcob-history-title"><strong><?=e($resultLabels[$a['result']]??$a['result'])?></strong><?php if((float)$a['amount']>0):?><b><?=money($a['amount'])?></b><?php endif;?><?php if(($a['local_status']??'')==='pending'):?><span>Baixa local pendente</span><?php endif;?></div><small><i class="fa-regular fa-calendar"></i> Pagamento/ação: <?=date('d/m/Y H:i',strtotime($a['created_at']))?> <i class="fa-regular fa-clock"></i> Lançado: <?=date('d/m/Y H:i',strtotime((string)($a['recorded_at']??$a['created_at'])))?> por <?=e($a['author_name'])?></small><?php if($a['notes']):?><p><?=nl2br(e($a['notes']))?></p><?php endif;?></div>
         <?php if($canDelete):?><button class="tdcob-history-delete" type="submit" name="single_id" value="<?=(int)$a['id']?>" data-confirm="Excluir definitivamente este valor? O saldo e os relatórios serão recalculados." title="Excluir este lançamento"><i class="fa-regular fa-trash-can"></i><span>Excluir</span></button><?php endif;?>
        </article>
       <?php endforeach;?>
       <?php if(!$actions):?><div class="tdcob-history-empty"><i class="fa-regular fa-folder-open"></i><strong>Nenhuma movimentação registrada</strong><span>Os contatos e pagamentos aparecerão aqui.</span></div><?php endif;?>
      </div>
     </form>
    </section>
   </section>
  <?php break;

  case 'agenda':
   $stats=is_array($agendaStats??null)?$agendaStats:[];
   $agendaLate=(int)($stats['late_count']??0);
   $agendaTodayCount=(int)($stats['today_count']??0);
   $agendaUpcoming=(int)($stats['upcoming_count']??0);
   $agendaCollection=(int)($stats['collection_count']??0);
   $agendaTotal=(int)($stats['total']??0);
   $agendaRoleLabels=['seller'=>'Vendas','collector'=>'Cobrança','supervisor'=>'Supervisor','admin'=>'Admin'];
   $groups=['late'=>[],'today'=>[],'upcoming'=>[]];
   foreach($rows as $agendaRow){
    $dueDate=date('Y-m-d',strtotime((string)$agendaRow['due_at']));
    if($dueDate<date('Y-m-d'))$groups['late'][]=$agendaRow;
    elseif($dueDate===date('Y-m-d'))$groups['today'][]=$agendaRow;
    else $groups['upcoming'][]=$agendaRow;
   }
   $agendaQueryBase=[];
   if(!empty($teamAgenda)&&!empty($agendaFilterUser))$agendaQueryBase['user_id']=(int)$agendaFilterUser;
   if(($agendaType??'all')!=='all')$agendaQueryBase['type']=$agendaType;
   if(!empty($agendaCreatedDate))$agendaQueryBase['created_date']=$agendaCreatedDate;
   $vision=is_array($agendaVision??null)?$agendaVision:[];
   $visionTotal=max(1,(int)($vision['total']??0));
   $visionValues=['upcoming'=>(int)($vision['upcoming_count']??0),'today'=>(int)($vision['today_count']??0),'late'=>(int)($vision['late_count']??0),'done'=>(int)($vision['done_count']??0),'other'=>(int)($vision['other_count']??0)];
   $visionDegrees=[];$visionCursor=0;foreach($visionValues as $visionKey=>$visionValue){$visionDegrees[$visionKey]=[$visionCursor,$visionCursor+($visionValue/$visionTotal*360)];$visionCursor=$visionDegrees[$visionKey][1];}
   $weekDays=['Sunday'=>'Domingo','Monday'=>'Segunda-feira','Tuesday'=>'Terça-feira','Wednesday'=>'Quarta-feira','Thursday'=>'Quinta-feira','Friday'=>'Sexta-feira','Saturday'=>'Sábado'];
   $collectionKpiQuery=$agendaQueryBase;$collectionKpiQuery['type']='collection';unset($collectionKpiQuery['period']);
   // Comercial e cobrança compartilham a mesma experiência visual e responsiva.
   $collectionAgenda=false;
   ?>
   <?php if($collectionAgenda):?>
   <section class="tdca4-page">
    <header class="tdca4-head"><div><span class="tdca4-kicker">COBRANÇA / AGENDA</span><h1>Agenda de cobrança</h1><p>Organize e execute as atividades de cobrança com foco, prioridade e resultado.</p></div><div class="tdca4-date"><i class="fa-regular fa-calendar"></i><span><strong><?=date('d/m/Y')?></strong><small><?=$agendaTotal?> pendente(s)</small></span></div></header>
    <?php if(!empty($flash)):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>
    <section class="tdca4-filters"><form method="get"><input type="hidden" name="type" value="collection"><?php if(!empty($teamAgenda)):?><label><span>Responsável</span><select class="form-select" name="user_id"><option value="0">Todos os responsáveis</option><?php foreach($agendaUsers??[] as $agendaUser):if($agendaUser['role']!=='collector')continue;?><option value="<?=(int)$agendaUser['id']?>" <?=((int)($agendaFilterUser??0)===(int)$agendaUser['id'])?'selected':''?>><?=e($agendaUser['name'])?></option><?php endforeach;?></select></label><?php endif;?><label><span>Período</span><select class="form-select" name="period"><option value="all" <?=($agendaPeriod??'all')==='all'?'selected':''?>>Todos</option><option value="late" <?=($agendaPeriod??'all')==='late'?'selected':''?>>Atrasadas</option><option value="today" <?=($agendaPeriod??'all')==='today'?'selected':''?>>Hoje</option><option value="next7" <?=($agendaPeriod??'all')==='next7'?'selected':''?>>Próximos 7 dias</option><option value="upcoming" <?=($agendaPeriod??'all')==='upcoming'?'selected':''?>>Próximas</option></select></label><a href="<?=APP_URL?>/agenda?type=collection">Limpar filtros</a><button class="tdca4-primary" type="submit"><i class="fa-solid fa-filter"></i>Aplicar filtros</button></form></section>
    <div class="tdca4-kpis"><article><span class="green"><i class="fa-regular fa-calendar-check"></i></span><div><small>Atividades de hoje</small><strong><?=$agendaTodayCount?></strong><em>Programadas para hoje</em></div></article><article><span class="red"><i class="fa-regular fa-clock"></i></span><div><small>Atrasadas</small><strong><?=$agendaLate?></strong><em>Exigem ação imediata</em></div></article><article><span class="blue"><i class="fa-solid fa-handshake"></i></span><div><small>Próximas</small><strong><?=$agendaUpcoming?></strong><em>Planejamento futuro</em></div></article><article><span class="yellow"><i class="fa-solid fa-list-check"></i></span><div><small>Total pendente</small><strong><?=$agendaTotal?></strong><em>Compromissos de cobrança</em></div></article></div>
    <div class="tdca4-layout"><div class="tdca4-board">
     <?php foreach(['late'=>['Atrasadas','Atividades que já passaram do prazo','red'],'today'=>['Hoje','Atividades programadas para hoje','green'],'upcoming'=>['Próximas','Atividades dos próximos dias','blue']] as $groupKey=>$meta):?>
      <section class="tdca4-column <?=$meta[2]?>"><header><div><strong><?=$meta[0]?></strong><small><?=$meta[1]?></small></div><b><?=count($groups[$groupKey])?></b></header><div class="tdca4-column-body"><?php foreach(array_slice($groups[$groupKey],0,8) as $r):$due=strtotime((string)$r['due_at']);?><article><div class="client"><span><i class="fa-solid fa-building"></i></span><div><strong><?=e($r['name'])?></strong><small><?=e($r['uf']??'')?></small></div><b><?=date($groupKey==='today'?'H:i':'d/m',$due)?></b></div><div class="meta"><span><i class="fa-solid fa-user"></i><?=e($r['assigned_name']??'')?></span><span><i class="fa-solid fa-hand-holding-dollar"></i>Cobrança</span></div><p><?=e($r['title'])?></p><footer><a href="<?=APP_URL?>/collection/<?=$r['client_id']?>">Abrir</a><form method="post" action="<?=APP_URL?>/agenda/<?=$r['id']?>/done"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="user_id" value="<?=(int)($agendaFilterUser??0)?>"><input type="hidden" name="type" value="collection"><input type="hidden" name="period" value="<?=e($agendaPeriod??'all')?>"><button type="submit">Concluir</button></form></footer></article><?php endforeach;?><?php if(empty($groups[$groupKey])):?><div class="tdca4-empty">Nenhuma atividade nesta coluna.</div><?php endif;?></div></section>
     <?php endforeach;?>
    </div>
    <aside class="tdca4-side"><section><header><strong>Carga por responsável</strong><a href="<?=APP_URL?>/agenda?type=collection">Ver todos</a></header><div class="tdca4-workload"><?php $maxWork=1;foreach($agendaWorkload??[] as $w)if($w['role']==='collector')$maxWork=max($maxWork,(int)$w['total']);foreach($agendaWorkload??[] as $w):if($w['role']!=='collector')continue;?><a href="<?=APP_URL?>/agenda?type=collection&user_id=<?=$w['id']?>"><span><?=e(mb_strtoupper(mb_substr((string)$w['name'],0,1)))?></span><strong><?=e($w['name'])?></strong><div><i style="width:<?=round((int)$w['total']/$maxWork*100)?>%"></i></div><b><?=(int)$w['total']?></b></a><?php endforeach;?></div></section><section><header><strong>Resumo do dia</strong></header><div class="tdca4-summary"><span><i class="ok"></i>Hoje<b><?=$agendaTodayCount?></b></span><span><i class="danger"></i>Atrasadas<b><?=$agendaLate?></b></span><span><i class="blue"></i>Próximas<b><?=$agendaUpcoming?></b></span></div></section></aside></div>
   </section>
   <?php else:?>
   <section class="tda-page">
    <header class="tda-head">
     <div class="tda-head-main">
      <span class="tda-head-icon"><i class="fa-regular fa-calendar-check"></i></span>
      <div>
       <span class="tda-kicker"><?=!empty($teamAgenda)?'GESTÃO / AGENDA':'AGENDA'?></span>
       <h1><?=$u['role']==='collector'?'Agenda de cobrança':'Agenda e retornos'?></h1>
       <p><?=!empty($teamAgenda)?'Organize, acompanhe e execute os compromissos da equipe de forma simples e eficiente.':($u['role']==='collector'?'Organize cobranças, acordos e retornos sem perder prazos e prioridades.':'Organize retornos, cobranças e próximos contatos sem perder prioridades.')?></p>
      </div>
     </div>
     <div class="tda-date"><i class="fa-regular fa-calendar"></i><div><strong><?=date('d/m/Y')?></strong><small><?=e($weekDays[date('l')]??'Hoje')?>, hoje</small></div></div>
    </header>

    <?php if(!empty($flash)):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>

    <section class="tda-controlbar">
     <form method="get" class="tda-filters">
      <label class="tda-created"><span>Data de criação</span><input class="form-control" type="date" name="created_date" value="<?=e($agendaCreatedDate??'')?>"></label>
      <?php if(!empty($teamAgenda)):?>
       <label><span>Vendedor / Responsável</span><select class="form-select" name="user_id">
        <option value="0">Todos os responsáveis</option>
        <?php foreach($agendaUsers??[] as $agendaUser):?><option value="<?=(int)$agendaUser['id']?>" <?=((int)($agendaFilterUser??0)===(int)$agendaUser['id'])?'selected':''?>><?=e($agendaUser['name'])?> · <?=e($agendaRoleLabels[$agendaUser['role']]??$agendaUser['role'])?></option><?php endforeach;?>
       </select></label>
      <?php endif;?>
      <label><span>Tipo</span><select class="form-select" name="type" <?=$u['role']==='collector'?'disabled':''?>>
       <?php if($u['role']!=='collector'):?><option value="all" <?=($agendaType??'all')==='all'?'selected':''?>>Todos</option><option value="sales" <?=($agendaType??'all')==='sales'?'selected':''?>>Comercial</option><?php endif;?>
       <option value="collection" <?=($agendaType??'all')==='collection'?'selected':''?>>Cobrança</option>
      </select><?php if($u['role']==='collector'):?><input type="hidden" name="type" value="collection"><?php endif;?></label>
      <label><span>Período</span><select class="form-select" name="period">
       <option value="all" <?=($agendaPeriod??'all')==='all'?'selected':''?>>Todos os pendentes</option>
       <option value="late" <?=($agendaPeriod??'all')==='late'?'selected':''?>>Somente vencidos</option>
       <option value="today" <?=($agendaPeriod??'all')==='today'?'selected':''?>>Somente hoje</option>
       <option value="next7" <?=($agendaPeriod??'all')==='next7'?'selected':''?>>Próximos 7 dias</option>
       <option value="upcoming" <?=($agendaPeriod??'all')==='upcoming'?'selected':''?>>Todos os próximos</option>
      </select></label>
      <button class="tda-btn tda-btn-filter" type="submit"><i class="fa-solid fa-magnifying-glass"></i>Aplicar filtros</button>
      <a class="tda-btn" href="<?=APP_URL?>/agenda"><i class="fa-solid fa-rotate-left"></i>Limpar</a>
     </form>
    </section>

    <div class="tda-kpis">
     <a class="tda-kpi red <?=($agendaPeriod??'all')==='late'?'active':''?>" href="<?=APP_URL?>/agenda?<?=e(http_build_query($agendaQueryBase+['period'=>'late']))?>"><b><?=round($agendaTotal?$agendaLate/$agendaTotal*100:0)?>%</b><span><i class="fa-solid fa-triangle-exclamation"></i></span><small>Vencidos</small><strong><?=number_format($agendaLate,0,',','.')?></strong><em>Compromissos em atraso</em></a>
     <a class="tda-kpi yellow <?=($agendaPeriod??'all')==='today'?'active':''?>" href="<?=APP_URL?>/agenda?<?=e(http_build_query($agendaQueryBase+['period'=>'today']))?>"><b><?=round($agendaTotal?$agendaTodayCount/$agendaTotal*100:0)?>%</b><span><i class="fa-regular fa-clock"></i></span><small>Hoje</small><strong><?=number_format($agendaTodayCount,0,',','.')?></strong><em>Compromissos para hoje</em></a>
     <a class="tda-kpi blue <?=($agendaPeriod??'all')==='upcoming'?'active':''?>" href="<?=APP_URL?>/agenda?<?=e(http_build_query($agendaQueryBase+['period'=>'upcoming']))?>"><b><?=round($agendaTotal?$agendaUpcoming/$agendaTotal*100:0)?>%</b><span><i class="fa-regular fa-calendar-plus"></i></span><small>Próximos</small><strong><?=number_format($agendaUpcoming,0,',','.')?></strong><em>Compromissos nos próximos dias</em></a>
     <a class="tda-kpi green <?=($agendaType??'all')==='collection'?'active':''?>" href="<?=APP_URL?>/agenda?<?=e(http_build_query($collectionKpiQuery))?>"><b><?=round($agendaTotal?$agendaCollection/$agendaTotal*100:0)?>%</b><span><i class="fa-solid fa-coins"></i></span><small>Cobrança</small><strong><?=number_format($agendaCollection,0,',','.')?></strong><em>Compromissos financeiros</em></a>
    </div>

     <div class="tda-insights <?=empty($teamAgenda)?'single':''?>">
    <?php if(!empty($teamAgenda)):?>
     <section class="tda-team">
      <div class="tda-section-head"><div><span><i class="fa-solid fa-people-group"></i></span><div><strong>Carga da equipe</strong><small>Acompanhe a quantidade de compromissos por responsável.</small></div></div><a href="<?=APP_URL?>/agenda"><i class="fa-solid fa-users"></i> Ver detalhes</a></div>
      <?php if(empty($agendaWorkload)):?>
       <div class="tda-empty compact"><span><i class="fa-solid fa-circle-check"></i></span><div><strong>Equipe sem pendências</strong><p>Não há compromissos pendentes para os filtros atuais.</p></div></div>
      <?php else:?>
       <div class="tda-team-grid">
        <?php foreach($agendaWorkload as $work):$initial=mb_strtoupper(mb_substr((string)$work['name'],0,1));?>
         <?php $workQuery=['user_id'=>(int)$work['id']];if(($agendaType??'all')!=='all')$workQuery['type']=$agendaType;?>
         <a class="tda-person <?=((int)($agendaFilterUser??0)===(int)$work['id'])?'active':''?>" href="<?=APP_URL?>/agenda?<?=e(http_build_query($workQuery))?>">
          <span class="tda-person-avatar"><?=$initial?></span>
          <div class="tda-person-main"><strong><?=e($work['name'])?></strong><small><?=e($agendaRoleLabels[$work['role']]??$work['role'])?></small></div>
          <div class="tda-person-stats"><span><b><?=(int)$work['late_count']?></b> venc.</span><span><b><?=(int)$work['today_count']?></b> hoje</span><span><b><?=(int)$work['upcoming_count']?></b> próximos</span></div>
          <strong class="tda-person-total"><?=(int)$work['total']?></strong>
         </a>
        <?php endforeach;?>
       </div>
      <?php endif;?>
     </section>
    <?php endif;?>
     <section class="tda-vision">
      <div class="tda-section-head"><div><span><i class="fa-solid <?=!empty($teamAgenda)?'fa-chart-pie':'fa-user-clock'?>"></i></span><div><strong><?=!empty($teamAgenda)?'Visão da equipe':'Resumo da minha agenda'?></strong><small><?=!empty($teamAgenda)?'Distribuição dos compromissos por situação.':'Leitura rápida dos seus compromissos e retornos.'?></small></div></div><small><?=number_format((int)($vision['total']??0),0,',','.')?> no total</small></div>
      <?php if(!empty($teamAgenda)):?>
      <div class="tda-vision-body">
       <div class="tda-donut" style="--chart:conic-gradient(#2794f2 <?=$visionDegrees['upcoming'][0]?>deg <?=$visionDegrees['upcoming'][1]?>deg,#f2b705 <?=$visionDegrees['today'][0]?>deg <?=$visionDegrees['today'][1]?>deg,#e34a43 <?=$visionDegrees['late'][0]?>deg <?=$visionDegrees['late'][1]?>deg,#08a66a <?=$visionDegrees['done'][0]?>deg <?=$visionDegrees['done'][1]?>deg,#99a8b3 <?=$visionDegrees['other'][0]?>deg <?=$visionDegrees['other'][1]?>deg)"><span><strong><?=number_format((int)($vision['total']??0),0,',','.')?></strong><small>total</small></span></div>
       <div class="tda-legend"><?php foreach(['upcoming'=>['Próximos','blue'],'today'=>['Hoje','yellow'],'late'=>['Vencidos','red'],'done'=>['Concluídos','green'],'other'=>['Outros','gray']] as $visionKey=>$visionMeta):$visionValue=$visionValues[$visionKey];?><div><span><i class="<?=$visionMeta[1]?>"></i><?=$visionMeta[0]?></span><strong><?=number_format($visionValue,0,',','.')?></strong><b><?=round($visionValue/$visionTotal*100)?>%</b></div><?php endforeach;?></div>
      </div>
      <?php else:?>
       <div class="tda-personal-status"><?php foreach(['upcoming'=>['Próximos','blue','fa-calendar-check'],'today'=>['Hoje','yellow','fa-clock'],'late'=>['Vencidos','red','fa-triangle-exclamation'],'done'=>['Concluídos','green','fa-circle-check'],'other'=>['Outros','gray','fa-layer-group']] as $visionKey=>$visionMeta):$visionValue=$visionValues[$visionKey];?><article class="<?=$visionMeta[1]?>"><span><i class="fa-solid <?=$visionMeta[2]?>"></i></span><div><small><?=$visionMeta[0]?></small><strong><?=number_format($visionValue,0,',','.')?></strong><em><?=round($visionValue/$visionTotal*100)?>% do total</em></div></article><?php endforeach;?></div>
      <?php endif;?>
     </section>
     </div>

    <section class="tda-list table-card">
     <div class="tda-list-head"><div class="tda-list-title"><span><i class="fa-solid fa-list-check"></i></span><div><strong>Compromissos</strong><small>Gerencie os compromissos da equipe, filtre, visualize e acompanhe cada retorno.</small></div></div><button class="tda-btn tda-btn-primary" type="button" data-agenda-create><i class="fa-solid fa-plus"></i><?=$u['role']==='seller'?'Agendar para consultor':'Novo compromisso'?></button></div>
     <div class="tda-list-tabs"><?php foreach(['upcoming'=>['Próximos',$agendaUpcoming,'fa-folder-open'],'late'=>['Vencidos',$agendaLate,'fa-triangle-exclamation'],'today'=>['Hoje',$agendaTodayCount,'fa-clock'],'all'=>['Todos',$agendaTotal,'fa-list']] as $tabValue=>$tabInfo):$tabQuery=$agendaQueryBase;if($tabValue!=='all')$tabQuery['period']=$tabValue;?><a class="<?=($agendaPeriod??'all')===$tabValue?'active':''?>" href="<?=APP_URL?>/agenda?<?=e(http_build_query($tabQuery))?>"><i class="fa-solid <?=$tabInfo[2]?>"></i><?=$tabInfo[0]?> (<?=$tabInfo[1]?>)</a><?php endforeach;?></div>
     <div class="tda-table-wrap">
      <table class="table tda-table" data-page-length="5">
       <thead><tr><th>Data de criação</th><th>Data do agendamento</th><th>Cliente</th><th>Descrição</th><th>Tipo</th><th>Responsável</th><th>Status</th><th data-dt-order="disable">Ações</th></tr></thead>
       <tbody><?php foreach($rows as $r):$due=strtotime((string)$r['due_at']);$created=strtotime((string)$r['created_at']);$isLate=date('Y-m-d',$due)<date('Y-m-d');$isToday=date('Y-m-d',$due)===date('Y-m-d');$isCollection=($r['type']??'')==='collection';$responsibleInitial=mb_strtoupper(mb_substr((string)($r['assigned_name']??''),0,1));?>
        <tr>
         <td data-order="<?=$created?>"><strong><?=date('d/m/Y H:i',$created)?></strong></td>
         <td data-order="<?=$due?>"><strong><?=date('d/m/Y H:i',$due)?></strong></td>
         <td><a class="tda-table-client" href="<?=APP_URL?>/<?=$isCollection?'collection':'clients'?>/<?=$r['client_id']?>"><?=e($r['name'])?></a></td>
         <td><span class="tda-description"><?=e($r['title'])?></span></td>
         <td><span class="tda-type <?=$isCollection?'collection':''?>"><i class="fa-solid <?=$isCollection?'fa-hand-holding-dollar':'fa-user-tie'?>"></i><?=$isCollection?'Cobrança':'Comercial'?></span></td>
         <td><span class="tda-owner-cell"><i><?=$responsibleInitial?></i><b><?=e($r['assigned_name']??'Não identificado')?></b></span></td>
         <td><span class="tda-status <?=$isLate?'late':($isToday?'today':'upcoming')?>"><i class="fa-regular <?=$isLate?'fa-circle-xmark':($isToday?'fa-clock':'fa-calendar-check')?>"></i><?=$isLate?'Vencido':($isToday?'Hoje':'Agendado')?></span></td>
         <td><div class="tda-actions compact">
          <a class="tda-btn tda-btn-open" href="<?=APP_URL?>/<?=$isCollection?'collection':'clients'?>/<?=$r['client_id']?>" title="Abrir cliente"><i class="fa-solid fa-eye"></i></a>
          <details class="tda-reschedule"><summary class="tda-btn tda-btn-edit" title="Editar"><i class="fa-solid fa-pen"></i></summary><form method="post" action="<?=APP_URL?>/agenda/<?=$r['id']?>/edit"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="user_id" value="<?=(int)($agendaFilterUser??0)?>"><input type="hidden" name="type" value="<?=e($agendaType??'all')?>"><input type="hidden" name="period" value="<?=e($agendaPeriod??'all')?>"><label><span>Descrição</span><input class="form-control" type="text" name="title" value="<?=e($r['title'])?>" maxlength="180" required></label><button class="tda-btn tda-btn-edit" type="submit"><i class="fa-solid fa-check"></i>Salvar descrição</button></form></details>
          <details class="tda-reschedule"><summary class="tda-btn tda-btn-reschedule" title="Reagendar"><i class="fa-regular fa-calendar-plus"></i></summary><form method="post" action="<?=APP_URL?>/agenda/<?=$r['id']?>/reschedule"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="user_id" value="<?=(int)($agendaFilterUser??0)?>"><input type="hidden" name="type" value="<?=e($agendaType??'all')?>"><input type="hidden" name="period" value="<?=e($agendaPeriod??'all')?>"><label><span>Nova data e horário</span><input class="form-control" type="datetime-local" name="due_at" value="<?=date('Y-m-d\TH:i',$due)?>" required></label><button class="tda-btn tda-btn-reschedule" type="submit"><i class="fa-solid fa-calendar-check"></i>Confirmar horário</button></form></details>
          <form method="post" action="<?=APP_URL?>/agenda/<?=$r['id']?>/done"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="user_id" value="<?=(int)($agendaFilterUser??0)?>"><input type="hidden" name="type" value="<?=e($agendaType??'all')?>"><input type="hidden" name="period" value="<?=e($agendaPeriod??'all')?>"><button class="tda-btn tda-btn-primary" data-confirm="Concluir este compromisso?" title="Concluir"><i class="fa-solid fa-check"></i></button></form>
          <form method="post" action="<?=APP_URL?>/agenda/<?=$r['id']?>/delete"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="user_id" value="<?=(int)($agendaFilterUser??0)?>"><input type="hidden" name="type" value="<?=e($agendaType??'all')?>"><input type="hidden" name="period" value="<?=e($agendaPeriod??'all')?>"><button class="tda-btn tda-btn-danger" data-confirm="Excluir este compromisso da agenda?" title="Excluir"><i class="fa-regular fa-trash-can"></i></button></form>
         </div></td>
        </tr>
       <?php endforeach;?></tbody>
      </table>
     </div>
    </section>
     <dialog class="tda-create-modal" data-agenda-create-modal>
      <form method="post" action="<?=APP_URL?>/agenda/create" data-agenda-create-form>
       <input type="hidden" name="_token" value="<?=CSRF::token()?>">
       <header><span><i class="fa-regular fa-calendar-plus"></i></span><div><small><?=$u['role']==='collector'?'AGENDA DE COBRANÇA':($u['role']==='seller'?'AGENDAMENTO DIRECIONADO':'AGENDA E RETORNOS')?></small><strong><?=$u['role']==='seller'?'Agendar para um consultor':'Novo compromisso'?></strong></div><button type="button" data-agenda-create-close><i class="fa-solid fa-xmark"></i></button></header>
       <div class="tda-create-body">
        <label class="wide"><span>Cliente</span><div class="search-box"><input class="form-control" type="search" placeholder="Busque por nome fantasia, documento ou código" autocomplete="off" data-agenda-client-search><input type="hidden" name="client_id" data-agenda-client-id required><div class="search-results" data-agenda-client-results></div><div class="selected-box" data-agenda-client-selected></div></div></label>
        <?php if(!empty($teamAgenda)||$u['role']==='seller'):?>
         <label><span><?=$u['role']==='seller'?'Consultor responsável':'Responsável'?></span><select class="form-select" name="assigned_user_id" required><option value="">Selecione</option><?php foreach($agendaAssignableUsers??[] as $agendaUser):?><option value="<?=(int)$agendaUser['id']?>" <?=((int)$agendaUser['id']===(int)$u['id'])?'selected':''?>><?=e($agendaUser['name'])?> · <?=e($agendaRoleLabels[$agendaUser['role']]??$agendaUser['role'])?></option><?php endforeach;?></select></label>
        <?php endif;?>
        <label><span>Tipo</span><?php if($u['role']==='collector'):?><input type="hidden" name="type" value="collection"><input class="form-control" value="Cobrança" disabled><?php elseif($u['role']==='seller'):?><input type="hidden" name="type" value="sales"><input class="form-control" value="Comercial" disabled><?php else:?><select class="form-select" name="type"><option value="sales">Comercial</option><option value="collection">Cobrança</option></select><?php endif;?></label>
        <label><span>Data e hora</span><input class="form-control" type="datetime-local" name="due_at" required></label>
        <label class="wide"><span>Descrição / motivo</span><input class="form-control" name="title" maxlength="180" placeholder="Ex.: Cliente ligou e pediu retorno sobre proposta" required></label>
        <?php if($u['role']==='seller'):?><div class="tda-directed-note wide"><i class="fa-solid fa-circle-info"></i><span>Este agendamento cria apenas um compromisso para o consultor escolhido. <strong>A carteira e o vendedor principal do cliente não serão alterados.</strong></span></div><?php endif;?>
       </div>
       <footer><button class="tda-btn" type="button" data-agenda-create-close>Cancelar</button><button class="tda-btn tda-btn-primary" type="submit"><i class="fa-solid fa-calendar-check"></i><?=$u['role']==='seller'?'Agendar para consultor':'Salvar compromisso'?></button></footer>
      </form>
     </dialog>
   </section>
  <?php endif;?>
  <?php break;

  case 'management_result':$g=$management['general_goal'];?>
   <section class="tdr-page">
    <header class="tdr-head">
     <div class="tdr-head-main"><span class="tdr-head-icon"><i class="fa-solid fa-chart-column"></i></span><div><span class="tdr-kicker">GESTÃO / RESULTADOS</span><h1>Resultados</h1><p>Acompanhe desempenho comercial, cobrança, metas e ranking da equipe.</p></div></div>
     <form class="tdr-month" method="get"><label><span>Mês</span><input class="form-control" type="month" name="month" value="<?=e($month)?>" onchange="this.form.submit()"></label><?php foreach($selectedDays as $selectedDay):?><input type="hidden" name="days[]" value="<?=$selectedDay?>"><?php endforeach;?></form>
    </header>

    <form class="tdr-filter" method="get" data-result-days>
     <input type="hidden" name="month" value="<?=e($month)?>">
     <div class="tdr-filter-head"><div class="tdr-filter-title"><span><i class="fa-regular fa-calendar-days"></i></span><div><small>PERÍODO DO RESULTADO</small><strong>Escolha os dias que deseja apresentar</strong><p data-result-day-count><?=$selectedDays?(count($selectedDays)===1?'1 dia selecionado':count($selectedDays).' dias selecionados'):'Mês inteiro selecionado'?></p></div></div><div class="tdr-filter-actions"><a class="tdr-btn" href="<?=APP_URL?>/result?month=<?=rawurlencode($month)?>"><i class="fa-solid fa-calendar-check"></i>Mês inteiro</a><button class="tdr-btn tdr-btn-primary" type="submit"><i class="fa-solid fa-filter"></i>Aplicar dias</button></div></div>
     <div class="tdr-day-grid"><?php for($resultDay=1;$resultDay<=31;$resultDay++):$available=$resultDay<=$daysInMonth;?><label class="<?=$available?'':'unavailable'?>"><input type="checkbox" name="days[]" value="<?=$resultDay?>" <?=in_array($resultDay,$selectedDays,true)?'checked':''?> <?=$available?'':'disabled'?>><span><?=str_pad((string)$resultDay,2,'0',STR_PAD_LEFT)?></span></label><?php endfor;?></div>
    </form>

    <div class="tdr-note"><i class="fa-solid fa-circle-info"></i><span>Pedidos OK utilizam o valor total do pedido com frete incluído e respeitam a política de exclusão de orçamentos, cancelados e demais situações inválidas.</span></div>

    <div class="tdr-kpis">
     <article class="tdr-kpi green"><div class="tdr-kpi-top"><span class="tdr-kpi-icon"><i class="fa-solid fa-sack-dollar"></i></span><b><?=number_format($management['sales_percent'],1,',','.')?>%</b></div><small>Pedidos OK</small><strong><?=money($management['sales'])?></strong><p><?=e($management['goal_scope'])?> · meta <?=money($management['effective_sales_goal'])?></p><div class="tdr-progress"><span style="width:<?=min(100,$management['sales_percent'])?>%"></span></div></article>
     <article class="tdr-kpi orange"><div class="tdr-kpi-top"><span class="tdr-kpi-icon"><i class="fa-solid fa-hand-holding-dollar"></i></span><b><?=number_format($management['collection_percent'],1,',','.')?>%</b></div><small>Recuperado</small><strong><?=money($management['recovered'])?></strong><p><?=e($management['goal_scope'])?> · meta <?=money($management['effective_collection_goal'])?></p><div class="tdr-progress orange"><span style="width:<?=min(100,$management['collection_percent'])?>%"></span></div></article>
     <article class="tdr-kpi blue"><div class="tdr-kpi-top"><span class="tdr-kpi-icon"><i class="fa-solid fa-phone"></i></span><b><?=number_format($management['contact_percent'],1,',','.')?>%</b></div><small>Contatos</small><strong><?=number_format((int)$management['contacts'],0,',','.')?></strong><p>Meta <?=number_format((float)$management['effective_contact_goal'],0,',','.')?></p><div class="tdr-progress blue"><span style="width:<?=min(100,$management['contact_percent'])?>%"></span></div></article>
     <article class="tdr-kpi yellow"><div class="tdr-kpi-top"><span class="tdr-kpi-icon"><i class="fa-solid fa-receipt"></i></span></div><small>Pedidos sem frete</small><strong><?=money($management['order_sales_without_freight']??0)?></strong><p>Com frete: <?=money($management['order_sales'])?></p></article>
    </div>

    <div class="tdr-grid">
     <section class="tdr-card"><div class="tdr-card-head"><div class="tdr-card-title"><span class="green"><i class="fa-solid fa-user-tie"></i></span><div><small>COMERCIAL</small><strong>Ranking de vendedores</strong></div></div></div><div class="tdr-ranking"><?php foreach($management['sellers'] as $idx=>$row):$usr=$row['user'];?><div class="tdr-rank"><span class="tdr-rank-num"><?=($idx+1)?></span><div><strong><?=e($usr['name'])?></strong><small>Com frete <?=money($row['sales'])?> · Sem frete <?=money($row['orders_without_freight']??0)?></small></div><div class="tdr-rank-value"><strong><?=number_format($row['sales_percent'],1,',','.')?>%</strong><div class="tdr-mini-progress"><span style="width:<?=min(100,$row['sales_percent'])?>%"></span></div></div></div><?php endforeach;?><?php if(!$management['sellers']):?><div class="tdr-empty">Nenhum vendedor com usuário vinculado.</div><?php endif;?></div></section>
     <section class="tdr-card"><div class="tdr-card-head"><div class="tdr-card-title"><span class="orange"><i class="fa-solid fa-hand-holding-dollar"></i></span><div><small>COBRANÇA</small><strong>Ranking de recuperação</strong></div></div></div><div class="tdr-ranking"><?php foreach($management['collectors'] as $idx=>$row):$usr=$row['user'];?><div class="tdr-rank"><span class="tdr-rank-num"><?=($idx+1)?></span><div><strong><?=e($usr['name'])?></strong><small><?=money($row['recovered'])?> de <?=money($row['goal']['collection_goal'])?></small></div><div class="tdr-rank-value"><strong><?=number_format($row['collection_percent'],1,',','.')?>%</strong><div class="tdr-mini-progress orange"><span style="width:<?=min(100,$row['collection_percent'])?>%"></span></div></div></div><?php endforeach;?><?php if(!$management['collectors']):?><div class="tdr-empty">Nenhum responsável de cobrança encontrado.</div><?php endif;?></div></section>
    </div>

    <?php if(!empty($management['virtual_sellers'])):?>
    <section class="tdr-card"><div class="tdr-card-head"><div class="tdr-card-title"><span class="blue"><i class="fa-solid fa-robot"></i></span><div><small>VENDEDORES VIRTUAIS</small><strong>Canais automáticos</strong></div></div></div><div class="tdr-virtual-grid"><?php foreach($management['virtual_sellers'] as $row):$goal=(float)($row['goal']['sales_goal']??0);?><article class="tdr-virtual"><div class="tdr-virtual-head"><span><i class="fa-solid <?=!empty($row['ead_reciclagem'])?'fa-graduation-cap':'fa-headset'?>"></i></span><div><small>VENDEDOR VIRTUAL</small><strong><?=e($row['seller']['name'])?></strong></div><b><?=number_format($row['sales_percent']??0,1,',','.')?>%</b></div><div class="tdr-virtual-value"><strong><?=money($row['sales'])?></strong> <span>de <?=money($goal)?></span></div><div class="tdr-mini-progress <?=!empty($row['ead_reciclagem'])?'':'blue'?>"><span style="width:<?=min(100,$row['sales_percent']??0)?>%"></span></div><div class="tdr-virtual-foot"><i class="fa-solid fa-receipt"></i> Com frete <strong><?=money($row['orders'])?></strong> · Sem frete <strong><?=money($row['orders_without_freight']??0)?></strong></div></article><?php endforeach;?></div></section>
    <?php endif;?>
   </section>
  <?php break;

  case 'result':$u=$result['user'];$g=$result['goal'];?>
   <section class="tdr-page">
    <header class="tdr-head">
     <div class="tdr-head-main"><span class="tdr-head-icon"><i class="fa-solid fa-chart-line"></i></span><div><span class="tdr-kicker">MEU RESULTADO</span><h1><?=e(explode(' ',trim((string)$u['name']))[0]??$u['name'])?></h1><p>Acompanhe seu desempenho no período selecionado e o avanço em relação à meta.</p></div></div>
     <form class="tdr-month" method="get"><label><span>Mês</span><input class="form-control" type="month" name="month" value="<?=e($month)?>" onchange="this.form.submit()"></label><?php foreach($selectedDays as $selectedDay):?><input type="hidden" name="days[]" value="<?=$selectedDay?>"><?php endforeach;?></form>
    </header>

    <form class="tdr-filter" method="get" data-result-days>
     <input type="hidden" name="month" value="<?=e($month)?>">
     <div class="tdr-filter-head"><div class="tdr-filter-title"><span><i class="fa-regular fa-calendar-days"></i></span><div><small>PERÍODO DO RESULTADO</small><strong>Escolha os dias que deseja apresentar</strong><p data-result-day-count><?=$selectedDays?(count($selectedDays)===1?'1 dia selecionado':count($selectedDays).' dias selecionados'):'Mês inteiro selecionado'?></p></div></div><div class="tdr-filter-actions"><a class="tdr-btn" href="<?=APP_URL?>/result?month=<?=rawurlencode($month)?>"><i class="fa-solid fa-calendar-check"></i>Mês inteiro</a><button class="tdr-btn tdr-btn-primary" type="submit"><i class="fa-solid fa-filter"></i>Aplicar dias</button></div></div>
     <div class="tdr-day-grid"><?php for($resultDay=1;$resultDay<=31;$resultDay++):$available=$resultDay<=$daysInMonth;?><label class="<?=$available?'':'unavailable'?>"><input type="checkbox" name="days[]" value="<?=$resultDay?>" <?=in_array($resultDay,$selectedDays,true)?'checked':''?> <?=$available?'':'disabled'?>><span><?=str_pad((string)$resultDay,2,'0',STR_PAD_LEFT)?></span></label><?php endfor;?></div>
    </form>

    <?php if($u['role']==='seller'):?>
     <div class="tdr-note"><i class="fa-solid fa-circle-info"></i><span>Pedidos OK consideram o valor total do pedido com frete incluído e excluem situações fora da política comercial.</span></div>
     <div class="tdr-personal">
      <section class="tdr-main"><div class="tdr-main-head"><div class="tdr-main-title"><span><i class="fa-solid fa-chart-line"></i></span><div><small>REALIZADO COMERCIAL</small><strong>Pedidos OK</strong></div></div><b><?=number_format($result['sales_percent'],1,',','.')?>%</b></div><div class="tdr-main-body"><div class="tdr-main-value"><strong><?=money($result['sales'])?></strong><span>de <?=money($g['sales_goal'])?> · <?=e($result['goal_scope'])?></span></div><div class="tdr-progress"><span style="width:<?=min(100,$result['sales_percent'])?>%"></span></div><div class="tdr-main-foot"><div><small>Com frete</small><strong><?=money($result['orders_sales']??0)?></strong></div><div><small>Sem frete</small><strong><?=money($result['orders_without_freight']??0)?></strong></div></div></div></section>
      <article class="tdr-side blue"><span><i class="fa-solid fa-address-book"></i></span><small>CONTATOS</small><strong><?=$result['contacts']?></strong><p><?=e(mb_strtolower($result['goal_scope']))?> · meta <?=number_format((float)$g['contact_goal'],1,',','.')?></p></article>
      <article class="tdr-side green"><span><i class="fa-solid fa-bullseye"></i></span><small>ATINGIMENTO</small><strong><?=number_format($result['sales_percent'],1,',','.')?>%</strong><p>da <?=e(mb_strtolower($result['goal_scope']))?></p></article>
     </div>
    <?php else:?>
     <div class="tdr-personal">
      <section class="tdr-main collector"><div class="tdr-main-head"><div class="tdr-main-title"><span><i class="fa-solid fa-hand-holding-dollar"></i></span><div><small>RECUPERAÇÃO</small><strong>Valor recuperado</strong></div></div><b><?=number_format($result['collection_percent'],1,',','.')?>%</b></div><div class="tdr-main-body"><div class="tdr-main-value"><strong><?=money($result['recovered'])?></strong><span>de <?=money($g['collection_goal'])?> · <?=e($result['goal_scope'])?></span></div><div class="tdr-progress orange"><span style="width:<?=min(100,$result['collection_percent'])?>%"></span></div></div></section>
      <article class="tdr-side orange"><span><i class="fa-solid fa-phone"></i></span><small>AÇÕES</small><strong><?=$result['contacts']?></strong><p><?=e(mb_strtolower($result['goal_scope']))?> · meta <?=number_format((float)$g['contact_goal'],1,',','.')?></p></article>
      <article class="tdr-side green"><span><i class="fa-solid fa-bullseye"></i></span><small>ATINGIMENTO</small><strong><?=number_format($result['collection_percent'],1,',','.')?>%</strong><p>da <?=e(mb_strtolower($result['goal_scope']))?></p></article>
     </div>
    <?php endif;?>
   </section>
  <?php break;
  case 'users':$editing=$edit??null;$activeUsers=0;$sellerUsers=0;$collectorUsers=0;foreach($users as $uu){if(!empty($uu['active']))$activeUsers++;if(($uu['role']??'')==='seller')$sellerUsers++;if(($uu['role']??'')==='collector')$collectorUsers++;}?>
   <section class="tdsys-page">
    <header class="tdsys-head"><div class="tdsys-head-main"><span class="tdsys-head-icon"><i class="fa-solid fa-users-gear"></i></span><div><span class="tdsys-kicker">GESTÃO / ACESSOS</span><h1>Usuários e acessos</h1><p>Gerencie perfis, permissões, status e vínculos operacionais da equipe.</p></div></div><?php if($editing):?><a class="tdsys-btn" href="<?=APP_URL?>/users"><i class="fa-solid fa-plus"></i>Novo usuário</a><?php endif;?></header>
    <div class="tdsys-kpis"><article><span><i class="fa-solid fa-users"></i></span><small>Total</small><strong><?=count($users)?></strong></article><article><span><i class="fa-solid fa-user-check"></i></span><small>Ativos</small><strong><?=$activeUsers?></strong></article><article><span><i class="fa-solid fa-user-tie"></i></span><small>Vendedores</small><strong><?=$sellerUsers?></strong></article><article><span><i class="fa-solid fa-headset"></i></span><small>Cobrança</small><strong><?=$collectorUsers?></strong></article></div>
    <div class="tdsys-two">
     <section class="tdsys-card"><div class="tdsys-card-head"><span><i class="fa-solid <?=$editing?'fa-user-pen':'fa-user-plus'?>"></i></span><div><strong><?=$editing?'Editar usuário':'Novo usuário'?></strong><small><?=$editing?'Atualize acesso e vínculo operacional.':'Crie um novo acesso ao CRM.'?></small></div></div><form class="tdsys-form" method="post" action="<?=APP_URL?>/users"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="id" value="<?=(int)($editing['id']??0)?>"><label>Nome</label><input class="form-control" name="name" value="<?=e($editing['name']??'')?>" required><label>E-mail</label><input class="form-control" type="email" name="email" value="<?=e($editing['email']??'')?>" required><label>Perfil</label><select class="form-select" name="role" data-role-select><?php foreach(['seller'=>'Vendedor','collector'=>'Cobrança','supervisor'=>'Supervisor','admin'=>'Administrador'] as $rv=>$rl):?><option value="<?=$rv?>" <?=($editing['role']??'seller')===$rv?'selected':''?>><?=$rl?></option><?php endforeach;?></select><div data-seller-field><label>Vendedor Omie</label><select class="form-select" name="seller_omie_code"><option value="">Selecione...</option><?php foreach($sellers as $s):?><option value="<?=e($s['omie_code'])?>" <?=($editing['seller_omie_code']??'')===$s['omie_code']?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select></div><label>Senha <?=$editing?'<small>(vazia mantém a atual)</small>':''?></label><input class="form-control" type="password" name="password" <?=$editing?'':'required'?>><label class="tdsys-check"><input type="checkbox" name="active" value="1" <?=!$editing||$editing['active']?'checked':''?>>Usuário ativo</label><button class="tdsys-btn tdsys-btn-primary w-100"><i class="fa-solid fa-check"></i><?=$editing?'Salvar alterações':'Criar usuário'?></button></form></section>
     <section class="tdsys-card"><div class="tdsys-card-head"><span><i class="fa-solid fa-table-list"></i></span><div><strong>Usuários cadastrados</strong><small>10 registros por página com busca e edição rápida.</small></div></div><div class="table-card tdsys-table-wrap"><table class="table tdsys-table" data-page-length="10" data-length-change="1"><thead><tr><th>Usuário</th><th>Perfil</th><th>Vínculo</th><th>Status</th><th data-dt-order="disable"></th></tr></thead><tbody><?php foreach($users as $row):?><tr><td><strong><?=e($row['name'])?></strong><small><?=e($row['email'])?></small></td><td><?php $roleLabel=['seller'=>'Vendedor','collector'=>'Cobrança','supervisor'=>'Supervisor','admin'=>'Administrador'][$row['role']]??$row['role'];?><span class="tdsys-badge role-<?=e($row['role'])?>"><?=e($roleLabel)?></span></td><td><?=e($row['seller_omie_code']??'—')?></td><td><span class="tdsys-status <?=$row['active']?'ok':'off'?>"><?=$row['active']?'Ativo':'Inativo'?></span></td><td class="text-end"><a class="tdsys-icon-btn" href="<?=APP_URL?>/users?edit=<?=$row['id']?>" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a></td></tr><?php endforeach;?></tbody></table></div></section>
    </div>
   </section>
  <?php break;
  case 'goals':
   $monthRef=(string)($management['month']??$month);
   $general=$management['general_goal'];
   $sellerRows=[];$collectorRows=[];
   foreach($rows as $goalRow){
    if(($goalRow['user']['role']??'')==='seller')$sellerRows[]=$goalRow;
    else $collectorRows[]=$goalRow;
   }
   $salesGoal=(float)($management['effective_sales_goal']??0);
   $collectionGoal=(float)($management['effective_collection_goal']??0);
   $contactGoal=(float)($management['effective_contact_goal']??0);
   $salesValue=(float)($management['sales']??0);
   $collectionValue=(float)($management['recovered']??0);
   $contactValue=(int)($management['contacts']??0);
   $salesRemaining=max(0,$salesGoal-$salesValue);
   $collectionRemaining=max(0,$collectionGoal-$collectionValue);
   $salesProgress=min(100,max(0,(float)($management['sales_percent']??0)));
   $collectionProgress=min(100,max(0,(float)($management['collection_percent']??0)));
   $contactProgress=min(100,max(0,(float)($management['contact_percent']??0)));
   $monthLabel=date('m/Y',strtotime($monthRef.'-01'));
   ?>
   <section class="tdgoal-page">
    <header class="tdgoal-head">
     <div class="tdgoal-head-main">
      <span class="tdgoal-head-icon"><i class="fa-solid fa-bullseye"></i></span>
      <div>
       <span class="tdgoal-kicker">GESTÃO / METAS</span>
       <h1>Metas</h1>
       <p>Defina os objetivos do mês e acompanhe o avanço da operação, da equipe e dos canais automáticos.</p>
      </div>
     </div>
     <form class="tdgoal-period" method="get">
      <label><span>Mês de referência</span><input class="form-control" type="month" name="month" value="<?=e($monthRef)?>" onchange="this.form.submit()"></label>
     </form>
    </header>

    <div class="tdgoal-kpis">
     <article class="tdgoal-kpi green">
      <span class="tdgoal-kpi-icon"><i class="fa-solid fa-chart-line"></i></span>
      <div class="tdgoal-kpi-main"><small>Resultado comercial</small><strong><?=money($salesValue)?></strong><p>Meta <?=money($salesGoal)?></p><div class="tdgoal-progress"><span style="width:<?=$salesProgress?>%"></span></div></div>
      <b><?=number_format((float)($management['sales_percent']??0),1,',','.')?>%</b>
     </article>
     <article class="tdgoal-kpi blue">
      <span class="tdgoal-kpi-icon"><i class="fa-solid fa-flag-checkered"></i></span>
      <div class="tdgoal-kpi-main"><small><?=$salesRemaining>0?'Falta para a meta':'Meta comercial alcançada'?></small><strong><?=money($salesRemaining)?></strong><p><?=$salesRemaining>0?'Saldo necessário no mês':'Objetivo comercial cumprido'?></p></div>
     </article>
     <article class="tdgoal-kpi orange">
      <span class="tdgoal-kpi-icon"><i class="fa-solid fa-hand-holding-dollar"></i></span>
      <div class="tdgoal-kpi-main"><small>Recuperação</small><strong><?=money($collectionValue)?></strong><p>Meta <?=money($collectionGoal)?> · faltam <?=money($collectionRemaining)?></p><div class="tdgoal-progress orange"><span style="width:<?=$collectionProgress?>%"></span></div></div>
      <b><?=number_format((float)($management['collection_percent']??0),1,',','.')?>%</b>
     </article>
     <article class="tdgoal-kpi yellow">
      <span class="tdgoal-kpi-icon"><i class="fa-solid fa-phone-volume"></i></span>
      <div class="tdgoal-kpi-main"><small>Contatos / ações</small><strong><?=number_format($contactValue,0,',','.')?></strong><p>Meta <?=number_format((int)$contactGoal,0,',','.')?></p><div class="tdgoal-progress yellow"><span style="width:<?=$contactProgress?>%"></span></div></div>
      <b><?=number_format((float)($management['contact_percent']??0),1,',','.')?>%</b>
     </article>
    </div>

    <section class="tdgoal-general">
     <header class="tdgoal-section-head">
      <div class="tdgoal-section-title">
       <span class="blue"><i class="fa-solid fa-building"></i></span>
       <div><small>OBJETIVO CORPORATIVO</small><strong>Meta geral da operação</strong><p>Use uma meta geral própria ou deixe o campo zerado para o CRM adotar automaticamente a soma das metas individuais.</p></div>
      </div>
      <span class="tdgoal-month-badge"><i class="fa-regular fa-calendar"></i><?=$monthLabel?></span>
     </header>
     <form class="tdgoal-general-form" method="post" action="<?=APP_URL?>/goals/general">
      <input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="month" value="<?=e($monthRef)?>">
      <label class="tdgoal-goal-field">
       <span class="tdgoal-field-icon green"><i class="fa-solid fa-sack-dollar"></i></span>
       <span class="tdgoal-field-copy"><strong>Meta comercial</strong><small><?=((float)($general['sales_goal']??0)>0)?'Meta geral definida':'Usando soma da equipe: '.money($management['sales_goal_sum'])?></small></span>
       <span class="tdgoal-input money"><i>R$</i><input class="form-control" type="number" min="0" step="0.01" name="sales_goal" value="<?=e((string)($general['sales_goal']??0))?>"></span>
      </label>
      <label class="tdgoal-goal-field">
       <span class="tdgoal-field-icon orange"><i class="fa-solid fa-wallet"></i></span>
       <span class="tdgoal-field-copy"><strong>Meta de recuperação</strong><small><?=((float)($general['collection_goal']??0)>0)?'Meta geral definida':'Usando soma da equipe: '.money($management['collection_goal_sum'])?></small></span>
       <span class="tdgoal-input money"><i>R$</i><input class="form-control" type="number" min="0" step="0.01" name="collection_goal" value="<?=e((string)($general['collection_goal']??0))?>"></span>
      </label>
      <label class="tdgoal-goal-field">
       <span class="tdgoal-field-icon yellow"><i class="fa-solid fa-headset"></i></span>
       <span class="tdgoal-field-copy"><strong>Meta de contatos</strong><small><?=((int)($general['contact_goal']??0)>0)?'Meta geral definida':'Usando soma da equipe: '.number_format((int)$management['contact_goal_sum'],0,',','.')?></small></span>
       <span class="tdgoal-input"><input class="form-control" type="number" min="0" step="1" name="contact_goal" value="<?=(int)($general['contact_goal']??0)?>"></span>
      </label>
      <div class="tdgoal-general-actions">
       <span><i class="fa-solid fa-circle-info"></i>Valor zero mantém a composição automática pelas metas individuais.</span>
       <button class="tdgoal-btn primary" type="submit"><i class="fa-solid fa-check"></i>Salvar meta geral</button>
      </div>
     </form>
    </section>

    <section class="tdgoal-team-section">
     <header class="tdgoal-section-head">
      <div class="tdgoal-section-title">
       <span class="green"><i class="fa-solid fa-user-tie"></i></span>
       <div><small>COMERCIAL</small><strong>Metas da equipe de vendas</strong><p>Edite a meta financeira e a meta de contatos de cada vendedor sem perder a leitura do realizado.</p></div>
      </div>
      <span class="tdgoal-count"><?=count($sellerRows)?> vendedor(es)</span>
     </header>
     <?php if(!$sellerRows):?>
      <div class="tdgoal-empty"><i class="fa-solid fa-user-slash"></i><div><strong>Nenhum vendedor ativo</strong><p>Não há usuários comerciais disponíveis para configurar neste mês.</p></div></div>
     <?php else:?>
      <div class="tdgoal-person-grid">
       <?php foreach($sellerRows as $row):$usr=$row['user'];$g=$row['goal'];$pct=(float)($row['sales_percent']??0);$bar=min(100,max(0,$pct));?>
        <form class="tdgoal-person-card" method="post" action="<?=APP_URL?>/goals/<?=$usr['id']?>">
         <input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="month" value="<?=e($monthRef)?>">
         <div class="tdgoal-person-head">
          <span class="tdgoal-avatar"><?=e(mb_strtoupper(mb_substr((string)$usr['name'],0,1)))?></span>
          <div><strong><?=e($usr['name'])?></strong><small>Vendedor<?=!empty($usr['seller_omie_code'])?' · Omie '.e($usr['seller_omie_code']):''?></small></div>
          <span class="tdgoal-status <?=$pct>=100?'success':($pct>=75?'progress':'attention')?>"><?=$pct>=100?'Meta atingida':($pct>=75?'Em evolução':'Acompanhar')?></span>
         </div>
         <div class="tdgoal-performance">
          <div><small>Realizado</small><strong><?=money($row['sales'])?></strong><span>Pedidos <?=money($row['orders_sales']??0)?> · Serviços <?=money($row['services_sales']??0)?></span></div>
          <b><?=number_format($pct,1,',','.')?>%</b>
         </div>
         <div class="tdgoal-progress"><span style="width:<?=$bar?>%"></span></div>
         <div class="tdgoal-edit-grid">
          <label><span>Meta de vendas</span><div class="tdgoal-input money"><i>R$</i><input class="form-control" type="number" min="0" step="0.01" name="sales_goal" value="<?=e((string)($g['sales_goal']??0))?>"></div></label>
          <label><span>Meta de contatos</span><div class="tdgoal-input"><input class="form-control" type="number" min="0" step="1" name="contact_goal" value="<?=(int)($g['contact_goal']??0)?>"></div><small><?=number_format((int)($row['contacts']??0),0,',','.')?> realizados</small></label>
         </div>
         <button class="tdgoal-btn save" type="submit"><i class="fa-solid fa-check"></i>Salvar metas de <?=e(explode(' ',trim((string)$usr['name']))[0]??'vendedor')?></button>
        </form>
       <?php endforeach;?>
      </div>
     <?php endif;?>
    </section>

    <section class="tdgoal-team-section">
     <header class="tdgoal-section-head">
      <div class="tdgoal-section-title">
       <span class="orange"><i class="fa-solid fa-headset"></i></span>
       <div><small>COBRANÇA</small><strong>Metas da equipe de cobrança</strong><p>Configure recuperação e produtividade por responsável, acompanhando o valor recuperado no mês.</p></div>
      </div>
      <span class="tdgoal-count"><?=count($collectorRows)?> responsável(is)</span>
     </header>
     <?php if(!$collectorRows):?>
      <div class="tdgoal-empty"><i class="fa-solid fa-user-slash"></i><div><strong>Nenhum responsável ativo</strong><p>Não há usuários de cobrança disponíveis para configurar neste mês.</p></div></div>
     <?php else:?>
      <div class="tdgoal-person-grid">
       <?php foreach($collectorRows as $row):$usr=$row['user'];$g=$row['goal'];$pct=(float)($row['collection_percent']??0);$bar=min(100,max(0,$pct));?>
        <form class="tdgoal-person-card collection" method="post" action="<?=APP_URL?>/goals/<?=$usr['id']?>">
         <input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="month" value="<?=e($monthRef)?>">
         <div class="tdgoal-person-head">
          <span class="tdgoal-avatar orange"><?=e(mb_strtoupper(mb_substr((string)$usr['name'],0,1)))?></span>
          <div><strong><?=e($usr['name'])?></strong><small>Responsável de cobrança</small></div>
          <span class="tdgoal-status <?=$pct>=100?'success':($pct>=75?'progress':'attention')?>"><?=$pct>=100?'Meta atingida':($pct>=75?'Em evolução':'Acompanhar')?></span>
         </div>
         <div class="tdgoal-performance">
          <div><small>Recuperado</small><strong><?=money($row['recovered'])?></strong><span><?=number_format((int)($row['contacts']??0),0,',','.')?> ações registradas no mês</span></div>
          <b><?=number_format($pct,1,',','.')?>%</b>
         </div>
         <div class="tdgoal-progress orange"><span style="width:<?=$bar?>%"></span></div>
         <div class="tdgoal-edit-grid">
          <label><span>Meta de recuperação</span><div class="tdgoal-input money"><i>R$</i><input class="form-control" type="number" min="0" step="0.01" name="collection_goal" value="<?=e((string)($g['collection_goal']??0))?>"></div></label>
          <label><span>Meta de contatos</span><div class="tdgoal-input"><input class="form-control" type="number" min="0" step="1" name="contact_goal" value="<?=(int)($g['contact_goal']??0)?>"></div><small><?=number_format((int)($row['contacts']??0),0,',','.')?> realizados</small></label>
         </div>
         <button class="tdgoal-btn save" type="submit"><i class="fa-solid fa-check"></i>Salvar metas de <?=e(explode(' ',trim((string)$usr['name']))[0]??'responsável')?></button>
        </form>
       <?php endforeach;?>
      </div>
     <?php endif;?>
    </section>

    <?php if(!empty($management['virtual_sellers'])):?>
     <section class="tdgoal-team-section virtual">
      <header class="tdgoal-section-head">
       <div class="tdgoal-section-title">
        <span class="blue"><i class="fa-solid fa-robot"></i></span>
        <div><small>CANAIS AUTOMÁTICOS</small><strong>Vendedores virtuais</strong><p>Defina a meta comercial dos canais que entram automaticamente no resultado geral.</p></div>
       </div>
       <span class="tdgoal-count"><?=count($management['virtual_sellers'])?> canal(is)</span>
      </header>
      <div class="tdgoal-virtual-grid">
       <?php foreach($management['virtual_sellers'] as $vr):$vg=$vr['goal']??['sales_goal'=>0];$vpct=(float)($vr['sales_percent']??0);$vbar=min(100,max(0,$vpct));?>
        <form class="tdgoal-virtual-card" method="post" action="<?=APP_URL?>/goals/virtual/<?=e($vr['seller']['omie_code'])?>">
         <input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="month" value="<?=e($monthRef)?>">
         <div class="tdgoal-person-head"><span class="tdgoal-avatar blue"><i class="fa-solid <?=!empty($vr['ead_reciclagem'])?'fa-graduation-cap':'fa-robot'?>"></i></span><div><strong><?=e($vr['seller']['name'])?></strong><small><?=!empty($vr['ead_reciclagem'])?'Canal EAD':'Canal automático'?></small></div><b><?=number_format($vpct,1,',','.')?>%</b></div>
         <div class="tdgoal-performance"><div><small>Realizado</small><strong><?=money($vr['sales'])?></strong><span>Pedidos <?=money($vr['orders']??0)?> · Serviços <?=money($vr['services']??0)?></span></div></div>
         <div class="tdgoal-progress blue"><span style="width:<?=$vbar?>%"></span></div>
         <label class="tdgoal-virtual-input"><span>Meta comercial</span><div class="tdgoal-input money"><i>R$</i><input class="form-control" type="number" min="0" step="0.01" name="sales_goal" value="<?=e((string)($vg['sales_goal']??0))?>"></div></label>
         <button class="tdgoal-btn save" type="submit"><i class="fa-solid fa-check"></i>Salvar meta</button>
        </form>
       <?php endforeach;?>
      </div>
     </section>
    <?php endif;?>
   </section>
  <?php break;

  case 'admin_center':
   $s=$stats??[];$syncRows=$syncRows??[];$syncOk=0;$syncFail=0;foreach($syncRows as $syncRow){if(!empty($syncRow['last_error']))$syncFail++;elseif(!empty($syncRow['last_success_at']))$syncOk++;}
   $adminUser=Auth::user();$firstName=trim(explode(' ',trim((string)($adminUser['name']??'Administrador')))[0]??'Administrador');
   $syncTotal=count($syncRows);$syncHealthy=max(0,$syncTotal-$syncFail);
   ?>
   <section class="tdadmin2-page">
    <header class="tdadmin2-hero">
     <div class="tdadmin2-title">
      <span class="tdadmin2-kicker">TECNODATA EDUCACIONAL</span>
      <h1>Centro Administrativo</h1>
      <p>Visão completa da operação, gestão e sistema em um só lugar.</p>
      <nav class="tdadmin2-quick">
       <a class="green" href="<?=APP_URL?>/users"><i class="fa-solid fa-users"></i>Usuários<i class="fa-solid fa-chevron-right"></i></a>
       <a class="blue" href="<?=APP_URL?>/sales-flow-settings"><i class="fa-solid fa-diagram-project"></i>Fluxo comercial<i class="fa-solid fa-chevron-right"></i></a>
       <a class="orange" href="<?=APP_URL?>/sync"><i class="fa-solid fa-arrows-rotate"></i>Sincronização<i class="fa-solid fa-chevron-right"></i></a>
       <a class="neutral" href="<?=APP_URL?>/settings"><i class="fa-solid fa-gear"></i>Configurações</a>
      </nav>
     </div>
     <div class="tdadmin2-welcome">
      <small><?=date('d/m/Y')?></small>
      <strong>Olá, <?=e($firstName)?>!</strong>
      <span>Aqui você gerencia o que faz a Tecnodata ir mais longe.</span>
     </div>
     <div class="tdadmin2-brand-card"><i class="fa-solid fa-leaf"></i><div><span>Mais gestão.</span><span>Mais educação.</span><strong>Mais impacto.</strong></div></div>
    </header>

    <div class="tdadmin2-kpis">
     <article><span class="green"><i class="fa-solid fa-users"></i></span><div><small>Usuários ativos</small><strong><?=number_format((int)($s['active_users']??0),0,',','.')?></strong><em><?=number_format((int)($s['users']??0),0,',','.')?> cadastrados</em></div></article>
     <article><span class="blue"><i class="fa-solid fa-sack-dollar"></i></span><div><small>Pipeline em negociação</small><strong><?=money($s['pipeline_value']??0)?></strong><em><?=number_format((int)($s['open_opportunities']??0),0,',','.')?> oportunidades abertas</em></div></article>
     <article><span class="yellow"><i class="fa-regular fa-calendar-check"></i></span><div><small>Agenda da equipe</small><strong><?=number_format((int)($s['today_tasks']??0),0,',','.')?></strong><em><?=number_format((int)($s['overdue_tasks']??0),0,',','.')?> atrasadas</em></div></article>
     <article><span class="red"><i class="fa-solid fa-circle-dollar-to-slot"></i></span><div><small>Cobrança em aberto</small><strong><?=money($s['open_collection']??0)?></strong><em><?=number_format((int)($s['collectors']??0),0,',','.')?> usuários de cobrança</em></div></article>
    </div>

    <div class="tdadmin2-main">
     <div class="tdadmin2-left">
      <section class="tdadmin2-section">
       <header><div><span class="lime"><i class="fa-solid fa-chart-line"></i></span><strong>Operação</strong><small>Acompanhe e gerencie o dia a dia da operação comercial.</small></div></header>
       <div class="tdadmin2-modules">
        <a href="<?=sales_flow_enabled()?APP_URL.'/opportunities':APP_URL.'/sales-flow-settings'?>"><span class="green"><i class="fa-solid fa-arrow-trend-up"></i></span><div><strong>Comercial</strong><small>Gerencie oportunidades, funil e conversões.</small><em><?=sales_flow_enabled()?number_format((int)($s['open_opportunities']??0),0,',','.').' oportunidades ativas':'Fluxo opcional desativado'?></em></div><i class="fa-solid fa-chevron-right"></i></a>
        <a href="<?=APP_URL?>/collection"><span class="blue"><i class="fa-solid fa-wallet"></i></span><div><strong>Cobrança</strong><small>Acompanhe recebimentos, inadimplência e status.</small><em><?=money($s['open_collection']??0)?> em aberto</em></div><i class="fa-solid fa-chevron-right"></i></a>
        <a href="<?=APP_URL?>/agenda"><span class="cyan"><i class="fa-regular fa-calendar-days"></i></span><div><strong>Agenda da equipe</strong><small>Visualize compromissos e distribua atendimentos.</small><em><?=number_format((int)($s['today_tasks']??0),0,',','.')?> compromissos hoje</em></div><i class="fa-solid fa-chevron-right"></i></a>
       </div>
      </section>

      <section class="tdadmin2-section">
       <header><div><span class="lime"><i class="fa-solid fa-chart-simple"></i></span><strong>Gestão</strong><small>Dados e pessoas para decisões mais estratégicas.</small></div></header>
       <div class="tdadmin2-modules">
        <a href="<?=APP_URL?>/goals"><span class="cyan"><i class="fa-solid fa-bullseye"></i></span><div><strong>Metas</strong><small>Acompanhe desempenho e resultados da equipe.</small><em>Gestão mensal</em></div><i class="fa-solid fa-chevron-right"></i></a>
        <a href="<?=APP_URL?>/settings"><span class="blue"><i class="fa-solid fa-chart-line"></i></span><div><strong>Acompanhamento</strong><small>Indicadores, regras e evolução da operação.</small><em><?=number_format((int)($s['monitored']??0),0,',','.')?> participantes</em></div><i class="fa-solid fa-chevron-right"></i></a>
        <a href="<?=APP_URL?>/users"><span class="blue"><i class="fa-solid fa-users-gear"></i></span><div><strong>Usuários e acessos</strong><small>Gerencie perfis, permissões e atividade da equipe.</small><em><?=number_format((int)($s['active_users']??0),0,',','.')?> usuários ativos</em></div><i class="fa-solid fa-chevron-right"></i></a>
       </div>
      </section>

      <section class="tdadmin2-section">
       <header><div><span class="lime"><i class="fa-solid fa-gear"></i></span><strong>Sistema</strong><small>Mantenha tudo integrado e funcionando perfeitamente.</small></div></header>
       <div class="tdadmin2-modules">
        <a href="<?=APP_URL?>/sync"><span class="blue"><i class="fa-solid fa-arrows-rotate"></i></span><div><strong>Sincronização Omie</strong><small>Integração de dados financeiros e comerciais.</small><em><?=$syncFail>0?$syncFail.' módulo(s) com atenção':'Sincronizações operacionais'?></em></div><i class="fa-solid fa-chevron-right"></i></a>
        <a href="<?=APP_URL?>/settings"><span class="cyan"><i class="fa-solid fa-gears"></i></span><div><strong>Configurações gerais</strong><small>Ajustes do sistema, parâmetros e preferências.</small><em><?=number_format((int)($taskResultCount??0),0,',','.')?> resultados configurados</em></div><i class="fa-solid fa-chevron-right"></i></a>
        <a href="<?=APP_URL?>/test-data"><span class="blue"><i class="fa-solid fa-flask"></i></span><div><strong>Ferramentas de teste</strong><small>Ambiente de validação e testes de integrações.</small><em>Área técnica</em></div><i class="fa-solid fa-chevron-right"></i></a>
       </div>
      </section>
     </div>

     <aside class="tdadmin2-right">
      <section class="tdadmin2-health">
       <header><div><i class="fa-solid fa-share-nodes"></i><span><strong>Saúde das integrações</strong><small>Status em tempo real dos principais serviços.</small></span></div></header>
       <div class="tdadmin2-health-list">
        <?php foreach(array_slice($syncRows,0,6) as $row):$hasError=!empty($row['last_error']);?>
         <div><span class="<?=$hasError?'error':'ok'?>"></span><strong><?=e($row['module_key'])?></strong><em class="<?=$hasError?'error':'ok'?>"><?=$hasError?'Atenção':'Operacional'?></em><small><?=!empty($row['last_success_at'])?'Há '.max(1,(int)round((time()-strtotime($row['last_success_at']))/60)).' min':'—'?></small></div>
        <?php endforeach;?>
        <?php if(empty($syncRows)):?><div class="tdadmin2-health-empty">Nenhuma integração registrada.</div><?php endif;?>
       </div>
       <a class="tdadmin2-health-action" href="<?=APP_URL?>/sync">Ver detalhes das integrações <i class="fa-solid fa-arrow-right"></i></a>
      </section>

      <section class="tdadmin2-impact">
       <div><i class="fa-solid fa-trophy"></i><span>Tecnologia que aproxima pessoas e transforma a educação.</span><strong>Grupo Tecnodata</strong></div>
       <span class="tdadmin2-impact-art"><i class="fa-solid fa-building"></i></span>
      </section>
     </aside>
    </div>
   </section>
  <?php break;
  case 'settings':
   $activeTaskResults=count(array_filter($taskResults??[],static fn($item)=>!empty($item['active'])));
   $flowActive=sales_flow_enabled();
   ?>
   <section class="tdcfg-page">
    <header class="tdcfg-head">
     <div class="tdcfg-title"><span class="tdcfg-title-icon"><i class="fa-solid fa-gears"></i></span><div><span class="tdcfg-kicker">SISTEMA / CONFIGURAÇÕES</span><h1>Configurações</h1><p>Gerencie regras, preferências e integrações do CRM em um só lugar.</p></div></div>
     <div class="tdcfg-health"><i class="fa-solid fa-circle-check"></i><div><strong>Sistema operacional</strong><small>As configurações essenciais estão disponíveis.</small></div></div>
    </header>

    <?php if(!empty($flash)):?><div class="alert alert-<?=e((string)($flash['type']??'info'))?>"><?=e((string)($flash['message']??''))?></div><?php endif;?>

    <nav class="tdcfg-categories" aria-label="Áreas de configuração">
     <a class="active" href="#geral"><span class="blue"><i class="fa-solid fa-sliders"></i></span><strong>Geral</strong><small>Preferências operacionais</small></a>
     <a href="<?=APP_URL?>/sales-flow-settings"><span class="green"><i class="fa-solid fa-filter-circle-dollar"></i></span><strong>Fluxo comercial</strong><small>Funil e oportunidades</small></a>
     <a href="#tarefas"><span class="red"><i class="fa-regular fa-calendar-check"></i></span><strong>Agenda e tarefas</strong><small>Resultados e rotinas</small></a>
     <a href="<?=APP_URL?>/sync"><span class="blue"><i class="fa-solid fa-link"></i></span><strong>Integrações</strong><small>Omie e sincronização</small></a>
     <a href="#acompanhamento"><span class="yellow"><i class="fa-solid fa-bell"></i></span><strong>Acompanhamento</strong><small>Usuários monitorados</small></a>
     <?php if($settingsAdmin):?><a href="<?=APP_URL?>/users"><span class="orange"><i class="fa-solid fa-shield-halved"></i></span><strong>Segurança</strong><small>Usuários e acessos</small></a><?php endif;?>
    </nav>

    <div class="tdcfg-layout">
     <div class="tdcfg-main">
      <section class="tdcfg-card" id="acompanhamento">
       <header><span class="blue"><i class="fa-solid fa-headset"></i></span><div><strong>Participantes do acompanhamento</strong><small>Escolha quem deve aparecer nos indicadores de acompanhamento comercial e cobrança.</small></div><b class="tdcfg-state <?=$monitorConfigured?'ok':'neutral'?>"><?=$monitorConfigured?'Regra ativa':'Padrão automático'?></b></header>
       <form method="post" action="<?=APP_URL?>/settings/contact-monitoring" class="tdcfg-monitor"><input type="hidden" name="_token" value="<?=CSRF::token()?>">
        <div class="tdcfg-info"><i class="fa-solid fa-circle-info"></i><span><?=$monitorConfigured?'Somente os participantes selecionados entram nessa visão.':'Sem regra específica: o CRM considera os usuários operacionais ativos.'?></span></div>
        <div class="tdcfg-users"><?php foreach($monitorUsers??[] as $monitorUser):?><label><input type="checkbox" name="monitor_user_ids[]" value="<?=(int)$monitorUser['id']?>" <?=in_array((int)$monitorUser['id'],$monitorIds??[],true)?'checked':''?>><span class="avatar <?=$monitorUser['role']==='collector'?'collection':''?>"><?=e(mb_strtoupper(mb_substr((string)$monitorUser['name'],0,1)))?></span><span><strong><?=e($monitorUser['name'])?></strong><small><?=$monitorUser['role']==='collector'?'Cobrança':'Vendas'?> · <?=e($monitorUser['email'])?></small></span><i class="fa-solid fa-check"></i></label><?php endforeach;?><?php if(empty($monitorUsers)):?><div class="tdcfg-empty">Nenhum usuário operacional disponível.</div><?php endif;?></div>
        <?php if(!empty($monitorUsers)):?><footer><span>Selecionados: <b data-monitor-selected><?=count($monitorIds??[])?></b></span><button class="tdcfg-btn primary" type="submit"><i class="fa-solid fa-check"></i>Salvar acompanhamento</button></footer><?php endif;?>
       </form>
      </section>

      <section class="tdcfg-card" id="tarefas">
       <header><span class="red"><i class="fa-solid fa-list-check"></i></span><div><strong>Resultados de tarefas e atendimentos</strong><small>Personalize os resultados usados no Comercial e na Cobrança.</small></div><b class="tdcfg-state ok"><?=$activeTaskResults?> ativos</b></header>
       <form method="post" action="<?=APP_URL?>/settings/task-results" class="tdcfg-result-create">
        <input type="hidden" name="_token" value="<?=CSRF::token()?>">
        <label><span>Nome do resultado</span><input class="form-control" name="label" maxlength="80" placeholder="Ex.: Retornar na próxima semana" required></label>
        <fieldset><legend>Disponível em</legend><label><input type="checkbox" name="contexts[]" value="sales" checked> Comercial</label><label><input type="checkbox" name="contexts[]" value="collection" checked> Cobrança</label></fieldset>
        <button class="tdcfg-btn primary" type="submit"><i class="fa-solid fa-plus"></i>Adicionar</button>
       </form>
       <div class="tdcfg-result-list"><?php foreach($taskResults??[] as $resultItem):?><div class="<?=!empty($resultItem['active'])?'active':'inactive'?>"><span><strong><?=e($resultItem['label'])?></strong><small><?=in_array('sales',(array)$resultItem['contexts'],true)?'Comercial':''?><?=in_array('sales',(array)$resultItem['contexts'],true)&&in_array('collection',(array)$resultItem['contexts'],true)?' · ':''?><?=in_array('collection',(array)$resultItem['contexts'],true)?'Cobrança':''?><?=!empty($resultItem['system'])?' · padrão do sistema':''?></small></span><form method="post" action="<?=APP_URL?>/settings/task-results/<?=e($resultItem['code'])?>/toggle"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button type="submit" class="tdcfg-toggle <?=!empty($resultItem['active'])?'on':'off'?>"><i class="fa-solid <?=!empty($resultItem['active'])?'fa-toggle-on':'fa-toggle-off'?>"></i><?=!empty($resultItem['active'])?'Ativo':'Inativo'?></button></form></div><?php endforeach;?></div>
      </section>

      <?php if($settingsAdmin):?>
      <form method="post" action="<?=APP_URL?>/settings" class="tdcfg-admin-form"><input type="hidden" name="_token" value="<?=CSRF::token()?>">
       <section class="tdcfg-card" id="geral">
        <header><span class="blue"><i class="fa-solid fa-receipt"></i></span><div><strong>Padrões operacionais do pedido</strong><small>Valores iniciais usados na criação de pedidos. O vendedor ainda pode ajustar durante o atendimento.</small></div></header>
        <div class="tdcfg-fields"><?php $fields=[['stage','Etapa',$stages,'code','name'],['category','Categoria',$categories,'code','description'],['account','Conta corrente',$accounts,'omie_code','name'],['payment_term','Condição de pagamento',$terms,'code','description'],['payment_method','Meio de pagamento',$methods,'code','description'],['document_type','Tipo documento',$documents,'code','description'],['tax_scenario','Cenário fiscal',$taxes,'omie_code','name'],['stock_location','Local estoque',$stocks,'omie_code','name']];foreach($fields as [$key,$label,$list,$vk,$lk]):?><label><span><?=$label?></span><select class="form-select" name="<?=$key?>"><option value="">Selecione</option><?php foreach($list as $row):?><option value="<?=e($row[$vk])?>" <?=($defaults[$key]??'')===(string)$row[$vk]?'selected':''?>><?=e($row[$lk])?></option><?php endforeach;?></select></label><?php endforeach;?>
         <label><span>Frete padrão</span><select class="form-select" name="freight_mode" data-freight-default-autosave><?php foreach(['9'=>'Sem frete','0'=>'CIF','1'=>'FOB','2'=>'Terceiros','3'=>'Próprio remetente','4'=>'Próprio destinatário'] as $k=>$vv):?><option value="<?=$k?>" <?=($defaults['freight_mode']??'9')===$k?'selected':''?>><?=$vv?></option><?php endforeach;?></select><small data-freight-default-status>Salvamento automático</small></label>
         <label><span>Consumidor final</span><select class="form-select" name="consumer_final"><option value="S">Sim</option><option value="N" <?=($defaults['consumer_final']??'S')==='N'?'selected':''?>>Não</option></select></label>
         <label class="tdcfg-check"><input type="checkbox" name="send_email" value="1" <?=($defaults['send_email']??'N')==='S'?'checked':''?>><span><strong>Enviar e-mail pela Omie</strong><small>Permite o disparo conforme o fluxo do pedido.</small></span></label>
        </div>
       </section>

       <div class="tdcfg-two">
        <section class="tdcfg-card">
         <header><span class="blue"><i class="fa-solid fa-truck-fast"></i></span><div><strong>Transportadoras</strong><small>Defina quais transportadoras estarão disponíveis no pedido.</small></div></header>
         <?php if(!empty($carriers)):?><div class="tdcfg-options"><?php foreach($carriers as $carrier):?><label><input type="checkbox" name="carrier_codes[]" value="<?=e((string)$carrier['omie_code'])?>" <?=!empty($carrier['selected'])?'checked':''?>><span><strong><?=e((string)$carrier['name'])?></strong><small><?=!empty($carrier['city'])?e((string)$carrier['city']).(!empty($carrier['uf'])?' / '.e((string)$carrier['uf']):''):'Omie '.e((string)$carrier['omie_code'])?></small></span></label><?php endforeach;?></div><?php else:?><div class="tdcfg-empty">Nenhuma transportadora encontrada. Sincronize os clientes após configurar a tag na Omie.</div><?php endif;?>
        </section>
        <section class="tdcfg-card">
         <header><span class="green"><i class="fa-solid fa-building-columns"></i></span><div><strong>Contas da cobrança</strong><small>Escolha as contas financeiras que entram na operação de cobrança.</small></div></header>
         <div class="tdcfg-options"><?php foreach($accounts as $row):?><label><input type="checkbox" name="collection_accounts[]" value="<?=e($row['omie_code'])?>" <?=$row['selected']?'checked':''?>><span><strong><?=e($row['name'])?></strong><small><?=e($row['omie_code'])?></small></span></label><?php endforeach;?></div>
        </section>
       </div>
       <div class="tdcfg-save"><span><i class="fa-solid fa-lightbulb"></i>Revise os padrões antes de salvar. Eles serão usados como ponto de partida nos novos pedidos.</span><button class="tdcfg-btn primary"><i class="fa-solid fa-floppy-disk"></i>Salvar configurações</button></div>
      </form>

      <section class="tdcfg-card">
       <header><span class="orange"><i class="fa-solid fa-layer-group"></i></span><div><strong>Perfis operacionais de pedido</strong><small>Crie comportamentos específicos para estoque, financeiro e NF-e sem alterar o fluxo padrão.</small></div></header>
       <div class="tdcfg-profiles"><?php foreach($profiles as $profile):?><article><div><strong><?=e($profile['name'])?></strong><small><?=e($profile['description']??'')?></small></div><div><?php if($profile['default_no_stock']==='S'):?><b>Sem estoque</b><?php endif;?><?php if($profile['default_no_finance']==='S'):?><b>Sem financeiro</b><?php endif;?><?php if($profile['default_no_total']==='S'):?><b>Fora total NF-e</b><?php endif;?><?php if($profile['default_reserve_stock']==='S'):?><b>Reserva</b><?php endif;?></div></article><?php endforeach;?></div>
       <form class="tdcfg-profile-form" method="post" action="<?=APP_URL?>/settings/order-profile"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><label>Código<input class="form-control" name="code" placeholder="EX: VENDA_ESPECIAL" required></label><label>Nome<input class="form-control" name="name" required></label><label class="wide">Descrição<input class="form-control" name="description"></label><label class="tdcfg-check compact"><input type="checkbox" name="default_no_stock" value="1"><span>Não movimentar estoque</span></label><label class="tdcfg-check compact"><input type="checkbox" name="default_no_finance" value="1"><span>Não gerar financeiro</span></label><label class="tdcfg-check compact"><input type="checkbox" name="default_no_total" value="1"><span>Não somar na NF-e</span></label><label class="tdcfg-check compact"><input type="checkbox" name="default_reserve_stock" value="1"><span>Reservar estoque</span></label><input type="hidden" name="active" value="1"><button class="tdcfg-btn"><i class="fa-solid fa-plus"></i>Criar perfil</button></form>
      </section>
      <?php endif;?>
     </div>

     <aside class="tdcfg-side">
      <section class="tdcfg-side-card">
       <header><span class="green"><i class="fa-solid fa-diagram-project"></i></span><div><strong>Fluxo comercial</strong><small>Oportunidades e funil</small></div></header>
       <div class="tdcfg-big-state <?=$flowActive?'ok':'off'?>"><i class="fa-solid <?=$flowActive?'fa-circle-check':'fa-circle-pause'?>"></i><div><strong><?=$flowActive?'Ativado':'Desativado'?></strong><span><?=$flowActive?'O módulo está disponível para a equipe comercial.':'O CRM segue no fluxo tradicional atual.'?></span></div></div>
       <a class="tdcfg-link" href="<?=APP_URL?>/sales-flow-settings">Configurar fluxo <i class="fa-solid fa-arrow-right"></i></a>
      </section>

      <section class="tdcfg-side-card">
       <header><span class="blue"><i class="fa-solid fa-plug"></i></span><div><strong>Integrações</strong><small>Dados e sincronização</small></div></header>
       <div class="tdcfg-integration"><i class="fa-solid fa-arrows-rotate"></i><div><strong>Omie</strong><span>Clientes, pedidos, serviços e financeiro.</span></div><b>Integrado</b></div>
       <a class="tdcfg-link" href="<?=APP_URL?>/sync">Gerenciar sincronização <i class="fa-solid fa-arrow-right"></i></a>
      </section>

      <?php if($settingsAdmin):?>
      <section class="tdcfg-side-card">
       <header><span class="orange"><i class="fa-solid fa-shield-halved"></i></span><div><strong>Usuários e segurança</strong><small>Perfis e permissões</small></div></header>
       <p>Gerencie administradores, supervisores, vendedores e cobrança em uma tela própria de acessos.</p>
       <a class="tdcfg-link" href="<?=APP_URL?>/users">Gerenciar acessos <i class="fa-solid fa-arrow-right"></i></a>
      </section>
      <?php endif;?>

      <section class="tdcfg-tip"><i class="fa-solid fa-lightbulb"></i><div><strong>Dica</strong><span>Mantenha as configurações simples. Ative somente os recursos que sua operação realmente utiliza.</span></div></section>
     </aside>
    </div>
   </section>
  <?php break;
  case 'test_data':?>
   <section class="tdtest-page">
    <header class="tdtest-head"><div class="tdtest-head-main"><span class="tdtest-head-icon"><i class="fa-solid fa-flask"></i></span><div><span class="tdtest-kicker">SISTEMA / TESTE CONTROLADO</span><h1>Carga mínima da Omie</h1><p>Valide o fluxo com apenas 1 cliente e até 2 produtos, sem carregar toda a base.</p></div></div><a class="tdtest-btn" href="<?=APP_URL?>/orders/new"><i class="fa-solid fa-cart-plus"></i>Ir para novo pedido</a></header>
    <?php if($flash):?><div class="alert alert-<?=e($flash['type']??'info')?>"><?=e($flash['message']??'')?></div><?php endif;?>
    <div class="tdtest-grid">
     <section class="tdtest-card"><div class="tdtest-card-head"><span>1</span><div><strong>Cliente e produtos</strong><small>Consulta direta usando os códigos internos da Omie.</small></div></div><form class="tdtest-form" method="post" action="<?=APP_URL?>/test-data/import"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><label>Código Omie do cliente<input class="form-control" name="client_omie_code" inputmode="numeric" required></label><div class="tdtest-mini"><label>Código do produto 1<input class="form-control" name="product_1_omie_code" inputmode="numeric" required></label><label>Código do produto 2<input class="form-control" name="product_2_omie_code" inputmode="numeric"></label></div><button class="tdtest-btn tdtest-btn-primary"><i class="fa-solid fa-download"></i>Importar estes registros</button></form><?php if(!empty($flash['client'])):?><div class="tdtest-result"><strong>Cliente importado</strong><span><?=e($flash['client']['name']??'')?></span></div><?php endif;?><?php if(!empty($flash['products'])):foreach($flash['products'] as $p):?><div class="tdtest-result"><strong>Produto importado</strong><span><?=e($p['description']??'')?> · <?=money($p['unit_price']??0)?></span></div><?php endforeach;endif;?></section>
     <section class="tdtest-card"><div class="tdtest-card-head"><span>2</span><div><strong>Parâmetros auxiliares</strong><small>Prepare vendedores, categorias, contas, etapas, condições e demais referências.</small></div></div><div class="tdtest-snapshot"><article><span>Clientes</span><strong><?=$snapshot['clients']?></strong></article><article><span>Produtos</span><strong><?=$snapshot['products']?></strong></article><article><span>Vendedores</span><strong><?=$snapshot['sellers']?></strong></article><article><span>Condições</span><strong><?=$snapshot['terms']?></strong></article></div><form class="tdtest-form" method="post" action="<?=APP_URL?>/test-data/references"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="tdtest-btn"><i class="fa-solid fa-gears"></i>Preparar parâmetros do pedido</button></form><?php if(!empty($flash['references'])):?><div class="tdtest-refs"><?php foreach($flash['references'] as $k=>$vv):?><div><span><?=e($k)?></span><strong><?=(int)$vv?></strong></div><?php endforeach;?></div><?php endif;?></section>
    </div>
    <section class="tdtest-flow"><span><i class="fa-solid fa-route"></i></span><div><strong>Fluxo recomendado</strong><p>1 cliente + 2 produtos → parâmetros auxiliares → Configurações → Novo pedido → Enviar e integrar na Omie.</p></div></section>
   </section>
  <?php break;
  case 'sync':
   $summary=$sync['summary'];$items=$sync['items'];
   $icons=['sellers'=>'fa-user-tie','clients'=>'fa-users','products'=>'fa-boxes-stacked','categories'=>'fa-tags','departments'=>'fa-sitemap','accounts'=>'fa-building-columns','stages'=>'fa-layer-group','payment_terms'=>'fa-calendar-check','tax_scenarios'=>'fa-file-invoice-dollar','stock_locations'=>'fa-warehouse','payment_methods'=>'fa-credit-card','document_types'=>'fa-file-lines','orders'=>'fa-receipt','services'=>'fa-screwdriver-wrench','financial'=>'fa-hand-holding-dollar'];
   $syncGroups=[
    'base'=>['label'=>'Cadastros base','description'=>'Dados essenciais usados no CRM comercial.','icon'=>'fa-database','keys'=>['sellers','clients','products']],
    'references'=>['label'=>'Referências Omie','description'=>'Cadastros auxiliares usados em pedidos, serviços e financeiro.','icon'=>'fa-diagram-project','keys'=>['categories','departments','accounts','stages','payment_terms','tax_scenarios','stock_locations','payment_methods','document_types']],
    'movement'=>['label'=>'Movimentação','description'=>'Dados operacionais que mudam diariamente e exigem acompanhamento mais próximo.','icon'=>'fa-arrows-rotate','keys'=>['orders','services','financial']]
   ];
   $modeLabels=['manual_period'=>'Período escolhido','forced_last_5_days'=>'Últimos 5 dias','incremental_5_days'=>'Últimos 5 dias','catchup_missing_period'=>'Atualizar lacuna','initial_current_year'=>'Carga inicial','manual_full_current_year'=>'Carga completa','products_full'=>'Catálogo completo','products_incremental'=>'Novos e alterados','clients_reconcile'=>'Reconciliação completa','initial'=>'Carga inicial','incremental'=>'Incremental'];
   $lastSuccessLabel=$summary['last_success']?date('d/m/Y H:i',strtotime($summary['last_success'])):'Nenhuma execução concluída';
   ?>
   <section class="tdsync2-page">
    <header class="tdsync2-head">
     <div class="tdsync2-head-main">
      <span class="tdsync2-head-icon"><i class="fa-solid fa-arrows-rotate"></i></span>
      <div><span class="tdsync2-kicker">SISTEMA / OMIE</span><h1>Sincronização Omie</h1><p>Gerencie os dados trazidos da Omie com leitura clara de status, volume local e última execução de cada módulo.</p></div>
     </div>
     <div class="tdsync2-head-status <?=$summary['errors']>0?'attention':'ok'?>">
      <span><i class="fa-solid <?=$summary['errors']>0?'fa-triangle-exclamation':'fa-circle-check'?>"></i></span>
      <div><small>Saúde da integração</small><strong><?=$summary['errors']>0?(int)$summary['errors'].' módulo(s) com atenção':'Sem erros registrados'?></strong><em><?=$lastSuccessLabel?></em></div>
     </div>
    </header>

    <div class="tdsync2-kpis">
     <article><span class="blue"><i class="fa-solid fa-puzzle-piece"></i></span><div><small>Módulos configurados</small><strong><?=(int)$summary['modules']?></strong><em>integrações disponíveis</em></div></article>
     <article><span class="green"><i class="fa-solid fa-circle-check"></i></span><div><small>Com execução concluída</small><strong><?=(int)$summary['synced']?></strong><em>módulos já processados</em></div></article>
     <article class="<?=$summary['errors']>0?'has-alert':''?>"><span class="red"><i class="fa-solid fa-triangle-exclamation"></i></span><div><small>Com erro registrado</small><strong><?=(int)$summary['errors']?></strong><em><?=$summary['errors']>0?'requerem revisão':'nenhuma pendência técnica'?></em></div></article>
     <article><span class="yellow"><i class="fa-solid fa-database"></i></span><div><small>Registros locais</small><strong><?=number_format((int)$summary['local_total'],0,',','.')?></strong><em>soma das bases sincronizadas</em></div></article>
    </div>

    <div class="tdsync2-guidance">
     <div><span><i class="fa-solid fa-shield-halved"></i></span><div><strong>Sincronize sem apagar o histórico local.</strong><small>Use “Zerar” apenas quando realmente precisar excluir todos os dados locais daquele módulo e recomeçar.</small></div></div>
     <div class="tdsync2-legend"><span><i class="ok"></i>Sincronizado</span><span><i class="idle"></i>Aguardando</span><span><i class="error"></i>Erro</span></div>
    </div>

    <?php foreach($syncGroups as $groupKey=>$group):?>
     <section class="tdsync2-section <?=$groupKey==='movement'?'movement':''?>">
      <header class="tdsync2-section-head">
       <div><span><i class="fa-solid <?=$group['icon']?>"></i></span><div><small><?=strtoupper($groupKey==='base'?'BASE LOCAL':($groupKey==='references'?'ESTRUTURA OMIE':'OPERAÇÃO'))?></small><strong><?=$group['label']?></strong><p><?=$group['description']?></p></div></div>
      </header>

      <div class="tdsync2-grid <?=$groupKey==='movement'?'featured':''?>">
       <?php foreach($group['keys'] as $key):if(empty($items[$key]))continue;$item=$items[$key];
        $state=$item['state'];$hasError=$item['has_error'];$lastPage=(int)($state['last_page']??0);$totalPages=(int)($state['total_pages']??0);
        $lastSuccess=$state['last_success_at']??null;$lastError=(string)($state['last_error']??'');
        $statusClass=$hasError?'error':($lastSuccess?'success':'idle');
        $statusLabel=$hasError?'Erro':($lastSuccess?'Sincronizado':'Aguardando');
        $modeLabel=$modeLabels[$item['mode']]??ucfirst(str_replace('_',' ',$item['mode']));
       ?>
        <article class="tdsync2-card sync-module-card <?=$groupKey==='movement'?'featured':''?>" data-sync-card="<?=$key?>">
         <header class="tdsync2-card-head">
          <div class="tdsync2-card-title">
           <span><i class="fa-solid <?=e($icons[$key]??'fa-arrows-rotate')?>"></i></span>
           <div><small><?=e(strtoupper($key))?></small><strong><?=e($item['label'])?></strong></div>
          </div>
          <span class="sync-status sync-status-<?=$statusClass?>" data-sync-badge><i class="fa-solid <?=$hasError?'fa-triangle-exclamation':($lastSuccess?'fa-check':'fa-minus')?>"></i><?=$statusLabel?></span>
         </header>

         <div class="tdsync2-card-stats">
          <div><small>Registros locais</small><strong><?=number_format((int)$item['local_count'],0,',','.')?></strong></div>
          <div><small>Última página</small><strong><?=$lastPage?:'—'?><?=$totalPages?' / '.$totalPages:''?></strong></div>
          <div><small>Último lote</small><strong><?=isset($state['last_count'])?(int)$state['last_count']:'—'?></strong></div>
         </div>

         <div class="tdsync2-card-info">
          <div><i class="fa-regular fa-clock"></i><span><small>Último sucesso</small><strong><?=$lastSuccess?date('d/m/Y H:i',strtotime($lastSuccess)):'Nunca executado'?></strong></span></div>
          <div><i class="fa-solid fa-code-branch"></i><span><small>Modo atual</small><strong><?=e($modeLabel)?></strong></span></div>
          <?php if(!empty($item['period_start'])&&!empty($item['period_end'])):?><div class="wide"><i class="fa-regular fa-calendar"></i><span><small>Janela atual</small><strong><?=e($item['period_start'])?> até <?=e($item['period_end'])?></strong></span></div><?php endif;?>
         </div>

         <div class="sync-flow-alert tdsync2-alert sync-flow-alert-<?=$hasError?'error':($lastSuccess?'success':'info')?>" data-sync-alert>
          <i class="fa-solid <?=$hasError?'fa-circle-exclamation':($lastSuccess?'fa-circle-check':'fa-circle-info')?>"></i>
          <div><strong data-sync-alert-title><?=$hasError?'Última execução com erro':($lastSuccess?'Última execução concluída':'Pronto para sincronizar')?></strong><span data-sync-alert-message><?=e($hasError?$lastError:($lastSuccess?'O módulo está atualizado conforme a última execução concluída.':'Nenhuma execução registrada ainda.'))?></span></div>
         </div>

         <div class="sync-progress-wrap tdsync2-progress" data-sync-progress-wrap hidden>
          <div class="sync-progress-copy"><span data-sync-progress-label>Preparando...</span><strong data-sync-progress-percent>0%</strong></div>
          <div class="sync-progress"><span data-sync-progress-bar style="width:0%"></span></div>
         </div>

         <?php if(in_array($key,['orders','services'],true)):?>
          <div class="tdsync2-period">
           <div class="tdsync2-period-copy"><span><i class="fa-regular fa-calendar-days"></i></span><div><strong>Sincronizar um período específico</strong><small>Escolha a janela exata que deseja consultar na Omie.</small></div></div>
           <div class="tdsync2-period-fields">
            <label><span>Data inicial</span><input class="form-control" type="date" value="<?=date('Y-m-01')?>" data-sync-date-from></label>
            <label><span>Data final</span><input class="form-control" type="date" value="<?=date('Y-m-d')?>" data-sync-date-to></label>
           </div>
           <button class="tdsync2-btn primary" type="button" data-sync-action="period" data-module="<?=$key?>"><i class="fa-solid fa-calendar-check"></i>Sincronizar período</button>
          </div>
         <?php endif;?>

         <footer class="tdsync2-actions sync-module-actions">
          <div class="tdsync2-actions-main">
           <?php if(in_array($key,['orders','services'],true)):?>
            <button class="tdsync2-btn primary" data-sync-action="catchup" data-module="<?=$key?>" data-sync-confirm="Atualizar todo o intervalo faltante de <?=e($item['label'])?> desde a última data local até hoje? Os registros existentes serão preservados."><i class="fa-solid fa-forward-step"></i>Atualizar lacuna</button>
            <button class="tdsync2-btn" data-sync-action="last5" data-module="<?=$key?>"><i class="fa-regular fa-calendar-days"></i>Últimos 5 dias</button>
            <button class="tdsync2-btn" data-sync-action="full" data-module="<?=$key?>" data-sync-confirm="Executar carga completa do ano corrente para <?=e($item['label'])?>?"><i class="fa-solid fa-layer-group"></i>Carga completa</button>
           <?php else:?>
            <button class="tdsync2-btn primary" data-sync-action="sync" data-module="<?=$key?>"><i class="fa-solid fa-arrows-rotate"></i><?=in_array($key,['products','clients'],true)?($lastSuccess||(int)$item['local_count']>0?'Buscar novos e alterados':'Carregar '.($key==='products'?'catálogo':'clientes')):'Sincronizar agora'?></button>
            <?php if($key==='clients'):?><button class="tdsync2-btn" data-sync-action="reconcile_clients" data-module="clients" data-sync-confirm="Executar a reconciliação completa? Clientes ativos serão inseridos ou atualizados pelo código Omie. Cadastros que já chegam inativos serão ignorados; se um cliente existente tiver sido inativado no Omie, ficará apenas arquivado no CRM. Nenhum histórico será excluído."><i class="fa-solid fa-shield-halved"></i>Reconciliar base completa</button><?php endif;?>
            <?php if($key==='products'):?><button class="tdsync2-btn" data-sync-action="product_obs" data-module="products" data-sync-confirm="Atualizar uma vez todo o catálogo para carregar a Descrição Detalhada e as Observações Internas da Omie? A carga é paginada, preserva os produtos locais e pode ser retomada."><i class="fa-regular fa-note-sticky"></i>Carregar descrições e observações</button><?php endif;?>
           <?php endif;?>
           <button class="tdsync2-btn" data-sync-action="resume" data-module="<?=$key?>" <?=$item['resumable']?'':'disabled'?>><i class="fa-solid fa-play"></i>Retomar</button>
          </div>
          <button class="tdsync2-btn danger" data-sync-action="reset" data-module="<?=$key?>" data-sync-confirm="Zerar <?=e($item['label'])?>? TODOS os dados locais deste módulo serão excluídos. Depois você escolherá manualmente uma nova sincronização."><i class="fa-solid fa-trash-can"></i>Zerar dados locais</button>
         </footer>
        </article>
       <?php endforeach;?>
      </div>
     </section>
    <?php endforeach;?>

    <section class="tdsync2-help">
     <header><div><span><i class="fa-solid fa-circle-info"></i></span><div><small>REFERÊNCIA RÁPIDA</small><strong>Como usar as ações</strong><p>As operações foram separadas para reduzir risco e facilitar a recuperação quando uma carga é interrompida.</p></div></div></header>
     <div class="tdsync2-help-grid">
      <div><span class="green"><i class="fa-solid fa-arrows-rotate"></i></span><strong>Sincronizar agora</strong><p>Executa a regra padrão do módulo e percorre automaticamente todas as páginas.</p></div>
      <div><span class="blue"><i class="fa-solid fa-forward-step"></i></span><strong>Atualizar lacuna</strong><p>Em Pedidos e Serviços, busca o intervalo que falta desde a última data local até hoje.</p></div>
      <div><span class="yellow"><i class="fa-solid fa-play"></i></span><strong>Retomar</strong><p>Continua da próxima página quando uma sincronização anterior foi interrompida.</p></div>
      <div><span class="red"><i class="fa-solid fa-trash-can"></i></span><strong>Zerar dados locais</strong><p>Exclui a base local do módulo. Use somente quando uma reconstrução completa for necessária.</p></div>
     </div>
    </section>
   </section>
  <?php break;
 }
 $body=ob_get_clean();
 layout($body,$u,$name);
}

function layout(string $body,?array $u,string $page=''): void{
 $pageMeta=[
  'dashboard'=>['Dashboard','Visão geral da operação','fa-chart-line'],
  'clients'=>['Clientes','Central de clientes','fa-users'],
  'client_audit'=>['Clientes','Central de clientes','fa-users'],
  'client_sync'=>['Clientes','Sincronização com a Omie','fa-cloud-arrow-up'],
  'products'=>['Produtos','Catálogo comercial','fa-boxes-stacked'],
  'client_new'=>['Clientes','Cadastro de cliente','fa-user-plus'],
  'client'=>['Clientes','Detalhes do cliente','fa-address-card'],
  'contact_monitoring'=>['Comercial','Contatos e retornos','fa-headset'],
  'orders'=>['Pedidos','Operação comercial','fa-receipt'],
  'order_detail'=>['Pedidos','Detalhes do pedido','fa-file-invoice'],
  'order_new'=>['Pedidos','Novo pedido','fa-cart-plus'],
  'services'=>['Serviços','Ordens de serviço','fa-screwdriver-wrench'],
  'collection'=>['Cobrança','Carteira de cobrança','fa-hand-holding-dollar'],
  'collection_report'=>['Cobrança','Relatório de cobranças','fa-chart-column'],
  'collection_recoveries'=>['Cobrança','Pagamentos recuperados','fa-money-bill-transfer'],
  'collection_case'=>['Cobrança','Atendimento de cobrança','fa-file-invoice-dollar'],
  'agenda'=>['Agenda','Agenda e retornos','fa-calendar-check'],
  'users'=>['Gestão','Usuários e permissões','fa-users-gear'],
  'goals'=>['Gestão','Metas da equipe','fa-bullseye'],
  'admin_center'=>['Administração','Centro administrativo','fa-table-cells-large'],
  'settings'=>['Sistema','Configurações do CRM','fa-gears'],
  'test_data'=>['Sistema','Ferramentas técnicas','fa-flask'],
  'sync'=>['Sistema','Sincronização com Omie','fa-arrows-rotate'],
  'opportunities'=>['Comercial','Oportunidades','fa-chart-column'],
  'opportunity_detail'=>['Comercial','Detalhe da oportunidade','fa-handshake'],
  'sales_flow_settings'=>['Gestão','Funil de vendas','fa-diagram-project'],
  'management_result'=>['Gestão','Resultados','fa-chart-column'],
  'result'=>['Resultados','Meu desempenho','fa-chart-line']
 ];
 $pageInfo=$pageMeta[$page]??['Tecnodata CRM','Operação','fa-graduation-cap'];
 ?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($GLOBALS['config']['app']['name']??'Tecnodata CRM')?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.datatables.net/3.0.3/css/dataTables.bootstrap5.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" rel="stylesheet"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="<?=APP_URL?>/assets/app.css?v=<?=is_file(APP_ROOT.'/public/assets/app.css')?filemtime(APP_ROOT.'/public/assets/app.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/premium.css?v=<?=is_file(APP_ROOT.'/public/assets/premium.css')?filemtime(APP_ROOT.'/public/assets/premium.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/clients-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/clients-v2.css')?filemtime(APP_ROOT.'/public/assets/clients-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/client-audit-v1.css?v=<?=is_file(APP_ROOT.'/public/assets/client-audit-v1.css')?filemtime(APP_ROOT.'/public/assets/client-audit-v1.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/dashboard-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/dashboard-v2.css')?filemtime(APP_ROOT.'/public/assets/dashboard-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/results-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/results-v2.css')?filemtime(APP_ROOT.'/public/assets/results-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/orders-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/orders-v2.css')?filemtime(APP_ROOT.'/public/assets/orders-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/order-new-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/order-new-v2.css')?filemtime(APP_ROOT.'/public/assets/order-new-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/services-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/services-v2.css')?filemtime(APP_ROOT.'/public/assets/services-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/collection-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/collection-v2.css')?filemtime(APP_ROOT.'/public/assets/collection-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/agenda-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/agenda-v2.css')?filemtime(APP_ROOT.'/public/assets/agenda-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/contact-monitoring-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/contact-monitoring-v2.css')?filemtime(APP_ROOT.'/public/assets/contact-monitoring-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/final-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/final-v2.css')?filemtime(APP_ROOT.'/public/assets/final-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/visual-polish.css?v=<?=is_file(APP_ROOT.'/public/assets/visual-polish.css')?filemtime(APP_ROOT.'/public/assets/visual-polish.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/results-models-v3.css?v=<?=is_file(APP_ROOT.'/public/assets/results-models-v3.css')?filemtime(APP_ROOT.'/public/assets/results-models-v3.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/opportunities-v1.css?v=<?=is_file(APP_ROOT.'/public/assets/opportunities-v1.css')?filemtime(APP_ROOT.'/public/assets/opportunities-v1.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/admin-center-v1.css?v=<?=is_file(APP_ROOT.'/public/assets/admin-center-v1.css')?filemtime(APP_ROOT.'/public/assets/admin-center-v1.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/settings-v3.css?v=<?=is_file(APP_ROOT.'/public/assets/settings-v3.css')?filemtime(APP_ROOT.'/public/assets/settings-v3.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/design-system-v4.css?v=<?=is_file(APP_ROOT.'/public/assets/design-system-v4.css')?filemtime(APP_ROOT.'/public/assets/design-system-v4.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/management-v4.css?v=<?=is_file(APP_ROOT.'/public/assets/management-v4.css')?filemtime(APP_ROOT.'/public/assets/management-v4.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/goals-v1.css?v=<?=is_file(APP_ROOT.'/public/assets/goals-v1.css')?filemtime(APP_ROOT.'/public/assets/goals-v1.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/workspace-v4.css?v=<?=is_file(APP_ROOT.'/public/assets/workspace-v4.css')?filemtime(APP_ROOT.'/public/assets/workspace-v4.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/sync-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/sync-v2.css')?filemtime(APP_ROOT.'/public/assets/sync-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/crm-master-v1.css?v=<?=is_file(APP_ROOT.'/public/assets/crm-master-v1.css')?filemtime(APP_ROOT.'/public/assets/crm-master-v1.css'):time()?>"></head><body data-page="<?=e($page)?>"><?php if(!$u){echo $body;}else{?>
 <div class="tdcrm-shell">
  <aside class="tdcrm-sidebar" id="appSidebar" aria-label="Navegação principal">
   <div class="tdcrm-brand">
    <a href="<?=APP_URL?>/">
     <span class="tdcrm-brand-mark"><i class="fa-solid fa-graduation-cap"></i></span>
     <span class="tdcrm-brand-copy"><strong>Tecnodata <b>CRM</b></strong><small>Educacional</small></span>
    </a>
    <button class="sidebar-close" type="button" data-menu-close aria-label="Fechar menu"><i class="fa-solid fa-xmark"></i></button>
   </div>
   <nav class="tdcrm-nav">
    <?php if($u['role']==='seller'):?>
     <a class="tdcrm-nav-home" href="<?=APP_URL?>/"><i class="fa-solid fa-house"></i><span>Meu painel</span></a>
     <div class="tdcrm-nav-group" data-nav-group="seller-clients" data-default-open="1"><button class="tdcrm-nav-group-toggle" type="button" aria-expanded="true"><span><i class="fa-solid fa-users"></i>Clientes</span><i class="fa-solid fa-chevron-down"></i></button><div class="tdcrm-nav-group-links"><a href="<?=APP_URL?>/clients"><i class="fa-solid fa-users"></i><span>Base de clientes</span></a><a href="<?=APP_URL?>/my-portfolio"><i class="fa-solid fa-briefcase"></i><span>Minha carteira</span></a></div></div>
     <div class="tdcrm-nav-group" data-nav-group="seller-sales" data-default-open="1"><button class="tdcrm-nav-group-toggle" type="button" aria-expanded="true"><span><i class="fa-solid fa-cart-shopping"></i>Vendas</span><i class="fa-solid fa-chevron-down"></i></button><div class="tdcrm-nav-group-links"><?php if(sales_flow_enabled()):?><a href="<?=APP_URL?>/opportunities"><i class="fa-solid fa-chart-column"></i><span>Oportunidades</span></a><?php endif;?><a href="<?=APP_URL?>/orders/new"><i class="fa-solid fa-circle-plus"></i><span>Novo pedido</span></a><a href="<?=APP_URL?>/orders"><i class="fa-regular fa-rectangle-list"></i><span>Meus pedidos</span></a></div></div>
     <a href="<?=APP_URL?>/products"><i class="fa-solid fa-boxes-stacked"></i><span>Produtos</span></a>
     <a href="<?=APP_URL?>/agenda"><i class="fa-regular fa-calendar-check"></i><span>Minha agenda</span></a>
    <?php elseif($u['role']==='collector'):?>
     <a class="tdcrm-nav-home" href="<?=APP_URL?>/"><i class="fa-solid fa-house"></i><span>Meu painel</span></a>
     <div class="tdcrm-nav-group" data-nav-group="collector-collection" data-default-open="1"><button class="tdcrm-nav-group-toggle" type="button" aria-expanded="true"><span><i class="fa-solid fa-hand-holding-dollar"></i>Cobrança</span><i class="fa-solid fa-chevron-down"></i></button><div class="tdcrm-nav-group-links"><a href="<?=APP_URL?>/collection"><i class="fa-solid fa-circle-dollar-to-slot"></i><span>Carteira de cobrança</span></a><a href="<?=APP_URL?>/collection/report"><i class="fa-solid fa-chart-column"></i><span>Relatório de cobranças</span></a><a href="<?=APP_URL?>/agenda"><i class="fa-regular fa-calendar-check"></i><span>Minha agenda</span></a></div></div>
    <?php else:?>
     <div class="tdcrm-nav-context">
      <span><?=e($u['role']==='admin'?'ADMINISTRAÇÃO':'SUPERVISÃO')?></span>
      <small><?=e($u['role']==='admin'?'Gestão completa do CRM':'Gestão da operação comercial')?></small>
     </div>
     <a class="tdcrm-nav-home" href="<?=APP_URL?>/"><i class="fa-solid fa-chart-line"></i><span>Dashboard</span></a>

     <div class="tdcrm-nav-section-label">OPERAÇÃO</div>

     <div class="tdcrm-nav-group" data-nav-group="commercial" data-default-open="1">
      <button class="tdcrm-nav-group-toggle" type="button" aria-expanded="true"><span><i class="fa-solid fa-handshake"></i>Comercial</span><i class="fa-solid fa-chevron-down"></i></button>
      <div class="tdcrm-nav-group-links">
       <?php if(sales_flow_enabled()):?><a href="<?=APP_URL?>/opportunities"><i class="fa-solid fa-chart-column"></i><span>Oportunidades</span></a><?php endif;?>
       <a href="<?=APP_URL?>/clients"><i class="fa-solid fa-users"></i><span>Clientes</span></a>
       <a href="<?=APP_URL?>/products"><i class="fa-solid fa-boxes-stacked"></i><span>Produtos</span></a>
       <a href="<?=APP_URL?>/contact-monitoring"><i class="fa-solid fa-headset"></i><span>Contatos e retornos</span></a>
       <a href="<?=APP_URL?>/orders"><i class="fa-regular fa-rectangle-list"></i><span>Pedidos</span></a>
       <a href="<?=APP_URL?>/services"><i class="fa-solid fa-screwdriver-wrench"></i><span>Serviços</span></a>
      </div>
     </div>

     <div class="tdcrm-nav-group" data-nav-group="collection">
      <button class="tdcrm-nav-group-toggle" type="button" aria-expanded="false"><span><i class="fa-solid fa-circle-dollar-to-slot"></i>Cobrança</span><i class="fa-solid fa-chevron-down"></i></button>
      <div class="tdcrm-nav-group-links">
       <a href="<?=APP_URL?>/collection"><i class="fa-solid fa-hand-holding-dollar"></i><span>Carteira de cobrança</span></a>
       <a href="<?=APP_URL?>/collection/report"><i class="fa-solid fa-chart-column"></i><span>Relatório de cobranças</span></a>
       <a href="<?=APP_URL?>/agenda?type=collection"><i class="fa-regular fa-calendar-check"></i><span>Agenda de cobrança</span></a>
      </div>
     </div>

     <div class="tdcrm-nav-section-label">GESTÃO</div>

     <div class="tdcrm-nav-group" data-nav-group="management" data-default-open="1">
      <button class="tdcrm-nav-group-toggle" type="button" aria-expanded="true"><span><i class="fa-solid fa-chart-line"></i>Gestão da equipe</span><i class="fa-solid fa-chevron-down"></i></button>
      <div class="tdcrm-nav-group-links">
       <a href="<?=APP_URL?>/agenda"><i class="fa-regular fa-calendar-days"></i><span>Agenda da equipe</span></a>
       <a href="<?=APP_URL?>/goals"><i class="fa-solid fa-bullseye"></i><span>Metas da equipe</span></a>
       <?php if($u['role']==='admin'):?><a href="<?=APP_URL?>/users"><i class="fa-solid fa-users-gear"></i><span>Usuários e permissões</span></a><?php endif;?>
       <a href="<?=APP_URL?>/sales-flow-settings"><i class="fa-solid fa-diagram-project"></i><span>Configurar funil de vendas</span></a>
       <?php if($u['role']==='supervisor'):?><a href="<?=APP_URL?>/settings"><i class="fa-solid fa-sliders"></i><span>Configurações da operação</span></a><?php endif;?>
      </div>
     </div>

     <?php if($u['role']==='admin'):?>
      <div class="tdcrm-nav-section-label">SISTEMA</div>
      <div class="tdcrm-nav-group" data-nav-group="system">
       <button class="tdcrm-nav-group-toggle" type="button" aria-expanded="false"><span><i class="fa-solid fa-gears"></i>Administração do CRM</span><i class="fa-solid fa-chevron-down"></i></button>
       <div class="tdcrm-nav-group-links">
        <a href="<?=APP_URL?>/admin"><i class="fa-solid fa-table-cells-large"></i><span>Centro administrativo</span></a>
        <a href="<?=APP_URL?>/sync"><i class="fa-solid fa-arrows-rotate"></i><span>Sincronização com Omie</span></a>
        <a href="<?=APP_URL?>/settings"><i class="fa-solid fa-gear"></i><span>Configurações do CRM</span></a>
        <a href="<?=APP_URL?>/test-data"><i class="fa-solid fa-flask"></i><span>Ferramentas técnicas</span></a>
       </div>
      </div>
     <?php endif;?>
    <?php endif;?>
   </nav>
   <div class="tdcrm-sidebar-footer">
    <span class="tdcrm-footer-mark"><i class="fa-solid fa-graduation-cap"></i></span>
    <div><strong>Tecnodata</strong><small>Educacional</small><p>Tecnologia que move pessoas mais longe.</p></div>
   </div>
  </aside>
  <button class="sidebar-backdrop" type="button" data-menu-backdrop aria-label="Fechar menu"></button>
  <main class="tdcrm-main">
   <header class="tdcrm-topbar">
    <div class="tdcrm-topbar-left">
     <button class="tdcrm-menu-button" type="button" data-menu aria-controls="appSidebar" aria-expanded="true"><i class="fa-solid fa-bars"></i></button>
     <div class="tdcrm-topbar-context"><span><i class="fa-solid <?=e($pageInfo[2])?>"></i></span><div><small><?=e($pageInfo[0])?></small><strong><?=e($pageInfo[1])?></strong></div></div>
    </div>
    <div class="tdcrm-topbar-right">
     <?php $notifications=NotificationService::forUser($u);$notificationTotal=(int)($notifications['total']??0);?>
     <div class="tdcrm-notification-wrap">
      <button class="tdcrm-notification" type="button" title="Notificações" aria-expanded="false" data-notification-toggle><i class="fa-regular fa-bell"></i><?php if($notificationTotal>0):?><b><?=$notificationTotal>99?'99+':$notificationTotal?></b><?php endif;?></button>
      <div class="tdcrm-notification-panel" data-notification-panel hidden>
       <div class="tdcrm-notification-head"><div><strong>Notificações</strong><small><?=$notificationTotal>0?$notificationTotal.' pendência(s)':'Nenhuma pendência'?></small></div><a href="<?=APP_URL?>/agenda">Ver agenda</a></div>
       <div class="tdcrm-notification-list">
        <?php if(!empty($notifications['items'])):foreach($notifications['items'] as $notification):?>
         <a class="tdcrm-notification-item <?=e($notification['type'])?>" href="<?=APP_URL.e($notification['href'])?>">
          <span><i class="fa-solid <?=e($notification['icon'])?>"></i></span>
          <div><strong><?=e($notification['title'])?></strong><small><?=e($notification['text'])?></small></div>
          <i class="fa-solid fa-chevron-right"></i>
         </a>
        <?php endforeach;else:?>
         <div class="tdcrm-notification-empty"><span><i class="fa-solid fa-circle-check"></i></span><div><strong>Tudo em dia</strong><small>Não há pendências para exibir agora.</small></div></div>
        <?php endif;?>
       </div>
      </div>
     </div>
     <div class="tdcrm-user">
      <span><?=e(mb_strtoupper(mb_substr((string)$u['name'],0,1)))?></span>
      <div><strong>Olá, <?=e(explode(' ',trim((string)$u['name']))[0]??$u['name'])?></strong><small><?=e($u['role']==='admin'?'Administrador':($u['role']==='supervisor'?'Supervisor':($u['role']==='seller'?'Vendedor':'Cobrança')))?></small></div>
     </div>
     <form method="post" action="<?=APP_URL?>/logout" class="tdcrm-logout"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button type="submit" title="Sair"><i class="fa-solid fa-right-from-bracket"></i></button></form>
    </div>
   </header>
   <section class="tdcrm-content"><?=$body?></section>
  </main>
 </div>
 <?php if($u):?>
 <dialog class="product-detail-modal" data-product-detail-modal>
  <div class="product-detail-shell">
   <header><span><i class="fa-solid fa-box-open"></i></span><div><small>CATÁLOGO LOCAL</small><strong data-product-detail-title>Detalhes do produto</strong><p data-product-detail-subtitle>Informações sincronizadas da Omie</p></div><button type="button" data-product-detail-close aria-label="Fechar"><i class="fa-solid fa-xmark"></i></button></header>
   <div class="product-detail-body" data-product-detail-body><div class="product-detail-loading"><i class="fa-solid fa-spinner fa-spin"></i><span>Carregando detalhes...</span></div></div>
  </div>
 </dialog>
 <?php endif;?>
 <?php }?>
 <script>window.APP_URL=<?=json_encode(APP_URL)?>;window.CSRF=<?=json_encode(CSRF::token())?>;</script>
 <script src="https://cdn.datatables.net/3.0.3/js/dataTables.min.js"></script>
 <script src="https://cdn.datatables.net/3.0.3/js/dataTables.bootstrap5.min.js"></script>
 <script src="<?=APP_URL?>/assets/app.js?v=<?=is_file(APP_ROOT.'/public/assets/app.js')?filemtime(APP_ROOT.'/public/assets/app.js'):time()?>"></script>
 <?php if($u):?><script>
 (()=>{const base=<?=json_encode(rtrim(APP_URL,'/'))?>;const p=location.pathname.replace(/\/+$/,'')||'/';const links=[...document.querySelectorAll('.tdcrm-nav a')];links.forEach(a=>a.classList.remove('active'));let best=null,bestLen=-1;for(const a of links){const ap=new URL(a.href,location.origin).pathname.replace(/\/+$/,'')||'/';const isHome=ap===base||ap===base+'/';const ok=isHome?(p===ap||p===base):(p===ap||p.startsWith(ap+'/'));if(ok&&ap.length>bestLen){best=a;bestLen=ap.length;}}if(best){best.classList.add('active');const group=best.closest('.tdcrm-nav-group');if(group){group.classList.add('is-open');const toggle=group.querySelector('.tdcrm-nav-group-toggle');if(toggle)toggle.setAttribute('aria-expanded','true');}}})();
 </script><?php endif;?>
 </body></html><?php
}
