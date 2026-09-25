# Mapa mestre das APIs Omie para o Tecnodata CRM

Atualizado em 24/09/2026 a partir da lista oficial de serviços da Omie.

## Regra de identidade

| Domínio | Entidade Omie | Chave oficial | Papel no CRM |
|---|---|---|---|
| Cadastro, vendas e cobrança | Cliente/Fornecedor Geral | `codigo_cliente_omie` | Identidade fiscal e financeira canônica |
| Relacionamento comercial | Conta CRM | `identificacao.nCod` | Carteira, responsável, contatos e atividades |
| Ponte local obrigatória | `crm_account_links` | Conta CRM + `clients.id` | Une o histórico comercial a pedidos, serviços e títulos |

`identificacao.cCodInt` da Conta CRM não é uma chave estrangeira documentada para o Cliente Geral. Na base analisada, ele não coincidiu com `codigo_cliente_omie` nem com `codigo_cliente_integracao`. Portanto:

- conciliação automática somente por CPF/CNPJ normalizado e único;
- divergência, documento ausente ou duplicado exige vínculo manual auditado;
- nome, telefone, e-mail e prefixos podem sugerir candidatos, mas nunca confirmar sozinhos;
- uma Conta CRM pode apontar para um Cliente Geral; um Cliente Geral pode ter mais de uma Conta CRM, situação que deve ser sinalizada para revisão de responsabilidade.

## Prioridade de integração

| Prioridade | APIs | Uso pretendido | Estado atual |
|---|---|---|---|
| P0 | Geral: Clientes | Cadastro fiscal comum, chave de vendas e cobrança | Integrado |
| P0 | CRM: Contas, Contatos, Usuários | Carteira e relacionamento | Contas e usuários integrados; contatos em cache da Conta |
| P0 | Vendas: Pedidos de Venda e Etapas | Resultado comercial de produtos | Integrado |
| P0 | Serviços: Ordens de Serviço | Resultado comercial de serviços/EAD | Integrado |
| P0 | Finanças: Movimentos Financeiros | Saldo, vencimento, baixa e carteira de cobrança | Integrado |
| P1 | Finanças: Contas a Receber | Título, parcela, vencimento e baixa com semântica própria | Próxima fonte candidata a canônica da cobrança; comparar antes de substituir movimentos |
| P1 | CRM: Tarefas | Sincronizar compromissos comerciais com a agenda Omie | Endpoint configurado; fluxo local ainda é autoridade |
| P1 | CRM: Oportunidades, fases, status, motivos, origens | Funil e previsão | Parcial/diagnóstico; não deve duplicar venda realizada |
| P1 | Geral: Tags e características de clientes/contas | Segmentação CFC/Revendedor e metadados | Tags de cliente e características de Conta parcialmente integradas |
| P1 | Vendas/Serviços: Vendedores | Identidade do vendedor nos documentos | Vendedores de Vendas integrados; ponte com Usuário CRM exige conciliação |
| P2 | Finanças: Pesquisar Títulos, boletos, PIX, extrato e resumo | Detalhe operacional de cobrança e conciliação | Candidato, não integrado |
| P2 | Serviços: Contratos | Receita recorrente e renovação | Candidato, não integrado |
| P2 | NF-e/NFS-e e Obter Documentos | Documento fiscal e comprovação | Candidato, não integrado |
| P2 | Produtos, categorias, departamentos, condições, meios de pagamento | Referências do pedido | Integrado conforme necessidade do pedido |
| P3 | Compras, estoque e produção | Contexto de disponibilidade e compra | Fora do foco Comercial/Cobrança neste ciclo |
| P3 | Painel do Contador e impostos | Fiscal/contábil | Catalogado, fora do foco deste ciclo |

## Catálogo oficial por família

### Geral

Clientes/fornecedores/transportadoras, características de clientes, tags e projetos. Cadastros auxiliares: empresas, departamentos, categorias, parcelas, atividades da empresa, CNAE, cidades, países, anexos, tipos de entrega/assinante, tarefas e tabelas IBS/CBS.

### CRM

