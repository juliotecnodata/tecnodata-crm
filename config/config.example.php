<?php
return [
 'app'=>[
  'name'=>'Tecnodata CRM',
  'timezone'=>'America/Sao_Paulo',
  'local_url'=>'http://localhost/tecnodata-crm/public',
  'production_url'=>'https://tecnodataeducacional.com.br/crm',
  'session_name'=>'tecnodata_crm_clean',
 ],
 'database'=>[
  'local'=>['host'=>'127.0.0.1','port'=>3306,'database'=>'crm_tecnodata_clean','username'=>'root','password'=>'','charset'=>'utf8mb4'],
  'production'=>['host'=>'SEU_HOST','port'=>3306,'database'=>'SEU_BANCO','username'=>'SEU_USUARIO','password'=>'SUA_SENHA','charset'=>'utf8mb4'],
 ],
 'omie'=>['app_key'=>'SUA_APP_KEY','app_secret'=>'SEU_APP_SECRET','timeout'=>60],
 'installer'=>[
  'enabled'=>false,
  'token'=>'TROQUE_ESTE_TOKEN',
  'admin'=>['name'=>'Administrador','email'=>'admin@exemplo.com','password'=>'TROQUE_ESTA_SENHA'],
 ],
];
