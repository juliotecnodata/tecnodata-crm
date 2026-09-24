# Paleta oficial — Tecnodata CRM

Data: 2026-09-24
Branch: `feature/fluxo-supervisor-comercial-v2`

## Arquivo fonte

`public/assets/crm-palette-v1.css`

Este arquivo é a fonte única de cores do novo CRM.

## Tokens oficiais

### Estrutura
- Navy principal: `#0B2239`
- Navy secundário: `#123B5D`
- Fundo geral: `#F6F8FA`
- Superfície: `#FFFFFF`
- Hover: `#F9FBFC`
- Borda: `#DCE4E9`
- Borda suave: `#EBF0F3`

### Texto
- Texto principal: `#1B2B36`
- Texto secundário: `#6E7D87`
- Texto discreto: `#93A0A8`

### Estados e ações
- Azul principal: `#007FC7`
- Azul suave: `#EAF5FB`
- Verde comercial: `#009657`
- Verde suave: `#EAF7F1`
- Lima de marca: `#BDD136`
- Amarelo: `#E7B400`
- Amarelo suave: `#FFF8DD`
- Laranja: `#EA5127`
- Laranja suave: `#FFF1EC`
- Vermelho: `#D64545`
- Vermelho suave: `#FFF0F0`
- Roxo funcional: `#7357C9`
- Roxo suave: `#F3F0FC`

## Semântica

- Azul: ação e informação;
- Verde: venda, sucesso, concluído, em dia;
- Amarelo: atenção moderada;
- Laranja: precisa de intervenção;
- Vermelho: atraso, problema ou erro;
- Roxo: follow-up e classificação híbrida;
- Navy: estrutura e hierarquia;
- Lima: assinatura da marca, nunca cor dominante de estado.

## Aplicação

A paleta já é consumida por:
- Minha Home;
- Minha Carteira;
- Conta CRM;
- Agenda;
- shell/sidebar/topbar do novo workspace comercial.

O arquivo também redefine os tokens legados `--crm-*`, para manter compatibilidade com componentes existentes durante a migração.

## Regras visuais

- radius padrão: 10px;
- sem gradientes;
- sem box-shadow decorativo;
- sem glassmorphism;
- sem letter-spacing decorativo;
- cor deve comunicar estado ou ação, não decoração.

## Tela 1

Na Minha Home:
- 30+ dias sem contato: laranja;
- retornos atrasados: vermelho;
- retornos hoje: azul;
- vendas no mês: verde;
- Tentativa: azul;
- Contato: azul outline;
- Follow-up: roxo;
- Venda: verde.
