<?php
declare(strict_types=1);

require dirname(__DIR__).'/app/bootstrap.php';

$extensions=BaldussiService::listExtensions('0');
if(empty($extensions['rows']))throw new RuntimeException('A API não retornou ramais para o teste.');
$allowed=['id','number','name','group','area_code','external_caller_id','recording','blocked'];
foreach($extensions['rows'] as $row){
 if(array_diff(array_keys($row),$allowed))throw new RuntimeException('A camada segura retornou campos não autorizados.');
}

$user=DB::one("SELECT id,name,email,role,seller_omie_code,crm_user_omie_code FROM users WHERE active=1 AND role IN('admin','supervisor') LIMIT 1");
if(!$user)throw new RuntimeException('Nenhum usuário de gestão disponível para testar a renderização.');
$_SESSION['user']=$user;
$_SESSION['baldussi_test_state']['last_call']=['ok'=>true,'active'=>true,'origin'=>'1811','destination_masked'=>'41•••••9999','route_label'=>'Chamada local (DDD 41)','started_at'=>date('Y-m-d H:i:s'),'requested_by'=>(int)$user['id'],'crm_account_code'=>'TESTE','account_name'=>'Conta de teste'];

$publicConfig=BaldussiService::publicConfig();
ob_start();
render('baldussi_test',[
 'baldussiConfig'=>$publicConfig,
 'baldussiState'=>['extensions'=>$extensions,'tested_at'=>date('Y-m-d H:i:s')],
 'baldussiExtension'=>BaldussiService::userExtension($user),
 'activityTypes'=>CommercialActivityService::types(),'activityChannels'=>CommercialActivityService::channels(),'activityCategories'=>CommercialActivityService::categories(),'activityAssignableUsers'=>CommercialActivityService::assignableUsers($user),
 'flash'=>null,
]);
$html=(string)ob_get_clean();
$token=(string)($GLOBALS['config']['baldussi']['token']??'');
$checks=[
 'Título'=>str_contains($html,'Telefonia Baldussi'),
 'Teste de conexão'=>str_contains($html,'test-connection'),
 'Teste de discagem'=>str_contains($html,'/dial'),
 'Ramal do usuário'=>BaldussiService::userExtension($user)!==null&&str_contains($html,'Seu ramal'),
 'Máscara de telefone'=>str_contains($html,'data-brazil-phone')&&str_contains($html,'(41) 99999-9999'),
 'Vínculo ao histórico'=>str_contains($html,'data-baldussi-account-search')&&str_contains($html,'data-commercial-activity-dialog'),
 'Painel flutuante'=>str_contains($html,'data-baldussi-call-dock')&&str_contains($html,'Registrar atividade'),
 'Sem senha SIP'=>!str_contains($html,'senha_sip'),
 'Sem token'=>$token===''||!str_contains($html,$token),
];
foreach($checks as $label=>$ok){echo $label.': '.($ok?'OK':'FALHA').PHP_EOL;if(!$ok)exit(1);}
$localPlan=BaldussiService::dialPlan('(41) 99999-9999');$longDistancePlan=BaldussiService::dialPlan('(11) 99999-9999');
$dialPlanOk=$localPlan['dial_number']==='999999999'&&$longDistancePlan['dial_number']==='011999999999';
echo 'Plano de discagem: '.($dialPlanOk?'OK':'FALHA').PHP_EOL;if(!$dialPlanOk)exit(1);
echo 'Ramais seguros: '.count($extensions['rows']).' de '.(int)$extensions['total'].PHP_EOL;
echo 'Status: OK'.PHP_EOL;
