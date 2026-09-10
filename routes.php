<?php
function client_portfolio_ddd_map(): array{
 return [
  'AC'=>['68'],'AL'=>['82'],'AP'=>['96'],'AM'=>['92','97'],'BA'=>['71','73','74','75','77'],
  'CE'=>['85','88'],'DF'=>['61'],'ES'=>['27','28'],'GO'=>['62','64'],'MA'=>['98','99'],
  'MT'=>['65','66'],'MS'=>['67'],'MG'=>['31','32','33','34','35','37','38'],'PA'=>['91','93','94'],
  'PB'=>['83'],'PR'=>['41','42','43','44','45','46'],'PE'=>['81','87'],'PI'=>['86','89'],
  'RJ'=>['21','22','24'],'RN'=>['84'],'RS'=>['51','53','54','55'],'RO'=>['69'],'RR'=>['95'],
  'SC'=>['47','48','49'],'SP'=>['11','12','13','14','15','16','17','18','19'],'SE'=>['79'],'TO'=>['63']
 ];
}
function client_portfolio_ddds(mixed $input,string $uf=''): array{
 $values=is_array($input)?$input:($input===null||$input===''?[]:[$input]);$selected=[];
 foreach($values as $value){$ddd=preg_replace('/\D+/','',(string)$value);if(strlen($ddd)===2)$selected[$ddd]=$ddd;}
 $selected=array_values($selected);sort($selected);
 if($uf!==''&&isset(client_portfolio_ddd_map()[$uf]))$selected=array_values(array_intersect(client_portfolio_ddd_map()[$uf],$selected));
 return $selected;
}
function client_ddd_sql(string $alias='c'): string{
 if(!in_array($alias,['c',''],true))throw new InvalidArgumentException('Alias de cliente inválido.');
 return "LEFT(REGEXP_REPLACE(COALESCE(".($alias!==''?$alias.'.':'')."phone,''),'[^0-9]',''),2)";
}
$router->get('/login',function(){if(Auth::check())redirect('/');render('login');});
$router->post('/login',function(){CSRF::require($_POST['_token']??null);if(Auth::attempt((string)($_POST['email']??''),(string)($_POST['password']??'')))redirect('/');render('login',['error'=>'E-mail ou senha inválidos.']);});
$router->post('/logout',function(){CSRF::require($_POST['_token']??null);Auth::logout();redirect('/login');});

$router->get('/',function(){Auth::requireLogin();$u=Auth::user();render('dashboard',['u'=>$u,'data'=>CRMService::dashboard($u)]);});
$router->get('/result',function(){
 Auth::requireLogin();$month=(string)($_GET['month']??date('Y-m'));
 if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');
 $daysInMonth=(int)date('t',strtotime($month.'-01'));
 $requestedDays=$_GET['days']??[];if(!is_array($requestedDays))$requestedDays=[$requestedDays];
 $selectedDays=[];foreach($requestedDays as $requestedDay){$value=(int)$requestedDay;if($value>=1&&$value<=$daysInMonth)$selectedDays[$value]=$value;}
 $selectedDays=array_values($selectedDays);sort($selectedDays);
 $u=Auth::user();$periodLabel=$selectedDays?'Dias '.implode(', ',array_map(static fn($value)=>str_pad((string)$value,2,'0',STR_PAD_LEFT),$selectedDays)).' de '.date('m/Y',strtotime($month.'-01')):date('m/Y',strtotime($month.'-01'));
 $common=['month'=>$month,'selectedDays'=>$selectedDays,'daysInMonth'=>$daysInMonth,'periodLabel'=>$periodLabel];
 if(in_array($u['role'],['admin','supervisor'],true))render('management_result',$common+['management'=>GoalService::managementMonth($month,$selectedDays)]);
 else render('result',$common+['result'=>GoalService::userMonth(Auth::id(),$month,$selectedDays)]);
});

$router->get('/clients/new',function(){
 Auth::requireRole('admin','supervisor','seller');
 $preview=$_SESSION['client_preview']??null;$error=$_SESSION['client_preview_error']??null;$old=$_SESSION['client_preview_old']??[];
 $createSuccess=$_SESSION['client_create_success']??null;$createError=$_SESSION['client_create_error']??null;$createOld=$_SESSION['client_create_old']??[];
 unset($_SESSION['client_preview'],$_SESSION['client_preview_error'],$_SESSION['client_preview_old'],$_SESSION['client_create_success'],$_SESSION['client_create_error'],$_SESSION['client_create_old']);
 if($createOld)$old=$createOld;
 render('client_new',[
  'preview'=>$preview,'error'=>$error,'old'=>$old,'createSuccess'=>$createSuccess,'createError'=>$createError,
  'sellers'=>Auth::can('admin','supervisor')?DB::all("SELECT omie_code,name FROM sellers WHERE active=1 ORDER BY name"):[]
 ]);
});
$router->post('/clients/preview',function(){
 Auth::requireRole('admin','supervisor','seller');CSRF::require($_POST['_token']??null);
 try{$_SESSION['client_preview']=ClientService::buildOmiePreview($_POST,Auth::user());}
 catch(Throwable $e){$_SESSION['client_preview_error']=$e->getMessage();}
 $_SESSION['client_preview_old']=$_POST;
 redirect('/clients/new');
});

$router->post('/clients/test-create',function(){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 try{
  $test=[
   'legal_name'=>'TECNODATA CLIENTE TESTE LTDA',
   'trade_name'=>'CLIENTE TESTE CRM',
   'document'=>'12345678000195',
   'email'=>'teste.crm@exemplo.com',
   'contact_name'=>'JOAO TESTE',
   'phone_ddd'=>'41',
   'phone_number'=>'999999999',
   'zip_code'=>'80010000',
   'address'=>'RUA XV DE NOVEMBRO',
   'address_number'=>'9999',
   'complement'=>'SALA TESTE',
   'neighborhood'=>'CENTRO',
   'city'=>'CURITIBA',
   'uf'=>'PR',
   'seller_omie_code'=>'',
   'tags'=>'CLIENTE, CFC',
   'notes'=>'Cadastro ficticio criado exclusivamente para validar o fluxo CRM -> Omie.',
  ];
  $existing=DB::one("SELECT id FROM clients WHERE document='12345678000195' AND active=1 LIMIT 1");
  if($existing){redirect('/clients/'.(int)$existing['id']);}
  $result=ClientService::createLocal($test,Auth::user());
  $_SESSION['client_flash']=['type'=>'success','message'=>'Cliente de teste criado somente no CRM. Agora clique em “Sincronizar Omie” para validar a integração.'];
  redirect('/clients/'.(int)$result['client']['id']);
 }catch(Throwable $e){
  $_SESSION['clients_flash']=['type'=>'danger','message'=>'Não foi possível preparar o cliente de teste: '.$e->getMessage()];
  redirect('/clients');
 }
});

$router->post('/clients/save-local',function(){
 Auth::requireRole('admin','supervisor','seller');CSRF::require($_POST['_token']??null);
 try{
  $result=ClientService::createLocal($_POST,Auth::user());
  $_SESSION['client_flash']=['type'=>'success','message'=>'Cliente salvo localmente. Agora verifique a situação na Omie antes de concluir a integração.'];
  redirect('/clients/'.(int)$result['client']['id']);
 }catch(Throwable $e){
  $_SESSION['client_create_error']=$e->getMessage();
  $_SESSION['client_create_old']=$_POST;
  redirect('/clients/new');
 }
});

$router->post('/clients/{id}/omie-sync',function($p){
 Auth::requireRole('admin','supervisor','seller');CSRF::require($_POST['_token']??null);$id=(int)$p['id'];
 try{
  $result=ClientService::syncLocalWithOmie($id,Auth::user());
  $_SESSION['client_flash']=['type'=>'success','message'=>$result['message']];
 }catch(Throwable $e){
  $_SESSION['client_flash']=['type'=>'danger','message'=>'Não foi possível concluir a verificação na Omie: '.$e->getMessage()];
 }
 redirect('/clients/'.$id);
});

