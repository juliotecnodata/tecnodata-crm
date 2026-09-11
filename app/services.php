<?php
final class OmieClient {
 private array $endpoints=[
  'clients'=>'https://app.omie.com.br/api/v1/geral/clientes/',
  'sellers'=>'https://app.omie.com.br/api/v1/geral/vendedores/',
  'products'=>'https://app.omie.com.br/api/v1/geral/produtos/',
  'categories'=>'https://app.omie.com.br/api/v1/geral/categorias/',
  'departments'=>'https://app.omie.com.br/api/v1/geral/departamentos/',
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
  if(str_starts_with($call,'Listar')){
   $batchSize=max(10,min(50,(int)($cfg['sync_batch_size']??25)));
   if(isset($param['registros_por_pagina'])&&(int)$param['registros_por_pagina']>$batchSize)$param['registros_por_pagina']=$batchSize;
   if(isset($param['nRegPorPagina'])&&(int)$param['nRegPorPagina']>$batchSize)$param['nRegPorPagina']=$batchSize;
  }
  $payload=['call'=>$call,'app_key'=>$cfg['app_key'],'app_secret'=>$cfg['app_secret'],'param'=>[$param]];
  $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: application/json','Accept-Encoding: identity'],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE),CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_TIMEOUT=>(int)($cfg['timeout']??60),CURLOPT_ENCODING=>'identity']);
  $raw=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$err=curl_error($ch);curl_close($ch);
  if($raw===false||$err)throw new RuntimeException('Falha de comunicação Omie: '.$err);
  $data=json_decode($raw,true);if(!is_array($data))throw new RuntimeException('Resposta inválida Omie HTTP '.$http.'.');
  if($http>=400||isset($data['faultstring']))throw new RuntimeException((string)($data['faultstring']??$data['message']??('Erro Omie HTTP '.$http)));
  return $data;
 }
}

final class OrderPolicy {
 public static function budgetStageCodes(): array{
  $codes=array_values(array_unique(array_filter(array_map('strval',(array)($GLOBALS['config']['omie']['order_budget_stage_codes']??['00','10'])),fn($code)=>$code!=='')));
  return $codes?:['00','10'];
 }
 public static function outsideBudgetSql(string $column='stage_code'): array{
  if(!preg_match('/^[a-zA-Z0-9_.]+$/',$column))throw new InvalidArgumentException('Coluna de etapa inválida.');
  $codes=self::budgetStageCodes();
  return ['('.$column.' IS NULL OR '.$column.' NOT IN ('.implode(',',array_fill(0,count($codes),'?')).'))',$codes];
 }
 public static function metricTotalSql(string $tableAlias=''): string{
  if($tableAlias!==''&&!preg_match('/^[a-zA-Z0-9_]+$/',$tableAlias))throw new InvalidArgumentException('Alias de pedidos inválido.');
  $p=$tableAlias!==''?$tableAlias.'.':'';
  // orders.total recebe valor_total_pedido da Omie e já inclui frete.
  return "COALESCE({$p}total,0)";
 }
 public static function freightValueSql(string $tableAlias=''): string{
  if($tableAlias!==''&&!preg_match('/^[a-zA-Z0-9_]+$/',$tableAlias))throw new InvalidArgumentException('Alias de pedidos inválido.');
  $p=$tableAlias!==''?$tableAlias.'.':'';
  return "COALESCE(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT({$p}raw_json,'$.frete.valor_frete')),'') AS DECIMAL(18,2)),0)";
 }
 public static function metricWithoutFreightSql(string $tableAlias=''): string{
  $total=self::metricTotalSql($tableAlias);
  $freight=self::freightValueSql($tableAlias);
  return "GREATEST((".$total.")-(".$freight."),0)";
 }
 public static function validReportSql(string $stageColumn='stage_code',string $statusColumn='status',string $rawJsonColumn='raw_json'): array{
  foreach([$stageColumn,$statusColumn,$rawJsonColumn] as $column)if(!preg_match('/^[a-zA-Z0-9_.]+$/',$column))throw new InvalidArgumentException('Coluna de pedidos inválida.');
  [$stageSql,$params]=self::outsideBudgetSql($stageColumn);
  $ignored=array_values(array_unique(array_map(static fn($value)=>mb_strtoupper(trim((string)$value)),(array)($GLOBALS['config']['omie']['ignored_order_statuses']??['CANCELADO','CANCELADA','DEVOLVIDO','DEVOLVIDA','DENEGADO']))));
  $ignored=array_values(array_filter($ignored,static fn($value)=>$value!==''));

  $parts=[$stageSql];

  if($ignored){
   $parts[]='UPPER(TRIM(COALESCE('.$statusColumn.",''))) NOT IN (".implode(',',array_fill(0,count($ignored),'?')).')';
   $params=array_merge($params,$ignored);
  }

  // Métrica comercial: somente Pedido OK.
  // Proposta/Orçamento e pedidos originados do Omie PDV não entram no realizado.
  $rawText="UPPER(COALESCE(CAST(".$rawJsonColumn." AS CHAR),''))";
  $parts[]=$rawText." NOT LIKE '%PROPOSTA%'";
  $parts[]=$rawText." NOT LIKE '%ORCAMENTO%'";
  $parts[]=$rawText." NOT LIKE '%ORÇAMENTO%'";
  $parts[]=$rawText." NOT LIKE '%OMIEPDV%'";
  $parts[]=$rawText." NOT LIKE '%OMIE PDV%'";
  $parts[]=$rawText." NOT LIKE '%\"PDV\"%'";
  $parts[]=$rawText." NOT LIKE '%\"PDV\":\"S\"%'";

  return ['('.implode(' AND ',$parts).')',$params];
 }
}

