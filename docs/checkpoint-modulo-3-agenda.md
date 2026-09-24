# Checkpoint — Módulo 3: Agenda por Conta CRM

Data: 2026-09-24
Branch: `feature/fluxo-supervisor-comercial-v2`

## Objetivo concluído

A Agenda passa a funcionar com tarefas comerciais ligadas diretamente a Conta CRM, inclusive prospects sem Cliente Geral.

## Regras

### Comercial
- entidade principal: Conta CRM;
- `crm_account_code` pode existir com `client_id = NULL`;
- novas tarefas podem ser criadas para prospect;
- responsáveis comerciais oferecidos: equipe operacional ativa + supervisor/admin permitido;
- Pamela e Jessica são os vendedores operacionais atuais.

### Cobrança
- continua exigindo Cliente Geral;
- mantém o fluxo e as permissões existentes.

## Agenda

A rota `/agenda`:
- mostra tarefas de Cliente Geral e de Conta CRM;
- separa atrasadas, hoje e próximas;
- mantém filtros por responsável, área e período;
- abre Conta CRM quando a tarefa é comercial e possui `crm_account_code`;
- abre Cliente/Cobrança nos fluxos legados correspondentes.

## Modal de tarefa

O mesmo modal global é mantido.

Em Comercial:
- pesquisa em `crm_accounts`;
- mostra responsável CRM;
- informa se é Cliente vinculado ou Prospect;
- grava `crm_account_code`;
- carrega `client_id` quando existir vínculo.

Em Cobrança:
- pesquisa em Clientes Geral;
- exige `client_id`.

Ao trocar Comercial -> Cobrança ou vice-versa, a entidade selecionada é limpa para impedir vínculo incompatível.

## Modais de edição

Tarefa de prospect pode:
- ser visualizada;
- editada;
- reagendada;
- concluída;
- cancelada.

A ficha aberta pelo modal usa:
- `/commercial/accounts/{code}` para Conta CRM;
- `/clients/{id}` para cliente comercial legado;
- `/collection/{id}` para cobrança.

## Próximo módulo

Módulo 4 — Parceiros EAD:
- ativação;
- acesso;
- produtos;
- orientação;
- abordagem;
- campanhas;
- materiais;
- recuperação;
- acompanhamento;
- indicadores próprios de relacionamento.
