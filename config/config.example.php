<?php
return [
 'app'=>[
  'name'=>'Tecnodata CRM',
  'timezone'=>'America/Sao_Paulo',

  // Desenvolvimento: acesse http://localhost/tecnodata-crm
  // Produção: acesse https://tecnodataeducacional.com.br/crm
  // O diretório /public não faz parte da URL pública.
  'local_url'=>'http://localhost/tecnodata-crm',
  'production_url'=>'https://tecnodataeducacional.com.br/crm',

  'session_name'=>'tecnodata_crm',
 ],

 'database'=>[
  'table_prefix'=>'tdcrm_',

  // XAMPP / desenvolvimento local.
  'local'=>[
   'host'=>'127.0.0.1',
   'port'=>3306,
   'database'=>'crm_tecnodata_clean',
   'username'=>'root',
   'password'=>'',
   'charset'=>'utf8mb4',
  ],

  // Produção: PHP e banco no mesmo servidor/hospedagem.
  // Preencha database/username/password somente em config/config.php.
  'production'=>[
   'host'=>'127.0.0.1',
   'port'=>3306,
   'database'=>'SEU_BANCO_PRODUCAO',
   'username'=>'SEU_USUARIO_PRODUCAO',
   'password'=>'SUA_SENHA_PRODUCAO',
   'charset'=>'utf8mb4',
  ],
 ],

 'omie'=>[
  'app_key'=>'SUA_APP_KEY',
  'app_secret'=>'SEU_APP_SECRET',
  'timeout'=>60,
 ],

 'installer'=>[
  'enabled'=>false,
  'token'=>'TROQUE_ESTE_TOKEN',
  'admin'=>[
   'name'=>'Administrador',
   'email'=>'admin@exemplo.com',
   'password'=>'TROQUE_ESTA_SENHA',
  ],
 ],
];
