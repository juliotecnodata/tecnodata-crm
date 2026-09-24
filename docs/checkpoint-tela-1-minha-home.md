# Checkpoint — Tela 1: Minha Home

Data: 2026-09-24
Branch: `feature/fluxo-supervisor-comercial-v2`

## Status

Tela aprovada pelo supervisor implementada como Home da consultora.

Ao acessar `/`:
- vendedor/consultora abre `Minha Home`;
- supervisor/admin continuam no dashboard de gestão.

## Estrutura

### Cabeçalho
- COMERCIAL / MINHA HOME;
- Minha Home;
- pergunta: "O que preciso fazer agora?";
- data atual;
- resumo automático;
- Atualizar;
- Novo cliente.

### KPIs automáticos
1. Clientes sem contato há 30+ dias;
2. Retornos atrasados;
3. Retornos agendados hoje;
4. Vendas no mês.

Os indicadores são calculados a partir de:
- CRM Accounts;
- activities;
- tasks;
- pedidos;
- serviços.

Não existem campos manuais para alimentar os cards.

## Clientes da minha carteira

Tabela operacional com:
- Cliente;
- Tipo;
- Consultor;
- Período de compra;
- Último contato;
- Dias sem contato;
- Última atividade;
- Próximo retorno;
- Status;
- Ações.

Filtros:
- tipo de cliente;
- ordenação;
- quantidade por página;
- busca.

Ordenações:
- mais tempo sem contato;
- mais urgente;
- próximo retorno.

## Ações rápidas

- Tentativa;
- Contato;
- Follow-up;
- Venda.

Tentativa, Contato e Follow-up usam o modal da operação comercial e retornam para a mesma página/filtros da Home.

Venda abre Novo Pedido com `client_id` pré-selecionado quando existe Cliente Geral vinculado.

Prospect sem Cliente Geral não recebe venda fictícia; o botão permanece indisponível até existir vínculo.

## Paleta

Arquivo:
`public/assets/commercial-home-v1.css`

A paleta atual é provisória e está centralizada em CSS variables.

Quando a equipe enviar a paleta oficial, alterar os tokens:
- --tdh-navy
- --tdh-navy-2
- --tdh-blue
- --tdh-green
- --tdh-lime
- --tdh-yellow
- --tdh-orange
- --tdh-follow
- --tdh-red
- neutros

Sem necessidade de reconstruir layout ou componentes.

## Validação

Ferramenta:
`tools/check-commercial-home.php`

Não consulta nem escreve no Omie.

Valida:
- mapeamento dos vendedores ativos;
- 30+ dias sem contato;
- atrasados;
- hoje;
- venda do mês;
- quantidade da carteira;
- primeira prioridade exibida.
