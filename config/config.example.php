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

 'google_auth'=>[
  // Crie um cliente OAuth 2.0 do tipo "Aplicativo da Web" no Google Cloud.
  // URI de redirecionamento: https://SEU-DOMINIO/crm/auth/google/callback
  'enabled'=>true,
  'client_id'=>'SEU_CLIENT_ID.apps.googleusercontent.com',
  'client_secret'=>'SEU_CLIENT_SECRET',
  'hosted_domain'=>'tecnodatacfc.com.br',
 ],

 'client_segments'=>[
  'general'=>['label'=>'Clientes Geral','description'=>'Base comercial ativa.','seller_codes'=>[]],
  'ead_reciclagem'=>['label'=>'Clientes EAD Reciclagem','description'=>'Operação virtual EAD.','seller_codes'=>['594326005']],
  'suporte_pet'=>['label'=>'Clientes Suporte PET','description'=>'Operação virtual PET.','seller_codes'=>['559876552']],
 ],

 // Carteiras administradas somente no CRM; informe os códigos Omie dos usuários.
 'crm_portfolio_seller_codes'=>['CODIGO_JESSICA','CODIGO_PAMELA'],

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

 'partner_database'=>[
  // Base externa de parceiros/CFCs. O sistema lê somente dados cadastrais necessários
  // para conciliar CNPJ com a Conta CRM; campos sensíveis como senha não são consultados.
  'local'=>[
   'host'=>'127.0.0.1',
   'port'=>3306,
   'database'=>'u695906402_Tecno_Loja_BD',
   'username'=>'root',
   'password'=>'',
   'charset'=>'utf8mb4',
  ],
  'production'=>[
   'host'=>'127.0.0.1',
   'port'=>3306,
   'database'=>'u695906402_Tecno_Loja_BD',
   'username'=>'SEU_USUARIO_PARCEIROS',
   'password'=>'SUA_SENHA_PARCEIROS',
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
