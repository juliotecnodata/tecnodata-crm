# Inteligência Comercial Tecnodata — Arquitetura Omie / CRM v2

Branch de desenvolvimento: `feature/fluxo-supervisor-comercial-v2`

## Objetivo

Transformar o Tecnodata CRM em ferramenta operacional diária das consultoras e de gestão, mantendo o Omie como repositório empresarial durável.

Princípio:

> Omie guarda a identidade empresarial e uma cópia durável do relacionamento; Tecnodata CRM organiza a operação, priorização, histórico e gestão.

## Fontes oficiais Omie analisadas

- Geral / Clientes: https://app.omie.com.br/api/v1/geral/clientes/
- Geral / Características de Clientes: https://app.omie.com.br/api/v1/geral/clientescaract/
- Geral / Tags: https://app.omie.com.br/api/v1/geral/clientetag/
- Geral / Vendedores: https://app.omie.com.br/api/v1/geral/vendedores/
- CRM / Contas: https://app.omie.com.br/api/v1/crm/contas/
- CRM / Características de Conta: https://app.omie.com.br/api/v1/crm/contascaract/
- CRM / Contatos: https://app.omie.com.br/api/v1/crm/contatos/
- CRM / Oportunidades: https://app.omie.com.br/api/v1/crm/oportunidades/
- CRM / Tarefas: https://app.omie.com.br/api/v1/crm/tarefas/
- CRM / Usuários: https://app.omie.com.br/api/v1/crm/usuarios/
- CRM / Fases: https://app.omie.com.br/api/v1/crm/fases/
- CRM / Status: https://app.omie.com.br/api/v1/crm/status/
- CRM / Motivos: https://app.omie.com.br/api/v1/crm/motivos/
- CRM / Tipos: https://app.omie.com.br/api/v1/crm/tipos/
- CRM / Origens: https://app.omie.com.br/api/v1/crm/origens/
- CRM / Tipos de Tarefas: https://app.omie.com.br/api/v1/crm/tipostarefa/
- Vendas / Pedidos: https://app.omie.com.br/api/v1/produtos/pedido/
- Serviços / OS: https://app.omie.com.br/api/v1/servicos/os/
- Finanças / Contas a Receber: https://app.omie.com.br/api/v1/financas/contareceber/

## Regra de autoridade dos dados

| Domínio | Autoridade | Uso no Tecnodata |
|---|---|---|
| Cadastro fiscal/comercial do cliente | Omie Geral / Clientes | cache local para busca e desempenho |
| Conta comercial | Omie CRM / Contas | vínculo comercial e carteira |
| Responsável da carteira | Omie CRM / Conta.nCodVend | define a carteira da consultora |
| Vendedor histórico do pedido/OS | Omie Vendas/Serviços | resultado da venda, não define carteira atual |
| Contatos da empresa | Omie CRM / Contatos | exibir e sincronizar |
| CFC / Revendedor | Tecnodata operacional + espelho em Características da Conta CRM | bidirecional controlado |
| Atividades | Tecnodata | histórico rico; espelho durável no CRM Omie |
| Próximos retornos | Tecnodata Tasks | espelho em Tarefas CRM Omie quando houver oportunidade-contêiner |
| Oportunidade de venda | CRM Omie + cache/vínculo Tecnodata | sincronização 1:1 |
| Pedido real | Omie Vendas | somente leitura para resultado/período de compra |
| Serviço/OS real | Omie Serviços | somente leitura para resultado/período de compra |
| Financeiro | Omie Finanças | cobrança e situação financeira |
| Indicadores/produtividade | Tecnodata | calculados localmente a partir dos fatos |

## Identidades e amarrações

### Cliente Geral ↔ Conta CRM

Primário:
1. vínculo previamente confirmado e armazenado;
2. CPF/CNPJ normalizado: `clientes.cnpj_cpf ↔ conta.identificacao.cDoc`;
3. código de integração somente quando comprovadamente equivalente.

Nunca vincular por nome sozinho.

Guardar localmente:
- `omie_client_code`;
- `omie_client_integration_code`;
- `omie_crm_account_code`;
- `omie_crm_account_integration_code`;
- `document`;
- método de vínculo;
- confiança do vínculo;
- última validação.

### Vendedor de Vendas ↔ Usuário CRM ↔ Usuário Tecnodata

