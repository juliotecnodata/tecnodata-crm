<?php
$router->get('/login',function(){if(Auth::check())redirect('/');render('login');});
$router->post('/login',function(){CSRF::require($_POST['_token']??null);if(Auth::attempt((string)($_POST['email']??''),(string)($_POST['password']??'')))redirect('/');render('login',['error'=>'E-mail ou senha inválidos.']);});
$router->post('/logout',function(){CSRF::require($_POST['_token']??null);Auth::logout();redirect('/login');});

$router->get('/',function(){Auth::requireLogin();$u=Auth::user();render('dashboard',['u'=>$u,'data'=>CRMService::dashboard($u)]);});
$router->get('/result',function(){
 Auth::requireLogin();$month=(string)($_GET['month']??date('Y-m'));$u=Auth::user();
 if(in_array($u['role'],['admin','supervisor'],true))render('management_result',['management'=>GoalService::managementMonth($month),'month'=>$month]);
 else render('result',['result'=>GoalService::userMonth(Auth::id(),$month),'month'=>$month]);
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

$router->get('/clients',function(){Auth::requireRole('admin','supervisor','seller');$u=Auth::user();$flash=$_SESSION['clients_flash']??null;unset($_SESSION['clients_flash']);$q=trim((string)($_GET['q']??''));$w=['c.active=1'];$p=[];if($u['role']==='seller'){$w[]='c.seller_omie_code=?';$p[]=$u['seller_omie_code'];}if($q!==''){$w[]='(c.name LIKE ? OR c.document LIKE ? OR c.city LIKE ?)';$x='%'.$q.'%';array_push($p,$x,$x,$x);}$rows=DB::all("SELECT c.*,m.last_purchase_at,m.revenue_12m,m.orders_12m,m.avg_interval_days FROM clients c LEFT JOIN client_metrics m ON m.client_id=c.id WHERE ".implode(' AND ',$w)." ORDER BY c.name LIMIT 300",$p);foreach($rows as &$r)$r['cycle']=CRMService::cycle($r['last_purchase_at']??null,(float)($r['avg_interval_days']??0));unset($r);render('clients',['rows'=>$rows,'q'=>$q,'flash'=>$flash]);});
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
$router->post('/clients/{id}/delete',function($p){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);$id=(int)$p['id'];
 try{
  $result=ClientService::deleteFromOmie($id,Auth::user());
  $_SESSION['clients_flash']=[
   'type'=>'success',
   'message'=>($result['status']??'')==='local_deleted'
    ?'Cliente local removido com sucesso. Como ainda não estava integrado, nenhuma chamada à Omie foi necessária.'
    :'Cliente excluído com sucesso na Omie e removido do CRM local.'
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
 if($u['role']==='seller'&&(string)$c['seller_omie_code']!==(string)$u['seller_omie_code']){http_response_code(403);exit('Cliente fora da sua carteira.');}
 $a=DB::all("SELECT a.*,u.name user_name FROM activities a JOIN users u ON u.id=a.user_id WHERE a.client_id=? ORDER BY a.created_at DESC LIMIT 30",[$id]);
 $o=DB::all("SELECT * FROM orders WHERE client_omie_code=? ORDER BY order_date DESC,id DESC LIMIT 20",[$c['omie_code']]);
 $form=ClientService::formFromClient($c);
 $sellerName=$c['seller_omie_code']?DB::scalar("SELECT name FROM sellers WHERE omie_code=?",[(string)$c['seller_omie_code']]):null;
 render('client',['client'=>$c,'activities'=>$a,'orders'=>$o,'cycle'=>CRMService::cycle($c['last_purchase_at']??null,(float)($c['avg_interval_days']??0)),'flash'=>$flash,'formData'=>$form,'sellerName'=>$sellerName]);
});
$router->post('/clients/{id}/activity',function($p){Auth::requireRole('admin','supervisor','seller');CSRF::require($_POST['_token']??null);$id=(int)$p['id'];$u=Auth::user();$c=DB::one("SELECT * FROM clients WHERE id=?",[$id]);if(!$c)exit('Cliente inválido.');if($u['role']==='seller'&&(string)$c['seller_omie_code']!==(string)$u['seller_omie_code']){http_response_code(403);exit('Sem permissão.');}DB::exec("INSERT INTO activities(client_id,user_id,channel,result,notes,next_at,created_at) VALUES(?,?,?,?,?,?,NOW())",[$id,(int)$u['id'],(string)($_POST['channel']??'phone'),(string)($_POST['result']??'contact'),trim((string)($_POST['notes']??'')),($_POST['next_at']??'')?:null]);if(!empty($_POST['next_at']))DB::exec("INSERT INTO tasks(client_id,assigned_user_id,type,title,due_at,status,created_at) VALUES(?,?,'sales','Retorno comercial',?,'pending',NOW())",[$id,(int)$u['id'],$_POST['next_at']]);redirect('/clients/'.$id);});

$router->get('/orders',function(){Auth::requireRole('admin','supervisor','seller');$u=Auth::user();$w=[];$p=[];if($u['role']==='seller'){$w[]='o.seller_omie_code=?';$p[]=$u['seller_omie_code'];}$sql="SELECT o.*,c.name client_name FROM orders o LEFT JOIN clients c ON c.omie_code=o.client_omie_code".($w?' WHERE '.implode(' AND ',$w):'')." ORDER BY o.order_date DESC,o.id DESC LIMIT 500";render('orders',['orders'=>DB::all($sql,$p)]);});
$router->get('/services',function(){
 Auth::requireRole('admin','supervisor','seller');$u=Auth::user();$month=(string)($_GET['month']??date('Y-m'));if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');
 $start=$month.'-01';$next=date('Y-m-d',strtotime($start.' +1 month'));$w=['so.service_date>=?','so.service_date<?'];$p=[$start,$next];
 if($u['role']==='seller'){$w[]='so.seller_omie_code=?';$p[]=(string)$u['seller_omie_code'];}
 $rows=DB::all("SELECT so.*,c.name client_name,s.name seller_name FROM service_orders so LEFT JOIN clients c ON c.omie_code=so.client_omie_code LEFT JOIN sellers s ON s.omie_code=so.seller_omie_code WHERE ".implode(' AND ',$w)." ORDER BY so.service_date DESC,so.id DESC LIMIT 500",$p);
 $total=0.0;foreach($rows as $row)if(!str_contains(mb_strtoupper((string)($row['status']??'')),'CANCEL'))$total+=(float)$row['total'];
 render('services',['rows'=>$rows,'month'=>$month,'total'=>$total]);
});
$router->get('/orders/new',function(){
 Auth::requireRole('admin','supervisor','seller');$r=OrderService::ready();
 render('order_new',[
  'ready'=>$r,
  'terms'=>DB::all("SELECT * FROM payment_terms WHERE active=1 AND code<>'999' ORDER BY description"),
  'methods'=>DB::all("SELECT * FROM payment_methods ORDER BY description"),
  'documents'=>DB::all("SELECT * FROM document_types ORDER BY description"),
  'stages'=>DB::all("SELECT * FROM order_stages WHERE active=1 ORDER BY code"),
  'categories'=>DB::all("SELECT * FROM categories WHERE active=1 ORDER BY description"),
  'accounts'=>DB::all("SELECT * FROM financial_accounts WHERE active=1 ORDER BY name"),
  'taxes'=>DB::all("SELECT * FROM tax_scenarios WHERE active=1 ORDER BY is_default DESC,name"),
  'stocks'=>DB::all("SELECT * FROM stock_locations WHERE active=1 ORDER BY is_default DESC,name"),
  'profiles'=>OrderService::profiles(),
  'sellers'=>Auth::can('admin','supervisor')?DB::all("SELECT * FROM sellers WHERE active=1 ORDER BY name"):[],
  'prefill'=>(int)($_GET['client_id']??0)
 ]);
});
$router->post('/orders',function(){Auth::requireRole('admin','supervisor','seller');CSRF::require($_POST['_token']??null);try{if(($_POST['submit_mode']??'send')==='preview'){$b=OrderService::build($_POST,Auth::user());$_SESSION['preview']=$b['payload'];$_SESSION['old']=$_POST;redirect('/orders/new?client_id='.(int)($_POST['client_id']??0));}$r=OrderService::send($_POST,Auth::user());$_SESSION['success']='Pedido enviado para Omie'.($r['number']?' • nº '.$r['number']:'').' • '.money($r['total']);redirect('/orders');}catch(Throwable $e){$_SESSION['error']=$e->getMessage();$_SESSION['old']=$_POST;redirect('/orders/new?client_id='.(int)($_POST['client_id']??0));}});

$router->get('/collection',function(){Auth::requireRole('admin','supervisor','collector');$view=(string)($_GET['view']??'open');$rows=DB::all("SELECT cc.*,c.name,c.document,c.uf,u.name assigned_name FROM collection_cases cc JOIN clients c ON c.id=cc.client_id LEFT JOIN users u ON u.id=cc.assigned_user_id WHERE cc.status=? ORDER BY cc.open_amount DESC LIMIT 500",[$view==='settled'?'settled':'open']);render('collection',['rows'=>$rows,'view'=>$view]);});
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

$router->get('/settings',function(){Auth::requireRole('admin');render('settings',['defaults'=>OrderService::defaults(),'stages'=>DB::all("SELECT * FROM order_stages WHERE active=1 ORDER BY code"),'categories'=>DB::all("SELECT * FROM categories WHERE active=1 ORDER BY description"),'accounts'=>DB::all("SELECT * FROM financial_accounts WHERE active=1 ORDER BY name"),'terms'=>DB::all("SELECT * FROM payment_terms WHERE active=1 AND code<>'999' ORDER BY description"),'methods'=>DB::all("SELECT * FROM payment_methods ORDER BY description"),'documents'=>DB::all("SELECT * FROM document_types ORDER BY description"),'taxes'=>DB::all("SELECT * FROM tax_scenarios WHERE active=1 ORDER BY is_default DESC,name"),'stocks'=>DB::all("SELECT * FROM stock_locations WHERE active=1 ORDER BY is_default DESC,name"),'profiles'=>OrderService::profiles()]);});
$router->post('/settings',function(){Auth::requireRole('admin');CSRF::require($_POST['_token']??null);OrderService::saveDefaults($_POST);DB::exec("UPDATE financial_accounts SET selected=0");foreach((array)($_POST['collection_accounts']??[]) as $c)DB::exec("UPDATE financial_accounts SET selected=1 WHERE omie_code=?",[(string)$c]);redirect('/settings');});
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
$router->post('/goals/{id}',function($p){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 GoalService::save((int)$p['id'],(string)($_POST['month']??date('Y-m')),$_POST,Auth::id());
 redirect('/goals?month='.urlencode((string)($_POST['month']??date('Y-m'))));
});
$router->post('/goals/general',function(){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 $month=(string)($_POST['month']??date('Y-m'));GoalService::saveGeneral($month,$_POST);
 redirect('/goals?month='.urlencode($month));
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

$router->get('/sync',function(){Auth::requireRole('admin');render('sync',['modules'=>SyncService::modules(),'states'=>DB::all("SELECT * FROM sync_state ORDER BY module_key")]);});
$router->post('/api/sync',function(){Auth::requireRole('admin');CSRF::require($_POST['_token']??null);try{json_response(['ok'=>true]+SyncService::run((string)($_POST['module']??''),(int)($_POST['page']??1)));}catch(Throwable $e){json_response(['ok'=>false,'error'=>$e->getMessage()],422);}});
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

$router->get('/api/clients',function(){Auth::requireRole('admin','supervisor','seller');$u=Auth::user();$q=trim((string)($_GET['q']??''));$w=['active=1'];$p=[];if($u['role']==='seller'){$w[]='seller_omie_code=?';$p[]=$u['seller_omie_code'];}if($q!==''){$w[]='(name LIKE ? OR document LIKE ? OR CAST(id AS CHAR)=?)';$x='%'.$q.'%';array_push($p,$x,$x,$q);}json_response(['items'=>DB::all("SELECT id,omie_code,name,document,email,city,uf FROM clients WHERE ".implode(' AND ',$w)." ORDER BY name LIMIT 25",$p)]);});
$router->get('/api/products',function(){Auth::requireRole('admin','supervisor','seller');$q=trim((string)($_GET['q']??''));$w=['active=1'];$p=[];if($q!==''){$w[]='(description LIKE ? OR sku LIKE ? OR omie_code LIKE ?)';$x='%'.$q.'%';array_push($p,$x,$x,$x);}json_response(['items'=>DB::all("SELECT id,omie_code,sku,description,unit,unit_price,stock_qty FROM products WHERE ".implode(' AND ',$w)." ORDER BY description LIMIT 30",$p)]);});
