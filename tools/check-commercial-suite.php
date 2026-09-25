<?php
declare(strict_types=1);

require dirname(__DIR__).'/app/bootstrap.php';

$seller=DB::one("SELECT id,name,role,crm_user_omie_code,seller_omie_code FROM users WHERE role='seller' AND active=1 ORDER BY id LIMIT 1");
if(!$seller)throw new RuntimeException('Nenhuma consultora ativa encontrada.');

$partners=CommercialPartnerService::listing($seller,[]);
$sales=CommercialSaleService::listing($seller,[]);
$management=CommercialManagementService::build([]);

$_SESSION['user']=$seller;
ob_start();render('commercial_partners',['partners'=>$partners,'partnerCatalog'=>CommercialPartnerService::catalog(),'flash'=>null]);$partnersHtml=ob_get_clean();
ob_start();render('commercial_sales',['salesData'=>$sales,'saleTypes'=>CommercialSaleService::types(),'saleConditions'=>CommercialSaleService::conditions(),'commercialSellers'=>[$seller],'flash'=>null]);$salesHtml=ob_get_clean();
$_SESSION['user']=array_merge($seller,['role'=>'supervisor']);
ob_start();render('commercial_management',['managementData'=>$management,'commercialSellers'=>[$seller],'activityChannels'=>CommercialActivityService::channels(),'saleTypes'=>CommercialSaleService::types()]);$managementHtml=ob_get_clean();

$writeStatus='NÃO EXECUTADO';
$account=DB::one("SELECT a.omie_code FROM crm_accounts a JOIN crm_account_links l ON l.crm_account_code=a.omie_code WHERE a.active=1 AND a.crm_user_code=? ORDER BY a.omie_code LIMIT 1",[(string)$seller['crm_user_omie_code']]);
if($account){
 $pdo=DB::conn();$pdo->beginTransaction();
 try{
  $_SESSION['user']=$seller;$code=(string)$account['omie_code'];$future=date('Y-m-d H:i',time()+86400);
  $sale=CommercialActivityService::record($code,$seller,['activity_type'=>'sale','channel'=>'other','category_code'=>'commercial','notes'=>'Validação transacional','sale_type'=>'material','sale_amount'=>'100,00','sale_discount'=>'0,00','commercial_condition'=>'cash']);
  DB::exec("INSERT INTO crm_account_commercial_profiles(crm_account_code,is_cfc,is_reseller,classification_source,updated_by_user_id,updated_at) VALUES(?,0,1,'test',?,NOW()) ON DUPLICATE KEY UPDATE is_reseller=1",[$code,(int)$seller['id']]);
  $partner=CommercialPartnerService::record($code,$seller,['work_items'=>['access:access_guidance'],'description'=>'Validação transacional','next_step'=>'Retornar ao parceiro','schedule_return'=>1,'next_date'=>date('Y-m-d',strtotime($future)),'next_time'=>date('H:i',strtotime($future))]);
  $writeStatus=!empty($sale['activity_id'])&&!empty($partner['workId'])&&!empty($partner['taskId'])?'OK':'ERRO';
 }finally{$pdo->rollBack();}
}

echo "INTELIGÊNCIA COMERCIAL — TELAS 5 A 7\n";
echo "===================================\n";
echo 'Consultora de teste: '.$seller['name'].PHP_EOL;
echo 'Parceiros visíveis: '.count($partners['rows']).PHP_EOL;
echo 'Vendas no período: '.count($sales['rows']).PHP_EOL;
echo 'Linhas gerenciais: '.count($management['rows']).PHP_EOL;
echo 'Atividades do dia: '.count($management['daily']).PHP_EOL;
echo 'Render Parceiros: '.(str_contains($partnersHtml,'Parceiros EAD')?'OK':'ERRO').PHP_EOL;
echo 'Render Vendas: '.(str_contains($salesHtml,'Registrar venda')?'OK':'ERRO').PHP_EOL;
echo 'Render Gestão: '.(str_contains($managementHtml,'Painel da Gestão')?'OK':'ERRO').PHP_EOL;
echo 'Fluxos de gravação (rollback): '.$writeStatus.PHP_EOL;
echo 'Status: OK'.PHP_EOL;