Contas, características de contas, contatos, oportunidades, resumo de oportunidades, tarefas e resumo de tarefas. Auxiliares: soluções, fases, usuários, status, motivos, tipos, parceiros, finders, origens, concorrentes, verticais, vendedores, telemarketing, pré-vendas e tipos de tarefas.

### Finanças

Contas correntes, lançamentos de conta corrente, contas a pagar, contas a receber, boletos, PIX, extrato, orçamento de caixa, pesquisa de títulos, movimentos financeiros e resumo. Auxiliares: bancos, tipos de documento/conta, DRE, finalidade de transferência, origem de títulos e bandeiras de cartão.

### Vendas e NF-e

Pedidos resumidos e completos, faturamento e etapas; CT-e, remessas, devoluções, resumo de vendas, documentos fiscais, cupons e NF-e. Auxiliares: vendedores, formas/meios de pagamento, tabelas de preços, origens, motivos de devolução, produtos e referências fiscais.

### Serviços e NFS-e

Serviços, ordens de serviço, faturamento individual/em lote, contratos e faturamento de contratos, resumo, documentos e NFS-e. Auxiliares: vendedores, serviços municipais, tributação, LC 116, NBS, IBPT, formas de pagamento, tipos/etapas de faturamento e classificação do serviço.

### Compras, estoque e produção

Produtos e suas características/estrutura/kit/variação/lote, requisições e pedidos de compra, produção, notas de entrada, recebimento e resumos; ajustes, consulta, movimentos, locais e resumo de estoque.

### Painel do Contador e fiscal

Documentos fiscais, resumo de fechamento contábil e tabelas fiscais (CFOP, CNAE, ICMS, PIS, COFINS, IPI, CEST e correlatas).

## Fluxo alvo Comercial + Cobrança

1. `ListarClientes` mantém a identidade fiscal/financeira local.
2. `ListarContas` mantém a carteira comercial e o `nCodVend` responsável.
3. A conciliação cria `crm_account_links`; vínculos manuais entram em auditoria.
4. Atividades e retornos usam `crm_account_code`.
5. Pedidos, OS, vendas e títulos usam `client_omie_code` obtido pela ponte.
6. A ficha comercial mostra dados de cobrança somente quando existe vínculo.
7. A ficha de cobrança mostra a Conta CRM e permite abrir o contexto comercial.
8. Nenhum dado financeiro é atribuído por nome, e-mail, telefone ou código de integração da Conta.

## Achado específico para cobrança

A API de Contas a Receber expõe diretamente `codigo_cliente_fornecedor`, `codigo_vendedor`, `status_titulo`, `data_vencimento`, `valor_documento`, `numero_parcela`, `nCodPedido` e `nCodOS`, além das operações de baixa e conciliação. Ela forma uma ponte estrutural mais explícita entre Cliente Geral, venda/serviço e título. Já Movimentos Financeiros fornece o saldo aberto (`nValAberto`) usado hoje pela carteira, portanto as duas fontes são complementares.

Por segurança, a troca de fonte não deve ser imediata. Primeiro será executado um comparativo somente leitura entre Contas a Receber e `financial_movements` para medir títulos, saldo aberto, atrasos, pagamentos parciais e contas correntes selecionadas. Até essa paridade ser comprovada, Movimentos Financeiros continua alimentando o saldo da carteira e Contas a Receber fica planejada como enriquecimento do título e elo com Pedido/OS.

## Próximas validações técnicas

- comparar Contas a Receber com Movimentos Financeiros para escolher a fonte canônica da cobrança;
- mapear chaves de Pedidos, OS, NF-e/NFS-e e títulos até o Cliente Geral;
- decidir se Tarefas CRM serão bidirecionais ou apenas espelhadas;
- medir cobertura e conflitos de `crm_account_links` em painel administrativo;
- testar webhooks oficiais para reduzir sincronizações completas sem perder reconciliação periódica.

## Fontes oficiais

- Portal do Desenvolvedor: https://developer.omie.com.br/
- Lista de APIs: https://developer.omie.com.br/service-list/
- Clientes Gerais: https://app.omie.com.br/api/v1/geral/clientes/
- Contas CRM: https://app.omie.com.br/api/v1/crm/contas/