$router->get('/clients',function(){
 Auth::requireRole('admin','supervisor','seller');
 $u=Auth::user();
 $flash=$_SESSION['clients_flash']??null;unset($_SESSION['clients_flash']);
 $q=trim((string)($_GET['q']??''));
 $clientScope=($u['role']==='seller'&&(string)($_GET['scope']??'mine')==='unassigned')?'unassigned':'mine';
 $uf=mb_strtoupper(trim((string)($_GET['uf']??'')),'UTF-8');
 if($uf!==''&&!preg_match('/^[A-Z]{2}$/',$uf))$uf='';
 $ddds=client_portfolio_ddds($_GET['ddds']??[],$uf);
 $w=['c.active=1'];$p=[];
 if($u['role']==='seller'){
  if($clientScope==='unassigned')$w[]="(c.seller_omie_code IS NULL OR c.seller_omie_code='')";
  else{$w[]='c.seller_omie_code=?';$p[]=trim((string)($u['seller_omie_code']??''))?:'__NO_SELLER_LINK__';}
 }
 if($uf!==''){$w[]='UPPER(TRIM(c.uf))=?';$p[]=$uf;}
 if($ddds){$w[]=client_ddd_sql('c').' IN ('.implode(',',array_fill(0,count($ddds),'?')).')';array_push($p,...$ddds);}
 if($q!==''){$w[]='(c.name LIKE ? OR c.document LIKE ? OR c.city LIKE ?)';$x='%'.$q.'%';array_push($p,$x,$x,$x);}
 $where=implode(' AND ',$w);
 $summary=DB::one(
  "SELECT COUNT(*) total_clients,
          COALESCE(SUM(COALESCE(m.revenue_12m,0)),0) revenue_12m,
          COALESCE(SUM(COALESCE(m.orders_12m,0)),0) orders_12m,
          SUM(CASE WHEN c.seller_omie_code IS NULL OR c.seller_omie_code='' THEN 1 ELSE 0 END) without_seller
   FROM clients c LEFT JOIN client_metrics m ON m.client_id=c.id WHERE ".$where,
  $p
 )?:[];
 $perPage=5;
 $totalClients=(int)($summary['total_clients']??0);
 $totalPages=max(1,(int)ceil($totalClients/$perPage));
 $page=max(1,min($totalPages,(int)($_GET['page']??1)));
 $offset=($page-1)*$perPage;
 $rows=DB::all(
  "SELECT c.*,s.name seller_name,m.last_purchase_at,m.revenue_12m,m.orders_12m,m.avg_interval_days
   FROM clients c LEFT JOIN client_metrics m ON m.client_id=c.id
   LEFT JOIN sellers s ON s.omie_code=c.seller_omie_code
   WHERE ".$where." ORDER BY c.name LIMIT ".$perPage." OFFSET ".$offset,
  $p
 );
 foreach($rows as &$r){
  $r['cycle']=CRMService::cycle($r['last_purchase_at']??null,(float)($r['avg_interval_days']??0));
 }
 unset($r);
 $availableClients=$u['role']==='seller'?(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1 AND (seller_omie_code IS NULL OR seller_omie_code='')")??0):0;
 render('clients',['rows'=>$rows,'q'=>$q,'uf'=>$uf,'ddds'=>$ddds,'clientScope'=>$clientScope,'availableClients'=>$availableClients,'portfolioDddMap'=>client_portfolio_ddd_map(),'flash'=>$flash,'clientStats'=>[
  'total'=>$totalClients,
  'revenue'=>(float)($summary['revenue_12m']??0),
  'orders'=>(int)($summary['orders_12m']??0),
  'without_seller'=>(int)($summary['without_seller']??0),
 ],'clientPagination'=>[
  'page'=>$page,'pages'=>$totalPages,'per_page'=>$perPage,
  'from'=>$totalClients?($offset+1):0,'to'=>min($offset+$perPage,$totalClients),
 ],'portfolioSellers'=>Auth::can('admin','supervisor')?DB::all("SELECT omie_code,name FROM sellers WHERE active=1 ORDER BY name"):[],
 'portfolioSourceSellers'=>Auth::can('admin','supervisor')?DB::all("SELECT DISTINCT c.seller_omie_code omie_code,COALESCE(s.name,CONCAT('Código ',c.seller_omie_code)) name,COALESCE(s.active,0) active FROM clients c LEFT JOIN sellers s ON s.omie_code=c.seller_omie_code WHERE c.active=1 AND c.seller_omie_code IS NOT NULL AND c.seller_omie_code<>'' ORDER BY active DESC,name"):[],
 'portfolioStates'=>Auth::can('admin','supervisor')?DB::all("SELECT DISTINCT UPPER(TRIM(uf)) uf FROM clients WHERE active=1 AND uf IS NOT NULL AND TRIM(uf)<>'' ORDER BY uf"):[]]);
});
$router->post('/clients/portfolio/assign',function(){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 try{
  $uf=mb_strtoupper(trim((string)($_POST['uf']??'')),'UTF-8');
  $ddds=client_portfolio_ddds($_POST['ddds']??[],$uf);
  $source=trim((string)($_POST['source_seller']??''));
  $target=trim((string)($_POST['target_seller']??''));
  if(!preg_match('/^[A-Z]{2}$/',$uf))throw new RuntimeException('Selecione um estado válido.');
  if(!$ddds)throw new RuntimeException('Selecione pelo menos um DDD do estado antes de aplicar a carteira.');
  $targetSeller=DB::one("SELECT omie_code,name FROM sellers WHERE omie_code=? AND active=1",[$target]);
  if(!$targetSeller)throw new RuntimeException('Selecione um vendedor de destino válido.');

  $where=['active=1','UPPER(TRIM(uf))=?',client_ddd_sql('').' IN ('.implode(',',array_fill(0,count($ddds),'?')).')'];$whereParams=array_merge([$uf],$ddds);$sourceLabel='todos os clientes';
  if($source==='__unassigned__'){
   $where[]="(seller_omie_code IS NULL OR seller_omie_code='')";$sourceLabel='clientes sem vendedor';
  }elseif($source!=='__all__'){
   $sourceSeller=DB::one("SELECT name FROM sellers WHERE omie_code=?",[$source]);
   $sourceExists=DB::one("SELECT 1 FROM clients WHERE active=1 AND seller_omie_code=? LIMIT 1",[$source]);
   if(!$sourceExists)throw new RuntimeException('Selecione uma carteira atual válida.');
   if($source===$target)throw new RuntimeException('O vendedor atual e o vendedor de destino são iguais.');
   $where[]='seller_omie_code=?';$whereParams[]=$source;$sourceLabel='carteira de '.($sourceSeller['name']??$source);
  }
  $affected=DB::exec("UPDATE clients SET seller_omie_code=?,updated_at=NOW() WHERE ".implode(' AND ',$where),array_merge([$target],$whereParams));
  $_SESSION['clients_flash']=['type'=>$affected>0?'success':'info','message'=>$affected>0
   ?number_format($affected,0,',','.').' cliente(s) de '.$uf.' nos DDDs '.implode(', ',$ddds).' transferido(s) de '.$sourceLabel.' para '.$targetSeller['name'].'.'
   :'Nenhum cliente de '.$uf.' nos DDDs '.implode(', ',$ddds).' corresponde à carteira selecionada.'];
 }catch(Throwable $e){$_SESSION['clients_flash']=['type'=>'danger','message'=>'Não foi possível atualizar a carteira: '.$e->getMessage()];}
 $redirect=['uf'=>$uf];if(!empty($ddds))$redirect['ddds']=$ddds;
 redirect('/clients?'.http_build_query($redirect));
});
$router->get('/clients/{id}/edit',function($p){
 Auth::requireRole('admin','supervisor','seller');$u=Auth::user();$id=(int)$p['id'];
 $client=DB::one("SELECT * FROM clients WHERE id=? AND active=1",[$id]);
 if(!$client){http_response_code(404);exit('Cliente não encontrado.');}
 if($u['role']==='seller'&&(string)$client['seller_omie_code']!==(string)$u['seller_omie_code']){http_response_code(403);exit('Cliente fora da sua carteira.');}
 $old=$_SESSION['client_edit_old']??ClientService::formFromClient($client);
 $error=$_SESSION['client_edit_error']??null;
 unset($_SESSION['client_edit_old'],$_SESSION['client_edit_error']);
 render('client_new',[
  'preview'=>null,'error'=>null,'old'=>$old,'createSuccess'=>null,'createError'=>null,
  'editClient'=>$client,'editError'=>$error,
  'sellers'=>Auth::can('admin','supervisor')?DB::all("SELECT omie_code,name FROM sellers WHERE active=1 ORDER BY name"):[]
 ]);
});
$router->post('/clients/{id}/update',function($p){
 Auth::requireRole('admin','supervisor','seller');CSRF::require($_POST['_token']??null);$id=(int)$p['id'];
 try{
  ClientService::updateInOmie($id,$_POST,Auth::user());
  $_SESSION['client_flash']=['type'=>'success','message'=>'Alterações salvas no CRM. A Omie ainda não foi alterada. Use o botão “Sincronizar Omie” para concluir.'];
  redirect('/clients/'.$id);
 }catch(Throwable $e){
  $_SESSION['client_edit_error']=$e->getMessage();
  $_SESSION['client_edit_old']=$_POST;
  redirect('/clients/'.$id.'/edit');
 }
});
$router->post('/clients/{id}/delete-local',function($p){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);$id=(int)$p['id'];
 try{
  ClientService::deleteLocalOnly($id,Auth::user());
  $_SESSION['clients_flash']=['type'=>'success','message'=>'Cliente removido somente do CRM local. Nenhuma chamada foi feita à Omie.'];
 }catch(Throwable $e){
  $_SESSION['clients_flash']=['type'=>'danger','message'=>'Não foi possível excluir localmente: '.$e->getMessage()];
 }
 redirect('/clients');
});

