<?php
function render(string $name,array $vars=[]): void{
 extract($vars,EXTR_SKIP);$u=Auth::user();ob_start();
 switch($name){
  case 'login':?>
   <main class="tdlogin-page">
    <section class="tdlogin-hero">
     <div class="tdlogin-hero-inner">
      <div class="tdlogin-brand">
       <span class="tdlogin-brand-icon"><i class="fa-solid fa-graduation-cap"></i></span>
       <div><strong>Tecnodata <b>CRM</b></strong><small>Organize, conecte e faça mais negócios.</small></div>
      </div>

      <div class="tdlogin-copy">
       <h1>Gestão comercial<br><span>inteligente.</span></h1>
       <h2>Comercial <b>•</b> Cobrança <b>•</b> Supervisão</h2>
       <p>Tudo o que sua equipe precisa para vender mais, atender melhor e crescer com controle.</p>
      </div>

      <div class="tdlogin-demo" aria-hidden="true">
       <div class="tdlogin-window">
        <div class="tdlogin-window-dots"><i></i><i></i><i></i></div>
        <div class="tdlogin-window-body">
         <aside>
          <span class="active"><i class="fa-solid fa-users"></i>Clientes</span>
          <span><i class="fa-regular fa-rectangle-list"></i>Pedidos</span>
          <span><i class="fa-solid fa-screwdriver-wrench"></i>Serviços</span>
          <span><i class="fa-solid fa-circle-dollar-to-slot"></i>Cobrança</span>
          <span><i class="fa-regular fa-calendar"></i>Agenda</span>
         </aside>
         <div class="tdlogin-demo-main">
          <article class="tdlogin-stat tdlogin-stat-a">
           <span><i class="fa-solid fa-users"></i></span>
           <div><small>Clientes na carteira</small><strong>33.444</strong><em>↑ 12% este mês</em></div>
           <i class="fa-solid fa-chart-line"></i>
          </article>
          <article class="tdlogin-stat tdlogin-stat-b">
           <span><i class="fa-solid fa-chart-column"></i></span>
           <div><small>Receita em 12 meses</small><strong>R$ 1.290.505,17</strong><em>↑ 8% este mês</em></div>
          </article>
          <article class="tdlogin-stat tdlogin-stat-c">
           <span><i class="fa-solid fa-cart-shopping"></i></span>
           <div><small>Pedidos em 12 meses</small><strong>939</strong><em>↑ 15% este mês</em></div>
          </article>
         </div>
        </div>
       </div>
      </div>

      <div class="tdlogin-hero-foot"><i></i><span>Mais dados. Mais resultados. Mais negócios.</span></div>
     </div>
    </section>

    <section class="tdlogin-access">
     <div class="tdlogin-card">
      <div class="tdlogin-card-brand">
       <span><i class="fa-solid fa-graduation-cap"></i></span>
       <strong>Tecnodata <b>CRM</b></strong>
      </div>
      <p class="tdlogin-subtitle">Acesse sua conta para continuar.</p>

      <?php if(!empty($error)):?><div class="alert alert-danger tdlogin-alert"><i class="fa-solid fa-circle-exclamation"></i><span><?=e($error)?></span></div><?php endif;?>

      <form method="post" action="<?=APP_URL?>/login" class="tdlogin-form" autocomplete="on">
       <input type="hidden" name="_token" value="<?=CSRF::token()?>">

       <label for="tdlogin-email">E-mail</label>
       <div class="tdlogin-input">
        <i class="fa-regular fa-envelope"></i>
        <input id="tdlogin-email" class="form-control" type="email" name="email" placeholder="seu@email.com" required autofocus autocomplete="username">
       </div>

       <label for="tdlogin-password">Senha</label>
       <div class="tdlogin-input">
        <i class="fa-solid fa-lock"></i>
        <input id="tdlogin-password" class="form-control" type="password" name="password" placeholder="Sua senha" required autocomplete="current-password">
        <button type="button" class="tdlogin-password-toggle" data-password-toggle aria-label="Mostrar senha" title="Mostrar senha"><i class="fa-regular fa-eye"></i></button>
       </div>

       <div class="tdlogin-options">
        <label class="tdlogin-remember"><input type="checkbox" name="remember_access" value="1"><span><i class="fa-solid fa-check"></i></span>Lembrar acesso</label>
        <span class="tdlogin-help" title="A redefinição de senha ainda é administrada internamente.">Esqueci minha senha</span>
       </div>

       <button class="tdlogin-submit" type="submit"><i class="fa-solid fa-arrow-right"></i><span>Entrar no sistema</span></button>
      </form>

      <div class="tdlogin-secure"><i class="fa-solid fa-shield-halved"></i><span>Ambiente seguro para equipe comercial, cobrança e supervisão.</span></div>
     </div>

     <footer class="tdlogin-footer"><strong>Tecnodata CRM</strong><span>Operação comercial inteligente.</span></footer>
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
  case 'clients':?>
   <?php $clientStats=$clientStats??['total'=>count($rows),'revenue'=>0,'orders'=>0,'without_seller'=>0];$portfolioMode=!empty($portfolioMode);?>
   <section class="tdc-page <?=$portfolioMode?'tdc-portfolio-page':''?>">
    <header class="tdc-head">
     <div class="tdc-head-main">
      <span class="tdc-head-icon"><i class="fa-solid <?=$portfolioMode?'fa-briefcase':'fa-users'?>"></i></span>
      <div><span class="tdc-kicker"><?=$portfolioMode?'COMERCIAL / MINHA CARTEIRA':'RELACIONAMENTO / CLIENTES'?></span><h1><?=$portfolioMode?'Minha Carteira':'Clientes'?></h1><p><?=$portfolioMode?'Seus clientes vinculados, organizados para facilitar contatos, pedidos e acompanhamento comercial.':'Consulte toda a base comercial e identifique rapidamente o responsável por cada cliente.'?></p></div>
     </div>
     <div class="tdc-head-actions"><a class="tdc-btn" href="<?=APP_URL?>/<?=$portfolioMode?'my-portfolio':'clients'?>"><i class="fa-solid fa-rotate-right"></i>Atualizar</a><a class="tdc-btn tdc-btn-primary" href="<?=APP_URL?>/clients/new"><i class="fa-solid fa-user-plus"></i>Novo cliente</a></div>
    </header>

    <?php if($flash):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>

    <div class="tdc-kpis">
     <article class="tdc-kpi green"><span class="tdc-kpi-icon"><i class="fa-solid fa-address-book"></i></span><div><small><?=$portfolioMode?'Clientes vinculados':'Clientes na consulta'?></small><strong><?=number_format((int)$clientStats['total'],0,',','.')?></strong><p><?=$tag!==''?'Tag: '.e($tag):($uf!==''?'Carteira de '.$uf.($ddds?' · DDD '.implode(', ',$ddds):''):($q!==''?'Resultado do filtro atual':($portfolioMode?'Somente sua responsabilidade':'Base comercial ativa')))?></p></div></article>
     <article class="tdc-kpi blue"><span class="tdc-kpi-icon"><i class="fa-solid fa-chart-line"></i></span><div><small>Receita em 12 meses</small><strong><?=money($clientStats['revenue'])?></strong><p>Produção acumulada da carteira</p></div></article>
     <article class="tdc-kpi yellow"><span class="tdc-kpi-icon"><i class="fa-solid fa-cart-shopping"></i></span><div><small>Pedidos em 12 meses</small><strong><?=number_format((int)$clientStats['orders'],0,',','.')?></strong><p>Volume comercial recente</p></div></article>
     <?php if($portfolioMode):?><article class="tdc-kpi orange owner"><span class="tdc-kpi-icon"><i class="fa-solid fa-user-tie"></i></span><div><small>Responsável pela carteira</small><strong><?=e(Auth::user()['name']??'Vendedor')?></strong><p>Vínculo exclusivo do seu usuário</p></div></article><?php else:?><article class="tdc-kpi orange"><span class="tdc-kpi-icon"><i class="fa-solid fa-user-tag"></i></span><div><small>Sem vendedor</small><strong><?=number_format((int)$clientStats['without_seller'],0,',','.')?></strong><p>Clientes para distribuição</p></div></article><?php endif;?>
    </div>

    <?php if(Auth::can('admin','supervisor')):?>
    <section class="tdc-card">
     <div class="tdc-card-head"><div class="tdc-card-title"><span><i class="fa-solid fa-users-gear"></i></span><div><strong>Gestão de carteira</strong><small>Distribua clientes por estado, DDD e vendedor.</small></div></div><span class="tdc-badge"><i class="fa-solid fa-shield-halved"></i> Somente CRM</span></div>
     <form method="post" action="<?=APP_URL?>/clients/portfolio/assign" class="tdc-portfolio-form">
      <input type="hidden" name="_token" value="<?=CSRF::token()?>">
      <div class="tdc-field"><label>Estado</label><select class="form-select" name="uf" required data-client-state-filter><option value="">Selecione</option><?php foreach($portfolioStates??[] as $state):?><option value="<?=e($state['uf'])?>" <?=$uf===$state['uf']?'selected':''?>><?=e($state['uf'])?></option><?php endforeach;?></select></div>
      <div class="tdc-ddd-picker clients-ddd-picker"><span>DDDs da região</span><div><?php foreach(($portfolioDddMap[$uf]??[]) as $ddd):?><label><input type="checkbox" name="ddds[]" value="<?=e($ddd)?>" <?=in_array($ddd,$ddds??[],true)?'checked':''?>><b><?=e($ddd)?></b></label><?php endforeach;?><?php if($uf===''):?><small>Selecione primeiro o estado.</small><?php endif;?></div><?php if($uf!==''):?><button class="tdc-btn" type="button" data-client-ddd-apply><i class="fa-solid fa-filter"></i>Filtrar tabela</button><?php endif;?></div>
      <div class="tdc-field"><label>Carteira atual</label><select class="form-select" name="source_seller" required><option value="__unassigned__">Somente sem vendedor</option><option value="__all__">Todos dos DDDs</option><?php foreach($portfolioSourceSellers??$portfolioSellers??[] as $seller):?><option value="<?=e($seller['omie_code'])?>"><?=e($seller['name'])?><?=isset($seller['active'])&&!(int)$seller['active']?' (inativo)':''?></option><?php endforeach;?></select></div>
      <div class="tdc-field"><label>Vendedor de destino</label><select class="form-select" name="target_seller" required><option value="">Selecione</option><?php foreach($portfolioSellers??[] as $seller):?><option value="<?=e($seller['omie_code'])?>"><?=e($seller['name'])?></option><?php endforeach;?></select></div>
      <button class="tdc-btn tdc-btn-primary" type="submit" data-submit-loading="Atualizando carteira..." data-confirm="Confirmar a transferência dos clientes dos DDDs selecionados para o vendedor de destino?"><i class="fa-solid fa-arrow-right-arrow-left"></i>Aplicar carteira</button>
     </form>
    </section>
    <?php endif;?>

    <?php if(Auth::can('seller')&&!$portfolioMode):?>
    <nav class="tdc-scope">
     <a class="<?=!$portfolioMode&&($clientScope??'all')==='all'?'active':''?>" href="<?=APP_URL?>/clients"><i class="fa-solid fa-users"></i><span><strong>Todos os clientes</strong><small>Visão geral da base comercial</small></span><i class="fa-solid fa-chevron-right"></i></a>
     <a class="<?=$portfolioMode?'active':''?>" href="<?=APP_URL?>/my-portfolio"><i class="fa-solid fa-briefcase"></i><span><strong>Minha carteira</strong><small>Somente os vinculados a você</small></span><i class="fa-solid fa-chevron-right"></i></a>
     <a class="<?=!$portfolioMode&&($clientScope??'all')==='unassigned'?'active':''?>" href="<?=APP_URL?>/clients?scope=unassigned"><i class="fa-solid fa-user-plus"></i><span><strong>Sem vendedor</strong><small><?=number_format((int)($availableClients??0),0,',','.')?> disponíveis</small></span><i class="fa-solid fa-chevron-right"></i></a>
    </nav>
    <?php endif;?>

    <section class="tdc-table-card">
     <div class="tdc-table-top"><div><span class="tdc-kicker"><?=$portfolioMode?'ATENDIMENTO PRIORITÁRIO':'BASE COMERCIAL'?></span><h2><?=$portfolioMode?'Clientes da minha carteira':'Todos os clientes'?></h2><p><?=$portfolioMode?'Busque somente entre os clientes vinculados ao seu vendedor.':'Busque por nome, documento, cidade ou vendedor.'?></p></div><div class="tdc-table-legend"><span class="view"><i class="fa-regular fa-eye"></i>Visualizar</span><span class="edit"><i class="fa-regular fa-pen-to-square"></i>Editar</span><?php if(Auth::can('admin','supervisor')):?><span class="local"><i class="fa-solid fa-database"></i>CRM</span><span class="delete"><i class="fa-regular fa-trash-can"></i>Excluir</span><?php endif;?></div></div>
     <div class="tdc-list-filterbar">
      <form method="get" action="<?=APP_URL?>/<?=$portfolioMode?'my-portfolio':'clients'?>">
       <?php if(!$portfolioMode&&Auth::can('seller')&&($clientScope??'all')==='unassigned'):?><input type="hidden" name="scope" value="unassigned"><?php endif;?>
       <label><span><i class="fa-solid fa-map-location-dot"></i> Estado</span><select class="form-select" name="uf" onchange="this.form.submit()"><option value="">Todos os estados</option><?php foreach($clientStates??[] as $state):?><option value="<?=e($state['uf'])?>" <?=$uf===$state['uf']?'selected':''?>><?=e($state['uf'])?></option><?php endforeach;?></select></label>
       <?php if(!$portfolioMode):?>
        <label><span><i class="fa-solid fa-tags"></i> Filtrar por tag</span><select class="form-select" name="tag" data-client-tag-filter><option value="">Todas as tags</option><?php foreach($clientTags??[] as $clientTag):?><option value="<?=e($clientTag['tag'])?>" <?=$tag===$clientTag['tag']?'selected':''?>><?=e($clientTag['tag'])?> (<?=number_format((int)$clientTag['client_count'],0,',','.')?>)</option><?php endforeach;?></select></label>
       <?php endif;?>
       <?php if($uf!==''||(!$portfolioMode&&$tag!=='')):?><a class="tdc-btn" href="<?=APP_URL?>/<?=$portfolioMode?'my-portfolio':'clients'?><?=(!$portfolioMode&&Auth::can('seller')&&($clientScope??'all')==='unassigned')?'?scope=unassigned':''?>"><i class="fa-solid fa-xmark"></i>Limpar filtros</a><?php endif;?>
      </form>
      <small><i class="fa-solid fa-circle-info"></i> <?=$portfolioMode?'A carteira permanece limitada aos seus clientes; o Estado apenas refina a visualização.':count($clientTags??[]).' tags mapeadas nos cadastros ativos'?></small>
     </div>
     <div class="table-card tdc-table-wrap">
      <?php $clientDataParams=['uf'=>$uf];if($ddds)$clientDataParams['ddds']=$ddds;if($tag!=='')$clientDataParams['tag']=$tag;if($portfolioMode)$clientDataParams['portfolio']='mine';elseif(Auth::can('seller')&&($clientScope??'all')==='unassigned')$clientDataParams['scope']='unassigned';?>
      <table class="table tdc-table clients-datatable" data-server-url="<?=APP_URL?>/api/clients/datatable?<?=e(http_build_query($clientDataParams))?>" data-search="<?=e($q)?>" data-page-length="5" data-length-change="1" data-order-column="0" data-order-direction="asc">
       <thead><tr><th>Cliente</th><th>Localização</th><th>Vendedor</th><th data-dt-order="disable">Tags</th><th>Ciclo</th><th>Dias sem contato</th><th>Última compra</th><th class="text-end">Receita 12m</th><th class="text-end" data-dt-order="disable">Ações</th></tr></thead>
       <tbody><?php foreach($rows as $r):?><tr>
        <td><?php $loggedSellerCode=trim((string)(Auth::user()['seller_omie_code']??''));$rowSellerOwned=Auth::can('admin','supervisor')||(Auth::can('seller')&&$loggedSellerCode!==''&&(string)($r['seller_omie_code']??'')===$loggedSellerCode);$rowSellerUnassigned=trim((string)($r['seller_omie_code']??''))==='';?><div class="tdc-client-cell"><span class="tdc-avatar"><?=e(mb_strtoupper(mb_substr((string)$r['name'],0,1)))?></span><div><?php if($rowSellerOwned||$rowSellerUnassigned):?><a href="<?=APP_URL?>/clients/<?=$r['id']?>"><strong><?=e($r['name'])?></strong></a><?php else:?><strong><?=e($r['name'])?></strong><?php endif;?><small><?=e($r['document']?:'Documento não informado')?></small></div></div></td>
        <td><span><i class="fa-solid fa-location-dot"></i> <?=e(trim(($r['city']??'').' / '.($r['uf']??''),' /')?:'Não informado')?></span></td>
        <td><?php if(!empty($r['seller_name'])):?><div class="tdc-seller-cell"><i class="fa-solid fa-user-tie"></i><div><strong><?=e($r['seller_name'])?></strong><small><?=e($r['seller_omie_code'])?></small></div></div><?php else:?><div class="tdc-seller-cell empty"><i class="fa-solid fa-user-slash"></i><div><strong>Sem vendedor</strong><small><?=Auth::can('seller')?'Atendimento compartilhado':'Disponível para vincular'?></small></div></div><?php endif;?></td>
        <td><?php $rowTags=client_tags_from_raw($r['raw_json']??null);?><div class="client-tag-list" title="<?=e(implode(', ',$rowTags))?>"><?php foreach(array_slice($rowTags,0,3) as $rowTag):?><span><?=e($rowTag)?></span><?php endforeach;?><?php if(count($rowTags)>3):?><b>+<?=count($rowTags)-3?></b><?php endif;?><?php if(!$rowTags):?><small>Sem tag</small><?php endif;?></div></td>
        <td><span class="tdc-cycle"><?=e($r['cycle']['label'])?></span></td>
        <td><?php $lastContactAt=trim((string)($r['last_contact_at']??''));if($lastContactAt===''):?><span class="tdc-contact-days never"><strong>Nunca</strong></span><?php else:$contactDays=max(0,(int)floor((strtotime(date('Y-m-d'))-strtotime(date('Y-m-d',strtotime($lastContactAt))))/86400));$contactClass=$contactDays<=30?'ok':($contactDays<=60?'warning':'late');?><span class="tdc-contact-days <?=$contactClass?>"><strong><?=$contactDays?></strong></span><?php endif;?></td>
        <td><strong><?=brdate($r['last_purchase_at']??null)?></strong><small><?=($r['orders_12m']??0)>0?(int)$r['orders_12m'].' pedido(s) em 12 meses':'Sem pedidos recentes'?></small></td>
        <td class="text-end"><strong><?=money($r['revenue_12m']??0)?></strong></td>
        <td><?php $rowOwned=Auth::can('admin','supervisor')||(Auth::can('seller')&&(string)($r['seller_omie_code']??'')===(string)(Auth::user()['seller_omie_code']??''));$rowUnassigned=trim((string)($r['seller_omie_code']??''))==='';?><div class="tdc-actions"><?php if($rowOwned||$rowUnassigned):?><a class="tdc-icon-btn view" href="<?=APP_URL?>/clients/<?=$r['id']?>" title="Visualizar"><i class="fa-regular fa-eye"></i></a><?php else:?><span class="tdc-icon-btn locked" title="Cliente vinculado a outro vendedor"><i class="fa-solid fa-lock"></i></span><?php endif;?><?php if($rowOwned):?><a class="tdc-icon-btn edit" href="<?=APP_URL?>/clients/<?=$r['id']?>/edit" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a><?php endif;?><?php if(Auth::can('admin','supervisor')):?><form method="post" action="<?=APP_URL?>/clients/<?=$r['id']?>/delete-local"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="tdc-icon-btn local" type="submit" title="Remover somente do CRM" data-confirm="Excluir somente do CRM local? Nenhuma chamada será feita à Omie."><i class="fa-solid fa-database"></i></button></form><form method="post" action="<?=APP_URL?>/clients/<?=$r['id']?>/delete"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="tdc-icon-btn delete" type="submit" title="Excluir da Omie e do CRM" data-confirm="Excluir este cliente na Omie e também no CRM?"><i class="fa-regular fa-trash-can"></i></button></form><?php endif;?></div></td>
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
         <div class="field span-4"><label>Vendedor responsável</label><?php if(Auth::can('admin','supervisor')):?><select class="form-select" name="seller_omie_code"><option value="">Selecione...</option><?php foreach($sellers as $seller):?><option value="<?=e($seller['omie_code'])?>" <?=($old['seller_omie_code']??'')===$seller['omie_code']?'selected':''?>><?=e($seller['name'])?></option><?php endforeach;?></select><?php else:?><div class="tdc-fixed"><?=e(Auth::user()['name']??'Vendedor')?></div><?php endif;?></div>
         <div class="field span-8"><label>Tags<span class="tdc-required">*</span></label><div class="tdc-tags"><span><i class="fa-solid fa-user-check"></i>CLIENTE</span><span><i class="fa-solid fa-car-side"></i>CFC</span></div><input type="hidden" name="tags" value="CLIENTE, CFC"><small class="tdc-hint">As tags CLIENTE e CFC serão enviadas automaticamente para a Omie.</small></div>
         <div class="field span-12"><label>Observações</label><textarea class="form-control" name="notes" rows="4" placeholder="Informações úteis para o atendimento."><?=e((string)($old['notes']??''))?></textarea></div>
        </div>
       </section>

       <?php if(!$editClient&&$preview):?>
       <section class="tdc-section" id="tdc-revisao">
        <div class="tdc-section-head"><span class="tdc-section-icon yellow"><i class="fa-solid fa-code"></i></span><div><strong>Prévia da integração</strong><small>Payload validado antes do envio.</small></div><span class="tdc-badge">VALIDADO</span></div>
        <div class="tdc-fields"><div class="field span-4"><label>Cliente</label><div class="tdc-fixed"><?=e($preview['summary']['name'])?></div></div><div class="field span-4"><label>Documento</label><div class="tdc-fixed"><?=e($preview['summary']['document'])?></div></div><div class="field span-4"><label>Vendedor</label><div class="tdc-fixed"><?=e($preview['summary']['seller'])?></div></div></div>
       </section>
       <?php else:?><span id="tdc-revisao"></span><?php endif;?>

       <footer class="tdc-form-actions"><div><i class="fa-solid fa-circle-info"></i><span><?=$editClient?'Salvar atualiza os dados deste cliente.':'O cliente será salvo no CRM e poderá ser sincronizado com a Omie.'?></span></div><div class="actions"><?php if($editClient&&Auth::can('admin','supervisor')):?><button class="tdc-btn tdc-btn-danger" type="submit" formaction="<?=APP_URL?>/clients/<?=(int)$editClient['id']?>/delete" formnovalidate data-confirm="Excluir este cliente?"><i class="fa-regular fa-trash-can"></i>Excluir</button><?php endif;?><a class="tdc-btn" href="<?=$editClient?APP_URL.'/clients/'.(int)$editClient['id']:APP_URL.'/clients'?>">Cancelar</a><button class="tdc-btn tdc-btn-primary" type="submit" <?=$editClient?'data-confirm="Salvar estas alterações também na Omie?"':''?>><i class="fa-solid <?=$editClient?'fa-check':'fa-floppy-disk'?>"></i><?=$editClient?'Salvar alterações':'Salvar cliente'?></button></div></footer>
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
   ?>
   <section class="tdc-page">
    <header class="tdc-head tdc-detail-head">
     <div class="tdc-head-main"><span class="tdc-detail-avatar"><?=e(mb_strtoupper(mb_substr((string)$client['name'],0,1)))?></span><div><a class="tdc-back" href="<?=APP_URL?>/clients<?=!empty($sharedUnassigned)?'?scope=unassigned':''?>"><i class="fa-solid fa-arrow-left"></i>Carteira de clientes</a><h1><?=e($client['name'])?></h1><div class="tdc-meta"><span><i class="fa-solid fa-location-dot"></i><?=e(trim(($client['city']??'').' / '.($client['uf']??''),' /')?:'Localização não informada')?></span><span><i class="fa-regular fa-id-card"></i><?=e($client['document']?:'Documento não informado')?></span><span><i class="fa-solid fa-cloud"></i><?=$isLocal?'Somente local':'Omie '.e($client['omie_code'])?></span></div></div></div>
     <div class="tdc-head-actions"><?php if(empty($sharedUnassigned)):?><a class="tdc-btn" href="<?=APP_URL?>/clients/<?=$client['id']?>/edit"><i class="fa-regular fa-pen-to-square"></i>Editar</a><?php endif;?><?php if(sales_flow_enabled()&&!$isLocal):?><a class="tdc-btn" href="<?=APP_URL?>/opportunities?client_id=<?=$client['id']?>"><i class="fa-solid fa-chart-column"></i>Oportunidade</a><?php endif;?><?php if(!$isLocal):?><a class="tdc-btn tdc-btn-primary" href="<?=APP_URL?>/orders/new?client_id=<?=$client['id']?>"><i class="fa-solid fa-plus"></i>Novo pedido</a><?php endif;?></div>
    </header>
    <?php if($flash):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>

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
       <div><span>Telefone</span><strong><?=e(trim(($formData['phone_ddd']?:'').' '.($formData['phone_number']?:''))?:'—')?></strong></div><div><span>Vendedor</span><strong><?=e($sellerName?:'—')?></strong></div>
       <div class="wide"><span>Endereço</span><strong><?=e(trim(($formData['address']??'').' '.($formData['address_number']??'').(($formData['complement']??'')?' • '.$formData['complement']:''))?:'—')?></strong><small><?=e(trim(($formData['neighborhood']??'').' • '.($formData['city']??'').' / '.($formData['uf']??''),' •/'))?><?=($formData['zip_code']??'')?' • CEP '.e($formData['zip_code']):''?></small></div>
       <div class="wide"><span>Tags</span><div class="tdc-tags"><?php foreach(array_filter(array_map('trim',explode(',',(string)($formData['tags']??'')))) as $tag):?><span><?=e($tag)?></span><?php endforeach;?></div></div>
       <div class="wide"><span>Observações</span><strong><?=nl2br(e($formData['notes']?:'—'))?></strong></div>
      </div>
     </section>
     <section class="tdc-card">
      <div class="tdc-card-head"><div class="tdc-card-title"><span><i class="fa-solid fa-headset"></i></span><div><strong>Registrar contato</strong><small>Atualize o relacionamento e programe o próximo passo.</small></div></div></div>
      <form class="tdc-contact-form" method="post" action="<?=APP_URL?>/clients/<?=$client['id']?>/activity"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><label>Canal utilizado</label><select class="form-select" name="channel"><option value="phone">Ligação</option><option value="whatsapp">WhatsApp</option><option value="email">E-mail</option></select><label>Resultado do contato</label><select class="form-select" name="result"><?php foreach($taskResults??[] as $resultOption):?><option value="<?=e($resultOption['code'])?>"><?=e($resultOption['label'])?></option><?php endforeach;?></select><label>Próximo retorno</label><input class="form-control" type="datetime-local" name="next_at"><label>Anotação</label><textarea class="form-control" name="notes" rows="4"></textarea><button class="tdc-btn tdc-btn-primary w-100 mt-2"><i class="fa-solid fa-check"></i>Salvar contato</button></form>
     </section>
    </div>

    <div class="tdc-history-grid" id="tdc-history">
     <section class="tdc-card"><div class="tdc-card-head"><div class="tdc-card-title"><span><i class="fa-regular fa-comments"></i></span><div><strong>Últimos contatos</strong><small>Histórico de relacionamento.</small></div></div></div><div class="tdc-list"><?php $resultLabels=$taskResultLabels??[];foreach($activities as $a):?><div><strong><?=e($resultLabels[$a['result']]??$a['result'])?></strong><small><?=e($a['user_name'])?> • <?=date('d/m/Y H:i',strtotime($a['created_at']))?></small><?php if($a['notes']):?><p><?=nl2br(e($a['notes']))?></p><?php endif;?></div><?php endforeach;?><?php if(!$activities):?><div>Nenhum contato registrado.</div><?php endif;?></div></section>
     <section class="tdc-card" id="tdc-orders"><div class="tdc-card-head"><div class="tdc-card-title"><span><i class="fa-solid fa-receipt"></i></span><div><strong>Últimos pedidos</strong><small>Compras mais recentes do cliente.</small></div></div></div><div class="tdc-list"><?php foreach($orders as $o):?><div><a href="<?=APP_URL?>/orders/<?=(int)$o['id']?>"><strong><?=brdate($o['order_date'])?> · <?=e($o['number']??$o['omie_code'])?></strong></a><small><?=money($o['total'])?></small></div><?php endforeach;?><?php if(!$orders):?><div>Nenhum pedido encontrado.</div><?php endif;?></div></section>
    </div>
   </section>
  <?php break;

  case 'contact_monitoring':
   $monitorStats=$monitorStats??['total'=>0,'contacted'=>0,'scheduled'=>0,'overdue'=>0,'without_next'=>0];
   $resultLabels=[];foreach(task_result_catalog() as $resultItem)$resultLabels[(string)$resultItem['code']]=(string)$resultItem['label'];
   $contactRate=(int)$monitorStats['total']>0?round(((int)$monitorStats['contacted']/(int)$monitorStats['total'])*100,1):0;
   $attentionRows=[];$agendaRows=[];
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

    <section class="tdcontact4-panel"><header><div><span><i class="fa-solid fa-chart-simple"></i></span><div><strong>Acompanhamento da equipe</strong><small>Contato, próxima ação e status por cliente.</small></div></div><b><?=number_format(count($rows),0,',','.')?> clientes</b></header><div class="table-card tdcontact4-table-wrap"><table class="table tdcontact4-table" data-page-length="10" data-length-change="1" data-order-column="3" data-order-direction="desc"><thead><tr><th>Cliente</th><th>Responsável</th><th>Último contato</th><th>Dias sem contato</th><th>Próxima ação</th><th>Resultado</th><th>Status</th><th data-dt-order="disable">Ações</th></tr></thead><tbody><?php foreach($rows as $row):$lastTs=!empty($row['last_contact_at'])?strtotime((string)$row['last_contact_at']):null;$daysWithout=$lastTs?(int)floor((time()-$lastTs)/86400):null;$nextDue=!empty($row['next_due_at'])?strtotime((string)$row['next_due_at']):null;$statusClass=$nextDue&&$nextDue<time()?'danger':(!$nextDue?'warning':'ok');$statusLabel=$statusClass==='danger'?'Atrasado':($statusClass==='warning'?'Sem próxima ação':'Em dia');$defaultResponsible=(int)($row['next_user_id']??0);if($defaultResponsible<=0)$defaultResponsible=(int)($row['collection_user_id']??$row['portfolio_user_id']??0);$schedulePayload=['client_id'=>(int)$row['id'],'client_name'=>(string)$row['name'],'task_id'=>(int)($row['next_task_id']??0),'assigned_user_id'=>$defaultResponsible,'title'=>(string)($row['next_title']??'Próximo contato'),'due_at'=>$nextDue?date('Y-m-d\TH:i',$nextDue):''];?><tr><td><div class="tdcontact4-person"><span><?=e(mb_strtoupper(mb_substr((string)$row['name'],0,1)))?></span><div><strong><?=e($row['name'])?></strong><small><?=e(trim((string)($row['city']??'').' / '.(string)($row['uf']??''),' /')?:'Localização não informada')?></small></div></div></td><td><strong><?=e($row['next_user_name']??$row['portfolio_user_name']??$row['collection_user_name']??'Sem responsável')?></strong><small><?=e($row['seller_name']??'Carteira')?></small></td><td data-order="<?=e((string)($row['last_contact_at']??''))?>"><?php if($lastTs):?><strong><?=date('d/m/Y H:i',$lastTs)?></strong><small><?=e($row['last_contact_user']??'Usuário')?></small><?php else:?><span class="tdcontact4-muted">Nunca</span><?php endif;?></td><td data-order="<?=$daysWithout??99999?>"><strong class="<?=$daysWithout!==null&&$daysWithout>=10?'danger':''?>"><?=$daysWithout===null?'Nunca':$daysWithout?></strong></td><td data-order="<?=e((string)($row['next_due_at']??''))?>"><?php if($nextDue):?><strong><?=date('d/m H:i',$nextDue)?></strong><small><?=e($row['next_title']??'Próximo contato')?></small><?php else:?><span class="tdcontact4-muted">Não agendado</span><?php endif;?></td><td><span class="tdcontact4-result"><?=e($resultLabels[$row['last_result']]??($row['last_result']?:'Sem resultado'))?></span></td><td><span class="tdcontact4-status <?=$statusClass?>"><i></i><?=$statusLabel?></span></td><td><div class="tdcontact4-actions"><a href="<?=APP_URL?>/clients/<?=(int)$row['id']?>" title="Abrir cliente"><i class="fa-regular fa-folder-open"></i></a><button type="button" data-contact-schedule='<?=e(json_encode($schedulePayload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?>' title="<?=$nextDue?'Reagendar':'Agendar'?>"><i class="fa-regular fa-calendar-plus"></i></button></div></td></tr><?php endforeach;?></tbody></table></div></section>

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
     <?php foreach($drafts as $d):?><div class="tdo-draft-row"><div class="tdo-draft-main"><strong><?=e($d['client_name']??'Pedido sem cliente definido')?></strong><small>Salvo por <?=e($d['author_name']??'—')?> · atualizado <?=date('d/m/Y H:i',strtotime($d['updated_at']))?></small></div><div><span>Vendedor</span><strong><?=e($d['seller_name']??($d['seller_omie_code']??'Não definido'))?></strong></div><div><span>Total estimado</span><strong><?=money($d['total'])?></strong></div><div class="tdo-draft-actions"><a class="tdo-btn" href="<?=APP_URL?>/orders/new?draft_id=<?=(int)$d['id']?>"><i class="fa-regular fa-pen-to-square"></i>Continuar</a><form method="post" action="<?=APP_URL?>/orders/drafts/<?=(int)$d['id']?>/delete"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="tdo-icon-btn danger" type="submit" data-confirm="Excluir este rascunho local?"><i class="fa-regular fa-trash-can"></i></button></form></div></div><?php endforeach;?>
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

     <?php if(!$orders):?>
      <div class="tdo-empty"><span><i class="fa-solid fa-receipt"></i></span><div><strong><?=$view==='budget'?'Nenhum pedido em orçamento neste período':'Nenhum pedido encontrado neste período'?></strong><p><?=$view==='budget'?'Não há propostas para analisar. Escolha outro período ou volte aos pedidos confirmados.':'Escolha outro período ou atualize a sincronização de pedidos.'?></p></div><a class="tdo-btn" href="<?=APP_URL?>/sync"><i class="fa-solid fa-arrows-rotate"></i>Sincronização</a></div>
     <?php else:?>
      <div class="tdo-table-head"><div class="tdo-table-title"><span><i class="fa-solid <?=$view==='budget'?'fa-magnifying-glass-chart':'fa-table-list'?>"></i></span><div><strong><?=$view==='budget'?'Fila de propostas':'Pedidos sincronizados'?></strong><small><?=$view==='budget'?'Revise cliente, vendedor, etapa, data e valor.':'Use a busca para localizar pedido, cliente, vendedor, etapa ou status.'?></small></div></div><?php if($withoutSeller>0):?><div class="tdo-warning"><i class="fa-solid fa-triangle-exclamation"></i><?=$withoutSeller?> pedido(s) sem vendedor</div><?php endif;?></div>
      <div class="table-card tdo-table-wrap">
       <table class="table orders-datatable tdo-table" data-page-length="10" data-length-change="1" data-order-column="3" data-order-direction="desc">
        <thead><tr><th>Pedido</th><th>Cliente</th><th>Vendedor</th><th>Data</th><th>Etapa</th><th>Status</th><th class="text-end">Valor</th><th class="text-end" data-dt-order="disable">Ações</th></tr></thead>
        <tbody><?php foreach($orders as $o):$status=(string)($o['status']??'ATIVO');$upper=mb_strtoupper($status);$statusClass=str_contains($upper,'CANCEL')?'cancelled':(str_contains($upper,'FATUR')?'billed':(in_array((string)($o['stage_code']??''),$budgetCodes??['00','10'],true)?'budget':'active'));?><tr>
         <td><a class="tdo-order-cell" href="<?=APP_URL?>/orders/<?=(int)$o['id']?>"><span class="tdo-order-icon"><i class="fa-solid fa-receipt"></i></span><span><strong><?=e($o['number']??'—')?></strong><small><?=e($o['omie_code'])?></small></span></a></td>
         <td><strong><?=e($o['client_name']??($o['client_omie_code']??'—'))?></strong><?php if(!empty($o['client_name'])&&!empty($o['client_omie_code'])):?><small><?=e($o['client_omie_code'])?></small><?php endif;?></td>
         <td><?php if(!empty($o['seller_name'])):?><strong><?=e($o['seller_name'])?></strong><small><?=e($o['seller_omie_code'])?></small><?php elseif(!empty($o['seller_omie_code'])):?><strong><?=e($o['seller_omie_code'])?></strong><small>código do vendedor</small><?php else:?><span class="tdo-no-seller"><i class="fa-solid fa-circle-exclamation"></i>Sem vendedor</span><?php endif;?></td>
         <td data-order="<?=e((string)$o['order_date'])?>"><?=brdate($o['order_date'])?></td>
         <td><span class="tdo-stage"><strong><?=e($o['stage_name']??($o['stage_code']??'—'))?></strong><?php if(!empty($o['stage_name'])&&!empty($o['stage_code'])):?><small><?=e($o['stage_code'])?></small><?php endif;?></span></td>
         <td><span class="tdo-status <?=$statusClass?>"><?=e($status)?></span></td>
         <td class="text-end"><strong><?=money($o['total'])?></strong></td>
         <td><div class="tdo-actions"><a class="tdo-icon-btn" href="<?=APP_URL?>/orders/<?=(int)$o['id']?>" title="Visualizar"><i class="fa-regular fa-eye"></i></a><?php if($statusClass==='budget'):?><a class="tdo-icon-btn" href="<?=APP_URL?>/orders/<?=(int)$o['id']?>/edit" title="Editar proposta"><i class="fa-regular fa-pen-to-square"></i></a><?php endif;?><a class="tdo-icon-btn" href="<?=APP_URL?>/orders/<?=(int)$o['id']?>/duplicate" title="Duplicar"><i class="fa-regular fa-copy"></i></a><?php if(Auth::can('admin')):?><form method="post" action="<?=APP_URL?>/orders/<?=(int)$o['id']?>/delete"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="tdo-icon-btn danger" type="submit" title="Excluir" data-confirm="Excluir definitivamente o pedido <?=e($o['number']??$o['omie_code'])?> da Omie e do CRM?"><i class="fa-regular fa-trash-can"></i></button></form><?php endif;?></div></td>
        </tr><?php endforeach;?></tbody>
       </table>
      </div>
     <?php endif;?>
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
     <div class="tdo-head-actions"><?php if($statusClass==='budget'):?><a class="tdo-btn tdo-btn-primary" href="<?=APP_URL?>/orders/<?=(int)$order['id']?>/edit"><i class="fa-regular fa-pen-to-square"></i>Editar proposta</a><?php endif;?><a class="tdo-btn" href="<?=APP_URL?>/orders/<?=(int)$order['id']?>/duplicate"><i class="fa-regular fa-copy"></i>Duplicar</a><?php if(Auth::can('admin')):?><form method="post" action="<?=APP_URL?>/orders/<?=(int)$order['id']?>/delete"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><button class="tdo-btn tdo-btn-danger" type="submit" data-confirm="Excluir definitivamente este pedido da Omie e do CRM?"><i class="fa-regular fa-trash-can"></i>Excluir</button></form><?php endif;?></div>
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
     <?php if(!$rows):?>
      <div class="tds-empty"><span><i class="fa-solid fa-magnifying-glass-chart"></i></span><div><strong>Nenhuma ordem de serviço encontrada neste período</strong><p>Selecione outro período ou sincronize Serviços novamente na Central de Sincronização.</p></div><a class="tds-btn" href="<?=APP_URL?>/sync"><i class="fa-solid fa-arrows-rotate"></i>Sincronização</a></div>
     <?php else:?>
      <div class="table-card tds-table-wrap">
       <table class="table services-datatable tds-table" data-page-length="10" data-length-change="1">
        <thead><tr><th>OS</th><th>Cliente</th><th>Vendedor</th><th>Data</th><th>Status</th><th class="text-end">Valor</th></tr></thead>
        <tbody><?php foreach($rows as $row):$status=(string)($row['status']??'ATIVO');$upper=mb_strtoupper($status);$statusClass=str_contains($upper,'CANCEL')?'cancelled':(str_contains($upper,'FATUR')?'billed':'active');?><tr>
         <td><div class="tds-os-cell"><span class="tds-os-icon"><i class="fa-solid fa-screwdriver-wrench"></i></span><span><strong><?=e($row['omie_code'])?></strong><small>código Omie</small></span></div></td>
         <td><strong><?=e($row['client_name']??($row['client_omie_code']??'—'))?></strong><?php if(!empty($row['client_name'])&&!empty($row['client_omie_code'])):?><small><?=e($row['client_omie_code'])?></small><?php endif;?></td>
         <td><?php $effectiveSeller=(string)($row['effective_seller_code']??$row['seller_omie_code']??'');if(!empty($row['seller_name'])):?><strong><?=e($row['seller_name'])?></strong><small><?=e($effectiveSeller)?></small><?php elseif($effectiveSeller!==''):?><strong><?=e($effectiveSeller)?></strong><small>código do vendedor</small><?php else:?><span class="tds-no-seller"><i class="fa-solid fa-circle-exclamation"></i>Sem vendedor</span><?php endif;?></td>
         <td data-order="<?=e((string)($row['effective_date']??$row['service_date']))?>"><?=brdate($row['effective_date']??$row['service_date'])?></td>
         <td><span class="tds-status <?=$statusClass?>"><?=e($status)?></span></td>
         <td class="text-end"><strong><?=money($row['total'])?></strong></td>
        </tr><?php endforeach;?></tbody>
       </table>
      </div>
     <?php endif;?>
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
        <div class="freight-field-carrier"><label>Transportadora</label><select class="form-select" name="carrier_code"><option value="">Sem transportadora</option><?php foreach($carriers??[] as $carrier):?><option value="<?=e((string)$carrier['omie_code'])?>" <?=((string)($old['carrier_code']??''))===(string)$carrier['omie_code']?'selected':''?>><?=e((string)$carrier['name'])?><?=!empty($carrier['city'])?' • '.e((string)$carrier['city']).(!empty($carrier['uf'])?' / '.e((string)$carrier['uf']):''):''?></option><?php endforeach;?></select><?php if(empty($carriers)):?><small class="field-hint">Nenhuma transportadora habilitada. Configure em Configurações.</small><?php endif;?></div>
        <div class="freight-field-mode"><label>Tipo do frete</label><select class="form-select" name="freight_mode"><?php foreach(['9'=>'Sem frete','0'=>'CIF • remetente','1'=>'FOB • destinatário','2'=>'Terceiros','3'=>'Próprio • remetente','4'=>'Próprio • destinatário'] as $k=>$v):?><option value="<?=$k?>" <?=$selectedFreightMode===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select></div>
        <div class="freight-field-volumes"><label>Quantidade de volumes</label><input class="form-control" type="number" min="0" step="1" name="volumes" value="<?=e((string)($old['volumes']??''))?>"></div>
        <div class="freight-field-weight"><label>Peso líquido (kg) <small>somado dos itens</small></label><input class="form-control" id="orderNetWeight" name="net_weight" inputmode="decimal" placeholder="0,000" data-manual="<?=isset($old['net_weight'])&&$old['net_weight']!==''?'1':'0'?>" value="<?=e((string)($old['net_weight']??''))?>"></div>
        <div class="freight-field-weight"><label>Peso bruto (kg) <small>somado dos itens</small></label><input class="form-control" id="orderGrossWeight" name="gross_weight" inputmode="decimal" placeholder="0,000" data-manual="<?=isset($old['gross_weight'])&&$old['gross_weight']!==''?'1':'0'?>" value="<?=e((string)($old['gross_weight']??''))?>"></div>
       </div>
       <div class="freight-api-placeholder"><i class="fa-solid fa-truck-fast"></i><div><strong>Cotação de frete</strong><span>O endpoint será integrado aqui usando o destino do cliente, a transportadora, os volumes e os pesos informados.</span></div><button type="button" class="btn btn-outline-secondary" disabled>Em breve</button></div>
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
   $collectionTotal=count($rows);$collectionAmount=0.0;$collectionOverdue=0;$collectionCritical=0;$collectionCurrent=0;
   foreach($rows as $rr){$collectionAmount+=(float)($rr['open_amount']??0);$days=(int)($rr['max_overdue_days']??0);if($days>0)$collectionOverdue++;if($days>60)$collectionCritical++;if($days<=0)$collectionCurrent++;}
   $topAttention=$rows;usort($topAttention,static fn($a,$b)=>((int)$b['max_overdue_days']<=> (int)$a['max_overdue_days']) ?: ((float)$b['open_amount']<=> (float)$a['open_amount']));
   ?>
   <section class="tdcob4-page">
    <header class="tdcob4-head"><div><span class="tdcob4-kicker">FINANCEIRO / COBRANÇA</span><h1>Carteira de cobrança</h1><p>Acompanhe títulos em aberto, organize ações e aumente a recuperação da sua carteira.</p></div><?php if(Auth::can('admin','supervisor')):?><a class="tdcob4-primary" href="<?=APP_URL?>/collection/recoveries"><i class="fa-solid fa-money-bill-transfer"></i>Lançar recuperação</a><?php endif;?></header>
    <?php if(!empty($flash)):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>

    <section class="tdcob4-filters"><form method="get"><input type="hidden" name="view" value="<?=e($view)?>"><?php if($u['role']!=='collector'):?><label><span>Responsável pela cobrança</span><select class="form-select" name="assigned_user_id"><option value="0">Todos os responsáveis</option><?php foreach($collectionCollectors??[] as $collector):?><option value="<?=(int)$collector['id']?>" <?=((int)($collectionAssigned??0)===(int)$collector['id'])?'selected':''?>><?=e($collector['name'])?></option><?php endforeach;?></select></label><?php endif;?><label><span>Faixa de atraso</span><select class="form-select" name="delay"><?php foreach(['all'=>'Todos','current'=>'Em dia','1_30'=>'1 a 30 dias','31_60'=>'31 a 60 dias','60_plus'=>'Acima de 60 dias'] as $key=>$label):?><option value="<?=$key?>" <?=($collectionDelay??'all')===$key?'selected':''?>><?=$label?></option><?php endforeach;?></select></label><label><span>UF</span><select class="form-select" name="uf"><option value="">Todas</option><?php foreach($collectionUfs??[] as $ufRow):?><option value="<?=e($ufRow['uf'])?>" <?=($collectionUf??'')===$ufRow['uf']?'selected':''?>><?=e($ufRow['uf'])?></option><?php endforeach;?></select></label><a class="tdcob4-clear" href="<?=APP_URL?>/collection?view=<?=e($view)?>">Limpar filtros</a><button class="tdcob4-primary" type="submit"><i class="fa-solid fa-filter"></i>Aplicar filtros</button></form></section>

    <div class="tdcob4-kpis">
     <article><span class="green"><i class="fa-solid fa-dollar-sign"></i></span><div><small>Saldo em aberto</small><strong><?=money($collectionAmount)?></strong><em><?=number_format($collectionTotal,0,',','.')?> cliente(s)</em></div></article>
     <article><span class="red"><i class="fa-regular fa-clock"></i></span><div><small>Títulos em atraso</small><strong><?=number_format($collectionOverdue,0,',','.')?></strong><em><?=number_format($collectionCritical,0,',','.')?> acima de 60 dias</em></div></article>
     <article><span class="blue"><i class="fa-solid fa-chart-column"></i></span><div><small>Recuperado no mês</small><strong><?=money($collectionRecovered??0)?></strong><em>Pagamentos registrados</em></div></article>
     <article><span class="yellow"><i class="fa-regular fa-calendar"></i></span><div><small>Promessas de pagamento</small><strong><?=number_format((int)($collectionPromises??0),0,',','.')?></strong><em>Promessas futuras</em></div></article>
    </div>

    <section class="tdcob4-panel"><header><div><span><i class="fa-solid fa-table-list"></i></span><div><strong><?=$view==='settled'?'Clientes quitados':'Títulos em cobrança'?></strong><small>Lista de clientes com saldo, atraso e próxima ação.</small></div></div><nav><a class="<?=$view==='open'?'active':''?>" href="<?=APP_URL?>/collection?view=open">Pendentes</a><a class="<?=$view==='settled'?'active':''?>" href="<?=APP_URL?>/collection?view=settled">Quitados</a></nav></header><div class="table-card tdcob4-table-wrap"><table class="table tdcob4-table" data-page-length="10" data-length-change="1"><thead><tr><th>Cliente / CFC</th><th>Vendedor</th><th>Resp. cobrança</th><th>Dias em atraso</th><th>Valor em aberto</th><th>Último contato</th><th>Próxima ação</th><th>Status</th><th data-dt-order="disable">Ações</th></tr></thead><tbody><?php foreach($rows as $row):$days=(int)($row['max_overdue_days']??0);$statusClass=$days>60?'danger':($days>30?'warning':($days>0?'today':'ok'));?><tr><td><div class="tdcob4-client"><span><?=e(mb_strtoupper(mb_substr((string)$row['name'],0,1)))?></span><div><strong><?=e($row['name'])?></strong><small><?=e(($row['city']??'').(!empty($row['uf'])?' / '.$row['uf']:''))?></small></div></div></td><td><?=e($row['seller_name']??'—')?></td><td><strong><?=e($row['assigned_name']??'Não atribuído')?></strong></td><td data-order="<?=$days?>"><strong class="<?=$days>30?'danger':''?>"><?=$days>0?$days.' dias':'Em dia'?></strong></td><td data-order="<?=e((string)$row['open_amount'])?>"><strong><?=money($row['open_amount'])?></strong><?php if((float)$row['partial_paid']>0):?><small><?=money($row['partial_paid'])?> pago</small><?php endif;?></td><td><?php if(!empty($row['last_contact_at'])):?><strong><?=date('d/m/Y',strtotime((string)$row['last_contact_at']))?></strong><small><?=e($row['last_channel']??'contato')?></small><?php else:?>—<?php endif;?></td><td><?php if(!empty($row['next_due_at'])):?><strong><?=e($row['next_title']??'Retorno')?></strong><small><?=date('d/m H:i',strtotime((string)$row['next_due_at']))?></small><?php else:?>—<?php endif;?></td><td><span class="tdcob4-status <?=$statusClass?>"><i></i><?=$days>60?'Crítico':($days>30?'Atenção':($days>0?'Em atraso':'Em dia'))?></span></td><td><a class="tdcob4-open" href="<?=APP_URL?>/collection/<?=$row['client_id']?>"><i class="fa-regular fa-folder-open"></i></a></td></tr><?php endforeach;?></tbody></table></div></section>

    <div class="tdcob4-bottom">
     <section class="tdcob4-radar"><header><span><i class="fa-solid fa-bullseye"></i></span><div><strong>Radar da cobrança</strong><small>Distribuição da carteira por nível de atenção.</small></div></header><div class="tdcob4-radar-body"><div class="tdcob4-ring" style="--p:<?=$collectionTotal?round($collectionOverdue/$collectionTotal*100):0?>"><strong><?=$collectionTotal?round($collectionOverdue/$collectionTotal*100):0?>%</strong><small>em atraso</small></div><div class="tdcob4-legend"><span><i class="danger"></i>Crítico (>60 dias)<b><?=$collectionCritical?></b></span><span><i class="warning"></i>Atenção (1–60 dias)<b><?=max(0,$collectionOverdue-$collectionCritical)?></b></span><span><i class="ok"></i>Em dia<b><?=$collectionCurrent?></b></span></div></div></section>
     <section class="tdcob4-attention"><header><span><i class="fa-solid fa-triangle-exclamation"></i></span><div><strong>Clientes que exigem atenção</strong><small>Maiores atrasos da carteira atual.</small></div></header><div><?php foreach(array_slice($topAttention,0,5) as $idx=>$row):?><a href="<?=APP_URL?>/collection/<?=$row['client_id']?>"><span><?=($idx+1)?></span><strong><?=e($row['name'])?></strong><b><?=money($row['open_amount'])?></b><em><?=(int)$row['max_overdue_days']?> dias</em></a><?php endforeach;?></div></section>
    </div>
   </section>
  <?php break;

  case 'collection_recoveries':
   $recoveryDate=(string)($old['recovery_date']??$defaults['recovery_date']??date('Y-m-d'));$assignedDefault=(int)($old['assigned_user_id']??$defaults['assigned_user_id']??0);$oldClientId=(int)($old['client_id']??0);?>
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
      <div class="table-card tdcob-table-wrap"><table class="table tdcob-table collection-recoveries-datatable" data-page-length="10" data-length-change="1" data-order-column="3" data-order-direction="desc"><thead><tr><th>Código</th><th>Cliente</th><th class="text-end">Valor</th><th>Data</th><th>Meta de</th><th>Incluído por</th></tr></thead><tbody><?php foreach($recoveries as $recovery):?><tr><td><strong><?=e($recovery['client_integration_code']?:$recovery['omie_code'])?></strong><small><?php if(!empty($recovery['client_integration_code'])):?>Omie <?=e($recovery['omie_code'])?><?php endif;?></small></td><td><strong><?=e($recovery['name'])?></strong><small><?=e($recovery['document']??'')?></small></td><td class="text-end"><strong><?=money($recovery['amount'])?></strong></td><td><?=date('d/m/Y',strtotime($recovery['created_at']))?></td><td><?=e($recovery['assigned_name']??'—')?></td><td><strong><?=e($recovery['author_name']??'—')?></strong><?php if(!empty($recovery['notes'])):?><small><?=e($recovery['notes'])?></small><?php endif;?></td></tr><?php endforeach;?></tbody></table></div>
     </section>
    </div>
   </section>
   <script>window.COLLECTION_RECOVERY_OLD_CLIENT=<?=$oldClientId?>;</script>
  <?php break;

  case 'collection_case':?>
   <section class="tdcob-page tdcob4-case-page">
    <header class="tdcob-head">
     <div class="tdcob-head-main"><span class="tdcob-head-icon"><i class="fa-solid fa-file-invoice-dollar"></i></span><div><a class="tdcob-kicker" href="<?=APP_URL?>/collection"><i class="fa-solid fa-arrow-left"></i> COBRANÇA / CLIENTE</a><h1><?=e($case['name'])?></h1><p><?=money($case['open_amount'])?> em aberto · <?=$case['max_overdue_days']?> dias de atraso</p></div></div>
    </header>

    <?php if(!empty($flash)):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>

    <div class="tdcob-case-summary">
     <div><span>Saldo em aberto</span><strong><?=money($case['open_amount'])?></strong></div>
     <div><span>Maior atraso</span><strong><?=$case['max_overdue_days']?> dias</strong></div>
     <div><span>Responsável</span><strong><?=e($case['assigned_name']??'Não atribuído')?></strong></div>
     <div><span>Cliente</span><strong><?=e($case['name'])?></strong></div>
    </div>

    <?php if($collectors):?><div class="tdcob-assign"><div><span class="tdcob-kicker">RESPONSABILIDADE</span><strong><?=e($case['assigned_name']??'Não atribuído')?></strong><small>Transferir move histórico, meta e retornos pendentes.</small></div><form method="post" action="<?=APP_URL?>/collection/<?=$case['client_id']?>/assign"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><select class="form-select" name="assigned_user_id" required><option value="">Novo responsável...</option><?php foreach($collectors as $c):?><option value="<?=$c['id']?>"><?=e($c['name'])?></option><?php endforeach;?></select><button class="tdcob-btn">Transferir tudo</button></form></div><?php endif;?>

    <div class="tdcob-grid">
     <section class="tdcob-card"><div class="tdcob-card-head"><span><i class="fa-solid fa-headset"></i></span><div><strong>Registrar ação</strong><small>Registre o resultado e, se necessário, programe o próximo passo.</small></div></div><form method="post" action="<?=APP_URL?>/collection/<?=$case['client_id']?>/action" class="tdcob-form"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><?php if($collectors):?><label>Responsável</label><select class="form-select" name="assigned_user_id"><option value="">Manter atual</option><?php foreach($collectors as $c):?><option value="<?=$c['id']?>" <?=(int)$case['assigned_user_id']===(int)$c['id']?'selected':''?>><?=e($c['name'])?></option><?php endforeach;?></select><?php endif;?><label>Canal</label><select class="form-select" name="channel"><option value="phone">Ligação</option><option value="whatsapp">WhatsApp</option><option value="email">E-mail</option></select><label>Resultado</label><select class="form-select" name="result"><?php foreach($taskResults??[] as $resultOption):?><option value="<?=e($resultOption['code'])?>"><?=e($resultOption['label'])?></option><?php endforeach;?></select><label>Valor</label><input class="form-control" name="amount"><label>Data e hora do retorno</label><input class="form-control" type="datetime-local" name="promise_at"><label>Anotação</label><textarea class="form-control" name="notes" rows="4"></textarea><button class="tdcob-btn tdcob-btn-primary w-100"><i class="fa-solid fa-check"></i>Salvar ação</button></form></section>

     <section class="tdcob-card"><div class="tdcob-card-head"><span><i class="fa-solid fa-clock-rotate-left"></i></span><div><strong>Histórico</strong><small>Interações realizadas neste cliente.</small></div></div><div class="tdcob-timeline"><?php $resultLabels=$taskResultLabels??[];foreach($actions as $a):?><div><strong><?=e($resultLabels[$a['result']]??$a['result'])?><?php if((float)$a['amount']>0):?> · <?=money($a['amount'])?><?php endif;?></strong><small>Feito por <?=e($a['author_name'])?> · responsável <?=e($a['assigned_name'])?> · <?=date('d/m/Y H:i',strtotime($a['created_at']))?></small><?php if($a['notes']):?><p><?=nl2br(e($a['notes']))?></p><?php endif;?></div><?php endforeach;?><?php if(!$actions):?><div>Nenhuma ação registrada.</div><?php endif;?></div></section>
    </div>
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
   $collectionAgenda=(($agendaType??'all')==='collection'||$u['role']==='collector');
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
       <span class="tda-kicker"><?=!empty($teamAgenda)?'GESTÃO / AGENDA':($u['role']==='collector'?'COBRANÇA / AGENDA':'AGENDA')?></span>
       <h1><?=!empty($teamAgenda)?'Agenda da equipe':($u['role']==='collector'?'Agenda de cobrança':'Minha agenda')?></h1>
       <p><?=!empty($teamAgenda)?'Controle os compromissos da equipe, identifique atrasos e acompanhe a carga por responsável.':'Organize retornos, cobranças e próximos contatos sem perder prioridades.'?></p>
      </div>
     </div>
     <div class="tda-date"><i class="fa-regular fa-calendar"></i><div><strong><?=date('d/m/Y')?></strong><small><?=$agendaTotal?> pendente(s)</small></div></div>
    </header>

    <?php if(!empty($flash)):?><div class="alert alert-<?=e($flash['type']??'success')?>"><?=e($flash['message']??'')?></div><?php endif;?>

    <section class="tda-controlbar">
     <form method="get" class="tda-filters">
      <?php if(!empty($teamAgenda)):?>
       <label><span>Responsável</span><select class="form-select" name="user_id">
        <option value="0">Toda a equipe</option>
        <?php foreach($agendaUsers??[] as $agendaUser):?><option value="<?=(int)$agendaUser['id']?>" <?=((int)($agendaFilterUser??0)===(int)$agendaUser['id'])?'selected':''?>><?=e($agendaUser['name'])?> · <?=e($agendaRoleLabels[$agendaUser['role']]??$agendaUser['role'])?></option><?php endforeach;?>
       </select></label>
      <?php endif;?>
      <label><span>Tipo</span><select class="form-select" name="type">
       <option value="all" <?=($agendaType??'all')==='all'?'selected':''?>>Todos</option>
       <option value="sales" <?=($agendaType??'all')==='sales'?'selected':''?>>Comercial</option>
       <option value="collection" <?=($agendaType??'all')==='collection'?'selected':''?>>Cobrança</option>
      </select></label>
      <label><span>Período</span><select class="form-select" name="period">
       <option value="all" <?=($agendaPeriod??'all')==='all'?'selected':''?>>Todos os pendentes</option>
       <option value="late" <?=($agendaPeriod??'all')==='late'?'selected':''?>>Somente vencidos</option>
       <option value="today" <?=($agendaPeriod??'all')==='today'?'selected':''?>>Somente hoje</option>
       <option value="next7" <?=($agendaPeriod??'all')==='next7'?'selected':''?>>Próximos 7 dias</option>
       <option value="upcoming" <?=($agendaPeriod??'all')==='upcoming'?'selected':''?>>Todos os próximos</option>
      </select></label>
      <button class="tda-btn tda-btn-primary" type="submit"><i class="fa-solid fa-filter"></i>Aplicar</button>
      <a class="tda-btn" href="<?=APP_URL?>/agenda"><i class="fa-solid fa-rotate-left"></i>Limpar</a>
     </form>
    </section>

    <div class="tda-kpis">
     <a class="tda-kpi red <?=($agendaPeriod??'all')==='late'?'active':''?>" href="<?=APP_URL?>/agenda?<?=e(http_build_query($agendaQueryBase+['period'=>'late']))?>"><span><i class="fa-solid fa-triangle-exclamation"></i></span><small>Vencidos</small><strong><?=number_format($agendaLate,0,',','.')?></strong><em>Prioridade imediata</em></a>
     <a class="tda-kpi yellow <?=($agendaPeriod??'all')==='today'?'active':''?>" href="<?=APP_URL?>/agenda?<?=e(http_build_query($agendaQueryBase+['period'=>'today']))?>"><span><i class="fa-regular fa-clock"></i></span><small>Hoje</small><strong><?=number_format($agendaTodayCount,0,',','.')?></strong><em>Agenda do dia</em></a>
     <a class="tda-kpi blue <?=($agendaPeriod??'all')==='upcoming'?'active':''?>" href="<?=APP_URL?>/agenda?<?=e(http_build_query($agendaQueryBase+['period'=>'upcoming']))?>"><span><i class="fa-regular fa-calendar-plus"></i></span><small>Próximos</small><strong><?=number_format($agendaUpcoming,0,',','.')?></strong><em>Planejamento</em></a>
     <a class="tda-kpi green <?=($agendaType??'all')==='collection'?'active':''?>" href="<?=APP_URL?>/agenda?<?=e(http_build_query(array_filter(['user_id'=>!empty($teamAgenda)?(int)($agendaFilterUser??0):null,'type'=>'collection'],static fn($value)=>$value!==null&&$value!==0)))?>"><span><i class="fa-solid fa-hand-holding-dollar"></i></span><small>Cobrança</small><strong><?=number_format($agendaCollection,0,',','.')?></strong><em>Compromissos financeiros</em></a>
    </div>

    <?php if(!empty($teamAgenda)):?>
     <section class="tda-team">
      <div class="tda-section-head"><div><span><i class="fa-solid fa-people-group"></i></span><div><strong>Carga da equipe</strong><small>Distribuição dos compromissos pendentes por responsável.</small></div></div></div>
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

    <section class="tda-list">
     <div class="tda-list-head"><div class="tda-list-title"><span><i class="fa-solid fa-list-check"></i></span><div><strong>Compromissos</strong><small>Abra o cliente, edite a descrição, reagende ou conclua o retorno.</small></div></div><span class="tda-list-count"><?=count($rows)?> exibido(s)</span></div>

     <?php if(!$rows):?>
      <div class="tda-empty"><span><i class="fa-solid fa-circle-check"></i></span><div><strong>Nenhum compromisso encontrado</strong><p>Não existem pendências para os filtros selecionados.</p></div></div>
     <?php else:?>
      <?php foreach(['late'=>['Vencidos','Ação imediata','fa-triangle-exclamation'],'today'=>['Hoje','Compromissos do dia','fa-clock'],'upcoming'=>['Próximos','Planejamento futuro','fa-calendar-days']] as $groupKey=>$groupInfo):?>
       <?php if(!empty($groups[$groupKey])):?>
        <div class="tda-group-head <?=$groupKey?>"><div><i class="fa-solid <?=$groupInfo[2]?>"></i><span><strong><?=$groupInfo[0]?></strong><small><?=$groupInfo[1]?></small></span></div><b><?=count($groups[$groupKey])?></b></div>
        <?php foreach($groups[$groupKey] as $r):
         $due=strtotime((string)$r['due_at']);$isLate=$groupKey==='late';$isCollection=($r['type']??'')==='collection';
         $responsibleInitial=mb_strtoupper(mb_substr((string)($r['assigned_name']??''),0,1));
        ?>
         <article class="tda-item <?=$isLate?'late':''?>">
          <div class="tda-time"><span><i class="fa-regular <?=$isLate?'fa-clock':'fa-calendar'?>"></i></span><div><strong><?=date('H:i',$due)?></strong><small><?=date('d/m/Y',$due)?></small></div></div>
          <div class="tda-client">
           <strong><?=e($r['name'])?></strong>
           <small><?=e($r['title'])?></small>
           <?php if(!empty($teamAgenda)):?><div class="tda-owner"><span><?=$responsibleInitial?></span><b><?=e($r['assigned_name']??'Não identificado')?></b></div><?php endif;?>
          </div>
          <span class="tda-type <?=$isCollection?'collection':''?>"><i class="fa-solid <?=$isCollection?'fa-hand-holding-dollar':'fa-user'?>"></i><?=$isCollection?'Cobrança':'Comercial'?></span>
          <div class="tda-actions">
           <a class="tda-btn tda-btn-open" href="<?=APP_URL?>/<?=$isCollection?'collection':'clients'?>/<?=$r['client_id']?>"><i class="fa-regular fa-folder-open"></i>Abrir</a>
           <details class="tda-reschedule"><summary class="tda-btn tda-btn-edit"><i class="fa-solid fa-pen"></i>Editar</summary><form method="post" action="<?=APP_URL?>/agenda/<?=$r['id']?>/edit"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="user_id" value="<?=(int)($agendaFilterUser??0)?>"><input type="hidden" name="type" value="<?=e($agendaType??'all')?>"><input type="hidden" name="period" value="<?=e($agendaPeriod??'all')?>"><label><span>Descrição</span><input class="form-control" type="text" name="title" value="<?=e($r['title'])?>" maxlength="180" required></label><button class="tda-btn tda-btn-edit" type="submit"><i class="fa-solid fa-check"></i>Salvar descrição</button></form></details>
           <details class="tda-reschedule"><summary class="tda-btn tda-btn-reschedule"><i class="fa-regular fa-calendar-plus"></i>Reagendar</summary><form method="post" action="<?=APP_URL?>/agenda/<?=$r['id']?>/reschedule"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="user_id" value="<?=(int)($agendaFilterUser??0)?>"><input type="hidden" name="type" value="<?=e($agendaType??'all')?>"><input type="hidden" name="period" value="<?=e($agendaPeriod??'all')?>"><label><span>Nova data e horário</span><input class="form-control" type="datetime-local" name="due_at" value="<?=date('Y-m-d\TH:i',$due)?>" required></label><button class="tda-btn tda-btn-reschedule" type="submit"><i class="fa-solid fa-calendar-check"></i>Confirmar horário</button></form></details>
           <form method="post" action="<?=APP_URL?>/agenda/<?=$r['id']?>/done"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="user_id" value="<?=(int)($agendaFilterUser??0)?>"><input type="hidden" name="type" value="<?=e($agendaType??'all')?>"><input type="hidden" name="period" value="<?=e($agendaPeriod??'all')?>"><button class="tda-btn tda-btn-primary" data-confirm="Concluir este compromisso?"><i class="fa-solid fa-check"></i>Concluir</button></form>
           <form method="post" action="<?=APP_URL?>/agenda/<?=$r['id']?>/delete"><input type="hidden" name="_token" value="<?=CSRF::token()?>"><input type="hidden" name="user_id" value="<?=(int)($agendaFilterUser??0)?>"><input type="hidden" name="type" value="<?=e($agendaType??'all')?>"><input type="hidden" name="period" value="<?=e($agendaPeriod??'all')?>"><button class="tda-btn tda-btn-danger" data-confirm="Excluir este compromisso da agenda?"><i class="fa-regular fa-trash-can"></i>Excluir</button></form>
          </div>
         </article>
        <?php endforeach;?>
       <?php endif;?>
      <?php endforeach;?>
     <?php endif;?>
    </section>
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
   $modeLabels=['manual_period'=>'Período escolhido','forced_last_5_days'=>'Últimos 5 dias','incremental_5_days'=>'Últimos 5 dias','catchup_missing_period'=>'Atualizar lacuna','initial_current_year'=>'Carga inicial','manual_full_current_year'=>'Carga completa','initial'=>'Carga inicial','incremental'=>'Incremental'];
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
            <button class="tdsync2-btn primary" data-sync-action="sync" data-module="<?=$key?>"><i class="fa-solid fa-arrows-rotate"></i>Sincronizar agora</button>
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
  'clients'=>['Clientes','Base e carteiras','fa-users'],
  'client_new'=>['Clientes','Cadastro de cliente','fa-user-plus'],
  'client'=>['Clientes','Detalhes do cliente','fa-address-card'],
  'contact_monitoring'=>['Comercial','Contatos e retornos','fa-headset'],
  'orders'=>['Pedidos','Operação comercial','fa-receipt'],
  'order_detail'=>['Pedidos','Detalhes do pedido','fa-file-invoice'],
  'order_new'=>['Pedidos','Novo pedido','fa-cart-plus'],
  'services'=>['Serviços','Ordens de serviço','fa-screwdriver-wrench'],
  'collection'=>['Cobrança','Carteira de cobrança','fa-hand-holding-dollar'],
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
 ?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($GLOBALS['config']['app']['name']??'Tecnodata CRM')?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.datatables.net/3.0.3/css/dataTables.bootstrap5.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" rel="stylesheet"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="<?=APP_URL?>/assets/app.css?v=<?=is_file(APP_ROOT.'/public/assets/app.css')?filemtime(APP_ROOT.'/public/assets/app.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/premium.css?v=<?=is_file(APP_ROOT.'/public/assets/premium.css')?filemtime(APP_ROOT.'/public/assets/premium.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/clients-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/clients-v2.css')?filemtime(APP_ROOT.'/public/assets/clients-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/dashboard-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/dashboard-v2.css')?filemtime(APP_ROOT.'/public/assets/dashboard-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/results-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/results-v2.css')?filemtime(APP_ROOT.'/public/assets/results-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/orders-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/orders-v2.css')?filemtime(APP_ROOT.'/public/assets/orders-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/order-new-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/order-new-v2.css')?filemtime(APP_ROOT.'/public/assets/order-new-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/services-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/services-v2.css')?filemtime(APP_ROOT.'/public/assets/services-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/collection-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/collection-v2.css')?filemtime(APP_ROOT.'/public/assets/collection-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/agenda-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/agenda-v2.css')?filemtime(APP_ROOT.'/public/assets/agenda-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/contact-monitoring-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/contact-monitoring-v2.css')?filemtime(APP_ROOT.'/public/assets/contact-monitoring-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/final-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/final-v2.css')?filemtime(APP_ROOT.'/public/assets/final-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/visual-polish.css?v=<?=is_file(APP_ROOT.'/public/assets/visual-polish.css')?filemtime(APP_ROOT.'/public/assets/visual-polish.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/results-models-v3.css?v=<?=is_file(APP_ROOT.'/public/assets/results-models-v3.css')?filemtime(APP_ROOT.'/public/assets/results-models-v3.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/opportunities-v1.css?v=<?=is_file(APP_ROOT.'/public/assets/opportunities-v1.css')?filemtime(APP_ROOT.'/public/assets/opportunities-v1.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/admin-center-v1.css?v=<?=is_file(APP_ROOT.'/public/assets/admin-center-v1.css')?filemtime(APP_ROOT.'/public/assets/admin-center-v1.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/settings-v3.css?v=<?=is_file(APP_ROOT.'/public/assets/settings-v3.css')?filemtime(APP_ROOT.'/public/assets/settings-v3.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/design-system-v4.css?v=<?=is_file(APP_ROOT.'/public/assets/design-system-v4.css')?filemtime(APP_ROOT.'/public/assets/design-system-v4.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/management-v4.css?v=<?=is_file(APP_ROOT.'/public/assets/management-v4.css')?filemtime(APP_ROOT.'/public/assets/management-v4.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/goals-v1.css?v=<?=is_file(APP_ROOT.'/public/assets/goals-v1.css')?filemtime(APP_ROOT.'/public/assets/goals-v1.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/workspace-v4.css?v=<?=is_file(APP_ROOT.'/public/assets/workspace-v4.css')?filemtime(APP_ROOT.'/public/assets/workspace-v4.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/sync-v2.css?v=<?=is_file(APP_ROOT.'/public/assets/sync-v2.css')?filemtime(APP_ROOT.'/public/assets/sync-v2.css'):time()?>"><link rel="stylesheet" href="<?=APP_URL?>/assets/crm-master-v1.css?v=<?=is_file(APP_ROOT.'/public/assets/crm-master-v1.css')?filemtime(APP_ROOT.'/public/assets/crm-master-v1.css'):time()?>"></head><body data-page="<?=e($page)?>"><?php if(!$u){echo $body;}else{?>
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
     <div class="tdcrm-nav-group" data-nav-group="seller-clients" data-default-open="1"><button class="tdcrm-nav-group-toggle" type="button" aria-expanded="true"><span><i class="fa-solid fa-users"></i>Clientes</span><i class="fa-solid fa-chevron-down"></i></button><div class="tdcrm-nav-group-links"><a href="<?=APP_URL?>/clients"><i class="fa-solid fa-list"></i><span>Lista de clientes</span></a><a href="<?=APP_URL?>/my-portfolio"><i class="fa-solid fa-briefcase"></i><span>Minha carteira</span></a></div></div>
     <div class="tdcrm-nav-group" data-nav-group="seller-sales" data-default-open="1"><button class="tdcrm-nav-group-toggle" type="button" aria-expanded="true"><span><i class="fa-solid fa-cart-shopping"></i>Vendas</span><i class="fa-solid fa-chevron-down"></i></button><div class="tdcrm-nav-group-links"><?php if(sales_flow_enabled()):?><a href="<?=APP_URL?>/opportunities"><i class="fa-solid fa-chart-column"></i><span>Oportunidades</span></a><?php endif;?><a href="<?=APP_URL?>/orders/new"><i class="fa-solid fa-circle-plus"></i><span>Novo pedido</span></a><a href="<?=APP_URL?>/orders"><i class="fa-regular fa-rectangle-list"></i><span>Meus pedidos</span></a></div></div>
     <a href="<?=APP_URL?>/agenda"><i class="fa-regular fa-calendar-check"></i><span>Minha agenda</span></a>
    <?php elseif($u['role']==='collector'):?>
     <a class="tdcrm-nav-home" href="<?=APP_URL?>/"><i class="fa-solid fa-house"></i><span>Meu painel</span></a>
     <div class="tdcrm-nav-group" data-nav-group="collector-collection" data-default-open="1"><button class="tdcrm-nav-group-toggle" type="button" aria-expanded="true"><span><i class="fa-solid fa-hand-holding-dollar"></i>Cobrança</span><i class="fa-solid fa-chevron-down"></i></button><div class="tdcrm-nav-group-links"><a href="<?=APP_URL?>/collection"><i class="fa-solid fa-circle-dollar-to-slot"></i><span>Carteira de cobrança</span></a><a href="<?=APP_URL?>/agenda"><i class="fa-regular fa-calendar-check"></i><span>Minha agenda</span></a></div></div>
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
       <a href="<?=APP_URL?>/contact-monitoring"><i class="fa-solid fa-headset"></i><span>Contatos e retornos</span></a>
       <a href="<?=APP_URL?>/orders"><i class="fa-regular fa-rectangle-list"></i><span>Pedidos</span></a>
       <a href="<?=APP_URL?>/services"><i class="fa-solid fa-screwdriver-wrench"></i><span>Serviços</span></a>
      </div>
     </div>

     <div class="tdcrm-nav-group" data-nav-group="collection">
      <button class="tdcrm-nav-group-toggle" type="button" aria-expanded="false"><span><i class="fa-solid fa-circle-dollar-to-slot"></i>Cobrança</span><i class="fa-solid fa-chevron-down"></i></button>
      <div class="tdcrm-nav-group-links">
       <a href="<?=APP_URL?>/collection"><i class="fa-solid fa-hand-holding-dollar"></i><span>Carteira de cobrança</span></a>
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
