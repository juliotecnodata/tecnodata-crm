# Checkpoint — Módulo 1B: reconciliação e vendedores ativos

Data: 2026-09-24
Branch: `feature/fluxo-supervisor-comercial-v2`

## Descoberta na base real

A primeira carga completa retornou:
- 25 usuários CRM Omie marcados como ativos;
- 11.344 Contas CRM;
- 4.412 Contas vinculadas a Cliente Geral;
- 6.932 Contas ainda sem vínculo.

A operação atual informou que os únicos vendedores comerciais ativos são:
- Pamela — CRM `2403587771`;
- Jessica Ribeiro — CRM `712952964`.

Os demais usuários/responsáveis CRM devem permanecer como histórico ou pendência de redistribuição, não como vendedores operacionais.

## Regra revisada

São conceitos distintos:
1. `crm_owner_omie_code`: responsável atualmente gravado na Conta do Omie, preservado mesmo quando antigo;
2. `crm_owner_user_id`: consultora operacional ativa no Tecnodata;
3. lista `commercial_active_crm_sellers`: vendedores que hoje compõem a equipe comercial.

Somente códigos presentes na lista operacional podem preencher `crm_owner_user_id`.

## Segurança adicional

A conclusão da sincronização não ativa automaticamente a carteira CRM como autoridade.

A ativação depende de `commercial_crm_portfolio_authority.enabled=true`, que só deverá ocorrer depois da reconciliação dos vínculos e conferência da carteira.

## Diagnósticos

- `tools/analyze-crm-reconciliation.php`: mede, sem alterar dados, quais chaves adicionais realmente conseguem reconciliar as Contas CRM ainda não ligadas.
- `tools/set-commercial-sellers.php`: define a equipe comercial operacional sem alterar usuários no Omie.

Nenhum desses comandos envia alterações ao Omie.
