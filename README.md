# Tecnodata CRM — Clean Rebuild

Reescrita integral, isolada do código legado.

## Stack
- PHP 8.2+
- MySQL/MariaDB
- Bootstrap 5
- JavaScript nativo
- Omie como ERP; navegação sempre usa banco local

## Perfis
- admin
- supervisor
- seller
- collector

## Fluxo comercial
Hoje → Clientes → Cliente → Novo pedido → Omie → Pedidos → Agenda

## Fluxo cobrança
Cobrança → Cliente → Ação → Agenda → Histórico

## Banco de dados
A reconstrução usa o mesmo banco do CRM atual. Para não colidir com tabelas legadas, todas as tabelas novas recebem o prefixo `tdcrm_` por padrão. O prefixo pode ser alterado em `database.table_prefix`.

## Instalação
1. Mantenha em `config/config.php` as mesmas credenciais de banco já usadas pelo CRM.
2. Adicione `'table_prefix'=>'tdcrm_'` dentro de `database` (se não adicionar, este será o padrão).
3. Mantenha URLs, Omie e instalador configurados.
4. Acesse `/install.php?token=SEU_TOKEN`.
5. Desative o instalador.
6. Sincronize Vendedores, Clientes, Produtos, Categorias, Contas, Etapas e Condições.
7. Configure os padrões do pedido.
