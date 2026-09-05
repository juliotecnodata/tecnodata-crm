<?php
final class OmieClient {
 private array $endpoints=[
  'clients'=>'https://app.omie.com.br/api/v1/geral/clientes/',
  'sellers'=>'https://app.omie.com.br/api/v1/geral/vendedores/',
  'products'=>'https://app.omie.com.br/api/v1/geral/produtos/',
  'categories'=>'https://app.omie.com.br/api/v1/geral/categorias/',
  'accounts'=>'https://app.omie.com.br/api/v1/geral/contacorrente/',
  'stages'=>'https://app.omie.com.br/api/v1/produtos/etapafat/',
  'payment_terms'=>'https://app.omie.com.br/api/v1/produtos/formaspagvendas/',
  'tax_scenarios'=>'https://app.omie.com.br/api/v1/geral/cenarios/',
  'stock_locations'=>'https://app.omie.com.br/api/v1/estoque/local/',
  'payment_methods'=>'https://app.omie.com.br/api/v1/geral/meiospagamento/',
  'document_types'=>'https://app.omie.com.br/api/v1/geral/tiposdoc/',
  'orders'=>'https://app.omie.com.br/api/v1/produtos/pedido/',
  'services'=>'https://app.omie.com.br/api/v1/servicos/os/',
  'financial'=>'https://app.omie.com.br/api/v1/financas/mf/',
 ];
 public function call(string $endpoint,string $call,array $param): array{
  $url=$this->endpoints[$endpoint]??null;if(!$url)throw new RuntimeException('Endpoint Omie desconhecido.');
  $cfg=$GLOBALS['config']['omie'];
  $payload=['call'=>$call,'app_key'=>$cfg['app_key'],'app_secret'=>$cfg['app_secret'],'param'=>[$param]];
  $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: application/json','Accept-Encoding: identity'],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE),CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_TIMEOUT=>(int)($cfg['timeout']??60),CURLOPT_ENCODING=>'identity']);
  $raw=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$err=curl_error($ch);curl_close($ch);
  if($raw===false||$err)throw new RuntimeException('Falha de comunicação Omie: '.$err);
  $data=json_decode($raw,true);if(!is_array($data))throw new RuntimeException('Resposta inválida Omie HTTP '.$http.'.');
  if($http>=400||isset($data['faultstring']))throw new RuntimeException((string)($data['faultstring']??$data['message']??('Erro Omie HTTP '.$http)));
  return $data;
 }
}