São identidades diferentes.

Mapear usando:
1. código, quando comprovado igual;
2. e-mail;
3. nome normalizado apenas como sugestão de reconciliação manual.

Guardar tabela explícita de identidade. Não reutilizar um único código para representar os dois módulos.

### Oportunidade Tecnodata ↔ Oportunidade CRM Omie

Usar `cCodIntOp` como chave idempotente para oportunidades criadas pelo Tecnodata.

Formato proposto:
`TDOP-{id}`

Guardar:
- `nCodOp`;
- `cCodIntOp`;
- `nCodConta`;
- `nCodVendedor`;
- `nCodFase`;
- `nCodStatus`;
- `nCodMotivo`;
- `nCodTipo`;
- sincronização e checksum.

Não criar duplicatas em reprocessamentos; usar Upsert.

### Tarefa Tecnodata ↔ Tarefa CRM Omie

Usar `cCodInt` da tarefa como chave idempotente.

Formato proposto:
`TDT-{id}`

Mapear:
- responsável -> `nCodUsuario`;
- data/hora -> `dData/cHora`;
- descrição -> `cDescricao`;
- concluída -> `cRealizada`;
- oportunidade -> `nCodOp`.

A API de Tarefas CRM exige vínculo com oportunidade. Portanto atividades que não pertencem a oportunidade comercial precisam de uma estratégia específica, definida abaixo.

## Estratégia de relacionamento sem venda

Não transformar toda interação em oportunidade comercial real.

Criar no máximo uma oportunidade técnica de relacionamento por Conta quando necessário, somente se a configuração real do CRM Omie permitir isolá-la sem poluir o funil.

Chave:
`TDREL-{client_id}`

Descrição:
`Relacionamento Comercial Tecnodata`

Ticket:
zero.

Uso:
- tarefa/retorno;
- nota operacional;
- espelho de atividades não vinculadas a venda.

Antes de ativar essa estratégia, mapear as fases/status/tipos reais existentes no Omie da empresa. Se não houver fase/tipo apropriado para separar relacionamento de pipeline, manter atividades locais e sincronizar apenas oportunidades/tarefas comerciais para não poluir o Omie.

## Classificação CFC / Revendedor

A classificação é não exclusiva:
- CFC;
- Revendedor;
- ambos;
- nenhum.

Localmente usar flags independentes.

Espelho recomendado no CRM Omie por Características da Conta:
- `TD_CFC = SIM/NÃO`;
- `TD_REVENDEDOR = SIM/NÃO`.

Motivo: a API de Características da Conta permite alterar campos individualmente, evitando sobrescrever tags ou o restante da Conta.

As alterações devem registrar auditoria local:
- usuário;
- data/hora;
- valor anterior;
- valor novo;
- origem: Tecnodata ou Omie;
- status de sincronização.

## Contatos

CRM Omie / Contatos já possui vínculo com `nCodConta` e `nCodVend`, além de nome, cargo, telefone, celular e e-mail.

Nova ficha de cliente deve priorizar esses contatos.

Não duplicar contato apenas porque o cadastro Geral possui telefone/e-mail. Cadastro Geral é informação cadastral; CRM Contatos representa pessoas com quem a consultora se relaciona.

## Compra e resultado

### Pedidos

Pedido de Venda possui:
- cliente;
- vendedor de venda;
- data/previsão;
- etapa;
- total;
- itens.

Usar para:
- última compra;
- período de compra;
- frequência;
- ticket;
- Material;
- resultado realizado.

Não usar vendedor do pedido para trocar a carteira atual.

### Serviços / OS

OS possui:
- `nCodCli`;
- `nCodVend`;
- `dDtPrevisao`;
- `nValorTotal`.

Usar para:
- histórico de serviços;
- resultado de serviços/EAD quando aplicável;
- última compra/atividade financeira real.

### Financeiro

Contas a Receber possui cliente, vendedor e status do título.

Usar para cobrança/risco/situação financeira. Não misturar atraso financeiro com prioridade comercial de relacionamento.

## Sincronização

### Entrada Omie -> Tecnodata

Sincronizar:
1. Clientes Geral;
2. Vendedores Geral;
3. CRM Usuários;
4. CRM Contas;
5. CRM Contatos;
6. CRM Fases/Status/Motivos/Tipos/Origens;
7. CRM Oportunidades;
8. CRM Tarefas;
9. Pedidos;
10. OS;
11. Financeiro.