final class CRMService {
 public static function cycle(?string $last,float $avg=0): array{
  // Regra comercial herdada da planilha:
  // sem última compra = Prospectar
  // até 180 dias = Regular
  // 181 a 365 dias = Inativo - 12 Meses
  // 366 dias ou mais = Inativo + 12 Meses
  if(!$last||trim($last)==='')return ['status'=>'prospect','label'=>'Prospectar','date'=>null,'delta'=>null,'days'=>null];

  $lastDate=date('Y-m-d',strtotime($last));
  $today=date('Y-m-d');
  $days=max(0,(int)floor((strtotime($today)-strtotime($lastDate))/86400));

  if($days<=180)return ['status'=>'regular','label'=>'Regular','date'=>$lastDate,'delta'=>null,'days'=>$days];
  if($days<366)return ['status'=>'inactive12','label'=>'Inativo - 12 Meses','date'=>$lastDate,'delta'=>null,'days'=>$days];
  return ['status'=>'inactive12plus','label'=>'Inativo + 12 Meses','date'=>$lastDate,'delta'=>null,'days'=>$days];
 }
 public static function dashboard(array $u): array{
  $start=date('Y-m-01');$next=date('Y-m-d',strtotime($start.' +1 month'));
  if($u['role']==='seller'){
   [$validOrders,$validOrderParams]=OrderPolicy::validReportSql();
   $orderTotalSql=OrderPolicy::metricTotalSql();
   $orderWithoutFreightSql=OrderPolicy::metricWithoutFreightSql();
   $orders=(float)(DB::scalar("SELECT COALESCE(SUM(".$orderTotalSql."),0) FROM orders WHERE seller_omie_code=? AND order_date>=? AND order_date<? AND ".$validOrders,array_merge([$u['seller_omie_code'],$start,$next],$validOrderParams))??0);
   $orders_without_freight=(float)(DB::scalar("SELECT COALESCE(SUM(".$orderWithoutFreightSql."),0) FROM orders WHERE seller_omie_code=? AND order_date>=? AND order_date<? AND ".$validOrders,array_merge([$u['seller_omie_code'],$start,$next],$validOrderParams))??0);
   $services=(float)(DB::scalar("SELECT COALESCE(SUM(total),0) FROM service_orders WHERE seller_omie_code=? AND service_date>=? AND service_date<? AND UPPER(COALESCE(status,'')) NOT LIKE '%CANCEL%'",[$u['seller_omie_code'],$start,$next])??0);
   $sales=$orders;
   $clients=(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE seller_omie_code=? AND active=1",[$u['seller_omie_code']])??0);
   $tasks=(int)(DB::scalar("SELECT COUNT(*) FROM tasks WHERE assigned_user_id=? AND status='pending'",[(int)$u['id']])??0);
   return compact('sales','orders','orders_without_freight','services','clients','tasks');
  }
  if($u['role']==='collector'){
   $debt=(float)(DB::scalar("SELECT COALESCE(SUM(open_amount),0) FROM collection_cases WHERE status='open'")??0);
   $recovered=(float)(DB::scalar("SELECT COALESCE(SUM(amount),0) FROM collection_actions WHERE assigned_user_id=? AND result='payment' AND created_at>=? AND created_at<?",[(int)$u['id'],$start,$next])??0);
   $worked=(int)(DB::scalar("SELECT COUNT(DISTINCT client_id) FROM collection_actions WHERE assigned_user_id=? AND created_at>=? AND created_at<?",[(int)$u['id'],$start,$next])??0);return compact('debt','recovered','worked');
  }
  $management=GoalService::managementMonth(date('Y-m'));
  $sales=(float)$management['sales'];
  $recovered=(float)$management['recovered'];
  $sales_percent=(float)$management['sales_percent'];
  $collection_percent=(float)$management['collection_percent'];
  $debt=(float)(DB::scalar("SELECT COALESCE(SUM(open_amount),0) FROM collection_cases WHERE status='open'")??0);
  $clients=(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1")??0);
  $late=(int)(DB::scalar("SELECT COUNT(*) FROM tasks WHERE status='pending' AND due_at<NOW()")??0);
  return compact('sales','recovered','sales_percent','collection_percent','debt','clients','late','management');
 }
}


final class NotificationService {
 public static function forUser(array $u): array{
  $items=[];$total=0;$uid=(int)($u['id']??0);$role=(string)($u['role']??'');
  try{
   if(in_array($role,['seller','collector'],true)){
    $late=(int)(DB::scalar("SELECT COUNT(*) FROM tasks WHERE assigned_user_id=? AND status='pending' AND DATE(due_at)<CURDATE()",[$uid])??0);
    if($late>0){$total+=$late;$items[]=['type'=>'danger','icon'=>'fa-clock-rotate-left','title'=>$late.' retorno(s) vencido(s)','text'=>'Existem compromissos atrasados na sua agenda.','href'=>'/agenda?period=late'];}
    $today=(int)(DB::scalar("SELECT COUNT(*) FROM tasks WHERE assigned_user_id=? AND status='pending' AND DATE(due_at)=CURDATE()",[$uid])??0);
    if($today>0){$total+=$today;$items[]=['type'=>'warning','icon'=>'fa-calendar-day','title'=>$today.' retorno(s) para hoje','text'=>'Há compromissos que precisam de atenção hoje.','href'=>'/agenda?period=today'];}
   }else{
    $late=(int)(DB::scalar("SELECT COUNT(*) FROM tasks WHERE status='pending' AND DATE(due_at)<CURDATE()")??0);
    if($late>0){$total+=$late;$items[]=['type'=>'danger','icon'=>'fa-clock-rotate-left','title'=>$late.' retorno(s) vencido(s)','text'=>'A equipe possui compromissos atrasados.','href'=>'/agenda?period=late'];}
   }

   if(in_array($role,['admin','supervisor','collector'],true)){
    $params=[];$where="status='open' AND max_overdue_days>0";
    if($role==='collector'){$where.=" AND assigned_user_id=?";$params[]=$uid;}
    $overdueCases=(int)(DB::scalar("SELECT COUNT(*) FROM collection_cases WHERE ".$where,$params)??0);
    if($overdueCases>0){$total+=$overdueCases;$items[]=['type'=>'orange','icon'=>'fa-hand-holding-dollar','title'=>$overdueCases.' cobrança(s) em atraso','text'=>'Há clientes com saldo vencido aguardando acompanhamento.','href'=>'/collection'];}
   }

   if($role==='admin'){
    $syncErrors=(int)(DB::scalar("SELECT COUNT(*) FROM sync_state WHERE last_error IS NOT NULL AND TRIM(last_error)<>''")??0);
    if($syncErrors>0){$total+=$syncErrors;$items[]=['type'=>'danger','icon'=>'fa-triangle-exclamation','title'=>$syncErrors.' integração(ões) com erro','text'=>'Revise a Central de Sincronização da Omie.','href'=>'/sync'];}
    $unassigned=(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1 AND (seller_omie_code IS NULL OR TRIM(seller_omie_code)='')")??0);
    if($unassigned>0){$total+=$unassigned;$items[]=['type'=>'info','icon'=>'fa-user-plus','title'=>$unassigned.' cliente(s) sem vendedor','text'=>'Existem clientes disponíveis para distribuição de carteira.','href'=>'/clients'];}
   }
  }catch(Throwable $e){
   return ['total'=>0,'items'=>[]];
  }
  return ['total'=>$total,'items'=>array_slice($items,0,6)];
 }
}

final class BrasilApiService {
 private static function get(string $path): array{
  $url='https://brasilapi.com.br/api/'.$path;
  $ch=curl_init($url);
  curl_setopt_array($ch,[
   CURLOPT_RETURNTRANSFER=>true,
   CURLOPT_HTTPHEADER=>['Accept: application/json','User-Agent: TecnodataCRM/1.0'],
   CURLOPT_CONNECTTIMEOUT=>8,
   CURLOPT_TIMEOUT=>15,
   CURLOPT_ENCODING=>'identity',
  ]);
  $raw=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$err=curl_error($ch);curl_close($ch);
  if($raw===false||$err)throw new RuntimeException('Não foi possível consultar o serviço público agora.');
  $data=json_decode($raw,true);
  if($http===404)throw new RuntimeException('Registro não encontrado.');
  if($http>=400||!is_array($data))throw new RuntimeException((string)($data['message']??'Falha na consulta pública.'));
  return $data;
 }

 public static function cnpj(string $value): array{
  $cnpj=preg_replace('/\D+/','',$value);
  if(strlen($cnpj)!==14)throw new RuntimeException('CNPJ deve conter 14 dígitos.');
  $r=self::get('cnpj/v1/'.$cnpj);
  $phone=preg_replace('/\D+/','',(string)($r['ddd_telefone_1']??$r['ddd_telefone_2']??''));
  $street=trim((string)($r['logradouro']??''));
  $type=trim((string)($r['descricao_tipo_de_logradouro']??''));
  if($type!==''&&$street!==''&&!str_starts_with(mb_strtoupper($street),mb_strtoupper($type)))$street=$type.' '.$street;
  return [
   'cnpj'=>$cnpj,
   'legal_name'=>(string)($r['razao_social']??''),
   'trade_name'=>(string)($r['nome_fantasia']??''),
   'email'=>mb_strtolower(trim((string)($r['email']??''))),
   'phone_ddd'=>$phone!==''?substr($phone,0,2):'',
   'phone_number'=>$phone!==''?substr($phone,2):'',
   'zip_code'=>preg_replace('/\D+/','',(string)($r['cep']??'')),
   'address'=>$street,
   'address_number'=>(string)($r['numero']??''),
   'complement'=>(string)($r['complemento']??''),
   'neighborhood'=>(string)($r['bairro']??''),
   'city'=>(string)($r['municipio']??''),
   'uf'=>(string)($r['uf']??''),
   'source'=>'BrasilAPI',
  ];
 }

 public static function cep(string $value): array{
  $cep=preg_replace('/\D+/','',$value);
  if(strlen($cep)!==8)throw new RuntimeException('CEP deve conter 8 dígitos.');
  $r=self::get('cep/v2/'.$cep);
  return [
   'zip_code'=>$cep,
   'address'=>(string)($r['street']??''),
   'neighborhood'=>(string)($r['neighborhood']??''),
   'city'=>(string)($r['city']??''),
   'uf'=>(string)($r['state']??''),
   'source'=>'BrasilAPI',
  ];
 }
}

final class ClientService {
 public static function buildOmiePreview(array $i,array $u): array{
  $legalName=trim((string)($i['legal_name']??''));
  $tradeName=trim((string)($i['trade_name']??''));
  $document=preg_replace('/\D+/','',(string)($i['document']??''));
  $email=mb_strtolower(trim((string)($i['email']??'')));
  $contactName=trim((string)($i['contact_name']??''));
  $phoneDdd=preg_replace('/\D+/','',(string)($i['phone_ddd']??''));
  $phoneNumber=preg_replace('/\D+/','',(string)($i['phone_number']??''));
  $zip=preg_replace('/\D+/','',(string)($i['zip_code']??''));
  $address=trim((string)($i['address']??''));
  $number=trim((string)($i['address_number']??''));
  $complement=trim((string)($i['complement']??''));
  $neighborhood=trim((string)($i['neighborhood']??''));
  $city=trim((string)($i['city']??''));
  $uf=strtoupper(trim((string)($i['uf']??'')));
  $notes=trim((string)($i['notes']??''));
  // Classificação comercial padronizada para todos os cadastros criados/editados pelo CRM.
  $tags=['CLIENTE','CFC'];

  if($legalName==='')throw new RuntimeException('Razão social / Nome é obrigatório.');
  if($tradeName==='')throw new RuntimeException('Nome fantasia é obrigatório.');
  if(!in_array(strlen($document),[11,14],true))throw new RuntimeException('CPF / CNPJ é obrigatório e deve conter 11 ou 14 dígitos.');
  if($email===''||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('E-mail é obrigatório e deve ser válido.');
  if($contactName==='')throw new RuntimeException('Nome do contato é obrigatório.');
  if(strlen($phoneDdd)!==2)throw new RuntimeException('DDD é obrigatório e deve conter 2 dígitos.');
  if(!in_array(strlen($phoneNumber),[8,9],true))throw new RuntimeException('Telefone é obrigatório e deve conter 8 ou 9 dígitos.');
  if(strlen($zip)!==8)throw new RuntimeException('CEP é obrigatório e deve conter 8 dígitos.');
  if($address==='')throw new RuntimeException('Endereço é obrigatório.');
  if($number==='')throw new RuntimeException('Número é obrigatório.');
  if($neighborhood==='')throw new RuntimeException('Bairro é obrigatório.');
  if($city==='')throw new RuntimeException('Cidade é obrigatória.');
  if(!preg_match('/^[A-Z]{2}$/',$uf))throw new RuntimeException('UF é obrigatória e deve conter 2 letras.');

  $seller='';
  if(($u['role']??'')==='seller')$seller=(string)($u['seller_omie_code']??'');
  else $seller=trim((string)($i['seller_omie_code']??''));
  if($seller!==''&&!DB::one("SELECT 1 FROM sellers WHERE omie_code=? AND active=1",[$seller]))throw new RuntimeException('Vendedor inválido.');

  $integration='TDCRM-CLI-'.date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(3)),0,6));
  $payload=[
   'codigo_cliente_integracao'=>$integration,
   'razao_social'=>$legalName,
   'nome_fantasia'=>$tradeName!==''?$tradeName:$legalName,
   'cnpj_cpf'=>$document,
   'email'=>$email,
   'contato'=>$contactName,
   'cep'=>$zip,
   'endereco'=>$address,
   'endereco_numero'=>$number,
   'complemento'=>$complement,
   'bairro'=>$neighborhood,
   'cidade'=>$city,
   'estado'=>$uf,
  ];
  if($tags)$payload['tags']=array_map(fn($tag)=>['tag'=>$tag],$tags);
  if($phoneDdd!==''&&$phoneNumber!==''){
   $payload['telefone1_ddd']=$phoneDdd;
   $payload['telefone1_numero']=$phoneNumber;
  }
  if($seller!=='')$payload['recomendacoes']=['codigo_vendedor'=>(int)$seller];
  if($notes!=='')$payload['observacao']=$notes;

  return [
   'call'=>'IncluirCliente',
   'endpoint'=>'/api/v1/geral/clientes/',
   'payload'=>$payload,
   'summary'=>[
    'name'=>$tradeName!==''?$tradeName:$legalName,
    'document'=>$document,
    'seller'=>$seller!==''?(DB::scalar("SELECT name FROM sellers WHERE omie_code=?",[$seller])?:$seller):'Não definido'
   ]
  ];
 }

 private static function extractOmieClientCode(array $response): string{
  $code=(string)($response['codigo_cliente_omie']??'');
  $code=trim($code);
  if($code===''||!ctype_digit($code))return '';
  return $code;
 }

 private static function findExistingInOmieByDocument(string $document): ?array{
  $document=preg_replace('/\D+/','',$document);
  if(!in_array(strlen($document),[11,14],true))return null;

  $omie=new OmieClient();
  try{
   $data=$omie->call('clients','ListarClientes',[
    'pagina'=>1,
    'registros_por_pagina'=>50,
    'apenas_importado_api'=>'N',
    'clientesFiltro'=>['cnpj_cpf'=>$document],
   ]);
  }catch(Throwable $e){
   $message=$e->getMessage();
   // A Omie pode devolver 5113 quando o filtro não encontra nenhum cliente.
   // Para a regra do CRM isso significa "não existe" e não deve interromper a integração.
   if(str_contains($message,'5113')||str_contains(mb_strtolower($message),'não existem registros para a página')||str_contains(mb_strtolower($message),'nao existem registros para a pagina')){
    return null;
   }
   throw $e;
  }

  if((int)($data['total_de_registros']??0)===0||empty($data['clientes_cadastro']))return null;
  $rows=(array)($data['clientes_cadastro']??[]);
  foreach($rows as $row){
   if(!is_array($row))continue;
   $remoteDocument=preg_replace('/\D+/','',(string)($row['cnpj_cpf']??''));
   if($remoteDocument===$document)return $row;
  }
  return null;
 }

 public static function createLocal(array $i,array $u): array{
  $built=self::buildOmiePreview($i,$u);
  $document=(string)$built['summary']['document'];
  $existing=DB::one("SELECT id,omie_code,name FROM clients WHERE document=? AND active=1 LIMIT 1",[$document]);
  if($existing)throw new RuntimeException('Este CPF/CNPJ já existe no CRM como cliente "'.$existing['name'].'".');

  $p=$built['payload'];
  $localCode='LOCAL-'.date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(3)),0,6));
  $name=(string)($p['nome_fantasia']??$p['razao_social']??$document);
  $phone=trim((string)($p['telefone1_ddd']??'').' '.(string)($p['telefone1_numero']??''));
  $seller=(string)($p['recomendacoes']['codigo_vendedor']??'');
  $raw=['request'=>$p,'source'=>'local_pending','omie_status'=>'pending'];

  DB::exec("INSERT INTO clients(omie_code,name,legal_name,document,email,phone,city,uf,seller_omie_code,active,raw_json,updated_at)
            VALUES(?,?,?,?,?,?,?,?,?,1,?,NOW())",
   [$localCode,$name,(string)($p['razao_social']??''),$document,(string)($p['email']??''),$phone,(string)($p['cidade']??''),(string)($p['estado']??''),$seller!==''?$seller:null,json_encode($raw,JSON_UNESCAPED_UNICODE)]);

  $client=DB::one("SELECT * FROM clients WHERE omie_code=?",[$localCode]);
  return ['client'=>$client,'payload'=>$p];
 }

 public static function syncLocalWithOmie(int $id,array $u): array{
  $client=DB::one("SELECT * FROM clients WHERE id=? AND active=1",[$id]);
  if(!$client)throw new RuntimeException('Cliente não encontrado.');
  if(($u['role']??'')==='seller'&&(string)$client['seller_omie_code']!==(string)($u['seller_omie_code']??''))throw new RuntimeException('Cliente fora da sua carteira.');

  $rawCurrent=json_decode((string)($client['raw_json']??''),true);
  if(!is_array($rawCurrent))$rawCurrent=[];
  $form=self::formFromClient($client);
  $built=self::buildOmiePreview($form,$u);
  $document=(string)$built['summary']['document'];
  $isLocal=str_starts_with((string)$client['omie_code'],'LOCAL-');
  $originalOmieCode=(string)($rawCurrent['previous_omie_code']??($isLocal?'':$client['omie_code']));

  $remoteExisting=self::findExistingInOmieByDocument($document);

  if($isLocal){
   if($remoteExisting){
    $remoteCode=(string)($remoteExisting['codigo_cliente_omie']??'');
    if($remoteCode==='')throw new RuntimeException('A Omie encontrou o CPF/CNPJ, mas não retornou o código do cliente.');
    $conflict=DB::one("SELECT id,name FROM clients WHERE omie_code=? AND id<>? LIMIT 1",[$remoteCode,$id]);
    if($conflict)throw new RuntimeException('Este cliente já está vinculado no CRM a "'.$conflict['name'].'".');

    $remoteName=(string)($remoteExisting['nome_fantasia']??$remoteExisting['razao_social']??'Cliente Omie');
    $raw=['request'=>$built['payload'],'response'=>$remoteExisting,'source'=>'omie_linked_existing','omie_status'=>'linked'];
    DB::exec("UPDATE clients SET omie_code=?,raw_json=?,updated_at=NOW() WHERE id=?",[$remoteCode,json_encode($raw,JSON_UNESCAPED_UNICODE),$id]);
    return ['status'=>'already_exists','message'=>'Este CPF/CNPJ já existe na Omie como "'.$remoteName.'" (código '.$remoteCode.'). Nenhum cadastro duplicado foi criado; o CRM apenas vinculou os registros.'];
   }

   $omie=new OmieClient();
   $response=$omie->call('clients','IncluirCliente',$built['payload']);
   $remoteCode=self::extractOmieClientCode($response);
   if($remoteCode==='')throw new RuntimeException('A Omie confirmou o cadastro, mas não retornou codigo_cliente_omie válido.');

   // Não repete ListarClientes imediatamente após a inclusão: a Omie bloqueia consumo redundante.
   $raw=[
    'request'=>$built['payload'],
    'response'=>$response,
    'response_code'=>$remoteCode,
    'source'=>'omie_created_from_local',
    'omie_status'=>'linked'
   ];
   DB::exec("UPDATE clients SET omie_code=?,raw_json=?,updated_at=NOW() WHERE id=?",[$remoteCode,json_encode($raw,JSON_UNESCAPED_UNICODE),$id]);
   return [
    'status'=>'created',
    'message'=>'Cliente integrado com sucesso. Código Omie '.$remoteCode.'.'
   ];
  }

  if($remoteExisting){
   $remoteCode=(string)($remoteExisting['codigo_cliente_omie']??'');
   if($remoteCode!==''&&$originalOmieCode!==''&&$remoteCode!==$originalOmieCode){
    $remoteName=(string)($remoteExisting['nome_fantasia']??$remoteExisting['razao_social']??'outro cliente');
    throw new RuntimeException('O CPF/CNPJ informado pertence a outro cadastro na Omie: "'.$remoteName.'" (código '.$remoteCode.'). A sincronização foi interrompida para evitar vínculo incorreto.');
   }
  }

  $p=$built['payload'];
  unset($p['codigo_cliente_integracao']);
  $p['codigo_cliente_omie']=(int)$originalOmieCode;

  $omie=new OmieClient();
  $response=$omie->call('clients','AlterarCliente',$p);
  $raw=['request'=>$p,'response'=>$response,'source'=>'omie_updated_after_local_save','omie_status'=>'linked'];
  DB::exec("UPDATE clients SET raw_json=?,updated_at=NOW() WHERE id=?",[json_encode($raw,JSON_UNESCAPED_UNICODE),$id]);
  return ['status'=>'updated','message'=>'Alterações locais sincronizadas com sucesso na Omie. Código Omie '.$originalOmieCode.'.'];
 }

 public static function createInOmie(array $i,array $u): array{
  $built=self::buildOmiePreview($i,$u);
  $document=(string)$built['summary']['document'];
  $existing=$document!==''?DB::one("SELECT id,omie_code,name FROM clients WHERE document=? LIMIT 1",[$document]):null;
  if($existing)throw new RuntimeException('Este CPF/CNPJ já existe no CRM como cliente "'.$existing['name'].'".');

  // Confirma também diretamente na Omie. A base local pode estar desatualizada entre sincronizações.
  $remoteExisting=self::findExistingInOmieByDocument($document);
  if($remoteExisting){
   $remoteName=(string)($remoteExisting['nome_fantasia']??$remoteExisting['razao_social']??'cliente existente');
   $remoteCode=(string)($remoteExisting['codigo_cliente_omie']??'');
   throw new RuntimeException('Este CPF/CNPJ já existe na Omie como "'.$remoteName.'"'.($remoteCode!==''?' (código '.$remoteCode.')':'.').' O cadastro foi cancelado para evitar duplicidade.');
  }

  $omie=new OmieClient();
  $response=$omie->call('clients','IncluirCliente',$built['payload']);
  $omieCode=self::extractOmieClientCode($response);
  if($omieCode==='')throw new RuntimeException('A Omie confirmou a operação, mas não retornou codigo_cliente_omie válido.');

  $p=$built['payload'];
  $name=(string)($p['nome_fantasia']??$p['razao_social']??$document);
  $phone=trim((string)($p['telefone1_ddd']??'').' '.(string)($p['telefone1_numero']??''));
  $seller=(string)($p['codigo_vendedor']??'');
  $raw=['request'=>$p,'response'=>$response,'response_code'=>$omieCode,'source'=>'crm_create'];

  DB::exec("INSERT INTO clients(omie_code,name,legal_name,document,email,phone,city,uf,seller_omie_code,active,raw_json,updated_at)
            VALUES(?,?,?,?,?,?,?,?,?,1,?,NOW())
            ON DUPLICATE KEY UPDATE name=VALUES(name),legal_name=VALUES(legal_name),document=VALUES(document),email=VALUES(email),phone=VALUES(phone),city=VALUES(city),uf=VALUES(uf),seller_omie_code=VALUES(seller_omie_code),active=1,raw_json=VALUES(raw_json),updated_at=NOW()",
   [$omieCode,$name,(string)($p['razao_social']??''),$document,(string)($p['email']??''),$phone,(string)($p['cidade']??''),(string)($p['estado']??''),$seller!==''?$seller:null,json_encode($raw,JSON_UNESCAPED_UNICODE)]);

  $client=DB::one("SELECT * FROM clients WHERE omie_code=?",[$omieCode]);
  return ['client'=>$client,'response'=>$response,'payload'=>$p];
 }
 public static function formFromClient(array $client): array{
  $raw=json_decode((string)($client['raw_json']??''),true);
  if(!is_array($raw))$raw=[];
  $src=is_array($raw['request']??null)?$raw['request']:$raw;
  $tags=[];
  foreach((array)($src['tags']??[]) as $t){
   if(is_array($t)&&isset($t['tag']))$tags[]=(string)$t['tag'];
   elseif(is_string($t))$tags[]=$t;
  }
  return [
   'legal_name'=>(string)($src['razao_social']??$client['legal_name']??$client['name']??''),
   'trade_name'=>(string)($src['nome_fantasia']??$client['name']??''),
   'document'=>(string)($src['cnpj_cpf']??$client['document']??''),
   'email'=>(string)($src['email']??$client['email']??''),
   'contact_name'=>(string)($src['contato']??''),
   'phone_ddd'=>(string)($src['telefone1_ddd']??''),
   'phone_number'=>(string)($src['telefone1_numero']??''),
   'zip_code'=>(string)($src['cep']??''),
   'address'=>(string)($src['endereco']??''),
   'address_number'=>(string)($src['endereco_numero']??''),
   'complement'=>(string)($src['complemento']??''),
   'neighborhood'=>(string)($src['bairro']??''),
   'city'=>(string)($src['cidade']??$client['city']??''),
   'uf'=>(string)($src['estado']??$client['uf']??''),
   'seller_omie_code'=>(string)($client['seller_omie_code']??$src['recomendacoes']['codigo_vendedor']??$src['codigo_vendedor']??''),
   'tags'=>implode(', ',$tags),
   'notes'=>(string)($src['observacao']??''),
  ];
 }

 public static function updateInOmie(int $id,array $i,array $u): array{
  $client=DB::one("SELECT * FROM clients WHERE id=? AND active=1",[$id]);
  if(!$client)throw new RuntimeException('Cliente não encontrado.');
  if(($u['role']??'')==='seller'&&(string)$client['seller_omie_code']!==(string)($u['seller_omie_code']??''))throw new RuntimeException('Cliente fora da sua carteira.');

  $built=self::buildOmiePreview($i,$u);
  $duplicate=DB::one("SELECT id,name FROM clients WHERE document=? AND id<>? AND active=1 LIMIT 1",[(string)$built['summary']['document'],$id]);
  if($duplicate)throw new RuntimeException('Este CPF/CNPJ já pertence ao cliente "'.$duplicate['name'].'".');

  $p=$built['payload'];
  $name=(string)($p['nome_fantasia']??$p['razao_social']??$client['name']);
  $phone=trim((string)($p['telefone1_ddd']??'').' '.(string)($p['telefone1_numero']??''));
  $seller=(string)($p['recomendacoes']['codigo_vendedor']??'');
  $isLocal=str_starts_with((string)$client['omie_code'],'LOCAL-');
  $raw=[
   'request'=>$p,
   'source'=>$isLocal?'local_pending':'local_update_pending',
   'omie_status'=>$isLocal?'pending':'pending_update',
   'previous_omie_code'=>$isLocal?null:(string)$client['omie_code'],
  ];

  DB::exec("UPDATE clients SET name=?,legal_name=?,document=?,email=?,phone=?,city=?,uf=?,seller_omie_code=?,raw_json=?,updated_at=NOW() WHERE id=?",
   [$name,(string)($p['razao_social']??''),(string)($p['cnpj_cpf']??''),(string)($p['email']??''),$phone,(string)($p['cidade']??''),(string)($p['estado']??''),$seller!==''?$seller:null,json_encode($raw,JSON_UNESCAPED_UNICODE),$id]);

  return ['status'=>'local_updated','client'=>DB::one("SELECT * FROM clients WHERE id=?",[$id]),'payload'=>$p];
 }

 public static function deleteLocalOnly(int $id,array $u): array{
  $client=DB::one("SELECT * FROM clients WHERE id=? AND active=1",[$id]);
  if(!$client)throw new RuntimeException('Cliente não encontrado.');
  if(($u['role']??'')==='seller'&&(string)$client['seller_omie_code']!==(string)($u['seller_omie_code']??''))throw new RuntimeException('Cliente fora da sua carteira.');

  DB::exec("UPDATE clients SET active=0,updated_at=NOW() WHERE id=?",[$id]);
  return ['status'=>'local_deleted','client'=>$client];
 }

 public static function deleteFromOmie(int $id,array $u): array{
  $client=DB::one("SELECT * FROM clients WHERE id=? AND active=1",[$id]);
  if(!$client)throw new RuntimeException('Cliente não encontrado.');
  if(($u['role']??'')==='seller'&&(string)$client['seller_omie_code']!==(string)($u['seller_omie_code']??''))throw new RuntimeException('Cliente fora da sua carteira.');

  $localOmieCode=(string)($client['omie_code']??'');
  if(str_starts_with($localOmieCode,'LOCAL-')){
   DB::exec("UPDATE clients SET active=0,updated_at=NOW() WHERE id=?",[$id]);
   return ['status'=>'local_deleted','client'=>$client,'response'=>null];
  }

  $document=preg_replace('/\D+/','',(string)($client['document']??''));
  if(!in_array(strlen($document),[11,14],true))throw new RuntimeException('CPF/CNPJ inválido no cadastro local. A exclusão foi interrompida.');

  // Antes de excluir, confirma o cliente real na Omie pelo documento.
  $remote=self::findExistingInOmieByDocument($document);
  if(!$remote){
   throw new RuntimeException('O CPF/CNPJ deste cliente não foi encontrado na Omie. Nenhuma exclusão foi realizada.');
  }

  $remoteCode=(string)($remote['codigo_cliente_omie']??'');
  if($remoteCode==='')throw new RuntimeException('A Omie localizou o cliente, mas não retornou um código válido. Nenhuma exclusão foi realizada.');

  // Se o código salvo localmente estiver divergente, corrige antes da exclusão.
  if($localOmieCode!==$remoteCode){
   DB::exec("UPDATE clients SET omie_code=?,updated_at=NOW() WHERE id=?",[$remoteCode,$id]);
  }

  $omie=new OmieClient();
  $response=$omie->call('clients','ExcluirCliente',['codigo_cliente_omie'=>(int)$remoteCode]);

  DB::exec("UPDATE clients SET active=0,updated_at=NOW() WHERE id=?",[$id]);
  return [
   'status'=>'synced_deleted',
   'client'=>$client,
   'response'=>$response,
   'remote_code'=>$remoteCode,
   'corrected_code'=>$localOmieCode!==$remoteCode,
  ];
 }

}