$router->post('/clients/{id}/delete',function($p){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);$id=(int)$p['id'];
 try{
  $result=ClientService::deleteFromOmie($id,Auth::user());
  $_SESSION['clients_flash']=[
   'type'=>'success',
   'message'=>($result['status']??'')==='local_deleted'
    ?'Cliente local removido com sucesso. Como ainda não estava integrado, nenhuma chamada à Omie foi necessária.'
    :(!empty($result['corrected_code'])
      ?'Cliente localizado pelo CPF/CNPJ, código Omie corrigido e exclusão concluída com sucesso na Omie e no CRM.'
      :'Cliente excluído com sucesso na Omie e removido do CRM local.')
  ];
  redirect('/clients');
 }catch(Throwable $e){
  $_SESSION['client_flash']=['type'=>'danger','message'=>$e->getMessage()];
  redirect('/clients/'.$id);
 }
});

$router->get('/clients/{id}',function($p){
 Auth::requireRole('admin','supervisor','seller');$u=Auth::user();$id=(int)$p['id'];$flash=$_SESSION['client_flash']??null;unset($_SESSION['client_flash']);
 $c=DB::one("SELECT c.*,m.* FROM clients c LEFT JOIN client_metrics m ON m.client_id=c.id WHERE c.id=?",[$id]);
 if(!$c){http_response_code(404);exit('Cliente não encontrado.');}
 $isUnassigned=trim((string)($c['seller_omie_code']??''))==='';
 if($u['role']==='seller'&&!$isUnassigned&&(string)$c['seller_omie_code']!==(string)$u['seller_omie_code']){http_response_code(403);exit('Cliente fora da sua carteira.');}
 $a=DB::all("SELECT a.*,u.name user_name FROM activities a JOIN users u ON u.id=a.user_id WHERE a.client_id=? ORDER BY a.created_at DESC LIMIT 30",[$id]);
 $o=DB::all("SELECT * FROM orders WHERE client_omie_code=? ORDER BY order_date DESC,id DESC LIMIT 20",[$c['omie_code']]);
 $form=ClientService::formFromClient($c);
 $sellerName=$c['seller_omie_code']?DB::scalar("SELECT name FROM sellers WHERE omie_code=?",[(string)$c['seller_omie_code']]):null;
 render('client',['client'=>$c,'activities'=>$a,'orders'=>$o,'cycle'=>CRMService::cycle($c['last_purchase_at']??null,(float)($c['avg_interval_days']??0)),'flash'=>$flash,'formData'=>$form,'sellerName'=>$sellerName,'sharedUnassigned'=>$u['role']==='seller'&&$isUnassigned]);
});
$router->post('/clients/{id}/activity',function($p){Auth::requireRole('admin','supervisor','seller');CSRF::require($_POST['_token']??null);$id=(int)$p['id'];$u=Auth::user();$c=DB::one("SELECT * FROM clients WHERE id=?",[$id]);if(!$c)exit('Cliente inválido.');$unassigned=trim((string)($c['seller_omie_code']??''))==='';if($u['role']==='seller'&&!$unassigned&&(string)$c['seller_omie_code']!==(string)$u['seller_omie_code']){http_response_code(403);exit('Sem permissão.');}DB::exec("INSERT INTO activities(client_id,user_id,channel,result,notes,next_at,created_at) VALUES(?,?,?,?,?,?,NOW())",[$id,(int)$u['id'],(string)($_POST['channel']??'phone'),(string)($_POST['result']??'contact'),trim((string)($_POST['notes']??'')),($_POST['next_at']??'')?:null]);if(!empty($_POST['next_at']))DB::exec("INSERT INTO tasks(client_id,assigned_user_id,type,title,due_at,status,created_at) VALUES(?,?,'sales','Retorno comercial',?,'pending',NOW())",[$id,(int)$u['id'],$_POST['next_at']]);redirect('/clients/'.$id);});

