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
function client_filter_ufs(mixed $input): array{
 $values=is_array($input)?$input:($input===null||$input===''?[]:[$input]);$selected=[];
 foreach($values as $value){$uf=mb_strtoupper(trim((string)$value),'UTF-8');if(preg_match('/^[A-Z]{2}$/',$uf))$selected[$uf]=$uf;}
 $selected=array_values($selected);sort($selected);return $selected;
}
function client_filter_tags(mixed $input): array{
 $values=is_array($input)?$input:($input===null||$input===''?[]:[$input]);$selected=[];
 foreach($values as $value){$tag=trim((string)$value);if($tag==='')continue;$key=mb_strtolower($tag,'UTF-8');if(!isset($selected[$key]))$selected[$key]=$tag;if(count($selected)>=30)break;}
 return array_values($selected);
}
function client_tags_any_filter_sql(string $alias,int $count): string{
 if(!in_array($alias,['c','clients',''],true))throw new InvalidArgumentException('Alias de cliente inválido.');
 if($count<1)return '1=1';$prefix=$alias!==''?$alias.'.':'';
 return "EXISTS (SELECT 1 FROM client_tags filter_tag WHERE filter_tag.client_id=".$prefix."id AND filter_tag.tag_key IN (".implode(',',array_fill(0,$count,'?'))."))";
}
function db_column_exists(string $table,string $column): bool{
 static $cache=[];$key=$table.'.'.$column;if(array_key_exists($key,$cache))return $cache[$key];
 $db=(string)(DB::scalar('SELECT DATABASE()')??'');$physical=DB::prefix().$table;
 return $cache[$key]=(bool)(DB::scalar("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?",[$db,$physical,$column])??0);
}
function collection_task_condition_sql(string $alias='t'): string{
 if(!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/',$alias))throw new InvalidArgumentException('Alias de tarefa inválido.');
 return db_column_exists('tasks','type')?$alias.".type='collection'":"(".$alias.".title LIKE 'Cobrança:%' OR ".$alias.".title LIKE 'Retorno de cobrança%')";
}
function client_ddd_sql(string $alias='c'): string{
 if(!in_array($alias,['c',''],true))throw new InvalidArgumentException('Alias de cliente inválido.');
 return "LEFT(REGEXP_REPLACE(COALESCE(".($alias!==''?$alias.'.':'')."phone,''),'[^0-9]',''),2)";
}
function client_effective_seller_sql(string $alias='c',?string $month=null): string{
 if(!in_array($alias,['c','clients'],true))throw new InvalidArgumentException('Alias de cliente inválido.');
 $month=ClientPortfolioService::monthRef($month);$prefix=$alias.'.';
 return "CASE WHEN EXISTS (SELECT 1 FROM client_portfolio_assignments pa_effective WHERE pa_effective.client_id=".$prefix."id AND pa_effective.month_ref='".$month."') THEN (SELECT pa_effective_value.seller_omie_code FROM client_portfolio_assignments pa_effective_value WHERE pa_effective_value.client_id=".$prefix."id AND pa_effective_value.month_ref='".$month."' LIMIT 1) ELSE ".$prefix."seller_omie_code END";
}
function client_tag_filter_sql(string $alias='c'): string{
 if(!in_array($alias,['c','clients',''],true))throw new InvalidArgumentException('Alias de cliente inválido.');
 $prefix=$alias!==''?$alias.'.':'';
 return "EXISTS (SELECT 1 FROM client_tags indexed_tag WHERE indexed_tag.client_id=".$prefix."id AND indexed_tag.tag_key=LOWER(TRIM(?)))";
}
function client_tags_from_raw(mixed $rawJson): array{
 $raw=is_array($rawJson)?$rawJson:json_decode((string)$rawJson,true);if(!is_array($raw))return [];
 $source=is_array($raw['request']??null)?$raw['request']:$raw;
 $tags=[];foreach((array)($source['tags']??[]) as $item){$tag=trim((string)(is_array($item)?($item['tag']??''):$item));if($tag!=='')$tags[mb_strtolower($tag)]=$tag;}
 return array_values($tags);
}
function client_tag_catalog(): array{
 ClientSegmentPolicy::ensureSchema();
 $cached=$_SESSION['client_tag_catalog_cache']??null;
 if(is_array($cached)&&time()-(int)($cached['at']??0)<300&&is_array($cached['items']??null))return $cached['items'];
 $items=DB::all("SELECT MIN(t.tag) tag,COUNT(*) client_count FROM client_tags t JOIN clients c ON c.id=t.client_id WHERE c.active=1 AND c.crm_inactive=0 GROUP BY t.tag_key ORDER BY client_count DESC,tag");
 $_SESSION['client_tag_catalog_cache']=['at'=>time(),'items'=>$items];
 return $items;
}
function client_base_counts_cached(): array{
 ClientSegmentPolicy::ensureSchema();
 $cached=$_SESSION['client_base_counts_cache']??null;
 if(is_array($cached)&&time()-(int)($cached['at']??0)<60&&is_array($cached['counts']??null))return $cached['counts'];
 $counts=[
  'all'=>(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1 AND crm_inactive=0")??0),
  'crm_inactive'=>(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1 AND crm_inactive=1")??0),
  'inactive'=>(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=0")??0)
 ];
 $counts['stored']=$counts['all']+$counts['crm_inactive']+$counts['inactive'];
 foreach(['general','ead_reciclagem','suporte_pet','supplier','carrier'] as $segment){[$sql,$params]=client_segment_filter($segment,'clients');$counts[$segment]=(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1 AND crm_inactive=0 AND ".$sql,$params)??0);}
 $_SESSION['client_base_counts_cache']=['at'=>time(),'counts'=>$counts];
 return $counts;
}
function client_cache_invalidate(): void{
 unset($_SESSION['client_base_counts_cache'],$_SESSION['client_tag_catalog_cache']);
}
function client_segment_catalog(): array{
 $catalog=ClientSegmentPolicy::catalog();
 $catalog['supplier']=['label'=>'Fornecedores','description'=>'Cadastros identificados pela tag Fornecedor.','seller_codes'=>[],'tag'=>'Fornecedor'];
 $catalog['carrier']=['label'=>'Transportadoras','description'=>'Cadastros identificados pela tag Transportadora.','seller_codes'=>[],'tag'=>'Transportadora'];
 return $catalog;
}
function client_virtual_seller_codes(): array{
 return ClientSegmentPolicy::virtualSellerCodes();
}
function client_segment_filter(string $segment,string $alias='c'): array{
 if(!in_array($alias,['c','clients',''],true))throw new InvalidArgumentException('Alias de cliente inválido.');
 ClientSegmentPolicy::ensureSchema();
 $prefix=$alias!==''?$alias.'.':'';$sellerColumn="COALESCE(NULLIF(".$prefix."omie_seller_code,''),".$prefix."seller_omie_code)";$catalog=client_segment_catalog();
 if($segment==='all')return ['1=1',[]];
 if(!isset($catalog[$segment]))$segment='general';
 $tagSql=client_tag_filter_sql($alias);
 if($segment==='carrier')return [$tagSql,['Transportadora']];
 if($segment==='supplier')return ['('.$tagSql.') AND NOT ('.$tagSql.')',['Fornecedor','Transportadora']];
 $codes=$segment==='general'?client_virtual_seller_codes():array_values(array_filter(array_map('strval',(array)($catalog[$segment]['seller_codes']??[]))));
 $parts=[];$params=[];
 if($segment==='general'){
  if($codes){$parts[]='('.$sellerColumn.' IS NULL OR '.$sellerColumn."='' OR ".$sellerColumn.' NOT IN ('.implode(',',array_fill(0,count($codes),'?')).'))';array_push($params,...$codes);}
 }else{
  if(!$codes)return ['1=0',[]];
  $parts[]=$sellerColumn.' IN ('.implode(',',array_fill(0,count($codes),'?')).')';array_push($params,...$codes);
 }
 $parts[]='NOT ('.$tagSql.')';$params[]='Fornecedor';
 $parts[]='NOT ('.$tagSql.')';$params[]='Transportadora';
 return [implode(' AND ',$parts),$params];
}
function client_sync_condition(string $status='all',string $alias='c'): array{
 if(!in_array($alias,['c','clients'],true))throw new InvalidArgumentException('Alias de cliente inválido.');
 $status=in_array($status,['all','pending','local','divergent','error'],true)?$status:'all';
 $p=$alias.'.';$remoteStatus="COALESCE(JSON_UNQUOTE(JSON_EXTRACT(".$p."raw_json,'$.omie_status')),'')";
 $local=$p."omie_code LIKE 'LOCAL-%'";$pending=$remoteStatus." IN ('pending','pending_update')";$error=$remoteStatus."='error'";$divergent="COALESCE(".$p."seller_omie_code,'')<>COALESCE(".$p."omie_seller_code,'')";
 $sql=match($status){'pending'=>$pending,'local'=>$local,'divergent'=>$divergent,'error'=>$error,default=>'('.$local.' OR '.$pending.' OR '.$error.' OR '.$divergent.')'};
 return [$sql,[]];
}
function client_audit_document_valid(string $digits): bool{
 if(strlen($digits)===11){
  if(preg_match('/^(\d)\1{10}$/',$digits))return false;
  for($t=9;$t<11;$t++){$sum=0;for($i=0;$i<$t;$i++)$sum+=(int)$digits[$i]*(($t+1)-$i);if((int)$digits[$t]!==((10*$sum)%11)%10)return false;}
  return true;
 }
 if(strlen($digits)===14){
  if(preg_match('/^(\d)\1{13}$/',$digits))return false;
  foreach([[5,4,3,2,9,8,7,6,5,4,3,2],[6,5,4,3,2,9,8,7,6,5,4,3,2]] as $position=>$weights){$sum=0;foreach($weights as $i=>$weight)$sum+=(int)$digits[$i]*$weight;$remainder=$sum%11;$digit=$remainder<2?0:11-$remainder;if((int)$digits[12+$position]!==$digit)return false;}
  return true;
 }
 return false;
}
function client_audit_document_format(string $digits): string{
 if(strlen($digits)===11)return substr($digits,0,3).'.'.substr($digits,3,3).'.'.substr($digits,6,3).'-'.substr($digits,9,2);
 if(strlen($digits)===14)return substr($digits,0,2).'.'.substr($digits,2,3).'.'.substr($digits,5,3).'/'.substr($digits,8,4).'-'.substr($digits,12,2);
 return $digits;
}
function crm_search_terms(string $query,int $limit=8): array{
 $query=trim((string)(preg_replace('/\s+/u',' ',$query)??$query));if($query==='')return [];
 $parts=preg_split('/[\s\-–—\/\\\\|,.;:()\[\]{}]+/u',$query,-1,PREG_SPLIT_NO_EMPTY)?:[$query];$terms=[];
 foreach($parts as $part){$part=trim((string)$part);if($part==='')continue;$key=mb_strtolower($part,'UTF-8');if(!isset($terms[$key]))$terms[$key]=$part;if(count($terms)>=$limit)break;}
 return array_values($terms);
}
function crm_search_filter(string $query,array $fields,int $limit=8): array{
 $terms=crm_search_terms($query,$limit);if(!$terms||!$fields)return ['',[]];$groups=[];$params=[];
 foreach($terms as $term){$or=[];foreach($fields as $field){$field=trim((string)$field);if($field==='')continue;$or[]='COALESCE('.$field.",'') LIKE ?";$params[]='%'.$term.'%';}if($or)$groups[]='('.implode(' OR ',$or).')';}
 return $groups?['('.implode(' AND ',$groups).')',$params]:['',[]];
}
function client_search_fields(string $alias='c'): array{
 if(!in_array($alias,['c','clients'],true))throw new InvalidArgumentException('Alias de cliente inválido.');$p=$alias.'.';
 return [$p.'name',$p.'legal_name',$p.'document',$p.'phone',$p.'email',$p.'omie_code',$p.'city',$p.'uf',
  "JSON_UNQUOTE(JSON_EXTRACT(".$p."raw_json,'$.nome_fantasia'))","JSON_UNQUOTE(JSON_EXTRACT(".$p."raw_json,'$.razao_social'))",
  "JSON_UNQUOTE(JSON_EXTRACT(".$p."raw_json,'$.codigo_cliente_integracao'))","JSON_UNQUOTE(JSON_EXTRACT(".$p."raw_json,'$.request.nome_fantasia'))",
  "JSON_UNQUOTE(JSON_EXTRACT(".$p."raw_json,'$.request.razao_social'))","JSON_UNQUOTE(JSON_EXTRACT(".$p."raw_json,'$.request.codigo_cliente_integracao'))"];
}
function contact_channel_catalog(): array{
 $defaults=[
  ['code'=>'phone','label'=>'Ligação','contexts'=>['sales','collection'],'active'=>true,'system'=>true],
  ['code'=>'whatsapp','label'=>'WhatsApp','contexts'=>['sales','collection'],'active'=>true,'system'=>true],
  ['code'=>'email','label'=>'E-mail','contexts'=>['sales','collection'],'active'=>true,'system'=>true],
  ['code'=>'presential','label'=>'Presencial','contexts'=>['sales','collection'],'active'=>false,'system'=>true],
  ['code'=>'video','label'=>'Videoconferência','contexts'=>['sales','collection'],'active'=>false,'system'=>true],
  ['code'=>'chat','label'=>'Chat / atendimento online','contexts'=>['sales','collection'],'active'=>false,'system'=>true],
 ];
 $raw=DB::scalar("SELECT value_json FROM settings WHERE setting_key='contact_channel_catalog'");
 $saved=$raw?json_decode((string)$raw,true):null;$map=[];foreach($defaults as $item)$map[$item['code']]=$item;
 if(is_array($saved))foreach($saved as $item){
  if(!is_array($item))continue;
  $code=preg_replace('/[^a-z0-9_\-]/','',mb_strtolower(trim((string)($item['code']??''))));
  $label=trim((string)($item['label']??''));if($code===''||$label==='')continue;
  $contexts=array_values(array_intersect(['sales','collection'],array_map('strval',(array)($item['contexts']??[]))));if(!$contexts)continue;
  $base=$map[$code]??['code'=>$code,'system'=>false];
  $map[$code]=$base+[];$map[$code]['label']=$label;$map[$code]['contexts']=$contexts;
  $map[$code]['active']=!array_key_exists('active',$item)||(bool)$item['active'];$map[$code]['system']=(bool)($base['system']??false);
 }
 return array_values($map);
}
function contact_channel_options(string $context,bool $activeOnly=true): array{
 $context=in_array($context,['sales','collection'],true)?$context:'sales';
 return array_values(array_filter(contact_channel_catalog(),static fn($item)=>in_array($context,(array)($item['contexts']??[]),true)&&(!$activeOnly||!empty($item['active']))));
}
function contact_channel_label(string $code): string{
 if($code==='manual')return 'Lançamento manual';
 foreach(contact_channel_catalog() as $item)if((string)$item['code']===$code)return (string)$item['label'];
 return $code!==''?$code:'Canal não informado';
}
function contact_channel_icon(string $code): string{
 return match($code){
  'whatsapp'=>'fa-brands fa-whatsapp',
  'email'=>'fa-solid fa-envelope',
  'presential'=>'fa-solid fa-user-group',
  'video'=>'fa-solid fa-video',
  'chat'=>'fa-solid fa-comments',
  'manual'=>'fa-solid fa-file-pen',
  default=>'fa-solid fa-phone'
 };
}
function save_contact_channel_catalog(array $catalog): void{
 DB::exec("INSERT INTO settings(setting_key,value_json,updated_at) VALUES('contact_channel_catalog',?,NOW()) ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=NOW()",[json_encode(array_values($catalog),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
}
function task_description_limit(): int{return 2000;}
function task_observation_limit(): int{return 10000;}
function crm_note_limit(): int{return 10000;}
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
function task_type_catalog(): array{
 $defaults=[
  ['code'=>'call','label'=>'Ligação','contexts'=>['sales','collection'],'active'=>true,'system'=>true],
  ['code'=>'whatsapp','label'=>'WhatsApp','contexts'=>['sales','collection'],'active'=>true,'system'=>true],
  ['code'=>'return','label'=>'Retorno','contexts'=>['sales','collection'],'active'=>true,'system'=>true],
  ['code'=>'proposal','label'=>'Enviar proposta','contexts'=>['sales'],'active'=>true,'system'=>true],
  ['code'=>'follow_up','label'=>'Acompanhamento','contexts'=>['sales','collection'],'active'=>true,'system'=>true],
  ['code'=>'post_sale','label'=>'Pós-venda','contexts'=>['sales'],'active'=>true,'system'=>true],
  ['code'=>'renewal','label'=>'Renovação','contexts'=>['sales'],'active'=>true,'system'=>true],
  ['code'=>'collection','label'=>'Cobrança','contexts'=>['collection'],'active'=>true,'system'=>true],
  ['code'=>'other','label'=>'Outro','contexts'=>['sales','collection'],'active'=>true,'system'=>true],
 ];
 $raw=DB::scalar("SELECT value_json FROM settings WHERE setting_key='task_type_catalog'");
 $saved=$raw?json_decode((string)$raw,true):null;$map=[];foreach($defaults as $item)$map[$item['code']]=$item;
 if(is_array($saved))foreach($saved as $item){
  if(!is_array($item))continue;$code=preg_replace('/[^a-z0-9_\-]/','',mb_strtolower(trim((string)($item['code']??''))));$label=trim((string)($item['label']??''));
  if($code===''||$label==='')continue;$contexts=array_values(array_intersect(['sales','collection'],array_map('strval',(array)($item['contexts']??[]))));if(!$contexts)continue;
  $base=$map[$code]??['code'=>$code,'system'=>false];$map[$code]=$base+[];$map[$code]['label']=$label;$map[$code]['contexts']=$contexts;
  $map[$code]['active']=!array_key_exists('active',$item)||(bool)$item['active'];$map[$code]['system']=(bool)($base['system']??false);
 }
 return array_values($map);
}
function task_type_options(string $context,bool $activeOnly=true): array{
 $context=in_array($context,['sales','collection'],true)?$context:'sales';
 return array_values(array_filter(task_type_catalog(),static fn($item)=>in_array($context,(array)($item['contexts']??[]),true)&&(!$activeOnly||!empty($item['active']))));
}
function task_type_label(string $code): string{
 foreach(task_type_catalog() as $item)if((string)$item['code']===$code)return (string)$item['label'];
 return $code!==''?$code:'Retorno / outro';
}
function save_task_type_catalog(array $catalog): void{
 DB::exec("INSERT INTO settings(setting_key,value_json,updated_at) VALUES('task_type_catalog',?,NOW()) ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=NOW()",[json_encode(array_values($catalog),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
}
function ensure_task_type_column(): void{
 static $ready=false;if($ready)return;
 if(!db_column_exists('tasks','task_type_code'))DB::exec("ALTER TABLE tasks ADD COLUMN task_type_code VARCHAR(50) NULL AFTER type, ADD INDEX idx_tasks_task_type(task_type_code)");
 $ready=true;
}
function ensure_task_detail_columns(): void{
 static $ready=false;if($ready)return;ensure_task_type_column();
 $columns=DB::all("SHOW COLUMNS FROM tasks");$have=array_fill_keys(array_map(static fn($row)=>(string)($row['Field']??''),$columns),true);
 $changes=[
  'created_by_user_id'=>"ALTER TABLE tasks ADD COLUMN created_by_user_id INT UNSIGNED NULL AFTER assigned_user_id",
  'completion_result_code'=>"ALTER TABLE tasks ADD COLUMN completion_result_code VARCHAR(40) NULL AFTER status",
  'completion_notes'=>"ALTER TABLE tasks ADD COLUMN completion_notes TEXT NULL AFTER completion_result_code",
  'completed_by_user_id'=>"ALTER TABLE tasks ADD COLUMN completed_by_user_id INT UNSIGNED NULL AFTER completion_notes",
  'updated_at'=>"ALTER TABLE tasks ADD COLUMN updated_at DATETIME NULL AFTER completed_at"
 ];
 foreach($changes as $name=>$sql){if(isset($have[$name]))continue;try{DB::exec($sql);}catch(Throwable $e){$check=DB::all("SHOW COLUMNS FROM tasks");$names=array_map(static fn($row)=>(string)($row['Field']??''),$check);if(!in_array($name,$names,true))throw $e;}}
 foreach($columns as $column){
  if((string)($column['Field']??'')!=='title')continue;
  $type=mb_strtolower((string)($column['Type']??''),'UTF-8');
  if(!str_contains($type,'text'))DB::exec("ALTER TABLE tasks MODIFY COLUMN title TEXT NOT NULL");
  break;
 }
 $ready=true;
}
function task_access_row(int $taskId,array $user,bool $pendingOnly=false): ?array{
 ensure_task_detail_columns();$where=['t.id=?'];$params=[$taskId];if($pendingOnly)$where[]="t.status='pending'";
 if(!in_array((string)($user['role']??''),['admin','supervisor'],true)){$where[]='t.assigned_user_id=?';$params[]=(int)($user['id']??0);$where[]='c.crm_inactive=0';}
 return DB::one("SELECT t.*,c.name client_name,c.document client_document,c.city client_city,c.uf client_uf,
   assigned.name assigned_name,assigned.role assigned_role,
   creator.name created_by_name,completed.name completed_by_name
  FROM tasks t
  JOIN clients c ON c.id=t.client_id
  JOIN users assigned ON assigned.id=t.assigned_user_id
  LEFT JOIN users creator ON creator.id=t.created_by_user_id
  LEFT JOIN users completed ON completed.id=t.completed_by_user_id
  WHERE ".implode(' AND ',$where),$params);
}
function task_creator_meta(array $task): array{
 $name=trim((string)($task['created_by_name']??''));
 if($name!=='')return ['label'=>$name,'source'=>'audit','known'=>true];

 $taskId=(int)($task['id']??0);
 $clientId=(int)($task['client_id']??0);
 $assignedId=(int)($task['assigned_user_id']??0);
 $assignedName=trim((string)($task['assigned_name']??''));
 $createdAt=(string)($task['created_at']??'');
 $dueAt=(string)($task['due_at']??'');
 $title=trim((string)($task['title']??''));

 $persist=static function(int $userId)use($taskId): void{
  if($taskId<=0||$userId<=0)return;
  DB::exec("UPDATE tasks SET created_by_user_id=? WHERE id=? AND created_by_user_id IS NULL",[$userId,$taskId]);
 };

 // Retornos comerciais antigos nasceram do atendimento do próprio vendedor.
 // Antes da coluna created_by_user_id existir, a regra gravava activities.user_id
 // também em tasks.assigned_user_id. Portanto o autor é recuperável com segurança.
 if($clientId>0&&str_starts_with($title,'Retorno comercial')){
  if($dueAt!==''){
   $rows=DB::all("SELECT a.user_id,u.name,a.result,a.created_at,
      ABS(TIMESTAMPDIFF(SECOND,a.created_at,?)) created_gap
     FROM activities a
     JOIN users u ON u.id=a.user_id
     WHERE a.client_id=? AND a.next_at IS NOT NULL
       AND ABS(TIMESTAMPDIFF(SECOND,a.next_at,?))<=90
     ORDER BY created_gap,a.id DESC LIMIT 8",[$createdAt,$clientId,$dueAt]);
   foreach($rows as $row){
    $resultLabel=task_result_label((string)($row['result']??''));
    if($resultLabel!==''&&!str_contains(mb_strtolower($title,'UTF-8'),mb_strtolower($resultLabel,'UTF-8')))continue;
    $userId=(int)($row['user_id']??0);$detected=trim((string)($row['name']??''));
    if($userId>0&&$detected!==''){$persist($userId);return ['label'=>$detected,'source'=>'activity','known'=>true];}
   }
  }
  // Fallback seguro para esse fluxo legado: o código antigo sempre criava
  // o retorno comercial para o mesmo usuário que registrou o atendimento.
  if($assignedId>0&&$assignedName!==''){$persist($assignedId);return ['label'=>$assignedName,'source'=>'legacy_commercial_return','known'=>true];}
 }

 // Retornos de cobrança guardavam o autor em collection_actions.author_user_id.
 if($clientId>0&&$createdAt!==''&&$dueAt!==''&&str_starts_with($title,'Retorno de cobrança')){
  $rows=DB::all("SELECT ca.author_user_id user_id,u.name,ca.result,ca.created_at,
     ABS(TIMESTAMPDIFF(SECOND,ca.created_at,?)) created_gap
    FROM collection_actions ca
    JOIN users u ON u.id=ca.author_user_id
    WHERE ca.client_id=? AND ca.promise_date=DATE(?)
      AND ABS(TIMESTAMPDIFF(SECOND,ca.created_at,?))<=1800
    ORDER BY created_gap,ca.id DESC LIMIT 8",[$createdAt,$clientId,$dueAt,$createdAt]);
  foreach($rows as $row){
   $resultLabel=task_result_label((string)($row['result']??''));
   if($resultLabel!==''&&!str_contains(mb_strtolower($title,'UTF-8'),mb_strtolower($resultLabel,'UTF-8')))continue;
   $userId=(int)($row['user_id']??0);$detected=trim((string)($row['name']??''));
   if($userId>0&&$detected!==''){$persist($userId);return ['label'=>$detected,'source'=>'collection_action','known'=>true];}
  }
 }

 return ['label'=>'Não identificado no histórico','source'=>'legacy','known'=>false];
}
function task_status_meta(array $task): array{
 $status=(string)($task['status']??'pending');
 if($status==='done')return ['label'=>'Concluída','tone'=>'done','lifecycle'=>'Concluída'];
 if($status==='cancelled')return ['label'=>'Cancelada','tone'=>'cancelled','lifecycle'=>'Cancelada'];
 $due=!empty($task['due_at'])?strtotime((string)$task['due_at']):false;
 if($due!==false&&$due<time())return ['label'=>'Pendente · atrasada','tone'=>'late','lifecycle'=>'Pendente'];
 if($due!==false&&date('Y-m-d',$due)===date('Y-m-d'))return ['label'=>'Pendente · hoje','tone'=>'today','lifecycle'=>'Pendente'];
 return ['label'=>'Pendente · agendada','tone'=>'scheduled','lifecycle'=>'Pendente'];
}
function task_updated_label(array $task): string{
 if(empty($task['updated_at']))return 'Sem alteração posterior';
 $updated=strtotime((string)$task['updated_at']);$created=!empty($task['created_at'])?strtotime((string)$task['created_at']):false;
 if($updated===false)return 'Sem alteração posterior';
 if($created!==false&&abs($updated-$created)<=5)return 'Sem alteração posterior';
 return date('d/m/Y H:i',$updated);
}
function task_assignable_users(array $user,string $context): array{
 $role=(string)($user['role']??'');$uid=(int)($user['id']??0);$context=$context==='collection'?'collection':'sales';
 if($context==='sales')$roles=['seller','supervisor'];else $roles=['collector','supervisor'];if($role==='admin')$roles[]='admin';
 $placeholders=implode(',',array_fill(0,count($roles),'?'));$params=$roles;
 if($uid>0)return DB::all("SELECT id,name,role FROM users WHERE active=1 AND role IN (".$placeholders.") ORDER BY CASE WHEN id=? THEN 0 ELSE 1 END,FIELD(role,'supervisor','seller','collector'),name",array_merge($roles,[$uid]));
 return DB::all("SELECT id,name,role FROM users WHERE active=1 AND role IN (".$placeholders.") ORDER BY name",$roles);
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
 [$generalClientSql,$generalClientParams]=client_segment_filter('general','c');
 $where=['c.active=1','c.crm_inactive=0',$generalClientSql];$params=$generalClientParams;$effectiveSellerSql=client_effective_seller_sql('c');
 $codes=array_values(array_unique(array_filter(array_map(static fn($seller)=>$seller['role']==='seller'?trim((string)$seller['seller_omie_code']):'',$sellers))));
 $collectorIds=array_values(array_map(static fn($seller)=>(int)$seller['id'],array_filter($sellers,static fn($seller)=>$seller['role']==='collector')));$participantWhere=[];$participantParams=[];
 if($codes){$participantWhere[]='('.$effectiveSellerSql.') IN ('.implode(',',array_fill(0,count($codes),'?')).')';array_push($participantParams,...$codes);}
 if($collectorIds){$participantWhere[]='EXISTS (SELECT 1 FROM collection_cases participant_case WHERE participant_case.client_id=c.id AND participant_case.assigned_user_id IN ('.implode(',',array_fill(0,count($collectorIds),'?')).'))';array_push($participantParams,...$collectorIds);}
 if($participantWhere){$where[]='('.implode(' OR ',$participantWhere).')';array_push($params,...$participantParams);}else $where[]='1=0';
 if($sellerId>0){
  $selectedSeller=null;foreach($sellers as $availableSeller)if((int)$availableSeller['id']===$sellerId){$selectedSeller=$availableSeller;break;}
  if($selectedSeller&&$selectedSeller['role']==='seller'){$where[]='('.$effectiveSellerSql.')=?';$params[]=(string)($selectedSeller['seller_omie_code']??'');}
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
 $resultLabels=['contact'=>'Contato realizado','interested'=>'Cliente interessado','agreement'=>'Acordo encaminhado','promise'=>'Promessa de pagamento','payment'=>'Pagamento registrado','no_answer'=>'Não atendeu'];
 if(!empty($row['last_collection_action_id'])&&(empty($row['last_contact_at'])||strtotime((string)$row['collection_contact_at'])>strtotime((string)$row['last_contact_at']))){
  $row['last_activity_id']=$row['last_collection_action_id'];$row['last_channel']=$row['collection_channel'];$row['last_result']=$row['collection_result'];$row['last_notes']=$row['collection_notes'];$row['last_contact_at']=$row['collection_contact_at'];$row['last_contact_user']=$row['collection_contact_user'];$row['last_contact_flow']='collection';
 }else $row['last_contact_flow']='sales';
 $nextDue=!empty($row['next_due_at'])?strtotime((string)$row['next_due_at']):null;
 $nextClass=$nextDue&&$nextDue<time()?'late':($nextDue&&date('Y-m-d',$nextDue)===date('Y-m-d')?'today':'upcoming');
 $channelIcon=contact_channel_icon((string)($row['last_channel']??''));
 $defaultResponsible=(int)($row['next_user_id']??0);if($defaultResponsible<=0)$defaultResponsible=(int)((($row['last_contact_flow']??'sales')==='collection'?($row['collection_user_id']??0):($row['portfolio_user_id']??0)));if($defaultResponsible<=0)$defaultResponsible=(int)($row['collection_user_id']??$row['portfolio_user_id']??0);
 $schedulePayload=['client_id'=>(int)$row['id'],'client_name'=>(string)$row['name'],'task_id'=>(int)($row['next_task_id']??0),'assigned_user_id'=>$defaultResponsible,'title'=>(string)($row['next_title']??'Próximo contato'),'due_at'=>$nextDue?date('Y-m-d\TH:i',$nextDue):''];
 $lastTs=!empty($row['last_contact_at'])?strtotime((string)$row['last_contact_at']):null;$daysWithout=$lastTs?(int)floor((time()-$lastTs)/86400):null;
 $statusClass=$nextDue&&$nextDue<time()?'danger':(!$nextDue?'warning':'ok');$statusLabel=$statusClass==='danger'?'Atrasado':($statusClass==='warning'?'Sem próxima ação':'Em dia');
 $identity='<div class="tdcontact4-person"><span>'.e(mb_strtoupper(mb_substr((string)$row['name'],0,1))).'</span><div><strong>'.e($row['name']).'</strong><small>'.e(trim((string)($row['city']??'').' / '.(string)($row['uf']??''),' /')?:'Localização não informada').'</small></div></div>';
 $responsible='<strong>'.e($row['next_user_name']??$row['portfolio_user_name']??$row['collection_user_name']??'Sem responsável').'</strong><small>'.e($row['seller_name']??'Carteira').'</small>';
 $last=$lastTs?'<strong>'.date('d/m/Y H:i',$lastTs).'</strong><small>'.e($row['last_contact_user']??'Usuário').'</small>':'<span class="tdcontact4-muted">Nunca</span>';
 $days='<strong class="'.($daysWithout!==null&&$daysWithout>=10?'danger':'').'">'.($daysWithout===null?'Nunca':$daysWithout).'</strong>';
 $next=$nextDue?'<strong>'.date('d/m H:i',$nextDue).'</strong><small>'.e($row['next_title']??'Próximo contato').'</small>':'<span class="tdcontact4-muted">Não agendado</span>';
 $result='<span class="tdcontact4-result">'.e($resultLabels[$row['last_result']]??($row['last_result']?:'Sem resultado')).'</span>';
 $status='<span class="tdcontact4-status '.$statusClass.'"><i></i>'.$statusLabel.'</span>';
 $actions='<div class="tdcontact4-actions"><a href="'.APP_URL.'/clients/'.(int)$row['id'].'" title="Abrir cliente"><i class="fa-regular fa-folder-open"></i></a><button type="button" data-contact-schedule="'.e(json_encode($schedulePayload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).'" title="'.($nextDue?'Reagendar':'Agendar').'"><i class="fa-regular fa-calendar-plus"></i></button></div>';
 return [$identity,$responsible,$last,$days,$next,$result,$status,$actions];
}
$router->get('/login',function(){if(Auth::check())redirect('/');$error=$_SESSION['login_error']??null;unset($_SESSION['login_error']);render('login',['error'=>$error,'googleAvailable'=>GoogleAuth::configured()]);});
$router->post('/login',function(){CSRF::require($_POST['_token']??null);if(Auth::attempt((string)($_POST['email']??''),(string)($_POST['password']??'')))redirect('/');render('login',['error'=>'E-mail ou senha inválidos.','googleAvailable'=>GoogleAuth::configured()]);});
$router->get('/auth/google',function(){
 if(Auth::check())redirect('/');
 try{redirect(GoogleAuth::authorizationUrl());}
 catch(Throwable $e){$_SESSION['login_error']=$e->getMessage();redirect('/login');}
});
$router->get('/auth/google/callback',function(){
 if(Auth::check())redirect('/');
 try{
  $identity=GoogleAuth::complete($_GET);
  if(!Auth::loginVerifiedEmail((string)$identity['email']))throw new RuntimeException('Sua conta Google não está cadastrada ou está inativa no CRM. Fale com o administrador.');
  redirect('/');
 }catch(Throwable $e){$_SESSION['login_error']=$e->getMessage();redirect('/login');}
});
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
 $role=(string)($u['role']??'');
 $dashboard=[];
 $payload=[
  'u'=>$u,
  'data'=>&$dashboard,
  'month'=>$month,
  'selectedDays'=>$selectedDays,
  'daysInMonth'=>$daysInMonth,
  'periodLabel'=>$periodLabel,
  'resultModel'=>$resultModel,
  'resultArea'=>$resultArea,
  'resultSeller'=>$resultSeller
 ];
 if(in_array($role,['admin','supervisor'],true)){
  $payload['management']=GoalService::managementMonth($month,$selectedDays);
  $dashboard['debt']=(float)(DB::scalar("SELECT COALESCE(SUM(open_amount),0) FROM collection_cases WHERE status='open'")??0);
  $dashboard['clients']=(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1")??0);
  $dashboard['late']=(int)(DB::scalar("SELECT COUNT(*) FROM tasks WHERE status='pending' AND due_at<NOW()")??0);
 }else{
  $payload['result']=GoalService::userMonth(Auth::id(),$month,$selectedDays);
  if($role==='collector')$dashboard['debt']=(float)(DB::scalar("SELECT COALESCE(SUM(open_amount),0) FROM collection_cases WHERE status='open'")??0);
 }
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

$renderClients=function(bool $portfolioOnly=false,?string $forcedSegment=null){
 Auth::requireRole('admin','supervisor','seller');
 $u=Auth::user();
 $defaultSegment=in_array((string)$u['role'],['admin','supervisor'],true)?'all':'general';
 $segment=(string)($forcedSegment??($_GET['segment']??$defaultSegment));
 $segmentCatalog=client_segment_catalog();if($segment!=='all'&&!isset($segmentCatalog[$segment]))$segment=$defaultSegment;
 if($portfolioOnly)$segment='general';
 if($segment!=='general'&&!in_array((string)$u['role'],['admin','supervisor'],true)){http_response_code(403);exit('Segmento restrito à gestão.');}
 $segmentMeta=$segment==='all'?['label'=>'Todos os clientes','description'=>'Base ativa completa, incluindo Comercial, EAD Reciclagem e Suporte PET.']:(array)($segmentCatalog[$segment]??[]);
 $clientBasePath='clients';
 if($portfolioOnly&&$u['role']!=='seller'){redirect('/clients');}
 $flash=$_SESSION['clients_flash']??null;unset($_SESSION['clients_flash']);
 $q=trim((string)($_GET['q']??''));
 $canManage=in_array((string)$u['role'],['admin','supervisor'],true);
 $crmStatus=$canManage?(string)($_GET['crm_status']??'active'):'active';if(!in_array($crmStatus,['active','inactive','all'],true))$crmStatus='active';
 if($portfolioOnly)$crmStatus='active';
 $clientScope=$portfolioOnly?'mine':(($u['role']==='seller'&&(string)($_GET['scope']??'all')==='unassigned')?'unassigned':'all');
 $clientUfs=client_filter_ufs($_GET['ufs']??($_GET['uf']??[]));
 $uf=count($clientUfs)===1?$clientUfs[0]:'';
 $ddds=$uf!==''?client_portfolio_ddds($_GET['ddds']??[],$uf):[];
 $clientTagsSelected=client_filter_tags($_GET['tags']??($_GET['tag']??[]));$tag=count($clientTagsSelected)===1?$clientTagsSelected[0]:'';
 $sellerFilter=trim((string)($_GET['seller_filter']??''));if(mb_strlen($sellerFilter)>80)$sellerFilter='';
 $portfolioMonth=ClientPortfolioService::monthRef($_GET['month']??null);$effectiveSellerSql=client_effective_seller_sql('c',$portfolioMonth);
 [$segmentSql,$segmentParams]=client_segment_filter($segment,'c');
 $w=['c.active=1',$segmentSql];$p=$segmentParams;
 if($crmStatus==='active')$w[]='c.crm_inactive=0';elseif($crmStatus==='inactive')$w[]='c.crm_inactive=1';
 if($u['role']==='seller'){
  if($portfolioOnly){$w[]='('.$effectiveSellerSql.')=?';$p[]=trim((string)($u['seller_omie_code']??''))?:'__NO_SELLER_LINK__';}
  elseif($clientScope==='unassigned')$w[]="((".$effectiveSellerSql.") IS NULL OR TRIM((".$effectiveSellerSql."))='')";
 }
 if($clientUfs){$w[]='UPPER(TRIM(c.uf)) IN ('.implode(',',array_fill(0,count($clientUfs),'?')).')';array_push($p,...$clientUfs);}
 if($ddds){$w[]=client_ddd_sql('c').' IN ('.implode(',',array_fill(0,count($ddds),'?')).')';array_push($p,...$ddds);}
 if($clientTagsSelected){$tagKeys=array_map(static fn($v)=>mb_strtolower((string)$v,'UTF-8'),$clientTagsSelected);$w[]=client_tags_any_filter_sql('c',count($tagKeys));array_push($p,...$tagKeys);}
 if($sellerFilter==='__none__')$w[]="((".$effectiveSellerSql.") IS NULL OR TRIM((".$effectiveSellerSql."))='')";
 elseif($sellerFilter!==''){$w[]='('.$effectiveSellerSql.')=?';$p[]=$sellerFilter;}
 if($q!==''){[$searchSql,$searchParams]=crm_search_filter($q,client_search_fields('c'));if($searchSql!==''){$w[]=$searchSql;array_push($p,...$searchParams);}}
 $where=implode(' AND ',$w);
 $summaryEffective="CASE WHEN pa_summary.id IS NOT NULL THEN pa_summary.seller_omie_code ELSE c.seller_omie_code END";
 $summaryWhere=str_replace($effectiveSellerSql,$summaryEffective,$where);
 $summary=DB::one(
  "SELECT COUNT(*) total_clients,
          COALESCE(SUM(COALESCE(m.revenue_12m,0)),0) revenue_12m,
          COALESCE(SUM(COALESCE(m.orders_12m,0)),0) orders_12m,
          SUM(CASE WHEN (".$summaryEffective.") IS NULL OR TRIM((".$summaryEffective."))='' THEN 1 ELSE 0 END) without_seller,
          SUM(CASE WHEN COALESCE(c.seller_omie_code,'')<>COALESCE(c.omie_seller_code,'') THEN 1 ELSE 0 END) seller_divergences,
          SUM(CASE WHEN pa_summary.id IS NOT NULL THEN 1 ELSE 0 END) monthly_overrides,
          SUM(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(c.raw_json,'$.omie_status')) IN ('pending','pending_update') THEN 1 ELSE 0 END) pending_sync
   FROM clients c
   LEFT JOIN client_metrics m ON m.client_id=c.id
   LEFT JOIN client_portfolio_assignments pa_summary ON pa_summary.client_id=c.id AND pa_summary.month_ref='".$portfolioMonth."'
   WHERE ".$summaryWhere,
  $p
 )?:[];
 $perPage=5;
 $totalClients=(int)($summary['total_clients']??0);
 $totalPages=max(1,(int)ceil($totalClients/$perPage));
 $page=max(1,min($totalPages,(int)($_GET['page']??1)));
 $offset=($page-1)*$perPage;
 $rows=[]; // A tabela é carregada exclusivamente pela API server-side para evitar consulta duplicada.
 [$generalSql,$generalParams]=client_segment_filter('general','clients');
 $availableEffectiveSql=client_effective_seller_sql('clients',$portfolioMonth);
 $availableClients=$u['role']==='seller'?(int)(DB::scalar("SELECT COUNT(*) FROM clients WHERE active=1 AND crm_inactive=0 AND ((".$availableEffectiveSql.") IS NULL OR TRIM((".$availableEffectiveSql."))='') AND ".$generalSql,$generalParams)??0):0;
 $virtualCodes=client_virtual_seller_codes();$realSellerSql=$virtualCodes?' AND omie_code NOT IN ('.implode(',',array_fill(0,count($virtualCodes),'?')).')':'';
 $crmPortfolioCodes=ClientSegmentPolicy::crmPortfolioSellerCodes();$crmPortfolioSql=$crmPortfolioCodes?' AND omie_code IN ('.implode(',',array_fill(0,count($crmPortfolioCodes),'?')).')':' AND 1=0';
 [$stateSegmentSql,$stateSegmentParams]=client_segment_filter($segment,'c');
 $stateWhere='c.active=1 AND '.$stateSegmentSql;$stateParams=$stateSegmentParams;$stateEffectiveSql=client_effective_seller_sql('c',$portfolioMonth);
 if($crmStatus==='active')$stateWhere.=' AND c.crm_inactive=0';elseif($crmStatus==='inactive')$stateWhere.=' AND c.crm_inactive=1';
 $statePortfolioJoin=" LEFT JOIN client_portfolio_assignments pa_state ON pa_state.client_id=c.id AND pa_state.month_ref='".$portfolioMonth."'";
 $stateEffectiveExpr="CASE WHEN pa_state.id IS NOT NULL THEN pa_state.seller_omie_code ELSE c.seller_omie_code END";
 $baseCounts=client_base_counts_cached();
 if($portfolioOnly&&$u['role']==='seller'){$stateWhere.=' AND ('.$stateEffectiveSql.')=?';$stateParams[]=trim((string)($u['seller_omie_code']??''))?:'__NO_SELLER_LINK__';}
 $stateWhereJoined=str_replace($stateEffectiveSql,$stateEffectiveExpr,$stateWhere);
 render('clients',['rows'=>$rows,'q'=>$q,'uf'=>$uf,'clientUfs'=>$clientUfs,'ddds'=>$ddds,'tag'=>$tag,'sellerFilter'=>$sellerFilter,'crmStatus'=>$crmStatus,'portfolioMonth'=>$portfolioMonth,'clientTags'=>client_tag_catalog(),'clientTagsSelected'=>$clientTagsSelected,'clientScope'=>$clientScope,'portfolioMode'=>$portfolioOnly,'clientSegment'=>$segment,'clientSegmentCatalog'=>$segmentCatalog,'clientSegmentLabel'=>(string)($segmentMeta['label']??'Clientes Geral'),'clientSegmentDescription'=>(string)($segmentMeta['description']??''),'clientBasePath'=>$clientBasePath,'availableClients'=>$availableClients,'portfolioDddMap'=>client_portfolio_ddd_map(),'flash'=>$flash,'clientStats'=>[
  'total'=>$totalClients,
  'revenue'=>(float)($summary['revenue_12m']??0),
  'orders'=>(int)($summary['orders_12m']??0),
  'without_seller'=>(int)($summary['without_seller']??0),
  'seller_divergences'=>(int)($summary['seller_divergences']??0),
  'monthly_overrides'=>(int)($summary['monthly_overrides']??0),
  'pending_sync'=>(int)($summary['pending_sync']??0),
 ],'clientPagination'=>[
  'page'=>$page,'pages'=>$totalPages,'per_page'=>$perPage,
  'from'=>$totalClients?($offset+1):0,'to'=>min($offset+$perPage,$totalClients),
 ],'baseCounts'=>$baseCounts,'portfolioSellers'=>Auth::can('admin','supervisor')?DB::all("SELECT omie_code,name FROM sellers WHERE active=1".$crmPortfolioSql." ORDER BY name",$crmPortfolioCodes):[],
 'bulkSellers'=>Auth::can('admin','supervisor')?DB::all("SELECT omie_code,name FROM sellers WHERE active=1 ORDER BY name"):[],
 'portfolioSourceSellers'=>Auth::can('admin','supervisor')&&$segment==='general'?DB::all("SELECT DISTINCT (".$stateEffectiveExpr.") omie_code,COALESCE(s.name,CONCAT('Código ',(".$stateEffectiveExpr."))) name,COALESCE(s.active,0) active FROM clients c".$statePortfolioJoin." LEFT JOIN sellers s ON s.omie_code=(".$stateEffectiveExpr.") WHERE c.active=1 AND c.crm_inactive=0 AND (".$stateEffectiveExpr.") IS NOT NULL AND TRIM((".$stateEffectiveExpr."))<>''".($virtualCodes?' AND ('.$stateEffectiveExpr.') NOT IN ('.implode(',',array_fill(0,count($virtualCodes),'?')).')':'')." ORDER BY active DESC,name",$virtualCodes):[],
 'clientSellerFilters'=>DB::all("SELECT DISTINCT (".$stateEffectiveExpr.") omie_code,COALESCE(s.name,CONCAT('Código ',(".$stateEffectiveExpr."))) name,COALESCE(s.active,0) active FROM clients c".$statePortfolioJoin." LEFT JOIN sellers s ON s.omie_code=(".$stateEffectiveExpr.") WHERE ".$stateWhereJoined." AND (".$stateEffectiveExpr.") IS NOT NULL AND TRIM((".$stateEffectiveExpr."))<>'' ORDER BY active DESC,name",$stateParams),
 'portfolioStates'=>Auth::can('admin','supervisor')&&$segment==='general'?DB::all("SELECT DISTINCT UPPER(TRIM(c.uf)) uf FROM clients c".$statePortfolioJoin." WHERE ".$stateWhereJoined." AND c.uf IS NOT NULL AND TRIM(c.uf)<>'' ORDER BY uf",$stateParams):[],
 'clientStates'=>DB::all("SELECT DISTINCT UPPER(TRIM(c.uf)) uf FROM clients c".$statePortfolioJoin." WHERE ".$stateWhereJoined." AND c.uf IS NOT NULL AND TRIM(c.uf)<>'' ORDER BY uf",$stateParams)
 ]);
};
$router->get('/clients',function()use($renderClients){$renderClients(false);});
$router->get('/my-portfolio',function()use($renderClients){$renderClients(true,'general');});
$router->get('/clients-ead-reciclagem',function(){Auth::requireRole('admin','supervisor');$query=$_GET;$query['segment']='ead_reciclagem';redirect('/clients?'.http_build_query($query));});
$router->get('/clients-suporte-pet',function(){Auth::requireRole('admin','supervisor');$query=$_GET;$query['segment']='suporte_pet';redirect('/clients?'.http_build_query($query));});
$router->get('/clients-sync',function(){
 Auth::requireRole('admin','supervisor');ClientSegmentPolicy::ensureSchema();
 $status=(string)($_GET['status']??'all');if(!in_array($status,['all','pending','local','divergent','error'],true))$status='all';
 $q=trim((string)($_GET['q']??''));if(mb_strlen($q)>120)$q=mb_substr($q,0,120);
 [$syncSql,$syncParams]=client_sync_condition($status,'c');$where=['c.active=1',$syncSql];$params=$syncParams;
 if($q!==''){[$searchSql,$searchParams]=crm_search_filter($q,array_merge(client_search_fields('c'),['ps.name','os.name']));if($searchSql!==''){$where[]=$searchSql;array_push($params,...$searchParams);}}
 $summary=DB::one("SELECT
  SUM(CASE WHEN (c.omie_code LIKE 'LOCAL-%' OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT(c.raw_json,'$.omie_status')),'') IN ('pending','pending_update','error') OR COALESCE(c.seller_omie_code,'')<>COALESCE(c.omie_seller_code,'')) THEN 1 ELSE 0 END) total,
  SUM(CASE WHEN COALESCE(JSON_UNQUOTE(JSON_EXTRACT(c.raw_json,'$.omie_status')),'') IN ('pending','pending_update') THEN 1 ELSE 0 END) pending,
  SUM(CASE WHEN c.omie_code LIKE 'LOCAL-%' THEN 1 ELSE 0 END) local,
  SUM(CASE WHEN COALESCE(c.seller_omie_code,'')<>COALESCE(c.omie_seller_code,'') THEN 1 ELSE 0 END) divergent,
  SUM(CASE WHEN COALESCE(JSON_UNQUOTE(JSON_EXTRACT(c.raw_json,'$.omie_status')),'')='error' THEN 1 ELSE 0 END) errors
  FROM clients c WHERE c.active=1")?:[];
 $total=(int)(DB::scalar("SELECT COUNT(*) FROM clients c LEFT JOIN sellers ps ON ps.omie_code=c.seller_omie_code LEFT JOIN sellers os ON os.omie_code=c.omie_seller_code WHERE ".implode(' AND ',$where),$params)??0);
 $perPage=50;$pages=max(1,(int)ceil($total/$perPage));$page=max(1,min($pages,(int)($_GET['page']??1)));$offset=($page-1)*$perPage;
 $rows=DB::all("SELECT c.*,ps.name principal_seller_name,os.name omie_seller_name,
  COALESCE(JSON_UNQUOTE(JSON_EXTRACT(c.raw_json,'$.omie_status')),'') sync_status,
  JSON_UNQUOTE(JSON_EXTRACT(c.raw_json,'$.sync_error')) sync_error,
  JSON_UNQUOTE(JSON_EXTRACT(c.raw_json,'$.sync_error_at')) sync_error_at,
  JSON_UNQUOTE(JSON_EXTRACT(c.raw_json,'$.source')) sync_source
  FROM clients c LEFT JOIN sellers ps ON ps.omie_code=c.seller_omie_code LEFT JOIN sellers os ON os.omie_code=c.omie_seller_code
  WHERE ".implode(' AND ',$where)."
  ORDER BY CASE WHEN COALESCE(JSON_UNQUOTE(JSON_EXTRACT(c.raw_json,'$.omie_status')),'')='error' THEN 0 WHEN c.omie_code LIKE 'LOCAL-%' THEN 1 WHEN COALESCE(JSON_UNQUOTE(JSON_EXTRACT(c.raw_json,'$.omie_status')),'') IN ('pending','pending_update') THEN 2 ELSE 3 END,c.updated_at DESC,c.id DESC
  LIMIT ".$perPage." OFFSET ".$offset,$params);
 $catalog=client_segment_catalog();foreach($rows as &$row){$seller=trim((string)($row['seller_omie_code']??''));$tags=array_map('mb_strtolower',client_tags_from_raw($row['raw_json']??''));$row['segment_label']='Comercial';if(in_array('transportadora',$tags,true))$row['segment_label']='Transportadoras';elseif(in_array('fornecedor',$tags,true))$row['segment_label']='Fornecedores';else foreach($catalog as $key=>$meta){if(in_array($key,['general','supplier','carrier'],true))continue;if(in_array($seller,array_map('strval',(array)($meta['seller_codes']??[])),true)){$row['segment_label']=(string)($meta['label']??$key);break;}}$remoteStatus=(string)($row['sync_status']??'');$divergent=trim((string)($row['seller_omie_code']??''))!==trim((string)($row['omie_seller_code']??''));$row['sync_kind']=$remoteStatus==='error'?'error':(str_starts_with((string)$row['omie_code'],'LOCAL-')?'local':(in_array($remoteStatus,['pending','pending_update'],true)?'pending':($divergent?'divergent':'pending')));}unset($row);
 $flash=$_SESSION['clients_sync_flash']??null;unset($_SESSION['clients_sync_flash']);
 render('client_sync',['syncRows'=>$rows,'syncStatus'=>$status,'syncQuery'=>$q,'syncStats'=>$summary,'syncPagination'=>['page'=>$page,'pages'=>$pages,'total'=>$total,'from'=>$total?$offset+1:0,'to'=>min($offset+$perPage,$total)],'flash'=>$flash]);
});

$router->post('/api/clients-sync/bulk',function(){
 Auth::requireRole('admin','supervisor');$input=json_decode((string)file_get_contents('php://input'),true);if(!is_array($input))$input=$_POST;CSRF::require($input['_token']??null);
 try{
  $selection=(string)($input['selection_mode']??'selected');if(!in_array($selection,['selected','filtered'],true))throw new RuntimeException('Seleção inválida.');
  $status=(string)($input['status']??'all');if(!in_array($status,['all','pending','local','divergent','error'],true))$status='all';
  $q=trim((string)($input['q']??''));if(mb_strlen($q)>120)$q=mb_substr($q,0,120);$cursor=max(0,(int)($input['cursor']??0));
  $ids=[];foreach((array)($input['client_ids']??[]) as $id){$id=(int)$id;if($id>0)$ids[$id]=$id;}$excluded=[];foreach((array)($input['excluded_ids']??[]) as $id){$id=(int)$id;if($id>0)$excluded[$id]=$id;}
  if($selection==='selected'&&!$ids)throw new RuntimeException('Selecione pelo menos um cliente.');
  [$syncSql,$syncParams]=client_sync_condition($status,'c');$where=['c.active=1',$syncSql];$params=$syncParams;
  if($q!==''){[$searchSql,$searchParams]=crm_search_filter($q,array_merge(client_search_fields('c'),['ps.name','os.name']));if($searchSql!==''){$where[]=$searchSql;array_push($params,...$searchParams);}}
  if($selection==='selected'){$where[]='c.id IN ('.implode(',',array_fill(0,count($ids),'?')).')';array_push($params,...array_values($ids));}
  if($excluded){$where[]='c.id NOT IN ('.implode(',',array_fill(0,count($excluded),'?')).')';array_push($params,...array_values($excluded));}
  $baseWhere=$where;$baseParams=$params;$where[]='c.id>?';$params[]=$cursor;
  $rows=DB::all("SELECT c.id,c.name FROM clients c LEFT JOIN sellers ps ON ps.omie_code=c.seller_omie_code LEFT JOIN sellers os ON os.omie_code=c.omie_seller_code WHERE ".implode(' AND ',$where)." ORDER BY c.id ASC LIMIT 5",$params);
  $success=0;$failed=0;$errors=[];$nextCursor=$cursor;$u=Auth::user();
  foreach($rows as $row){$id=(int)$row['id'];$nextCursor=max($nextCursor,$id);try{ClientService::syncLocalWithOmie($id,$u);$success++;}catch(Throwable $e){ClientService::markSyncError($id,$e->getMessage());$failed++;if(count($errors)<20)$errors[]=['id'=>$id,'name'=>(string)$row['name'],'message'=>$e->getMessage()];}}
  $remaining=(int)(DB::scalar("SELECT COUNT(*) FROM clients c LEFT JOIN sellers ps ON ps.omie_code=c.seller_omie_code LEFT JOIN sellers os ON os.omie_code=c.omie_seller_code WHERE ".implode(' AND ',$baseWhere)." AND c.id>?",array_merge($baseParams,[$nextCursor]))??0);
  json_response(['success'=>true,'processed'=>count($rows),'succeeded'=>$success,'failed'=>$failed,'errors'=>$errors,'next_cursor'=>$nextCursor,'done'=>count($rows)===0||$remaining===0]);
 }catch(Throwable $e){json_response(['success'=>false,'error'=>$e->getMessage()],422);}
});

$router->get('/clients-audit',function(){
 Auth::requireRole('admin','supervisor');
 $tab=(string)($_GET['tab']??'duplicates');if(!in_array($tab,['duplicates','responsibility','inactive'],true))$tab='duplicates';
 $auditMonth=ClientPortfolioService::monthRef($_GET['month']??null);
 $q=trim((string)($_GET['q']??''));if(mb_strlen($q)>120)$q=mb_substr($q,0,120);
 $conflict=(string)($_GET['conflict']??'all');if(!in_array($conflict,['all','active','mixed','invalid'],true))$conflict='all';
 $requestedPage=max(1,(int)($_GET['page']??1));
 $docSql="REGEXP_REPLACE(COALESCE(c.document,''),'[^0-9]','')";

 // KPIs leves: não precisamos materializar todas as duplicidades só para exibir os totais.
 $clientTotals=DB::one("SELECT COUNT(*) total_clients,SUM(active=1) active_clients,SUM(active=0) inactive_clients,
                              SUM(active=1 AND COALESCE(seller_omie_code,'')<>COALESCE(omie_seller_code,'')) seller_divergences
                       FROM clients")?:[];
 $duplicateSummary=DB::one(
  "SELECT COUNT(*) duplicate_groups,COALESCE(SUM(records_count),0) duplicate_rows
   FROM (
    SELECT COUNT(*) records_count
    FROM clients c
    WHERE CHAR_LENGTH({$docSql}) IN (11,14)
    GROUP BY {$docSql}
    HAVING COUNT(*)>1
   ) duplicate_docs"
 )?:[];
 $auditStats=[
  'duplicate_groups'=>(int)($duplicateSummary['duplicate_groups']??0),
  'duplicate_rows'=>(int)($duplicateSummary['duplicate_rows']??0),
  'valid_groups'=>0,'invalid_groups'=>0,'active_groups'=>0,'mixed_groups'=>0,
  'total_clients'=>(int)($clientTotals['total_clients']??0),
  'active_clients'=>(int)($clientTotals['active_clients']??0),
  'inactive_clients'=>(int)($clientTotals['inactive_clients']??0),
  'seller_divergences'=>(int)($clientTotals['seller_divergences']??0),
  'monthly_overrides'=>(int)(DB::scalar("SELECT COUNT(*) FROM client_portfolio_assignments pa JOIN clients c ON c.id=pa.client_id WHERE c.active=1 AND pa.month_ref=?",[$auditMonth])??0),
 ];

 $visibleGroups=[];$duplicateRows=[];$duplicateRowsByDocument=[];$inactiveRows=[];$responsibilityRows=[];
 $pagination=['page'=>1,'pages'=>1,'total'=>0,'from'=>0,'to'=>0];

 if($tab==='duplicates'){
  $groups=DB::all(
   "SELECT {$docSql} document_digits,COUNT(*) records_count,SUM(c.active=1) active_count,SUM(c.active=0) inactive_count,
           SUM(c.omie_code LIKE 'LOCAL-%') local_count,
           GROUP_CONCAT(CONCAT_WS(' ',c.name,c.legal_name,c.omie_code,c.document) SEPARATOR ' ') search_blob
    FROM clients c
    WHERE CHAR_LENGTH({$docSql}) IN (11,14)
    GROUP BY document_digits HAVING COUNT(*)>1
    ORDER BY active_count DESC,records_count DESC,document_digits"
  );
  foreach($groups as &$group){
   $digits=(string)$group['document_digits'];$valid=client_audit_document_valid($digits);$active=(int)$group['active_count'];$inactive=(int)$group['inactive_count'];
   $group['document_formatted']=client_audit_document_format($digits);$group['document_type']=strlen($digits)===11?'CPF':'CNPJ';$group['document_valid']=$valid;
   $group['conflict_type']=$active>1?'active':($active>0&&$inactive>0?'mixed':'inactive');
   $valid?$auditStats['valid_groups']++:$auditStats['invalid_groups']++;
   if($active>1)$auditStats['active_groups']++;if($active>0&&$inactive>0)$auditStats['mixed_groups']++;
  }
  unset($group);
  $filteredGroups=array_values(array_filter($groups,static function(array $group)use($q,$conflict): bool{
   if($conflict==='active'&&$group['conflict_type']!=='active')return false;
   if($conflict==='mixed'&&$group['conflict_type']!=='mixed')return false;
   if($conflict==='invalid'&&!empty($group['document_valid']))return false;
   if($q==='')return true;
   $haystack=mb_strtolower(implode(' ',[(string)$group['document_digits'],(string)$group['document_formatted'],(string)($group['search_blob']??'')]),'UTF-8');
   foreach(crm_search_terms($q) as $term)if(!str_contains($haystack,mb_strtolower($term,'UTF-8')))return false;
   return true;
  }));
  $perPage=15;$total=count($filteredGroups);$pages=max(1,(int)ceil($total/$perPage));$page=min($requestedPage,$pages);$offset=($page-1)*$perPage;
  $visibleGroups=array_slice($filteredGroups,$offset,$perPage);$visibleDocuments=array_column($visibleGroups,'document_digits');
  $pagination=['page'=>$page,'pages'=>$pages,'total'=>$total,'from'=>$total?$offset+1:0,'to'=>min($offset+$perPage,$total)];
  if($visibleDocuments){
   $placeholders=implode(',',array_fill(0,count($visibleDocuments),'?'));
   $duplicateRows=DB::all(
    "SELECT c.*,s.name seller_name,{$docSql} document_digits,
            JSON_UNQUOTE(JSON_EXTRACT(c.raw_json,'$.crm_duplicate_of')) duplicate_of
     FROM clients c LEFT JOIN sellers s ON s.omie_code=c.seller_omie_code
     WHERE {$docSql} IN ({$placeholders})
     ORDER BY document_digits,c.active DESC,(c.omie_code NOT LIKE 'LOCAL-%') DESC,c.updated_at DESC,c.id",
    $visibleDocuments
   );
  }
 }elseif($tab==='inactive'){
  $inactiveWhere=['c.active=0'];$inactiveParams=[];
  if($q!==''){[$searchSql,$searchParams]=crm_search_filter($q,client_search_fields('c'));if($searchSql!==''){$inactiveWhere[]=$searchSql;array_push($inactiveParams,...$searchParams);}}
  $inactiveSql=implode(' AND ',$inactiveWhere);$total=(int)(DB::scalar("SELECT COUNT(*) FROM clients c WHERE ".$inactiveSql,$inactiveParams)??0);
  $perPage=50;$pages=max(1,(int)ceil($total/$perPage));$page=min($requestedPage,$pages);$offset=($page-1)*$perPage;
  $pagination=['page'=>$page,'pages'=>$pages,'total'=>$total,'from'=>$total?$offset+1:0,'to'=>min($offset+$perPage,$total)];
  $inactiveRows=DB::all(
   "SELECT c.*,s.name seller_name,{$docSql} document_digits
    FROM clients c LEFT JOIN sellers s ON s.omie_code=c.seller_omie_code
    WHERE ".$inactiveSql."
    ORDER BY c.updated_at DESC,c.name
    LIMIT ".$perPage." OFFSET ".$offset,
   $inactiveParams
  );
 }else{
  $responsibilityWhere=["c.active=1","(COALESCE(c.seller_omie_code,'')<>COALESCE(c.omie_seller_code,'') OR pa_row.id IS NOT NULL)"];$responsibilityParams=[$auditMonth];
  if($q!==''){[$searchSql,$searchParams]=crm_search_filter($q,array_merge(client_search_fields('c'),['ps.name','os.name','es.name']));if($searchSql!==''){$responsibilityWhere[]=$searchSql;array_push($responsibilityParams,...$searchParams);}}
  $responsibilityFrom=" FROM clients c
    LEFT JOIN sellers ps ON ps.omie_code=c.seller_omie_code
    LEFT JOIN sellers os ON os.omie_code=c.omie_seller_code
    LEFT JOIN client_portfolio_assignments pa_row ON pa_row.client_id=c.id AND pa_row.month_ref=?
    LEFT JOIN sellers es ON es.omie_code=(CASE WHEN pa_row.id IS NOT NULL THEN pa_row.seller_omie_code ELSE c.seller_omie_code END)";
  $responsibilitySql=implode(' AND ',$responsibilityWhere);
  $total=(int)(DB::scalar("SELECT COUNT(*)".$responsibilityFrom." WHERE ".$responsibilitySql,$responsibilityParams)??0);
  $perPage=50;$pages=max(1,(int)ceil($total/$perPage));$page=min($requestedPage,$pages);$offset=($page-1)*$perPage;
  $pagination=['page'=>$page,'pages'=>$pages,'total'=>$total,'from'=>$total?$offset+1:0,'to'=>min($offset+$perPage,$total)];
  $responsibilityRows=DB::all(
   "SELECT c.*,ps.name principal_seller_name,os.name omie_seller_name,
           (CASE WHEN pa_row.id IS NOT NULL THEN pa_row.seller_omie_code ELSE c.seller_omie_code END) effective_seller_code,
           es.name effective_seller_name,pa_row.id portfolio_assignment_id,pa_row.seller_omie_code portfolio_seller_code".
   $responsibilityFrom."
    WHERE ".$responsibilitySql."
    ORDER BY (COALESCE(c.seller_omie_code,'')<>COALESCE(c.omie_seller_code,'')) DESC,c.updated_at DESC,c.name
    LIMIT ".$perPage." OFFSET ".$offset,
   $responsibilityParams
  );
 }

 $allAuditRows=$duplicateRows;
 $auditCodes=array_values(array_unique(array_filter(array_map(static fn($row)=>trim((string)($row['omie_code']??'')),$allAuditRows))));
 $auditIds=array_values(array_unique(array_map(static fn($row)=>(int)$row['id'],$allAuditRows)));
 $operationalFlags=['orders_history'=>[],'services_history'=>[],'financial_history'=>[]];
 if($auditCodes){
  $codePlaceholders=implode(',',array_fill(0,count($auditCodes),'?'));
  foreach(['orders_history'=>'orders','services_history'=>'service_orders','financial_history'=>'financial_movements'] as $flag=>$table){
   foreach(DB::all("SELECT client_omie_code FROM {$table} WHERE client_omie_code IN ({$codePlaceholders}) GROUP BY client_omie_code",$auditCodes) as $historyRow)$operationalFlags[$flag][(string)$historyRow['client_omie_code']]=true;
  }
 }
 $crmHistory=[];
 if($auditIds){
  $idPlaceholders=implode(',',array_fill(0,count($auditIds),'?'));
  foreach(['activities','tasks','collection_cases','collection_actions'] as $table){
   foreach(DB::all("SELECT client_id FROM {$table} WHERE client_id IN ({$idPlaceholders}) GROUP BY client_id",$auditIds) as $historyRow)$crmHistory[(int)$historyRow['client_id']]=true;
  }
 }
 $activeMatches=[];$inactiveDocuments=array_values(array_unique(array_filter(array_map(static fn($row)=>(string)($row['document_digits']??''),$inactiveRows))));
 if($inactiveDocuments){
  $documentPlaceholders=implode(',',array_fill(0,count($inactiveDocuments),'?'));
  foreach(DB::all("SELECT {$docSql} document_digits,GROUP_CONCAT(CONCAT(c.omie_code,' — ',c.name) SEPARATOR ' | ') active_matches FROM clients c WHERE c.active=1 AND {$docSql} IN ({$documentPlaceholders}) GROUP BY document_digits",$inactiveDocuments) as $matchRow)$activeMatches[(string)$matchRow['document_digits']]=(string)$matchRow['active_matches'];
 }
 $hydrateAuditRow=static function(array $row)use($operationalFlags,$crmHistory,$activeMatches): array{
  $code=(string)($row['omie_code']??'');$id=(int)($row['id']??0);
  foreach($operationalFlags as $flag=>$codes)$row[$flag]=isset($codes[$code])?1:0;
  $row['crm_history']=isset($crmHistory[$id])?1:0;
  $row['active_matches']=$activeMatches[(string)($row['document_digits']??'')]??'';
  return $row;
 };
 $duplicateRows=array_map($hydrateAuditRow,$duplicateRows);
 foreach($inactiveRows as &$inactiveRow){$inactiveRow['orders_history']=0;$inactiveRow['services_history']=0;$inactiveRow['financial_history']=0;$inactiveRow['crm_history']=0;$inactiveRow['history_preserved']=1;$inactiveRow['active_matches']=$activeMatches[(string)($inactiveRow['document_digits']??'')]??'';}unset($inactiveRow);
 foreach($duplicateRows as $row)$duplicateRowsByDocument[(string)$row['document_digits']][]=$row;
 render('client_audit',[
  'auditTab'=>$tab,'auditQuery'=>$q,'auditConflict'=>$conflict,'auditMonth'=>$auditMonth,'auditStats'=>$auditStats,
  'auditGroups'=>$visibleGroups,'auditRowsByDocument'=>$duplicateRowsByDocument,'auditInactiveRows'=>$inactiveRows,'auditResponsibilityRows'=>$responsibilityRows,
  'auditPagination'=>$pagination
 ]);
});
$router->get('/products',function(){
 Auth::requireRole('admin','supervisor','seller');
 $status=(string)($_GET['status']??'active');
 if(!in_array($status,['all','active','inactive'],true))$status='active';
 $unit=mb_strtoupper(trim((string)($_GET['unit']??'')));
 $units=DB::all("SELECT unit,COUNT(*) total FROM products WHERE unit IS NOT NULL AND TRIM(unit)<>'' GROUP BY unit ORDER BY unit");
 if($unit!==''&&!array_filter($units,static fn($row)=>mb_strtoupper((string)$row['unit'])===$unit))$unit='';
 $syncState=DB::one("SELECT last_success_at,last_error FROM sync_state WHERE module_key='products'");
 render('products',[
  'productStatus'=>$status,'productUnit'=>$unit,'productUnits'=>$units,'productSyncState'=>$syncState,
  'productStats'=>[
   'total'=>(int)(DB::scalar("SELECT COUNT(*) FROM products")??0),
   'active'=>(int)(DB::scalar("SELECT COUNT(*) FROM products WHERE active=1")??0),
   'inactive'=>(int)(DB::scalar("SELECT COUNT(*) FROM products WHERE active=0")??0),
   'priced'=>(int)(DB::scalar("SELECT COUNT(*) FROM products WHERE active=1 AND unit_price>0")??0),
  ]
 ]);
});
$router->post('/clients/portfolio/assign',function(){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 $uf='';$ddds=[];$month=ClientPortfolioService::monthRef($_POST['month']??null);
 try{
  $uf=mb_strtoupper(trim((string)($_POST['uf']??'')),'UTF-8');$ddds=client_portfolio_ddds($_POST['ddds']??[],$uf);
  $source=trim((string)($_POST['source_seller']??''));$target=trim((string)($_POST['target_seller']??''));
  ClientSegmentPolicy::ensureSchema();
  if(!preg_match('/^[A-Z]{2}$/',$uf))throw new RuntimeException('Selecione um estado válido.');
  if(!$ddds)throw new RuntimeException('Selecione pelo menos um DDD do estado antes de aplicar a carteira.');
  $returnToPrincipal=$target==='__principal__';
  if(!$returnToPrincipal&&in_array($target,client_virtual_seller_codes(),true))throw new RuntimeException('Vendedores virtuais não podem receber clientes pela gestão de carteiras reais.');
  if(!$returnToPrincipal&&!ClientSegmentPolicy::isCrmPortfolioSeller($target))throw new RuntimeException('Selecione uma carteira comercial administrada pelo CRM.');
  $targetSeller=$returnToPrincipal?null:DB::one("SELECT omie_code,name FROM sellers WHERE omie_code=? AND active=1",[$target]);if(!$returnToPrincipal&&!$targetSeller)throw new RuntimeException('Selecione um vendedor de destino válido.');
  [$generalSql,$generalParams]=client_segment_filter('general','c');$effective=client_effective_seller_sql('c',$month);
  $where=['c.active=1',$generalSql,'UPPER(TRIM(c.uf))=?',client_ddd_sql('c').' IN ('.implode(',',array_fill(0,count($ddds),'?')).')'];$params=array_merge($generalParams,[$uf],$ddds);$sourceLabel='todos os clientes';
  if($source==='__unassigned__'){$where[]="((".$effective.") IS NULL OR TRIM((".$effective."))='')";$sourceLabel='clientes sem responsável no mês';}
  elseif($source!=='__all__'){$sourceSeller=DB::one("SELECT name FROM sellers WHERE omie_code=?",[$source]);if($source===$target)throw new RuntimeException('O responsável atual e o vendedor de destino são iguais.');$where[]='('.$effective.')=?';$params[]=$source;$sourceLabel='carteira de '.($sourceSeller['name']??$source);}
  $clients=DB::all("SELECT c.id,c.name FROM clients c WHERE ".implode(' AND ',$where)." ORDER BY c.id",$params);
  $affected=$returnToPrincipal
   ?ClientPortfolioService::clearAssignments(array_column($clients,'id'),$month,Auth::id(),'Retorno ao vendedor principal por UF/DDD: '.$uf.' · '.implode(', ',$ddds).'.')
   :ClientPortfolioService::setAssignments(array_column($clients,'id'),$month,$target,Auth::id(),'Redistribuição mensal por UF/DDD: '.$uf.' · '.implode(', ',$ddds).'.');
  $label=date('m/Y',strtotime($month.'-01'));
  $_SESSION['clients_flash']=['type'=>$affected>0?'success':'info','message'=>$affected>0
   ?($returnToPrincipal
     ?number_format($affected,0,',','.').' cliente(s) voltaram a usar o vendedor principal em '.$label.'. Nenhum cadastro principal ou dado da Omie foi alterado.'
     :number_format($affected,0,',','.').' cliente(s) de '.$uf.' nos DDDs '.implode(', ',$ddds).' atribuídos à carteira de '.$targetSeller['name'].' em '.$label.'. O vendedor principal e a Omie não foram alterados.')
   :'Nenhum cliente corresponde à carteira selecionada para '.$label.'.'];
 }catch(Throwable $e){$_SESSION['clients_flash']=['type'=>'danger','message'=>'Não foi possível atualizar a carteira mensal: '.$e->getMessage()];}
 $redirect=['uf'=>$uf,'month'=>$month];if($ddds)$redirect['ddds']=$ddds;redirect('/clients?'.http_build_query($redirect));
});
$router->get('/clients/{id}/edit',function($p){
 Auth::requireRole('admin','supervisor','seller');$u=Auth::user();$id=(int)$p['id'];
 $client=DB::one("SELECT * FROM clients WHERE id=? AND active=1",[$id]);
 if(!$client){http_response_code(404);exit('Cliente não encontrado.');}
 if(($u['role']??'')==='seller'&&ClientSegmentPolicy::isVirtualSeller(ClientSegmentPolicy::segmentSeller($client))){http_response_code(403);exit('Cliente pertencente a uma operação virtual.');}
 $old=$_SESSION['client_edit_old']??ClientService::formFromClient($client);
 $error=$_SESSION['client_edit_error']??null;
 unset($_SESSION['client_edit_old'],$_SESSION['client_edit_error']);
 render('client_new',[
  'preview'=>null,'error'=>null,'old'=>$old,'createSuccess'=>null,'createError'=>null,
  'editClient'=>$client,'editError'=>$error,
  'editSellerName'=>$client['seller_omie_code']?(DB::scalar("SELECT name FROM sellers WHERE omie_code=?",[(string)$client['seller_omie_code']])?:$client['seller_omie_code']):'Sem vendedor',
  'sellers'=>($u['role']??'')==='seller'
   ?DB::all("SELECT omie_code,name FROM sellers WHERE active=1".(client_virtual_seller_codes()?' AND omie_code NOT IN ('.implode(',',array_fill(0,count(client_virtual_seller_codes()),'?')).')':'')." ORDER BY name",client_virtual_seller_codes())
   :DB::all("SELECT omie_code,name FROM sellers WHERE active=1 ORDER BY name")
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
   'message'=>match((string)($result['status']??'')){
    'local_deleted'=>'Cliente local removido com sucesso. Como ainda não estava integrado, nenhuma chamada à Omie foi necessária.',
    'local_archived'=>'Cliente local arquivado. Agenda, tarefas e atendimentos foram preservados.',
    'synced_archived'=>'Cliente excluído na Omie e arquivado no CRM para preservar agenda, tarefas e histórico.',
    default=>!empty($result['corrected_code'])?'Cliente localizado pelo CPF/CNPJ, código Omie corrigido e exclusão concluída com sucesso na Omie e no CRM.':'Cliente excluído com sucesso na Omie e removido do CRM local.'
   }
  ];
  redirect('/clients');
 }catch(Throwable $e){
  $_SESSION['client_flash']=['type'=>'danger','message'=>$e->getMessage()];
  redirect('/clients/'.$id);
 }
});

$router->post('/clients/{id}/crm-status',function($p){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);ClientSegmentPolicy::ensureSchema();
 $id=(int)$p['id'];$client=DB::one("SELECT id,name,active,crm_inactive FROM clients WHERE id=?",[$id]);
 if(!$client){$_SESSION['clients_flash']=['type'=>'danger','message'=>'Cliente não encontrado.'];redirect('/clients');}
 if((int)$client['active']!==1){$_SESSION['clients_flash']=['type'=>'danger','message'=>'Este cadastro já está inativo na origem/Omie e não pode ser alterado por este controle local.'];redirect('/clients?crm_status=inactive');}
 $inactive=!empty($_POST['inactive'])?1:0;
 DB::exec("UPDATE clients SET crm_inactive=?,crm_inactivated_at=?,crm_inactivated_by=?,updated_at=NOW() WHERE id=?",[
  $inactive,$inactive?date('Y-m-d H:i:s'):null,$inactive?Auth::id():null,$id
 ]);
 client_cache_invalidate();
 if($inactive){
  $_SESSION['clients_flash']=['type'=>'success','message'=>'Cliente “'.$client['name'].'” inativado somente no CRM. Ele saiu das carteiras e das buscas operacionais, sem alterar a Omie.'];
  redirect('/clients');
 }
 $_SESSION['clients_flash']=['type'=>'success','message'=>'Cliente “'.$client['name'].'” reativado no CRM e devolvido às visões operacionais.'];
 redirect('/clients/'.$id);
});

$router->get('/clients/{id}',function($p){
 Auth::requireRole('admin','supervisor','seller');ClientSegmentPolicy::ensureSchema();$u=Auth::user();$id=(int)$p['id'];$flash=$_SESSION['client_flash']??null;unset($_SESSION['client_flash']);
 $c=DB::one("SELECT c.*,m.*,ciu.name crm_inactivated_by_name FROM clients c LEFT JOIN client_metrics m ON m.client_id=c.id LEFT JOIN users ciu ON ciu.id=c.crm_inactivated_by WHERE c.id=?",[$id]);
 if(!$c){http_response_code(404);exit('Cliente não encontrado.');}
 if(!in_array((string)($u['role']??''),['admin','supervisor'],true)&&!empty($c['crm_inactive'])){http_response_code(404);exit('Cliente não encontrado.');}
 if(($u['role']??'')==='seller'&&ClientSegmentPolicy::isVirtualSeller(ClientSegmentPolicy::segmentSeller($c))){http_response_code(403);exit('Cliente pertencente a uma operação virtual.');}
 $portfolioMonth=ClientPortfolioService::monthRef();$portfolioAssignment=ClientPortfolioService::assignment($id,$portfolioMonth);$effectiveSellerCode=ClientPortfolioService::effectiveSellerCode($id,$portfolioMonth);
 $isUnassigned=$effectiveSellerCode==='';
 $a=DB::all("SELECT a.*,u.name user_name FROM activities a JOIN users u ON u.id=a.user_id WHERE a.client_id=? ORDER BY a.created_at DESC LIMIT 30",[$id]);
 $sellerAudit=DB::all("SELECT sa.*,u.name actor_name,ps.name previous_seller_name,ns.name new_seller_name,pos.name previous_omie_seller_name,nos.name new_omie_seller_name FROM client_seller_audit sa LEFT JOIN users u ON u.id=sa.actor_user_id LEFT JOIN sellers ps ON ps.omie_code=sa.previous_seller_omie_code LEFT JOIN sellers ns ON ns.omie_code=sa.new_seller_omie_code LEFT JOIN sellers pos ON pos.omie_code=sa.previous_omie_seller_code LEFT JOIN sellers nos ON nos.omie_code=sa.new_omie_seller_code WHERE sa.client_id=? ORDER BY sa.created_at DESC,sa.id DESC LIMIT 30",[$id]);
 $o=DB::all("SELECT * FROM orders WHERE client_omie_code=? ORDER BY order_date DESC,id DESC LIMIT 20",[$c['omie_code']]);
 $form=ClientService::formFromClient($c);
 $sellerName=$c['seller_omie_code']?DB::scalar("SELECT name FROM sellers WHERE omie_code=?",[(string)$c['seller_omie_code']]):null;
 $effectiveSellerName=$effectiveSellerCode!==''?(DB::scalar("SELECT name FROM sellers WHERE omie_code=?",[$effectiveSellerCode])?:$effectiveSellerCode):null;$omieSellerName=!empty($c['omie_seller_code'])?(DB::scalar("SELECT name FROM sellers WHERE omie_code=?",[(string)$c['omie_seller_code']])?:$c['omie_seller_code']):null;
 render('client',['client'=>$c,'activities'=>$a,'sellerAudit'=>$sellerAudit,'orders'=>$o,'cycle'=>CRMService::cycle($c['last_purchase_at']??null,(float)($c['avg_interval_days']??0)),'flash'=>$flash,'formData'=>$form,'sellerName'=>$sellerName,'effectiveSellerCode'=>$effectiveSellerCode,'effectiveSellerName'=>$effectiveSellerName,'omieSellerName'=>$omieSellerName,'portfolioAssignment'=>$portfolioAssignment,'portfolioMonth'=>$portfolioMonth,'sharedUnassigned'=>$u['role']==='seller'&&$isUnassigned,'taskResults'=>task_result_options('sales'),'taskResultLabels'=>array_column(task_result_catalog(),'label','code'),'contactChannels'=>contact_channel_options('sales')]);
});
$router->post('/clients/{id}/activity',function($p){
 Auth::requireRole('admin','supervisor','seller');ClientSegmentPolicy::ensureSchema();CSRF::require($_POST['_token']??null);
 $id=(int)$p['id'];$u=Auth::user();$c=DB::one("SELECT * FROM clients WHERE id=?",[$id]);if(!$c)exit('Cliente inválido.');
 if(!empty($c['crm_inactive'])){http_response_code(422);exit('Cliente inativo no CRM. Reative o cadastro antes de registrar novos atendimentos.');}
 $effectiveSeller=ClientPortfolioService::effectiveSellerCode($id);$unassigned=$effectiveSeller==='';if($u['role']==='seller'&&!$unassigned&&$effectiveSeller!==(string)$u['seller_omie_code']){http_response_code(403);exit('Sem permissão para registrar atendimento fora da carteira efetiva do mês.');}
 $result=(string)($_POST['result']??'contact');$allowed=array_column(task_result_options('sales'),'code');
 if(!in_array($result,$allowed,true))$result='contact';
 $channel=(string)($_POST['channel']??'phone');$allowedChannels=array_column(contact_channel_options('sales'),'code');
 if(!in_array($channel,$allowedChannels,true))$channel=in_array('phone',$allowedChannels,true)?'phone':(string)($allowedChannels[0]??'phone');
 $notes=trim((string)($_POST['notes']??''));if(mb_strlen($notes)>crm_note_limit())throw new RuntimeException('A anotação deve ter até 10.000 caracteres.');
 $nextAt=trim((string)($_POST['next_at']??''));
 DB::exec("INSERT INTO activities(client_id,user_id,channel,result,notes,next_at,created_at) VALUES(?,?,?,?,?,?,NOW())",[$id,(int)$u['id'],$channel,$result,$notes!==''?$notes:null,$nextAt!==''?$nextAt:null]);
 if($nextAt!==''){ensure_task_detail_columns();DB::exec("INSERT INTO tasks(client_id,assigned_user_id,created_by_user_id,type,task_type_code,title,due_at,status,created_at,updated_at) VALUES(?,?,?,'sales','return',?,?,'pending',NOW(),NOW())",[$id,(int)$u['id'],(int)$u['id'],'Retorno comercial · '.task_result_label($result),$nextAt]);}
 redirect('/clients/'.$id);
});

$router->get('/contact-monitoring',function(){
 Auth::requireRole('admin','supervisor');
 $context=contact_monitoring_context($_GET);$where=$context['where'];$params=$context['params'];$effectiveSellerSql=client_effective_seller_sql('c');
 $portfolioMonth=ClientPortfolioService::monthRef();$portfolioJoin=" LEFT JOIN client_portfolio_assignments pa_monitor ON pa_monitor.client_id=c.id AND pa_monitor.month_ref='".$portfolioMonth."'";
 $effectiveSellerExpr="CASE WHEN pa_monitor.id IS NOT NULL THEN pa_monitor.seller_omie_code ELSE c.seller_omie_code END";
 $whereSql=str_replace($effectiveSellerSql,$effectiveSellerExpr,implode(' AND ',$where));
 $stats=DB::one(
  "SELECT COUNT(*) total,
          COALESCE(SUM(CASE WHEN EXISTS (SELECT 1 FROM activities stat_activity WHERE stat_activity.client_id=c.id) OR EXISTS (SELECT 1 FROM collection_actions stat_collection WHERE stat_collection.client_id=c.id) THEN 1 ELSE 0 END),0) contacted,
          COALESCE(SUM(CASE WHEN EXISTS (SELECT 1 FROM tasks stat_task WHERE stat_task.client_id=c.id AND stat_task.type IN ('sales','collection') AND stat_task.status='pending') THEN 1 ELSE 0 END),0) scheduled,
          COALESCE(SUM(CASE WHEN EXISTS (SELECT 1 FROM tasks overdue_task WHERE overdue_task.client_id=c.id AND overdue_task.type IN ('sales','collection') AND overdue_task.status='pending' AND overdue_task.due_at<NOW()) THEN 1 ELSE 0 END),0) overdue,
          COALESCE(SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM tasks next_task WHERE next_task.client_id=c.id AND next_task.type IN ('sales','collection') AND next_task.status='pending') THEN 1 ELSE 0 END),0) without_next
   FROM clients c".$portfolioJoin."
   WHERE ".$whereSql,$params
 )?:['total'=>0,'contacted'=>0,'scheduled'=>0,'overdue'=>0,'without_next'=>0];
 $attentionRows=DB::all(
  "SELECT c.id,c.name,
          NULLIF(GREATEST(COALESCE((SELECT MAX(a.created_at) FROM activities a WHERE a.client_id=c.id),'1000-01-01'),COALESCE((SELECT MAX(ca.created_at) FROM collection_actions ca WHERE ca.client_id=c.id),'1000-01-01')),'1000-01-01') last_contact_at,
          (SELECT u.name FROM users u WHERE u.role='seller' AND u.active=1 AND u.seller_omie_code=(".$effectiveSellerExpr.") ORDER BY u.id LIMIT 1) portfolio_user_name,
          (SELECT u.name FROM collection_cases cc JOIN users u ON u.id=cc.assigned_user_id WHERE cc.client_id=c.id LIMIT 1) collection_user_name,
          (SELECT u.name FROM tasks t JOIN users u ON u.id=t.assigned_user_id WHERE t.client_id=c.id AND t.type IN ('sales','collection') AND t.status='pending' ORDER BY t.due_at,t.id LIMIT 1) next_user_name,
          (SELECT t.due_at FROM tasks t WHERE t.client_id=c.id AND t.type IN ('sales','collection') AND t.status='pending' ORDER BY t.due_at,t.id LIMIT 1) next_due_at
   FROM clients c".$portfolioJoin." WHERE ".$whereSql."
   AND (NOT EXISTS (SELECT 1 FROM activities a WHERE a.client_id=c.id) OR NOT EXISTS (SELECT 1 FROM tasks t WHERE t.client_id=c.id AND t.type IN ('sales','collection') AND t.status='pending') OR EXISTS (SELECT 1 FROM tasks t WHERE t.client_id=c.id AND t.type IN ('sales','collection') AND t.status='pending' AND t.due_at<NOW()))
   ORDER BY last_contact_at ASC,c.name ASC LIMIT 5",$params
 );
 $agendaRows=DB::all(
  "SELECT c.id,c.name,t.due_at next_due_at,t.title next_title,u.name next_user_name
   FROM tasks t JOIN clients c ON c.id=t.client_id".$portfolioJoin." JOIN users u ON u.id=t.assigned_user_id
   WHERE t.type IN ('sales','collection') AND t.status='pending' AND ".$whereSql."
   ORDER BY t.due_at,t.id LIMIT 5",$params
 );
 $flash=$_SESSION['contact_monitoring_flash']??null;unset($_SESSION['contact_monitoring_flash']);
 render('contact_monitoring',['rows'=>[],'monitorAttentionRows'=>$attentionRows,'monitorAgendaRows'=>$agendaRows,'monitorSellers'=>$context['sellers'],'monitorSellerId'=>$context['seller_id'],'monitorStatus'=>$context['status'],'monitorStats'=>$stats,'flash'=>$flash]);
});
$router->post('/contact-monitoring/{id}/schedule',function($p){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 $clientId=(int)$p['id'];$taskId=max(0,(int)($_POST['task_id']??0));$assignedId=max(0,(int)($_POST['assigned_user_id']??0));
 $title=trim((string)($_POST['title']??''));$value=trim((string)($_POST['due_at']??''));
 $redirect=[];$sellerFilter=max(0,(int)($_POST['seller_filter']??0));if($sellerFilter>0)$redirect['seller_id']=$sellerFilter;
 $statusFilter=(string)($_POST['status_filter']??'all');if(in_array($statusFilter,['contacted','never','scheduled','overdue','without_next'],true))$redirect['status']=$statusFilter;
 try{
  $client=DB::one("SELECT id,name FROM clients WHERE id=? AND active=1 AND crm_inactive=0",[$clientId]);
  if(!$client)throw new RuntimeException('Cliente não encontrado.');
  $seller=DB::one("SELECT id,name,role FROM users WHERE id=? AND role IN ('seller','collector') AND active=1",[$assignedId]);
  if(!$seller)throw new RuntimeException('Selecione um usuário ativo de vendas ou cobrança para o próximo contato.');
  $monitorUserIds=contact_monitoring_user_ids();if(is_array($monitorUserIds)&&!in_array($assignedId,$monitorUserIds,true))throw new RuntimeException('Este vendedor não participa do acompanhamento conforme a regra das configurações.');
  if($title===''||mb_strlen($title)>task_description_limit())throw new RuntimeException('Informe uma descrição de até 2.000 caracteres.');
  $date=DateTime::createFromFormat('Y-m-d\TH:i',$value);
  if(!$date||$date->format('Y-m-d\TH:i')!==$value)throw new RuntimeException('Informe uma data e hora válidas.');
  if($date->getTimestamp()<time()-60)throw new RuntimeException('O próximo contato precisa ser agendado para um horário futuro.');
  $formatted=$date->format('Y-m-d H:i:00');
  if($taskId>0){
   $task=DB::one("SELECT id FROM tasks WHERE id=? AND client_id=? AND type IN ('sales','collection') AND status='pending'",[$taskId,$clientId]);
   if(!$task)throw new RuntimeException('Este agendamento não está mais pendente. Atualize a tela.');
   ensure_task_detail_columns();DB::exec("UPDATE tasks SET assigned_user_id=?,type=?,task_type_code='return',title=?,due_at=?,updated_at=NOW() WHERE id=?",[$assignedId,$seller['role']==='collector'?'collection':'sales',$title,$formatted,$taskId]);
   $message='Próximo contato de '.$client['name'].' reagendado para '.$date->format('d/m/Y').' às '.$date->format('H:i').'.';
  }else{
   ensure_task_detail_columns();DB::exec("INSERT INTO tasks(client_id,assigned_user_id,created_by_user_id,type,task_type_code,title,due_at,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,'pending',NOW(),NOW())",[$clientId,$assignedId,Auth::id(),$seller['role']==='collector'?'collection':'sales','return',$title,$formatted]);
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

 $orders=[]; // grade carregada via DataTables server-side

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

  $rows=[]; // grade carregada via DataTables server-side

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
$router->get('/orders/{id}/pdf',function($p){
 Auth::requireRole('admin','supervisor','seller');
 try{$pdf=OrderProposalPdf::fromOrder((int)$p['id'],Auth::user());}
 catch(Throwable $e){http_response_code(str_contains($e->getMessage(),'permissão')?403:422);exit(e($e->getMessage()));}
 header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="'.$pdf['filename'].'"');header('Content-Length: '.strlen($pdf['content']));header('Cache-Control: private, no-store, max-age=0');echo $pdf['content'];exit;
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
  if($mode==='pdf'){
   $draftId=null;$form=$_POST;
   if($editOrderId<=0){$draft=OrderService::saveDraft($_POST,Auth::user());$draftId=(int)$draft['id'];$form=json_decode((string)$draft['form_json'],true)?:$_POST;}
   $pdf=OrderProposalPdf::fromForm($form,Auth::user(),$draftId);
   header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="'.$pdf['filename'].'"');header('Content-Length: '.strlen($pdf['content']));header('Cache-Control: private, no-store, max-age=0');echo $pdf['content'];exit;
  }
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
$router->get('/orders/drafts/{id}/pdf',function($p){
 Auth::requireRole('admin','supervisor','seller');
 try{$draft=OrderService::draft((int)$p['id'],Auth::user());$pdf=OrderProposalPdf::fromForm((array)$draft['form'],Auth::user(),(int)$draft['id']);}
 catch(Throwable $e){http_response_code(str_contains($e->getMessage(),'permissão')?403:422);exit(e($e->getMessage()));}
 header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="'.$pdf['filename'].'"');header('Content-Length: '.strlen($pdf['content']));header('Cache-Control: private, no-store, max-age=0');echo $pdf['content'];exit;
});
$router->post('/orders/drafts/{id}/delete',function($p){
 Auth::requireRole('admin','supervisor','seller');CSRF::require($_POST['_token']??null);
 OrderService::deleteDraft((int)$p['id'],Auth::user());
 $_SESSION['success']='Rascunho excluído.';
 redirect('/orders');
});

$router->get('/collection',function(){
 Auth::requireRole('admin','supervisor','collector');
 $u=Auth::user();$view=(string)($_GET['view']??'open');if(!in_array($view,['open','settled'],true))$view='open';
 $assigned=max(0,(int)($_GET['assigned_user_id']??0));$uf=mb_strtoupper(trim((string)($_GET['uf']??'')));$delay=(string)($_GET['delay']??'all');
 $collectionTagsSelected=client_filter_tags($_GET['tags']??[]);
 if(!in_array($delay,['all','current','1_30','31_60','60_plus'],true))$delay='all';
 if($u['role']==='collector')$assigned=0;
 $where=['cc.status=?'];$params=[$view];
 if($assigned>0){$where[]='cc.assigned_user_id=?';$params[]=$assigned;}
 if($uf!==''){$where[]='c.uf=?';$params[]=$uf;}
 if($collectionTagsSelected){$tagKeys=array_map(static fn($v)=>mb_strtolower((string)$v,'UTF-8'),$collectionTagsSelected);$where[]=client_tags_any_filter_sql('c',count($tagKeys));array_push($params,...$tagKeys);}
 if($delay==='current')$where[]='cc.max_overdue_days<=0';
 elseif($delay==='1_30')$where[]='cc.max_overdue_days BETWEEN 1 AND 30';
 elseif($delay==='31_60')$where[]='cc.max_overdue_days BETWEEN 31 AND 60';
 elseif($delay==='60_plus')$where[]='cc.max_overdue_days>60';
 $whereSql=implode(' AND ',$where);
 $stats=DB::one(
  "SELECT COUNT(*) total,
          COALESCE(SUM(GREATEST(0,cc.open_amount-COALESCE(lp.pending_local,0))),0) available_amount,
          SUM(cc.max_overdue_days>0) overdue_count,
          SUM(cc.max_overdue_days>60) critical_count,
          SUM(cc.max_overdue_days<=0) current_count
   FROM collection_cases cc
   JOIN clients c ON c.id=cc.client_id
   LEFT JOIN (
    SELECT client_id,SUM(amount) pending_local
    FROM collection_actions
    WHERE result='payment' AND local_status='pending'
    GROUP BY client_id
   ) lp ON lp.client_id=cc.client_id
   WHERE ".$whereSql,
  $params
 )?:[];
 $topAttention=DB::all(
  "SELECT cc.client_id,cc.open_amount,cc.max_overdue_days,c.name
   FROM collection_cases cc
   JOIN clients c ON c.id=cc.client_id
   WHERE ".$whereSql."
   ORDER BY cc.max_overdue_days DESC,cc.open_amount DESC
   LIMIT 5",
  $params
 );
 $collectors=DB::all("SELECT id,name FROM users WHERE role='collector' AND active=1 ORDER BY name");
 $ufs=DB::all("SELECT DISTINCT c.uf FROM collection_cases cc JOIN clients c ON c.id=cc.client_id WHERE cc.status='open' AND c.uf IS NOT NULL AND TRIM(c.uf)<>'' ORDER BY c.uf");
 $actionWhere=["ca.result='payment'","ca.local_status<>'cancelled'","ca.created_at>=DATE_FORMAT(CURDATE(),'%Y-%m-01')"];$actionParams=[];
 if($assigned>0){$actionWhere[]='ca.assigned_user_id=?';$actionParams[]=$assigned;}
 if($uf!==''){$actionWhere[]='c.uf=?';$actionParams[]=$uf;}
 if($collectionTagsSelected){$tagKeys=array_map(static fn($v)=>mb_strtolower((string)$v,'UTF-8'),$collectionTagsSelected);$actionWhere[]=client_tags_any_filter_sql('c',count($tagKeys));array_push($actionParams,...$tagKeys);}
 $recovered=(float)(DB::scalar("SELECT COALESCE(SUM(ca.amount),0) FROM collection_actions ca JOIN clients c ON c.id=ca.client_id WHERE ".implode(' AND ',$actionWhere),$actionParams)??0);
 $promiseWhere=["ca.result='promise'","ca.promise_date>=CURDATE()"];$promiseParams=[];
 if($assigned>0){$promiseWhere[]='ca.assigned_user_id=?';$promiseParams[]=$assigned;}
 if($uf!==''){$promiseWhere[]='c.uf=?';$promiseParams[]=$uf;}
 if($collectionTagsSelected){$tagKeys=array_map(static fn($v)=>mb_strtolower((string)$v,'UTF-8'),$collectionTagsSelected);$promiseWhere[]=client_tags_any_filter_sql('c',count($tagKeys));array_push($promiseParams,...$tagKeys);}
 $promises=(int)(DB::scalar("SELECT COUNT(*) FROM collection_actions ca JOIN clients c ON c.id=ca.client_id WHERE ".implode(' AND ',$promiseWhere),$promiseParams)??0);
 $flash=$_SESSION['collection_flash']??null;unset($_SESSION['collection_flash']);
 render('collection',[
  'rows'=>[],'view'=>$view,'flash'=>$flash,'collectionCollectors'=>$collectors,'collectionUfs'=>$ufs,
  'collectionAssigned'=>$assigned,'collectionUf'=>$uf,'collectionDelay'=>$delay,'collectionTags'=>client_tag_catalog(),'collectionTagsSelected'=>$collectionTagsSelected,'collectionRecovered'=>$recovered,'collectionPromises'=>$promises,
  'collectionStats'=>$stats,'collectionTopAttention'=>$topAttention
 ]);
});
$router->get('/collection/report',function(){
 Auth::requireRole('admin','supervisor','collector');
 $u=Auth::user();$month=trim((string)($_GET['month']??date('Y-m')));
 if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');
 $paymentStatus=(string)($_GET['payment_status']??'all');if(!in_array($paymentStatus,['all','paid','waiting','uncontacted'],true))$paymentStatus='all';
 $assigned=max(0,(int)($_GET['assigned_user_id']??0));
 if((string)$u['role']==='collector')$assigned=0;
 $from=$month.'-01 00:00:00';$next=date('Y-m-d H:i:s',strtotime($from.' +1 month'));
 $reportWhere=['1=1'];$reportParams=[];
 if($assigned>0){$reportWhere[]='COALESCE(cc.assigned_user_id,actions.action_assigned_user_id)=?';$reportParams[]=$assigned;}
 $rows=DB::all(
  "SELECT report_clients.client_id,COALESCE(cc.assigned_user_id,actions.action_assigned_user_id) assigned_user_id,
          c.name,c.document,u.name assigned_name,COALESCE(cc.open_amount,0) open_amount,
          COALESCE(pending.pending_local,0) pending_local,
          GREATEST(0,COALESCE(cc.open_amount,0)-COALESCE(pending.pending_local,0)) available_amount,cc.status case_status,
          COALESCE(actions.action_count,0) action_count,COALESCE(actions.recovered,0) recovered,
          actions.last_action_at,actions.last_payment_at,actions.last_payment_recorded_at,actions.last_result,actions.last_channel
   FROM (
    SELECT current_cases.client_id FROM collection_cases current_cases
    UNION
    SELECT monthly_actions.client_id FROM collection_actions monthly_actions
    WHERE monthly_actions.created_at>=? AND monthly_actions.created_at<? AND monthly_actions.local_status<>'cancelled'
   ) report_clients
   JOIN clients c ON c.id=report_clients.client_id
   LEFT JOIN collection_cases cc ON cc.client_id=report_clients.client_id
   LEFT JOIN (
    SELECT ca.client_id,
           SUM(CASE WHEN ca.result<>'payment' THEN 1 ELSE 0 END) action_count,
           SUM(CASE WHEN ca.result='payment' AND ca.amount>0 THEN ca.amount ELSE 0 END) recovered,
           MAX(ca.created_at) last_action_at,
           MAX(CASE WHEN ca.result='payment' AND ca.amount>0 THEN ca.created_at END) last_payment_at,
           MAX(CASE WHEN ca.result='payment' AND ca.amount>0 THEN COALESCE(ca.recorded_at,ca.created_at) END) last_payment_recorded_at,
           SUBSTRING_INDEX(GROUP_CONCAT(ca.result ORDER BY ca.created_at DESC,ca.id DESC),',',1) last_result,
           SUBSTRING_INDEX(GROUP_CONCAT(ca.channel ORDER BY ca.created_at DESC,ca.id DESC),',',1) last_channel,
           SUBSTRING_INDEX(GROUP_CONCAT(ca.assigned_user_id ORDER BY ca.created_at DESC,ca.id DESC),',',1) action_assigned_user_id
    FROM collection_actions ca WHERE ca.created_at>=? AND ca.created_at<? AND ca.local_status<>'cancelled' GROUP BY ca.client_id
   ) actions ON actions.client_id=report_clients.client_id
   LEFT JOIN (
    SELECT client_id,SUM(amount) pending_local
    FROM collection_actions
    WHERE result='payment' AND local_status='pending'
    GROUP BY client_id
   ) pending ON pending.client_id=report_clients.client_id
   LEFT JOIN users u ON u.id=COALESCE(cc.assigned_user_id,actions.action_assigned_user_id)
   WHERE ".implode(' AND ',$reportWhere)."
   ORDER BY actions.last_action_at IS NULL,actions.last_action_at DESC,c.name",
  array_merge([$from,$next,$from,$next],$reportParams)
 );
 if($paymentStatus==='paid')$rows=array_values(array_filter($rows,static fn(array $row): bool=>(float)$row['recovered']>0));
 elseif($paymentStatus==='waiting')$rows=array_values(array_filter($rows,static fn(array $row): bool=>(int)$row['action_count']>0&&(float)$row['recovered']<=0));
 elseif($paymentStatus==='uncontacted')$rows=array_values(array_filter($rows,static fn(array $row): bool=>(int)$row['action_count']===0&&(float)$row['recovered']<=0));
 $summary=['portfolio'=>count($rows),'clients'=>0,'actions'=>0,'paid'=>0,'waiting'=>0,'recovered'=>0.0,'open'=>0.0];
 foreach($rows as $row){
  $contacted=(int)$row['action_count']>0;$paid=(float)$row['recovered']>0;
  $summary['actions']+=(int)$row['action_count'];$summary['recovered']+=(float)$row['recovered'];$summary['open']+=(float)($row['available_amount']??0);
  if($contacted)$summary['clients']++;if($paid)$summary['paid']++;if($contacted&&!$paid)$summary['waiting']++;
 }
 render('collection_report',['collectionReportRows'=>$rows,'collectionReportMonth'=>$month,'collectionReportAssigned'=>$assigned,'collectionReportStatus'=>$paymentStatus,'collectionReportSummary'=>$summary,'collectionCollectors'=>DB::all("SELECT id,name FROM users WHERE role='collector' AND active=1 ORDER BY name")]);
});
$router->get('/collection/recoveries',function(){
 Auth::requireRole('admin','supervisor');
 $period=selected_date_period();$flash=$_SESSION['collection_recovery_flash']??null;$old=$_SESSION['collection_recovery_old']??[];$defaults=$_SESSION['collection_recovery_defaults']??[];
 unset($_SESSION['collection_recovery_flash'],$_SESSION['collection_recovery_old']);
 $where=["ca.result='payment'","ca.amount>0","ca.local_status<>'cancelled'"];$params=[];
 if(!$period['all']){$where[]='ca.created_at>=?';$where[]='ca.created_at<?';$params[]=$period['from'].' 00:00:00';$params[]=$period['next'].' 00:00:00';}
 $sqlWhere=implode(' AND ',$where);
 $total=(float)(DB::scalar("SELECT COALESCE(SUM(ca.amount),0) FROM collection_actions ca WHERE ".$sqlWhere,$params)??0);
 render('collection_recoveries',['recoveries'=>[],'period'=>$period,'total'=>$total,'flash'=>$flash,'old'=>$old,'defaults'=>$defaults,'collectors'=>DB::all("SELECT id,name FROM users WHERE role='collector' AND active=1 ORDER BY name")]);
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
  DB::exec("INSERT INTO collection_actions(client_id,author_user_id,assigned_user_id,channel,result,amount,promise_date,local_status,reconciled_at,recorded_at,notes,created_at) VALUES(?,?,?,'manual','payment',?,NULL,'pending',NULL,NOW(),?,?)",[$clientId,Auth::id(),$assignedId,$amount,$notes!==''?$notes:'Lançamento retroativo de cobrança já efetuada.',$recoveryDate.' '.date('H:i:s')]);
  $_SESSION['collection_recovery_defaults']=['recovery_date'=>$recoveryDate,'assigned_user_id'=>$assignedId];
  $_SESSION['collection_recovery_flash']=['type'=>'success','message'=>'Recuperação de '.money($amount).' para '.$client['name'].' lançada em '.date('d/m/Y',strtotime($recoveryDate)).' e creditada na meta de '.$collector['name'].'.'];
  redirect('/collection/recoveries?date_from='.$recoveryDate.'&date_to='.$recoveryDate);
 }catch(Throwable $e){$_SESSION['collection_recovery_old']=$_POST;$_SESSION['collection_recovery_flash']=['type'=>'danger','message'=>$e->getMessage()];redirect('/collection/recoveries');}
});
$router->get('/collection/{id}',function($p){
 Auth::requireRole('admin','supervisor','collector');ClientSegmentPolicy::ensureSchema();
 $id=(int)$p['id'];
 $c=DB::one("SELECT cc.*,c.name,c.document,c.uf,c.phone,c.crm_inactive,u.name assigned_name FROM collection_cases cc JOIN clients c ON c.id=cc.client_id LEFT JOIN users u ON u.id=cc.assigned_user_id WHERE cc.client_id=?",[$id]);
 if(!$c){http_response_code(404);exit('Cobrança não encontrada.');}
 if(($u=Auth::user())&&($u['role']??'')==='collector'&&!empty($c['crm_inactive'])){http_response_code(404);exit('Cobrança não encontrada.');}
 $a=DB::all("SELECT ca.*,ua.name author_name,ur.name assigned_name FROM collection_actions ca JOIN users ua ON ua.id=ca.author_user_id JOIN users ur ON ur.id=ca.assigned_user_id WHERE ca.client_id=? ORDER BY ca.created_at DESC",[$id]);
 $c['pending_local']=0.0;$c['available_amount']=(float)$c['open_amount'];$c['agreement_amount']=0.0;$c['agreement_date']=null;
 foreach($a as $action){
  if((string)$action['result']==='payment'&&(string)($action['local_status']??'pending')==='pending')$c['pending_local']+=(float)$action['amount'];
  if($c['agreement_date']===null&&(string)$action['result']==='agreement'&&(string)($action['local_status']??'none')!=='cancelled'&&(float)$action['amount']>0){$c['agreement_amount']=(float)$action['amount'];$c['agreement_date']=$action['created_at'];}
 }
 $c['available_amount']=max(0,(float)$c['open_amount']-(float)$c['pending_local']);
 $flash=$_SESSION['collection_case_flash']??null;unset($_SESSION['collection_case_flash']);
 render('collection_case',['case'=>$c,'actions'=>$a,'collectors'=>Auth::can('admin','supervisor')?DB::all("SELECT id,name FROM users WHERE role='collector' AND active=1 ORDER BY name"):[],'flash'=>$flash,'taskResults'=>task_result_options('collection'),'taskResultLabels'=>array_column(task_result_catalog(),'label','code'),'contactChannels'=>contact_channel_options('collection')]);
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
 Auth::requireRole('admin','supervisor','collector');ClientSegmentPolicy::ensureSchema();CSRF::require($_POST['_token']??null);
 $id=(int)$p['id'];$u=Auth::user();
 $case=DB::one("SELECT cc.*,c.crm_inactive FROM collection_cases cc JOIN clients c ON c.id=cc.client_id WHERE cc.client_id=?",[$id]);
 if(!$case){$_SESSION['collection_case_flash']=['type'=>'danger','message'=>'Cobrança inválida.'];redirect('/collection');}
 if(!empty($case['crm_inactive'])){$_SESSION['collection_case_flash']=['type'=>'danger','message'=>'Cliente inativo no CRM. Reative o cadastro antes de registrar nova movimentação.'];redirect('/collection');}
 $assigned=(int)($case['assigned_user_id']??0);
 if(Auth::can('admin','supervisor')&&!empty($_POST['assigned_user_id']))$assigned=(int)$_POST['assigned_user_id'];
 if($assigned<=0)$assigned=(int)$u['id'];

 $result=(string)($_POST['result']??'contact');$allowedResults=array_column(task_result_options('collection'),'code');
 if(!in_array($result,$allowedResults,true))$result='contact';
 $channel=(string)($_POST['channel']??'phone');$allowedChannels=array_column(contact_channel_options('collection'),'code');
 if(!in_array($channel,$allowedChannels,true))$channel=in_array('phone',$allowedChannels,true)?'phone':(string)($allowedChannels[0]??'phone');
 $notes=trim((string)($_POST['notes']??''));if(mb_strlen($notes)>crm_note_limit()){$_SESSION['collection_case_flash']=['type'=>'danger','message'=>'A anotação deve ter até 10.000 caracteres.'];redirect('/collection/'.$id);}
 $rawAmount=trim((string)($_POST['amount']??''));$normalizedAmount=str_contains($rawAmount,',')?str_replace(',','.',str_replace('.','',$rawAmount)):str_replace(' ','',$rawAmount);$amount=(float)$normalizedAmount;
 if(in_array($result,['agreement','payment'],true)&&$amount<=0){$_SESSION['collection_case_flash']=['type'=>'danger','message'=>'Informe o valor do '.($result==='payment'?'pagamento':'acordo').'.'];redirect('/collection/'.$id);}

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
  DB::exec("INSERT INTO collection_actions(client_id,author_user_id,assigned_user_id,channel,result,amount,promise_date,local_status,reconciled_at,recorded_at,notes,created_at) VALUES(?,?,?,?,?,?,?, ?,NULL,NOW(),?,NOW())",[$id,(int)$u['id'],$assigned,$channel,$result,$amount,$promiseDate,$result==='payment'?'pending':'none',$notes!==''?$notes:null]);
  if($promiseDueAt!==null){ensure_task_detail_columns();DB::exec("INSERT INTO tasks(client_id,assigned_user_id,created_by_user_id,type,task_type_code,title,due_at,status,created_at,updated_at) VALUES(?,?,?,'collection','return',?,?,'pending',NOW(),NOW())",[$id,$assigned,(int)$u['id'],'Retorno de cobrança · '.task_result_label($result),$promiseDueAt]);}
  DB::conn()->commit();
  $_SESSION['collection_case_flash']=['type'=>'success','message'=>$promiseDueAt?'Ação salva e retorno agendado para '.$date->format('d/m/Y').' às '.$date->format('H:i').'.':'Ação de cobrança salva com sucesso.'];
 }catch(Throwable $e){
  if(DB::conn()->inTransaction())DB::conn()->rollBack();
  $_SESSION['collection_case_flash']=['type'=>'danger','message'=>'Não foi possível salvar a ação de cobrança. Tente novamente.'];
 }
 redirect('/collection/'.$id);
});
$router->post('/collection/actions/{id}/delete',function($p){
 Auth::requireRole('admin','supervisor','collector');CSRF::require($_POST['_token']??null);
 $id=(int)$p['id'];$action=DB::one("SELECT id,client_id,author_user_id,result,amount,local_status FROM collection_actions WHERE id=?",[$id]);
 if(!$action){http_response_code(404);exit('Lançamento não encontrado.');}
 $returnTo=(string)($_POST['return_to']??'case');
 if((float)$action['amount']<=0){$_SESSION['collection_case_flash']=['type'=>'danger','message'=>'Este registro não possui valor para excluir.'];redirect('/collection/'.(int)$action['client_id']);}
 DB::exec("DELETE FROM collection_actions WHERE id=?",[$id]);
 $message='Valor excluído definitivamente. Saldos e relatórios foram recalculados.';
 if($returnTo==='recoveries'&&Auth::can('admin','supervisor')){$_SESSION['collection_recovery_flash']=['type'=>'success','message'=>$message];redirect('/collection/recoveries');}
 $_SESSION['collection_case_flash']=['type'=>'success','message'=>$message];
 redirect('/collection/'.(int)$action['client_id']);
});
$router->post('/collection/{id}/actions/delete',function($p){
 Auth::requireRole('admin','supervisor','collector');CSRF::require($_POST['_token']??null);
 $clientId=(int)$p['id'];$singleId=(int)($_POST['single_id']??0);
 $submitted=$singleId>0?[$singleId]:(array)($_POST['action_ids']??[]);
 $ids=array_values(array_unique(array_filter(array_map('intval',$submitted),static fn(int $id): bool=>$id>0)));
 if(!$ids){$_SESSION['collection_case_flash']=['type'=>'warning','message'=>'Selecione pelo menos um lançamento para excluir.'];redirect('/collection/'.$clientId);}
 $placeholders=implode(',',array_fill(0,count($ids),'?'));
 $actions=DB::all("SELECT id,author_user_id FROM collection_actions WHERE client_id=? AND id IN (".$placeholders.") AND amount>0",array_merge([$clientId],$ids));
 if(count($actions)!==count($ids)){$_SESSION['collection_case_flash']=['type'=>'danger','message'=>'Um ou mais lançamentos selecionados não estão disponíveis para exclusão.'];redirect('/collection/'.$clientId);}
 DB::exec("DELETE FROM collection_actions WHERE client_id=? AND id IN (".$placeholders.")",array_merge([$clientId],$ids));
 $count=count($ids);$_SESSION['collection_case_flash']=['type'=>'success','message'=>$count===1?'1 valor foi excluído definitivamente. Saldos e relatórios foram recalculados.':$count.' valores foram excluídos definitivamente. Saldos e relatórios foram recalculados.'];
 redirect('/collection/'.$clientId);
});

$router->get('/agenda',function(){
 Auth::requireLogin();ClientSegmentPolicy::ensureSchema();
 $u=Auth::user();$role=(string)$u['role'];$teamAgenda=in_array($role,['admin','supervisor'],true);
 $filterUser=$teamAgenda?max(0,(int)($_GET['user_id']??0)):(int)$u['id'];
 $agendaType=(string)($_GET['type']??'all');if(!in_array($agendaType,['all','sales','collection'],true))$agendaType='all';
 if($role==='collector')$agendaType='collection';
 $agendaPeriod=(string)($_GET['period']??'all');if(!in_array($agendaPeriod,['all','late','today','next7','upcoming'],true))$agendaPeriod='all';
 $validAgendaDate=static fn(string $value): bool=>(bool)preg_match('/^\d{4}-\d{2}-\d{2}$/',$value)&&date('Y-m-d',strtotime($value))===$value;
 $createdDate=trim((string)($_GET['created_date']??''));if($createdDate!==''&&!$validAgendaDate($createdDate))$createdDate='';
 $createdNext=$createdDate!==''?date('Y-m-d',strtotime($createdDate.' +1 day')):'';
 $flash=$_SESSION['agenda_flash']??null;unset($_SESSION['agenda_flash']);

 $baseWhere=["t.status='pending'"];$baseParams=[];
 if(!$teamAgenda)$baseWhere[]="EXISTS (SELECT 1 FROM clients agenda_client WHERE agenda_client.id=t.client_id AND agenda_client.crm_inactive=0)";
 if($teamAgenda){
  if($filterUser>0){$baseWhere[]='t.assigned_user_id=?';$baseParams[]=$filterUser;}
 }else{
  $baseWhere[]='t.assigned_user_id=?';$baseParams[]=(int)$u['id'];
 }
 if($agendaType!=='all'){$baseWhere[]='t.type=?';$baseParams[]=$agendaType;}
 if($createdDate!==''){$baseWhere[]='t.created_at>=? AND t.created_at<?';array_push($baseParams,$createdDate.' 00:00:00',$createdNext.' 00:00:00');}

 $listWhere=$baseWhere;$listParams=$baseParams;
 if($agendaPeriod==='late')$listWhere[]='t.due_at<CURDATE()';
 elseif($agendaPeriod==='today')$listWhere[]='t.due_at>=CURDATE() AND t.due_at<CURDATE()+INTERVAL 1 DAY';
 elseif($agendaPeriod==='next7')$listWhere[]='t.due_at>=CURDATE()+INTERVAL 1 DAY AND t.due_at<CURDATE()+INTERVAL 8 DAY';
 elseif($agendaPeriod==='upcoming')$listWhere[]='t.due_at>=CURDATE()+INTERVAL 1 DAY';

 $rows=DB::all(
  "SELECT t.*,c.name,c.uf,u.name assigned_name,u.role assigned_role
   FROM tasks t JOIN clients c ON c.id=t.client_id
   JOIN users u ON u.id=t.assigned_user_id
   WHERE ".implode(' AND ',$listWhere)."
   ORDER BY CASE WHEN t.due_at<CURDATE() THEN 0 WHEN t.due_at<CURDATE()+INTERVAL 1 DAY THEN 1 ELSE 2 END,t.due_at",
  $listParams
 );

 $stats=DB::one(
  "SELECT COUNT(*) total,
          SUM(CASE WHEN t.due_at<CURDATE() THEN 1 ELSE 0 END) late_count,
          SUM(CASE WHEN t.due_at>=CURDATE() AND t.due_at<CURDATE()+INTERVAL 1 DAY THEN 1 ELSE 0 END) today_count,
          SUM(CASE WHEN t.due_at>=CURDATE()+INTERVAL 1 DAY THEN 1 ELSE 0 END) upcoming_count,
          SUM(CASE WHEN t.type='collection' THEN 1 ELSE 0 END) collection_count
   FROM tasks t WHERE ".implode(' AND ',$baseWhere),
  $baseParams
 )?:['total'=>0,'late_count'=>0,'today_count'=>0,'upcoming_count'=>0,'collection_count'=>0];

 // A visão usa exatamente a mesma base pendente dos cards, evitando totais divergentes.
 $vision=[
  'total'=>(int)($stats['total']??0),'upcoming_count'=>(int)($stats['upcoming_count']??0),
  'today_count'=>(int)($stats['today_count']??0),'late_count'=>(int)($stats['late_count']??0),
  'done_count'=>0,'other_count'=>0
 ];
 if(!$teamAgenda){
  $personalWhere=['t.assigned_user_id=?',"EXISTS (SELECT 1 FROM clients agenda_client WHERE agenda_client.id=t.client_id AND agenda_client.crm_inactive=0)"];$personalParams=[(int)$u['id']];
  if($agendaType!=='all'){$personalWhere[]='t.type=?';$personalParams[]=$agendaType;}
  if($createdDate!==''){$personalWhere[]='t.created_at>=? AND t.created_at<?';array_push($personalParams,$createdDate.' 00:00:00',$createdNext.' 00:00:00');}
  $vision=DB::one(
   "SELECT COUNT(*) total,
           SUM(CASE WHEN t.status='pending' AND t.due_at>=CURDATE()+INTERVAL 1 DAY THEN 1 ELSE 0 END) upcoming_count,
           SUM(CASE WHEN t.status='pending' AND t.due_at>=CURDATE() AND t.due_at<CURDATE()+INTERVAL 1 DAY THEN 1 ELSE 0 END) today_count,
           SUM(CASE WHEN t.status='pending' AND t.due_at<CURDATE() THEN 1 ELSE 0 END) late_count,
           SUM(CASE WHEN t.status='done' THEN 1 ELSE 0 END) done_count,
           SUM(CASE WHEN t.status NOT IN ('pending','done') THEN 1 ELSE 0 END) other_count
    FROM tasks t WHERE ".implode(' AND ',$personalWhere),$personalParams
  )?:$vision;
 }

 $users=$teamAgenda?DB::all("SELECT id,name,role FROM users WHERE active=1 ORDER BY FIELD(role,'seller','collector','supervisor','admin'),name"):[];
 $workload=[];
 if($teamAgenda){
  $teamWhere=["t.status='pending'"];$teamParams=[];
  if($filterUser>0){$teamWhere[]='t.assigned_user_id=?';$teamParams[]=$filterUser;}
  if($agendaType!=='all'){$teamWhere[]='t.type=?';$teamParams[]=$agendaType;}
  if($createdDate!==''){$teamWhere[]='t.created_at>=? AND t.created_at<?';array_push($teamParams,$createdDate.' 00:00:00',$createdNext.' 00:00:00');}
  $workload=DB::all(
   "SELECT u.id,u.name,u.role,COUNT(*) total,
           SUM(CASE WHEN t.due_at<CURDATE() THEN 1 ELSE 0 END) late_count,
           SUM(CASE WHEN t.due_at>=CURDATE() AND t.due_at<CURDATE()+INTERVAL 1 DAY THEN 1 ELSE 0 END) today_count,
           SUM(CASE WHEN t.due_at>=CURDATE()+INTERVAL 1 DAY THEN 1 ELSE 0 END) upcoming_count
    FROM tasks t JOIN users u ON u.id=t.assigned_user_id
    WHERE ".implode(' AND ',$teamWhere)."
    GROUP BY u.id,u.name,u.role
    ORDER BY late_count DESC,today_count DESC,u.name",
   $teamParams
  );
 }

 render('agenda',[
  'rows'=>$rows,'agendaUsers'=>$users,'agendaFilterUser'=>$filterUser,'teamAgenda'=>$teamAgenda,
  'agendaType'=>$agendaType,'agendaPeriod'=>$agendaPeriod,'agendaStats'=>$stats,'agendaWorkload'=>$workload,'agendaVision'=>$vision,
  'agendaCreatedDate'=>$createdDate,'taskTypeLabels'=>array_column(task_type_catalog(),'label','code'),'flash'=>$flash
 ]);
});
$router->get('/api/tasks/form-context',function(){
 Auth::requireLogin();ClientSegmentPolicy::ensureSchema();$u=Auth::user();$role=(string)($u['role']??'');$context=$role==='collector'?'collection':'sales';$clientId=max(0,(int)($_GET['client_id']??0));$client=null;
 if($clientId>0)$client=DB::one("SELECT id,name,document,city,uf FROM clients WHERE id=? AND active=1 AND crm_inactive=0",[$clientId]);
 if($role==='seller')$users=DB::all("SELECT id,name,role FROM users WHERE active=1 AND role IN('seller','supervisor') ORDER BY CASE WHEN id=? THEN 0 ELSE 1 END,FIELD(role,'seller','supervisor'),name",[(int)$u['id']]);
 elseif($role==='collector')$users=DB::all("SELECT id,name,role FROM users WHERE active=1 AND role IN('collector','supervisor') ORDER BY CASE WHEN id=? THEN 0 ELSE 1 END,FIELD(role,'collector','supervisor'),name",[(int)$u['id']]);
 elseif($role==='supervisor')$users=DB::all("SELECT id,name,role FROM users WHERE active=1 AND role IN('seller','collector','supervisor') ORDER BY CASE WHEN id=? THEN 0 ELSE 1 END,FIELD(role,'supervisor','seller','collector'),name",[(int)$u['id']]);
 else $users=DB::all("SELECT id,name,role FROM users WHERE active=1 AND role IN('seller','collector','supervisor','admin') ORDER BY CASE WHEN id=? THEN 0 ELSE 1 END,FIELD(role,'admin','supervisor','seller','collector'),name",[(int)$u['id']]);
 json_response(['ok'=>true,'current_user_id'=>(int)$u['id'],'role'=>$role,'default_context'=>$context,'users'=>$users,'types'=>task_type_catalog(),'client'=>$client]);
});
$router->post('/api/tasks',function(){
 Auth::requireLogin();ClientSegmentPolicy::ensureSchema();CSRF::require($_POST['_token']??null);$u=Auth::user();$role=(string)($u['role']??'');
 try{
  $clientId=max(0,(int)($_POST['client_id']??0));$assignedId=max(0,(int)($_POST['assigned_user_id']??0));
  $context=(string)($_POST['context']??($role==='collector'?'collection':'sales'));if(!in_array($context,['sales','collection'],true))$context='sales';
  if($role==='seller')$context='sales';if($role==='collector')$context='collection';
  $taskTypeCode=preg_replace('/[^a-z0-9_\-]/','',mb_strtolower(trim((string)($_POST['task_type_code']??''))));
  $title=trim((string)($_POST['title']??''));$value=trim((string)($_POST['due_at']??''));$date=DateTime::createFromFormat('Y-m-d\TH:i',$value);
  $client=$clientId>0?DB::one("SELECT id,name FROM clients WHERE id=? AND active=1 AND crm_inactive=0",[$clientId]):null;if(!$client)throw new RuntimeException('Selecione um cliente válido.');
  $assigned=$assignedId>0?DB::one("SELECT id,name,role FROM users WHERE id=? AND active=1",[$assignedId]):null;if(!$assigned)throw new RuntimeException('Selecione um responsável ativo.');
  $assignedRole=(string)$assigned['role'];
  if($context==='sales'&&!in_array($assignedRole,['seller','supervisor','admin'],true))throw new RuntimeException('Tarefas comerciais devem ser atribuídas a vendedor, supervisor ou administrador.');
  if($context==='collection'&&!in_array($assignedRole,['collector','supervisor','admin'],true))throw new RuntimeException('Tarefas de cobrança devem ser atribuídas à cobrança, supervisor ou administrador.');
  $allowed=array_column(task_type_options($context),'code');if(!in_array($taskTypeCode,$allowed,true))throw new RuntimeException('Selecione um tipo de tarefa válido.');
  if(!$date||$date->format('Y-m-d\TH:i')!==$value||$date->getTimestamp()<time()-60)throw new RuntimeException('Informe uma data e hora futura válida.');
  if($title===''||mb_strlen($title)>task_description_limit())throw new RuntimeException('Informe uma descrição com até 2.000 caracteres.');
  ensure_task_detail_columns();DB::exec("INSERT INTO tasks(client_id,assigned_user_id,created_by_user_id,type,task_type_code,title,due_at,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,'pending',NOW(),NOW())",[$clientId,$assignedId,(int)$u['id'],$context,$taskTypeCode,$title,$date->format('Y-m-d H:i:00')]);
  json_response(['ok'=>true,'message'=>'Tarefa criada para '.$assigned['name'].' em '.$date->format('d/m/Y').' às '.$date->format('H:i').'.','task'=>['client_name'=>$client['name'],'assigned_name'=>$assigned['name'],'task_type'=>task_type_label($taskTypeCode)]]);
 }catch(Throwable $e){json_response(['ok'=>false,'error'=>$e->getMessage()],422);}
});

$router->get('/api/tasks/{id}',function($p){
 Auth::requireLogin();$u=Auth::user();$id=(int)$p['id'];$task=task_access_row($id,$u,false);
 if(!$task){json_response(['ok'=>false,'error'=>'Tarefa não encontrada ou sem permissão de acesso.'],404);}
 $context=(string)$task['type'];$status=(string)$task['status'];$creatorMeta=task_creator_meta($task);$statusMeta=task_status_meta($task);$updatedLabel=task_updated_label($task);
 $users=['sales'=>task_assignable_users($u,'sales'),'collection'=>task_assignable_users($u,'collection')];
 $types=['sales'=>task_type_options('sales',false),'collection'=>task_type_options('collection',false)];
 $results=['sales'=>task_result_options('sales'),'collection'=>task_result_options('collection')];
 json_response(['ok'=>true,'current_role'=>(string)($u['role']??''),'task'=>[
  'id'=>(int)$task['id'],'client_id'=>(int)$task['client_id'],'client_name'=>(string)$task['client_name'],
  'client_document'=>(string)($task['client_document']??''),'client_city'=>(string)($task['client_city']??''),'client_uf'=>(string)($task['client_uf']??''),
  'assigned_user_id'=>(int)$task['assigned_user_id'],'assigned_name'=>(string)$task['assigned_name'],'assigned_role'=>(string)$task['assigned_role'],
  'created_by_name'=>$creatorMeta['label'],'created_by_source'=>$creatorMeta['source'],'created_by_known'=>$creatorMeta['known'],'completed_by_name'=>(string)($task['completed_by_name']??''),
  'context'=>$context,'task_type_code'=>(string)($task['task_type_code']??''),'task_type_label'=>task_type_label((string)($task['task_type_code']??'')),
  'title'=>(string)$task['title'],'due_at'=>date('Y-m-d\TH:i',strtotime((string)$task['due_at'])),
  'due_at_label'=>date('d/m/Y H:i',strtotime((string)$task['due_at'])),'created_at_label'=>date('d/m/Y H:i',strtotime((string)$task['created_at'])),
  'updated_at_label'=>$updatedLabel,
  'status'=>$status,'status_label'=>$statusMeta['label'],'status_tone'=>$statusMeta['tone'],'lifecycle_status_label'=>$statusMeta['lifecycle'],
  'completion_result_code'=>(string)($task['completion_result_code']??''),'completion_result_label'=>task_result_label((string)($task['completion_result_code']??'')),
  'completion_notes'=>(string)($task['completion_notes']??''),'completed_at_label'=>!empty($task['completed_at'])?date('d/m/Y H:i',strtotime((string)$task['completed_at'])):''
 ],'users'=>$users,'types'=>$types,'results'=>$results,'can_edit'=>$status==='pending']);
});
$router->post('/api/tasks/{id}',function($p){
 Auth::requireLogin();CSRF::require($_POST['_token']??null);$u=Auth::user();$id=(int)$p['id'];
 try{
  $task=task_access_row($id,$u,true);if(!$task)throw new RuntimeException('Tarefa não encontrada, encerrada ou sem permissão.');
  $mode=(string)($_POST['mode']??'edit');if(!in_array($mode,['edit','reschedule'],true))$mode='edit';
  $title=trim((string)($_POST['title']??''));if($title===''||mb_strlen($title)>task_description_limit())throw new RuntimeException('Informe uma descrição de até 2.000 caracteres.');
  $value=trim((string)($_POST['due_at']??''));$date=DateTime::createFromFormat('Y-m-d\TH:i',$value);
  if(!$date||$date->format('Y-m-d\TH:i')!==$value)throw new RuntimeException('Informe uma data e hora válidas.');
  $formatted=$date->format('Y-m-d H:i:00');$original=(string)$task['due_at'];
  if(($mode==='reschedule'||$formatted!==$original)&&$date->getTimestamp()<time()-60)throw new RuntimeException('Ao alterar o horário, escolha uma data futura.');

  if($mode==='reschedule'){
   DB::exec("UPDATE tasks SET title=?,due_at=?,updated_at=NOW() WHERE id=?",[$title,$formatted,$id]);
   json_response(['ok'=>true,'message'=>'Tarefa reagendada para '.$date->format('d/m/Y').' às '.$date->format('H:i').'.']);
  }

  $context=(string)($_POST['context']??$task['type']);if(!in_array($context,['sales','collection'],true))throw new RuntimeException('Área da tarefa inválida.');
  $role=(string)($u['role']??'');if($role==='seller')$context='sales';elseif($role==='collector')$context='collection';
  $assignedId=max(0,(int)($_POST['assigned_user_id']??0));$assigned=$assignedId>0?DB::one("SELECT id,name,role FROM users WHERE id=? AND active=1",[$assignedId]):null;
  if(!$assigned)throw new RuntimeException('Selecione um responsável ativo.');
  $assignedRole=(string)$assigned['role'];
  if($context==='sales'&&!in_array($assignedRole,['seller','supervisor','admin'],true))throw new RuntimeException('Em Comercial, selecione vendedor, supervisor ou administrador.');
  if($context==='collection'&&!in_array($assignedRole,['collector','supervisor','admin'],true))throw new RuntimeException('Em Cobrança, selecione cobrança, supervisor ou administrador.');
  $typeCode=preg_replace('/[^a-z0-9_\-]/','',mb_strtolower(trim((string)($_POST['task_type_code']??''))));
  $allowed=array_column(task_type_options($context),'code');$sameType=$context===(string)$task['type']&&$typeCode===(string)($task['task_type_code']??'');if(!$sameType&&!in_array($typeCode,$allowed,true))throw new RuntimeException('Selecione um tipo de tarefa ativo.');
  DB::exec("UPDATE tasks SET assigned_user_id=?,type=?,task_type_code=?,title=?,due_at=?,updated_at=NOW() WHERE id=?",[$assignedId,$context,$typeCode,$title,$formatted,$id]);
  json_response(['ok'=>true,'message'=>'Tarefa atualizada com sucesso.']);
 }catch(Throwable $e){json_response(['ok'=>false,'error'=>$e->getMessage()],422);}
});
$router->post('/api/tasks/{id}/complete',function($p){
 Auth::requireLogin();CSRF::require($_POST['_token']??null);$u=Auth::user();$id=(int)$p['id'];
 try{
  $task=task_access_row($id,$u,true);if(!$task)throw new RuntimeException('Tarefa não encontrada, já encerrada ou sem permissão.');
  $title=trim((string)($_POST['title']??$task['title']));if($title===''||mb_strlen($title)>task_description_limit())throw new RuntimeException('Informe uma descrição de até 2.000 caracteres.');
  $result=preg_replace('/[^a-z0-9_\-]/','',mb_strtolower(trim((string)($_POST['completion_result_code']??''))));
  if($result!==''){$allowed=array_column(task_result_options((string)$task['type']),'code');if(!in_array($result,$allowed,true))throw new RuntimeException('Resultado de conclusão inválido.');}
  $notes=trim((string)($_POST['completion_notes']??''));if(mb_strlen($notes)>task_observation_limit())throw new RuntimeException('A observação final deve ter até 10.000 caracteres.');
  DB::exec("UPDATE tasks SET title=?,status='done',completion_result_code=?,completion_notes=?,completed_by_user_id=?,completed_at=NOW(),updated_at=NOW() WHERE id=? AND status='pending'",[$title,$result!==''?$result:null,$notes!==''?$notes:null,(int)$u['id'],$id]);
  json_response(['ok'=>true,'message'=>'Tarefa concluída com sucesso.']);
 }catch(Throwable $e){json_response(['ok'=>false,'error'=>$e->getMessage()],422);}
});

$router->post('/agenda/create',function(){
 Auth::requireLogin();CSRF::require($_POST['_token']??null);
 $u=Auth::user();$role=(string)($u['role']??'');$teamAgenda=in_array($role,['admin','supervisor'],true);$sellerCanDirect=$role==='seller';
 $clientId=max(0,(int)($_POST['client_id']??0));
 $requestedAssigned=max(0,(int)($_POST['assigned_user_id']??0));
 $assignedId=($teamAgenda||$sellerCanDirect)?($requestedAssigned>0?$requestedAssigned:(int)$u['id']):(int)$u['id'];
 $type=(string)($_POST['type']??'sales');if(!in_array($type,['sales','collection'],true))$type='sales';
 if($role==='collector')$type='collection';
 if($role==='seller'&&$assignedId!==(int)$u['id'])$type='sales';
 $title=trim((string)($_POST['title']??''));$value=trim((string)($_POST['due_at']??''));$date=DateTime::createFromFormat('Y-m-d\TH:i',$value);
 try{
  [$generalSql,$generalParams]=client_segment_filter('general','clients');
  $client=$clientId>0?DB::one("SELECT id,name,seller_omie_code FROM clients WHERE id=? AND active=1 AND ".$generalSql,array_merge([$clientId],$generalParams)):null;
  if(!$client)throw new RuntimeException('Selecione um cliente comercial válido.');

  $assignedUser=$assignedId>0?DB::one("SELECT id,name,role,seller_omie_code FROM users WHERE id=? AND active=1",[$assignedId]):null;
  if(!$assignedUser)throw new RuntimeException('Selecione um responsável ativo.');

  if($sellerCanDirect){
   if((string)$assignedUser['role']!=='seller')throw new RuntimeException('O agendamento direcionado comercial deve ser enviado para um consultor de vendas.');
   $effectiveSeller=ClientPortfolioService::effectiveSellerCode($clientId);
   $ownOrShared=$effectiveSeller===''||$effectiveSeller===(string)($u['seller_omie_code']??'');
   if(!$ownOrShared&&$assignedId===(int)$u['id'])throw new RuntimeException('Este cliente pertence a outra carteira. Direcione o compromisso para outro consultor.');
  }

  if($teamAgenda){
   if($type==='sales'&&(string)$assignedUser['role']==='collector')throw new RuntimeException('Para compromisso comercial, selecione um usuário de vendas.');
   if($type==='collection'&&(string)$assignedUser['role']==='seller')throw new RuntimeException('Para compromisso de cobrança, selecione um usuário da cobrança.');
  }

  if($title===''||mb_strlen($title)>task_description_limit())throw new RuntimeException('Informe uma descrição de até 2.000 caracteres.');
  if(!$date||$date->format('Y-m-d\TH:i')!==$value||$date->getTimestamp()<time()-60)throw new RuntimeException('Informe uma data e hora futura válida.');

  ensure_task_detail_columns();DB::exec("INSERT INTO tasks(client_id,assigned_user_id,created_by_user_id,type,task_type_code,title,due_at,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,'pending',NOW(),NOW())",[$clientId,$assignedId,(int)$u['id'],$type,'return',$title,$date->format('Y-m-d H:i:00')]);
  $directed=$assignedId!==(int)$u['id'];
  $_SESSION['agenda_flash']=['type'=>'success','message'=>$directed
   ?'Agendamento de '.$client['name'].' enviado para '.$assignedUser['name'].' em '.$date->format('d/m/Y').' às '.$date->format('H:i').'. A carteira do cliente não foi alterada.'
   :'Novo compromisso incluído na agenda.'];
 }catch(Throwable $e){$_SESSION['agenda_flash']=['type'=>'danger','message'=>$e->getMessage()];}
 redirect('/agenda');
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
 $createdDate=trim((string)($_POST['created_date']??''));if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$createdDate))$params['created_date']=$createdDate;
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
 $createdDate=trim((string)($_POST['created_date']??''));if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$createdDate))$params['created_date']=$createdDate;
 redirect('/agenda'.($params?'?'.http_build_query($params):''));
});
$router->post('/agenda/{id}/edit',function($p){
 Auth::requireLogin();CSRF::require($_POST['_token']??null);
 $u=Auth::user();$id=(int)$p['id'];$teamAgenda=in_array((string)$u['role'],['admin','supervisor'],true);
 $title=trim((string)($_POST['title']??''));
 if($title===''||mb_strlen($title)>task_description_limit()){
  $_SESSION['agenda_flash']=['type'=>'danger','message'=>'Informe uma descrição de até 2.000 caracteres.'];
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
 $createdDate=trim((string)($_POST['created_date']??''));if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$createdDate))$params['created_date']=$createdDate;
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
 $createdDate=trim((string)($_POST['created_date']??''));if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$createdDate))$params['created_date']=$createdDate;
 redirect('/agenda'.($params?'?'.http_build_query($params):''));
});

$router->get('/admin',function(){
 Auth::requireRole('admin');
 $userStats=DB::one("SELECT COUNT(*) users,SUM(active=1) active_users,SUM(active=1 AND role='seller') sellers,SUM(active=1 AND role='collector') collectors FROM users")?:[];
 $taskStats=DB::one("SELECT SUM(status='pending' AND due_at>=CURDATE() AND due_at<CURDATE()+INTERVAL 1 DAY) today_tasks,SUM(status='pending' AND due_at<CURDATE()) overdue_tasks FROM tasks")?:[];
 $stats=[
  'users'=>(int)($userStats['users']??0),
  'active_users'=>(int)($userStats['active_users']??0),
  'sellers'=>(int)($userStats['sellers']??0),
  'collectors'=>(int)($userStats['collectors']??0),
  'sync_errors'=>(int)(DB::scalar("SELECT COUNT(*) FROM sync_state WHERE last_error IS NOT NULL AND TRIM(last_error)<>''")??0),
  'monitored'=>count(contact_monitoring_user_ids()??[]),
  'open_opportunities'=>0,
  'pipeline_value'=>0.0,
  'today_tasks'=>(int)($taskStats['today_tasks']??0),
  'overdue_tasks'=>(int)($taskStats['overdue_tasks']??0),
  'open_collection'=>(float)(DB::scalar("SELECT COALESCE(SUM(open_amount),0) FROM collection_cases WHERE status='open'")??0),
 ];
 if(sales_flow_enabled()){
  try{
   ensure_sales_flow_tables();
   $oppStats=DB::one("SELECT COUNT(*) open_opportunities,COALESCE(SUM(estimated_value),0) pipeline_value FROM opportunities WHERE status='open'")?:[];
   $stats['open_opportunities']=(int)($oppStats['open_opportunities']??0);
   $stats['pipeline_value']=(float)($oppStats['pipeline_value']??0);
  }catch(Throwable){}
 }
 render('admin_center',[
  'stats'=>$stats,
  'flowEnabled'=>sales_flow_enabled(),
  'syncRows'=>DB::all("SELECT module_key,last_success_at,last_error FROM sync_state ORDER BY module_key"),
  'taskResultCount'=>count(task_result_catalog())
 ]);
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
  'taskResults'=>task_result_catalog(),'taskTypes'=>task_type_catalog(),'contactChannels'=>contact_channel_catalog()
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
$router->post('/settings/contact-channels',function(){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 try{
  $label=trim((string)($_POST['label']??''));if($label===''||mb_strlen($label)>80)throw new RuntimeException('Informe um nome de canal com até 80 caracteres.');
  $contexts=array_values(array_intersect(['sales','collection'],array_map('strval',(array)($_POST['contexts']??[]))));if(!$contexts)throw new RuntimeException('Selecione onde o canal será utilizado.');
  $catalog=contact_channel_catalog();foreach($catalog as $item)if(mb_strtolower(trim((string)$item['label']))===mb_strtolower($label))throw new RuntimeException('Já existe um canal com esse nome.');
  $code='custom_'.substr(hash('sha256',mb_strtolower($label).'|'.date('c').'|'.Auth::id()),0,12);
  $catalog[]=['code'=>$code,'label'=>$label,'contexts'=>$contexts,'active'=>true,'system'=>false];
  save_contact_channel_catalog($catalog);
  $_SESSION['settings_flash']=['type'=>'success','message'=>'Canal “'.$label.'” criado e disponibilizado nos atendimentos selecionados.'];
 }catch(Throwable $e){$_SESSION['settings_flash']=['type'=>'danger','message'=>'Não foi possível criar o canal: '.$e->getMessage()];}
 redirect('/settings#contact-channels');
});
$router->post('/settings/contact-channels/{code}/toggle',function($p){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 $code=(string)($p['code']??'');$catalog=contact_channel_catalog();$found=false;$turningOff=false;
 foreach($catalog as &$item)if((string)$item['code']===$code){$turningOff=!empty($item['active']);$item['active']=empty($item['active']);$found=true;break;}unset($item);
 if(!$found){$_SESSION['settings_flash']=['type'=>'danger','message'=>'Canal não encontrado.'];redirect('/settings#contact-channels');}
 if($turningOff){
  foreach(['sales'=>'Comercial','collection'=>'Cobrança'] as $context=>$label){
   $active=array_filter($catalog,static fn($item)=>!empty($item['active'])&&in_array($context,(array)($item['contexts']??[]),true));
   if(!$active){$_SESSION['settings_flash']=['type'=>'danger','message'=>'Mantenha pelo menos um canal ativo para '.$label.'.'];redirect('/settings#contact-channels');}
  }
 }
 save_contact_channel_catalog($catalog);$_SESSION['settings_flash']=['type'=>'success','message'=>'Disponibilidade do canal atualizada.'];
 redirect('/settings#contact-channels');
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

$router->post('/settings/task-types',function(){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 try{
  $label=trim((string)($_POST['label']??''));if($label===''||mb_strlen($label)>80)throw new RuntimeException('Informe um nome de tipo com até 80 caracteres.');
  $contexts=array_values(array_intersect(['sales','collection'],array_map('strval',(array)($_POST['contexts']??[]))));if(!$contexts)throw new RuntimeException('Selecione onde o tipo será utilizado.');
  $catalog=task_type_catalog();foreach($catalog as $item)if(mb_strtolower(trim((string)$item['label']))===mb_strtolower($label))throw new RuntimeException('Já existe um tipo de tarefa com esse nome.');
  $code='custom_'.substr(hash('sha256',mb_strtolower($label).'|'.date('c').'|'.Auth::id()),0,12);$catalog[]=['code'=>$code,'label'=>$label,'contexts'=>$contexts,'active'=>true,'system'=>false];
  save_task_type_catalog($catalog);$_SESSION['settings_flash']=['type'=>'success','message'=>'Tipo de tarefa “'.$label.'” criado.'];
 }catch(Throwable $e){$_SESSION['settings_flash']=['type'=>'danger','message'=>'Não foi possível criar o tipo de tarefa: '.$e->getMessage()];}
 redirect('/settings#task-types');
});
$router->post('/settings/task-types/{code}/toggle',function($p){
 Auth::requireRole('admin','supervisor');CSRF::require($_POST['_token']??null);
 $code=(string)($p['code']??'');$catalog=task_type_catalog();$found=false;
 foreach($catalog as &$item)if((string)$item['code']===$code){$item['active']=empty($item['active']);$found=true;break;}unset($item);
 if($found){save_task_type_catalog($catalog);$_SESSION['settings_flash']=['type'=>'success','message'=>'Disponibilidade do tipo de tarefa atualizada.'];}
 else $_SESSION['settings_flash']=['type'=>'danger','message'=>'Tipo de tarefa não encontrado.'];
 redirect('/settings#task-types');
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
 $locked=false;$status=200;$payload=[];
 try{
  $locked=SyncService::acquireLock($module);
  if(!$locked)throw new RuntimeException('Este módulo já está sendo sincronizado em outra aba ou sessão. Aguarde o lote atual terminar e use Retomar.');
  if($action==='reset')$payload=['ok'=>true]+SyncService::resetState($module);
  else{
   if($action==='catchup'&&$page<=1){SyncService::prepareCatchup($module);$page=1;}
   if($action==='last5'&&$page<=1){SyncService::prepareLastFiveDays($module);$page=1;}
   if($action==='period'&&$page<=1){SyncService::preparePeriod($module,(string)($_POST['date_from']??''),(string)($_POST['date_to']??''));$page=1;}
   if($action==='full'&&$page<=1){SyncService::prepareFull($module);$page=1;}
   if($action==='reconcile_clients'&&$page<=1){if($module!=='clients')throw new RuntimeException('A reconciliação completa está disponível somente para Clientes.');SyncService::prepareClientReconciliation();$page=1;}
   if($action==='product_obs'&&$page<=1){if($module!=='products')throw new RuntimeException('A atualização de observações está disponível somente para Produtos.');SyncService::prepareProductObservations();$page=1;}
   if($action==='resume'&&$page<=0)$page=SyncService::resumePage($module);
   $payload=['ok'=>true,'action'=>$action]+SyncService::run($module,max(1,$page));
  }
 }catch(Throwable $e){
  SyncService::recordError($module,$e->getMessage());
  $status=422;$payload=['ok'=>false,'module'=>$module,'action'=>$action,'error'=>$e->getMessage()];
 }finally{if($locked)SyncService::releaseLock($module);}
 json_response($payload,$status);
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

$router->post('/api/freight/quote',function(){
 Auth::requireRole('admin','supervisor','seller');CSRF::require($_POST['_token']??null);
 try{json_response(['ok'=>true]+FreightQuoteService::quote($_POST,Auth::user()));}
 catch(Throwable $e){json_response(['ok'=>false,'error'=>$e->getMessage()],422);}
});

$router->get('/api/contact-monitoring/datatable',function(){
 Auth::requireRole('admin','supervisor');
 $draw=max(0,(int)($_GET['draw']??0));$start=max(0,(int)($_GET['start']??0));$length=max(1,min(50,(int)($_GET['length']??5)));
 $context=contact_monitoring_context($_GET);$baseWhere=$context['where'];$baseParams=$context['params'];$effectiveSellerSql=client_effective_seller_sql('c');
 $portfolioMonth=ClientPortfolioService::monthRef();$portfolioJoin=" LEFT JOIN client_portfolio_assignments pa_monitor ON pa_monitor.client_id=c.id AND pa_monitor.month_ref='".$portfolioMonth."'";
 $effectiveSellerExpr="CASE WHEN pa_monitor.id IS NOT NULL THEN pa_monitor.seller_omie_code ELSE c.seller_omie_code END";
 $baseWhereSql=str_replace($effectiveSellerSql,$effectiveSellerExpr,implode(' AND ',$baseWhere));
 $recordsTotal=(int)(DB::scalar("SELECT COUNT(*) FROM clients c".$portfolioJoin." WHERE ".$baseWhereSql,$baseParams)??0);
 $where=$baseWhere;$params=$baseParams;$searchInput=$_GET['search']??[];$search=trim((string)(is_array($searchInput)?($searchInput['value']??''):''));
 if($search!==''){
  $extraFields=['('.$effectiveSellerExpr.')',
   "(SELECT search_seller.name FROM sellers search_seller WHERE search_seller.omie_code=(".$effectiveSellerExpr.") LIMIT 1)",
   "(SELECT search_user.name FROM collection_cases search_case JOIN users search_user ON search_user.id=search_case.assigned_user_id WHERE search_case.client_id=c.id LIMIT 1)"];
  [$searchSql,$searchParams]=crm_search_filter($search,array_merge(client_search_fields('c'),$extraFields));
  if($searchSql!==''){$where[]=$searchSql;array_push($params,...$searchParams);}
 }
 $whereSql=str_replace($effectiveSellerSql,$effectiveSellerExpr,implode(' AND ',$where));
 $recordsFiltered=$search===''?$recordsTotal:(int)(DB::scalar("SELECT COUNT(*) FROM clients c".$portfolioJoin." WHERE ".$whereSql,$params)??0);
 $orderInput=$_GET['order']??[];$orderRow=is_array($orderInput)&&isset($orderInput[0])&&is_array($orderInput[0])?$orderInput[0]:[];$orderColumn=(int)($orderRow['column']??2);$direction=strtolower((string)($orderRow['dir']??'desc'))==='asc'?'ASC':'DESC';
 $lastContactOrder="GREATEST(COALESCE(la.created_at,'1000-01-01'),COALESCE(lca.created_at,'1000-01-01'))";
 $orderColumns=[0=>'c.name',1=>'COALESCE(s.name,collection_user.name)',2=>$lastContactOrder,3=>'DATEDIFF(NOW(),'.$lastContactOrder.')',4=>'nt.due_at',5=>'COALESCE(la.result,lca.result)',6=>'nt.due_at'];
 $orderSql=($orderColumns[$orderColumn]??$orderColumns[2]).' '.$direction.',c.name ASC';
 $rows=DB::all(
  "SELECT c.id,c.name,c.document,c.city,c.uf,c.phone,c.seller_omie_code,(".$effectiveSellerExpr.") effective_seller_code,
          s.name seller_name,portfolio_user.id portfolio_user_id,portfolio_user.name portfolio_user_name,
          collection_user.id collection_user_id,collection_user.name collection_user_name,
          la.id last_activity_id,la.channel last_channel,la.result last_result,la.notes last_notes,la.created_at last_contact_at,activity_user.name last_contact_user,
          lca.id last_collection_action_id,lca.channel collection_channel,lca.result collection_result,lca.notes collection_notes,lca.created_at collection_contact_at,collection_author.name collection_contact_user,
          ((SELECT COUNT(*) FROM activities ac WHERE ac.client_id=c.id)+(SELECT COUNT(*) FROM collection_actions cac WHERE cac.client_id=c.id)) contact_count,
          nt.id next_task_id,nt.title next_title,nt.due_at next_due_at,next_user.id next_user_id,next_user.name next_user_name
   FROM clients c
   ".$portfolioJoin."
   LEFT JOIN sellers s ON s.omie_code=(".$effectiveSellerExpr.")
   LEFT JOIN users portfolio_user ON portfolio_user.id=(SELECT pu.id FROM users pu WHERE pu.role='seller' AND pu.active=1 AND pu.seller_omie_code=(".$effectiveSellerExpr.") ORDER BY pu.id LIMIT 1)
   LEFT JOIN collection_cases cc ON cc.client_id=c.id
   LEFT JOIN users collection_user ON collection_user.id=cc.assigned_user_id
   LEFT JOIN activities la ON la.id=(SELECT a2.id FROM activities a2 WHERE a2.client_id=c.id ORDER BY a2.created_at DESC,a2.id DESC LIMIT 1)
   LEFT JOIN users activity_user ON activity_user.id=la.user_id
   LEFT JOIN collection_actions lca ON lca.id=(SELECT ca2.id FROM collection_actions ca2 WHERE ca2.client_id=c.id ORDER BY ca2.created_at DESC,ca2.id DESC LIMIT 1)
   LEFT JOIN users collection_author ON collection_author.id=lca.author_user_id
   LEFT JOIN tasks nt ON nt.id=(SELECT t2.id FROM tasks t2 WHERE t2.client_id=c.id AND t2.type IN ('sales','collection') AND t2.status='pending' ORDER BY t2.due_at,t2.id LIMIT 1)
   LEFT JOIN users next_user ON next_user.id=nt.assigned_user_id
   WHERE ".$whereSql." ORDER BY ".$orderSql." LIMIT ".$length." OFFSET ".$start,
  $params
 );
 json_response(['draw'=>$draw,'recordsTotal'=>$recordsTotal,'recordsFiltered'=>$recordsFiltered,'data'=>array_map('contact_monitoring_row_cells',$rows)]);
});

$router->post('/api/clients/bulk',function(){
 Auth::requireRole('admin','supervisor');
 $input=json_decode((string)file_get_contents('php://input'),true);if(!is_array($input))$input=$_POST;
 CSRF::require($input['_token']??null);
 try{
  $u=Auth::user();$selection=(string)($input['selection_mode']??'selected');
  if(!in_array($selection,['selected','filtered'],true))throw new RuntimeException('Seleção inválida.');
  $action=(string)($input['action']??'apply');
  if(!in_array($action,['apply','sync','apply_sync'],true))throw new RuntimeException('Ação inválida.');
   $segment=(string)($input['segment']??'all');if($segment!=='all'&&!isset(client_segment_catalog()[$segment]))$segment='all';
  $clientUfs=client_filter_ufs($input['ufs']??($input['uf']??[]));$uf=count($clientUfs)===1?$clientUfs[0]:'';
  $ddds=$uf!==''?client_portfolio_ddds($input['ddds']??[],$uf):[];
  $filterTags=client_filter_tags($input['filter_tags']??($input['tag']??[]));
  $sellerFilter=trim((string)($input['seller_filter']??''));if(mb_strlen($sellerFilter)>80)$sellerFilter='';
   $portfolioMonth=ClientPortfolioService::monthRef($input['month']??null);$effectiveSellerSql=client_effective_seller_sql('c',$portfolioMonth);
  $search=trim((string)($input['search']??''));if(mb_strlen($search)>190)$search=mb_substr($search,0,190);
  $cursor=max(0,(int)($input['cursor']??0));
  $ids=[];foreach((array)($input['client_ids']??[]) as $id){$id=(int)$id;if($id>0)$ids[$id]=$id;}
  $excluded=[];foreach((array)($input['excluded_ids']??[]) as $id){$id=(int)$id;if($id>0)$excluded[$id]=$id;}
  if($selection==='selected'&&!$ids)throw new RuntimeException('Selecione pelo menos um cliente.');

  [$segmentSql,$segmentParams]=client_segment_filter($segment,'c');$where=['c.active=1',$segmentSql];$params=$segmentParams;
  if($clientUfs){$where[]='UPPER(TRIM(c.uf)) IN ('.implode(',',array_fill(0,count($clientUfs),'?')).')';array_push($params,...$clientUfs);}
  if($ddds){$where[]=client_ddd_sql('c').' IN ('.implode(',',array_fill(0,count($ddds),'?')).')';array_push($params,...$ddds);}
  if($filterTags){$tagKeys=array_map(static fn($v)=>mb_strtolower((string)$v,'UTF-8'),$filterTags);$where[]=client_tags_any_filter_sql('c',count($tagKeys));array_push($params,...$tagKeys);}
   if($sellerFilter==='__none__')$where[]="((".$effectiveSellerSql.") IS NULL OR TRIM((".$effectiveSellerSql."))='')";
   elseif($sellerFilter!==''){$where[]='('.$effectiveSellerSql.')=?';$params[]=$sellerFilter;}
  if($search!==''){[$searchSql,$searchParams]=crm_search_filter($search,array_merge(client_search_fields('c'),['search_effective.name','('.$effectiveSellerSql.')']));if($searchSql!==''){$where[]=$searchSql;array_push($params,...$searchParams);}}
  if($selection==='selected'){$where[]='c.id IN ('.implode(',',array_fill(0,count($ids),'?')).')';array_push($params,...array_values($ids));}
  if($excluded){$where[]='c.id NOT IN ('.implode(',',array_fill(0,count($excluded),'?')).')';array_push($params,...array_values($excluded));}
  $portfolioJoin=" LEFT JOIN client_portfolio_assignments pa_bulk ON pa_bulk.client_id=c.id AND pa_bulk.month_ref='".$portfolioMonth."'";
  $effectiveSellerExpr="CASE WHEN pa_bulk.id IS NOT NULL THEN pa_bulk.seller_omie_code ELSE c.seller_omie_code END";
  $sellerJoin=" LEFT JOIN sellers search_effective ON search_effective.omie_code=(".$effectiveSellerExpr.")";
  $baseWhere=$where;$baseParams=$params;$baseWhereSql=str_replace($effectiveSellerSql,$effectiveSellerExpr,implode(' AND ',$baseWhere));
  $total=(int)(DB::scalar("SELECT COUNT(*) FROM clients c".$portfolioJoin.$sellerJoin." WHERE ".$baseWhereSql,$baseParams)??0);
  $where[]='c.id>?';$params[]=$cursor;
  $changeSeller=!empty($input['change_seller']);$sellerCode=trim((string)($input['seller_code']??''));if($sellerCode==='__none__')$sellerCode='';
  $tagOperation=(string)($input['tag_operation']??'none');$requestedTags=ClientService::normalizeTags($input['tags']??[]);
  if($changeSeller&&$sellerCode!==''){
   $targetSeller=DB::one("SELECT 1 FROM sellers WHERE omie_code=? AND active=1",[$sellerCode]);
   if(!$targetSeller)throw new RuntimeException('Selecione um vendedor ativo.');
  }
  if(!in_array($tagOperation,['none','add','remove','replace'],true))throw new RuntimeException('Operação de tags inválida.');
  if(in_array($tagOperation,['add','remove'],true)&&!$requestedTags)throw new RuntimeException('Informe ao menos uma tag.');
  if(in_array($action,['apply','apply_sync'],true)&&!$changeSeller&&$tagOperation==='none')throw new RuntimeException('Escolha uma alteração de vendedor ou tags.');

  $limit=$action==='apply'?500:5;
  $whereSql=str_replace($effectiveSellerSql,$effectiveSellerExpr,implode(' AND ',$where));
  $rows=DB::all("SELECT c.id,c.name FROM clients c".$portfolioJoin.$sellerJoin." WHERE ".$whereSql." ORDER BY c.id ASC LIMIT ".$limit,$params);
  $success=0;$failed=0;$errors=[];$nextCursor=$cursor;
  foreach($rows as $row){
   $id=(int)$row['id'];$nextCursor=max($nextCursor,$id);
   try{
    if(in_array($action,['apply','apply_sync'],true))ClientService::applyBulkLocal($id,$changeSeller,$sellerCode,$tagOperation,$requestedTags,false);
    if(in_array($action,['sync','apply_sync'],true))ClientService::syncLocalWithOmie($id,$u);
    $success++;
   }catch(Throwable $e){if(in_array($action,['sync','apply_sync'],true))ClientService::markSyncError($id,$e->getMessage());$failed++;if(count($errors)<20)$errors[]=['id'=>$id,'name'=>(string)$row['name'],'message'=>$e->getMessage()];}
  }
  $done=count($rows)<$limit||$nextCursor===0||(int)(DB::scalar("SELECT COUNT(*) FROM clients c".$portfolioJoin.$sellerJoin." WHERE ".$baseWhereSql." AND c.id>?",array_merge($baseParams,[$nextCursor]))??0)===0;
  client_cache_invalidate();
  json_response(['success'=>true,'processed'=>count($rows),'succeeded'=>$success,'failed'=>$failed,'errors'=>$errors,'next_cursor'=>$nextCursor,'done'=>$done,'total'=>$total]);
 }catch(Throwable $e){json_response(['success'=>false,'error'=>$e->getMessage()],422);}
});

$router->get('/api/clients/datatable',function(){
 Auth::requireRole('admin','supervisor','seller');
 $u=Auth::user();
 $draw=max(0,(int)($_GET['draw']??0));
 $start=max(0,(int)($_GET['start']??0));
 $length=(int)($_GET['length']??5);$length=$length<1?5:min(100,$length);
 $defaultSegment=in_array((string)$u['role'],['admin','supervisor'],true)?'all':'general';
 $segment=(string)($_GET['segment']??$defaultSegment);if($segment!=='all'&&!isset(client_segment_catalog()[$segment]))$segment=$defaultSegment;
 if($segment!=='general'&&!in_array((string)$u['role'],['admin','supervisor'],true)){http_response_code(403);json_response(['error'=>'Segmento restrito à gestão.']);}
 $portfolioOnly=$u['role']==='seller'&&(string)($_GET['portfolio']??'')==='mine';
 if($portfolioOnly)$segment='general';
 $canManage=in_array((string)$u['role'],['admin','supervisor'],true);
 $crmStatus=$canManage?(string)($_GET['crm_status']??'active'):'active';if(!in_array($crmStatus,['active','inactive','all'],true))$crmStatus='active';
 if($portfolioOnly)$crmStatus='active';
 $clientScope=$portfolioOnly?'mine':(($u['role']==='seller'&&(string)($_GET['scope']??'all')==='unassigned')?'unassigned':'all');
 $clientUfs=client_filter_ufs($_GET['ufs']??($_GET['uf']??[]));
 $uf=count($clientUfs)===1?$clientUfs[0]:'';
 $ddds=$uf!==''?client_portfolio_ddds($_GET['ddds']??[],$uf):[];
 $clientTagsSelected=client_filter_tags($_GET['tags']??($_GET['tag']??[]));$tag=count($clientTagsSelected)===1?$clientTagsSelected[0]:'';
 $sellerFilter=trim((string)($_GET['seller_filter']??''));if(mb_strlen($sellerFilter)>80)$sellerFilter='';
 $portfolioMonth=ClientPortfolioService::monthRef($_GET['month']??null);$effectiveSellerSql=client_effective_seller_sql('c',$portfolioMonth);

 [$segmentSql,$segmentParams]=client_segment_filter($segment,'c');
 $baseWhere=['c.active=1',$segmentSql];$baseParams=$segmentParams;
 if($crmStatus==='active')$baseWhere[]='c.crm_inactive=0';elseif($crmStatus==='inactive')$baseWhere[]='c.crm_inactive=1';
 if(($u['role']??'')==='seller'){
  if($portfolioOnly){$baseWhere[]='('.$effectiveSellerSql.')=?';$baseParams[]=trim((string)($u['seller_omie_code']??''))?:'__NO_SELLER_LINK__';}
  elseif($clientScope==='unassigned')$baseWhere[]="((".$effectiveSellerSql.") IS NULL OR TRIM((".$effectiveSellerSql."))='')";
 }
 if($clientUfs){$baseWhere[]='UPPER(TRIM(c.uf)) IN ('.implode(',',array_fill(0,count($clientUfs),'?')).')';array_push($baseParams,...$clientUfs);}
 if($ddds){$baseWhere[]=client_ddd_sql('c').' IN ('.implode(',',array_fill(0,count($ddds),'?')).')';array_push($baseParams,...$ddds);}
 if($clientTagsSelected){$tagKeys=array_map(static fn($v)=>mb_strtolower((string)$v,'UTF-8'),$clientTagsSelected);$baseWhere[]=client_tags_any_filter_sql('c',count($tagKeys));array_push($baseParams,...$tagKeys);}
 if($sellerFilter==='__none__')$baseWhere[]="((".$effectiveSellerSql.") IS NULL OR TRIM((".$effectiveSellerSql."))='')";
 elseif($sellerFilter!==''){$baseWhere[]='('.$effectiveSellerSql.')=?';$baseParams[]=$sellerFilter;}
 $portfolioJoin=" LEFT JOIN client_portfolio_assignments pa ON pa.client_id=c.id AND pa.month_ref='".$portfolioMonth."'";
 $effectiveSellerExpr="CASE WHEN pa.id IS NOT NULL THEN pa.seller_omie_code ELSE c.seller_omie_code END";
 $baseSqlWhere=str_replace($effectiveSellerSql,$effectiveSellerExpr,implode(' AND ',$baseWhere));
 $recordsTotal=(int)(DB::scalar("SELECT COUNT(*) FROM clients c".$portfolioJoin." WHERE ".$baseSqlWhere,$baseParams)??0);

 $where=$baseWhere;$params=$baseParams;
 $searchInput=$_GET['search']??[];
 $search=trim((string)(is_array($searchInput)?($searchInput['value']??''):''));
 if($search!==''){
  [$searchSql,$searchParams]=crm_search_filter($search,array_merge(client_search_fields('c'),['es.name','('.$effectiveSellerSql.')']));
  if($searchSql!==''){$where[]=$searchSql;array_push($params,...$searchParams);}
 }
 $sqlWhere=str_replace($effectiveSellerSql,$effectiveSellerExpr,implode(' AND ',$where));
 $sellerJoin=" LEFT JOIN sellers es ON es.omie_code=(".$effectiveSellerExpr.")";
 $recordsFiltered=$search===''?$recordsTotal:(int)(DB::scalar("SELECT COUNT(*) FROM clients c".$portfolioJoin.$sellerJoin." WHERE ".$sqlWhere,$params)??0);
 $canBulk=$canManage&&$crmStatus==='active';
 $lastContactOrder="GREATEST(COALESCE((SELECT MAX(a_order.created_at) FROM activities a_order WHERE a_order.client_id=c.id),'1000-01-01'),COALESCE((SELECT MAX(ca_order.created_at) FROM collection_actions ca_order WHERE ca_order.client_id=c.id),'1000-01-01'))";
 $orderColumns=$canBulk?['c.id','c.name','c.city','('.$effectiveSellerExpr.')','c.id','m.last_purchase_at',$lastContactOrder,'m.last_purchase_at','m.revenue_12m','c.id']:['c.name','c.city','('.$effectiveSellerExpr.')','c.id','m.last_purchase_at',$lastContactOrder,'m.last_purchase_at','m.revenue_12m','c.id'];
 $orderInput=$_GET['order']??[];
 $orderIndex=(int)(is_array($orderInput)?($orderInput[0]['column']??0):0);
 $orderBy=$orderColumns[$orderIndex]??'c.name';
 $orderDirection=strtolower((string)(is_array($orderInput)?($orderInput[0]['dir']??'asc'):'asc'))==='desc'?'DESC':'ASC';
 $rows=DB::all(
  "SELECT c.*,s.name seller_name,os.name omie_seller_name,(".$effectiveSellerExpr.") effective_seller_code,
          es.name effective_seller_name,pa.id portfolio_assignment_id,pa.seller_omie_code portfolio_seller_code,
          m.last_purchase_at,m.revenue_12m,m.orders_12m,m.avg_interval_days,
          (SELECT MAX(a_last.created_at) FROM activities a_last WHERE a_last.client_id=c.id) last_activity_at,
          (SELECT MAX(ca_last.created_at) FROM collection_actions ca_last WHERE ca_last.client_id=c.id) last_collection_at
   FROM clients c
   LEFT JOIN client_metrics m ON m.client_id=c.id
   ".$portfolioJoin."
   LEFT JOIN sellers s ON s.omie_code=c.seller_omie_code
   LEFT JOIN sellers os ON os.omie_code=c.omie_seller_code
   ".$sellerJoin."
   WHERE ".$sqlWhere." ORDER BY ".$orderBy." ".$orderDirection.",c.id ASC LIMIT ".$length." OFFSET ".$start,
  $params
 );

 $data=[];$token=CSRF::token();
 foreach($rows as $row){
  $id=(int)$row['id'];$name=(string)$row['name'];$document=(string)($row['document']??'');$crmInactive=!empty($row['crm_inactive']);
  $canEdit=(!$crmInactive)&&($canManage||$u['role']==='seller');
  $effectiveSeller=trim((string)($row['effective_seller_code']??''));$unassigned=$effectiveSeller==='';
  $canOpen=$canManage||(!$crmInactive&&$u['role']==='seller');
  $initial=mb_strtoupper(mb_substr($name,0,1));
  $inactiveBadge=$crmInactive?'<b class="client-crm-inactive-badge"><i class="fa-solid fa-user-slash"></i> Inativo no CRM</b>':'';
  $identity='<div class="client-table-identity'.($crmInactive?' is-inactive':'').'"><span>'.e($initial).'</span><div>'.($canOpen?'<a href="'.APP_URL.'/clients/'.$id.'"><strong>'.e($name).'</strong></a>':'<strong>'.e($name).'</strong>').$inactiveBadge.'<small>'.e($document!==''?$document:'Documento não informado').'</small></div></div>';
  $location=trim((string)($row['city']??'').' / '.(string)($row['uf']??''),' /');
  $phoneDigits=preg_replace('/\D+/','',(string)($row['phone']??''));$rowDdd=strlen($phoneDigits)>=2?substr($phoneDigits,0,2):'';
  $locationHtml='<span class="client-location"><i class="fa-solid fa-location-dot"></i>'.e($location!==''?$location:'Não informado').($rowDdd!==''?'<b>DDD '.e($rowDdd).'</b>':'').'</span>';
  $principalCode=trim((string)($row['seller_omie_code']??''));$omieCode=trim((string)($row['omie_seller_code']??''));$hasOverride=!empty($row['portfolio_assignment_id']);$diverged=$principalCode!==$omieCode;
  if($effectiveSeller!==''){$effectiveName=(string)($row['effective_seller_name']??$effectiveSeller);$meta=$hasOverride?'Carteira '.$portfolioMonth.' · principal '.(($row['seller_name']??'')?:($principalCode?:'sem vendedor')):($diverged?'Principal · Omie '.(($row['omie_seller_name']??'')?:($omieCode?:'sem vendedor')):'Principal alinhado com a Omie');$sellerHtml='<span class="client-seller'.($hasOverride?' monthly':'').($diverged?' divergent':'').'"><i class="fa-solid '.($hasOverride?'fa-calendar-check':'fa-user-tie').'"></i><span><strong>'.e($effectiveName).'</strong><small>'.e($meta).'</small></span></span>';}
  else $sellerHtml='<span class="client-seller unassigned"><i class="fa-solid fa-user-slash"></i><span><strong>Sem responsável</strong><small>'.($hasOverride?'Sem vendedor na carteira '.$portfolioMonth:($u['role']==='seller'?'Atendimento compartilhado':'Disponível para vincular')).'</small></span></span>';
  $rowTags=client_tags_from_raw($row['raw_json']??null);$visibleTags=array_slice($rowTags,0,3);
  $tagsHtml='<div class="client-tag-list" title="'.e(implode(', ',$rowTags)).'">';foreach($visibleTags as $rowTag)$tagsHtml.='<span>'.e($rowTag).'</span>';if(count($rowTags)>3)$tagsHtml.='<b>+'.(count($rowTags)-3).'</b>';if(!$rowTags)$tagsHtml.='<small>Sem tag</small>';$tagsHtml.='</div>';
  $cycle=CRMService::cycle($row['last_purchase_at']??null,(float)($row['avg_interval_days']??0));
  $cycleHtml='<span class="cycle cycle-'.e($cycle['status']).'">'.e($cycle['label']).'</span>';
  $orders=(int)($row['orders_12m']??0);
  $purchaseHtml='<strong>'.brdate($row['last_purchase_at']??null).'</strong><small>'.($orders>0?$orders.' pedido(s) em 12 meses':'Sem pedidos recentes').'</small>';
  $activityAt=trim((string)($row['last_activity_at']??''));$collectionAt=trim((string)($row['last_collection_at']??''));$lastContactAt=$activityAt===''?$collectionAt:($collectionAt===''?$activityAt:($activityAt>=$collectionAt?$activityAt:$collectionAt));
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
  if($canManage){
   if($crmInactive){
    $actions.='<form method="post" action="'.APP_URL.'/clients/'.$id.'/crm-status"><input type="hidden" name="_token" value="'.e($token).'"><input type="hidden" name="inactive" value="0"><button class="client-action client-action-reactivate" type="submit" title="Reativar no CRM" data-confirm="Reativar este cliente no CRM e devolvê-lo às carteiras e buscas operacionais?"><i class="fa-solid fa-user-check"></i><span>Reativar</span></button></form>';
   }else{
    $actions.='<form method="post" action="'.APP_URL.'/clients/'.$id.'/crm-status"><input type="hidden" name="_token" value="'.e($token).'"><input type="hidden" name="inactive" value="1"><button class="client-action client-action-inactivate" type="submit" title="Inativar somente no CRM" data-confirm="Inativar este cliente somente no CRM? Ele deixará de aparecer para vendedores e cobrança, sem alterar a Omie."><i class="fa-solid fa-user-slash"></i><span>Inativar</span></button></form>';
    $actions.='<button class="client-action client-action-sync" type="button" data-client-omie-one="'.$id.'" title="Atualizar este cadastro na Omie"><i class="fa-solid fa-cloud-arrow-up"></i><span>Omie</span></button>';
    if(str_starts_with((string)$row['omie_code'],'LOCAL-'))$actions.='<form method="post" action="'.APP_URL.'/clients/'.$id.'/delete-local"><input type="hidden" name="_token" value="'.e($token).'"><button class="client-action client-action-local" type="submit" title="Remover apenas do CRM" data-confirm="Excluir somente do CRM local? Esta ação será bloqueada se houver histórico relacionado."><i class="fa-solid fa-database"></i><span>CRM</span></button></form>';
    $actions.='<form method="post" action="'.APP_URL.'/clients/'.$id.'/delete"><input type="hidden" name="_token" value="'.e($token).'"><button class="client-action client-action-delete" type="submit" title="Excluir ou arquivar preservando histórico" data-confirm="Excluir este cliente? Se houver histórico no CRM, ele será preservado em arquivo."><i class="fa-regular fa-trash-can"></i><span>Excluir</span></button></form>';
   }
  }
  $actions.='</div>';
  $cells=[$identity,$locationHtml,$sellerHtml,$tagsHtml,$cycleHtml,$daysContactHtml,$purchaseHtml,'<strong class="client-revenue">'.money($row['revenue_12m']??0).'</strong>',$actions];
  if($canBulk)array_unshift($cells,'<label class="tdc-row-check" title="Selecionar cliente"><input type="checkbox" data-client-select value="'.$id.'"><span></span></label>');
  $data[]=$cells;
 }
 json_response(['draw'=>$draw,'recordsTotal'=>$recordsTotal,'recordsFiltered'=>$recordsFiltered,'data'=>$data]);
});

$router->get('/api/clients',function(){
 Auth::requireRole('admin','supervisor','seller','collector');$u=Auth::user();$q=trim((string)($_GET['q']??''));$broadScope=in_array((string)($_GET['scope']??''),['agenda','task'],true);
 [$segmentSql,$segmentParams]=client_segment_filter('general','clients');$effective=client_effective_seller_sql('clients');$w=['active=1','crm_inactive=0',$segmentSql];$p=$segmentParams;
 if($u['role']==='seller'&&!$broadScope){$w[]="((".$effective.")=? OR (".$effective.") IS NULL OR TRIM((".$effective."))='')";$p[]=$u['seller_omie_code'];}
 if($q!==''){[$searchSql,$searchParams]=crm_search_filter($q,array_merge(client_search_fields('clients'),['CAST(clients.id AS CHAR)']));if($searchSql!==''){$w[]=$searchSql;array_push($p,...$searchParams);}}
 $items=DB::all(
  "SELECT id,omie_code,JSON_UNQUOTE(JSON_EXTRACT(raw_json,'$.codigo_cliente_integracao')) client_integration_code,
          name,document,email,city,uf,
          (".$effective.") portfolio_seller_code,
          (SELECT ags.name FROM sellers ags WHERE ags.omie_code=(".$effective.") LIMIT 1) portfolio_seller_name,
          (SELECT assigned_user_id FROM collection_cases WHERE client_id=clients.id) collection_assigned_user_id
   FROM clients
   WHERE ".implode(' AND ',$w)."
   ORDER BY CASE WHEN (".$effective.")=? THEN 0 ELSE 1 END,name
   LIMIT 25",
  array_merge($p,[$u['role']==='seller'?(string)($u['seller_omie_code']??''):''])
 );
 json_response(['items'=>$items]);
});

$router->get('/api/orders/datatable',function(){
 Auth::requireRole('admin','supervisor','seller');$u=Auth::user();
 $draw=max(0,(int)($_GET['draw']??0));$start=max(0,(int)($_GET['start']??0));$length=max(1,min(100,(int)($_GET['length']??10)));
 $period=selected_date_period();$view=(string)($_GET['view']??'all');if(!in_array($view,['all','budget'],true))$view='all';
 $budgetCodes=OrderPolicy::budgetStageCodes();$stageFilter=trim((string)($_GET['stage']??''));if(mb_strlen($stageFilter)>20)$stageFilter='';
 $where=[];$params=[];
 if(!$period['all']){$where[]='o.order_date>=?';$where[]='o.order_date<?';array_push($params,$period['from'],$period['next']);}
 if(($u['role']??'')==='seller'){$where[]='o.seller_omie_code=?';$params[]=(string)($u['seller_omie_code']??'');}
 $budgetPlaceholders=implode(',',array_fill(0,count($budgetCodes),'?'));
 if($view==='budget'){$where[]='o.stage_code IN ('.$budgetPlaceholders.')';array_push($params,...$budgetCodes);}
 else{$where[]='(o.stage_code IS NULL OR o.stage_code NOT IN ('.$budgetPlaceholders.'))';array_push($params,...$budgetCodes);}
 if($stageFilter!==''){$where[]='o.stage_code=?';$params[]=$stageFilter;}
 $baseWhere=$where;$baseParams=$params;$baseSql=$baseWhere?implode(' AND ',$baseWhere):'1=1';
 $recordsTotal=(int)(DB::scalar("SELECT COUNT(*) FROM orders o WHERE ".$baseSql,$baseParams)??0);

 $searchInput=$_GET['search']??[];$search=trim((string)(is_array($searchInput)?($searchInput['value']??''):''));
 if($search!==''){
  [$searchSql,$searchParams]=crm_search_filter($search,array_merge(client_search_fields('c'),['o.number','o.omie_code','s.name','o.seller_omie_code','os.name','o.stage_code','o.status']));
  if($searchSql!==''){$where[]=$searchSql;array_push($params,...$searchParams);}
 }
 $sqlWhere=$where?implode(' AND ',$where):'1=1';
 $recordsFiltered=$search===''?$recordsTotal:(int)(DB::scalar(
  "SELECT COUNT(*) FROM orders o LEFT JOIN clients c ON c.omie_code=o.client_omie_code LEFT JOIN sellers s ON s.omie_code=o.seller_omie_code LEFT JOIN order_stages os ON os.code=o.stage_code WHERE ".$sqlWhere,$params
 )??0);

 $orderInput=$_GET['order'][0]??[];$orderIndex=max(0,(int)(is_array($orderInput)?($orderInput['column']??3):3));$orderDir=strtolower((string)(is_array($orderInput)?($orderInput['dir']??'desc'):'desc'))==='asc'?'ASC':'DESC';
 $orderColumns=['o.number','c.name','s.name','o.order_date','os.name','o.status','o.total','o.id'];$orderBy=$orderColumns[$orderIndex]??'o.order_date';
 $rows=DB::all(
  "SELECT o.id,o.omie_code,o.number,o.client_omie_code,o.seller_omie_code,o.order_date,o.forecast_date,o.total,o.status,o.stage_code,c.name client_name,s.name seller_name,os.name stage_name
   FROM orders o
   LEFT JOIN clients c ON c.omie_code=o.client_omie_code
   LEFT JOIN sellers s ON s.omie_code=o.seller_omie_code
   LEFT JOIN order_stages os ON os.code=o.stage_code
   WHERE ".$sqlWhere." ORDER BY ".$orderBy." ".$orderDir.",o.id DESC LIMIT ".$length." OFFSET ".$start,
  $params
 );
 $data=[];$token=CSRF::token();$canDelete=Auth::can('admin');
 foreach($rows as $o){
  $id=(int)$o['id'];$status=(string)($o['status']??'ATIVO');$upper=mb_strtoupper($status);$isBudget=in_array((string)($o['stage_code']??''),$budgetCodes,true);
  $statusClass=str_contains($upper,'CANCEL')?'cancelled':(str_contains($upper,'FATUR')?'billed':($isBudget?'budget':'active'));
  $orderCell='<a class="tdo-order-cell" href="'.APP_URL.'/orders/'.$id.'"><span class="tdo-order-icon"><i class="fa-solid fa-receipt"></i></span><span><strong>'.e($o['number']??'—').'</strong><small>'.e($o['omie_code']).'</small></span></a>';
  $clientName=(string)($o['client_name']??'');$clientCode=(string)($o['client_omie_code']??'');$clientCell='<strong>'.e($clientName!==''?$clientName:($clientCode!==''?$clientCode:'—')).'</strong>'.($clientName!==''&&$clientCode!==''?'<small>'.e($clientCode).'</small>':'');
  if(!empty($o['seller_name']))$sellerCell='<strong>'.e($o['seller_name']).'</strong><small>'.e($o['seller_omie_code']).'</small>';
  elseif(!empty($o['seller_omie_code']))$sellerCell='<strong>'.e($o['seller_omie_code']).'</strong><small>código do vendedor</small>';
  else $sellerCell='<span class="tdo-no-seller"><i class="fa-solid fa-circle-exclamation"></i>Sem vendedor</span>';
  $stageName=(string)($o['stage_name']??'');$stageCode=(string)($o['stage_code']??'');$stageCell='<span class="tdo-stage"><strong>'.e($stageName!==''?$stageName:($stageCode!==''?$stageCode:'—')).'</strong>'.($stageName!==''&&$stageCode!==''?'<small>'.e($stageCode).'</small>':'').'</span>';
  $actions='<div class="tdo-actions"><a class="tdo-icon-btn" href="'.APP_URL.'/orders/'.$id.'" title="Visualizar"><i class="fa-regular fa-eye"></i></a>';
  if($isBudget)$actions.='<a class="tdo-icon-btn" href="'.APP_URL.'/orders/'.$id.'/edit" title="Editar proposta"><i class="fa-regular fa-pen-to-square"></i></a>';
  $actions.='<a class="tdo-icon-btn" href="'.APP_URL.'/orders/'.$id.'/pdf" target="_blank" rel="noopener" title="Gerar PDF"><i class="fa-regular fa-file-pdf"></i></a><a class="tdo-icon-btn" href="'.APP_URL.'/orders/'.$id.'/duplicate" title="Duplicar"><i class="fa-regular fa-copy"></i></a>';
  if($canDelete){$label=(string)($o['number']??$o['omie_code']);$actions.='<form method="post" action="'.APP_URL.'/orders/'.$id.'/delete"><input type="hidden" name="_token" value="'.e($token).'"><button class="tdo-icon-btn danger" type="submit" title="Excluir" data-confirm="Excluir definitivamente o pedido '.e($label).' da Omie e do CRM?"><i class="fa-regular fa-trash-can"></i></button></form>';}
  $actions.='</div>';
  $data[]=[$orderCell,$clientCell,$sellerCell,brdate($o['order_date']??null),$stageCell,'<span class="tdo-status '.$statusClass.'">'.e($status).'</span>','<strong>'.money($o['total']??0).'</strong>',$actions];
 }
 json_response(['draw'=>$draw,'recordsTotal'=>$recordsTotal,'recordsFiltered'=>$recordsFiltered,'data'=>$data]);
});

$router->get('/api/services/datatable',function(){
 Auth::requireRole('admin','supervisor');
 $draw=max(0,(int)($_GET['draw']??0));$start=max(0,(int)($_GET['start']??0));$length=max(1,min(100,(int)($_GET['length']??10)));
 $period=selected_date_period();$where=[];$params=[];
 if(!$period['all']){$where[]='so.service_date>=?';$where[]='so.service_date<?';array_push($params,$period['from'],$period['next']);}
 $baseWhere=$where;$baseParams=$params;$baseSql=$baseWhere?implode(' AND ',$baseWhere):'1=1';
 $recordsTotal=(int)(DB::scalar("SELECT COUNT(*) FROM service_orders so WHERE ".$baseSql,$baseParams)??0);

 $searchInput=$_GET['search']??[];$search=trim((string)(is_array($searchInput)?($searchInput['value']??''):''));
 if($search!==''){
  [$searchSql,$searchParams]=crm_search_filter($search,array_merge(client_search_fields('c'),['so.omie_code','s.name','so.seller_omie_code','so.status']));
  if($searchSql!==''){$where[]=$searchSql;array_push($params,...$searchParams);}
 }
 $sqlWhere=$where?implode(' AND ',$where):'1=1';
 $recordsFiltered=$search===''?$recordsTotal:(int)(DB::scalar(
  "SELECT COUNT(*) FROM service_orders so LEFT JOIN clients c ON c.omie_code=so.client_omie_code LEFT JOIN sellers s ON s.omie_code=so.seller_omie_code WHERE ".$sqlWhere,$params
 )??0);

 $orderInput=$_GET['order'][0]??[];$orderIndex=max(0,(int)(is_array($orderInput)?($orderInput['column']??3):3));$orderDir=strtolower((string)(is_array($orderInput)?($orderInput['dir']??'desc'):'desc'))==='asc'?'ASC':'DESC';
 $orderColumns=['so.omie_code','c.name','s.name','so.service_date','so.status','so.total'];$orderBy=$orderColumns[$orderIndex]??'so.service_date';
 $rows=DB::all(
  "SELECT so.id,so.omie_code,so.client_omie_code,so.seller_omie_code,so.service_date,so.total,so.status,c.name client_name,s.name seller_name
   FROM service_orders so
   LEFT JOIN clients c ON c.omie_code=so.client_omie_code
   LEFT JOIN sellers s ON s.omie_code=so.seller_omie_code
   WHERE ".$sqlWhere." ORDER BY ".$orderBy." ".$orderDir.",so.id DESC LIMIT ".$length." OFFSET ".$start,
  $params
 );
 $data=[];
 foreach($rows as $row){
  $status=(string)($row['status']??'ATIVO');$upper=mb_strtoupper($status);$statusClass=str_contains($upper,'CANCEL')?'cancelled':(str_contains($upper,'FATUR')?'billed':'active');
  $osCell='<div class="tds-os-cell"><span class="tds-os-icon"><i class="fa-solid fa-screwdriver-wrench"></i></span><span><strong>'.e($row['omie_code']).'</strong><small>código Omie</small></span></div>';
  $clientName=(string)($row['client_name']??'');$clientCode=(string)($row['client_omie_code']??'');$clientCell='<strong>'.e($clientName!==''?$clientName:($clientCode!==''?$clientCode:'—')).'</strong>'.($clientName!==''&&$clientCode!==''?'<small>'.e($clientCode).'</small>':'');
  if(!empty($row['seller_name']))$sellerCell='<strong>'.e($row['seller_name']).'</strong><small>'.e($row['seller_omie_code']).'</small>';
  elseif(!empty($row['seller_omie_code']))$sellerCell='<strong>'.e($row['seller_omie_code']).'</strong><small>código do vendedor</small>';
  else $sellerCell='<span class="tds-no-seller"><i class="fa-solid fa-circle-exclamation"></i>Sem vendedor</span>';
  $data[]=[$osCell,$clientCell,$sellerCell,brdate($row['service_date']??null),'<span class="tds-status '.$statusClass.'">'.e($status).'</span>','<strong>'.money($row['total']??0).'</strong>'];
 }
 json_response(['draw'=>$draw,'recordsTotal'=>$recordsTotal,'recordsFiltered'=>$recordsFiltered,'data'=>$data]);
});


$router->get('/api/collection/datatable',function(){
 Auth::requireRole('admin','supervisor','collector');ClientSegmentPolicy::ensureSchema();
 $u=Auth::user();$draw=max(0,(int)($_GET['draw']??0));$start=max(0,(int)($_GET['start']??0));$length=max(1,min(100,(int)($_GET['length']??10)));
 try{
  $view=(string)($_GET['view']??'open');if(!in_array($view,['open','settled'],true))$view='open';
  $assigned=max(0,(int)($_GET['assigned_user_id']??0));$uf=mb_strtoupper(trim((string)($_GET['uf']??'')));$delay=(string)($_GET['delay']??'all');
  $collectionTagsSelected=client_filter_tags($_GET['tags']??[]);
  if(!in_array($delay,['all','current','1_30','31_60','60_plus'],true))$delay='all';if(($u['role']??'')==='collector')$assigned=0;

  // Compatibilidade com bases que ainda não receberam todas as colunas novas de cobrança.
  $caseHasAssigned=db_column_exists('collection_cases','assigned_user_id');
  $actionHasLocalStatus=db_column_exists('collection_actions','local_status');
  $actionHasCore=db_column_exists('collection_actions','client_id')
   &&db_column_exists('collection_actions','result')
   &&db_column_exists('collection_actions','channel')
   &&db_column_exists('collection_actions','amount')
   &&db_column_exists('collection_actions','created_at');
  $taskHasCore=db_column_exists('tasks','id')
   &&db_column_exists('tasks','client_id')
   &&db_column_exists('tasks','title')
   &&db_column_exists('tasks','due_at')
   &&db_column_exists('tasks','status');

  $baseWhere=['cc.status=?','c.crm_inactive=0'];$baseParams=[$view];
  if($assigned>0){
   if(!$caseHasAssigned)throw new RuntimeException('Estrutura de cobrança desatualizada: responsável da carteira ainda não existe no banco.');
   $baseWhere[]='cc.assigned_user_id=?';$baseParams[]=$assigned;
  }
  if($uf!==''){$baseWhere[]='c.uf=?';$baseParams[]=$uf;}
  if($collectionTagsSelected){$tagKeys=array_map(static fn($v)=>mb_strtolower((string)$v,'UTF-8'),$collectionTagsSelected);$baseWhere[]=client_tags_any_filter_sql('c',count($tagKeys));array_push($baseParams,...$tagKeys);}
  if($delay==='current')$baseWhere[]='cc.max_overdue_days<=0';
  elseif($delay==='1_30')$baseWhere[]='cc.max_overdue_days BETWEEN 1 AND 30';
  elseif($delay==='31_60')$baseWhere[]='cc.max_overdue_days BETWEEN 31 AND 60';
  elseif($delay==='60_plus')$baseWhere[]='cc.max_overdue_days>60';

  $baseSql=implode(' AND ',$baseWhere);
  $recordsTotal=(int)(DB::scalar("SELECT COUNT(*) FROM collection_cases cc JOIN clients c ON c.id=cc.client_id WHERE ".$baseSql,$baseParams)??0);

  $where=$baseWhere;$params=$baseParams;$searchInput=$_GET['search']??[];$search=trim((string)(is_array($searchInput)?($searchInput['value']??''):''));
  $searchFields=client_search_fields('c');if($caseHasAssigned)$searchFields[]='u.name';
  $searchFields[]='s.name';
  if($search!==''){[$searchSql,$searchParams]=crm_search_filter($search,$searchFields);if($searchSql!==''){$where[]=$searchSql;array_push($params,...$searchParams);}}
  $whereSql=implode(' AND ',$where);

  $userJoin=$caseHasAssigned?' LEFT JOIN users u ON u.id=cc.assigned_user_id ':'';
  $recordsFiltered=$search===''?$recordsTotal:(int)(DB::scalar(
   "SELECT COUNT(*) FROM collection_cases cc
    JOIN clients c ON c.id=cc.client_id
    LEFT JOIN sellers s ON s.omie_code=c.seller_omie_code".
    $userJoin."
    WHERE ".$whereSql,$params
  )??0);

  $lastActionJoin='';$lastResultSelect='NULL last_result,NULL last_channel,0 last_amount,NULL last_contact_at';
  $pendingLocalSelect='0 pending_local';
  if($actionHasCore){
   $activeAction=$actionHasLocalStatus?" AND ca.local_status<>'cancelled'":'';
   $lastActionJoin=" LEFT JOIN collection_actions la ON la.id=(SELECT ca.id FROM collection_actions ca WHERE ca.client_id=cc.client_id".$activeAction." ORDER BY ca.created_at DESC,ca.id DESC LIMIT 1) ";
   $lastResultSelect='la.result last_result,la.channel last_channel,la.amount last_amount,la.created_at last_contact_at';
   if($actionHasLocalStatus)$pendingLocalSelect="COALESCE((SELECT SUM(cp.amount) FROM collection_actions cp WHERE cp.client_id=cc.client_id AND cp.result='payment' AND cp.local_status='pending'),0) pending_local";
  }

  $taskJoin='';$nextTaskSelect='NULL next_task_id,NULL next_title,NULL next_due_at';
  if($taskHasCore){
   $taskJoin=" LEFT JOIN tasks nt ON nt.id=(SELECT t.id FROM tasks t WHERE t.client_id=cc.client_id AND ".collection_task_condition_sql('t')." AND t.status='pending' ORDER BY t.due_at,t.id LIMIT 1) ";
   $nextTaskSelect='nt.id next_task_id,nt.title next_title,nt.due_at next_due_at';
  }

  $orderInput=$_GET['order'][0]??[];$orderIndex=max(0,(int)(is_array($orderInput)?($orderInput['column']??4):4));$orderDir=strtolower((string)(is_array($orderInput)?($orderInput['dir']??'desc'):'desc'))==='asc'?'ASC':'DESC';
  $orderColumns=[
   0=>'c.name',
   1=>'s.name',
   2=>$caseHasAssigned?'u.name':'c.name',
   3=>'cc.max_overdue_days',
   4=>'cc.open_amount',
   5=>$actionHasCore?'la.created_at':'cc.client_id',
   6=>$taskHasCore?'nt.due_at':'cc.client_id',
   7=>'cc.max_overdue_days',
   8=>'cc.client_id'
  ];
  $orderBy=$orderColumns[$orderIndex]??'cc.open_amount';
  $assignedSelect=$caseHasAssigned?'u.name assigned_name':'NULL assigned_name';

  $rows=DB::all(
   "SELECT cc.client_id,cc.open_amount,cc.max_overdue_days,cc.status,
           c.name,c.document,c.uf,c.city,c.seller_omie_code,s.name seller_name,".$assignedSelect.",
           ".$lastResultSelect.",
           ".$pendingLocalSelect.",
           ".$nextTaskSelect."
    FROM collection_cases cc
    JOIN clients c ON c.id=cc.client_id
    LEFT JOIN sellers s ON s.omie_code=c.seller_omie_code".
    $userJoin.$lastActionJoin.$taskJoin."
    WHERE ".$whereSql."
    ORDER BY ".$orderBy." ".$orderDir.",cc.client_id DESC
    LIMIT ".$length." OFFSET ".$start,
   $params
  );

  $data=[];
  foreach($rows as $row){
   $days=(int)($row['max_overdue_days']??0);$pendingLocal=(float)($row['pending_local']??0);$available=max(0,(float)$row['open_amount']-$pendingLocal);
   $hasAgreement=($row['last_result']??'')==='agreement'&&(float)($row['last_amount']??0)>0;
   $statusClass=$pendingLocal>0?'local-paid':($hasAgreement?'agreement':($days>60?'danger':($days>30?'warning':($days>0?'today':'ok'))));
   $statusText=$pendingLocal>0?'Baixa local pendente':($hasAgreement?'Acordo '.money($row['last_amount']):($days>60?'Crítico':($days>30?'Atenção':($days>0?'Em atraso':'Em dia'))));
   $client='<div class="tdcob4-client"><span>'.e(mb_strtoupper(mb_substr((string)$row['name'],0,1))).'</span><div><strong>'.e($row['name']).'</strong><small>'.e(($row['city']??'').(!empty($row['uf'])?' / '.$row['uf']:'')).'</small></div></div>';
   $daysCell='<strong class="'.($days>30?'danger':'').'">'.($days>0?$days.' dias':'Em dia').'</strong>';
   $amount='<strong>'.money($available).'</strong><small>Omie '.money($row['open_amount']).($pendingLocal>0?' · baixa '.money($pendingLocal):'').'</small>';
   $last=!empty($row['last_contact_at'])?'<strong>'.date('d/m/Y',strtotime((string)$row['last_contact_at'])).'</strong><small>'.e($row['last_channel']??'contato').'</small>':'—';
   $next=!empty($row['next_due_at'])?'<strong>'.e($row['next_title']??'Retorno').'</strong><small>'.date('d/m H:i',strtotime((string)$row['next_due_at'])).'</small>':'—';
   $status='<span class="tdcob4-status '.$statusClass.'"><i></i>'.$statusText.'</span>';
   $action='<a class="tdcob4-open" href="'.APP_URL.'/collection/'.(int)$row['client_id'].'" title="Abrir cobrança"><i class="fa-regular fa-folder-open"></i></a>';
   $data[]=[$client,e($row['seller_name']??'—'),'<strong>'.e($row['assigned_name']??'Não atribuído').'</strong>',$daysCell,$amount,$last,$next,$status,$action];
  }
  json_response(['draw'=>$draw,'recordsTotal'=>$recordsTotal,'recordsFiltered'=>$recordsFiltered,'data'=>$data]);
 }catch(Throwable $e){
  $errorRef='COL-'.date('YmdHis').'-'.strtoupper(substr(sha1($e->getMessage()),0,6));
  error_log('['.$errorRef.'] collection datatable: '.$e->getMessage());
  json_response(['draw'=>$draw,'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[],'error'=>'['.$errorRef.'] '.$e->getMessage()],500);
 }
});

$router->get('/api/collection/recoveries/datatable',function(){
 Auth::requireRole('admin','supervisor');
 $draw=max(0,(int)($_GET['draw']??0));$start=max(0,(int)($_GET['start']??0));$length=max(1,min(100,(int)($_GET['length']??10)));
 $period=selected_date_period();$baseWhere=["ca.result='payment'","ca.amount>0","ca.local_status<>'cancelled'"];$baseParams=[];
 if(!$period['all']){$baseWhere[]='ca.created_at>=?';$baseWhere[]='ca.created_at<?';$baseParams[]=$period['from'].' 00:00:00';$baseParams[]=$period['next'].' 00:00:00';}
 $baseSql=implode(' AND ',$baseWhere);$recordsTotal=(int)(DB::scalar("SELECT COUNT(*) FROM collection_actions ca WHERE ".$baseSql,$baseParams)??0);
 $where=$baseWhere;$params=$baseParams;$searchInput=$_GET['search']??[];$search=trim((string)(is_array($searchInput)?($searchInput['value']??''):''));
 if($search!==''){[$searchSql,$searchParams]=crm_search_filter($search,array_merge(client_search_fields('c'),['assigned.name','author.name','ca.notes']));if($searchSql!==''){$where[]=$searchSql;array_push($params,...$searchParams);}}
 $whereSql=implode(' AND ',$where);
 $recordsFiltered=$search===''?$recordsTotal:(int)(DB::scalar(
  "SELECT COUNT(*) FROM collection_actions ca
   JOIN clients c ON c.id=ca.client_id
   LEFT JOIN users assigned ON assigned.id=ca.assigned_user_id
   LEFT JOIN users author ON author.id=ca.author_user_id
   WHERE ".$whereSql,$params
 )??0);
 $orderInput=$_GET['order'][0]??[];$orderIndex=max(0,(int)(is_array($orderInput)?($orderInput['column']??3):3));$orderDir=strtolower((string)(is_array($orderInput)?($orderInput['dir']??'desc'):'desc'))==='asc'?'ASC':'DESC';
 $orderColumns=['c.omie_code','c.name','ca.amount','ca.created_at','ca.recorded_at','assigned.name','author.name','ca.id'];$orderBy=$orderColumns[$orderIndex]??'ca.created_at';
 $rows=DB::all(
  "SELECT ca.id,ca.client_id,ca.amount,ca.notes,ca.created_at,ca.recorded_at,ca.local_status,c.omie_code,
          JSON_UNQUOTE(JSON_EXTRACT(c.raw_json,'$.codigo_cliente_integracao')) client_integration_code,c.name,c.document,
          assigned.name assigned_name,author.name author_name
   FROM collection_actions ca
   JOIN clients c ON c.id=ca.client_id
   LEFT JOIN users assigned ON assigned.id=ca.assigned_user_id
   LEFT JOIN users author ON author.id=ca.author_user_id
   WHERE ".$whereSql."
   ORDER BY ".$orderBy." ".$orderDir.",ca.id DESC
   LIMIT ".$length." OFFSET ".$start,
  $params
 );
 $data=[];$token=CSRF::token();
 foreach($rows as $row){
  $code='<strong>'.e($row['client_integration_code']?:$row['omie_code']).'</strong><small>'.(!empty($row['client_integration_code'])?'Omie '.e($row['omie_code']):'').'</small>';
  $client='<strong>'.e($row['name']).'</strong><small>'.e($row['document']??'').'</small>';
  $author='<strong>'.e($row['author_name']??'—').'</strong>'.(!empty($row['notes'])?'<small>'.e($row['notes']).'</small>':'');
  $action='<form method="post" action="'.APP_URL.'/collection/actions/'.(int)$row['id'].'/delete"><input type="hidden" name="_token" value="'.e($token).'"><input type="hidden" name="return_to" value="recoveries"><button class="tdcob-delete-value icon-only" type="submit" data-confirm="Excluir este valor recuperado? O saldo e os relatórios serão recalculados." title="Excluir"><i class="fa-regular fa-trash-can"></i></button></form>';
  $data[]=[$code,$client,'<strong>'.money($row['amount']).'</strong>',date('d/m/Y H:i',strtotime((string)$row['created_at'])),date('d/m/Y H:i',strtotime((string)($row['recorded_at']??$row['created_at']))),e($row['assigned_name']??'—'),$author,$action];
 }
 json_response(['draw'=>$draw,'recordsTotal'=>$recordsTotal,'recordsFiltered'=>$recordsFiltered,'data'=>$data]);
});

$router->get('/api/products/datatable',function(){
 Auth::requireRole('admin','supervisor','seller');
 $draw=max(0,(int)($_GET['draw']??0));$start=max(0,(int)($_GET['start']??0));$length=max(1,min(50,(int)($_GET['length']??5)));
 $status=(string)($_GET['status']??'active');if(!in_array($status,['all','active','inactive'],true))$status='active';
 $unit=mb_strtoupper(trim((string)($_GET['unit']??'')));
 $baseWhere=[];$baseParams=[];
 if($status==='active')$baseWhere[]='p.active=1';elseif($status==='inactive')$baseWhere[]='p.active=0';
 if($unit!==''){$baseWhere[]='UPPER(p.unit)=?';$baseParams[]=$unit;}
 $baseSql=$baseWhere?implode(' AND ',$baseWhere):'1=1';
 $recordsTotal=(int)(DB::scalar("SELECT COUNT(*) FROM products p WHERE ".$baseSql,$baseParams)??0);
 $where=$baseWhere;$params=$baseParams;$searchInput=$_GET['search']??[];$search=trim((string)(is_array($searchInput)?($searchInput['value']??''):''));
 if($search!==''){$needle='%'.$search.'%';$where[]='(p.description LIKE ? OR p.sku LIKE ? OR p.omie_code LIKE ? OR p.ncm LIKE ?)';array_push($params,$needle,$needle,$needle,$needle);}
 $whereSql=$where?implode(' AND ',$where):'1=1';
 $recordsFiltered=(int)(DB::scalar("SELECT COUNT(*) FROM products p WHERE ".$whereSql,$params)??0);
 $orderInput=$_GET['order'][0]??[];$orderIndex=max(0,(int)(is_array($orderInput)?($orderInput['column']??0):0));$orderDir=strtolower((string)(is_array($orderInput)?($orderInput['dir']??'asc'):'asc'))==='desc'?'DESC':'ASC';
 $orderColumns=['p.description','p.sku','p.unit','p.unit_price','p.stock_qty','p.description','p.ncm','p.active','p.updated_at','p.id'];$orderColumn=$orderColumns[$orderIndex]??'p.description';
 $rows=DB::all("SELECT p.* FROM products p WHERE ".$whereSql." ORDER BY ".$orderColumn.' '.$orderDir.",p.id ASC LIMIT ".$length." OFFSET ".$start,$params);
 $data=[];
 foreach($rows as $row){
  $raw=json_decode((string)($row['raw_json']??''),true);if(!is_array($raw))$raw=[];
  $net=(float)($raw['peso_liq']??$raw['peso_liquido']??0);$gross=(float)($raw['peso_bruto']??0);
  $identity='<div class="tdp-product"><span><i class="fa-solid fa-box"></i></span><div><strong>'.e($row['description']).'</strong><small>Omie '.e($row['omie_code']).'</small></div></div>';
  $sku='<strong>'.e($row['sku']?:'Sem SKU').'</strong>';
  $unitCell='<span class="tdp-unit">'.e($row['unit']?:'—').'</span>';
  $price='<strong class="tdp-price">'.money($row['unit_price']).'</strong>';
  $stock=$row['stock_qty']===null?'<span class="tdp-muted">Não informado</span>':'<strong>'.number_format((float)$row['stock_qty'],3,',','.').'</strong>';
  $weights=($net>0||$gross>0)?'<div class="tdp-weight"><span>L '.number_format($net,3,',','.').' kg</span><span>B '.number_format($gross,3,',','.').' kg</span></div>':'<span class="tdp-muted">Não informado</span>';
  $ncm='<strong>'.e($row['ncm']?:'—').'</strong>';
  $active=(int)$row['active']===1;$statusCell='<span class="tdp-status '.($active?'active':'inactive').'"><i></i>'.($active?'Ativo':'Inativo').'</span>';
  $updated='<strong>'.(!empty($row['updated_at'])?date('d/m/Y H:i',strtotime((string)$row['updated_at'])):'—').'</strong>';
  $action='<button class="tdp-detail-button" type="button" data-product-details="'.(int)$row['id'].'" title="Ver detalhes do produto"><i class="fa-solid fa-magnifying-glass"></i><span>Detalhes</span></button>';
  $data[]=[$identity,$sku,$unitCell,$price,$stock,$weights,$ncm,$statusCell,$updated,$action];
 }
 json_response(['draw'=>$draw,'recordsTotal'=>$recordsTotal,'recordsFiltered'=>$recordsFiltered,'data'=>$data]);
});
$router->get('/api/products/{id}',function($params){
 Auth::requireRole('admin','supervisor','seller');
 $id=max(0,(int)($params['id']??0));$row=DB::one("SELECT * FROM products WHERE id=?",[$id]);
 if(!$row){json_response(['error'=>'Produto não encontrado no catálogo local.'],404);return;}
 $raw=json_decode((string)($row['raw_json']??''),true);if(!is_array($raw))$raw=[];$info=is_array($raw['info']??null)?$raw['info']:[];
 $number=static fn($value,int $decimals=3)=>number_format((float)$value,$decimals,',','.');
 $typeLabels=['00'=>'Mercadoria para revenda','01'=>'Matéria-prima','02'=>'Embalagem','03'=>'Produto em processo','04'=>'Produto acabado','05'=>'Subproduto','06'=>'Produto intermediário','07'=>'Material de uso e consumo','08'=>'Ativo imobilizado','09'=>'Serviço','10'=>'Outros insumos','99'=>'Outras'];
 $field=static fn(string $label,mixed $value)=>$value===null||$value===''?null:['label'=>$label,'value'=>(string)$value];
 $group=static function(string $title,string $icon,array $fields): ?array{$fields=array_values(array_filter($fields));return $fields?['title'=>$title,'icon'=>$icon,'fields'=>$fields]:null;};
 $included=trim((string)($info['dInc']??'').' '.(string)($info['hInc']??''));$changed=trim((string)($info['dAlt']??'').' '.(string)($info['hAlt']??''));
 $groups=array_values(array_filter([
  $group('Identificação','fa-barcode',[
   $field('Código Omie',$row['omie_code']),$field('SKU / código',$row['sku']),$field('Código de integração',$raw['codigo_produto_integracao']??null),
   $field('Família',$raw['descricao_familia']??null),$field('Tipo do item',$typeLabels[(string)($raw['tipoItem']??'')]??($raw['tipoItem']??null)),$field('Unidade',$row['unit'])
  ]),
  $group('Comercial e estoque','fa-tag',[
   $field('Preço de venda',money($row['unit_price'])),$field('Estoque',$row['stock_qty']===null?'Não informado':$number($row['stock_qty'])),
   $field('EAN / GTIN',$raw['ean']??null),$field('Marca',$raw['marca']??null),$field('Modelo',$raw['modelo']??null),
   $field('Lead time',isset($raw['lead_time'])?$raw['lead_time'].' dia(s)':null),$field('Garantia',isset($raw['dias_garantia'])?$raw['dias_garantia'].' dia(s)':null)
  ]),
  $group('Logística','fa-box-open',[
   $field('Peso líquido',isset($raw['peso_liq'])?$number($raw['peso_liq']).' kg':null),$field('Peso bruto',isset($raw['peso_bruto'])?$number($raw['peso_bruto']).' kg':null),
   $field('Altura',isset($raw['altura'])?$number($raw['altura']).' cm':null),$field('Largura',isset($raw['largura'])?$number($raw['largura']).' cm':null),
   $field('Profundidade',isset($raw['profundidade'])?$number($raw['profundidade']).' cm':null),$field('Cross-docking',isset($raw['dias_crossdocking'])?$raw['dias_crossdocking'].' dia(s)':null)
  ]),
  $group('Fiscal','fa-file-invoice',[
   $field('NCM',$row['ncm']),$field('CEST',$raw['cest']??null),$field('CFOP',$raw['cfop']??null),$field('CST ICMS',$raw['cst_icms']??null),
   $field('CSOSN ICMS',$raw['csosn_icms']??null),$field('CST PIS',$raw['cst_pis']??null),$field('CST COFINS',$raw['cst_cofins']??null),$field('Classificação tributária',$raw['class_trib']??null)
  ]),
  $group('Controle','fa-clock-rotate-left',[
   $field('Incluído na Omie',$included?:null),$field('Alterado na Omie',$changed?:null),$field('Atualizado no CRM',!empty($row['updated_at'])?date('d/m/Y H:i',strtotime((string)$row['updated_at'])):null),
   $field('Situação',(int)$row['active']===1?'Ativo':'Inativo'),$field('Bloqueado',($raw['bloqueado']??'N')==='S'?'Sim':'Não')
  ])
 ]));
 $observations=trim((string)($raw['obs_internas']??$raw['observacoes']??$raw['observacao']??''));
 json_response(['product'=>[
  'id'=>(int)$row['id'],'name'=>(string)$row['description'],'sku'=>(string)($row['sku']?:$row['omie_code']),'active'=>(int)$row['active']===1,
  'detailed_description'=>trim((string)($raw['descr_detalhada']??'')),'observations'=>$observations,'groups'=>$groups
 ]]);
});
$router->get('/api/products',function(){Auth::requireRole('admin','supervisor','seller');$q=trim((string)($_GET['q']??''));$w=['active=1'];$p=[];if($q!==''){$w[]='(description LIKE ? OR sku LIKE ? OR omie_code LIKE ?)';$x='%'.$q.'%';array_push($p,$x,$x,$x);}$items=DB::all("SELECT id,omie_code,sku,description,unit,unit_price,stock_qty,raw_json FROM products WHERE ".implode(' AND ',$w)." ORDER BY description LIMIT 30",$p);foreach($items as &$item){$raw=json_decode((string)($item['raw_json']??''),true);$item['net_weight']=(float)($raw['peso_liq']??0);$item['gross_weight']=(float)($raw['peso_bruto']??0);unset($item['raw_json']);}unset($item);json_response(['items'=>$items]);});


register_opportunity_routes($router);