final class OrderService {
 private static function normalizeFreightMode(mixed $value,string $fallback='9'): string{
  $allowed=['0','1','2','3','4','9'];
  $mode=trim((string)$value);
  if(in_array($mode,$allowed,true))return $mode;
  $fallback=trim($fallback);
  return in_array($fallback,$allowed,true)?$fallback:'9';
 }
 private static bool $draftTableReady=false;
 private static function ensureDraftTable(): void{
  if(self::$draftTableReady)return;
  DB::conn()->exec(DB::sql("CREATE TABLE IF NOT EXISTS local_order_drafts(
   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   request_token VARCHAR(80) NOT NULL,
   created_by INT UNSIGNED NOT NULL,
   client_id BIGINT UNSIGNED NULL,
   seller_omie_code VARCHAR(80) NULL,
   status ENUM('draft','sent') NOT NULL DEFAULT 'draft',
   total DECIMAL(15,2) NOT NULL DEFAULT 0,
   form_json JSON NOT NULL,
   omie_code VARCHAR(80) NULL,
   omie_number VARCHAR(30) NULL,
   created_at DATETIME NOT NULL,
   updated_at DATETIME NOT NULL,
   sent_at DATETIME NULL,
   UNIQUE KEY uq_local_order_draft_token(request_token),
   INDEX idx_local_order_draft_user_status(created_by,status),
   INDEX idx_local_order_draft_updated(updated_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"));
  self::$draftTableReady=true;
 }

 public static function saveDraft(array $i,array $u): array{
  self::ensureDraftTable();
  $id=(int)($i['draft_id']??0);
  $token=preg_replace('/[^A-Za-z0-9_-]/','',(string)($i['request_token']??''));
  if($token==='')$token=date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,8));

  $items=json_decode((string)($i['items_json']??'[]'),true);
  $total=0.0;
  if(is_array($items)){
   foreach($items as $it){
    if(!is_array($it))continue;
    $q=max(0,(float)($it['quantity']??0));
    $price=max(0,(float)($it['unit_price']??0));
    $discountType=(string)($it['discount_type']??'V');
    $discountValue=max(0,(float)($it['discount_value']??$it['discount']??0));
    $line=$q*$price;
    $discount=$discountType==='P'?($line*min(100,$discountValue)/100):$discountValue;
    $total+=max(0,$line-$discount);
   }
  }

  $clientId=(int)($i['client_id']??0);
  if($clientId<=0)$clientId=null;
  $seller=($u['role']??'')==='seller'
   ? trim((string)($u['seller_omie_code']??''))
   : trim((string)($i['seller_omie_code']??''));
  $seller=$seller!==''?$seller:null;

  $safe=$i;
  unset($safe['_token'],$safe['submit_mode']);
  $safe['request_token']=$token;

  if($id>0){
   $draft=DB::one("SELECT * FROM local_order_drafts WHERE id=?",[$id]);
   if(!$draft)throw new RuntimeException('Rascunho não encontrado.');
   if(($u['role']??'')==='seller'&&(int)$draft['created_by']!==(int)$u['id'])throw new RuntimeException('Sem permissão para editar este rascunho.');
   if((string)$draft['status']!=='draft')throw new RuntimeException('Este rascunho já foi enviado.');
   DB::exec("UPDATE local_order_drafts SET request_token=?,client_id=?,seller_omie_code=?,total=?,form_json=?,updated_at=NOW() WHERE id=?",
    [$token,$clientId,$seller,$total,json_encode($safe,JSON_UNESCAPED_UNICODE),$id]);
  }else{
   DB::exec("INSERT INTO local_order_drafts(request_token,created_by,client_id,seller_omie_code,status,total,form_json,created_at,updated_at)
             VALUES(?,?,?,?, 'draft',?,?,NOW(),NOW())
             ON DUPLICATE KEY UPDATE client_id=VALUES(client_id),seller_omie_code=VALUES(seller_omie_code),total=VALUES(total),form_json=VALUES(form_json),updated_at=NOW()",
    [$token,(int)$u['id'],$clientId,$seller,$total,json_encode($safe,JSON_UNESCAPED_UNICODE)]);
   $id=(int)(DB::scalar("SELECT id FROM local_order_drafts WHERE request_token=?",[$token])??0);
  }

  return DB::one("SELECT * FROM local_order_drafts WHERE id=?",[$id])??[];
 }

 public static function draft(int $id,array $u): array{
  self::ensureDraftTable();
  self::reconcileSentDrafts();
  $draft=DB::one("SELECT d.*,c.name client_name FROM local_order_drafts d LEFT JOIN clients c ON c.id=d.client_id WHERE d.id=?",[$id]);
  if(!$draft)throw new RuntimeException('Rascunho não encontrado.');
  if(($u['role']??'')==='seller'&&(int)$draft['created_by']!==(int)$u['id'])throw new RuntimeException('Sem permissão para acessar este rascunho.');
  if((string)$draft['status']!=='draft')throw new RuntimeException('Este pedido já foi integrado à Omie e não é mais um rascunho.');
  $form=json_decode((string)$draft['form_json'],true);
  $draft['form']=is_array($form)?$form:[];
  return $draft;
 }

 public static function drafts(array $u): array{
  self::ensureDraftTable();
  self::reconcileSentDrafts();
  $where="d.status='draft'";$p=[];
  if(($u['role']??'')==='seller'){$where.=" AND d.created_by=?";$p[]=(int)$u['id'];}
  return DB::all("SELECT d.*,c.name client_name,s.name seller_name,u.name author_name
                  FROM local_order_drafts d
                  LEFT JOIN clients c ON c.id=d.client_id
                  LEFT JOIN sellers s ON s.omie_code=d.seller_omie_code
                  LEFT JOIN users u ON u.id=d.created_by
                  WHERE ".$where."
                  ORDER BY d.updated_at DESC LIMIT 200",$p);
 }

 public static function deleteDraft(int $id,array $u): void{
  self::ensureDraftTable();
  $draft=DB::one("SELECT * FROM local_order_drafts WHERE id=?",[$id]);
  if(!$draft)return;
  if(($u['role']??'')==='seller'&&(int)$draft['created_by']!==(int)$u['id'])throw new RuntimeException('Sem permissão para excluir este rascunho.');
  if((string)$draft['status']!=='draft')throw new RuntimeException('Pedido já enviado não pode ser excluído como rascunho.');
  DB::exec("DELETE FROM local_order_drafts WHERE id=?",[$id]);
 }

 public static function orderDetail(int $id,array $u): array{
  $order=DB::one(
   "SELECT o.*,c.id client_id,c.name client_name,c.document client_document,c.city client_city,c.uf client_uf,
           s.name seller_name,os.name stage_name,os.active stage_active
    FROM orders o
    LEFT JOIN clients c ON c.omie_code=o.client_omie_code
    LEFT JOIN sellers s ON s.omie_code=o.seller_omie_code
    LEFT JOIN order_stages os ON os.code=o.stage_code
    WHERE o.id=?",[$id]
  );
  if(!$order)throw new RuntimeException('Pedido não encontrado.');
  if(($u['role']??'')==='seller'&&(string)$order['seller_omie_code']!==(string)($u['seller_omie_code']??''))throw new RuntimeException('Sem permissão para acessar este pedido.');
  $raw=json_decode((string)($order['raw_json']??''),true);
  if(!is_array($raw))$raw=[];
  $items=[];
  foreach((array)($raw['det']??[]) as $row){
   if(!is_array($row))continue;
   $product=(array)($row['produto']??[]);$extra=(array)($row['inf_adic']??[]);
   $items[]=['product'=>$product,'extra'=>$extra,'ide'=>(array)($row['ide']??[])];
  }
  return ['order'=>$order,'raw'=>$raw,'items'=>$items];
 }

 public static function duplicateOrderForm(int $id,array $u): array{
  $detail=self::orderDetail($id,$u);$order=$detail['order'];$raw=$detail['raw'];
  $header=(array)($raw['cabecalho']??[]);$info=(array)($raw['informacoes_adicionais']??[]);$freight=(array)($raw['frete']??[]);
  $defaults=self::defaults();$items=[];$missing=[];
  foreach($detail['items'] as $row){
   $product=(array)$row['product'];$extra=(array)$row['extra'];$omieCode=(string)($product['codigo_produto']??'');
   $local=$omieCode!==''?DB::one("SELECT id,description,sku,unit,ncm,active,raw_json FROM products WHERE omie_code=?",[$omieCode]):null;
   if(!$local||!(int)$local['active']){$missing[]=(string)($product['descricao']??$omieCode?:'Item sem identificação');continue;}
   $localRaw=json_decode((string)($local['raw_json']??''),true);if(!is_array($localRaw))$localRaw=[];$itemQuantity=max(0.0001,(float)($product['quantidade']??1));
   $items[]=[
    'product_id'=>(int)$local['id'],'description'=>(string)($product['descricao']??$local['description']),
    'sku'=>(string)($product['codigo']??$local['sku']??$omieCode),'unit'=>(string)($product['unidade']??$local['unit']??'UN'),
    'quantity'=>(float)($product['quantidade']??1),'unit_price'=>(float)($product['valor_unitario']??0),
    'discount_type'=>(string)($product['tipo_desconto']??'V'),
    'discount_value'=>(float)(((string)($product['tipo_desconto']??'V'))==='P'?($product['percentual_desconto']??0):($product['valor_desconto']??0)),
    'no_stock'=>(string)($extra['nao_movimentar_estoque']??'N')==='S','no_finance'=>(string)($extra['nao_gerar_financeiro']??'N')==='S',
    'no_total'=>(string)($extra['nao_somar_total']??'N')==='S','reserve_stock'=>(string)($product['reservado']??'N')==='S',
    'category_code'=>(string)($extra['codigo_categoria_item']??''),'tax_scenario_code'=>(string)($extra['codigo_cenario_impostos_item']??''),
    'stock_location_code'=>(string)($extra['codigo_local_estoque']??''),'purchase_order_number'=>(string)($extra['numero_pedido_compra']??''),
    'purchase_order_item'=>(int)($extra['item_pedido_compra']??0),'fiscal_notes'=>(string)($extra['dados_adicionais_item']??''),
    'cfop'=>(string)($product['cfop']??''),'ncm'=>(string)($product['ncm']??$local['ncm']??''),
    'unit_net_weight'=>(float)($localRaw['peso_liq']??((float)($extra['peso_liquido']??0)/$itemQuantity)),
    'unit_gross_weight'=>(float)($localRaw['peso_bruto']??((float)($extra['peso_bruto']??0)/$itemQuantity))
   ];
  }
  $departments=[];
  foreach((array)($raw['departamentos']??[]) as $row)if(is_array($row)&&!empty($row['cCodDepto']))$departments[]=['code'=>(string)$row['cCodDepto'],'percent'=>(float)($row['nPerc']??0)];
  $form=[
   'client_id'=>(int)($order['client_id']??0),'seller_omie_code'=>(string)($order['seller_omie_code']??''),
   'forecast_date'=>date('Y-m-d'),'payment_term'=>(string)(($header['codigo_parcela']??'')==='999'?($defaults['payment_term']??''):($header['codigo_parcela']??$defaults['payment_term']??'')),
   'tax_scenario'=>(string)($header['codigo_cenario_impostos']??$defaults['tax_scenario']??''),'stage'=>(string)($defaults['stage']??''),
   'category'=>(string)($info['codigo_categoria']??$defaults['category']??''),'account'=>(string)($info['codigo_conta_corrente']??$defaults['account']??''),
   'payment_method'=>(string)($info['meio_pagamento']??$defaults['payment_method']??''),
   'consumer_final'=>(string)($info['consumidor_final']??$defaults['consumer_final']??'S'),'send_email'=>(string)($info['enviar_email']??'N'),
   'freight_mode'=>(string)($freight['modalidade']??$defaults['freight_mode']??'9'),'carrier_code'=>(string)($freight['codigo_transportadora']??''),
   'plate'=>(string)($freight['placa']??''),'plate_state'=>(string)($freight['placa_estado']??''),'rntrc'=>(string)($freight['registro_transportador']??''),
   'volumes'=>(string)($freight['quantidade_volumes']??''),'volume_type'=>(string)($freight['especie_volumes']??''),'volume_brand'=>(string)($freight['marca_volumes']??''),'volume_numbering'=>(string)($freight['numeracao_volumes']??''),
   'net_weight'=>(string)($freight['peso_liquido']??''),'gross_weight'=>(string)($freight['peso_bruto']??''),'freight_value'=>(string)($freight['valor_frete']??''),'insurance_value'=>(string)($freight['valor_seguro']??''),'other_expenses'=>(string)($freight['outras_despesas']??''),
   'delivery_date'=>!empty($freight['previsao_entrega'])?date('Y-m-d',strtotime(str_replace('/','-',(string)$freight['previsao_entrega']))):'','tracking_code'=>(string)($freight['codigo_rastreio']??''),'own_vehicle'=>(string)($freight['veiculo_proprio']??'N')==='S'?'1':'',
   'notes'=>(string)($raw['observacoes']['obs_venda']??''),'items_json'=>json_encode($items,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
   'departments_json'=>json_encode($departments,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
   'duplicated_from'=>(string)($order['number']??$order['omie_code'])
  ];
  return ['form'=>$form,'missing_items'=>$missing,'source'=>$order];
 }

 private static function importMissingOrderClient(string $omieCode): void{
  if($omieCode===''||!ctype_digit($omieCode))throw new RuntimeException('O orçamento está sem um cliente válido da Omie.');
  try{
   $response=(new OmieClient())->call('clients','ConsultarCliente',['codigo_cliente_omie'=>(int)$omieCode]);
   $client=$response['clientes_cadastro'][0]??$response;
   $code=(string)($client['codigo_cliente_omie']??$client['codigo_cliente']??$omieCode);
   $name=(string)($client['nome_fantasia']??$client['razao_social']??$code);
   $phone=trim((string)($client['telefone1_ddd']??'').' '.(string)($client['telefone1_numero']??''));
   DB::exec("INSERT INTO clients(omie_code,name,legal_name,document,email,phone,city,uf,seller_omie_code,active,raw_json,updated_at)
             VALUES(?,?,?,?,?,?,?,?,?,1,?,NOW())
             ON DUPLICATE KEY UPDATE name=VALUES(name),legal_name=VALUES(legal_name),document=VALUES(document),email=VALUES(email),phone=VALUES(phone),city=VALUES(city),uf=VALUES(uf),seller_omie_code=VALUES(seller_omie_code),active=1,raw_json=VALUES(raw_json),updated_at=NOW()",
    [$code,$name,$client['razao_social']??null,$client['cnpj_cpf']??null,$client['email']??null,$phone,$client['cidade']??null,$client['estado']??null,$client['codigo_vendedor']??null,json_encode($client,JSON_UNESCAPED_UNICODE)]);
  }catch(Throwable $e){throw new RuntimeException('O cliente deste orçamento ainda não está no CRM e não pôde ser carregado da Omie agora: '.$e->getMessage(),0,$e);}
 }

 public static function editBudgetForm(int $id,array $u): array{
  $detail=self::orderDetail($id,$u);$order=$detail['order'];$raw=$detail['raw'];
  if(!in_array((string)($order['stage_code']??''),OrderPolicy::budgetStageCodes(),true))throw new RuntimeException('Somente pedidos que estão em orçamento podem ser editados por este fluxo.');
  $status=mb_strtoupper(trim((string)($order['status']??'')));
  $registration=(array)($raw['infoCadastro']??[]);
  if(str_contains($status,'CANCEL')||str_contains($status,'FATUR')||($registration['cancelado']??'N')==='S'||($registration['faturado']??'N')==='S')throw new RuntimeException('Este orçamento não pode mais ser alterado porque foi cancelado ou faturado na Omie.');
  $omieCode=trim((string)($order['omie_code']??''));
  if($omieCode===''||!ctype_digit($omieCode))throw new RuntimeException('Orçamento sem código válido da Omie.');
  if((int)($order['client_id']??0)<=0){self::importMissingOrderClient((string)($order['client_omie_code']??''));$detail=self::orderDetail($id,$u);$order=$detail['order'];$raw=$detail['raw'];}

  $copy=self::duplicateOrderForm($id,$u);$form=$copy['form'];$header=(array)($raw['cabecalho']??[]);
  $forecast=(string)($order['forecast_date']??'');
  $form['forecast_date']=$forecast!==''&&$forecast>=date('Y-m-d')?$forecast:date('Y-m-d');
  $form['stage']=(string)($order['stage_code']??$header['etapa']??'');
  $form['edit_order_id']=$id;
  $form['editing_order_label']=(string)($order['number']??$omieCode);
  $form['request_token']='OMIE-'.$omieCode;
  unset($form['duplicated_from']);

  if((string)($header['codigo_parcela']??'')==='999'){
   $installments=[];
   foreach((array)($raw['lista_parcelas']['parcela']??[]) as $parcel){
    if(!is_array($parcel))continue;
    $due=(string)($parcel['data_vencimento']??'');
    if($due!=='')$due=date('Y-m-d',strtotime(str_replace('/','-',$due)));
    $installments[]=['value'=>(float)($parcel['valor']??0),'due_date'=>$due,'payment_method'=>(string)($parcel['meio_pagamento']??''),'generate_boleto'=>(string)($parcel['nao_gerar_boleto']??'S')==='N'];
   }
   if($installments){$form['custom_installments']='S';$form['installments_json']=json_encode($installments,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
  }
  return ['form'=>$form,'missing_items'=>$copy['missing_items'],'source'=>$order];
 }

 public static function deleteOrder(int $id): array{
  $order=DB::one("SELECT * FROM orders WHERE id=?",[$id]);
  if(!$order)throw new RuntimeException('Pedido não encontrado.');
  $omieCode=trim((string)($order['omie_code']??''));
  if($omieCode==='')throw new RuntimeException('Pedido sem código Omie. A exclusão sincronizada não pode ser realizada.');

  $omie=new OmieClient();$remoteAlreadyAbsent=false;
  try{$response=$omie->call('orders','ExcluirPedido',['codigo_pedido'=>(int)$omieCode]);}
  catch(Throwable $e){
   $message=mb_strtoupper($e->getMessage(),'UTF-8');
   $remoteAlreadyAbsent=str_contains($message,'PEDIDO NÃO CADASTRADO')||str_contains($message,'PEDIDO NAO CADASTRADO');
   if(!$remoteAlreadyAbsent)throw $e;
   $response=['already_absent'=>true,'message'=>$e->getMessage()];
  }

  try{DB::exec("DELETE FROM orders WHERE id=?",[$id]);}
  catch(Throwable $e){throw new RuntimeException('O pedido foi excluído na Omie, mas não pôde ser removido do CRM: '.$e->getMessage(),0,$e);}
  $metricUpdated=true;
  try{self::rebuildClientMetric((string)($order['client_omie_code']??''));}
  catch(Throwable){$metricUpdated=false;}
  return ['order'=>$order,'response'=>$response,'metric_updated'=>$metricUpdated,'remote_already_absent'=>$remoteAlreadyAbsent];
 }

 private static function rebuildClientMetric(string $clientOmieCode): void{
  if($clientOmieCode==='')return;
  $client=DB::one("SELECT id FROM clients WHERE omie_code=?",[$clientOmieCode]);if(!$client)return;
  [$validOrders,$validOrderParams]=OrderPolicy::validReportSql();
  $orders=DB::all(
   "SELECT order_date,total,seller_omie_code FROM orders
    WHERE client_omie_code=? AND order_date IS NOT NULL AND ".$validOrders."
    ORDER BY order_date DESC",
   array_merge([$clientOmieCode],$validOrderParams)
  );
  $today=date('Y-m-d');$yearAgo=date('Y-m-d',strtotime('-12 months'));
  $last=$orders[0]['order_date']??null;$revenue=0.0;$count=0;$diffs=[];$dates=[];
  foreach($orders as $order){
   if($order['order_date']>=$yearAgo&&$order['order_date']<=$today){$revenue+=(float)$order['total'];$count++;}
   $dates[]=$order['order_date'];
  }
  for($i=0;$i<count($dates)-1;$i++){$diff=(strtotime($dates[$i])-strtotime($dates[$i+1]))/86400;if($diff>0)$diffs[]=$diff;}
  $average=$diffs?array_sum($diffs)/count($diffs):null;$ticket=$count>0?$revenue/$count:0;
  DB::exec(
   "INSERT INTO client_metrics(client_id,last_purchase_at,revenue_12m,orders_12m,avg_ticket_12m,avg_interval_days,updated_at)
    VALUES(?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE last_purchase_at=VALUES(last_purchase_at),revenue_12m=VALUES(revenue_12m),orders_12m=VALUES(orders_12m),avg_ticket_12m=VALUES(avg_ticket_12m),avg_interval_days=VALUES(avg_interval_days),updated_at=NOW()",
   [(int)$client['id'],$last,$revenue,$count,$ticket,$average]
  );
 }

 private static function markDraftSent(array $i,string $code,string $number): void{
  self::ensureDraftTable();
  $id=(int)($i['draft_id']??0);
  if($id<=0)return;
  DB::exec("UPDATE local_order_drafts SET status='sent',omie_code=?,omie_number=?,sent_at=NOW(),updated_at=NOW() WHERE id=?",
   [$code?:null,$number?:null,$id]);
 }

 private static function reconcileSentDrafts(): void{
  DB::exec("UPDATE local_order_drafts d
            INNER JOIN omie_order_logs l
              ON l.integration_code=LEFT(CONCAT('TDCRM-',d.request_token),60)
             AND l.status='success'
            SET d.status='sent',d.omie_code=COALESCE(NULLIF(d.omie_code,''),l.omie_order_code),
                d.omie_number=COALESCE(NULLIF(d.omie_number,''),l.omie_order_number),
                d.sent_at=COALESCE(d.sent_at,l.created_at),d.updated_at=NOW()
            WHERE d.status='draft'");
 }

 private static function persistSentOrder(string $code,string $number,array $payload,array $remote,string $clientCode,string $seller,float $total): bool{
  if($code==='')return false;
  try{
   $record=(array)($remote['pedido_venda_produto']??$remote);
   $cab=(array)($record['cabecalho']??$payload['cabecalho']??[]);
   $info=(array)($record['infoCadastro']??[]);
   $additional=(array)($record['informacoes_adicionais']??$payload['informacoes_adicionais']??[]);
   $totals=(array)($record['total_pedido']??[]);
   $status=(($info['cancelado']??'N')==='S')?'CANCELADO':((($info['faturado']??'N')==='S')?'FATURADO':'ATIVO');
   $included=!empty($info['dInc'])?date('Y-m-d',strtotime(str_replace('/','-',(string)$info['dInc']))):date('Y-m-d');
   $forecast=!empty($cab['data_previsao'])?date('Y-m-d',strtotime(str_replace('/','-',(string)$cab['data_previsao']))):null;
   $raw=$record?:$payload;
   DB::exec("INSERT INTO orders(omie_code,number,client_omie_code,seller_omie_code,order_date,forecast_date,total,status,stage_code,raw_json,updated_at)
             VALUES(?,?,?,?,?,?,?,?,?,?,NOW())
             ON DUPLICATE KEY UPDATE number=VALUES(number),client_omie_code=VALUES(client_omie_code),seller_omie_code=VALUES(seller_omie_code),
               order_date=VALUES(order_date),forecast_date=VALUES(forecast_date),total=VALUES(total),status=VALUES(status),stage_code=VALUES(stage_code),raw_json=VALUES(raw_json),updated_at=NOW()",
    [$code,$number!==''?$number:($cab['numero_pedido']??null),$clientCode,(string)($additional['codVend']??$seller),$included,$forecast,
     (float)($totals['valor_total_pedido']??$total),$status,(string)($cab['etapa']??''),json_encode($raw,JSON_UNESCAPED_UNICODE)]);
   return true;
  }catch(Throwable){return false;}
 }

 public static function ensureCoreCatalogs(): array{
  DB::conn()->exec(DB::sql("CREATE TABLE IF NOT EXISTS departments(code VARCHAR(80) PRIMARY KEY,description VARCHAR(255) NOT NULL,structure VARCHAR(255) NULL,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"));
  $checks=[
   'stages'=>"SELECT COUNT(*) FROM order_stages WHERE active=1",
   'categories'=>"SELECT COUNT(*) FROM categories WHERE active=1",
   'departments'=>"SELECT COUNT(*) FROM departments WHERE active=1",
   'accounts'=>"SELECT COUNT(*) FROM financial_accounts WHERE active=1",
   'payment_terms'=>"SELECT COUNT(*) FROM payment_terms WHERE active=1 AND code<>'999'",
  ];
  $synced=[];$errors=[];
  foreach($checks as $module=>$sql){
   $count=(int)(DB::scalar($sql)??0);
   if($count>0)continue;
   try{
    $page=1;$guard=0;
    do{
     $result=SyncService::run($module,$page);
     $synced[$module]=($synced[$module]??0)+(int)($result['count']??0);
     $done=!empty($result['done']);
     $page=(int)($result['page']??$page)+1;
     $guard++;
    }while(!$done&&$guard<25);
   }catch(Throwable $e){
    $errors[$module]=$e->getMessage();
   }
  }
  return ['synced'=>$synced,'errors'=>$errors];
 }

 public static function defaults(): array{
  $j=DB::scalar("SELECT value_json FROM settings WHERE setting_key='order_defaults'");
  $d=$j?json_decode((string)$j,true):[];
  if(!is_array($d))$d=[];

  $changed=false;

  $validations=[
   'stage'=>["SELECT 1 FROM order_stages WHERE code=? AND active=1","SELECT code FROM order_stages WHERE active=1 ORDER BY code LIMIT 1",'code'],
   'category'=>["SELECT 1 FROM categories WHERE code=? AND active=1","SELECT code FROM categories WHERE active=1 ORDER BY code LIMIT 1",'code'],
   'account'=>["SELECT 1 FROM financial_accounts WHERE omie_code=? AND active=1","SELECT omie_code FROM financial_accounts WHERE active=1 ORDER BY selected DESC,name,omie_code LIMIT 1",'omie_code'],
   'payment_term'=>["SELECT 1 FROM payment_terms WHERE code=? AND active=1 AND code<>'999'","SELECT code FROM payment_terms WHERE active=1 AND code<>'999' ORDER BY code LIMIT 1",'code'],
   'payment_method'=>["SELECT 1 FROM payment_methods WHERE code=?","SELECT code FROM payment_methods ORDER BY description,code LIMIT 1",'code'],
   'document_type'=>["SELECT 1 FROM document_types WHERE code=?","SELECT code FROM document_types ORDER BY description,code LIMIT 1",'code'],
   'tax_scenario'=>["SELECT 1 FROM tax_scenarios WHERE omie_code=? AND active=1","SELECT omie_code FROM tax_scenarios WHERE active=1 ORDER BY is_default DESC,name,omie_code LIMIT 1",'omie_code'],
   'stock_location'=>["SELECT 1 FROM stock_locations WHERE omie_code=? AND active=1","SELECT omie_code FROM stock_locations WHERE active=1 ORDER BY is_default DESC,name,omie_code LIMIT 1",'omie_code'],
  ];

  foreach($validations as $key=>[$validSql,$fallbackSql,$field]){
   $current=(string)($d[$key]??'');
   $valid=$current!==''?DB::one($validSql,[$current]):null;
   if(!$valid){
    $row=DB::one($fallbackSql);
    $new=$row&&!empty($row[$field])?(string)$row[$field]:'';
    if($current!==$new){$d[$key]=$new;$changed=true;}
   }
  }

  $consumer=($d['consumer_final']??'S')==='N'?'N':'S';
  if(($d['consumer_final']??null)!==$consumer){$d['consumer_final']=$consumer;$changed=true;}

  $send=($d['send_email']??'N')==='S'?'S':'N';
  if(($d['send_email']??null)!==$send){$d['send_email']=$send;$changed=true;}

  $freight=self::normalizeFreightMode($d['freight_mode']??'9');
  if(($d['freight_mode']??null)!==$freight){$d['freight_mode']=$freight;$changed=true;}

  if($changed){
   DB::exec("INSERT INTO settings(setting_key,value_json,updated_at) VALUES('order_defaults',?,NOW())
             ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=NOW()",
    [json_encode($d,JSON_UNESCAPED_UNICODE)]);
  }

  return $d;
 }
 public static function selectedCarrierCodes(): array{
  $raw=DB::scalar("SELECT value_json FROM settings WHERE setting_key='order_carriers'");
  $data=$raw?json_decode((string)$raw,true):[];
  $codes=is_array($data)?(array)($data['codes']??$data):[];
  return array_values(array_unique(array_filter(array_map(static fn($code)=>trim((string)$code),$codes),static fn($code)=>$code!==''&&ctype_digit($code))));
 }
 private static function clientHasTag(array $client,string $expected): bool{
  $raw=json_decode((string)($client['raw_json']??''),true);
  if(!is_array($raw))return false;
  $found=[];
  $scan=function(mixed $value,string $key='') use (&$scan,&$found): void{
   if(!is_array($value)){
    if(in_array(mb_strtolower($key),['tag','tags'],true)&&is_scalar($value))$found[]=trim((string)$value);
    return;
   }
   foreach($value as $childKey=>$child)$scan($child,is_string($childKey)?$childKey:$key);
  };
  $scan($raw);
  $needle=mb_strtolower(trim($expected));
  foreach($found as $tag)if(mb_strtolower($tag)===$needle)return true;
  return false;
 }
 public static function carrierCandidates(): array{
  $selected=array_flip(self::selectedCarrierCodes());
  $rows=DB::all("SELECT id,omie_code,name,legal_name,document,city,uf,raw_json FROM clients WHERE active=1 AND LOWER(CAST(raw_json AS CHAR)) LIKE ? ORDER BY name",['%transportadora%']);
  $carriers=[];
  foreach($rows as $row){
   $code=trim((string)($row['omie_code']??''));
   if($code===''||!ctype_digit($code)||!self::clientHasTag($row,'Transportadora'))continue;
   $row['selected']=isset($selected[$code]);
   unset($row['raw_json']);
   $carriers[]=$row;
  }
  return $carriers;
 }
 public static function configuredCarriers(): array{
  return array_values(array_filter(self::carrierCandidates(),static fn($row)=>!empty($row['selected'])));
 }
 public static function saveCarriers(array $codes): void{
  $allowed=[];
  foreach(self::carrierCandidates() as $row)$allowed[(string)$row['omie_code']]=true;
  $selected=[];
  foreach($codes as $code){
   $code=trim((string)$code);
   if(isset($allowed[$code]))$selected[]=$code;
  }
  $selected=array_values(array_unique($selected));
  DB::exec("INSERT INTO settings(setting_key,value_json,updated_at) VALUES('order_carriers',?,NOW())
            ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=NOW()",[json_encode(['codes'=>$selected],JSON_UNESCAPED_UNICODE)]);
 }
 public static function saveDefaults(array $i): void{
  $pickValid=static function(string $value,string $validSql,string $fallbackSql,string $field): string{
   $value=trim($value);
   if($value!==''&&DB::one($validSql,[$value]))return $value;
   $row=DB::one($fallbackSql);
   return $row&&!empty($row[$field])?(string)$row[$field]:'';
  };

  $d=[
   'stage'=>$pickValid((string)($i['stage']??''),"SELECT 1 FROM order_stages WHERE code=? AND active=1","SELECT code FROM order_stages WHERE active=1 ORDER BY code LIMIT 1",'code'),
   'category'=>$pickValid((string)($i['category']??''),"SELECT 1 FROM categories WHERE code=? AND active=1","SELECT code FROM categories WHERE active=1 ORDER BY code LIMIT 1",'code'),
   'account'=>$pickValid((string)($i['account']??''),"SELECT 1 FROM financial_accounts WHERE omie_code=? AND active=1","SELECT omie_code FROM financial_accounts WHERE active=1 ORDER BY selected DESC,name,omie_code LIMIT 1",'omie_code'),
   'payment_term'=>$pickValid((string)($i['payment_term']??''),"SELECT 1 FROM payment_terms WHERE code=? AND active=1 AND code<>'999'","SELECT code FROM payment_terms WHERE active=1 AND code<>'999' ORDER BY code LIMIT 1",'code'),
   'payment_method'=>$pickValid((string)($i['payment_method']??''),"SELECT 1 FROM payment_methods WHERE code=?","SELECT code FROM payment_methods ORDER BY description,code LIMIT 1",'code'),
   'document_type'=>$pickValid((string)($i['document_type']??''),"SELECT 1 FROM document_types WHERE code=?","SELECT code FROM document_types ORDER BY description,code LIMIT 1",'code'),
   'tax_scenario'=>$pickValid((string)($i['tax_scenario']??''),"SELECT 1 FROM tax_scenarios WHERE omie_code=? AND active=1","SELECT omie_code FROM tax_scenarios WHERE active=1 ORDER BY is_default DESC,name,omie_code LIMIT 1",'omie_code'),
   'stock_location'=>$pickValid((string)($i['stock_location']??''),"SELECT 1 FROM stock_locations WHERE omie_code=? AND active=1","SELECT omie_code FROM stock_locations WHERE active=1 ORDER BY is_default DESC,name,omie_code LIMIT 1",'omie_code'),
   'consumer_final'=>(string)($i['consumer_final']??'S')==='N'?'N':'S',
   'send_email'=>!empty($i['send_email'])?'S':'N',
   'freight_mode'=>self::normalizeFreightMode($i['freight_mode']??'9')
  ];

  $missing=[];
  foreach(['stage'=>'Etapa','category'=>'Categoria','account'=>'Conta corrente','payment_term'=>'Condição de pagamento'] as $key=>$label){
   if($d[$key]==='')$missing[]=$label;
  }
  if($missing)throw new RuntimeException('Não existem opções sincronizadas para: '.implode(', ',$missing).'. Sincronize esses módulos antes de salvar as configurações.');

  DB::exec("INSERT INTO settings(setting_key,value_json,updated_at) VALUES('order_defaults',?,NOW())
            ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=NOW()",[json_encode($d,JSON_UNESCAPED_UNICODE)]);
  $savedRaw=DB::scalar("SELECT value_json FROM settings WHERE setting_key='order_defaults'");
  $saved=$savedRaw?json_decode((string)$savedRaw,true):null;
  if(!is_array($saved)||self::normalizeFreightMode($saved['freight_mode']??null)!==$d['freight_mode'])throw new RuntimeException('Não foi possível confirmar o salvamento do frete padrão.');
 }
 public static function saveFreightMode(mixed $value): string{
  $mode=trim((string)$value);
  if(!in_array($mode,['0','1','2','3','4','9'],true))throw new RuntimeException('Tipo de frete padrão inválido.');
  $defaults=self::defaults();
  $defaults['freight_mode']=$mode;
  DB::exec("INSERT INTO settings(setting_key,value_json,updated_at) VALUES('order_defaults',?,NOW())
            ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=NOW()",[json_encode($defaults,JSON_UNESCAPED_UNICODE)]);
  $savedRaw=DB::scalar("SELECT value_json FROM settings WHERE setting_key='order_defaults'");
  $saved=$savedRaw?json_decode((string)$savedRaw,true):null;
  if(!is_array($saved)||self::normalizeFreightMode($saved['freight_mode']??null)!==$mode)throw new RuntimeException('O banco não confirmou o novo frete padrão.');
  return $mode;
 }
 private static function validateHeaderChoices(array $d,bool $requireCore=false): void{
  if(($requireCore||($d['stage']??'')!=='')&&!DB::one("SELECT 1 FROM order_stages WHERE code=? AND active=1",[(string)($d['stage']??'')]))throw new RuntimeException('Etapa inválida.');
  if(($requireCore||($d['category']??'')!=='')&&!DB::one("SELECT 1 FROM categories WHERE code=? AND active=1",[(string)($d['category']??'')]))throw new RuntimeException('Categoria inválida.');
  if(($requireCore||($d['account']??'')!=='')&&!DB::one("SELECT 1 FROM financial_accounts WHERE omie_code=? AND active=1",[(string)($d['account']??'')]))throw new RuntimeException('Conta corrente inválida.');
  if(($requireCore||($d['payment_term']??'')!=='')&&((string)($d['payment_term']??'')==='999'||!DB::one("SELECT 1 FROM payment_terms WHERE code=? AND active=1",[(string)($d['payment_term']??'')])))throw new RuntimeException('Condição de pagamento inválida.');
  if(($d['tax_scenario']??'')!==''&&!DB::one("SELECT 1 FROM tax_scenarios WHERE omie_code=? AND active=1",[(string)$d['tax_scenario']]))throw new RuntimeException('Cenário fiscal inválido.');
  if(($d['stock_location']??'')!==''&&!DB::one("SELECT 1 FROM stock_locations WHERE omie_code=? AND active=1",[(string)$d['stock_location']]))throw new RuntimeException('Local de estoque inválido.');
  if(($d['payment_method']??'')!==''&&!DB::one("SELECT 1 FROM payment_methods WHERE code=?",[(string)$d['payment_method']]))throw new RuntimeException('Meio de pagamento inválido.');
  if(($d['document_type']??'')!==''&&!DB::one("SELECT 1 FROM document_types WHERE code=?",[(string)$d['document_type']]))throw new RuntimeException('Tipo de documento inválido.');
  if(!in_array((string)($d['freight_mode']??'9'),['0','1','2','3','4','9'],true))throw new RuntimeException('Modalidade de frete inválida.');
 }
 public static function ready(): array{
  self::ensureCoreCatalogs();
  $d=self::defaults();$m=[];
  foreach(['stage','category','account','payment_term'] as $k)if(empty($d[$k]))$m[]=$k;
  if((int)(DB::scalar("SELECT COUNT(*) FROM products WHERE active=1 AND unit_price>0")??0)===0)$m[]='products';
  if((int)(DB::scalar("SELECT COUNT(*) FROM payment_terms WHERE active=1 AND code<>'999'")??0)===0)$m[]='payment_terms';
  return ['ok'=>!$m,'missing'=>$m,'defaults'=>$d];
 }
 public static function profiles(): array{
  return DB::all("SELECT * FROM order_profiles WHERE active=1 ORDER BY id");
 }
 public static function saveProfile(array $i): void{
  $id=(int)($i['id']??0);$code=strtoupper(preg_replace('/[^A-Za-z0-9_]/','_',trim((string)($i['code']??''))));
  $name=trim((string)($i['name']??''));if($code===''||$name==='')throw new RuntimeException('Código e nome do tipo de pedido são obrigatórios.');
  $flags=[];
  foreach(['default_no_stock','default_no_finance','default_no_total','default_reserve_stock'] as $k)$flags[$k]=!empty($i[$k])?'S':'N';
  if($id>0)DB::exec("UPDATE order_profiles SET code=?,name=?,description=?,default_no_stock=?,default_no_finance=?,default_no_total=?,default_reserve_stock=?,active=?,updated_at=NOW() WHERE id=?",
   [$code,$name,trim((string)($i['description']??'')),$flags['default_no_stock'],$flags['default_no_finance'],$flags['default_no_total'],$flags['default_reserve_stock'],!empty($i['active'])?1:0,$id]);
  else DB::exec("INSERT INTO order_profiles(code,name,description,default_no_stock,default_no_finance,default_no_total,default_reserve_stock,active,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,NOW(),NOW())",
   [$code,$name,trim((string)($i['description']??'')),$flags['default_no_stock'],$flags['default_no_finance'],$flags['default_no_total'],$flags['default_reserve_stock'],!empty($i['active'])?1:0]);
 }
 public static function build(array $i,array $u): array{
  $r=self::ready();if(!$r['ok'])throw new RuntimeException('Configuração incompleta: '.implode(', ',$r['missing']).'.');$defaults=$r['defaults'];
  $client=DB::one("SELECT * FROM clients WHERE id=? AND active=1",[(int)($i['client_id']??0)]);if(!$client)throw new RuntimeException('Cliente inválido.');
  $clientUnassigned=trim((string)($client['seller_omie_code']??''))==='';
  if($u['role']==='seller'&&!$clientUnassigned&&(string)$client['seller_omie_code']!==(string)$u['seller_omie_code']){
   $editableOriginal=null;$editingId=(int)($i['edit_order_id']??0);
   if($editingId>0)$editableOriginal=DB::one("SELECT client_omie_code FROM orders WHERE id=? AND seller_omie_code=?",[$editingId,(string)$u['seller_omie_code']]);
   if(!$editableOriginal||(string)$editableOriginal['client_omie_code']!==(string)$client['omie_code'])throw new RuntimeException('Cliente fora da sua carteira.');
  }
  $seller=$u['role']==='seller'?(string)$u['seller_omie_code']:(string)($i['seller_omie_code']??'');if($seller===''||!DB::one("SELECT 1 FROM sellers WHERE omie_code=? AND active=1",[$seller]))throw new RuntimeException('Vendedor obrigatório ou inválido.');

  // O vendedor pode setar os campos operacionais do pedido; os padrões apenas agilizam a digitação.
  $header=[
   'stage'=>(string)($i['stage']??$defaults['stage']??''),
   'category'=>(string)($i['category']??$defaults['category']??''),
   'account'=>(string)($i['account']??$defaults['account']??''),
   'payment_term'=>(string)($i['payment_term']??$defaults['payment_term']??''),
   'payment_method'=>(string)($i['payment_method']??$defaults['payment_method']??''),
   'document_type'=>(string)($defaults['document_type']??''),
   'tax_scenario'=>(string)($i['tax_scenario']??$defaults['tax_scenario']??''),
   'stock_location'=>(string)($defaults['stock_location']??''),
   'consumer_final'=>(string)($i['consumer_final']??$defaults['consumer_final']??'S')==='N'?'N':'S',
   'send_email'=>(string)($i['send_email']??$defaults['send_email']??'N')==='S'?'S':'N',
   'freight_mode'=>self::normalizeFreightMode($i['freight_mode']??null,(string)($defaults['freight_mode']??'9')),
  ];
  self::validateHeaderChoices($header,true);

  $items=json_decode((string)($i['items_json']??'[]'),true);if(!is_array($items)||!$items)throw new RuntimeException('Inclua ao menos um produto.');
  if(count($items)>199)throw new RuntimeException('A Omie aceita no máximo 199 itens por pedido.');

  $requestToken=preg_replace('/[^A-Za-z0-9_-]/','',(string)($i['request_token']??''));
  if($requestToken==='')$requestToken=date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,8));
  $integration=substr('TDCRM-'.$requestToken,0,60);
  $editingOrder=(int)($i['edit_order_id']??0);
  $existing=$editingOrder>0?null:DB::one("SELECT * FROM omie_order_logs WHERE integration_code=? AND status='success' LIMIT 1",[$integration]);
  if($existing)return ['payload'=>json_decode((string)$existing['request_json'],true),'client'=>$client,'seller'=>$seller,'total'=>(float)$existing['total'],'integration'=>$integration,'existing'=>$existing];

  $det=[];$commercialTotal=0.0;$fiscalTotal=0.0;$financialTotal=0.0;$calculatedNetWeight=0.0;$calculatedGrossWeight=0.0;$n=0;
  foreach($items as $it){
   $p=DB::one("SELECT * FROM products WHERE id=? AND active=1",[(int)($it['product_id']??0)]);if(!$p)throw new RuntimeException('Produto inválido.');
   $q=(float)($it['quantity']??0);if($q<=0)throw new RuntimeException('Quantidade inválida para '.$p['description'].'.');
   $price=(float)($it['unit_price']??$p['unit_price']);if($price<=0)throw new RuntimeException('Produto sem preço: '.$p['description'].'.');
   $discountType=in_array((string)($it['discount_type']??'V'),['V','P'],true)?(string)$it['discount_type']:'V';
   $discountValue=max(0,(float)($it['discount_value']??$it['discount']??0));
   $discount=$discountType==='P'?($q*$price*min(100,$discountValue)/100):$discountValue;
   $line=max(0,$q*$price-$discount);if($line<=0)throw new RuntimeException('Total inválido para '.$p['description'].'.');

   $noStock=!empty($it['no_stock'])?'S':'N';
   $noFinance=!empty($it['no_finance'])?'S':'N';
   $noTotal=!empty($it['no_total'])?'S':'N';
   $reserve=!empty($it['reserve_stock'])?'S':'N';
   $commercialTotal+=$line;if($noTotal==='N')$fiscalTotal+=$line;if($noFinance==='N')$financialTotal+=$line;$n++;
   $productRaw=json_decode((string)($p['raw_json']??''),true);if(!is_array($productRaw))$productRaw=[];
   $unitNet=max(0,(float)($it['unit_net_weight']??$productRaw['peso_liq']??0));$unitGross=max(0,(float)($it['unit_gross_weight']??$productRaw['peso_bruto']??0));
   $calculatedNetWeight+=$q*$unitNet;$calculatedGrossWeight+=$q*$unitGross;

   $prod=['codigo_produto'=>(int)$p['omie_code'],'descricao'=>(string)$p['description'],'quantidade'=>$q,'unidade'=>(string)($it['unit']??$p['unit']?:'UN'),'valor_unitario'=>$price,'reservado'=>$reserve];
   if($discountValue>0){$prod['tipo_desconto']=$discountType;if($discountType==='P')$prod['percentual_desconto']=$discountValue;else $prod['valor_desconto']=$discountValue;}
   $ncm=trim((string)($it['ncm']??$p['ncm']??''));if($ncm!=='')$prod['ncm']=$ncm;
   $cfop=trim((string)($it['cfop']??''));if($cfop!=='')$prod['cfop']=$cfop;

   $inf=[
    'nao_movimentar_estoque'=>$noStock,
    'nao_gerar_financeiro'=>$noFinance,
    'nao_somar_total'=>$noTotal,
   ];
   $categoryItem=trim((string)($it['category_code']??''));if($categoryItem!==''){
    if(!DB::one("SELECT 1 FROM categories WHERE code=? AND active=1",[$categoryItem]))throw new RuntimeException('Categoria inválida no item '.$n.'.');
    $inf['codigo_categoria_item']=$categoryItem;
   }
   $taxItem=trim((string)($it['tax_scenario_code']??''));if($taxItem!==''){
    if(!DB::one("SELECT 1 FROM tax_scenarios WHERE omie_code=? AND active=1",[$taxItem]))throw new RuntimeException('Cenário fiscal inválido no item '.$n.'.');
    $inf['codigo_cenario_impostos_item']=(int)$taxItem;
   }
   $stockItem=trim((string)($it['stock_location_code']??$header['stock_location']));if($stockItem!==''){
    if(!DB::one("SELECT 1 FROM stock_locations WHERE omie_code=? AND active=1",[$stockItem]))throw new RuntimeException('Local de estoque inválido no item '.$n.'.');
    $inf['codigo_local_estoque']=(int)$stockItem;
   }
   $po=trim((string)($it['purchase_order_number']??''));if($po!=='')$inf['numero_pedido_compra']=mb_substr($po,0,15);
   $poItem=(int)($it['purchase_order_item']??0);if($poItem>0)$inf['item_pedido_compra']=$poItem;
   $fiscalNotes=trim((string)($it['fiscal_notes']??''));if($fiscalNotes!=='')$inf['dados_adicionais_item']=$fiscalNotes;

   $det[]=['ide'=>['codigo_item_integracao'=>(string)$n],'produto'=>$prod,'inf_adic'=>$inf];
  }

  $forecast=(string)($i['forecast_date']??date('Y-m-d'));if(!strtotime($forecast)||$forecast<date('Y-m-d'))throw new RuntimeException('Previsão inválida.');
  $customInstallments=(string)($i['custom_installments']??'N')==='S';$parcelList=[];
  if($customInstallments){
   $parcelInput=json_decode((string)($i['installments_json']??'[]'),true);if(!is_array($parcelInput)||!$parcelInput)throw new RuntimeException('Inclua ao menos uma parcela personalizada.');
   if(count($parcelInput)>999)throw new RuntimeException('A Omie aceita no máximo 999 parcelas.');
   $targetCents=(int)round($financialTotal*100);if($targetCents<=0)throw new RuntimeException('Não há valor financeiro para parcelar.');
   $parcelSource=[];$parcelCents=0;
   foreach(array_values($parcelInput) as $index=>$row){
    if(!is_array($row))throw new RuntimeException('Parcela personalizada inválida.');
    $valueCents=(int)round((float)str_replace(',','.',(string)($row['value']??0))*100);if($valueCents<=0)throw new RuntimeException('O valor da parcela '.($index+1).' deve ser maior que zero.');
    $due=(string)($row['due_date']??'');$date=DateTime::createFromFormat('Y-m-d',$due);if(!$date||$date->format('Y-m-d')!==$due)throw new RuntimeException('Vencimento inválido na parcela '.($index+1).'.');
    if($due<date('Y-m-d'))throw new RuntimeException('O vencimento da parcela '.($index+1).' não pode estar no passado.');
    $method=trim((string)($row['payment_method']??$header['payment_method']));if($method!==''&&!DB::one("SELECT 1 FROM payment_methods WHERE code=?",[$method]))throw new RuntimeException('Meio de pagamento inválido na parcela '.($index+1).'.');
    $parcelCents+=$valueCents;$parcelSource[]=['value_cents'=>$valueCents,'due_date'=>$due,'payment_method'=>$method,'generate_boleto'=>!empty($row['generate_boleto'])];
   }
   if($parcelCents!==$targetCents)throw new RuntimeException('As parcelas devem totalizar '.number_format($targetCents/100,2,',','.').'. Diferença atual: '.number_format(($targetCents-$parcelCents)/100,2,',','.').'.');
   $percentUsed=0.0;$lastParcel=count($parcelSource)-1;
   foreach($parcelSource as $index=>$row){$percent=$index===$lastParcel?round(100-$percentUsed,4):round($row['value_cents']/$targetCents*100,4);$percentUsed+=$percent;$parcel=['numero_parcela'=>$index+1,'valor'=>$row['value_cents']/100,'percentual'=>$percent,'data_vencimento'=>date('d/m/Y',strtotime($row['due_date'])),'nao_gerar_boleto'=>$row['generate_boleto']?'N':'S'];if($row['payment_method']!=='')$parcel['meio_pagamento']=$row['payment_method'];if($header['document_type']!=='')$parcel['tipo_documento']=$header['document_type'];$parcelList[]=$parcel;}
  }
  $cab=['codigo_pedido_integracao'=>$integration,'codigo_cliente'=>(int)$client['omie_code'],'data_previsao'=>date('d/m/Y',strtotime($forecast)),'etapa'=>$header['stage'],'codigo_parcela'=>$customInstallments?'999':$header['payment_term']];
  if($customInstallments)$cab['qtde_parcelas']=count($parcelList);
  if($header['tax_scenario']!=='')$cab['codigo_cenario_impostos']=(int)$header['tax_scenario'];

  $info=['codigo_categoria'=>$header['category'],'codigo_conta_corrente'=>(int)$header['account'],'consumidor_final'=>$header['consumer_final'],'enviar_email'=>$header['send_email'],'codVend'=>(int)$seller];
  if($header['payment_method']!=='')$info['meio_pagamento']=$header['payment_method'];
  if($header['document_type']!=='')$info['tipo_documento']=$header['document_type'];
  if($header['send_email']==='S'&&!empty($client['email']))$info['utilizar_emails']=$client['email'];
  $carrierCode=trim((string)($i['carrier_code']??''));
  if($carrierCode!==''){
   $allowedCarrierCodes=array_column(self::configuredCarriers(),'omie_code');
   if(!ctype_digit($carrierCode)||!in_array($carrierCode,$allowedCarrierCodes,true))throw new RuntimeException('Transportadora inválida ou não habilitada nas configurações do pedido.');
  }
  $freight=['modalidade'=>$header['freight_mode']];
  $freightMap=['carrier_code'=>'codigo_transportadora','plate'=>'placa','plate_state'=>'placa_estado','rntrc'=>'registro_transportador','volumes'=>'quantidade_volumes','volume_type'=>'especie_volumes','volume_brand'=>'marca_volumes','volume_numbering'=>'numeracao_volumes','net_weight'=>'peso_liquido','gross_weight'=>'peso_bruto','freight_value'=>'valor_frete','insurance_value'=>'valor_seguro','other_expenses'=>'outras_despesas','delivery_date'=>'previsao_entrega','tracking_code'=>'codigo_rastreio'];
  foreach($freightMap as $from=>$to){$v=trim((string)($i[$from]??''));if($v==='')continue;if(in_array($from,['carrier_code','volumes'],true))$freight[$to]=(int)$v;elseif(in_array($from,['net_weight','gross_weight','freight_value','insurance_value','other_expenses'],true))$freight[$to]=(float)str_replace(',','.',$v);elseif($from==='delivery_date'&&strtotime($v))$freight[$to]=date('d/m/Y',strtotime($v));else $freight[$to]=$v;}
  if(!isset($freight['peso_liquido'])&&$calculatedNetWeight>0)$freight['peso_liquido']=round($calculatedNetWeight,3);
  if(!isset($freight['peso_bruto'])&&$calculatedGrossWeight>0)$freight['peso_bruto']=round($calculatedGrossWeight,3);
  if(!empty($i['own_vehicle']))$freight['veiculo_proprio']='S';

  $departmentsRaw=json_decode((string)($i['departments_json']??'[]'),true);
  if(!is_array($departmentsRaw))$departmentsRaw=[];
  $departmentBase=round($commercialTotal,2);
  if($departmentBase<=0)throw new RuntimeException('Não é possível distribuir departamentos em um pedido com valor total zerado.');

  $departmentSource=[];$departmentPercent=0.0;
  foreach($departmentsRaw as $dep){
   if(!is_array($dep))continue;
   $code=trim((string)($dep['code']??''));
   $percent=(float)str_replace(',','.',(string)($dep['percent']??0));
   if($code===''||$percent<=0)continue;
   if(!DB::one("SELECT 1 FROM departments WHERE code=? AND active=1",[$code]))throw new RuntimeException('Departamento inválido no rateio do pedido.');
   $departmentPercent+=$percent;
   $departmentSource[]=['code'=>$code,'percent'=>$percent];
  }
  if($departmentSource&&abs($departmentPercent-100)>0.01)throw new RuntimeException('O rateio por departamentos deve totalizar 100%. Total atual: '.number_format($departmentPercent,2,',','.').'%.');

  // A API do Pedido de Venda pode exigir tanto o percentual quanto o valor monetário
  // da distribuição. Calculamos em centavos e deixamos o resíduo para a última linha,
  // garantindo que a soma de nValor feche exatamente com o total do pedido.
  $departments=[];$distributed=0.0;$lastIndex=count($departmentSource)-1;
  foreach($departmentSource as $index=>$dep){
   $value=$index===$lastIndex
    ? round($departmentBase-$distributed,2)
    : round($departmentBase*((float)$dep['percent']/100),2);
   if($value<=0)throw new RuntimeException('Valor calculado da distribuição por departamento não pode ser zero.');
   $distributed=round($distributed+$value,2);
   $departments[]=[
    'cCodDepto'=>(string)$dep['code'],
    'nPerc'=>round((float)$dep['percent'],4),
    'nValor'=>$value,
    'nValorFixo'=>'N'
   ];
  }

  $payload=['cabecalho'=>$cab,'det'=>$det,'frete'=>$freight,'informacoes_adicionais'=>$info];
  if($departments)$payload['departamentos']=$departments;
  if($customInstallments)$payload['lista_parcelas']=['parcela'=>$parcelList];
  $notes=trim((string)($i['notes']??''));if($notes!=='')$payload['observacoes']=['obs_venda'=>$notes];
  return ['payload'=>$payload,'client'=>$client,'seller'=>$seller,'total'=>$commercialTotal,'fiscal_total'=>$fiscalTotal,'financial_total'=>$financialTotal,'integration'=>$integration];
 }
 public static function send(array $i,array $u): array{
  $b=self::build($i,$u);
  if(!empty($b['existing'])){
   $code=(string)($b['existing']['omie_order_code']??'');
   $number=(string)($b['existing']['omie_order_number']??'');
   $response=json_decode((string)($b['existing']['response_json']??''),true);if(!is_array($response))$response=[];
   $listed=self::persistSentOrder($code,$number,$b['payload'],(array)($response['verify']??$response['recovered']??[]),(string)$b['client']['omie_code'],$b['seller'],$b['total']);
   self::markDraftSent($i,$code,$number);
   return ['code'=>$code,'number'=>$number,'total'=>$b['total'],'reused'=>true,'listed'=>$listed];
  }
  $o=new OmieClient();
  try{
   $res=$o->call('orders','IncluirPedido',$b['payload']);
   $code=(string)($res['codigo_pedido']??'');$number=(string)($res['numero_pedido']??'');$verify=null;
   if($code!==''){try{$verify=$o->call('orders','ConsultarPedido',['codigo_pedido'=>(int)$code]);}catch(Throwable){}}
   DB::exec("INSERT INTO omie_order_logs(integration_code,omie_order_code,omie_order_number,client_id,seller_omie_code,user_id,total,request_json,response_json,status,created_at)
             VALUES(?,?,?,?,?,?,?,?,?,'success',NOW())
             ON DUPLICATE KEY UPDATE omie_order_code=VALUES(omie_order_code),omie_order_number=VALUES(omie_order_number),
               client_id=VALUES(client_id),seller_omie_code=VALUES(seller_omie_code),user_id=VALUES(user_id),total=VALUES(total),
               request_json=VALUES(request_json),response_json=VALUES(response_json),status='success',error_message=NULL,created_at=NOW()",
      [$b['integration'],$code?:null,$number?:null,(int)$b['client']['id'],$b['seller'],(int)$u['id'],$b['total'],json_encode($b['payload'],JSON_UNESCAPED_UNICODE),json_encode(['include'=>$res,'verify'=>$verify,'fiscal_total'=>$b['fiscal_total'],'financial_total'=>$b['financial_total']],JSON_UNESCAPED_UNICODE)]);
   $listed=self::persistSentOrder($code,$number,$b['payload'],(array)($verify??[]),(string)$b['client']['omie_code'],$b['seller'],$b['total']);
   self::markDraftSent($i,$code,$number);
   return ['code'=>$code,'number'=>$number,'total'=>$b['total'],'listed'=>$listed];
  }catch(Throwable $e){
   try{
    $found=$o->call('orders','ConsultarPedido',['codigo_pedido_integracao'=>$b['integration']]);
    $cab=$found['pedido_venda_produto']['cabecalho']??$found['cabecalho']??[];$code=(string)($cab['codigo_pedido']??'');
    if($code!==''){
     $number=(string)($cab['numero_pedido']??'');
     DB::exec("INSERT INTO omie_order_logs(integration_code,omie_order_code,omie_order_number,client_id,seller_omie_code,user_id,total,request_json,response_json,status,created_at)
               VALUES(?,?,?,?,?,?,?,?,?,'success',NOW())
               ON DUPLICATE KEY UPDATE omie_order_code=VALUES(omie_order_code),omie_order_number=VALUES(omie_order_number),
                 client_id=VALUES(client_id),seller_omie_code=VALUES(seller_omie_code),user_id=VALUES(user_id),total=VALUES(total),
                 request_json=VALUES(request_json),response_json=VALUES(response_json),status='success',error_message=NULL,created_at=NOW()",
       [$b['integration'],$code,$number?:null,(int)$b['client']['id'],$b['seller'],(int)$u['id'],$b['total'],json_encode($b['payload'],JSON_UNESCAPED_UNICODE),json_encode(['recovered'=>$found],JSON_UNESCAPED_UNICODE)]);
     $listed=self::persistSentOrder($code,$number,$b['payload'],$found,(string)$b['client']['omie_code'],$b['seller'],$b['total']);
     self::markDraftSent($i,$code,$number);
     return ['code'=>$code,'number'=>$number,'total'=>$b['total'],'recovered'=>true,'listed'=>$listed];
    }
   }catch(Throwable){}
   DB::exec("INSERT INTO omie_order_logs(integration_code,client_id,seller_omie_code,user_id,total,request_json,status,error_message,created_at)
             VALUES(?,?,?,?,?,?,'error',?,NOW()) ON DUPLICATE KEY UPDATE error_message=VALUES(error_message),created_at=NOW()",
      [$b['integration'],(int)$b['client']['id'],$b['seller'],(int)$u['id'],$b['total'],json_encode($b['payload'],JSON_UNESCAPED_UNICODE),mb_substr($e->getMessage(),0,4000)]);
   throw $e;
  }
 }

 public static function updateBudget(array $i,array $u): array{
  $id=(int)($i['edit_order_id']??0);if($id<=0)throw new RuntimeException('Orçamento inválido para atualização.');
  $edit=self::editBudgetForm($id,$u);$original=$edit['source'];$raw=json_decode((string)($original['raw_json']??''),true);if(!is_array($raw))$raw=[];
  $b=self::build($i,$u);$omieCode=(string)$original['omie_code'];$header=(array)($raw['cabecalho']??[]);
  $originalIntegration=trim((string)($header['codigo_pedido_integracao']??''));
  $integration=$originalIntegration!==''?$originalIntegration:substr('TDCRM-OMIE-'.$omieCode,0,60);
  $b['payload']['cabecalho']['codigo_pedido']=(int)$omieCode;
  $b['payload']['cabecalho']['codigo_pedido_integracao']=$integration;
  $originalFreight=(array)($raw['frete']??[]);
  foreach(['placa','placa_estado','registro_transportador','especie_volumes','marca_volumes','numeracao_volumes','valor_frete','valor_seguro','outras_despesas','previsao_entrega','codigo_rastreio','veiculo_proprio'] as $key)if(!array_key_exists($key,$b['payload']['frete'])&&array_key_exists($key,$originalFreight))$b['payload']['frete'][$key]=$originalFreight[$key];
  $originalInfo=(array)($raw['informacoes_adicionais']??[]);
  foreach(['codProj','contato','dados_adicionais_nf','enviar_pix','numero_contrato','numero_pedido_cliente','utilizar_emails'] as $key)if(!array_key_exists($key,$b['payload']['informacoes_adicionais'])&&array_key_exists($key,$originalInfo))$b['payload']['informacoes_adicionais'][$key]=$originalInfo[$key];
  $originalItemsByProduct=[];
  foreach((array)($raw['det']??[]) as $originalItem){
   $productCode=(string)($originalItem['produto']['codigo_produto']??'');
   if($productCode!=='')$originalItemsByProduct[$productCode][]=$originalItem;
  }
  foreach($b['payload']['det'] as &$item){
   $productCode=(string)($item['produto']['codigo_produto']??'');
   $originalItem=(array)(isset($originalItemsByProduct[$productCode])?array_shift($originalItemsByProduct[$productCode]):[]);
   $originalItemCode=(string)($originalItem['ide']['codigo_item_integracao']??'');
   if($originalItemCode!=='')$item['ide']['codigo_item_integracao']=$originalItemCode;
   if(isset($originalItem['observacao']))$item['observacao']=$originalItem['observacao'];
   if(isset($originalItem['inf_adic'])&&is_array($originalItem['inf_adic']))$item['inf_adic']=array_replace($originalItem['inf_adic'],$item['inf_adic']);
  }
  unset($item);

  $omie=new OmieClient();
  $response=$omie->call('orders','AlterarPedidoVenda',$b['payload']);
  $verify=null;
  try{$verify=$omie->call('orders','ConsultarPedido',['codigo_pedido'=>(int)$omieCode]);}catch(Throwable){}
  $number=(string)($response['numero_pedido']??$original['number']??'');
  $listed=self::persistSentOrder($omieCode,$number,$b['payload'],(array)($verify??[]),(string)$b['client']['omie_code'],$b['seller'],$b['total']);
  if(!$listed)throw new RuntimeException('A Omie confirmou a alteração, mas o CRM não conseguiu atualizar a cópia local. Sincronize os pedidos para concluir.');
  DB::exec("UPDATE orders SET order_date=? WHERE omie_code=?",[$original['order_date'],$omieCode]);
  self::rebuildClientMetric((string)$b['client']['omie_code']);
  $oldClient=(string)($original['client_omie_code']??'');
  if($oldClient!==''&&$oldClient!==(string)$b['client']['omie_code'])self::rebuildClientMetric($oldClient);
  return ['code'=>$omieCode,'number'=>$number,'total'=>$b['total'],'listed'=>true,'response'=>$response];
 }
}

final class GoalService {
 private static bool $virtualGoalTableReady=false;
 private static function ensureVirtualGoalTable(): void{
  if(self::$virtualGoalTableReady)return;
  DB::conn()->exec(DB::sql("CREATE TABLE IF NOT EXISTS virtual_seller_goals(
   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   seller_omie_code VARCHAR(80) NOT NULL,
   month_ref CHAR(7) NOT NULL,
   sales_goal DECIMAL(15,2) NOT NULL DEFAULT 0,
   updated_by INT UNSIGNED NULL,
   updated_at DATETIME NOT NULL,
   UNIQUE KEY uq_virtual_seller_goal(seller_omie_code,month_ref),
   INDEX idx_virtual_goal_month(month_ref)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"));
  self::$virtualGoalTableReady=true;
 }
 public static function isVirtualSellerName(string $name): bool{
  $n=mb_strtoupper(trim($name));
  return $n==='EAD RECICLAGEM'||$n==='SUPORTE - PET CURSOS'||$n==='SUPORTE PET CURSOS';
 }

 private static function selectedDaysSql(string $column,array $days): array{
  if(!$days)return ['',[]];
  return [' AND DAY('.$column.') IN ('.implode(',',array_fill(0,count($days),'?')).')',array_values($days)];
 }
 private static function sellerProduction(string $sellerCode,string $start,string $next,array $days=[]): array{
  [$validOrders,$validOrderParams]=OrderPolicy::validReportSql();
  [$orderDaysSql,$orderDaysParams]=self::selectedDaysSql('order_date',$days);
  $orderTotalSql=OrderPolicy::metricTotalSql();
  $orderWithoutFreightSql=OrderPolicy::metricWithoutFreightSql();
  $params=array_merge([$sellerCode,$start,$next],$validOrderParams,$orderDaysParams);
  $orders=(float)(DB::scalar(
   "SELECT COALESCE(SUM(".$orderTotalSql."),0) FROM orders
    WHERE seller_omie_code=? AND order_date>=? AND order_date<?
      AND ".$validOrders.$orderDaysSql,$params
  )??0);
  $ordersWithoutFreight=(float)(DB::scalar(
   "SELECT COALESCE(SUM(".$orderWithoutFreightSql."),0) FROM orders
    WHERE seller_omie_code=? AND order_date>=? AND order_date<?
      AND ".$validOrders.$orderDaysSql,$params
  )??0);
  return ['orders'=>$orders,'orders_without_freight'=>$ordersWithoutFreight,'services'=>0.0,'total'=>$orders];
 }

 public static function userMonth(int $userId,string $month,array $days=[]): array{
  if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');
  $u=DB::one("SELECT * FROM users WHERE id=?",[$userId]);if(!$u)return [];
  $g=DB::one("SELECT * FROM goals WHERE user_id=? AND month_ref=?",[$userId,$month])?:[
   'month_ref'=>$month,'sales_goal'=>0,'collection_goal'=>0,'contact_goal'=>0
  ];
  $daysInMonth=(int)date('t',strtotime($month.'-01'));
  $days=array_values(array_unique(array_filter(array_map('intval',$days),static fn($value)=>$value>=1&&$value<=$daysInMonth)));sort($days);
  $start=$month.'-01';$next=date('Y-m-d',strtotime($start.' +1 month'));
  $goalFactor=$days?count($days)/$daysInMonth:1;
  if($days){$g['sales_goal']=(float)$g['sales_goal']*$goalFactor;$g['collection_goal']=(float)$g['collection_goal']*$goalFactor;$g['contact_goal']=(float)$g['contact_goal']*$goalFactor;}
  $sales=0.0;$recovered=0.0;$contacts=0;$ordersSales=0.0;$ordersWithoutFreight=0.0;$servicesSales=0.0;

  if($u['role']==='seller'&&!empty($u['seller_omie_code'])){
   $prod=self::sellerProduction((string)$u['seller_omie_code'],$start,$next,$days);
   $ordersSales=$prod['orders'];$ordersWithoutFreight=$prod['orders_without_freight'];$servicesSales=$prod['services'];$sales=$prod['total'];
   [$activityDaysSql,$activityDaysParams]=self::selectedDaysSql('created_at',$days);
   $contacts=(int)(DB::scalar(
    "SELECT COUNT(*) FROM activities WHERE user_id=? AND created_at>=? AND created_at<?".$activityDaysSql,
    array_merge([$userId,$start,$next],$activityDaysParams)
   )??0);
  }

  if($u['role']==='collector'){
   [$collectionDaysSql,$collectionDaysParams]=self::selectedDaysSql('created_at',$days);
   $summary=DB::one(
    "SELECT
      COALESCE(SUM(CASE WHEN result='payment' THEN amount ELSE 0 END),0) recovered,
      COUNT(*) contacts
     FROM collection_actions
     WHERE assigned_user_id=? AND created_at>=? AND created_at<?".$collectionDaysSql,
    array_merge([$userId,$start,$next],$collectionDaysParams)
   )?:[];
   $recovered=(float)($summary['recovered']??0);
   $contacts=(int)($summary['contacts']??0);
  }

  return [
   'user'=>$u,'goal'=>$g,'month'=>$month,'days'=>$days,'goal_scope'=>$days?'Meta proporcional aos dias':'Meta mensal','sales'=>$sales,'orders_sales'=>$ordersSales,'orders_without_freight'=>$ordersWithoutFreight,'services_sales'=>$servicesSales,
   'recovered'=>$recovered,'contacts'=>$contacts,
   'sales_percent'=>(float)$g['sales_goal']>0?min(999,$sales/(float)$g['sales_goal']*100):0,
   'collection_percent'=>(float)$g['collection_goal']>0?min(999,$recovered/(float)$g['collection_goal']*100):0,
   'contact_percent'=>(float)$g['contact_goal']>0?min(999,$contacts/(float)$g['contact_goal']*100):0
  ];
 }

 public static function managementMonth(string $month,array $days=[]): array{
  if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');
  $daysInMonth=(int)date('t',strtotime($month.'-01'));
  $days=array_values(array_unique(array_filter(array_map('intval',$days),static fn($value)=>$value>=1&&$value<=$daysInMonth)));sort($days);
  $start=$month.'-01';$next=date('Y-m-d',strtotime($start.' +1 month'));
  $goalFactor=$days?count($days)/$daysInMonth:1;

  $users=DB::all("SELECT * FROM users WHERE active=1 AND role IN('seller','collector') ORDER BY role,name");
  $goalsRaw=DB::all("SELECT * FROM goals WHERE month_ref=?",[$month]);
  $goals=[];
  foreach($goalsRaw as $g)$goals[(int)$g['user_id']]=$g;

  self::ensureVirtualGoalTable();
  $virtualGoalsRaw=DB::all("SELECT * FROM virtual_seller_goals WHERE month_ref=?",[$month]);
  $virtualGoals=[];
  foreach($virtualGoalsRaw as $g)$virtualGoals[(string)$g['seller_omie_code']]=$g;

  $ordersMap=[];$ordersWithoutFreightMap=[];
  [$validOrders,$validOrderParams]=OrderPolicy::validReportSql();
  [$orderDaysSql,$orderDaysParams]=self::selectedDaysSql('order_date',$days);
  $orderTotalSql=OrderPolicy::metricTotalSql();
  $orderWithoutFreightSql=OrderPolicy::metricWithoutFreightSql();
  foreach(DB::all(
   "SELECT seller_omie_code,COALESCE(SUM(".$orderTotalSql."),0) total,COALESCE(SUM(".$orderWithoutFreightSql."),0) total_without_freight
    FROM orders
    WHERE order_date>=? AND order_date<?
      AND ".$validOrders."
      AND seller_omie_code IS NOT NULL AND seller_omie_code<>''".$orderDaysSql."
    GROUP BY seller_omie_code",array_merge([$start,$next],$validOrderParams,$orderDaysParams)
  ) as $r){$code=(string)$r['seller_omie_code'];$ordersMap[$code]=(float)$r['total'];$ordersWithoutFreightMap[$code]=(float)$r['total_without_freight'];}

  $servicesMap=[];

  $activityMap=[];
  [$activityDaysSql,$activityDaysParams]=self::selectedDaysSql('created_at',$days);
  foreach(DB::all(
   "SELECT user_id,COUNT(*) total
    FROM activities
    WHERE created_at>=? AND created_at<?".$activityDaysSql."
    GROUP BY user_id",array_merge([$start,$next],$activityDaysParams)
  ) as $r)$activityMap[(int)$r['user_id']]=(int)$r['total'];

  $collectionMap=[];
  [$collectionDaysSql,$collectionDaysParams]=self::selectedDaysSql('created_at',$days);
  foreach(DB::all(
   "SELECT assigned_user_id,
           COALESCE(SUM(CASE WHEN result='payment' THEN amount ELSE 0 END),0) recovered,
           COUNT(*) contacts
    FROM collection_actions
    WHERE created_at>=? AND created_at<?".$collectionDaysSql."
    GROUP BY assigned_user_id",array_merge([$start,$next],$collectionDaysParams)
  ) as $r)$collectionMap[(int)$r['assigned_user_id']]=[
   'recovered'=>(float)$r['recovered'],'contacts'=>(int)$r['contacts']
  ];

  $rows=[];$sellerRows=[];$collectorRows=[];
  $recovered=0.0;$contacts=0;$salesGoals=0.0;$collectionGoals=0.0;$contactGoals=0;

  foreach($users as $u){
   $uid=(int)$u['id'];
   $g=$goals[$uid]??[
    'month_ref'=>$month,'sales_goal'=>0,'collection_goal'=>0,'contact_goal'=>0
   ];
   if($days){$g['sales_goal']=(float)$g['sales_goal']*$goalFactor;$g['collection_goal']=(float)$g['collection_goal']*$goalFactor;$g['contact_goal']=(float)$g['contact_goal']*$goalFactor;}
   $sales=0.0;$ordersSales=0.0;$ordersWithoutFreight=0.0;$servicesSales=0.0;$userRecovered=0.0;$userContacts=0;

   if($u['role']==='seller'){
    $code=(string)($u['seller_omie_code']??'');
    $ordersSales=$code!==''?($ordersMap[$code]??0.0):0.0;
    $ordersWithoutFreight=$code!==''?($ordersWithoutFreightMap[$code]??0.0):0.0;
    $servicesSales=0.0;
    $sales=$ordersSales;
    $userContacts=$activityMap[$uid]??0;
    $salesGoals+=(float)($g['sales_goal']??0);
   }else{
    $userRecovered=(float)($collectionMap[$uid]['recovered']??0);
    $userContacts=(int)($collectionMap[$uid]['contacts']??0);
    $collectionGoals+=(float)($g['collection_goal']??0);
    $recovered+=$userRecovered;
   }

   $contacts+=$userContacts;
   $contactGoals+=(float)($g['contact_goal']??0);

   $row=[
    'user'=>$u,'goal'=>$g,'sales'=>$sales,'orders_sales'=>$ordersSales,'orders_without_freight'=>$ordersWithoutFreight,'services_sales'=>$servicesSales,
    'recovered'=>$userRecovered,'contacts'=>$userContacts,
    'sales_percent'=>(float)($g['sales_goal']??0)>0?min(999,$sales/(float)$g['sales_goal']*100):0,
    'collection_percent'=>(float)($g['collection_goal']??0)>0?min(999,$userRecovered/(float)$g['collection_goal']*100):0,
    'contact_percent'=>(float)($g['contact_goal']??0)>0?min(999,$userContacts/(float)$g['contact_goal']*100):0
   ];
   $rows[]=$row;
   if($u['role']==='seller')$sellerRows[]=$row;else $collectorRows[]=$row;
  }

  $allSellers=DB::all("SELECT omie_code,name,active FROM sellers WHERE active=1 ORDER BY name");
  $sales=0.0;$orderSales=0.0;$orderSalesWithoutFreight=0.0;$serviceSales=0.0;$virtualRows=[];
  foreach($allSellers as $seller){
   $code=(string)$seller['omie_code'];
   $o=(float)($ordersMap[$code]??0);
   $oWithoutFreight=(float)($ordersWithoutFreightMap[$code]??0);
   $sv=0.0;
   $tot=$o;
   $sales+=$tot;$orderSales+=$o;$orderSalesWithoutFreight+=$oWithoutFreight;
   if(self::isVirtualSellerName((string)$seller['name'])){
    $vg=$virtualGoals[$code]??['month_ref'=>$month,'sales_goal'=>0];
    if($days)$vg['sales_goal']=(float)$vg['sales_goal']*$goalFactor;
    $goalValue=(float)($vg['sales_goal']??0);
    $salesGoals+=$goalValue;
    $virtualRows[]=[
     'seller'=>$seller,
     'goal'=>$vg,
     'orders'=>$o,
     'orders_without_freight'=>$oWithoutFreight,
     'services'=>$sv,
     'sales'=>$tot,
     'sales_percent'=>$goalValue>0?min(999,$tot/$goalValue*100):0,
     'virtual'=>true,
     'ead_reciclagem'=>mb_strtoupper(trim((string)$seller['name']))==='EAD RECICLAGEM',
     'pet_cursos'=>in_array(mb_strtoupper(trim((string)$seller['name'])),['SUPORTE - PET CURSOS','SUPORTE PET CURSOS'],true)
    ];
   }
  }

  usort($sellerRows,fn($a,$b)=>$b['sales']<=>$a['sales']);
  usort($collectorRows,fn($a,$b)=>$b['recovered']<=>$a['recovered']);
  usort($virtualRows,fn($a,$b)=>$b['sales']<=>$a['sales']);

  $raw=DB::scalar("SELECT value_json FROM settings WHERE setting_key=?",['general_goal_'.$month]);
  $general=$raw?json_decode((string)$raw,true):[];
  if(!is_array($general))$general=[];
  $general+=['sales_goal'=>0,'collection_goal'=>0,'contact_goal'=>0];

  $effectiveSalesGoal=(float)$general['sales_goal']>0?(float)$general['sales_goal']*$goalFactor:$salesGoals;
  $effectiveCollectionGoal=(float)$general['collection_goal']>0?(float)$general['collection_goal']*$goalFactor:$collectionGoals;
  $effectiveContactGoal=(float)$general['contact_goal']>0?(float)$general['contact_goal']*$goalFactor:$contactGoals;

  return [
   'month'=>$month,'days'=>$days,'goal_scope'=>$days?'Meta proporcional aos dias':'Meta mensal','rows'=>$rows,'sellers'=>$sellerRows,'collectors'=>$collectorRows,'virtual_sellers'=>$virtualRows,
   'general_goal'=>$general,'sales'=>$sales,'order_sales'=>$orderSales,'order_sales_without_freight'=>$orderSalesWithoutFreight,'service_sales'=>$serviceSales,
   'recovered'=>$recovered,'contacts'=>$contacts,'sales_goal_sum'=>$salesGoals,
   'collection_goal_sum'=>$collectionGoals,'contact_goal_sum'=>$contactGoals,
   'effective_sales_goal'=>$effectiveSalesGoal,'effective_collection_goal'=>$effectiveCollectionGoal,
   'effective_contact_goal'=>$effectiveContactGoal,
   'sales_percent'=>$effectiveSalesGoal>0?min(999,$sales/$effectiveSalesGoal*100):0,
   'collection_percent'=>$effectiveCollectionGoal>0?min(999,$recovered/$effectiveCollectionGoal*100):0,
   'contact_percent'=>$effectiveContactGoal>0?min(999,$contacts/$effectiveContactGoal*100):0
  ];
 }

 public static function save(int $userId,string $month,array $i,int $actor): void{
  if(!preg_match('/^\d{4}-\d{2}$/',$month))throw new RuntimeException('Mês inválido.');
  $u=DB::one("SELECT id,role FROM users WHERE id=?",[$userId]);if(!$u)throw new RuntimeException('Usuário inválido.');
  $sales=max(0,(float)str_replace(',','.',(string)($i['sales_goal']??0)));
  $collection=max(0,(float)str_replace(',','.',(string)($i['collection_goal']??0)));
  if($u['role']==='seller')$collection=0;
  if($u['role']==='collector')$sales=0;
  DB::exec("INSERT INTO goals(user_id,month_ref,sales_goal,collection_goal,contact_goal,updated_by,updated_at)
            VALUES(?,?,?,?,?,?,NOW())
            ON DUPLICATE KEY UPDATE sales_goal=VALUES(sales_goal),collection_goal=VALUES(collection_goal),
            contact_goal=VALUES(contact_goal),updated_by=VALUES(updated_by),updated_at=NOW()",
   [$userId,$month,$sales,$collection,max(0,(int)($i['contact_goal']??0)),$actor]);
 }

 public static function saveVirtual(string $sellerCode,string $month,array $i,int $actor): void{
  self::ensureVirtualGoalTable();
  if(!preg_match('/^\d{4}-\d{2}$/',$month))throw new RuntimeException('Mês inválido.');
  $seller=DB::one("SELECT omie_code,name,active FROM sellers WHERE omie_code=? AND active=1",[$sellerCode]);
  if(!$seller||!self::isVirtualSellerName((string)$seller['name']))throw new RuntimeException('Vendedor virtual inválido.');
  $sales=max(0,(float)str_replace(',','.',(string)($i['sales_goal']??0)));
  DB::exec("INSERT INTO virtual_seller_goals(seller_omie_code,month_ref,sales_goal,updated_by,updated_at)
            VALUES(?,?,?,?,NOW())
            ON DUPLICATE KEY UPDATE sales_goal=VALUES(sales_goal),updated_by=VALUES(updated_by),updated_at=NOW()",
   [$sellerCode,$month,$sales,$actor]);
 }

 public static function saveGeneral(string $month,array $i): void{
  if(!preg_match('/^\d{4}-\d{2}$/',$month))throw new RuntimeException('Mês inválido.');
  $g=[
   'sales_goal'=>max(0,(float)str_replace(',','.',(string)($i['sales_goal']??0))),
   'collection_goal'=>max(0,(float)str_replace(',','.',(string)($i['collection_goal']??0))),
   'contact_goal'=>max(0,(int)($i['contact_goal']??0))
  ];
  DB::exec("INSERT INTO settings(setting_key,value_json,updated_at) VALUES(?,?,NOW())
            ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=NOW()",
   ['general_goal_'.$month,json_encode($g,JSON_UNESCAPED_UNICODE)]);
 }
}

final class TestDataService {
 public static function importClient(string $omieCode): array{
  $code=trim($omieCode);if($code===''||!ctype_digit($code))throw new RuntimeException('Informe o código numérico do cliente na Omie.');
  $o=new OmieClient();
  $r=$o->call('clients','ConsultarCliente',['codigo_cliente_omie'=>(int)$code]);
  $client=$r['clientes_cadastro'][0]??$r;
  $omie=(string)($client['codigo_cliente_omie']??$client['codigo_cliente']??$code);
  $name=(string)($client['nome_fantasia']??$client['razao_social']??$omie);
  $phone=trim((string)($client['telefone1_ddd']??'').' '.(string)($client['telefone1_numero']??''));
  DB::exec("INSERT INTO clients(omie_code,name,legal_name,document,email,phone,city,uf,seller_omie_code,active,raw_json,updated_at)
            VALUES(?,?,?,?,?,?,?,?,?,1,?,NOW())
            ON DUPLICATE KEY UPDATE name=VALUES(name),legal_name=VALUES(legal_name),document=VALUES(document),email=VALUES(email),
            phone=VALUES(phone),city=VALUES(city),uf=VALUES(uf),seller_omie_code=VALUES(seller_omie_code),active=1,raw_json=VALUES(raw_json),updated_at=NOW()",
   [$omie,$name,$client['razao_social']??null,$client['cnpj_cpf']??null,$client['email']??null,$phone,$client['cidade']??null,$client['estado']??null,$client['codigo_vendedor']??null,json_encode($client,JSON_UNESCAPED_UNICODE)]);
  return DB::one("SELECT * FROM clients WHERE omie_code=?",[$omie])??[];
 }

 public static function importProduct(string $omieCode): array{
  $code=trim($omieCode);if($code===''||!ctype_digit($code))throw new RuntimeException('Informe o código numérico do produto na Omie.');
  $o=new OmieClient();
  $r=$o->call('products','ConsultarProduto',['codigo_produto'=>(int)$code]);
  $product=$r['produto_servico_cadastro'][0]??$r;
  $omie=(string)($product['codigo_produto']??$code);
  DB::exec("INSERT INTO products(omie_code,sku,description,unit,ncm,unit_price,stock_qty,active,raw_json,updated_at)
            VALUES(?,?,?,?,?,?,?,?,?,NOW())
            ON DUPLICATE KEY UPDATE sku=VALUES(sku),description=VALUES(description),unit=VALUES(unit),ncm=VALUES(ncm),
            unit_price=VALUES(unit_price),stock_qty=VALUES(stock_qty),active=VALUES(active),raw_json=VALUES(raw_json),updated_at=NOW()",
   [$omie,$product['codigo']??null,(string)($product['descricao']??$omie),$product['unidade']??null,$product['ncm']??null,(float)($product['valor_unitario']??0),
    isset($product['quantidade_estoque'])?(float)$product['quantidade_estoque']:null,(($product['inativo']??'N')==='S'?0:1),json_encode($product,JSON_UNESCAPED_UNICODE)]);
  return DB::one("SELECT * FROM products WHERE omie_code=?",[$omie])??[];
 }

 public static function importMinimal(array $input): array{
  $client=self::importClient((string)($input['client_omie_code']??''));
  $products=[];
  foreach(['product_1_omie_code','product_2_omie_code'] as $key){
   $v=trim((string)($input[$key]??''));if($v!=='')$products[]=self::importProduct($v);
  }
  if(count($products)<1)throw new RuntimeException('Informe pelo menos um produto para teste.');
  return ['client'=>$client,'products'=>$products];
 }

 public static function prepareReferences(): array{
  $modules=['sellers','categories','departments','accounts','stages','payment_terms','tax_scenarios','stock_locations','payment_methods','document_types'];
  $result=[];
  foreach($modules as $module){
   $page=1;$processed=0;
   do{
    $r=SyncService::run($module,$page);$processed+=(int)($r['count']??0);$page++;
   }while(empty($r['done'])&&$page<=50);
   $result[$module]=$processed;
  }
  return $result;
 }

 public static function snapshot(): array{
  return [
   'clients'=>(int)(DB::scalar("SELECT COUNT(*) FROM clients")??0),
   'products'=>(int)(DB::scalar("SELECT COUNT(*) FROM products")??0),
   'sellers'=>(int)(DB::scalar("SELECT COUNT(*) FROM sellers WHERE active=1")??0),
   'categories'=>(int)(DB::scalar("SELECT COUNT(*) FROM categories WHERE active=1")??0),
   'departments'=>(int)(DB::scalar("SELECT COUNT(*) FROM departments WHERE active=1")??0),
   'accounts'=>(int)(DB::scalar("SELECT COUNT(*) FROM financial_accounts WHERE active=1")??0),
   'stages'=>(int)(DB::scalar("SELECT COUNT(*) FROM order_stages WHERE active=1")??0),
   'terms'=>(int)(DB::scalar("SELECT COUNT(*) FROM payment_terms WHERE active=1 AND code<>'999'")??0),
  ];
 }
}

final class SyncService {
 public static function modules(): array{return ['sellers'=>'Vendedores','clients'=>'Clientes','products'=>'Produtos','categories'=>'Categorias','departments'=>'Departamentos','accounts'=>'Contas correntes','stages'=>'Etapas','payment_terms'=>'Condições','tax_scenarios'=>'Cenários fiscais','stock_locations'=>'Locais de estoque','payment_methods'=>'Meios de pagamento','document_types'=>'Tipos de documento','orders'=>'Pedidos','services'=>'Serviços','financial'=>'Financeiro'];}

 public static function tableMap(): array{
  return [
   'sellers'=>'sellers','clients'=>'clients','products'=>'products','categories'=>'categories','departments'=>'departments',
   'accounts'=>'financial_accounts','stages'=>'order_stages','payment_terms'=>'payment_terms',
   'tax_scenarios'=>'tax_scenarios','stock_locations'=>'stock_locations',
   'payment_methods'=>'payment_methods','document_types'=>'document_types',
   'orders'=>'orders','services'=>'service_orders','financial'=>'financial_movements'
  ];
 }
 public static function overview(): array{
  $states=[];foreach(DB::all("SELECT * FROM sync_state ORDER BY module_key") as $row)$states[(string)$row['module_key']]=$row;
  $tables=self::tableMap();$items=[];$errors=0;$synced=0;$totalLocal=0;$lastSuccess=null;
  foreach(self::modules() as $key=>$label){
   $state=$states[$key]??null;$table=$tables[$key]??null;$local=0;
   if($table)$local=(int)(DB::scalar("SELECT COUNT(*) FROM ".$table)??0);
   $totalLocal+=$local;
   $ctx=$state&&!empty($state['context_json'])?json_decode((string)$state['context_json'],true):null;
   if(!is_array($ctx))$ctx=[];
   $hasError=$state&&!empty($state['last_error']);if($hasError)$errors++;
   if($state&&!empty($state['last_success_at'])){$synced++;if($lastSuccess===null||$state['last_success_at']>$lastSuccess)$lastSuccess=$state['last_success_at'];}
   $lastPage=(int)($state['last_page']??0);$totalPages=(int)($state['total_pages']??0);
   $resumable=!empty($state)&&$lastPage>0&&(!empty($state['last_error'])||$totalPages===0||$lastPage<$totalPages);
   $items[$key]=[
    'key'=>$key,'label'=>$label,'local_count'=>$local,'state'=>$state,'context'=>$ctx,
    'has_error'=>$hasError,'resumable'=>$resumable,
    'mode'=>(string)($ctx['mode']??($state&&!empty($state['last_success_at'])?'incremental':'initial')),
    'period_start'=>$ctx['start']??null,'period_end'=>$ctx['end']??null
   ];
  }
  return ['items'=>$items,'summary'=>['modules'=>count(self::modules()),'synced'=>$synced,'errors'=>$errors,'local_total'=>$totalLocal,'last_success'=>$lastSuccess]];
 }
 public static function resetState(string $module): array{
  if(!isset(self::modules()[$module]))throw new RuntimeException('Módulo inválido.');

  $tables=self::tableMap();
  $table=$tables[$module]??null;
  if(!$table)throw new RuntimeException('Tabela local do módulo não identificada.');

  // Limpa também dados derivados que dependem diretamente do módulo sincronizado.
  if($module==='financial'){
   DB::exec("DELETE FROM collection_cases");
   DB::exec("DELETE FROM financial_movements");
  }elseif($module==='orders'){
   DB::exec("DELETE FROM orders");
   DB::exec("DELETE FROM client_metrics");
  }elseif($module==='clients'){
   // As tabelas relacionadas a client_id usam ON DELETE CASCADE no schema.
   DB::exec("DELETE FROM clients");
  }else{
   DB::exec("DELETE FROM ".$table);
  }

  DB::exec("DELETE FROM sync_state WHERE module_key=?",[$module]);

  return [
   'module'=>$module,
   'action'=>'reset',
   'done'=>true,
   'message'=>'Módulo zerado: todos os dados locais desta sincronização foram excluídos. Nenhuma nova sincronização foi iniciada.'
  ];
 }
 public static function prepareCatchup(string $module): array{
  if(!in_array($module,['orders','services'],true))throw new RuntimeException('Atualização de lacuna disponível somente para Pedidos e Serviços.');
  $table=$module==='orders'?'orders':'service_orders';
  $dateColumn=$module==='orders'?'order_date':'service_date';
  $lastLocal=(string)(DB::scalar("SELECT MAX(".$dateColumn.") FROM ".$table." WHERE ".$dateColumn." IS NOT NULL")??'');
  if($lastLocal===''){
   $startIso=date('Y-01-01');
  }else{
   $startIso=date('Y-m-d',strtotime($lastLocal.' +1 day'));
  }
  $today=date('Y-m-d');
  if(strtotime($startIso)>strtotime($today)){
   $startIso=date('Y-m-d',strtotime('-4 days'));
  }
  $ctx=[
   'start'=>date('d/m/Y',strtotime($startIso)),
   'end'=>date('d/m/Y',strtotime($today)),
   'mode'=>'catchup_missing_period',
   'forced'=>true,
   'last_local_date'=>$lastLocal?:null
  ];
  DB::exec("INSERT INTO sync_state(module_key,last_page,total_pages,last_count,context_json,last_success_at,last_error)
            VALUES(?,0,0,0,?,NULL,NULL)
            ON DUPLICATE KEY UPDATE last_page=0,total_pages=0,last_count=0,context_json=VALUES(context_json),last_error=NULL",
   [$module,json_encode($ctx,JSON_UNESCAPED_UNICODE)]);
  return $ctx;
 }
 public static function prepareLastFiveDays(string $module): array{
  if(!in_array($module,['orders','services'],true))throw new RuntimeException('A busca dos últimos 5 dias está disponível somente para Pedidos e Serviços.');
  $ctx=['start'=>date('d/m/Y',strtotime('-4 days')),'end'=>date('d/m/Y'),'mode'=>'forced_last_5_days','forced'=>true];
  DB::exec("INSERT INTO sync_state(module_key,last_page,total_pages,last_count,context_json,last_success_at,last_error)
            VALUES(?,0,0,0,?,NULL,NULL)
            ON DUPLICATE KEY UPDATE last_page=0,total_pages=0,last_count=0,context_json=VALUES(context_json),last_error=NULL",
   [$module,json_encode($ctx,JSON_UNESCAPED_UNICODE)]);
  return $ctx;
 }
 public static function preparePeriod(string $module,string $from,string $to): array{
  if(!in_array($module,['orders','services'],true))throw new RuntimeException('A sincronização por período está disponível somente para Pedidos e Serviços.');
  $valid=static function(string $value): bool{
   if(!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/',$value,$match))return false;
   return checkdate((int)$match[2],(int)$match[3],(int)$match[1]);
  };
  if(!$valid($from)||!$valid($to))throw new RuntimeException('Informe uma data inicial e uma data final válidas.');
  if($from>$to)throw new RuntimeException('A data inicial não pode ser posterior à data final.');
  $days=(int)floor((strtotime($to)-strtotime($from))/86400)+1;
  if($days>366)throw new RuntimeException('Selecione um período de até 366 dias por sincronização.');
  $ctx=['start'=>date('d/m/Y',strtotime($from)),'end'=>date('d/m/Y',strtotime($to)),'mode'=>'manual_period','forced'=>true,'days'=>$days];
  DB::exec("INSERT INTO sync_state(module_key,last_page,total_pages,last_count,context_json,last_success_at,last_error)
            VALUES(?,0,0,0,?,NULL,NULL)
            ON DUPLICATE KEY UPDATE last_page=0,total_pages=0,last_count=0,context_json=VALUES(context_json),last_error=NULL",
   [$module,json_encode($ctx,JSON_UNESCAPED_UNICODE)]);
  return $ctx;
 }
 public static function prepareFull(string $module): array{
  if(!in_array($module,['orders','services'],true))throw new RuntimeException('Carga completa manual disponível somente para Pedidos e Serviços.');
  $ctx=['start'=>date('01/01/Y'),'end'=>date('d/m/Y'),'mode'=>'manual_full_current_year','forced'=>true];
  DB::exec("INSERT INTO sync_state(module_key,last_page,total_pages,last_count,context_json,last_success_at,last_error)
            VALUES(?,0,0,0,?,NULL,NULL)
            ON DUPLICATE KEY UPDATE last_page=0,total_pages=0,last_count=0,context_json=VALUES(context_json),last_error=NULL",
   [$module,json_encode($ctx,JSON_UNESCAPED_UNICODE)]);
  return $ctx;
 }
 public static function resumePage(string $module): int{
  if(!isset(self::modules()[$module]))throw new RuntimeException('Módulo inválido.');
  $state=DB::one("SELECT * FROM sync_state WHERE module_key=?",[$module]);
  if(!$state)throw new RuntimeException('Não existe sincronização anterior para retomar.');
  $last=max(0,(int)($state['last_page']??0));$total=max(0,(int)($state['total_pages']??0));
  if($total>0&&$last>=$total)throw new RuntimeException('A última sincronização deste módulo já foi concluída.');
  return $last+1;
 }
 public static function recordError(string $module,string $message): void{
  if(!isset(self::modules()[$module]))return;
  DB::exec("INSERT INTO sync_state(module_key,last_page,total_pages,last_count,context_json,last_success_at,last_error)
            VALUES(?,0,0,0,NULL,NULL,?)
            ON DUPLICATE KEY UPDATE last_error=VALUES(last_error)",[$module,mb_substr($message,0,1000)]);
 }
 public static function repairStoredServices(): int{
  $rows=DB::all("SELECT id,raw_json FROM service_orders WHERE raw_json IS NOT NULL");
  $updated=0;
  foreach($rows as $row){
   $r=json_decode((string)$row['raw_json'],true);
   if(!is_array($r))continue;
   $n=self::normalizeServiceRecord($r);
   if(!$n||$n['code']==='')continue;
   DB::exec("UPDATE service_orders SET client_omie_code=?,seller_omie_code=?,service_date=?,total=?,status=?,updated_at=NOW() WHERE id=?",
    [$n['client'],$n['seller']!==''?$n['seller']:null,$n['date'],$n['total'],$n['status'],(int)$row['id']]);
   $updated++;
  }
  return $updated;
 }
 private static function normalizeServiceRecord(array $r): ?array{
  $cab=(array)($r['Cabecalho']??$r['cabecalho']??[]);
  $add=(array)($r['InformacoesAdicionais']??$r['informacoesAdicionais']??$r['informacoes_adicionais']??[]);
  $info=(array)($r['InfoCadastro']??$r['infoCadastro']??[]);
  $services=(array)($r['ServicosPrestados']??$r['servicosPrestados']??[]);

  $code=(string)($cab['nCodOS']??$r['nCodOS']??$cab['codigo_os']??'');
  if($code==='')return null;
  $client=(string)($cab['nCodCli']??$r['nCodCli']??'');
  $seller=(string)($cab['nCodVend']??$cab['nCodVendedor']??$add['nCodVend']??$add['nCodVendedor']??$info['nCodVend']??$info['nCodVendedor']??$r['nCodVend']??'');

  $dateRaw=(string)($cab['dDtPrevisao']??$info['dDtFat']??$info['dDtInc']??$r['dDtPrevisao']??'');
  $serviceDate=$dateRaw!==''?date('Y-m-d',strtotime(str_replace('/','-',$dateRaw))):null;

  $total=(float)($cab['nValorTotal']??$cab['nValorTot']??$info['nValorTot']??$info['nValorTotal']??$r['nValorTot']??0);
  if($total<=0&&$services){
   foreach($services as $srv){
    if(!is_array($srv))continue;
    $qty=(float)($srv['nQtde']??1);$unit=(float)($srv['nValUnit']??$srv['nValorUnitario']??0);
    $total+=($qty>0?$qty:1)*$unit;
   }
  }

  $cancelled=strtoupper((string)($info['cCancelada']??$cab['cCancelada']??'N'))==='S';
  $billed=strtoupper((string)($info['cFaturada']??$cab['cFaturada']??'N'))==='S';
  $status=(string)($cab['cStatus']??$info['cStatus']??$r['cStatus']??'');
  if($cancelled)$status='CANCELADO';elseif($billed)$status='FATURADO';elseif($status==='')$status='ATIVO';

  $number=(string)($cab['cNumOS']??$cab['nNumOS']??$r['cNumOS']??$r['nNumOS']??'');
  return ['code'=>$code,'number'=>$number,'client'=>$client,'seller'=>$seller,'date'=>$serviceDate,'total'=>$total,'status'=>$status];
 }
 private static function pick(array $d,array $keys): array{foreach($keys as $k)if(isset($d[$k])&&is_array($d[$k]))return $d[$k];return [];}
 private static function purgeOldYearData(string $module): void{
  $year=(int)date('Y');
  if($module==='orders'){
   DB::exec("DELETE FROM orders WHERE order_date IS NOT NULL AND YEAR(order_date)<>?",[$year]);
  }elseif($module==='services'){
   DB::exec("DELETE FROM service_orders WHERE service_date IS NOT NULL AND YEAR(service_date)<>?",[$year]);
  }
 }
 private static function syncWindow(string $module,int $page): array{
  $state=DB::one("SELECT * FROM sync_state WHERE module_key=?",[$module]);
  $ctx=$state&&!empty($state['context_json'])?json_decode((string)$state['context_json'],true):null;
  $hasInitialLoad=$state&&!empty($state['last_success_at']);

  $forced=is_array($ctx)&&!empty($ctx['forced'])&&!empty($ctx['start'])&&!empty($ctx['end']);
  if((($page===1)&&!$forced)||!is_array($ctx)||empty($ctx['start'])||empty($ctx['end'])){
   if($hasInitialLoad){
    // Incremental inteligente: se a base local ficou atrasada, recupera todo o intervalo faltante.
    // Só usa a janela móvel de 5 dias quando a última data local já está recente.
    $dateColumn=$module==='orders'?'order_date':'service_date';
    $table=$module==='orders'?'orders':'service_orders';
    $lastLocal=(string)(DB::scalar("SELECT MAX(".$dateColumn.") FROM ".$table." WHERE ".$dateColumn." IS NOT NULL")??'');
    $rollingStart=date('Y-m-d',strtotime('-4 days'));
    if($lastLocal!==''&&strtotime($lastLocal)<strtotime($rollingStart)){
     $startIso=date('Y-m-d',strtotime($lastLocal.' +1 day'));
     $start=date('d/m/Y',strtotime($startIso));
     $end=date('d/m/Y');
     $ctx=['start'=>$start,'end'=>$end,'mode'=>'catchup_missing_period','last_local_date'=>$lastLocal];
    }else{
     $start=date('d/m/Y',strtotime('-4 days'));
     $end=date('d/m/Y');
     $ctx=['start'=>$start,'end'=>$end,'mode'=>'incremental_5_days'];
    }
   }else{
    // Primeira carga: mantém a carga histórica do ano corrente.
    self::purgeOldYearData($module);
    $start=date('01/01/Y');
    $end=date('d/m/Y');
    $ctx=['start'=>$start,'end'=>$end,'mode'=>'initial_current_year'];
   }

   DB::exec("INSERT INTO sync_state(module_key,last_page,total_pages,last_count,context_json,last_success_at,last_error)
             VALUES(?,0,0,0,?,NULL,NULL)
             ON DUPLICATE KEY UPDATE last_page=0,total_pages=0,last_count=0,context_json=VALUES(context_json),last_error=NULL",
    [$module,json_encode($ctx,JSON_UNESCAPED_UNICODE)]);
  }
  return $ctx;
 }
 private static function finishWindow(string $module,array $data,int $page,int $count,array $period): array{
  $total=max(1,(int)($data['total_de_paginas']??$data['nTotPaginas']??1));
  $done=$page>=$total;
  DB::exec("INSERT INTO sync_state(module_key,last_page,total_pages,last_count,context_json,last_success_at,last_error)
            VALUES(?,?,?,?,?,IF(?,NOW(),NULL),NULL)
            ON DUPLICATE KEY UPDATE last_page=VALUES(last_page),total_pages=VALUES(total_pages),last_count=VALUES(last_count),
            context_json=VALUES(context_json),last_success_at=IF(VALUES(last_success_at) IS NULL,last_success_at,VALUES(last_success_at)),last_error=NULL",
   [$module,$page,$total,$count,$done?null:json_encode($period,JSON_UNESCAPED_UNICODE),$done?1:0]);
  if($module==='orders'&&$done)self::rebuildMetrics();
  return ['module'=>$module,'page'=>$page,'total_pages'=>$total,'count'=>$count,'done'=>$done,'period'=>$period];
 }
 private static function finish(string $m,array $d,int $page,int $count): array{$total=(int)($d['total_de_paginas']??$d['nTotPaginas']??1);$total=max(1,$total);DB::exec("INSERT INTO sync_state(module_key,last_page,total_pages,last_count,context_json,last_success_at,last_error) VALUES(?,?,?,?,NULL,NOW(),NULL) ON DUPLICATE KEY UPDATE last_page=VALUES(last_page),total_pages=VALUES(total_pages),last_count=VALUES(last_count),context_json=NULL,last_success_at=NOW(),last_error=NULL",[$m,$page,$total,$count]);if($m==='orders'&&$page>=$total)self::rebuildMetrics();return ['module'=>$m,'page'=>$page,'total_pages'=>$total,'count'=>$count,'done'=>$page>=$total];}
 public static function run(string $m,int $page=1): array{
  if(!isset(self::modules()[$m]))throw new RuntimeException('Módulo inválido.');$o=new OmieClient();$page=max(1,$page);
  if($m==='sellers'){$d=$o->call('sellers','ListarVendedores',['pagina'=>$page,'registros_por_pagina'=>100,'apenas_importado_api'=>'N']);$it=self::pick($d,['cadastro','vendedores']);foreach($it as $r){$c=(string)($r['codigo']??'');if($c==='')continue;DB::exec("INSERT INTO sellers(omie_code,name,email,active,raw_json,updated_at) VALUES(?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),email=VALUES(email),active=VALUES(active),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['nome']??$c),$r['email']??null,(($r['inativo']??'N')==='S'?0:1),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='clients'){$d=$o->call('clients','ListarClientes',['pagina'=>$page,'registros_por_pagina'=>100,'apenas_importado_api'=>'N']);$it=self::pick($d,['clientes_cadastro']);foreach($it as $r){$c=(string)($r['codigo_cliente_omie']??'');if($c==='')continue;$phone=trim((string)($r['telefone1_ddd']??'').' '.(string)($r['telefone1_numero']??''));DB::exec("INSERT INTO clients(omie_code,name,legal_name,document,email,phone,city,uf,seller_omie_code,active,raw_json,updated_at) VALUES(?,?,?,?,?,?,?,?,?,1,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),legal_name=VALUES(legal_name),document=VALUES(document),email=VALUES(email),phone=VALUES(phone),city=VALUES(city),uf=VALUES(uf),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['nome_fantasia']??$r['razao_social']??$c),$r['razao_social']??null,$r['cnpj_cpf']??null,$r['email']??null,$phone,$r['cidade']??null,$r['estado']??null,$r['codigo_vendedor']??null,json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='products'){$d=$o->call('products','ListarProdutos',['pagina'=>$page,'registros_por_pagina'=>100,'apenas_importado_api'=>'N','filtrar_apenas_omiepdv'=>'N']);$it=self::pick($d,['produto_servico_cadastro']);foreach($it as $r){$c=(string)($r['codigo_produto']??'');if($c==='')continue;DB::exec("INSERT INTO products(omie_code,sku,description,unit,ncm,unit_price,stock_qty,active,raw_json,updated_at) VALUES(?,?,?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE sku=VALUES(sku),description=VALUES(description),unit=VALUES(unit),ncm=VALUES(ncm),unit_price=VALUES(unit_price),stock_qty=VALUES(stock_qty),active=VALUES(active),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,$r['codigo']??null,(string)($r['descricao']??$c),$r['unidade']??null,$r['ncm']??null,(float)($r['valor_unitario']??0),isset($r['quantidade_estoque'])?(float)$r['quantidade_estoque']:null,(($r['inativo']??'N')==='S'?0:1),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='categories'){$d=$o->call('categories','ListarCategorias',['pagina'=>$page,'registros_por_pagina'=>100]);$it=self::pick($d,['categoria_cadastro']);foreach($it as $r){$c=(string)($r['codigo']??'');if($c==='')continue;DB::exec("INSERT INTO categories(code,description,active,raw_json,updated_at) VALUES(?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE description=VALUES(description),active=VALUES(active),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['descricao']??$c),(($r['conta_inativa']??'N')==='S'?0:1),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='departments'){
   DB::conn()->exec(DB::sql("CREATE TABLE IF NOT EXISTS departments(code VARCHAR(80) PRIMARY KEY,description VARCHAR(255) NOT NULL,structure VARCHAR(255) NULL,active TINYINT(1) NOT NULL DEFAULT 1,raw_json JSON NULL,updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"));
   $d=$o->call('departments','ListarDepartamentos',['pagina'=>$page,'registros_por_pagina'=>100,'apenas_importado_api'=>'N']);
   $it=self::pick($d,['departamentos','cadastros','departamento_cadastro','requisicaoCadastro']);
   if(isset($d['codigo']))$it=[$d];
   foreach($it as $r){
    if(!is_array($r))continue;
    $c=(string)($r['codigo']??$r['cCodigo']??'');
    if($c==='')continue;
    $desc=(string)($r['descricao']??$r['cDescricao']??$c);
    $structure=(string)($r['estrutura']??'');
    $active=mb_strtoupper((string)($r['inativo']??'N'))==='S'?0:1;
    DB::exec("INSERT INTO departments(code,description,structure,active,raw_json,updated_at) VALUES(?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE description=VALUES(description),structure=VALUES(structure),active=VALUES(active),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,$desc,$structure,$active,json_encode($r,JSON_UNESCAPED_UNICODE)]);
   }
   return self::finish($m,$d,$page,count($it));
  }
  if($m==='accounts'){$d=$o->call('accounts','ListarContasCorrentes',['pagina'=>$page,'registros_por_pagina'=>100,'apenas_importado_api'=>'N']);$it=self::pick($d,['ListarContasCorrentes','conta_corrente_lista']);foreach($it as $r){$c=(string)($r['nCodCC']??'');if($c==='')continue;DB::exec("INSERT INTO financial_accounts(omie_code,name,account_type,active,selected,raw_json,updated_at) VALUES(?,?,?,?,0,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),account_type=VALUES(account_type),active=VALUES(active),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['descricao']??$c),$r['tipo_conta_corrente']??null,(($r['inativo']??'N')==='S'?0:1),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='stages'){
   $d=$o->call('stages','ListarEtapasFaturamento',['pagina'=>$page,'registros_por_pagina'=>100]);

   // A Omie retorna operações de faturamento e, dentro de cada operação, o array "etapas".
   // Para Pedidos de Venda usamos a operação de venda de produto. Mantemos fallback para
   // variações de nomenclatura/estrutura sem misturar etapas de serviços no cadastro.
   $groups=self::pick($d,['etapasFaturamento','cadastros','lista','operacoes']);
   if(!$groups&&isset($d['cCodOperacao']))$groups=[$d];

   $it=[];$productGroupFound=false;
   foreach($groups as $group){
    if(!is_array($group))continue;
    $operationCode=trim((string)($group['cCodOperacao']??$group['codigo_operacao']??''));
    $op=mb_strtoupper(trim((string)($group['cDescOperacao']??$group['descricao_operacao']??'')));
    $isProduct=$operationCode==='11'||($operationCode===''&&($op==='VENDA DE PRODUTO'||$op==='PEDIDO DE VENDA'));
    if(!$isProduct)continue;
    $productGroupFound=true;

    $nested=$group['etapas']??$group['Etapas']??[];
    if(is_array($nested)){
     foreach($nested as $stage){
      if(is_array($stage))$it[]=$stage+[
       '_cCodOperacao'=>$group['cCodOperacao']??null,
       '_cDescOperacao'=>$group['cDescOperacao']??null
      ];
     }
    }
   }

   // Compatibilidade com eventual retorno já plano.
   if(!$it){
    $flat=self::pick($d,['etapas']);
    foreach($flat as $stage)if(is_array($stage))$it[]=$stage;
   }

   foreach($it as $r){
    $c=(string)($r['cCodigo']??$r['codigo']??'');
    if($c==='')continue;
    $inactive=mb_strtoupper((string)($r['cInativo']??$r['inativo']??'N'))==='S';
    $name=trim((string)($r['cDescricao']??''));
    if($name==='')$name=trim((string)($r['cDescrPadrao']??$r['descricao']??''));
    if($name==='')$name=$c;
    DB::exec("INSERT INTO order_stages(code,name,active,raw_json,updated_at)
              VALUES(?,?,?,?,NOW())
              ON DUPLICATE KEY UPDATE name=VALUES(name),active=VALUES(active),raw_json=VALUES(raw_json),updated_at=NOW()",
      [$c,$name,$inactive?0:1,json_encode($r,JSON_UNESCAPED_UNICODE)]);
   }
   if($productGroupFound&&$it){
    $validCodes=array_values(array_unique(array_map(fn($stage)=>(string)($stage['cCodigo']??$stage['codigo']??''),$it)));
    $validCodes=array_values(array_filter($validCodes,fn($code)=>$code!==''));
    if($validCodes)DB::exec("DELETE FROM order_stages WHERE code NOT IN (".implode(',',array_fill(0,count($validCodes),'?')).")",$validCodes);
   }
   return self::finish($m,$d,$page,count($it));
  }
  if($m==='payment_terms'){$d=$o->call('payment_terms','ListarFormasPagVendas',['pagina'=>$page,'registros_por_pagina'=>100]);$it=self::pick($d,['cadastros']);foreach($it as $r){$c=(string)($r['nCodigo']??$r['cCodigo']??'');if($c==='')continue;DB::exec("INSERT INTO payment_terms(code,description,installments,days_list,active,raw_json,updated_at) VALUES(?,?,?,?,1,?,NOW()) ON DUPLICATE KEY UPDATE description=VALUES(description),installments=VALUES(installments),days_list=VALUES(days_list),active=1,raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['cDescricao']??$c),(int)($r['nQtdeParc']??0),(string)($r['cListaParc']??''),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='tax_scenarios'){$d=$o->call('tax_scenarios','ListarCenarios',['nPagina'=>$page,'nRegPorPagina'=>100,'cNome'=>'']);$it=self::pick($d,['cenariosEncontrados']);foreach($it as $r){$c=(string)($r['nCodigo']??'');if($c==='')continue;DB::exec("INSERT INTO tax_scenarios(omie_code,name,is_default,active,raw_json,updated_at) VALUES(?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),is_default=VALUES(is_default),active=VALUES(active),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['cNome']??$c),!empty($r['padrao'])?1:0,(($r['inativo']??'N')==='S'?0:1),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='stock_locations'){$d=$o->call('stock_locations','ListarLocaisEstoque',['nPagina'=>$page,'nRegPorPagina'=>100]);$it=self::pick($d,['locaisEncontrados']);foreach($it as $r){$c=(string)($r['codigo_local_estoque']??'');if($c==='')continue;DB::exec("INSERT INTO stock_locations(omie_code,name,sale_enabled,is_default,active,raw_json,updated_at) VALUES(?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),sale_enabled=VALUES(sale_enabled),is_default=VALUES(is_default),active=VALUES(active),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['descricao']??$c),(($r['dispVenda']??'N')==='S'?1:0),(($r['padrao']??'N')==='S'?1:0),(($r['inativo']??'N')==='S'?0:1),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,$d,$page,count($it));}
  if($m==='payment_methods'){$d=$o->call('payment_methods','ListarMeiosPagamento',['codigo'=>'']);$it=self::pick($d,['MeiosPagamentoLista']);foreach($it as $r){$c=(string)($r['codigo']??'');if($c==='')continue;DB::exec("INSERT INTO payment_methods(code,description,raw_json,updated_at) VALUES(?,?,?,NOW()) ON DUPLICATE KEY UPDATE description=VALUES(description),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['descricao']??$c),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,['total_de_paginas'=>1],1,count($it));}
  if($m==='document_types'){$d=$o->call('document_types','PesquisarTipoDocumento',['codigo'=>'']);$it=self::pick($d,['tipo_documento_cadastro']);foreach($it as $r){$c=(string)($r['codigo']??'');if($c==='')continue;DB::exec("INSERT INTO document_types(code,description,raw_json,updated_at) VALUES(?,?,?,NOW()) ON DUPLICATE KEY UPDATE description=VALUES(description),raw_json=VALUES(raw_json),updated_at=NOW()",[$c,(string)($r['descricao']??$c),json_encode($r,JSON_UNESCAPED_UNICODE)]);}return self::finish($m,['total_de_paginas'=>1],1,count($it));}
  if($m==='orders'){
   $period=self::syncWindow('orders',$page);
   $d=$o->call('orders','ListarPedidos',[
    'pagina'=>$page,'registros_por_pagina'=>100,'apenas_importado_api'=>'N',
    'filtrar_por_data_de'=>$period['start'],'filtrar_por_data_ate'=>$period['end']
   ]);
   $it=self::pick($d,['pedido_venda_produto']);
   foreach($it as $r){
    $cab=$r['cabecalho']??[];$info=$r['infoCadastro']??[];$add=$r['informacoes_adicionais']??[];$tot=$r['total_pedido']??[];
    $code=(string)($cab['codigo_pedido']??'');if($code==='')continue;
    $status=(($info['cancelado']??'N')==='S')?'CANCELADO':((($info['faturado']??'N')==='S')?'FATURADO':'ATIVO');
    DB::exec("INSERT INTO orders(omie_code,number,client_omie_code,seller_omie_code,order_date,forecast_date,total,status,stage_code,raw_json,updated_at)
              VALUES(?,?,?,?,?,?,?,?,?,?,NOW())
              ON DUPLICATE KEY UPDATE number=VALUES(number),client_omie_code=VALUES(client_omie_code),seller_omie_code=VALUES(seller_omie_code),
              order_date=VALUES(order_date),forecast_date=VALUES(forecast_date),total=VALUES(total),status=VALUES(status),stage_code=VALUES(stage_code),raw_json=VALUES(raw_json),updated_at=NOW()",
      [$code,$cab['numero_pedido']??null,(string)($cab['codigo_cliente']??''),(string)($add['codVend']??''),
       !empty($info['dInc'])?date('Y-m-d',strtotime(str_replace('/','-',(string)$info['dInc']))):null,
       !empty($cab['data_previsao'])?date('Y-m-d',strtotime(str_replace('/','-',(string)$cab['data_previsao']))):null,
       (float)($tot['valor_total_pedido']??0),$status,(string)($cab['etapa']??''),json_encode($r,JSON_UNESCAPED_UNICODE)]);
   }
   return self::finishWindow('orders',$d,$page,count($it),$period);
  }
  if($m==='services'){
   $period=self::syncWindow('services',$page);
   $d=$o->call('services','ListarOS',[
    'pagina'=>$page,'registros_por_pagina'=>100,'apenas_importado_api'=>'N',
    'filtrar_por_data_de'=>$period['start'],'filtrar_por_data_ate'=>$period['end'],
    'filtrar_apenas_inclusao'=>'N','filtrar_apenas_alteracao'=>'N'
   ]);
   $it=self::pick($d,['osCadastro','ordens_servico','cadastros']);
   foreach($it as $r){
    if(!is_array($r))continue;
    $n=self::normalizeServiceRecord($r);if(!$n)continue;
    DB::exec("INSERT INTO service_orders(omie_code,client_omie_code,seller_omie_code,service_date,total,status,raw_json,updated_at)
              VALUES(?,?,?,?,?,?,?,NOW())
              ON DUPLICATE KEY UPDATE client_omie_code=VALUES(client_omie_code),seller_omie_code=VALUES(seller_omie_code),
              service_date=VALUES(service_date),total=VALUES(total),status=VALUES(status),raw_json=VALUES(raw_json),updated_at=NOW()",
      [$n['code'],$n['client'],$n['seller']!==''?$n['seller']:null,$n['date'],$n['total'],$n['status'],json_encode($r,JSON_UNESCAPED_UNICODE)]);
   }
   return self::finishWindow('services',$d,$page,count($it),$period);
  }
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
  $today=date('Y-m-d');$yearAgo=date('Y-m-d',strtotime('-12 months'));
  [$validOrders,$validOrderParams]=OrderPolicy::validReportSql('o.stage_code','o.status');
  DB::exec("INSERT INTO client_metrics(client_id,last_purchase_at,revenue_12m,orders_12m,avg_ticket_12m,avg_interval_days,updated_at)
            SELECT c.id,a.last_purchase_at,COALESCE(a.revenue_12m,0),COALESCE(a.orders_12m,0),
                   CASE WHEN COALESCE(a.orders_12m,0)>0 THEN a.revenue_12m/a.orders_12m ELSE 0 END,
                   a.avg_interval_days,NOW()
            FROM clients c
            LEFT JOIN (
             SELECT o.client_omie_code,MAX(o.order_date) last_purchase_at,
                    SUM(CASE WHEN o.order_date>=? AND o.order_date<=? THEN o.total ELSE 0 END) revenue_12m,
                    SUM(CASE WHEN o.order_date>=? AND o.order_date<=? THEN 1 ELSE 0 END) orders_12m,
                    CASE WHEN COUNT(DISTINCT o.order_date)>1
                         THEN DATEDIFF(MAX(o.order_date),MIN(o.order_date))/(COUNT(DISTINCT o.order_date)-1)
                         ELSE NULL END avg_interval_days
             FROM orders o
             WHERE o.order_date IS NOT NULL AND ".$validOrders."
             GROUP BY o.client_omie_code
            ) a ON a.client_omie_code=c.omie_code
            WHERE c.active=1
            ON DUPLICATE KEY UPDATE last_purchase_at=VALUES(last_purchase_at),revenue_12m=VALUES(revenue_12m),
             orders_12m=VALUES(orders_12m),avg_ticket_12m=VALUES(avg_ticket_12m),avg_interval_days=VALUES(avg_interval_days),updated_at=NOW()",
   array_merge([$yearAgo,$today,$yearAgo,$today],$validOrderParams));
 }

}
