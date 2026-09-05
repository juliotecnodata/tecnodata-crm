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

## Instalação
1. Copie `config/config.example.php` para `config/config.php`.
2. Configure banco, URLs, Omie e instalador.
3. Acesse `/install.php?token=SEU_TOKEN`.
4. Desative o instalador.
5. Sincronize Vendedores, Clientes, Produtos, Categorias, Contas, Etapas e Condições.
6. Configure os padrões do pedido.