$router->get('/orders',function(){
 Auth::requireRole('admin','supervisor','seller');
 $u=Auth::user();
 $period=selected_date_period();
 $view=(string)($_GET['view']??'all');
 if(!in_array($view,['all','budget'],true))$view='all';
 $budgetCodes=OrderPolicy::budgetStageCodes();
 $stages=DB::all("SELECT code,name,active,raw_json FROM order_stages ORDER BY code");
 $allowedStageCodes=[];
 foreach($stages as $stage){
  $code=(string)$stage['code'];$isBudget=in_array($code,$budgetCodes,true);
  if(($view==='budget'&&$isBudget)||($view==='all'&&!$isBudget))$allowedStageCodes[]=$code;
 }
 $stageFilter=trim((string)($_GET['stage']??''));
 if($stageFilter!==''&&!in_array($stageFilter,$allowedStageCodes,true))$stageFilter='';

 $where=[];$params=[];
 if(!$period['all']){
  $where[]='o.order_date>=?';
  $where[]='o.order_date<?';
  $params[]=$period['from'];$params[]=$period['next'];
 }
 if(($u['role']??'')==='seller'){
  $where[]='o.seller_omie_code=?';
  $params[]=(string)($u['seller_omie_code']??'');
 }
 $baseWhere=$where;
 $baseParams=$params;
 $budgetPlaceholders=implode(',',array_fill(0,count($budgetCodes),'?'));
 if($view==='budget'){
  $where[]='o.stage_code IN ('.$budgetPlaceholders.')';
  array_push($params,...$budgetCodes);
 }else{
  $where[]='(o.stage_code IS NULL OR o.stage_code NOT IN ('.$budgetPlaceholders.'))';
  array_push($params,...$budgetCodes);
 }
 if($stageFilter!==''){$where[]='o.stage_code=?';$params[]=$stageFilter;}
 $sqlWhere=$where?' WHERE '.implode(' AND ',$where):'';

 $orders=DB::all(
  "SELECT o.id,o.omie_code,o.number,o.client_omie_code,o.seller_omie_code,o.order_date,o.forecast_date,o.total,o.status,o.stage_code,
          c.name client_name,s.name seller_name,os.name stage_name
   FROM orders o
   LEFT JOIN clients c ON c.omie_code=o.client_omie_code
   LEFT JOIN sellers s ON s.omie_code=o.seller_omie_code
   LEFT JOIN order_stages os ON os.code=o.stage_code".
   $sqlWhere."
   ORDER BY o.order_date DESC,o.id DESC
   LIMIT 1500",
  $params
 );

 $stats=DB::one(
  "SELECT COUNT(*) total_rows,
          COALESCE(SUM(CASE WHEN UPPER(COALESCE(o.status,'')) NOT LIKE '%CANCEL%' THEN o.total ELSE 0 END),0) total_value,
          SUM(CASE WHEN UPPER(COALESCE(o.status,'')) LIKE '%CANCEL%' THEN 1 ELSE 0 END) cancelled_rows,
          SUM(CASE WHEN UPPER(COALESCE(o.status,'')) LIKE '%FATUR%' THEN 1 ELSE 0 END) billed_rows,
          SUM(CASE WHEN UPPER(COALESCE(o.status,'')) NOT LIKE '%CANCEL%' AND UPPER(COALESCE(o.status,'')) NOT LIKE '%FATUR%' THEN 1 ELSE 0 END) active_rows,
          SUM(CASE WHEN o.seller_omie_code IS NULL OR o.seller_omie_code='' THEN 1 ELSE 0 END) without_seller
   FROM orders o".$sqlWhere,
  $params
 )?:[];

 $baseSqlWhere=$baseWhere?' WHERE '.implode(' AND ',$baseWhere):'';
 $tabStats=DB::one(
  "SELECT COALESCE(SUM(CASE WHEN o.stage_code IS NULL OR o.stage_code NOT IN (".$budgetPlaceholders.") THEN 1 ELSE 0 END),0) total_rows,
          COALESCE(SUM(CASE WHEN o.stage_code IN (".$budgetPlaceholders.") THEN 1 ELSE 0 END),0) budget_rows,
          COALESCE(SUM(CASE WHEN o.stage_code IN (".$budgetPlaceholders.") AND UPPER(COALESCE(o.status,'')) NOT LIKE '%CANCEL%' THEN o.total ELSE 0 END),0) budget_value
   FROM orders o".$baseSqlWhere,
  array_merge($budgetCodes,$budgetCodes,$budgetCodes,$baseParams)
 )?:[];
 [$validReportSql,$validReportParams]=OrderPolicy::validReportSql('o.stage_code','o.status');
 $validBaseWhere=$baseWhere;$validBaseWhere[]=$validReportSql;
 $validBaseSql=' WHERE '.implode(' AND ',$validBaseWhere);
 $validTotals=DB::one("SELECT COUNT(*) total_rows,COALESCE(SUM(o.total),0) total_value FROM orders o".$validBaseSql,array_merge($baseParams,$validReportParams))?:[];
 if($view==='all'){
  $viewValidWhere=$validBaseWhere;$viewValidParams=array_merge($baseParams,$validReportParams);
  if($stageFilter!==''){$viewValidWhere[]='o.stage_code=?';$viewValidParams[]=$stageFilter;}
  $stats['total_value']=(float)(DB::scalar("SELECT COALESCE(SUM(o.total),0) FROM orders o WHERE ".implode(' AND ',$viewValidWhere),$viewValidParams)??0);
 }

 $drafts=OrderService::drafts($u);

 render('orders',[
  'orders'=>$orders,
  'drafts'=>$drafts,
  'period'=>$period,
  'view'=>$view,
  'totalRows'=>(int)($stats['total_rows']??0),
  'total'=>(float)($stats['total_value']??0),
  'billed'=>(int)($stats['billed_rows']??0),
  'active'=>(int)($stats['active_rows']??0),
  'cancelled'=>(int)($stats['cancelled_rows']??0),
  'withoutSeller'=>(int)($stats['without_seller']??0),
  'allOrders'=>(int)($validTotals['total_rows']??0),
  'budgetOrders'=>(int)($tabStats['budget_rows']??0),
  'budgetValue'=>(float)($tabStats['budget_value']??0),
  'budgetCodes'=>$budgetCodes,
  'stages'=>$stages,
  'stageFilter'=>$stageFilter
 ]);
});
$router->get('/services',function(){
 Auth::requireRole('admin','supervisor');
 $u=Auth::user();
 $period=selected_date_period();

 try{
  $where=[];$params=[];
  if(!$period['all']){
   $where[]='so.service_date>=?';
   $where[]='so.service_date<?';
   $params[]=$period['from'];$params[]=$period['next'];
  }
  if(($u['role']??'')==='seller'){
   $where[]='so.seller_omie_code=?';
   $params[]=(string)($u['seller_omie_code']??'');
  }
  $sqlWhere=$where?' WHERE '.implode(' AND ',$where):'';

  $rows=DB::all(
   "SELECT so.id,so.omie_code,so.client_omie_code,so.seller_omie_code,so.service_date,so.total,so.status,so.updated_at,
           c.name AS client_name,s.name AS seller_name
    FROM service_orders so
    LEFT JOIN clients c ON c.omie_code=so.client_omie_code
    LEFT JOIN sellers s ON s.omie_code=so.seller_omie_code".
    $sqlWhere."
    ORDER BY so.service_date DESC,so.id DESC
    LIMIT 1500",
   $params
  );

  $stats=DB::one(
   "SELECT COUNT(*) total_rows,
           COALESCE(SUM(CASE WHEN UPPER(COALESCE(so.status,'')) NOT LIKE '%CANCEL%' THEN so.total ELSE 0 END),0) total_value,
           SUM(CASE WHEN UPPER(COALESCE(so.status,'')) LIKE '%CANCEL%' THEN 1 ELSE 0 END) cancelled_rows,
           SUM(CASE WHEN UPPER(COALESCE(so.status,'')) NOT LIKE '%CANCEL%' THEN 1 ELSE 0 END) valid_rows,
           SUM(CASE WHEN so.seller_omie_code IS NULL OR so.seller_omie_code='' THEN 1 ELSE 0 END) without_seller
    FROM service_orders so".$sqlWhere,
   $params
  )?:[];

  $health=DB::one(
   "SELECT COUNT(*) total_table,
           SUM(CASE WHEN service_date IS NULL THEN 1 ELSE 0 END) null_dates,
           SUM(CASE WHEN seller_omie_code IS NULL OR seller_omie_code='' THEN 1 ELSE 0 END) null_sellers
    FROM service_orders"
  )?:[];

  render('services',[
   'rows'=>$rows,
   'period'=>$period,
   'total'=>(float)($stats['total_value']??0),
   'valid'=>(int)($stats['valid_rows']??0),
   'cancelled'=>(int)($stats['cancelled_rows']??0),
   'withoutSeller'=>(int)($stats['without_seller']??0),
   'totalRows'=>(int)($stats['total_rows']??0),
   'health'=>$health,
   'serviceSchema'=>['code'=>'omie_code','date'=>'service_date'],
   'serviceError'=>null
  ]);
 }catch(Throwable $e){
  render('services',[
   'rows'=>[],'period'=>$period,
   'total'=>0,'valid'=>0,'cancelled'=>0,'withoutSeller'=>0,'totalRows'=>0,
   'health'=>['total_table'=>0,'null_dates'=>0,'null_sellers'=>0],
   'serviceSchema'=>null,
   'serviceError'=>$e->getMessage()
  ]);
 }
});
$router->get('/orders/new',function(){
 Auth::requireRole('admin','supervisor','seller');$r=OrderService::ready();
 $draftId=(int)($_GET['draft_id']??0);$editOrderId=(int)($_GET['edit_order_id']??0);$draft=null;$editOrder=null;
 if($draftId>0){
  try{$draft=OrderService::draft($draftId,Auth::user());$_SESSION['old']=$draft['form']??[];}
  catch(Throwable $e){$_SESSION['error']=$e->getMessage();}
 }
 if($editOrderId>0){
  try{
   $editOrder=OrderService::editBudgetForm($editOrderId,Auth::user());
   if(empty($_SESSION['old']))$_SESSION['old']=$editOrder['form']??[];
   if(!empty($editOrder['missing_items'])&&empty($_SESSION['error']))$_SESSION['error']='Alguns produtos deste orçamento não estão ativos no cadastro local: '.implode(', ',$editOrder['missing_items']).'.';
  }catch(Throwable $e){$_SESSION['error']=$e->getMessage();redirect('/orders?view=budget');}
 }
 render('order_new',[
  'ready'=>$r,
  'draft'=>$draft,
  'editOrder'=>$editOrder,
  'terms'=>DB::all("SELECT * FROM payment_terms WHERE active=1 AND code<>'999' ORDER BY description"),
  'methods'=>DB::all("SELECT * FROM payment_methods ORDER BY description"),
  'documents'=>DB::all("SELECT * FROM document_types ORDER BY description"),
  'stages'=>DB::all("SELECT * FROM order_stages WHERE active=1 ORDER BY code"),
  'categories'=>DB::all("SELECT * FROM categories WHERE active=1 ORDER BY description"),
  'departments'=>DB::all("SELECT * FROM departments WHERE active=1 ORDER BY description"),
  'accounts'=>DB::all("SELECT * FROM financial_accounts WHERE active=1 ORDER BY name"),
  'taxes'=>DB::all("SELECT * FROM tax_scenarios WHERE active=1 ORDER BY is_default DESC,name"),
  'stocks'=>DB::all("SELECT * FROM stock_locations WHERE active=1 ORDER BY is_default DESC,name"),
  'profiles'=>OrderService::profiles(),
  'carriers'=>OrderService::configuredCarriers(),
  'sellers'=>Auth::can('admin','supervisor')?DB::all("SELECT * FROM sellers WHERE active=1 ORDER BY name"):[],
  'prefill'=>(int)($_GET['client_id']??0)
 ]);
});
$router->get('/orders/{id}/duplicate',function($p){
 Auth::requireRole('admin','supervisor','seller');
 try{
  $copy=OrderService::duplicateOrderForm((int)$p['id'],Auth::user());
  $_SESSION['old']=$copy['form'];
  $_SESSION['success']='Pedido '.($copy['source']['number']??$copy['source']['omie_code']).' copiado. Revise a nova data, a etapa e os valores antes de enviar.';
  if($copy['missing_items'])$_SESSION['error']='Alguns produtos não foram copiados porque estão inativos ou não existem no cadastro local: '.implode(', ',$copy['missing_items']).'.';
  redirect('/orders/new');
 }catch(Throwable $e){$_SESSION['success']=null;$_SESSION['error']=$e->getMessage();redirect('/orders');}
});
$router->get('/orders/{id}/edit',function($p){
 Auth::requireRole('admin','supervisor','seller');
 redirect('/orders/new?edit_order_id='.(int)$p['id']);
});
$router->get('/orders/{id}',function($p){
 Auth::requireRole('admin','supervisor','seller');
 try{$detail=OrderService::orderDetail((int)$p['id'],Auth::user());}
 catch(Throwable $e){http_response_code(str_contains($e->getMessage(),'permissão')?403:404);exit(e($e->getMessage()));}
 render('order_detail',['detail'=>$detail,'stages'=>DB::all("SELECT code,name,active FROM order_stages ORDER BY code")]);
});
$router->post('/orders/{id}/delete',function($p){
 Auth::requireRole('admin');CSRF::require($_POST['_token']??null);
 try{
  $result=OrderService::deleteOrder((int)$p['id']);
  $orderLabel=$result['order']['number']??$result['order']['omie_code'];
  $_SESSION['success']=!empty($result['remote_already_absent'])
   ?'Pedido '.$orderLabel.' já não existia na Omie e foi removido do CRM.'
   :'Pedido '.$orderLabel.' excluído da Omie e do CRM.';
  if(empty($result['metric_updated']))$_SESSION['error']='O pedido foi excluído, mas os indicadores do cliente serão recalculados na próxima sincronização.';
  $targetView=in_array((string)($result['order']['stage_code']??''),OrderPolicy::budgetStageCodes(),true)?'budget':'all';
  redirect('/orders?view='.$targetView);
 }catch(Throwable $e){
  $_SESSION['error']='Não foi possível excluir o pedido: '.$e->getMessage();
  redirect('/orders/'.(int)$p['id']);
 }
});
$router->post('/orders',function(){
 Auth::requireRole('admin','supervisor','seller');CSRF::require($_POST['_token']??null);
 $mode=(string)($_POST['submit_mode']??'send');
 try{
  $editOrderId=(int)($_POST['edit_order_id']??0);
  if($editOrderId>0){
   if($mode!=='send')throw new RuntimeException('Para editar um orçamento da Omie, use Atualizar na Omie.');
   $r=OrderService::updateBudget($_POST,Auth::user());
   $_SESSION['success']='Orçamento '.($r['number']?:$r['code']).' atualizado na Omie e no CRM - '.money($r['total']).'.';
   redirect('/orders/'.$editOrderId);
  }
  if($mode==='draft'){
   $draft=OrderService::saveDraft($_POST,Auth::user());
   $_SESSION['success']='Rascunho salvo localmente.';
   redirect('/orders/new?draft_id='.(int)$draft['id']);
  }
  if($mode!=='send')throw new RuntimeException('Ação de pedido inválida.');
  $r=OrderService::send($_POST,Auth::user());
  $targetView=in_array((string)($_POST['stage']??''),OrderPolicy::budgetStageCodes(),true)?'budget':'all';
  $targetLabel=$targetView==='budget'?'Em orçamento':'Pedidos Confirmados';
  $_SESSION['success']='Integração confirmada: pedido enviado para a Omie'.($r['number']?' • nº '.$r['number']:'').(!empty($r['code'])?' • código '.$r['code']:'').' • '.money($r['total']).'. O rascunho foi finalizado'.(!empty($r['listed'])?' e o pedido está em “'.$targetLabel.'”.':'.');
  redirect('/orders?view='.$targetView);
 }catch(Throwable $e){
  $_SESSION['error']=$e->getMessage();$_SESSION['old']=$_POST;
  $q=(int)($_POST['edit_order_id']??0)>0?'?edit_order_id='.(int)$_POST['edit_order_id']:((int)($_POST['draft_id']??0)>0?'?draft_id='.(int)$_POST['draft_id']:'?client_id='.(int)($_POST['client_id']??0));
  redirect('/orders/new'.$q);
 }
});
$router->post('/orders/drafts/{id}/delete',function($p){
 Auth::requireRole('admin','supervisor','seller');CSRF::require($_POST['_token']??null);
 OrderService::deleteDraft((int)$p['id'],Auth::user());
 $_SESSION['success']='Rascunho excluído.';
 redirect('/orders');
});

