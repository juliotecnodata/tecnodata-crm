# Checkpoint — Redesign do Workspace Comercial V2

Data: 2026-09-24
Branch: `feature/fluxo-supervisor-comercial-v2`

## Direção

O novo Comercial não reutiliza a linguagem visual administrativa das telas legadas.

O padrão passa a ser um workspace operacional centrado em:
1. prioridade;
2. próxima ação;
3. Conta CRM;
4. histórico cronológico;
5. execução sem trocar de tela desnecessariamente.

## Identidade

- layout limpo e corporativo;
- borda padrão 10px;
- sem sombras;
- sem gradientes;
- sem glassmorphism;
- sem letter-spacing decorativo;
- alta densidade de informação sem poluição;
- estados funcionais por cor;
- comportamento mobile-first;
- ações principais sempre próximas do objeto trabalhado.

## Minha Carteira

A listagem administrativa foi substituída por uma work queue.

### Barra de prioridade
- Fila completa;
- Atrasados;
- Hoje;
- Nunca trabalhados;
- Próximos.

Os contadores representam o universo filtrado e não mudam de significado ao abrir uma fila.

### Ordem operacional
1. retorno atrasado;
2. retorno de hoje;
3. Conta nunca trabalhada;
4. demais por próxima ação e tempo de relacionamento.

### Cada linha exibe
- prioridade;
- Conta / documento / código CRM;
- CFC / Revendedor;
- Cliente vinculado ou Prospect;
- responsável;
- último contato;
- próxima ação;
- última compra;
- receita 12m;
- registro rápido;
- abertura da Conta.

## Conta CRM — visão 360

A ficha passa a ter:
- cabeçalho compacto com identidade e responsável;
- navegação interna;
- composer de atividade no topo;
- timeline comercial;
- notas internas separadas;
- lateral de contexto fixa em desktop;
- resumo da Conta;
- contatos;
- classificação;
- auditoria.

A timeline diferencia:
- tentativa;
- contato realizado;
- follow-up;
- retorno ligado à atividade.

## Agenda

A agenda deixa de usar dashboard decorativo como tela principal.

Nova organização:
- Atrasados;
- Hoje;
- Próximos;
- filtros compactos;
- lista operacional agrupada;
- Conta/Cliente visível;
- responsável;
- tipo;
- criação;
- ações inline.

Gestão possui uma lateral com carga da equipe e resumo operacional.

## Modais

Atividade rápida:
- tipo;
- canal;
- resultado;
- categoria;
- próximo retorno;
- responsável;
- contexto.

Tarefa:
- mesma linguagem visual;
- Comercial trabalha Conta CRM;
- Cobrança trabalha Cliente Geral.

## Arquivo visual

`public/assets/commercial-workspace-v2.css`

Carregado após os stylesheets legados para assumir a experiência comercial sem quebrar outras áreas durante a migração.

## Regra para próximos módulos

Parceiros EAD, Oportunidades, Vendas e Gestão devem usar este workspace como base e não criar novos padrões visuais paralelos.
