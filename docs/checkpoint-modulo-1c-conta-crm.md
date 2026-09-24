# Checkpoint — Módulo 1C: carteira centrada em Conta CRM

Data: 2026-09-24
Branch: `feature/fluxo-supervisor-comercial-v2`

## Descoberta confirmada pela base real e documentação Omie

Conta CRM e Cliente Geral não são a mesma entidade.

Uma Conta CRM pode representar relacionamento/prospect antes de existir um Cliente Geral. O Cliente Geral passa a existir ou ser associado quando necessário para faturamento.

Por isso, a carteira comercial não pode ser construída apenas sobre `clients`.

## Modelo definitivo do Módulo 1

```
CRM Account
  omie_code
  crm_user_code
  contatos
  classificação
  relacionamento
       |
       +-- client_id opcional
             |
             +-- pedidos
             +-- serviços
             +-- financeiro
             +-- métricas de compra
```

### Vendedor operacional

Equipe atual:
- Pamela — CRM 2403587771
- Jessica Ribeiro — CRM 712952964

Outros responsáveis continuam preservados como histórico/pendência de redistribuição.

## Minha Carteira

`/my-portfolio` passa a consultar `crm_accounts`.

Mostra:
- Conta / Cliente;
- CNPJ/CPF;
- CFC / Revendedor;
- responsável CRM;
- período de compra quando houver Cliente Geral;
- dias sem contato;
- Cliente vinculado ou Prospect / Conta CRM;
- receita de 12 meses quando houver vínculo.

Ordem padrão:
1. nunca contatados;
2. maior tempo sem contato;
3. nome.

## Ficha da Conta

Rota:
`/commercial/accounts/{nCod}`

Funciona com ou sem Cliente Geral.

Possui:
- responsável CRM;
- contatos;
- classificação CFC/Revendedor;
- observação estratégica;
- auditoria;
- período de compra;
- receita/pedidos;
- acesso ao Cliente Geral quando vinculado.

## Reconciliação

Não usar como chave automática:
- cCodInt: não apresentou equivalência;
- prefixo Cxxxxx sozinho: existem colisões/reuso;
- nome sozinho: não confiável.

Documento exato é o vínculo automático principal.

O vínculo técnico por documento pode existir mesmo quando o Cliente local está `crm_inactive`; isso não reativa o Cliente nem altera sua visibilidade.

## Ferramenta local

`tools/rebuild-commercial-cache.php`

Reaplica usando somente dados já baixados:
- perfis comerciais;
- vínculo exato por documento;
- Pamela/Jessica como equipe operacional;
- métricas de saúde.

Não consulta API e não escreve no Omie.

## Próxima fase

Módulo 2 — Operação deve ser Conta-centric:
- tentativa de contato;
- contato realizado;
- follow-up;
- observações;
- histórico;
- atualização de último contato;
- atividade em prospect mesmo sem Cliente Geral.