$router->get('/collection',function(){Auth::requireRole('admin','supervisor','collector');$view=(string)($_GET['view']??'open');$flash=$_SESSION['collection_flash']??null;unset($_SESSION['collection_flash']);$rows=DB::all("SELECT cc.*,c.name,c.document,c.uf,u.name assigned_name FROM collection_cases cc JOIN clients c ON c.id=cc.client_id LEFT JOIN users u ON u.id=cc.assigned_user_id WHERE cc.status=? ORDER BY cc.open_amount DESC LIMIT 500",[$view==='settled'?'settled':'open']);render('collection',['rows'=>$rows,'view'=>$view,'flash'=>$flash]);});
$router->get('/collection/recoveries',function(){
 Auth::requireRole('admin','supervisor');
 $period=selected_date_period();$flash=$_SESSION['collection_recovery_flash']??null;$old=$_SESSION['collection_recovery_old']??[];$defaults=$_SESSION['collection_recovery_defaults']??[];
 unset($_SESSION['collection_recovery_flash'],$_SESSION['collection_recovery_old']);
 $where=["ca.result='payment'","ca.amount>0"];$params=[];
 if(!$period['all']){$where[]='ca.created_at>=?';$where[]='ca.created_at<?';$params[]=$period['from'].' 00:00:00';$params[]=$period['next'].' 00:00:00';}
 $sqlWhere=implode(' AND ',$where);
 $recoveries=DB::all("SELECT ca.id,ca.client_id,ca.amount,ca.notes,ca.created_at,c.omie_code,JSON_UNQUOTE(JSON_EXTRACT(c.raw_json,'$.codigo_cliente_integracao')) client_integration_code,c.name,c.document,assigned.name assigned_name,author.name author_name
                      FROM collection_actions ca JOIN clients c ON c.id=ca.client_id
                      LEFT JOIN users assigned ON assigned.id=ca.assigned_user_id LEFT JOIN users author ON author.id=ca.author_user_id
                      WHERE ".$sqlWhere." ORDER BY ca.created_at DESC,ca.id DESC LIMIT 1000",$params);
 $total=(float)(DB::scalar("SELECT COALESCE(SUM(ca.amount),0) FROM collection_actions ca WHERE ".$sqlWhere,$params)??0);
 render('collection_recoveries',['recoveries'=>$recoveries,'period'=>$period,'total'=>$total,'flash'=>$flash,'old'=>$old,'defaults'=>$defaults,'collectors'=>DB::all("SELECT id,name FROM users WHERE role='collector' AND active=1 ORDER BY name")]);
});
$router->post('/collection/recoveries',function(){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 try{
  $clientId=(int)($_POST['client_id']??0);$assignedId=(int)($_POST['assigned_user_id']??0);
  $client=DB::one("SELECT id,omie_code,name FROM clients WHERE id=? AND active=1",[$clientId]);if(!$client)throw new RuntimeException('Selecione um cliente válido.');
  $collector=DB::one("SELECT id,name FROM users WHERE id=? AND role='collector' AND active=1",[$assignedId]);if(!$collector)throw new RuntimeException('Selecione o responsável que receberá o valor na meta.');
  $rawAmount=trim((string)($_POST['amount']??''));
  $normalizedAmount=str_contains($rawAmount,',')?str_replace(',','.',str_replace('.','',$rawAmount)):str_replace(' ','',$rawAmount);
  $amount=(float)$normalizedAmount;if($amount<=0)throw new RuntimeException('Informe um valor recuperado maior que zero.');
  $recoveryDate=trim((string)($_POST['recovery_date']??''));$date=DateTime::createFromFormat('Y-m-d',$recoveryDate);
  if(!$date||$date->format('Y-m-d')!==$recoveryDate)throw new RuntimeException('Informe uma data de recuperação válida.');
  if($recoveryDate>date('Y-m-d'))throw new RuntimeException('A data da recuperação não pode estar no futuro.');
  $notes=trim((string)($_POST['notes']??''));
  DB::exec("INSERT INTO collection_actions(client_id,author_user_id,assigned_user_id,channel,result,amount,promise_date,notes,created_at) VALUES(?,?,?,'manual','payment',?,NULL,?,?)",[$clientId,Auth::id(),$assignedId,$amount,$notes!==''?$notes:'Lançamento retroativo de cobrança já efetuada.',$recoveryDate.' '.date('H:i:s')]);
  $_SESSION['collection_recovery_defaults']=['recovery_date'=>$recoveryDate,'assigned_user_id'=>$assignedId];
  $_SESSION['collection_recovery_flash']=['type'=>'success','message'=>'Recuperação de '.money($amount).' para '.$client['name'].' lançada em '.date('d/m/Y',strtotime($recoveryDate)).' e creditada na meta de '.$collector['name'].'.'];
  redirect('/collection/recoveries?date_from='.$recoveryDate.'&date_to='.$recoveryDate);
 }catch(Throwable $e){$_SESSION['collection_recovery_old']=$_POST;$_SESSION['collection_recovery_flash']=['type'=>'danger','message'=>$e->getMessage()];redirect('/collection/recoveries');}
});
$router->get('/collection/{id}',function($p){Auth::requireRole('admin','supervisor','collector');$id=(int)$p['id'];$c=DB::one("SELECT cc.*,c.name,c.document,c.uf,c.phone,u.name assigned_name FROM collection_cases cc JOIN clients c ON c.id=cc.client_id LEFT JOIN users u ON u.id=cc.assigned_user_id WHERE cc.client_id=?",[$id]);if(!$c){http_response_code(404);exit('Cobrança não encontrada.');}$a=DB::all("SELECT ca.*,ua.name author_name,ur.name assigned_name FROM collection_actions ca JOIN users ua ON ua.id=ca.author_user_id JOIN users ur ON ur.id=ca.assigned_user_id WHERE ca.client_id=? ORDER BY ca.created_at DESC",[$id]);render('collection_case',['case'=>$c,'actions'=>$a,'collectors'=>Auth::can('admin','supervisor')?DB::all("SELECT id,name FROM users WHERE role='collector' AND active=1 ORDER BY name"):[]]);});
$router->post('/collection/{id}/assign',function($p){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 $id=(int)$p['id'];$to=(int)($_POST['assigned_user_id']??0);
 $target=DB::one("SELECT id FROM users WHERE id=? AND role='collector' AND active=1",[$to]);if(!$target)exit('Responsável inválido.');
 $case=DB::one("SELECT * FROM collection_cases WHERE client_id=?",[$id]);if(!$case)exit('Cobrança inválida.');
 $from=(int)($case['assigned_user_id']??0);
 DB::conn()->beginTransaction();
 try{
  DB::exec("UPDATE collection_cases SET assigned_user_id=?,assigned_at=NOW(),updated_at=NOW() WHERE client_id=?",[$to,$id]);
  DB::exec("UPDATE collection_actions SET assigned_user_id=? WHERE client_id=?",[$to,$id]);
  DB::exec("UPDATE tasks SET assigned_user_id=? WHERE client_id=? AND type='collection' AND status='pending'",[$to,$id]);
  DB::exec("INSERT INTO collection_assignment_log(client_id,from_user_id,to_user_id,changed_by,created_at) VALUES(?,?,?,?,NOW())",[$id,$from?:null,$to,Auth::id()]);
  DB::conn()->commit();
 }catch(Throwable $e){if(DB::conn()->inTransaction())DB::conn()->rollBack();throw $e;}
 redirect('/collection/'.$id);
});
$router->post('/collection/{id}/action',function($p){Auth::requireRole('admin','supervisor','collector');CSRF::require($_POST['_token']??null);$id=(int)$p['id'];$u=Auth::user();$case=DB::one("SELECT * FROM collection_cases WHERE client_id=?",[$id]);if(!$case)exit('Cobrança inválida.');$assigned=(int)($case['assigned_user_id']??0);if(Auth::can('admin','supervisor')&&!empty($_POST['assigned_user_id']))$assigned=(int)$_POST['assigned_user_id'];if($assigned<=0)$assigned=(int)$u['id'];DB::exec("UPDATE collection_cases SET assigned_user_id=?,assigned_at=IF(COALESCE(assigned_user_id,0)<>?,NOW(),assigned_at),updated_at=NOW() WHERE client_id=?",[$assigned,$assigned,$id]);DB::exec("INSERT INTO collection_actions(client_id,author_user_id,assigned_user_id,channel,result,amount,promise_date,notes,created_at) VALUES(?,?,?,?,?,?,?,?,NOW())",[$id,(int)$u['id'],$assigned,(string)($_POST['channel']??'phone'),(string)($_POST['result']??'contact'),(float)str_replace(',','.',(string)($_POST['amount']??0)),($_POST['promise_date']??'')?:null,trim((string)($_POST['notes']??''))]);if(!empty($_POST['promise_date']))DB::exec("INSERT INTO tasks(client_id,assigned_user_id,type,title,due_at,status,created_at) VALUES(?,?,'collection','Retorno de cobrança',?,'pending',NOW())",[$id,$assigned,$_POST['promise_date'].' 09:00:00']);redirect('/collection/'.$id);});

$router->get('/agenda',function(){Auth::requireLogin();render('agenda',['rows'=>DB::all("SELECT t.*,c.name,c.uf FROM tasks t JOIN clients c ON c.id=t.client_id WHERE t.assigned_user_id=? AND t.status='pending' ORDER BY t.due_at",[Auth::id()])]);});
$router->post('/agenda/{id}/done',function($p){Auth::requireLogin();CSRF::require($_POST['_token']??null);DB::exec("UPDATE tasks SET status='done',completed_at=NOW() WHERE id=? AND assigned_user_id=?",[(int)$p['id'],Auth::id()]);redirect('/agenda');});

$router->get('/settings',function(){
 Auth::requireRole('admin');
 OrderService::ensureCoreCatalogs();
 $flash=$_SESSION['settings_flash']??null;unset($_SESSION['settings_flash']);
 render('settings',[
  'flash'=>$flash,
  'defaults'=>OrderService::defaults(),
  'stages'=>DB::all("SELECT * FROM order_stages WHERE active=1 ORDER BY code"),
  'categories'=>DB::all("SELECT * FROM categories WHERE active=1 ORDER BY description"),
  'accounts'=>DB::all("SELECT * FROM financial_accounts WHERE active=1 ORDER BY name"),
  'terms'=>DB::all("SELECT * FROM payment_terms WHERE active=1 AND code<>'999' ORDER BY description"),
  'methods'=>DB::all("SELECT * FROM payment_methods ORDER BY description"),
  'documents'=>DB::all("SELECT * FROM document_types ORDER BY description"),
  'taxes'=>DB::all("SELECT * FROM tax_scenarios WHERE active=1 ORDER BY is_default DESC,name"),
  'stocks'=>DB::all("SELECT * FROM stock_locations WHERE active=1 ORDER BY is_default DESC,name"),
  'carriers'=>OrderService::carrierCandidates(),
  'profiles'=>OrderService::profiles()
 ]);
});
$router->post('/settings/freight-default',function(){
 Auth::requireRole('admin');CSRF::require($_POST['_token']??null);
 header('Content-Type: application/json; charset=utf-8');
 try{
  $mode=OrderService::saveFreightMode($_POST['freight_mode']??null);
  $labels=['9'=>'Sem frete','0'=>'CIF','1'=>'FOB','2'=>'Terceiros','3'=>'Próprio remetente','4'=>'Próprio destinatário'];
  echo json_encode(['ok'=>true,'mode'=>$mode,'label'=>$labels[$mode]],JSON_UNESCAPED_UNICODE);
 }catch(Throwable $e){
  http_response_code(422);
  echo json_encode(['ok'=>false,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);
 }
});
$router->post('/settings',function(){
 Auth::requireRole('admin');CSRF::require($_POST['_token']??null);
 try{
  DB::conn()->beginTransaction();
  OrderService::saveDefaults($_POST);
  OrderService::saveCarriers((array)($_POST['carrier_codes']??[]));
  DB::exec("UPDATE financial_accounts SET selected=0");
  foreach((array)($_POST['collection_accounts']??[]) as $c)DB::exec("UPDATE financial_accounts SET selected=1 WHERE omie_code=?",[(string)$c]);
  DB::conn()->commit();
  $_SESSION['settings_flash']=['type'=>'success','message'=>'Configurações salvas. Frete padrão: '.(['9'=>'Sem frete','0'=>'CIF','1'=>'FOB','2'=>'Terceiros','3'=>'Próprio remetente','4'=>'Próprio destinatário'][(string)($_POST['freight_mode']??'9')]??'Sem frete').'.'];
 }catch(Throwable $e){
  if(DB::conn()->inTransaction())DB::conn()->rollBack();
  $_SESSION['settings_flash']=['type'=>'danger','message'=>'Não foi possível salvar as configurações: '.$e->getMessage()];
 }
 redirect('/settings');
});
$router->post('/settings/order-profile',function(){Auth::requireRole('admin');CSRF::require($_POST['_token']??null);OrderService::saveProfile($_POST);redirect('/settings');});
$router->get('/users',function(){
 Auth::requireRole('admin');
 $editId=(int)($_GET['edit']??0);
 render('users',['users'=>DB::all("SELECT * FROM users ORDER BY active DESC,name"),'sellers'=>DB::all("SELECT * FROM sellers WHERE active=1 ORDER BY name"),'edit'=>$editId?DB::one("SELECT * FROM users WHERE id=?",[$editId]):null]);
});
$router->post('/users',function(){
 Auth::requireRole('admin');CSRF::require($_POST['_token']??null);
 $id=(int)($_POST['id']??0);$name=trim((string)($_POST['name']??''));$email=mb_strtolower(trim((string)($_POST['email']??'')));
 $role=(string)($_POST['role']??'seller');if(!in_array($role,['admin','supervisor','seller','collector'],true))exit('Perfil inválido.');
 $seller=trim((string)($_POST['seller_omie_code']??''))?:null;$active=!empty($_POST['active'])?1:0;$password=(string)($_POST['password']??'');
 if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL))exit('Nome/e-mail inválidos.');
 if($role==='seller'&&$seller===null)exit('Vincule o vendedor Omie.');
 if($id>0){
  DB::exec("UPDATE users SET name=?,email=?,role=?,seller_omie_code=?,active=?,updated_at=NOW() WHERE id=?",[$name,$email,$role,$role==='seller'?$seller:null,$active,$id]);
  if($password!=='')DB::exec("UPDATE users SET password_hash=? WHERE id=?",[password_hash($password,PASSWORD_DEFAULT),$id]);
 }else{
  if($password==='')exit('Senha obrigatória.');
  DB::exec("INSERT INTO users(name,email,password_hash,role,seller_omie_code,active,created_at,updated_at) VALUES(?,?,?,?,?,?,NOW(),NOW())",[$name,$email,password_hash($password,PASSWORD_DEFAULT),$role,$role==='seller'?$seller:null,$active]);
 }
 redirect('/users');
});
$router->get('/goals',function(){
 Auth::requireRole('admin','supervisor');$month=(string)($_GET['month']??date('Y-m'));
 $management=GoalService::managementMonth($month);
 render('goals',['rows'=>$management['rows'],'month'=>$month,'management'=>$management]);
});
$router->post('/goals/general',function(){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 $month=(string)($_POST['month']??date('Y-m'));GoalService::saveGeneral($month,$_POST);
 redirect('/goals?month='.urlencode($month));
});
$router->post('/goals/virtual/{code}',function($p){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 $month=(string)($_POST['month']??date('Y-m'));
 GoalService::saveVirtual((string)$p['code'],$month,$_POST,Auth::id());
 redirect('/goals?month='.urlencode($month));
});
$router->post('/goals/{id}',function($p){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 GoalService::save((int)$p['id'],(string)($_POST['month']??date('Y-m')),$_POST,Auth::id());
 redirect('/goals?month='.urlencode((string)($_POST['month']??date('Y-m'))));
});
$router->get('/test-data',function(){
 Auth::requireRole('admin');
 $flash=$_SESSION['test_flash']??null;unset($_SESSION['test_flash']);
 render('test_data',['snapshot'=>TestDataService::snapshot(),'flash'=>$flash]);
});
$router->post('/test-data/import',function(){
 Auth::requireRole('admin');CSRF::require($_POST['_token']??null);
 try{
  $r=TestDataService::importMinimal($_POST);
  $_SESSION['test_flash']=['type'=>'success','message'=>'Carga de teste concluída.','client'=>$r['client'],'products'=>$r['products']];
 }catch(Throwable $e){$_SESSION['test_flash']=['type'=>'danger','message'=>$e->getMessage()];}
 redirect('/test-data');
});
$router->post('/test-data/references',function(){
 Auth::requireRole('admin');CSRF::require($_POST['_token']??null);
 try{
  $r=TestDataService::prepareReferences();
  $_SESSION['test_flash']=['type'=>'success','message'=>'Parâmetros auxiliares sincronizados sem carregar toda a base de clientes/produtos.','references'=>$r];
 }catch(Throwable $e){$_SESSION['test_flash']=['type'=>'danger','message'=>$e->getMessage()];}
 redirect('/test-data');
});

