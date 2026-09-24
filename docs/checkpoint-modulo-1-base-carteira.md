# Checkpoint — Módulo 1: Base e Carteira

Data: 2026-09-24  
Branch: `feature/fluxo-supervisor-comercial-v2`

## Estado

O Módulo 1 da nova Inteligência Comercial está implementado para validação local.

### Implementado

- endpoints separados do CRM Omie;
- espelho local de Usuários CRM;
- espelho local de Contas CRM;
- reconciliação Conta CRM x Cliente Geral por CPF/CNPJ;
- identidade explícita Usuário Tecnodata x Usuário CRM x Vendedor de Vendas;
- `Conta.nCodVend` como autoridade progressiva da carteira;
- fallback legado somente até a primeira sincronização CRM completa;
- snapshot integral com inativação segura de registros ausentes;
- contatos da Conta CRM;
- CFC e Revendedor como flags independentes;
- leitura inicial de características/tags CRM;
- edição de CFC/Revendedor pela ficha do cliente;
- auditoria de classificação, observação estratégica e responsável;
- fila `sync_outbox` para alterações Tecnodata -> Omie;
- espelho CFC/Revendedor em características `TD_CFC` e `TD_REVENDEDOR`;
- filtro por classificação na carteira;
- carteira oficial priorizada por maior tempo sem contato;
- bloqueio de ação comercial fora da carteira oficial;
- período de compra usando Pedido e Serviço para primeira/última compra;
- ferramentas CLI de diagnóstico e sincronização.

## Segurança

A sincronização padrão de `tools/sync-commercial-base.php` consulta o Omie e atualiza somente o cache/banco local.

Ela **não altera o Omie** sem a opção explícita:

```
--push
```

Não executar `--push` antes de validar o mapeamento de Contas e Usuários.

## Próximo módulo

Após validação:

**Módulo 2 — Operação**
- tentativa de contato;
- contato realizado;
- follow-up;
- observações;
- histórico unificado;
- ações rápidas;
- atualização automática da prioridade da carteira.
