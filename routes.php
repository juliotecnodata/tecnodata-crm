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
function client_tag_filter_sql(string $alias='c'): string{
 if(!in_array($alias,['c',''],true))throw new InvalidArgumentException('Alias de cliente inválido.');
 $column=($alias!==''?$alias.'.':'').'raw_json';
 return "EXISTS (SELECT 1 FROM JSON_TABLE(".$column.", '$.tags[*]' COLUMNS(tag VARCHAR(190) PATH '$.tag')) client_tag WHERE LOWER(TRIM(client_tag.tag))=LOWER(TRIM(?)))";
}
function client_tags_from_raw(mixed $rawJson): array{
 $raw=is_array($rawJson)?$rawJson:json_decode((string)$rawJson,true);if(!is_array($raw))return [];
 $tags=[];foreach((array)($raw['tags']??[]) as $item){$tag=trim((string)(is_array($item)?($item['tag']??''):$item));if($tag!=='')$tags[mb_strtolower($tag)]=$tag;}
 return array_values($tags);
}
function client_tag_catalog(): array{
 return DB::all("SELECT MIN(TRIM(client_tag.tag)) tag,COUNT(DISTINCT c.id) client_count FROM clients c JOIN JSON_TABLE(c.raw_json, '$.tags[*]' COLUMNS(tag VARCHAR(190) PATH '$.tag')) client_tag WHERE c.active=1 AND client_tag.tag IS NOT NULL AND TRIM(client_tag.tag)<>'' GROUP BY LOWER(TRIM(client_tag.tag)) ORDER BY client_count DESC,tag");
}
function task_result_catalog(): array{
 $defaults=[
  ['code'=>'contact','label'=>'Contato','contexts'=>['sales','collection'],'active'=>true,'system'=>true],
  ['code'=>'interested','label'=>'Cliente interessado','contexts'=>['sales'],'active'=>true,'system'=>true],
  ['code'=>'agreement','label'=>'Acordo','contexts'=>['sales','collection'],'active'=>true,'system'=>true],
  ['code'=>'promise','label'=>'Promessa','contexts'=>['collection'],'active'=>true,'system'=>true],
  ['code'=>'payment','label'=>'Pagamento','contexts'=>['collection'],'active'=>true,'system'=>true],
  ['code'=>'no_answer','label'=>'Não atendeu','contexts'=>['sales','collection'],'active'=>true,'system'=>true],
  ['code'=>'registry','label'=>'Foi para cartório','contexts'=>['collection'],'active'=>true,'system'=>true],
 ];
 $raw=DB::scalar("SELECT value_json FROM settings WHERE setting_key='task_result_catalog'");
 $saved=$raw?json_decode((string)$raw,true):null;
 $map=[];foreach($defaults as $item)$map[$item['code']]=$item;
 if(is_array($saved)){
  foreach($saved as $item){
   if(!is_array($item))continue;
   $code=preg_replace('/[^a-z0-9_\-]/','',mb_strtolower(trim((string)($item['code']??''))));
   $label=trim((string)($item['label']??''));
   if($code===''||$label==='')continue;
   $contexts=array_values(array_intersect(['sales','collection'],array_map('strval',(array)($item['contexts']??[]))));
   if(!$contexts)continue;
   $base=$map[$code]??['code'=>$code,'system'=>false];
   $map[$code]=$base+[];
   $map[$code]['label']=$label;
   $map[$code]['contexts']=$contexts;
   $map[$code]['active']=!array_key_exists('active',$item)||(bool)$item['active'];
   $map[$code]['system']=(bool)($base['system']??false);
  }
 }
 return array_values($map);
}
function task_result_options(string $context,bool $activeOnly=true): array{
 $context=in_array($context,['sales','collection'],true)?$context:'sales';
 return array_values(array_filter(task_result_catalog(),static function($item)use($context,$activeOnly){
  return in_array($context,(array)($item['contexts']??[]),true)&&(!$activeOnly||!empty($item['active']));
 }));
}
function task_result_label(string $code): string{
 foreach(task_result_catalog() as $item)if((string)$item['code']===$code)return (string)$item['label'];
 return $code;
}
function save_task_result_catalog(array $catalog): void{
 DB::exec("INSERT INTO settings(setting_key,value_json,updated_at) VALUES('task_result_catalog',?,NOW()) ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=NOW()",[json_encode(array_values($catalog),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
}

function contact_monitoring_user_ids(): ?array{
 $raw=DB::scalar("SELECT value_json FROM settings WHERE setting_key='contact_monitoring_users'");
 if($raw===false||$raw===null||$raw==='')return null;
 $config=json_decode((string)$raw,true);if(!is_array($config))return null;
 $ids=[];foreach((array)($config['user_ids']??[]) as $id){$id=(int)$id;if($id>0)$ids[$id]=$id;}
 return array_values($ids);
}
function contact_monitoring_context(array $query): array{
 $sellerId=max(0,(int)($query['seller_id']??0));
 $contactStatus=(string)($query['status']??'all');
 if(!in_array($contactStatus,['all','contacted','never','scheduled','overdue','without_next'],true))$contactStatus='all';
 $monitorUserIds=contact_monitoring_user_ids();
 $sellerSql="SELECT id,name,role,seller_omie_code FROM users WHERE active=1 AND (role='collector' OR (role='seller' AND seller_omie_code IS NOT NULL AND TRIM(seller_omie_code)<>''))";$sellerParams=[];
 if(is_array($monitorUserIds)){$sellerSql.=' AND id IN ('.($monitorUserIds?implode(',',array_fill(0,count($monitorUserIds),'?')):'0').')';$sellerParams=$monitorUserIds;}
 $sellers=DB::all($sellerSql.' ORDER BY name',$sellerParams);
 $where=['c.active=1'];$params=[];
 $codes=array_values(array_unique(array_filter(array_map(static fn($seller)=>$seller['role']==='seller'?trim((string)$seller['seller_omie_code']):'',$sellers))));
 $collectorIds=array_values(array_map(static fn($seller)=>(int)$seller['id'],array_filter($sellers,static fn($seller)=>$seller['role']==='collector')));$participantWhere=[];$participantParams=[];
 if($codes){$participantWhere[]='c.seller_omie_code IN ('.implode(',',array_fill(0,count($codes),'?')).')';array_push($participantParams,...$codes);}
 if($collectorIds){$participantWhere[]='EXISTS (SELECT 1 FROM collection_cases participant_case WHERE participant_case.client_id=c.id AND participant_case.assigned_user_id IN ('.implode(',',array_fill(0,count($collectorIds),'?')).'))';array_push($participantParams,...$collectorIds);}
 if($participantWhere){$where[]='('.implode(' OR ',$participantWhere).')';array_push($params,...$participantParams);}else $where[]='1=0';
 if($sellerId>0){
  $selectedSeller=null;foreach($sellers as $availableSeller)if((int)$availableSeller['id']===$sellerId){$selectedSeller=$availableSeller;break;}
  if($selectedSeller&&$selectedSeller['role']==='seller'){$where[]='c.seller_omie_code=?';$params[]=(string)($selectedSeller['seller_omie_code']??'');}
  elseif($selectedSeller&&$selectedSeller['role']==='collector'){$where[]='EXISTS (SELECT 1 FROM collection_cases selected_case WHERE selected_case.client_id=c.id AND selected_case.assigned_user_id=?)';$params[]=(int)$selectedSeller['id'];}
  else $sellerId=0;
 }
 $hasContact="(EXISTS (SELECT 1 FROM activities status_activity WHERE status_activity.client_id=c.id) OR EXISTS (SELECT 1 FROM collection_actions status_collection WHERE status_collection.client_id=c.id))";
 $hasNext="EXISTS (SELECT 1 FROM tasks status_task WHERE status_task.client_id=c.id AND status_task.type IN ('sales','collection') AND status_task.status='pending')";
 if($contactStatus==='contacted')$where[]=$hasContact;
 elseif($contactStatus==='never')$where[]='NOT '.$hasContact;
 elseif($contactStatus==='scheduled')$where[]=$hasNext;
 elseif($contactStatus==='overdue')$where[]="EXISTS (SELECT 1 FROM tasks status_task WHERE status_task.client_id=c.id AND status_task.type IN ('sales','collection') AND status_task.status='pending' AND status_task.due_at<NOW())";
 elseif($contactStatus==='without_next')$where[]='NOT '.$hasNext;
 return ['sellers'=>$sellers,'seller_id'=>$sellerId,'status'=>$contactStatus,'where'=>$where,'params'=>$params];
}
function contact_monitoring_row_cells(array $row): array{
 $channelLabels=['phone'=>'LigaÃ§Ã£o','whatsapp'=>'WhatsApp','email'=>'E-mail','manual'=>'LanÃ§amento manual'];
 $resultLabels=['contact'=>'Contato realizado','interested'=>'Cliente interessado','agreement'=>'Acordo encaminhado','promise'=>'Promessa de pagamento','payment'=>'Pagamento registrado','no_answer'=>'NÃ£o atendeu'];
 if(!empty($row['last_collection_action_id'])&&(empty($row['last_contact_at'])||strtotime((string)$row['collection_contact_at'])>strtotime((string)$row['last_contact_at']))){
  $row['last_activity_id']=$row['last_collection_action_id'];$row['last_channel']=$row['collection_channel'];$row['last_result']=$row['collection_result'];$row['last_notes']=$row['collection_notes'];$row['last_contact_at']=$row['collection_contact_at'];$row['last_contact_user']=$row['collection_contact_user'];$row['last_contact_flow']='collection';
 }else $row['last_contact_flow']='sales';
 $nextDue=!empty($row['next_due_at'])?strtotime((string)$row['next_due_at']):null;
 $nextClass=$nextDue&&$nextDue<time()?'late':($nextDue&&date('Y-m-d',$nextDue)===date('Y-m-d')?'today':'upcoming');
 $channelIcon=($row['last_channel']??'')==='whatsapp'?'fa-brands fa-whatsapp':(($row['last_channel']??'')==='email'?'fa-solid fa-envelope':(($row['last_contact_flow']??'sales')==='collection'?'fa-solid fa-hand-holding-dollar':'fa-solid fa-phone'));
 $defaultResponsible=(int)($row['next_user_id']??0);if($defaultResponsible<=0)$defaultResponsible=(int)((($row['last_contact_flow']??'sales')==='collection'?($row['collection_user_id']??0):($row['portfolio_user_id']??0)));if($defaultResponsible<=0)$defaultResponsible=(int)($row['collection_user_id']??$row['portfolio_user_id']??0);
 $schedulePayload=['client_id'=>(int)$row['id'],'client_name'=>(string)$row['name'],'task_id'=>(int)($row['next_task_id']??0),'assigned_user_id'=>$defaultResponsible,'title'=>(string)($row['next_title']??'PrÃ³ximo contato'),'due_at'=>$nextDue?date('Y-m-d\TH:i',$nextDue):''];
 $identity='<div class="tdcontact-client"><span>'.e(mb_strtoupper(mb_substr((string)$row['name'],0,1))).'</span><div><strong>'.e($row['name']).'</strong><small>'.e(trim((string)($row['city']??'').' / '.(string)($row['uf']??''),' /')?:'LocalizaÃ§Ã£o nÃ£o informada').'</small></div></div>';
 $owners='<div class="tdcontact-owner-list">';
 if(!empty($row['seller_name']))$owners.='<span><i class="fa-solid fa-user-tie"></i><b>'.e($row['seller_name']).'</b><small>Vendas Â· '.e($row['portfolio_user_name']??'sem usuÃ¡rio').'</small></span>';
 if(!empty($row['collection_user_name']))$owners.='<span class="collection"><i class="fa-solid fa-hand-holding-dollar"></i><b>'.e($row['collection_user_name']).'</b><small>Carteira de cobranÃ§a</small></span>';
 if(empty($row['seller_name'])&&empty($row['collection_user_name']))$owners.='<span class="tdcontact-unassigned"><i class="fa-solid fa-user-slash"></i>Sem responsÃ¡vel</span>';
 $owners.='</div>';
 $last=!empty($row['last_contact_at'])?'<strong>'.date('d/m/Y H:i',strtotime((string)$row['last_contact_at'])).'</strong><small>Por '.e($row['last_contact_user']??'UsuÃ¡rio nÃ£o identificado').' Â· '.(int)$row['contact_count'].' contato(s)</small>':'<span class="tdcontact-empty"><i class="fa-regular fa-clock"></i>Nunca contatado</span>';
 $contact=!empty($row['last_activity_id'])?'<span class="tdcontact-channel"><i class="'.$channelIcon.'"></i>'.e($channelLabels[$row['last_channel']]??$row['last_channel']).'</span><small>'.e($resultLabels[$row['last_result']]??$row['last_result']).'</small>':'â€”';
 $note='<span class="tdcontact-note">'.e(trim((string)($row['last_notes']??''))?:'Sem observaÃ§Ã£o').'</span>';
 $next=$nextDue?'<span class="tdcontact-next '.$nextClass.'"><i class="fa-regular fa-calendar"></i><strong>'.date('d/m/Y H:i',$nextDue).'</strong></span><small>'.e($row['next_user_name']??'Sem responsÃ¡vel').' Â· '.e($row['next_title']??'PrÃ³ximo contato').'</small>':'<span class="tdcontact-empty"><i class="fa-regular fa-calendar-xmark"></i>NÃ£o agendado</span>';
 $actions='<div class="tdcontact-actions"><a class="tdcontact-btn tdcontact-btn-open" href="'.APP_URL.'/clients/'.(int)$row['id'].'"><i class="fa-regular fa-folder-open"></i>Abrir</a><button class="tdcontact-btn tdcontact-btn-schedule" type="button" data-contact-schedule="'.e(json_encode($schedulePayload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).'"><i class="fa-regular fa-calendar-plus"></i>'.($nextDue?'Reagendar':'Agendar').'</button></div>';
 return [$identity,$owners,$last,$contact,$note,$next,$actions];
}
$router->get('/login',function(){if(Auth::check())redirect('/');render('login');});
$router->post('/login',function(){CSRF::require($_POST['_token']??null);if(Auth::attempt((string)($_POST['email']??''),(string)($_POST['password']??'')))redirect('/');render('login',['error'=>'E-mail ou senha inválidos.']);});
$router->post('/logout',function(){CSRF::require($_POST['_token']??null);Auth::logout();redirect('/login');});

$router->get('/',function(){
 Auth::requireLogin();
 $month=(string)($_GET['month']??date('Y-m'));
 if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');
 $daysInMonth=(int)date('t',strtotime($month.'-01'));
 $requestedDays=$_GET['days']??[];if(!is_array($requestedDays))$requestedDays=[$requestedDays];
 $selectedDays=[];foreach($requestedDays as $requestedDay){$value=(int)$requestedDay;if($value>=1&&$value<=$daysInMonth)$selectedDays[$value]=$value;}
 $selectedDays=array_values($selectedDays);sort($selectedDays);
 $periodLabel=$selectedDays?'Dias '.implode(', ',array_map(static fn($value)=>str_pad((string)$value,2,'0',STR_PAD_LEFT),$selectedDays)).' de '.date('m/Y',strtotime($month.'-01')):date('m/Y',strtotime($month.'-01'));
 $u=Auth::user();
 $resultModel=(string)($_GET['result_model']??'executive');
 if(!in_array($resultModel,['executive','cards','compare','detail'],true))$resultModel='executive';
 $resultArea=(string)($_GET['result_area']??'commercial');
 if(!in_array($resultArea,['commercial','collection'],true))$resultArea='commercial';
 $resultSeller=trim((string)($_GET['seller']??''));
 $payload=[
  'u'=>$u,
  'data'=>CRMService::dashboard($u),
  'month'=>$month,
  'selectedDays'=>$selectedDays,
  'daysInMonth'=>$daysInMonth,
  'periodLabel'=>$periodLabel,
  'resultModel'=>$resultModel,
  'resultArea'=>$resultArea,
  'resultSeller'=>$resultSeller
 ];
 if(in_array($u['role'],['admin','supervisor'],true))$payload['management']=GoalService::managementMonth($month,$selectedDays);
 else $payload['result']=GoalService::userMonth(Auth::id(),$month,$selectedDays);
 render('dashboard',$payload);
});
$router->get('/result',function(){
 Auth::requireLogin();
 $query=(string)($_SERVER['QUERY_STRING']??'');
 redirect('/'.($query!==''?'?'.$query:''));
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

$renderClients=function(bool $portfolioOnly=false){
 Auth::requireRole('admin','supervisor','seller');
 $u=Auth::user();
 if($portfolioOnly&&$u['role']!=='seller'){redirect('/clients');}
 $flash=$_SESSION['clients_flash']??null;unset($_SESSION['clients_flash']);
 $q=trim((string)($_GET['q']??''));
 $clientScope=$portfolioOnly?'mine':(($u['role']==='seller'&&(string)($_GET['scope']??'all')==='unassigned')?'unassigned':'all');
 $uf=mb_strtoupper(trim((string)($_GET['uf']??'')),'UTF-8');
 if($uf!==''&&!preg_match('/^[A-Z]{2}$/',$uf))$uf='';
 $ddds=client_portfolio_ddds($_GET['ddds']??[],$uf);
 $tag=trim((string)($_GET['tag']??''));if(mb_strlen($tag)>190)$tag='';
 $w=['c.active=1'];$p=[];
 if($u['role']==='seller'){
  if($portfolioOnly){$w[]='c.seller_omie_code=?';$p[]=trim((string)($u['seller_omie_code']??''))?:'__NO_SELLER_LINK__';}
  elseif($clientScope==='unassigned')$w[]="(c.seller_omie_code IS NULL OR c.seller_omie_code='')";
 }
 if($uf!==''){$w[]='UPPER(TRIM(c.uf))=?';$p[]=$uf;}
 if($ddds){$w[]=client_ddd_sql('c').' IN ('.implode(',',array_fill(0,count($ddds),'?')).')';array_push($p,...$ddds);}
 if($tag!==''){$w[]=client_tag_filter_sql('c');$p[]=$tag;}
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
  "SELECT c.*,s.name seller_name,m.last_purchase_at,m.revenue_12m,m.orders_12m,m.avg_interval_days,
          CASE
           WHEN act.last_activity_at IS NULL THEN col.last_collection_at
           WHEN col.last_collection_at IS NULL THEN act.last_activity_at
           WHEN act.last_activity_at>=col.last_collection_at THEN act.last_activity_at
           ELSE col.last_collection_at
          END last_contact_at
   FROM clients c LEFT JOIN client_metrics m ON m.client_id=c.id
   LEFT JOIN sellers s ON s.omie_code=c.seller_omie_code
   LEFT JOIN (SELECT client_id,MAX(created_at) last_activity_at FROM activities GROUP BY client_id) act ON act.client_id=c.id
   LEFT JOIN (SELECT client_id,MAX(created_at) last_collection_at FROM collection_actions GROUP BY client_id) col ON col.client_id=c.id
   WHERE ".$where." ORDER BY c.name LIMIT ".$perPage." OFFSET ".$offset,
  $p
 );
 foreach($rows as &$r){
  $r['cycle']=CRMService::cycle($r['last_purchase_at']??null,(float)($r['avg_interval_days']??0));
 }
 unset($r);
 $availableClients=$u['role']==='seller'?(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1 AND (seller_omie_code IS NULL OR seller_omie_code='')")??0):0;
 render('clients',['rows'=>$rows,'q'=>$q,'uf'=>$uf,'ddds'=>$ddds,'tag'=>$tag,'clientTags'=>client_tag_catalog(),'clientScope'=>$clientScope,'portfolioMode'=>$portfolioOnly,'availableClients'=>$availableClients,'portfolioDddMap'=>client_portfolio_ddd_map(),'flash'=>$flash,'clientStats'=>[
  'total'=>$totalClients,
  'revenue'=>(float)($summary['revenue_12m']??0),
  'orders'=>(int)($summary['orders_12m']??0),
  'without_seller'=>(int)($summary['without_seller']??0),
 ],'clientPagination'=>[
  'page'=>$page,'pages'=>$totalPages,'per_page'=>$perPage,
  'from'=>$totalClients?($offset+1):0,'to'=>min($offset+$perPage,$totalClients),
 ],'portfolioSellers'=>Auth::can('admin','supervisor')?DB::all("SELECT omie_code,name FROM sellers WHERE active=1 ORDER BY name"):[],
 'portfolioSourceSellers'=>Auth::can('admin','supervisor')?DB::all("SELECT DISTINCT c.seller_omie_code omie_code,COALESCE(s.name,CONCAT('Código ',c.seller_omie_code)) name,COALESCE(s.active,0) active FROM clients c LEFT JOIN sellers s ON s.omie_code=c.seller_omie_code WHERE c.active=1 AND c.seller_omie_code IS NOT NULL AND c.seller_omie_code<>'' ORDER BY active DESC,name"):[],
 'portfolioStates'=>Auth::can('admin','supervisor')?DB::all("SELECT DISTINCT UPPER(TRIM(uf)) uf FROM clients WHERE active=1 AND uf IS NOT NULL AND TRIM(uf)<>'' ORDER BY uf"):[],
 'clientStates'=>$portfolioOnly&&$u['role']==='seller'
  ?DB::all("SELECT DISTINCT UPPER(TRIM(uf)) uf FROM clients WHERE active=1 AND seller_omie_code=? AND uf IS NOT NULL AND TRIM(uf)<>'' ORDER BY uf",[trim((string)($u['seller_omie_code']??''))?:'__NO_SELLER_LINK__'])
  :DB::all("SELECT DISTINCT UPPER(TRIM(uf)) uf FROM clients WHERE active=1 AND uf IS NOT NULL AND TRIM(uf)<>'' ORDER BY uf")
 ]);
};
$router->get('/clients',function()use($renderClients){$renderClients(false);});
$router->get('/my-portfolio',function()use($renderClients){$renderClients(true);});
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
 render('client',['client'=>$c,'activities'=>$a,'orders'=>$o,'cycle'=>CRMService::cycle($c['last_purchase_at']??null,(float)($c['avg_interval_days']??0)),'flash'=>$flash,'formData'=>$form,'sellerName'=>$sellerName,'sharedUnassigned'=>$u['role']==='seller'&&$isUnassigned,'taskResults'=>task_result_options('sales'),'taskResultLabels'=>array_column(task_result_catalog(),'label','code')]);
});
$router->post('/clients/{id}/activity',function($p){
 Auth::requireRole('admin','supervisor','seller');CSRF::require($_POST['_token']??null);
 $id=(int)$p['id'];$u=Auth::user();$c=DB::one("SELECT * FROM clients WHERE id=?",[$id]);if(!$c)exit('Cliente inválido.');
 $unassigned=trim((string)($c['seller_omie_code']??''))==='';if($u['role']==='seller'&&!$unassigned&&(string)$c['seller_omie_code']!==(string)$u['seller_omie_code']){http_response_code(403);exit('Sem permissão.');}
 $result=(string)($_POST['result']??'contact');$allowed=array_column(task_result_options('sales'),'code');
 if(!in_array($result,$allowed,true))$result='contact';
 $nextAt=trim((string)($_POST['next_at']??''));
 DB::exec("INSERT INTO activities(client_id,user_id,channel,result,notes,next_at,created_at) VALUES(?,?,?,?,?,?,NOW())",[$id,(int)$u['id'],(string)($_POST['channel']??'phone'),$result,trim((string)($_POST['notes']??'')),$nextAt!==''?$nextAt:null]);
 if($nextAt!=='')DB::exec("INSERT INTO tasks(client_id,assigned_user_id,type,title,due_at,status,created_at) VALUES(?,?,'sales',?,?,'pending',NOW())",[$id,(int)$u['id'],'Retorno comercial · '.task_result_label($result),$nextAt]);
 redirect('/clients/'.$id);
});

$router->get('/contact-monitoring',function(){
 Auth::requireRole('admin','supervisor');
 $sellerId=max(0,(int)($_GET['seller_id']??0));
 $contactStatus=(string)($_GET['status']??'all');
 if(!in_array($contactStatus,['all','contacted','never','scheduled','overdue','without_next'],true))$contactStatus='all';
 $monitorUserIds=contact_monitoring_user_ids();$sellerSql="SELECT id,name,role,seller_omie_code FROM users WHERE active=1 AND (role='collector' OR (role='seller' AND seller_omie_code IS NOT NULL AND TRIM(seller_omie_code)<>''))";$sellerParams=[];
 if(is_array($monitorUserIds)){$sellerSql.=' AND id IN ('.($monitorUserIds?implode(',',array_fill(0,count($monitorUserIds),'?')):'0').')';$sellerParams=$monitorUserIds;}
 $sellers=DB::all($sellerSql.' ORDER BY name',$sellerParams);
 $where=['c.active=1'];$params=[];
 {
  $codes=array_values(array_unique(array_filter(array_map(static fn($seller)=>$seller['role']==='seller'?trim((string)$seller['seller_omie_code']):'',$sellers))));
  $collectorIds=array_values(array_map(static fn($seller)=>(int)$seller['id'],array_filter($sellers,static fn($seller)=>$seller['role']==='collector')));$participantWhere=[];$participantParams=[];
  if($codes){$participantWhere[]='c.seller_omie_code IN ('.implode(',',array_fill(0,count($codes),'?')).')';array_push($participantParams,...$codes);}
  if($collectorIds){$participantWhere[]='EXISTS (SELECT 1 FROM collection_cases participant_case WHERE participant_case.client_id=c.id AND participant_case.assigned_user_id IN ('.implode(',',array_fill(0,count($collectorIds),'?')).'))';array_push($participantParams,...$collectorIds);}
  if($participantWhere){$where[]='('.implode(' OR ',$participantWhere).')';array_push($params,...$participantParams);}else $where[]='1=0';
 }
 if($sellerId>0){
  $selectedSeller=null;foreach($sellers as $availableSeller)if((int)$availableSeller['id']===$sellerId){$selectedSeller=$availableSeller;break;}
  if($selectedSeller&&$selectedSeller['role']==='seller'){$where[]='c.seller_omie_code=?';$params[]=(string)($selectedSeller['seller_omie_code']??'');}
  elseif($selectedSeller&&$selectedSeller['role']==='collector'){$where[]='EXISTS (SELECT 1 FROM collection_cases selected_case WHERE selected_case.client_id=c.id AND selected_case.assigned_user_id=?)';$params[]=(int)$selectedSeller['id'];}
  else $sellerId=0;
 }
 if($contactStatus==='contacted')$where[]='(la.id IS NOT NULL OR lca.id IS NOT NULL)';
 elseif($contactStatus==='never')$where[]='la.id IS NULL AND lca.id IS NULL';
 elseif($contactStatus==='scheduled')$where[]='nt.id IS NOT NULL';
 elseif($contactStatus==='overdue')$where[]='nt.due_at<NOW()';
 elseif($contactStatus==='without_next')$where[]='nt.id IS NULL';
 $rows=DB::all(
  "SELECT c.id,c.name,c.document,c.city,c.uf,c.phone,c.seller_omie_code,
          s.name seller_name,portfolio_user.id portfolio_user_id,portfolio_user.name portfolio_user_name,
          collection_user.id collection_user_id,collection_user.name collection_user_name,
          la.id last_activity_id,la.channel last_channel,la.result last_result,la.notes last_notes,la.created_at last_contact_at,
          activity_user.name last_contact_user,
          lca.id last_collection_action_id,lca.channel collection_channel,lca.result collection_result,lca.notes collection_notes,lca.created_at collection_contact_at,
          collection_author.name collection_contact_user,
          ((SELECT COUNT(*) FROM activities ac WHERE ac.client_id=c.id)+(SELECT COUNT(*) FROM collection_actions cac WHERE cac.client_id=c.id)) contact_count,
          nt.id next_task_id,nt.title next_title,nt.due_at next_due_at,
          next_user.id next_user_id,next_user.name next_user_name
   FROM clients c
   LEFT JOIN sellers s ON s.omie_code=c.seller_omie_code
   LEFT JOIN users portfolio_user ON portfolio_user.id=(SELECT pu.id FROM users pu WHERE pu.role='seller' AND pu.active=1 AND pu.seller_omie_code=c.seller_omie_code ORDER BY pu.id LIMIT 1)
   LEFT JOIN collection_cases cc ON cc.client_id=c.id
   LEFT JOIN users collection_user ON collection_user.id=cc.assigned_user_id
   LEFT JOIN activities la ON la.id=(SELECT a2.id FROM activities a2 WHERE a2.client_id=c.id ORDER BY a2.created_at DESC,a2.id DESC LIMIT 1)
   LEFT JOIN users activity_user ON activity_user.id=la.user_id
   LEFT JOIN collection_actions lca ON lca.id=(SELECT ca2.id FROM collection_actions ca2 WHERE ca2.client_id=c.id ORDER BY ca2.created_at DESC,ca2.id DESC LIMIT 1)
   LEFT JOIN users collection_author ON collection_author.id=lca.author_user_id
   LEFT JOIN tasks nt ON nt.id=(SELECT t2.id FROM tasks t2 WHERE t2.client_id=c.id AND t2.type IN ('sales','collection') AND t2.status='pending' ORDER BY t2.due_at,t2.id LIMIT 1)
   LEFT JOIN users next_user ON next_user.id=nt.assigned_user_id
   WHERE ".implode(' AND ',$where)."
   ORDER BY CASE WHEN nt.due_at<NOW() THEN 0 WHEN la.id IS NULL AND lca.id IS NULL THEN 1 WHEN nt.id IS NULL THEN 2 ELSE 3 END,
            COALESCE(nt.due_at,GREATEST(COALESCE(la.created_at,'1000-01-01'),COALESCE(lca.created_at,'1000-01-01'))) ASC,c.name ASC",
  $params
 );
 foreach($rows as &$row)if(!empty($row['last_collection_action_id'])&&(empty($row['last_contact_at'])||strtotime((string)$row['collection_contact_at'])>strtotime((string)$row['last_contact_at']))){$row['last_activity_id']=$row['last_collection_action_id'];$row['last_channel']=$row['collection_channel'];$row['last_result']=$row['collection_result'];$row['last_notes']=$row['collection_notes'];$row['last_contact_at']=$row['collection_contact_at'];$row['last_contact_user']=$row['collection_contact_user'];$row['last_contact_flow']='collection';}else $row['last_contact_flow']='sales';unset($row);
 $stats=DB::one(
  "SELECT COUNT(*) total,
          SUM(CASE WHEN la.id IS NOT NULL OR lca.id IS NOT NULL THEN 1 ELSE 0 END) contacted,
          SUM(CASE WHEN nt.id IS NOT NULL THEN 1 ELSE 0 END) scheduled,
          SUM(CASE WHEN nt.due_at<NOW() THEN 1 ELSE 0 END) overdue,
          SUM(CASE WHEN nt.id IS NULL THEN 1 ELSE 0 END) without_next
   FROM clients c
   LEFT JOIN activities la ON la.id=(SELECT a2.id FROM activities a2 WHERE a2.client_id=c.id ORDER BY a2.created_at DESC,a2.id DESC LIMIT 1)
   LEFT JOIN collection_actions lca ON lca.id=(SELECT ca2.id FROM collection_actions ca2 WHERE ca2.client_id=c.id ORDER BY ca2.created_at DESC,ca2.id DESC LIMIT 1)
   LEFT JOIN tasks nt ON nt.id=(SELECT t2.id FROM tasks t2 WHERE t2.client_id=c.id AND t2.type IN ('sales','collection') AND t2.status='pending' ORDER BY t2.due_at,t2.id LIMIT 1)
   WHERE ".implode(' AND ',$where),$params
 )?:['total'=>0,'contacted'=>0,'scheduled'=>0,'overdue'=>0,'without_next'=>0];
 $flash=$_SESSION['contact_monitoring_flash']??null;unset($_SESSION['contact_monitoring_flash']);
 render('contact_monitoring',['rows'=>$rows,'monitorSellers'=>$sellers,'monitorSellerId'=>$sellerId,'monitorStatus'=>$contactStatus,'monitorStats'=>$stats,'flash'=>$flash]);
});
$router->post('/contact-monitoring/{id}/schedule',function($p){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 $clientId=(int)$p['id'];$taskId=max(0,(int)($_POST['task_id']??0));$assignedId=max(0,(int)($_POST['assigned_user_id']??0));
 $title=trim((string)($_POST['title']??''));$value=trim((string)($_POST['due_at']??''));
 $redirect=[];$sellerFilter=max(0,(int)($_POST['seller_filter']??0));if($sellerFilter>0)$redirect['seller_id']=$sellerFilter;
 $statusFilter=(string)($_POST['status_filter']??'all');if(in_array($statusFilter,['contacted','never','scheduled','overdue','without_next'],true))$redirect['status']=$statusFilter;
 try{
  $client=DB::one("SELECT id,name FROM clients WHERE id=? AND active=1",[$clientId]);
  if(!$client)throw new RuntimeException('Cliente não encontrado.');
  $seller=DB::one("SELECT id,name,role FROM users WHERE id=? AND role IN ('seller','collector') AND active=1",[$assignedId]);
  if(!$seller)throw new RuntimeException('Selecione um usuário ativo de vendas ou cobrança para o próximo contato.');
  $monitorUserIds=contact_monitoring_user_ids();if(is_array($monitorUserIds)&&!in_array($assignedId,$monitorUserIds,true))throw new RuntimeException('Este vendedor não participa do acompanhamento conforme a regra das configurações.');
  if($title===''||mb_strlen($title)>180)throw new RuntimeException('Informe uma descrição de até 180 caracteres.');
  $date=DateTime::createFromFormat('Y-m-d\TH:i',$value);
  if(!$date||$date->format('Y-m-d\TH:i')!==$value)throw new RuntimeException('Informe uma data e hora válidas.');
  if($date->getTimestamp()<time()-60)throw new RuntimeException('O próximo contato precisa ser agendado para um horário futuro.');
  $formatted=$date->format('Y-m-d H:i:00');
  if($taskId>0){
   $task=DB::one("SELECT id FROM tasks WHERE id=? AND client_id=? AND type IN ('sales','collection') AND status='pending'",[$taskId,$clientId]);
   if(!$task)throw new RuntimeException('Este agendamento não está mais pendente. Atualize a tela.');
   DB::exec("UPDATE tasks SET assigned_user_id=?,type=?,title=?,due_at=? WHERE id=?",[$assignedId,$seller['role']==='collector'?'collection':'sales',$title,$formatted,$taskId]);
   $message='Próximo contato de '.$client['name'].' reagendado para '.$date->format('d/m/Y').' às '.$date->format('H:i').'.';
  }else{
   DB::exec("INSERT INTO tasks(client_id,assigned_user_id,type,title,due_at,status,created_at) VALUES(?,?,?,?,?,'pending',NOW())",[$clientId,$assignedId,$seller['role']==='collector'?'collection':'sales',$title,$formatted]);
   $message='Próximo contato de '.$client['name'].' agendado para '.$date->format('d/m/Y').' às '.$date->format('H:i').'.';
  }
  $_SESSION['contact_monitoring_flash']=['type'=>'success','message'=>$message];
 }catch(Throwable $e){$_SESSION['contact_monitoring_flash']=['type'=>'danger','message'=>$e->getMessage()];}
 redirect('/contact-monitoring'.($redirect?'?'.http_build_query($redirect):''));
});

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
$router->get('/collection/{id}',function($p){
 Auth::requireRole('admin','supervisor','collector');
 $id=(int)$p['id'];
 $c=DB::one("SELECT cc.*,c.name,c.document,c.uf,c.phone,u.name assigned_name FROM collection_cases cc JOIN clients c ON c.id=cc.client_id LEFT JOIN users u ON u.id=cc.assigned_user_id WHERE cc.client_id=?",[$id]);
 if(!$c){http_response_code(404);exit('Cobrança não encontrada.');}
 $a=DB::all("SELECT ca.*,ua.name author_name,ur.name assigned_name FROM collection_actions ca JOIN users ua ON ua.id=ca.author_user_id JOIN users ur ON ur.id=ca.assigned_user_id WHERE ca.client_id=? ORDER BY ca.created_at DESC",[$id]);
 $flash=$_SESSION['collection_case_flash']??null;unset($_SESSION['collection_case_flash']);
 render('collection_case',['case'=>$c,'actions'=>$a,'collectors'=>Auth::can('admin','supervisor')?DB::all("SELECT id,name FROM users WHERE role='collector' AND active=1 ORDER BY name"):[],'flash'=>$flash,'taskResults'=>task_result_options('collection'),'taskResultLabels'=>array_column(task_result_catalog(),'label','code')]);
});
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
$router->post('/collection/{id}/action',function($p){
 Auth::requireRole('admin','supervisor','collector');CSRF::require($_POST['_token']??null);
 $id=(int)$p['id'];$u=Auth::user();
 $case=DB::one("SELECT * FROM collection_cases WHERE client_id=?",[$id]);
 if(!$case){$_SESSION['collection_case_flash']=['type'=>'danger','message'=>'Cobrança inválida.'];redirect('/collection');}
 $assigned=(int)($case['assigned_user_id']??0);
 if(Auth::can('admin','supervisor')&&!empty($_POST['assigned_user_id']))$assigned=(int)$_POST['assigned_user_id'];
 if($assigned<=0)$assigned=(int)$u['id'];

 $result=(string)($_POST['result']??'contact');$allowedResults=array_column(task_result_options('collection'),'code');
 if(!in_array($result,$allowedResults,true))$result='contact';

 $promiseAt=trim((string)($_POST['promise_at']??''));$promiseDate=null;$promiseDueAt=null;
 if($promiseAt!==''){
  $date=DateTime::createFromFormat('Y-m-d\TH:i',$promiseAt);
  if(!$date||$date->format('Y-m-d\TH:i')!==$promiseAt){
   $_SESSION['collection_case_flash']=['type'=>'danger','message'=>'Informe uma data e hora válidas para o retorno.'];
   redirect('/collection/'.$id);
  }
  $promiseDate=$date->format('Y-m-d');$promiseDueAt=$date->format('Y-m-d H:i:00');
 }

 DB::conn()->beginTransaction();
 try{
  DB::exec("UPDATE collection_cases SET assigned_user_id=?,assigned_at=IF(COALESCE(assigned_user_id,0)<>?,NOW(),assigned_at),updated_at=NOW() WHERE client_id=?",[$assigned,$assigned,$id]);
  DB::exec("INSERT INTO collection_actions(client_id,author_user_id,assigned_user_id,channel,result,amount,promise_date,notes,created_at) VALUES(?,?,?,?,?,?,?,?,NOW())",[$id,(int)$u['id'],$assigned,(string)($_POST['channel']??'phone'),$result,(float)str_replace(',','.',(string)($_POST['amount']??0)),$promiseDate,trim((string)($_POST['notes']??''))]);
  if($promiseDueAt!==null)DB::exec("INSERT INTO tasks(client_id,assigned_user_id,type,title,due_at,status,created_at) VALUES(?,?,'collection',?,?,'pending',NOW())",[$id,$assigned,'Retorno de cobrança · '.task_result_label($result),$promiseDueAt]);
  DB::conn()->commit();
  $_SESSION['collection_case_flash']=['type'=>'success','message'=>$promiseDueAt?'Ação salva e retorno agendado para '.$date->format('d/m/Y').' às '.$date->format('H:i').'.':'Ação de cobrança salva com sucesso.'];
 }catch(Throwable $e){
  if(DB::conn()->inTransaction())DB::conn()->rollBack();
  $_SESSION['collection_case_flash']=['type'=>'danger','message'=>'Não foi possível salvar a ação de cobrança. Tente novamente.'];
 }
 redirect('/collection/'.$id);
});

$router->get('/agenda',function(){
 Auth::requireLogin();
 $u=Auth::user();$role=(string)$u['role'];$teamAgenda=in_array($role,['admin','supervisor'],true);
 $filterUser=$teamAgenda?max(0,(int)($_GET['user_id']??0)):(int)$u['id'];
 $agendaType=(string)($_GET['type']??'all');if(!in_array($agendaType,['all','sales','collection'],true))$agendaType='all';
 $agendaPeriod=(string)($_GET['period']??'all');if(!in_array($agendaPeriod,['all','late','today','next7','upcoming'],true))$agendaPeriod='all';
 $flash=$_SESSION['agenda_flash']??null;unset($_SESSION['agenda_flash']);

 $baseWhere=["t.status='pending'"];$baseParams=[];
 if($teamAgenda){
  if($filterUser>0){$baseWhere[]='t.assigned_user_id=?';$baseParams[]=$filterUser;}
 }else{
  $baseWhere[]='t.assigned_user_id=?';$baseParams[]=(int)$u['id'];
 }
 if($agendaType!=='all'){$baseWhere[]='t.type=?';$baseParams[]=$agendaType;}

 $listWhere=$baseWhere;$listParams=$baseParams;
 if($agendaPeriod==='late')$listWhere[]='DATE(t.due_at)<CURDATE()';
 elseif($agendaPeriod==='today')$listWhere[]='DATE(t.due_at)=CURDATE()';
 elseif($agendaPeriod==='next7')$listWhere[]='DATE(t.due_at)>CURDATE() AND DATE(t.due_at)<=DATE_ADD(CURDATE(),INTERVAL 7 DAY)';
 elseif($agendaPeriod==='upcoming')$listWhere[]='DATE(t.due_at)>CURDATE()';

 $rows=DB::all(
  "SELECT t.*,c.name,c.uf,u.name assigned_name,u.role assigned_role
   FROM tasks t JOIN clients c ON c.id=t.client_id
   JOIN users u ON u.id=t.assigned_user_id
   WHERE ".implode(' AND ',$listWhere)."
   ORDER BY CASE WHEN DATE(t.due_at)<CURDATE() THEN 0 WHEN DATE(t.due_at)=CURDATE() THEN 1 ELSE 2 END,t.due_at",
  $listParams
 );

 $stats=DB::one(
  "SELECT COUNT(*) total,
          SUM(CASE WHEN DATE(t.due_at)<CURDATE() THEN 1 ELSE 0 END) late_count,
          SUM(CASE WHEN DATE(t.due_at)=CURDATE() THEN 1 ELSE 0 END) today_count,
          SUM(CASE WHEN DATE(t.due_at)>CURDATE() THEN 1 ELSE 0 END) upcoming_count,
          SUM(CASE WHEN t.type='collection' THEN 1 ELSE 0 END) collection_count
   FROM tasks t WHERE ".implode(' AND ',$baseWhere),
  $baseParams
 )?:['total'=>0,'late_count'=>0,'today_count'=>0,'upcoming_count'=>0,'collection_count'=>0];

 $users=$teamAgenda?DB::all("SELECT id,name,role FROM users WHERE active=1 ORDER BY FIELD(role,'seller','collector','supervisor','admin'),name"):[];
 $workload=[];
 if($teamAgenda){
  $teamWhere=["t.status='pending'"];$teamParams=[];
  if($agendaType!=='all'){$teamWhere[]='t.type=?';$teamParams[]=$agendaType;}
  $workload=DB::all(
   "SELECT u.id,u.name,u.role,COUNT(*) total,
           SUM(CASE WHEN DATE(t.due_at)<CURDATE() THEN 1 ELSE 0 END) late_count,
           SUM(CASE WHEN DATE(t.due_at)=CURDATE() THEN 1 ELSE 0 END) today_count,
           SUM(CASE WHEN DATE(t.due_at)>CURDATE() THEN 1 ELSE 0 END) upcoming_count
    FROM tasks t JOIN users u ON u.id=t.assigned_user_id
    WHERE ".implode(' AND ',$teamWhere)."
    GROUP BY u.id,u.name,u.role
    ORDER BY late_count DESC,today_count DESC,u.name",
   $teamParams
  );
 }

 render('agenda',[
  'rows'=>$rows,'agendaUsers'=>$users,'agendaFilterUser'=>$filterUser,'teamAgenda'=>$teamAgenda,
  'agendaType'=>$agendaType,'agendaPeriod'=>$agendaPeriod,'agendaStats'=>$stats,'agendaWorkload'=>$workload,'flash'=>$flash
 ]);
});
$router->post('/agenda/{id}/done',function($p){
 Auth::requireLogin();CSRF::require($_POST['_token']??null);
 $u=Auth::user();$id=(int)$p['id'];$teamAgenda=in_array((string)$u['role'],['admin','supervisor'],true);
 if($teamAgenda)DB::exec("UPDATE tasks SET status='done',completed_at=NOW() WHERE id=? AND status='pending'",[$id]);
 else DB::exec("UPDATE tasks SET status='done',completed_at=NOW() WHERE id=? AND assigned_user_id=? AND status='pending'",[$id,(int)$u['id']]);
 $_SESSION['agenda_flash']=['type'=>'success','message'=>'Compromisso concluído.'];
 $params=[];$filterUser=max(0,(int)($_POST['user_id']??0));if($filterUser>0)$params['user_id']=$filterUser;
 $type=(string)($_POST['type']??'all');if(in_array($type,['sales','collection'],true))$params['type']=$type;
 $period=(string)($_POST['period']??'all');if(in_array($period,['late','today','next7','upcoming'],true))$params['period']=$period;
 redirect('/agenda'.($params?'?'.http_build_query($params):''));
});
$router->post('/agenda/{id}/reschedule',function($p){
 Auth::requireLogin();CSRF::require($_POST['_token']??null);
 $u=Auth::user();$id=(int)$p['id'];$teamAgenda=in_array((string)$u['role'],['admin','supervisor'],true);
 $value=trim((string)($_POST['due_at']??''));
 $date=DateTime::createFromFormat('Y-m-d\TH:i',$value);
 $valid=$date&&$date->format('Y-m-d\TH:i')===$value;
 if(!$valid){
  $_SESSION['agenda_flash']=['type'=>'danger','message'=>'Informe uma data e horário válidos para reagendar.'];
 }else{
  $formatted=$date->format('Y-m-d H:i:00');
  if($teamAgenda)$changed=DB::exec("UPDATE tasks SET due_at=? WHERE id=? AND status='pending'",[$formatted,$id]);
  else $changed=DB::exec("UPDATE tasks SET due_at=? WHERE id=? AND assigned_user_id=? AND status='pending'",[$formatted,$id,(int)$u['id']]);
  $_SESSION['agenda_flash']=$changed?['type'=>'success','message'=>'Compromisso reagendado com sucesso.']:['type'=>'danger','message'=>'Não foi possível reagendar este compromisso.'];
 }
 $params=[];$filterUser=max(0,(int)($_POST['user_id']??0));if($filterUser>0)$params['user_id']=$filterUser;
 $type=(string)($_POST['type']??'all');if(in_array($type,['sales','collection'],true))$params['type']=$type;
 $period=(string)($_POST['period']??'all');if(in_array($period,['late','today','next7','upcoming'],true))$params['period']=$period;
 redirect('/agenda'.($params?'?'.http_build_query($params):''));
});
$router->post('/agenda/{id}/edit',function($p){
 Auth::requireLogin();CSRF::require($_POST['_token']??null);
 $u=Auth::user();$id=(int)$p['id'];$teamAgenda=in_array((string)$u['role'],['admin','supervisor'],true);
 $title=trim((string)($_POST['title']??''));
 if($title===''||mb_strlen($title)>180){
  $_SESSION['agenda_flash']=['type'=>'danger','message'=>'Informe uma descrição de até 180 caracteres.'];
 }else{
  $allowed=$teamAgenda
   ?DB::one("SELECT id FROM tasks WHERE id=? AND status='pending'",[$id])
   :DB::one("SELECT id FROM tasks WHERE id=? AND assigned_user_id=? AND status='pending'",[$id,(int)$u['id']]);
  if($allowed){
   DB::exec("UPDATE tasks SET title=? WHERE id=?",[$title,$id]);
   $_SESSION['agenda_flash']=['type'=>'success','message'=>'Descrição do compromisso atualizada.'];
  }else $_SESSION['agenda_flash']=['type'=>'danger','message'=>'Você não pode editar este compromisso ou ele já foi encerrado.'];
 }
 $params=[];$filterUser=max(0,(int)($_POST['user_id']??0));if($filterUser>0)$params['user_id']=$filterUser;
 $type=(string)($_POST['type']??'all');if(in_array($type,['sales','collection'],true))$params['type']=$type;
 $period=(string)($_POST['period']??'all');if(in_array($period,['late','today','next7','upcoming'],true))$params['period']=$period;
 redirect('/agenda'.($params?'?'.http_build_query($params):''));
});
$router->post('/agenda/{id}/delete',function($p){
 Auth::requireLogin();CSRF::require($_POST['_token']??null);
 $u=Auth::user();$id=(int)$p['id'];$teamAgenda=in_array((string)$u['role'],['admin','supervisor'],true);
 if($teamAgenda)$changed=DB::exec("UPDATE tasks SET status='cancelled',completed_at=NOW() WHERE id=? AND status='pending'",[$id]);
 else $changed=DB::exec("UPDATE tasks SET status='cancelled',completed_at=NOW() WHERE id=? AND assigned_user_id=? AND status='pending'",[$id,(int)$u['id']]);
 $_SESSION['agenda_flash']=$changed?['type'=>'success','message'=>'Compromisso excluído da agenda.']:['type'=>'danger','message'=>'Você não pode excluir este compromisso ou ele já foi encerrado.'];
 $params=[];$filterUser=max(0,(int)($_POST['user_id']??0));if($filterUser>0)$params['user_id']=$filterUser;
 $type=(string)($_POST['type']??'all');if(in_array($type,['sales','collection'],true))$params['type']=$type;
 $period=(string)($_POST['period']??'all');if(in_array($period,['late','today','next7','upcoming'],true))$params['period']=$period;
 redirect('/agenda'.($params?'?'.http_build_query($params):''));
});

$router->get('/settings',function(){
 Auth::requireRole('admin','supervisor');
 $isAdmin=Auth::can('admin');if($isAdmin)OrderService::ensureCoreCatalogs();
 $flash=$_SESSION['settings_flash']??null;unset($_SESSION['settings_flash']);
 $monitorUsers=DB::all("SELECT id,name,email,role,seller_omie_code FROM users WHERE active=1 AND (role='collector' OR (role='seller' AND seller_omie_code IS NOT NULL AND TRIM(seller_omie_code)<>'')) ORDER BY FIELD(role,'seller','collector'),name");
 $monitorIds=contact_monitoring_user_ids();$monitorConfigured=is_array($monitorIds);if(!$monitorConfigured)$monitorIds=array_map(static fn($user)=>(int)$user['id'],$monitorUsers);
 $data=[
  'flash'=>$flash,
  'settingsAdmin'=>$isAdmin,'monitorUsers'=>$monitorUsers,'monitorIds'=>$monitorIds,'monitorConfigured'=>$monitorConfigured,
  'taskResults'=>task_result_catalog()
 ];
 if($isAdmin)$data=array_merge($data,[
  'defaults'=>OrderService::defaults(),'stages'=>DB::all("SELECT * FROM order_stages WHERE active=1 ORDER BY code"),'categories'=>DB::all("SELECT * FROM categories WHERE active=1 ORDER BY description"),
  'accounts'=>DB::all("SELECT * FROM financial_accounts WHERE active=1 ORDER BY name"),'terms'=>DB::all("SELECT * FROM payment_terms WHERE active=1 AND code<>'999' ORDER BY description"),'methods'=>DB::all("SELECT * FROM payment_methods ORDER BY description"),'documents'=>DB::all("SELECT * FROM document_types ORDER BY description"),'taxes'=>DB::all("SELECT * FROM tax_scenarios WHERE active=1 ORDER BY is_default DESC,name"),'stocks'=>DB::all("SELECT * FROM stock_locations WHERE active=1 ORDER BY is_default DESC,name"),'carriers'=>OrderService::carrierCandidates(),'profiles'=>OrderService::profiles()
 ]);
 render('settings',$data);
});
$router->post('/settings/contact-monitoring',function(){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 try{
  $requested=$_POST['monitor_user_ids']??[];if(!is_array($requested))$requested=[];
  $ids=[];foreach($requested as $id){$id=(int)$id;if($id>0)$ids[$id]=$id;}$ids=array_values($ids);
  if(!$ids)throw new RuntimeException('Selecione pelo menos um vendedor para o acompanhamento.');
  $placeholders=implode(',',array_fill(0,count($ids),'?'));
  $valid=DB::all("SELECT id FROM users WHERE id IN (".$placeholders.") AND active=1 AND (role='collector' OR (role='seller' AND seller_omie_code IS NOT NULL AND TRIM(seller_omie_code)<>''))",$ids);
  $validIds=array_map(static fn($user)=>(int)$user['id'],$valid);sort($validIds);sort($ids);
  if($validIds!==$ids)throw new RuntimeException('Um dos usuários selecionados está inativo ou não possui um vínculo operacional válido.');
  DB::exec("INSERT INTO settings(setting_key,value_json,updated_at) VALUES('contact_monitoring_users',?,NOW()) ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=NOW()",[json_encode(['user_ids'=>$ids],JSON_UNESCAPED_UNICODE)]);
  $_SESSION['settings_flash']=['type'=>'success','message'=>number_format(count($ids),0,',','.').' participante(s) definido(s) para o acompanhamento de contatos.'];
 }catch(Throwable $e){$_SESSION['settings_flash']=['type'=>'danger','message'=>'Não foi possível salvar a regra de acompanhamento: '.$e->getMessage()];}
 redirect('/settings');
});
$router->post('/settings/task-results',function(){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 try{
  $label=trim((string)($_POST['label']??''));if($label===''||mb_strlen($label)>80)throw new RuntimeException('Informe um nome de resultado com até 80 caracteres.');
  $contexts=array_values(array_intersect(['sales','collection'],array_map('strval',(array)($_POST['contexts']??[]))));
  if(!$contexts)throw new RuntimeException('Selecione onde o resultado será utilizado.');
  $catalog=task_result_catalog();
  foreach($catalog as $item)if(mb_strtolower(trim((string)$item['label']))===mb_strtolower($label))throw new RuntimeException('Já existe um resultado com esse nome.');
  $code='custom_'.substr(hash('sha256',mb_strtolower($label).'|'.date('c').'|'.Auth::id()),0,12);
  $catalog[]=['code'=>$code,'label'=>$label,'contexts'=>$contexts,'active'=>true,'system'=>false];
  save_task_result_catalog($catalog);
  $_SESSION['settings_flash']=['type'=>'success','message'=>'Resultado “'.$label.'” criado e disponibilizado nas tarefas selecionadas.'];
 }catch(Throwable $e){$_SESSION['settings_flash']=['type'=>'danger','message'=>'Não foi possível criar o resultado: '.$e->getMessage()];}
 redirect('/settings#task-results');
});
$router->post('/settings/task-results/{code}/toggle',function($p){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 $code=(string)($p['code']??'');$catalog=task_result_catalog();$found=false;
 foreach($catalog as &$item)if((string)$item['code']===$code){$item['active']=empty($item['active']);$found=true;break;}unset($item);
 if($found){save_task_result_catalog($catalog);$_SESSION['settings_flash']=['type'=>'success','message'=>'Disponibilidade do resultado atualizada.'];}
 else $_SESSION['settings_flash']=['type'=>'danger','message'=>'Resultado não encontrado.'];
 redirect('/settings#task-results');
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
 $portfolioOnly=$u['role']==='seller'&&(string)($_GET['portfolio']??'')==='mine';
 $clientScope=$portfolioOnly?'mine':(($u['role']==='seller'&&(string)($_GET['scope']??'all')==='unassigned')?'unassigned':'all');
 $uf=mb_strtoupper(trim((string)($_GET['uf']??'')),'UTF-8');
 if($uf!==''&&!preg_match('/^[A-Z]{2}$/',$uf))$uf='';
 $ddds=client_portfolio_ddds($_GET['ddds']??[],$uf);
 $tag=trim((string)($_GET['tag']??''));if(mb_strlen($tag)>190)$tag='';

 $baseWhere=['c.active=1'];$baseParams=[];
 if(($u['role']??'')==='seller'){
  if($portfolioOnly){$baseWhere[]='c.seller_omie_code=?';$baseParams[]=trim((string)($u['seller_omie_code']??''))?:'__NO_SELLER_LINK__';}
  elseif($clientScope==='unassigned')$baseWhere[]="(c.seller_omie_code IS NULL OR c.seller_omie_code='')";
 }
 if($uf!==''){$baseWhere[]='UPPER(TRIM(c.uf))=?';$baseParams[]=$uf;}
 if($ddds){$baseWhere[]=client_ddd_sql('c').' IN ('.implode(',',array_fill(0,count($ddds),'?')).')';array_push($baseParams,...$ddds);}
 if($tag!==''){$baseWhere[]=client_tag_filter_sql('c');$baseParams[]=$tag;}
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
 $orderColumns=['c.name','c.city','s.name','c.id','m.last_purchase_at','last_contact_at','m.last_purchase_at','m.revenue_12m','c.id'];
 $orderInput=$_GET['order']??[];
 $orderIndex=(int)(is_array($orderInput)?($orderInput[0]['column']??0):0);
 $orderBy=$orderColumns[$orderIndex]??'c.name';
 $orderDirection=strtolower((string)(is_array($orderInput)?($orderInput[0]['dir']??'asc'):'asc'))==='desc'?'DESC':'ASC';
 $rows=DB::all(
  "SELECT c.*,s.name seller_name,m.last_purchase_at,m.revenue_12m,m.orders_12m,m.avg_interval_days,
          CASE
           WHEN act.last_activity_at IS NULL THEN col.last_collection_at
           WHEN col.last_collection_at IS NULL THEN act.last_activity_at
           WHEN act.last_activity_at>=col.last_collection_at THEN act.last_activity_at
           ELSE col.last_collection_at
          END last_contact_at
   FROM clients c LEFT JOIN client_metrics m ON m.client_id=c.id
   LEFT JOIN sellers s ON s.omie_code=c.seller_omie_code
   LEFT JOIN (SELECT client_id,MAX(created_at) last_activity_at FROM activities GROUP BY client_id) act ON act.client_id=c.id
   LEFT JOIN (SELECT client_id,MAX(created_at) last_collection_at FROM collection_actions GROUP BY client_id) col ON col.client_id=c.id
   WHERE ".$sqlWhere." ORDER BY ".$orderBy." ".$orderDirection.",c.id ASC LIMIT ".$length." OFFSET ".$start,
  $params
 );

 $data=[];$canManage=Auth::can('admin','supervisor');$token=CSRF::token();
 foreach($rows as $row){
  $id=(int)$row['id'];$name=(string)$row['name'];$document=(string)($row['document']??'');
  $userSellerCode=trim((string)($u['seller_omie_code']??''));
  $canEdit=$canManage||($u['role']==='seller'&&$userSellerCode!==''&&(string)($row['seller_omie_code']??'')===$userSellerCode);
  $unassigned=trim((string)($row['seller_omie_code']??''))==='';
  $canOpen=$canManage||$u['role']!=='seller'||$canEdit||$unassigned;
  $initial=mb_strtoupper(mb_substr($name,0,1));
  $identity='<div class="client-table-identity"><span>'.e($initial).'</span><div>'.($canOpen?'<a href="'.APP_URL.'/clients/'.$id.'"><strong>'.e($name).'</strong></a>':'<strong>'.e($name).'</strong>').'<small>'.e($document!==''?$document:'Documento não informado').'</small></div></div>';
  $location=trim((string)($row['city']??'').' / '.(string)($row['uf']??''),' /');
  $phoneDigits=preg_replace('/\D+/','',(string)($row['phone']??''));$rowDdd=strlen($phoneDigits)>=2?substr($phoneDigits,0,2):'';
  $locationHtml='<span class="client-location"><i class="fa-solid fa-location-dot"></i>'.e($location!==''?$location:'Não informado').($rowDdd!==''?'<b>DDD '.e($rowDdd).'</b>':'').'</span>';
  if(!empty($row['seller_name']))$sellerHtml='<span class="client-seller"><i class="fa-solid fa-user-tie"></i><span><strong>'.e($row['seller_name']).'</strong><small>'.e($row['seller_omie_code']).'</small></span></span>';
  elseif(!empty($row['seller_omie_code']))$sellerHtml='<span class="client-seller"><i class="fa-solid fa-user-tie"></i><span><strong>'.e($row['seller_omie_code']).'</strong><small>Vendedor não sincronizado</small></span></span>';
  else $sellerHtml='<span class="client-seller unassigned"><i class="fa-solid fa-user-slash"></i><span><strong>Sem vendedor</strong><small>'.($u['role']==='seller'?'Atendimento compartilhado':'Disponível para vincular').'</small></span></span>';
  $rowTags=client_tags_from_raw($row['raw_json']??null);$visibleTags=array_slice($rowTags,0,3);
  $tagsHtml='<div class="client-tag-list" title="'.e(implode(', ',$rowTags)).'">';foreach($visibleTags as $rowTag)$tagsHtml.='<span>'.e($rowTag).'</span>';if(count($rowTags)>3)$tagsHtml.='<b>+'.(count($rowTags)-3).'</b>';if(!$rowTags)$tagsHtml.='<small>Sem tag</small>';$tagsHtml.='</div>';
  $cycle=CRMService::cycle($row['last_purchase_at']??null,(float)($row['avg_interval_days']??0));
  $cycleHtml='<span class="cycle cycle-'.e($cycle['status']).'">'.e($cycle['label']).'</span>';
  $orders=(int)($row['orders_12m']??0);
  $purchaseHtml='<strong>'.brdate($row['last_purchase_at']??null).'</strong><small>'.($orders>0?$orders.' pedido(s) em 12 meses':'Sem pedidos recentes').'</small>';
  $lastContactAt=trim((string)($row['last_contact_at']??''));
  if($lastContactAt===''){
   $daysContactHtml='<span class="tdc-contact-days never"><strong>Nunca</strong></span>';
  }else{
   $contactDays=max(0,(int)floor((strtotime(date('Y-m-d'))-strtotime(date('Y-m-d',strtotime($lastContactAt))))/86400));
   $contactClass=$contactDays<=30?'ok':($contactDays<=60?'warning':'late');
   $daysContactHtml='<span class="tdc-contact-days '.$contactClass.'"><strong>'.$contactDays.'</strong></span>';
  }
  $openLabel=$canEdit?'Abrir cliente':($unassigned?'Selecionar cliente disponível':'Cliente vinculado a outro vendedor');
  $actions='<div class="client-action-group">'.($canOpen?'<a class="client-action client-action-view" href="'.APP_URL.'/clients/'.$id.'" title="'.e($openLabel).'"><i class="fa-regular fa-eye"></i><span>Ver</span></a>':'<span class="client-action client-action-locked" title="'.e($openLabel).'"><i class="fa-solid fa-lock"></i><span>Vinculado</span></span>');
  if($canEdit)$actions.='<a class="client-action client-action-edit" href="'.APP_URL.'/clients/'.$id.'/edit" title="Editar cliente"><i class="fa-regular fa-pen-to-square"></i><span>Editar</span></a>';
  if($canManage)$actions.='<form method="post" action="'.APP_URL.'/clients/'.$id.'/delete-local"><input type="hidden" name="_token" value="'.e($token).'"><button class="client-action client-action-local" type="submit" title="Remover apenas do CRM" data-confirm="Excluir somente do CRM local? Nenhuma chamada será feita à Omie."><i class="fa-solid fa-database"></i><span>CRM</span></button></form><form method="post" action="'.APP_URL.'/clients/'.$id.'/delete"><input type="hidden" name="_token" value="'.e($token).'"><button class="client-action client-action-delete" type="submit" title="Excluir do CRM e da Omie" data-confirm="Excluir este cliente na Omie e também no CRM?"><i class="fa-regular fa-trash-can"></i><span>Excluir</span></button></form>';
  $actions.='</div>';
  $data[]=[$identity,$locationHtml,$sellerHtml,$tagsHtml,$cycleHtml,$daysContactHtml,$purchaseHtml,'<strong class="client-revenue">'.money($row['revenue_12m']??0).'</strong>',$actions];
 }
 json_response(['draw'=>$draw,'recordsTotal'=>$recordsTotal,'recordsFiltered'=>$recordsFiltered,'data'=>$data]);
});

$router->get('/api/clients',function(){Auth::requireRole('admin','supervisor','seller');$u=Auth::user();$q=trim((string)($_GET['q']??''));$w=['active=1'];$p=[];if($u['role']==='seller'){$w[]="(seller_omie_code=? OR seller_omie_code IS NULL OR seller_omie_code='')";$p[]=$u['seller_omie_code'];}if($q!==''){$w[]="(name LIKE ? OR document LIKE ? OR omie_code LIKE ? OR JSON_UNQUOTE(JSON_EXTRACT(raw_json,'$.codigo_cliente_integracao')) LIKE ? OR CAST(id AS CHAR)=?)";$x='%'.$q.'%';array_push($p,$x,$x,$x,$x,$q);}json_response(['items'=>DB::all("SELECT id,omie_code,JSON_UNQUOTE(JSON_EXTRACT(raw_json,'$.codigo_cliente_integracao')) client_integration_code,name,document,email,city,uf,(SELECT assigned_user_id FROM collection_cases WHERE client_id=clients.id) collection_assigned_user_id FROM clients WHERE ".implode(' AND ',$w)." ORDER BY CASE WHEN seller_omie_code=? THEN 0 ELSE 1 END,name LIMIT 25",array_merge($p,[$u['role']==='seller'?$u['seller_omie_code']:'']))]);});
$router->get('/api/products',function(){Auth::requireRole('admin','supervisor','seller');$q=trim((string)($_GET['q']??''));$w=['active=1'];$p=[];if($q!==''){$w[]='(description LIKE ? OR sku LIKE ? OR omie_code LIKE ?)';$x='%'.$q.'%';array_push($p,$x,$x,$x);}$items=DB::all("SELECT id,omie_code,sku,description,unit,unit_price,stock_qty,raw_json FROM products WHERE ".implode(' AND ',$w)." ORDER BY description LIMIT 30",$p);foreach($items as &$item){$raw=json_decode((string)($item['raw_json']??''),true);$item['net_weight']=(float)($raw['peso_liq']??0);$item['gross_weight']=(float)($raw['peso_bruto']??0);unset($item['raw_json']);}unset($item);json_response(['items'=>$items]);});


register_opportunity_routes($router);
