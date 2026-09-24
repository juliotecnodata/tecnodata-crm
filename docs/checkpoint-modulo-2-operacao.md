# Checkpoint — Módulo 2: Operação por Conta CRM

Data: 2026-09-24
Branch: `feature/fluxo-supervisor-comercial-v2`

## Objetivo concluído

A operação comercial deixa de depender de Cliente Geral e passa a trabalhar diretamente com Conta CRM.

## Estrutura

### activities

Novos campos:
- `crm_account_code`;
- `activity_type`;
- `category_code`;
- `outcome_code`.

`client_id` passa a ser opcional.

Histórico legado é associado à Conta CRM apenas quando o Cliente possui um único vínculo de Conta, evitando associação arbitrária.

### tasks

Novos campos:
- `crm_account_code`;
- `source_activity_id`.

`client_id` passa a ser opcional, permitindo retorno agendado para prospect.

### crm_account_notes

Notas livres são armazenadas separadamente das atividades formais.

Uma nota:
- não atualiza último contato;
- não altera prioridade da carteira;
- não conta como atividade comercial.

## Tipos de atividade

- Tentativa de contato;
- Contato realizado;
- Follow-up.

## Canais

- Ligação;
- WhatsApp;
- E-mail;
- Presencial;
- Videoconferência;
- Outro.

## Categorias

Contato realizado:
- Comercial;
- Relacionamento;
- Suporte;
- Atualização cadastral;
- Acompanhamento;
- Outro.

Follow-up:
- Acompanhamento;
- Boleto;
- Frete;
- Mídia / artes;
- Proposta;
- Acompanhamento de pedido;
- Material;
- Retorno do cliente;
- Acesso;
- Orientação de produto;
- Campanha;
- Ativação;
- Outro.

## Resultado

Resultados são filtrados pelo tipo de atividade. Tentativas e contatos concluídos não compartilham opções incoerentes.

## Retorno

Toda atividade pode receber `next_at`.

Quando informado:
- cria task comercial;
- vincula à Conta CRM;
- mantém `client_id` se houver;
- vincula a task à atividade por `source_activity_id`;
- vendedor agenda para si;
- supervisor/admin pode definir responsável permitido.

## Interface

### /my-portfolio

Possui registro rápido em modal, sem necessidade de abrir a ficha.

Após salvar:
- a Conta é reposicionada pela nova data de contato;
- os filtros e a página atual são preservados.

### /commercial/accounts/{code}

Possui:
- formulário completo de atividade;
- histórico formal;
- próximo retorno ligado à atividade;
- notas livres separadas;
- classificação comercial;
- contatos da Conta;
- dados de compra quando houver Cliente Geral.

## Ferramenta de validação

`tools/check-commercial-operation.php`

Somente verifica estrutura e migração.
Não cria atividade, não chama Omie e não escreve no Omie.

## Próximo módulo

Módulo 3 — Agenda:
- Hoje;
- Atrasados;
- Próximos;
- tarefas de Conta CRM com ou sem Cliente Geral;
- reagendar;
- concluir;
- abrir Conta CRM diretamente da tarefa.