$router->get('/sync',function(){
 Auth::requireRole('admin');
 render('sync',['sync'=>SyncService::overview()]);
});
$router->post('/api/sync',function(){
 Auth::requireRole('admin');CSRF::require($_POST['_token']??null);
 $module=(string)($_POST['module']??'');$action=(string)($_POST['action']??'sync');$page=(int)($_POST['page']??1);
 try{
  if($action==='reset')json_response(['ok'=>true]+SyncService::resetState($module));
  if($action==='catchup'&&$page<=1){SyncService::prepareCatchup($module);$page=1;}
  if($action==='last5'&&$page<=1){SyncService::prepareLastFiveDays($module);$page=1;}
  if($action==='period'&&$page<=1){SyncService::preparePeriod($module,(string)($_POST['date_from']??''),(string)($_POST['date_to']??''));$page=1;}
  if($action==='full'&&$page<=1){SyncService::prepareFull($module);$page=1;}
  if($action==='resume'&&$page<=0)$page=SyncService::resumePage($module);
  $result=SyncService::run($module,max(1,$page));
  json_response(['ok'=>true,'action'=>$action]+$result);
 }catch(Throwable $e){
  SyncService::recordError($module,$e->getMessage());
  json_response(['ok'=>false,'module'=>$module,'action'=>$action,'error'=>$e->getMessage()],422);
 }
});
$router->get('/api/public/cnpj',function(){
 Auth::requireRole('admin','supervisor','seller');
 try{json_response(['ok'=>true,'data'=>BrasilApiService::cnpj((string)($_GET['value']??''))]);}
 catch(Throwable $e){json_response(['ok'=>false,'error'=>$e->getMessage()],422);}
});
$router->get('/api/public/cep',function(){
 Auth::requireRole('admin','supervisor','seller');
 try{json_response(['ok'=>true,'data'=>BrasilApiService::cep((string)($_GET['value']??''))]);}
 catch(Throwable $e){json_response(['ok'=>false,'error'=>$e->getMessage()],422);}
});