final class CRMService {
 public static function cycle(?string $last,float $avg): array{
  if(!$last||$avg<=0)return ['status'=>'unknown','label'=>'Sem ciclo','date'=>null,'delta'=>null];
  $date=date('Y-m-d',strtotime($last.' +'.max(1,(int)round($avg)).' days'));$delta=(int)floor((strtotime($date)-strtotime(date('Y-m-d')))/86400);
  if($delta>7)return ['status'=>'ok','label'=>'Dentro do ciclo','date'=>$date,'delta'=>$delta];
  if($delta>=0)return ['status'=>'soon','label'=>'Hora de aproximar','date'=>$date,'delta'=>$delta];
  if(abs($delta)<=10)return ['status'=>'window','label'=>'Janela de compra','date'=>$date,'delta'=>$delta];
  return ['status'=>'late','label'=>'Ciclo vencido','date'=>$date,'delta'=>$delta];
 }
 public static function dashboard(array $u): array{
  $start=date('Y-m-01');$next=date('Y-m-d',strtotime($start.' +1 month'));
  if($u['role']==='seller'){
   $sales=(float)(DB::scalar("SELECT COALESCE(SUM(total),0) FROM orders WHERE seller_omie_code=? AND order_date>=? AND order_date<? AND status<>'CANCELADO'",[$u['seller_omie_code'],$start,$next])??0);
   $clients=(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE seller_omie_code=? AND active=1",[$u['seller_omie_code']])??0);
   $tasks=(int)(DB::scalar("SELECT COUNT(*) FROM tasks WHERE assigned_user_id=? AND status='pending'",[(int)$u['id']])??0);return compact('sales','clients','tasks');
  }
  if($u['role']==='collector'){
   $debt=(float)(DB::scalar("SELECT COALESCE(SUM(open_amount),0) FROM collection_cases WHERE status='open'")??0);
   $recovered=(float)(DB::scalar("SELECT COALESCE(SUM(amount),0) FROM collection_actions WHERE assigned_user_id=? AND result='payment' AND created_at>=? AND created_at<?",[(int)$u['id'],$start,$next])??0);
   $worked=(int)(DB::scalar("SELECT COUNT(DISTINCT client_id) FROM collection_actions WHERE assigned_user_id=? AND created_at>=? AND created_at<?",[(int)$u['id'],$start,$next])??0);return compact('debt','recovered','worked');
  }
  $sales=(float)(DB::scalar("SELECT COALESCE(SUM(total),0) FROM orders WHERE order_date>=? AND order_date<? AND status<>'CANCELADO'",[$start,$next])??0);
  $debt=(float)(DB::scalar("SELECT COALESCE(SUM(open_amount),0) FROM collection_cases WHERE status='open'")??0);
  $clients=(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1")??0);
  $late=(int)(DB::scalar("SELECT COUNT(*) FROM tasks WHERE status='pending' AND due_at<NOW()")??0);return compact('sales','debt','clients','late');
 }
}

final class OrderService {
 public static function defaults(): array{
  $j=DB::scalar("SELECT value_json FROM settings WHERE setting_key='order_defaults'");
  $d=$j?json_decode((string)$j,true):[];
  return is_array($d)?$d:[];
 }
 public static function saveDefaults(array $i): void{
  $d=[
   'stage'=>(string)($i['stage']??''),'category'=>(string)($i['category']??''),'account'=>(string)($i['account']??''),
   'payment_term'=>(string)($i['payment_term']??''),'payment_method'=>(string)($i['payment_method']??''),
   'document_type'=>(string)($i['document_type']??''),'tax_scenario'=>(string)($i['tax_scenario']??''),
   'stock_location'=>(string)($i['stock_location']??''),'consumer_final'=>(string)($i['consumer_final']??'S')==='N'?'N':'S',
   'send_email'=>!empty($i['send_email'])?'S':'N','freight_mode'=>(string)($i['freight_mode']??'9')
  ];
  if($d['stage']===''||!DB::one("SELECT 1 FROM order_stages WHERE code=? AND active=1",[$d['stage']]))throw new RuntimeException('Etapa padrão inválida.');
  if($d['category']===''||!DB::one("SELECT 1 FROM categories WHERE code=? AND active=1",[$d['category']]))throw new RuntimeException('Categoria padrão inválida.');
  if($d['account']===''||!DB::one("SELECT 1 FROM financial_accounts WHERE omie_code=? AND active=1",[$d['account']]))throw new RuntimeException('Conta corrente padrão inválida.');
  if($d['payment_term']===''||$d['payment_term']==='999'||!DB::one("SELECT 1 FROM payment_terms WHERE code=? AND active=1",[$d['payment_term']]))throw new RuntimeException('Condição de pagamento padrão inválida.');
  DB::exec("INSERT INTO settings(setting_key,value_json,updated_at) VALUES('order_defaults',?,NOW())
            ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=NOW()",[json_encode($d,JSON_UNESCAPED_UNICODE)]);
 }
 public static function ready(): array{
  $d=self::defaults();$m=[];
  foreach(['stage','category','account','payment_term'] as $k)if(empty($d[$k]))$m[]=$k;
  if((int)(DB::scalar("SELECT COUNT(*) FROM products WHERE active=1 AND unit_price>0")??0)===0)$m[]='products';
  if((int)(DB::scalar("SELECT COUNT(*) FROM payment_terms WHERE active=1 AND code<>'999'")??0)===0)$m[]='payment_terms';
  return ['ok'=>!$m,'missing'=>$m,'defaults'=>$d];
 }
 public static function build(array $i,array $u): array{
  $r=self::ready();if(!$r['ok'])throw new RuntimeException('Configuração incompleta: '.implode(', ',$r['missing']).'.');$d=$r['defaults'];
  $client=DB::one("SELECT * FROM clients WHERE id=? AND active=1",[(int)($i['client_id']??0)]);if(!$client)throw new RuntimeException('Cliente inválido.');
  if($u['role']==='seller'&&(string)$client['seller_omie_code']!==(string)$u['seller_omie_code'])throw new RuntimeException('Cliente fora da sua carteira.');
  $seller=$u['role']==='seller'?(string)$u['seller_omie_code']:(string)($i['seller_omie_code']??'');if($seller===''||!DB::one("SELECT 1 FROM sellers WHERE omie_code=? AND active=1",[$seller]))throw new RuntimeException('Vendedor obrigatório ou inválido.');
  $term=(string)($i['payment_term']??$d['payment_term']);if($term==='999'||!DB::one("SELECT 1 FROM payment_terms WHERE code=? AND active=1",[$term]))throw new RuntimeException('Condição de pagamento inválida.');
  $items=json_decode((string)($i['items_json']??'[]'),true);if(!is_array($items)||!$items)throw new RuntimeException('Inclua ao menos um produto.');

  $requestToken=preg_replace('/[^A-Za-z0-9_-]/','',(string)($i['request_token']??''));
  if($requestToken==='')$requestToken=date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,8));
  $integration=substr('TDCRM-'.$requestToken,0,60);

  $existing=DB::one("SELECT * FROM omie_order_logs WHERE integration_code=? AND status='success' LIMIT 1",[$integration]);
  if($existing)return ['payload'=>json_decode((string)$existing['request_json'],true),'client'=>$client,'seller'=>$seller,'total'=>(float)$existing['total'],'integration'=>$integration,'existing'=>$existing];

  $det=[];$total=0.0;$n=0;
  foreach($items as $it){
   $p=DB::one("SELECT * FROM products WHERE id=? AND active=1",[(int)($it['product_id']??0)]);if(!$p)throw new RuntimeException('Produto inválido.');
   $q=(float)($it['quantity']??0);if($q<=0)throw new RuntimeException('Quantidade inválida para '.$p['description'].'.');
   $price=$u['role']==='seller'?(float)$p['unit_price']:(float)($it['unit_price']??$p['unit_price']);
   $discount=$u['role']==='seller'?0:max(0,(float)($it['discount']??0));if($price<=0)throw new RuntimeException('Produto sem preço: '.$p['description'].'.');
   $line=max(0,$q*$price-$discount);if($line<=0)throw new RuntimeException('Total inválido para '.$p['description'].'.');$total+=$line;$n++;
   $prod=['codigo_produto'=>(int)$p['omie_code'],'descricao'=>(string)$p['description'],'quantidade'=>$q,'unidade'=>(string)($p['unit']?:'UN'),'valor_unitario'=>$price];
   if($discount>0){$prod['tipo_desconto']='V';$prod['valor_desconto']=$discount;}
   if(!empty($p['ncm']))$prod['ncm']=$p['ncm'];
   $detail=['ide'=>['codigo_item_integracao'=>(string)$n],'produto'=>$prod];
   if(!empty($d['stock_location']))$detail['inf_adic']=['codigo_local_estoque'=>(int)$d['stock_location']];
   $det[]=$detail;
  }

  $forecast=(string)($i['forecast_date']??date('Y-m-d'));if(!strtotime($forecast)||$forecast<date('Y-m-d'))throw new RuntimeException('Previsão inválida.');
  $cab=['codigo_pedido_integracao'=>$integration,'codigo_cliente'=>(int)$client['omie_code'],'data_previsao'=>date('d/m/Y',strtotime($forecast)),'etapa'=>(string)$d['stage'],'codigo_parcela'=>$term];
  if(!empty($d['tax_scenario']))$cab['codigo_cenario_impostos']=(int)$d['tax_scenario'];
  $info=['codigo_categoria'=>(string)$d['category'],'codigo_conta_corrente'=>(int)$d['account'],'consumidor_final'=>(string)($d['consumer_final']??'S'),'enviar_email'=>(string)($d['send_email']??'N'),'codVend'=>(int)$seller];
  $pm=(string)($i['payment_method']??$d['payment_method']??'');if($pm!=='')$info['meio_pagamento']=$pm;
  if(!empty($d['document_type']))$info['tipo_documento']=$d['document_type'];
  if(($d['send_email']??'N')==='S'&&!empty($client['email']))$info['utilizar_emails']=$client['email'];
  foreach(['customer_order'=>'numero_pedido_cliente','contract'=>'numero_contrato','contact'=>'contato'] as $from=>$to){$v=trim((string)($i[$from]??''));if($v!=='')$info[$to]=$v;}
  $payload=['cabecalho'=>$cab,'det'=>$det,'frete'=>['modalidade'=>(string)($d['freight_mode']??'9')],'informacoes_adicionais'=>$info];
  $notes=trim((string)($i['notes']??''));if($notes!=='')$payload['observacoes']=['obs_venda'=>$notes];
  return ['payload'=>$payload,'client'=>$client,'seller'=>$seller,'total'=>$total,'integration'=>$integration];
 }
 public static function send(array $i,array $u): array{
  $b=self::build($i,$u);
  if(!empty($b['existing']))return ['code'=>(string)($b['existing']['omie_order_code']??''),'number'=>(string)($b['existing']['omie_order_number']??''),'total'=>$b['total'],'reused'=>true];
  $o=new OmieClient();
  try{
   $res=$o->call('orders','IncluirPedido',$b['payload']);
   $code=(string)($res['codigo_pedido']??'');$number=(string)($res['numero_pedido']??'');$verify=null;
   if($code!==''){try{$verify=$o->call('orders','ConsultarPedido',['codigo_pedido'=>(int)$code]);}catch(Throwable){}}
   DB::exec("INSERT INTO omie_order_logs(integration_code,omie_order_code,omie_order_number,client_id,seller_omie_code,user_id,total,request_json,response_json,status,created_at)
             VALUES(?,?,?,?,?,?,?,?,?,'success',NOW())",
      [$b['integration'],$code?:null,$number?:null,(int)$b['client']['id'],$b['seller'],(int)$u['id'],$b['total'],json_encode($b['payload'],JSON_UNESCAPED_UNICODE),json_encode(['include'=>$res,'verify'=>$verify],JSON_UNESCAPED_UNICODE)]);
   return ['code'=>$code,'number'=>$number,'total'=>$b['total']];
  }catch(Throwable $e){
   try{
    $found=$o->call('orders','ConsultarPedido',['codigo_pedido_integracao'=>$b['integration']]);
    $cab=$found['pedido_venda_produto']['cabecalho']??$found['cabecalho']??[];
    $code=(string)($cab['codigo_pedido']??'');
    if($code!==''){
     $number=(string)($cab['numero_pedido']??'');
     DB::exec("INSERT INTO omie_order_logs(integration_code,omie_order_code,omie_order_number,client_id,seller_omie_code,user_id,total,request_json,response_json,status,created_at)
               VALUES(?,?,?,?,?,?,?,?,?,'success',NOW())",
       [$b['integration'],$code,$number?:null,(int)$b['client']['id'],$b['seller'],(int)$u['id'],$b['total'],json_encode($b['payload'],JSON_UNESCAPED_UNICODE),json_encode(['recovered'=>$found],JSON_UNESCAPED_UNICODE)]);
     return ['code'=>$code,'number'=>$number,'total'=>$b['total'],'recovered'=>true];
    }
   }catch(Throwable){}
   DB::exec("INSERT INTO omie_order_logs(integration_code,client_id,seller_omie_code,user_id,total,request_json,status,error_message,created_at)
             VALUES(?,?,?,?,?,?,'error',?,NOW()) ON DUPLICATE KEY UPDATE error_message=VALUES(error_message),created_at=NOW()",
      [$b['integration'],(int)$b['client']['id'],$b['seller'],(int)$u['id'],$b['total'],json_encode($b['payload'],JSON_UNESCAPED_UNICODE),mb_substr($e->getMessage(),0,4000)]);
   throw $e;
  }
 }
}

final class GoalService {
 public static function userMonth(int $userId,string $month): array{
  $u=DB::one("SELECT * FROM users WHERE id=?",[$userId]);if(!$u)return [];
  $g=DB::one("SELECT * FROM goals WHERE user_id=? AND month_ref=?",[$userId,$month])?:['sales_goal'=>0,'collection_goal'=>0,'contact_goal'=>0];
  $start=$month.'-01';$next=date('Y-m-d',strtotime($start.' +1 month'));
  $sales=0.0;$recovered=0.0;$contacts=0;
  if($u['role']==='seller'&&!empty($u['seller_omie_code']))$sales=(float)(DB::scalar("SELECT COALESCE(SUM(total),0) FROM orders WHERE seller_omie_code=? AND order_date>=? AND order_date<? AND status<>'CANCELADO'",[$u['seller_omie_code'],$start,$next])??0);
  if($u['role']==='collector')$recovered=(float)(DB::scalar("SELECT COALESCE(SUM(amount),0) FROM collection_actions WHERE assigned_user_id=? AND result='payment' AND created_at>=? AND created_at<?",[$userId,$start,$next])??0);
  if($u['role']==='seller')$contacts=(int)(DB::scalar("SELECT COUNT(*) FROM activities WHERE user_id=? AND created_at>=? AND created_at<?",[$userId,$start,$next])??0);
  if($u['role']==='collector')$contacts=(int)(DB::scalar("SELECT COUNT(*) FROM collection_actions WHERE assigned_user_id=? AND created_at>=? AND created_at<?",[$userId,$start,$next])??0);
  return ['user'=>$u,'goal'=>$g,'sales'=>$sales,'recovered'=>$recovered,'contacts'=>$contacts,
   'sales_percent'=>(float)$g['sales_goal']>0?min(999,$sales/(float)$g['sales_goal']*100):0,
   'collection_percent'=>(float)$g['collection_goal']>0?min(999,$recovered/(float)$g['collection_goal']*100):0,
   'contact_percent'=>(int)$g['contact_goal']>0?min(999,$contacts/(int)$g['contact_goal']*100):0];
 }
 public static function save(int $userId,string $month,array $i,int $actor): void{
  if(!preg_match('/^\d{4}-\d{2}$/',$month))throw new RuntimeException('Mês inválido.');
  DB::exec("INSERT INTO goals(user_id,month_ref,sales_goal,collection_goal,contact_goal,updated_by,updated_at) VALUES(?,?,?,?,?,?,NOW())
            ON DUPLICATE KEY UPDATE sales_goal=VALUES(sales_goal),collection_goal=VALUES(collection_goal),contact_goal=VALUES(contact_goal),updated_by=VALUES(updated_by),updated_at=NOW()",
   [$userId,$month,max(0,(float)str_replace(',','.',(string)($i['sales_goal']??0))),max(0,(float)str_replace(',','.',(string)($i['collection_goal']??0))),max(0,(int)($i['contact_goal']??0)),$actor]);
 }
}

final class SyncService {
 public static function modules(): array{return ['sellers'=>'Vendedores','clients'=>'Clientes','products'=>'Produtos','categories'=>'Categorias','accounts'=>'Contas correntes','stages'=>'Etapas','payment_terms'=>'Condições','tax_scenarios'=>'Cenários fiscais','stock_locations'=>'Locais de estoque','payment_methods'=>'Meios de pagamento','document_types'=>'Tipos de documento','orders'=>'Pedidos','services'=>'Serviços','financial'=>'Financeiro'];}
 private static function pick(array $d,array $keys): array{foreach($keys as $k)if(isset($d[$k])&&is_array($d[$k]))return $d[$k];return [];}
 private static function finish(string $m,array $d,int $page,int $count): array{$total=(int)($d['total_de_paginas']??$d['nTotPaginas']??1);$total=max(1,$total);DB::exec("INSERT INTO sync_state(module_key,last_page,total_pages,last_count,context_json,last_success_at,last_error) VALUES(?,?,?,?,NULL,NOW(),NULL) ON DUPLICATE KEY UPDATE last_page=VALUES(last_page),total_pages=VALUES(total_pages),last_count=VALUES(last_count),context_json=NULL,last_success_at=NOW(),last_error=NULL",[$m,$page,$total,$count]);if($m==='orders'&&$page>=$total)self::rebuildMetrics();return ['module'=>$m,'page'=>$page,'total_pages'=>$total,'count'=>$count,'done'=>$page>=$total];}
 public static function run(string $m,int $page=1): array{
  if(!isset(self::modules()[$m]))throw new RuntimeException('Módulo inválido.');$o=new OmieClient();$page=max(1,$page);
  if($m==='sellers'){$d=$o->call('sellers','ListarVendedores',['pagina'=>$page,'registros_por_pagina'=>100,'apenas_importado_api'=>'N']);$it=self::pick($d,['cadastro','vendedores']);foreach($it as $r){$c=(string)($r['codigo']??'');if($c==='')continue;DB::exec("INSERT INTO sellers(omie_code,name,email,active,raw_json,updated_at) VALUES(?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),email=VALUES(email),active=VALUES(active),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['nome']??$c),$r['email']??null,(($r['inativo']??'N')==='S'?0:1),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='clients'){$d=$o->call('clients','ListarClientes',['pagina'=>$page,'registros_por_pagina'=>100,'apenas_importado_api'=>'N']);$it=self::pick($d,['clientes_cadastro']);foreach($it as $r){$c=(string)($r['codigo_cliente_omie']??'');if($c==='')continue;$phone=trim((string)($r['telefone1_ddd']??'').' '.(string)($r['telefone1_numero']??''));DB::exec("INSERT INTO clients(omie_code,name,legal_name,document,email,phone,city,uf,seller_omie_code,active,raw_json,updated_at) VALUES(?,?,?,?,?,?,?,?,?,1,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),legal_name=VALUES(legal_name),document=VALUES(document),email=VALUES(email),phone=VALUES(phone),city=VALUES(city),uf=VALUES(uf),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['nome_fantasia']??$r['razao_social']??$c),$r['razao_social']??null,$r['cnpj_cpf']??null,$r['email']??null,$phone,$r['cidade']??null,$r['estado']??null,$r['codigo_vendedor']??null,json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='products'){$d=$o->call('products','ListarProdutos',['pagina'=>$page,'registros_por_pagina'=>100,'apenas_importado_api'=>'N','filtrar_apenas_omiepdv'=>'N']);$it=self::pick($d,['produto_servico_cadastro']);foreach($it as $r){$c=(string)($r['codigo_produto']??'');if($c==='')continue;DB::exec("INSERT INTO products(omie_code,sku,description,unit,ncm,unit_price,stock_qty,active,raw_json,updated_at) VALUES(?,?,?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE sku=VALUES(sku),description=VALUES(description),unit=VALUES(unit),ncm=VALUES(ncm),unit_price=VALUES(unit_price),stock_qty=VALUES(stock_qty),active=VALUES(active),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,$r['codigo']??null,(string)($r['descricao']??$c),$r['unidade']??null,$r['ncm']??null,(float)($r['valor_unitario']??0),isset($r['quantidade_estoque'])?(float)$r['quantidade_estoque']:null,(($r['inativo']??'N')==='S'?0:1),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='categories'){$d=$o->call('categories','ListarCategorias',['pagina'=>$page,'registros_por_pagina'=>100]);$it=self::pick($d,['categoria_cadastro']);foreach($it as $r){$c=(string)($r['codigo']??'');if($c==='')continue;DB::exec("INSERT INTO categories(code,description,active,raw_json,updated_at) VALUES(?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE description=VALUES(description),active=VALUES(active),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['descricao']??$c),(($r['conta_inativa']??'N')==='S'?0:1),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='accounts'){$d=$o->call('accounts','ListarContasCorrentes',['pagina'=>$page,'registros_por_pagina'=>100,'apenas_importado_api'=>'N']);$it=self::pick($d,['ListarContasCorrentes','conta_corrente_lista']);foreach($it as $r){$c=(string)($r['nCodCC']??'');if($c==='')continue;DB::exec("INSERT INTO financial_accounts(omie_code,name,account_type,active,selected,raw_json,updated_at) VALUES(?,?,?,?,0,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),account_type=VALUES(account_type),active=VALUES(active),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['descricao']??$c),$r['tipo_conta_corrente']??null,(($r['inativo']??'N')==='S'?0:1),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='stages'){$d=$o->call('stages','ListarEtapasFaturamento',['pagina'=>$page,'registros_por_pagina'=>100]);$it=self::pick($d,['etapasFaturamento','cadastros','etapas']);foreach($it as $r){$c=(string)($r['codigo']??$r['cCodigo']??'');if($c==='')continue;DB::exec("INSERT INTO order_stages(code,name,active,raw_json,updated_at) VALUES(?,?,1,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),active=1,raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['descricao']??$r['cDescricao']??$c),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='payment_terms'){$d=$o->call('payment_terms','ListarFormasPagVendas',['pagina'=>$page,'registros_por_pagina'=>100]);$it=self::pick($d,['cadastros']);foreach($it as $r){$c=(string)($r['nCodigo']??$r['cCodigo']??'');if($c==='')continue;DB::exec("INSERT INTO payment_terms(code,description,installments,days_list,active,raw_json,updated_at) VALUES(?,?,?,?,1,?,NOW()) ON DUPLICATE KEY UPDATE description=VALUES(description),installments=VALUES(installments),days_list=VALUES(days_list),active=1,raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['cDescricao']??$c),(int)($r['nQtdeParc']??0),(string)($r['cListaParc']??''),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='tax_scenarios'){$d=$o->call('tax_scenarios','ListarCenarios',['nPagina'=>$page,'nRegPorPagina'=>100,'cNome'=>'']);$it=self::pick($d,['cenariosEncontrados']);foreach($it as $r){$c=(string)($r['nCodigo']??'');if($c==='')continue;DB::exec("INSERT INTO tax_scenarios(omie_code,name,is_default,active,raw_json,updated_at) VALUES(?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),is_default=VALUES(is_default),active=VALUES(active),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['cNome']??$c),!empty($r['padrao'])?1:0,(($r['inativo']??'N')==='S'?0:1),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='stock_locations'){$d=$o->call('stock_locations','ListarLocaisEstoque',['nPagina'=>$page,'nRegPorPagina'=>100]);$it=self::pick($d,['locaisEncontrados']);foreach($it as $r){$c=(string)($r['codigo_local_estoque']??'');if($c==='')continue;DB::exec("INSERT INTO stock_locations(omie_code,name,sale_enabled,is_default,active,raw_json,updated_at) VALUES(?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),sale_enabled=VALUES(sale_enabled),is_default=VALUES(is_default),active=VALUES(active),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['descricao']??$c),(($r['dispVenda']??'N')==='S'?1:0),(($r['padrao']??'N')==='S'?1:0),(($r['inativo']??'N')==='S'?0:1),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='payment_methods'){$d=$o->call('payment_methods','ListarMeiosPagamento',['codigo'=>'']);$it=self::pick($d,['MeiosPagamentoLista']);foreach($it as $r){$c=(string)($r['codigo']??'');if($c==='')continue;DB::exec("INSERT INTO payment_methods(code,description,raw_json,updated_at) VALUES(?,?,?,NOW()) ON DUPLICATE KEY UPDATE description=VALUES(description),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['descricao']??$c),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,['total_de_paginas'=>1],1,count($it));}
  if($m==='document_types'){$d=$o->call('document_types','PesquisarTipoDocumento',['codigo'=>'']);$it=self::pick($d,['tipo_documento_cadastro']);foreach($it as $r){$c=(string)($r['codigo']??'');if($c==='')continue;DB::exec("INSERT INTO document_types(code,description,raw_json,updated_at) VALUES(?,?,?,NOW()) ON DUPLICATE KEY UPDATE description=VALUES(description),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['descricao']??$c),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,['total_de_paginas'=>1],1,count($it));}
  if($m==='orders'){$d=$o->call('orders','ListarPedidos',['pagina'=>$page,'registros_por_pagina'=>100,'apenas_importado_api'=>'N']);$it=self::pick($d,['pedido_venda_produto']);foreach($it as $r){$cab=$r['cabecalho']??[];$info=$r['infoCadastro']??[];$add=$r['informacoes_adicionais']??[];$tot=$r['total_pedido']??[];$c=(string)($cab['codigo_pedido']??'');if($c==='')continue;$status=(($info['cancelado']??'N')==='S')?'CANCELADO':((($info['faturado']??'N')==='S')?'FATURADO':'ATIVO');DB::exec("INSERT INTO orders(omie_code,number,client_omie_code,seller_omie_code,order_date,forecast_date,total,status,stage_code,raw_json,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE number=VALUES(number),client_omie_code=VALUES(client_omie_code),seller_omie_code=VALUES(seller_omie_code),order_date=VALUES(order_date),forecast_date=VALUES(forecast_date),total=VALUES(total),status=VALUES(status),stage_code=VALUES(stage_code),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,$cab['numero_pedido']??null,(string)($cab['codigo_cliente']??''),(string)($add['codVend']??''),!empty($info['dInc'])?date('Y-m-d',strtotime(str_replace('/','-',$info['dInc']))):null,!empty($cab['data_previsao'])?date('Y-m-d',strtotime(str_replace('/','-',$cab['data_previsao']))):null,(float)($tot['valor_total_pedido']??0),$status,(string)($cab['etapa']??''),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='services'){$d=$o->call('services','ListarOS',['pagina'=>$page,'registros_por_pagina'=>100,'filtrar_apenas_inclusao'=>'N']);$it=self::pick($d,['osCadastro','ordens_servico']);foreach($it as $r){$cab=$r['Cabecalho']??$r['cabecalho']??$r;$c=(string)($cab['nCodOS']??$cab['codigo_os']??'');if($c==='')continue;DB::exec("INSERT INTO service_orders(omie_code,client_omie_code,seller_omie_code,service_date,total,status,raw_json,updated_at) VALUES(?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE client_omie_code=VALUES(client_omie_code),seller_omie_code=VALUES(seller_omie_code),service_date=VALUES(service_date),total=VALUES(total),status=VALUES(status),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($cab['nCodCli']??''),(string)($cab['nCodVend']??''),!empty($cab['dDtPrevisao'])?date('Y-m-d',strtotime(str_replace('/','-',$cab['dDtPrevisao']))):null,(float)($cab['nValorTotal']??0),(string)($cab['cStatus']??'ATIVO'),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='financial'){return self::financial($o,$page);}
  throw new RuntimeException('Módulo não implementado.');
 }
 private static function financial(OmieClient $o,int $page): array{
  $state=DB::one("SELECT * FROM sync_state WHERE module_key='financial'");
  $ctx=$state&&!empty($state['context_json'])?json_decode((string)$state['context_json'],true):null;
  if($page===1||!is_array($ctx)||empty($ctx['token'])){
   $accounts=DB::all("SELECT omie_code,name FROM financial_accounts WHERE selected=1 AND active=1 ORDER BY omie_code");
   if(!$accounts)throw new RuntimeException('Selecione ao menos uma conta de cobrança.');
   $ctx=['token'=>bin2hex(random_bytes(12)),'accounts'=>$accounts,'account_index'=>0,'status_index'=>0,'api_page'=>1,'processed'=>0];
  }
  $statuses=['ATRASADO','PAGTO_PARCIAL'];
  $account=$ctx['accounts'][$ctx['account_index']]??null;
  if(!$account)throw new RuntimeException('Contexto financeiro inválido.');
  $status=$statuses[(int)$ctx['status_index']]??'ATRASADO';
  $apiPage=max(1,(int)$ctx['api_page']);
  $d=$o->call('financial','ListarMovimentos',['nPagina'=>$apiPage,'nRegPorPagina'=>100,'cNatureza'=>'R','cTpLancamento'=>'CR','nCodCC'=>(int)$account['omie_code'],'cStatus'=>$status,'lDadosCad'=>true]);
  $it=self::pick($d,['movimentos']);
  foreach($it as $r){
   $det=$r['detalhes']??[];$sum=$r['resumo']??[];$code=(string)($det['nCodTitulo']??$det['nCodMovCC']??'');if($code==='')continue;
   $open=(float)($sum['nValAberto']??$det['nValorTitulo']??0);$paid=(float)($sum['nValPago']??0);if($open<=0)continue;
   $local=$paid>0?'PAGTO_PARCIAL':$status;
   DB::exec("INSERT INTO financial_movements(omie_code,client_omie_code,account_omie_code,seller_omie_code,due_date,open_amount,paid_amount,status,last_seen_token,raw_json,updated_at)
             VALUES(?,?,?,?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE client_omie_code=VALUES(client_omie_code),account_omie_code=VALUES(account_omie_code),seller_omie_code=VALUES(seller_omie_code),due_date=VALUES(due_date),open_amount=VALUES(open_amount),paid_amount=VALUES(paid_amount),status=VALUES(status),last_seen_token=VALUES(last_seen_token),raw_json=VALUES(raw_json),updated_at=NOW()",
    [$code,(string)($det['nCodCliente']??$det['nCodCli']??''),(string)$account['omie_code'],(string)($det['cCodVendedor']??''),!empty($det['dDtVenc'])?date('Y-m-d',strtotime(str_replace('/','-',$det['dDtVenc']))):null,$open,$paid,$local,$ctx['token'],json_encode($r,JSON_UNESCAPED_UNICODE)]);
  }
  $ctx['processed']+=(int)count($it);
  $apiTotal=max(1,(int)($d['nTotPaginas']??$d['total_de_paginas']??1));
  if($apiPage<$apiTotal)$ctx['api_page']=$apiPage+1;
  else{
   $ctx['api_page']=1;
   if((int)$ctx['status_index']===0)$ctx['status_index']=1;
   else{$ctx['status_index']=0;$ctx['account_index']++;}
  }
  $done=(int)$ctx['account_index']>=count($ctx['accounts']);
  if($done){
   $selected=array_map(fn($a)=>(string)$a['omie_code'],$ctx['accounts']);
   $placeholders=implode(',',array_fill(0,count($selected),'?'));
   DB::exec("DELETE FROM financial_movements WHERE account_omie_code IN ($placeholders) AND (last_seen_token IS NULL OR last_seen_token<>?)",array_merge($selected,[$ctx['token']]));
   self::rebuildCollection();
   DB::exec("INSERT INTO sync_state(module_key,last_page,total_pages,last_count,context_json,last_success_at,last_error) VALUES('financial',?,?,?,NULL,NOW(),NULL)
             ON DUPLICATE KEY UPDATE last_page=VALUES(last_page),total_pages=VALUES(total_pages),last_count=VALUES(last_count),context_json=NULL,last_success_at=NOW(),last_error=NULL",
      [$page,$page,(int)$ctx['processed']]);
   return ['module'=>'financial','page'=>$page,'total_pages'=>$page,'count'=>count($it),'processed'=>$ctx['processed'],'done'=>true,'account'=>$account['name'],'status'=>$status];
  }
  DB::exec("INSERT INTO sync_state(module_key,last_page,total_pages,last_count,context_json,last_success_at,last_error) VALUES('financial',?,?,?, ?,NULL,NULL)
            ON DUPLICATE KEY UPDATE last_page=VALUES(last_page),last_count=VALUES(last_count),context_json=VALUES(context_json),last_error=NULL",
    [$page,0,(int)$ctx['processed'],json_encode($ctx,JSON_UNESCAPED_UNICODE)]);
  return ['module'=>'financial','page'=>$page,'total_pages'=>0,'count'=>count($it),'processed'=>$ctx['processed'],'done'=>false,'account'=>$account['name'],'status'=>$status];
 }
 private static function rebuildCollection(): void{
  DB::exec("INSERT INTO collection_cases(client_id,open_amount,partial_paid,max_overdue_days,status,updated_at)
            SELECT c.id,SUM(f.open_amount),SUM(f.paid_amount),MAX(GREATEST(0,DATEDIFF(CURDATE(),f.due_date))),'open',NOW()
            FROM clients c JOIN financial_movements f ON f.client_omie_code=c.omie_code
            JOIN financial_accounts a ON a.omie_code=f.account_omie_code AND a.selected=1 AND a.active=1
            WHERE f.status IN('ATRASADO','PAGTO_PARCIAL') GROUP BY c.id
            ON DUPLICATE KEY UPDATE open_amount=VALUES(open_amount),partial_paid=VALUES(partial_paid),max_overdue_days=VALUES(max_overdue_days),status='open',updated_at=NOW()");
  DB::exec("UPDATE collection_cases cc LEFT JOIN (
             SELECT DISTINCT c.id client_id FROM clients c JOIN financial_movements f ON f.client_omie_code=c.omie_code
             JOIN financial_accounts a ON a.omie_code=f.account_omie_code AND a.selected=1 AND a.active=1
             WHERE f.status IN('ATRASADO','PAGTO_PARCIAL')
           ) x ON x.client_id=cc.client_id
           SET cc.status='settled',cc.open_amount=0,cc.partial_paid=0,cc.max_overdue_days=0,cc.updated_at=NOW()
           WHERE x.client_id IS NULL");
 }
 private static function rebuildMetrics(): void{
  $clients=DB::all("SELECT id,omie_code FROM clients WHERE active=1");
  $today=date('Y-m-d');$yearAgo=date('Y-m-d',strtotime('-12 months'));
  foreach($clients as $client){
   $orders=DB::all("SELECT order_date,total,seller_omie_code FROM orders WHERE client_omie_code=? AND status<>'CANCELADO' AND order_date IS NOT NULL ORDER BY order_date DESC",[$client['omie_code']]);
   $last=$orders[0]['order_date']??null;$revenue=0.0;$count=0;$diffs=[];$dates=[];$seller=$orders[0]['seller_omie_code']??null;
   foreach($orders as $o){if($o['order_date']>=$yearAgo&&$o['order_date']<=$today){$revenue+=(float)$o['total'];$count++;}$dates[]=$o['order_date'];}
   for($i=0;$i<count($dates)-1;$i++){$d=(strtotime($dates[$i])-strtotime($dates[$i+1]))/86400;if($d>0)$diffs[]=$d;}
   $avg=$diffs?array_sum($diffs)/count($diffs):null;$ticket=$count>0?$revenue/$count:0;
   DB::exec("INSERT INTO client_metrics(client_id,last_purchase_at,revenue_12m,orders_12m,avg_ticket_12m,avg_interval_days,updated_at)
             VALUES(?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE last_purchase_at=VALUES(last_purchase_at),revenue_12m=VALUES(revenue_12m),orders_12m=VALUES(orders_12m),avg_ticket_12m=VALUES(avg_ticket_12m),avg_interval_days=VALUES(avg_interval_days),updated_at=NOW()",
      [(int)$client['id'],$last,$revenue,$count,$ticket,$avg]);
   if($seller!==null&&$seller!=='')DB::exec("UPDATE clients SET seller_omie_code=? WHERE id=?",[(string)$seller,(int)$client['id']]);
  }
 }
}