Priorizar filtros de alteração/data disponíveis nas APIs para sync incremental.

### Saída Tecnodata -> Omie

Usar fila `sync_outbox`.

Eventos:
- classificação alterada;
- oportunidade criada/alterada;
- retorno criado/reagendado/concluído;
- contato alterado quando permitido;
- atividade que tenha destino Omie definido.

Estados:
- pending;
- processing;
- synced;
- error;
- ignored.

Cada registro guarda:
- entidade;
- id local;
- operação;
- payload;
- tentativas;
- último erro;
- próxima tentativa;
- data de sincronização.

### Fechamento diário

Botão/processo “Sincronizar Omie”:
1. envia outbox pendente;
2. importa alterações Omie desde o último checkpoint;
3. reconcilia IDs;
4. marca divergências;
5. gera resumo;
6. nunca apaga histórico local por ausência temporária na API.

## Conflitos

Por domínio:
- cadastro: Omie vence;
- responsável da carteira: CRM Omie vence;
- oportunidade existente no Omie e alterada fora do Tecnodata: Omie vence nos campos CRM;
- classificação TD_CFC/TD_REVENDEDOR: última alteração confirmada, com auditoria e proteção contra loop;
- atividade local: nunca apagada por sync;
- tarefa local espelhada: status mais recente confirmado, preservando log de alterações.

## Implementação modular

### Módulo 1 — Base / Carteira
- mapa Cliente Geral ↔ Conta CRM;
- mapa vendedores Vendas ↔ usuários CRM ↔ usuários Tecnodata;
- responsável da carteira vindo de `Conta.nCodVend`;
- classificação CFC/Revendedor;
- características CRM;
- período/última compra;
- ordenação por último contato;
- auditoria.

Critério de pronto:
a carteira exibida no Tecnodata é a mesma do CRM Omie e nenhuma atribuição local silenciosa contradiz o Omie.

### Módulo 2 — Operação
- tentativa de contato;
- contato realizado;
- follow-up;
- observação;
- canal;
- tipo/subtipo;
- histórico único;
- ações rápidas;
- atualização automática de último contato/prioridade.

Critério de pronto:
registrar uma interação reposiciona o cliente sem navegar entre telas.

### Módulo 3 — Agenda
- agendar retorno dentro de qualquer atividade;
- hoje;
- atrasados;
- próximos;
- reagendar;
- concluir;
- vínculo activity -> task;
- espelho Omie quando aplicável.

Critério de pronto:
nenhum próximo passo depende de texto livre ou memória da consultora.

### Módulo 4 — Parceiros EAD
- acesso;
- produtos;
- abordagem/vendas;
- campanhas;
- materiais;
- ativação;
- recuperação;
- acompanhamento;
- orientação.

Critério de pronto:
atividade de relacionamento pode ser medida sem depender de uma venda individual de curso.

### Módulo 5 — Vendas / Oportunidades
- oportunidade 1:1 com CRM Omie;
- fase/status/motivo/tipo do Omie;
- Material / EAD / ambos;
- ticket produtos/serviços;
- valor, condição, desconto, expectativa;
- oportunidade ganha pode apontar para Pedido/OS real.

Critério de pronto:
pipeline Tecnodata pode ser reconstruído a partir do Omie e pedidos/OS confirmam o realizado.

### Módulo 6 — Gestão
- filtros por período/consultora/atividade/cliente/venda/canal;
- atividades;
- agenda;
- vendas;
- parceiros;
- consulta diária;
- produtividade;
- divergências e saúde de sincronização.

Critério de pronto:
gestor consegue responder o que foi feito, por quem, quando, com quem, por qual motivo e qual próximo passo.

## O que não deve ser mantido por legado

Qualquer tela/regra que:
- mantenha carteira paralela ao CRM Omie;
- exija navegação duplicada;
- duplique oportunidade sem integração;
- misture vendedor do pedido com responsável atual da carteira;
- trate EAD como venda individual ao consumidor quando o fluxo é de parceiro;
- não alimente histórico, próxima ação, resultado ou gestão;

deve ser retirada do fluxo principal após a substituição modular estar validada.

Não excluir tabelas ou histórico antes da migração/reconciliação dos dados.