$router->get('/api/clients/datatable',function(){
 Auth::requireRole('admin','supervisor','seller');
 $u=Auth::user();
 $draw=max(0,(int)($_GET['draw']??0));
 $start=max(0,(int)($_GET['start']??0));
 $length=(int)($_GET['length']??5);$length=$length<1?5:min(100,$length);
 $clientScope=($u['role']==='seller'&&(string)($_GET['scope']??'mine')==='unassigned')?'unassigned':'mine';
 $uf=mb_strtoupper(trim((string)($_GET['uf']??'')),'UTF-8');
 if($uf!==''&&!preg_match('/^[A-Z]{2}$/',$uf))$uf='';
 $ddds=client_portfolio_ddds($_GET['ddds']??[],$uf);

 $baseWhere=['c.active=1'];$baseParams=[];
 if(($u['role']??'')==='seller'){
  if($clientScope==='unassigned')$baseWhere[]="(c.seller_omie_code IS NULL OR c.seller_omie_code='')";
  else{$baseWhere[]='c.seller_omie_code=?';$baseParams[]=trim((string)($u['seller_omie_code']??''))?:'__NO_SELLER_LINK__';}
 }
 if($uf!==''){$baseWhere[]='UPPER(TRIM(c.uf))=?';$baseParams[]=$uf;}
 if($ddds){$baseWhere[]=client_ddd_sql('c').' IN ('.implode(',',array_fill(0,count($ddds),'?')).')';array_push($baseParams,...$ddds);}
 $recordsTotal=(int)(DB::scalar("SELECT COUNT(*) FROM clients c WHERE ".implode(' AND ',$baseWhere),$baseParams)??0);

 $where=$baseWhere;$params=$baseParams;
 $searchInput=$_GET['search']??[];
 $search=trim((string)(is_array($searchInput)?($searchInput['value']??''):''));
 if($search!==''){
  $like='%'.$search.'%';
  $where[]='(c.name LIKE ? OR c.document LIKE ? OR c.phone LIKE ? OR c.city LIKE ? OR c.uf LIKE ? OR s.name LIKE ? OR c.seller_omie_code LIKE ?)';
  array_push($params,$like,$like,$like,$like,$like,$like,$like);
 }
 $sqlWhere=implode(' AND ',$where);
 $recordsFiltered=(int)(DB::scalar("SELECT COUNT(*) FROM clients c LEFT JOIN sellers s ON s.omie_code=c.seller_omie_code WHERE ".$sqlWhere,$params)??0);
 $orderColumns=['c.name','c.city','s.name','m.last_purchase_at','m.last_purchase_at','m.revenue_12m','c.id'];
 $orderInput=$_GET['order']??[];
 $orderIndex=(int)(is_array($orderInput)?($orderInput[0]['column']??0):0);
 $orderBy=$orderColumns[$orderIndex]??'c.name';
 $orderDirection=strtolower((string)(is_array($orderInput)?($orderInput[0]['dir']??'asc'):'asc'))==='desc'?'DESC':'ASC';
 $rows=DB::all(
  "SELECT c.*,s.name seller_name,m.last_purchase_at,m.revenue_12m,m.orders_12m,m.avg_interval_days
   FROM clients c LEFT JOIN client_metrics m ON m.client_id=c.id
   LEFT JOIN sellers s ON s.omie_code=c.seller_omie_code
   WHERE ".$sqlWhere." ORDER BY ".$orderBy." ".$orderDirection.",c.id ASC LIMIT ".$length." OFFSET ".$start,
  $params
 );

 $data=[];$canManage=Auth::can('admin','supervisor');$token=CSRF::token();
 foreach($rows as $row){
  $id=(int)$row['id'];$name=(string)$row['name'];$document=(string)($row['document']??'');
  $initial=mb_strtoupper(mb_substr($name,0,1));
  $identity='<div class="client-table-identity"><span>'.e($initial).'</span><div><a href="'.APP_URL.'/clients/'.$id.'"><strong>'.e($name).'</strong></a><small>'.e($document!==''?$document:'Documento não informado').'</small></div></div>';
  $location=trim((string)($row['city']??'').' / '.(string)($row['uf']??''),' /');
  $phoneDigits=preg_replace('/\D+/','',(string)($row['phone']??''));$rowDdd=strlen($phoneDigits)>=2?substr($phoneDigits,0,2):'';
  $locationHtml='<span class="client-location"><i class="fa-solid fa-location-dot"></i>'.e($location!==''?$location:'Não informado').($rowDdd!==''?'<b>DDD '.e($rowDdd).'</b>':'').'</span>';
  if(!empty($row['seller_name']))$sellerHtml='<span class="client-seller"><i class="fa-solid fa-user-tie"></i><span><strong>'.e($row['seller_name']).'</strong><small>'.e($row['seller_omie_code']).'</small></span></span>';
  elseif(!empty($row['seller_omie_code']))$sellerHtml='<span class="client-seller"><i class="fa-solid fa-user-tie"></i><span><strong>'.e($row['seller_omie_code']).'</strong><small>Vendedor não sincronizado</small></span></span>';
  else $sellerHtml='<span class="client-seller unassigned"><i class="fa-solid fa-user-slash"></i><span><strong>Sem vendedor</strong><small>'.($u['role']==='seller'?'Atendimento compartilhado':'Disponível para vincular').'</small></span></span>';
  $cycle=CRMService::cycle($row['last_purchase_at']??null,(float)($row['avg_interval_days']??0));
  $cycleHtml='<span class="cycle cycle-'.e($cycle['status']).'">'.e($cycle['label']).'</span>';
  $orders=(int)($row['orders_12m']??0);
  $purchaseHtml='<strong>'.brdate($row['last_purchase_at']??null).'</strong><small>'.($orders>0?$orders.' pedido(s) em 12 meses':'Sem pedidos recentes').'</small>';
  $userSellerCode=trim((string)($u['seller_omie_code']??''));
  $canEdit=$canManage||($u['role']==='seller'&&$userSellerCode!==''&&(string)($row['seller_omie_code']??'')===$userSellerCode);
  $openLabel=$canEdit?'Abrir cliente':'Selecionar cliente disponível';
  $actions='<div class="table-actions"><a class="btn btn-sm btn-light" href="'.APP_URL.'/clients/'.$id.'" title="'.e($openLabel).'" aria-label="'.e($openLabel).'"><i class="fa-regular fa-eye"></i></a>';
  if($canEdit)$actions.='<a class="btn btn-sm btn-light" href="'.APP_URL.'/clients/'.$id.'/edit" title="Editar cliente" aria-label="Editar cliente"><i class="fa-regular fa-pen-to-square"></i></a>';
  if($canManage)$actions.='<form method="post" action="'.APP_URL.'/clients/'.$id.'/delete-local" class="d-inline"><input type="hidden" name="_token" value="'.e($token).'"><button class="btn btn-sm btn-light" type="submit" title="Excluir somente do CRM" aria-label="Excluir somente do CRM" data-confirm="Excluir somente do CRM local? Nenhuma chamada será feita à Omie."><i class="fa-solid fa-database-circle-xmark"></i></button></form><form method="post" action="'.APP_URL.'/clients/'.$id.'/delete" class="d-inline"><input type="hidden" name="_token" value="'.e($token).'"><button class="btn btn-sm btn-light danger" type="submit" title="Excluir do CRM e da Omie" aria-label="Excluir do CRM e da Omie" data-confirm="Excluir este cliente na Omie e também no CRM?"><i class="fa-regular fa-trash-can"></i></button></form>';
  $actions.='</div>';
  $data[]=[$identity,$locationHtml,$sellerHtml,$cycleHtml,$purchaseHtml,'<strong class="client-revenue">'.money($row['revenue_12m']??0).'</strong>',$actions];
 }
 json_response(['draw'=>$draw,'recordsTotal'=>$recordsTotal,'recordsFiltered'=>$recordsFiltered,'data'=>$data]);
});

