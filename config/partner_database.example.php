<?php
/**
 * Conexão exclusiva com a base de parceiros/CFCs.
 *
 * Este arquivo é apenas um modelo e pode ser versionado.
 * O CRM usa config/partner_database.php, que fica fora do Git.
 *
 * Depois do primeiro acesso ao CRM, se partner_database.php não existir,
 * o sistema tenta criá-lo automaticamente a partir deste modelo.
 */
return [
 'local'=>[
  // Use estes dados quando o CRM estiver rodando no XAMPP.
  // Se o banco de parceiros estiver remoto, informe aqui o host remoto.
  'host'=>'SEU_HOST_PARCEIROS',
  'port'=>3306,
  'database'=>'u695906402_Tecno_Loja_BD',
  'username'=>'SEU_USUARIO_PARCEIROS',
  'password'=>'SUA_SENHA_PARCEIROS',
  'charset'=>'utf8mb4',
 ],

 'production'=>[
  // Dados usados no servidor de produção.
  'host'=>'SEU_HOST_PARCEIROS_PRODUCAO',
  'port'=>3306,
  'database'=>'u695906402_Tecno_Loja_BD',
  'username'=>'SEU_USUARIO_PARCEIROS_PRODUCAO',
  'password'=>'SUA_SENHA_PARCEIROS_PRODUCAO',
  'charset'=>'utf8mb4',
 ],
];