$router->get('/api/clients',function(){Auth::requireRole('admin','supervisor','seller');$u=Auth::user();$q=trim((string)($_GET['q']??''));$w=['active=1'];$p=[];if($u['role']==='seller'){$w[]="(seller_omie_code=? OR seller_omie_code IS NULL OR seller_omie_code='')";$p[]=$u['seller_omie_code'];}if($q!==''){$w[]="(name LIKE ? OR document LIKE ? OR omie_code LIKE ? OR JSON_UNQUOTE(JSON_EXTRACT(raw_json,'$.codigo_cliente_integracao')) LIKE ? OR CAST(id AS CHAR)=?)";$x='%'.$q.'%';array_push($p,$x,$x,$x,$x,$q);}json_response(['items'=>DB::all("SELECT id,omie_code,JSON_UNQUOTE(JSON_EXTRACT(raw_json,'$.codigo_cliente_integracao')) client_integration_code,name,document,email,city,uf,(SELECT assigned_user_id FROM collection_cases WHERE client_id=clients.id) collection_assigned_user_id FROM clients WHERE ".implode(' AND ',$w)." ORDER BY CASE WHEN seller_omie_code=? THEN 0 ELSE 1 END,name LIMIT 25",array_merge($p,[$u['role']==='seller'?$u['seller_omie_code']:'']))]);});
$router->get('/api/products',function(){Auth::requireRole('admin','supervisor','seller');$q=trim((string)($_GET['q']??''));$w=['active=1'];$p=[];if($q!==''){$w[]='(description LIKE ? OR sku LIKE ? OR omie_code LIKE ?)';$x='%'.$q.'%';array_push($p,$x,$x,$x);}$items=DB::all("SELECT id,omie_code,sku,description,unit,unit_price,stock_qty,raw_json FROM products WHERE ".implode(' AND ',$w)." ORDER BY description LIMIT 30",$p);foreach($items as &$item){$raw=json_decode((string)($item['raw_json']??''),true);$item['net_weight']=(float)($raw['peso_liq']??0);$item['gross_weight']=(float)($raw['peso_bruto']??0);unset($item['raw_json']);}unset($item);json_response(['items'=>$items]);});
