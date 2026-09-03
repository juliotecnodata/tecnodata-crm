# Tecnodata — Inteligência de Carteira

Sistema leve de carteira comercial integrado ao Omie, pensado para trabalhar fora do ERP com foco em:

- Minha Carteira
- Atendimento rápido por cliente
- Minha Agenda
- Meu Resultado
- Metas do mês
- Gestão da equipe
- Sincronização controlada com Omie
- Logs e auditoria

## Requisitos

- PHP 8.1+
- MySQL 8 / MariaDB 10.6+
- PDO MySQL
- cURL
- mbstring
- OpenSSL
- mod_rewrite (Apache) ou regra equivalente no Nginx

## URLs previstas

Local:
- `http://localhost/tecnodata-crm/public`

Produção:
- `https://tecnodataeducacional.com.br/crm`

## Configuração

Edite somente:

`config/config.php`

Ali estão:
- banco local
- banco produção
- Omie app_key/app_secret
- URL local/produção
- parâmetros de sincronização
- usuário administrador inicial
- token do instalador

## Instalação

1. Crie o banco vazio.
2. Ajuste `config/config.php`.
3. Acesse:
   - local: `/install.php?token=SEU_TOKEN`
   - produção: `https://tecnodataeducacional.com.br/crm/install.php?token=SEU_TOKEN`
4. Após instalar, defina `installer.enabled => false`.
5. Entre com o administrador configurado.
6. Cadastre/associe vendedores em **Admin > Usuários**.
7. Rode **Admin > Sincronização Omie**.

## Segurança

- `config/`, `app/`, `database/`, `storage/` são bloqueados por `.htaccess`.
- Senhas usam `password_hash`.
- Sessão usa cookies HttpOnly e SameSite=Lax.
- Formulários usam CSRF.
- SQL usa prepared statements.
- Sincronização Omie é manual/agendada, nunca disparada a cada navegação.
- Credenciais não aparecem na interface.


## Atualização sem consultar a Omie nas telas

As telas sempre leem o banco local. Para manter a cobrança atualizada, agende `php cron/sync.php financial` nos horários definidos (por exemplo: 06h, 12h, 18h e 23h). A rotina consulta somente as contas escolhidas, os títulos atrasados/parciais e os últimos 3 anos. Para uma atualização completa eventual, use `php cron/sync.php all` fora do horário comercial.

## V2 — Sincronização modular

A V2 não mantém uma única requisição PHP aberta tentando baixar tudo.

Cada módulo trabalha página a página:

1. Vendedores
2. Clientes
3. Pedidos
4. Financeiro
5. Indicadores

Cada clique inicia/retoma um módulo. O navegador processa uma página, aguarda a pausa configurada e solicita a próxima.

Vantagens:
- evita timeout do PHP/Apache;
- reduz risco de limite temporário da Omie;
- retry automático para HTTP 429, 408 e 5xx;
- backoff exponencial;
- uma sincronização por vez;
- estado persistente no banco;
- retomada após fechar navegador;
- execução antiga "correndo" é liberada automaticamente após o lock expirar.

### Atualizando uma instalação V1 existente

1. Preserve seu `config/config.php` atual.
2. Suba os arquivos V2.
3. Ajuste somente a seção `sync` do config, se necessário.
4. Ative temporariamente `installer.enabled = true`.
5. Acesse:
   `/upgrade-v2.php?token=SEU_TOKEN`
6. Desative novamente o instalador.
7. Abra `Sincronização Omie`.

## V6 — Perfil de cobrança e carteira geral de devedores

### Novo perfil
- `collector` / **Cobrança**: vê todos os devedores das contas financeiras selecionadas, sem ficar preso a um vendedor Omie.
- Vendedor continua vendo somente sua carteira comercial e pode filtrar por situação financeira.
- Supervisor/Admin acessam a carteira geral, agenda e desempenho da cobrança.

### Fluxo de cobrança
1. Abra **Cobrança → Carteira de devedores**.
2. A tabela mostra cliente, vendedor, UF, última compra, dias, status financeiro, valor devido e último contato.
3. Clique em **Atender**.
4. Registre canal, resultado, promessa, próximo retorno e observação.
5. Se houver recebimento, selecione **Pagamento recebido** e informe o valor.
6. O valor entra no realizado da meta de cobrança e reduz o saldo local imediatamente.
7. Se o saldo chegar a zero, o cliente é sinalizado como **Quitado** e sai da visão padrão de pendentes.
8. A próxima sincronização financeira reconcilia o abatimento local com o Omie.

### Atualizando instalação existente
1. Faça backup da pasta e do banco.
2. Preserve o `config/config.php` que já contém suas credenciais.
3. Suba os arquivos da V6.
4. Ative temporariamente `installer.enabled = true`.
5. Acesse `/upgrade-v6.php?token=SEU_TOKEN`.
6. Desative novamente `installer.enabled`.
7. Em **Usuários**, crie a pessoa de cobrança com perfil **Cobrança**.
8. Em **Metas do mês**, defina a meta financeira e a meta de clientes trabalhados para a pessoa de cobrança.

### Observação sobre recebimentos locais
O Omie continua sendo a fonte financeira oficial. Um pagamento informado pela cobrança é tratado como ajuste local pendente de reconciliação, para que a equipe veja o saldo reduzido imediatamente sem precisar esperar a próxima sincronização. Quando o Omie refletir o recebimento, o ajuste pendente é reconciliado automaticamente ao concluir a sincronização financeira.


## V6.1 — correção de collation

Corrige o erro MySQL 1267 `Illegal mix of collations` na carteira de cobrança.

Mudanças:
- removido `DATE_FORMAT(...)=?` da contagem mensal;
- consultas mensais usam intervalo de datas;
- conexão PDO força `utf8mb4_unicode_ci`;
- `upgrade-v6.1.php` padroniza as tabelas existentes;
- correção do usuário atual em `cobranca.php`.

Atualização:
1. mantenha seu `config/config.php`;
2. suba os arquivos V6.1;
3. temporariamente deixe `installer.enabled = true`;
4. acesse `/upgrade-v6.1.php?token=SEU_TOKEN`;
5. depois volte `installer.enabled = false`.


## V6.2 — status financeiro Omie

Corrige o filtro da API de movimentos financeiros.

A Omie aceita:
`PAGTO_PARCIAL`

e não:
`PAGTOPARCIAL`

A V6.2:
- usa `ATRASADO` e `PAGTO_PARCIAL` nas chamadas oficiais;
- corrige automaticamente contextos financeiros gravados por versões anteriores;
- normaliza respostas/linhas legadas;
- preserva o progresso da sincronização quando possível.

Para instalação já existente:
1. mantenha seu `config/config.php`;
2. suba a V6.2;
3. ative temporariamente `installer.enabled`;
4. acesse `/upgrade-v6.2.php?token=SEU_TOKEN`;
5. desative novamente o instalador;
6. em Sincronização Omie, execute/retome apenas Financeiro.


## V6.3 — Alertas de atendimento

- sino no topo com contador de atrasados;
- painel dos próximos retornos;
- aviso preventivo padrão de 10 minutos;
- notificação + som no horário;
- uma repetição padrão após 15 minutos;
- atrasados permanecem visíveis até o atendimento;
- preferências por usuário;
- promessa de pagamento gera compromisso quando não há retorno explícito no mesmo dia;
- ao registrar o atendimento, o retorno correspondente é concluído;
- polling de 60 segundos somente no banco local, sem consultar a Omie.

Atualização: preserve `config/config.php`, suba a V6.3, ative temporariamente o instalador, acesse `/upgrade-v6.3.php?token=SEU_TOKEN`, desative o instalador e abra `Alertas` para autorizar o navegador.


## V6.4 — Gestão inteligente e atendimentos auditáveis

Principais mudanças:
- supervisor/admin entram diretamente em **Visão de gestão**;
- realizado geral = vendas + serviços + valores recuperados pela cobrança;
- painel mostra clientes únicos atendidos, acordos fechados, retornos e carteira de cobrança;
- carteiras de vendas e cobrança sinalizam **Atendido**, **Acordo** e **Não trabalhado**;
- filtros por atendimento/acordo;
- vendas passam a ter o resultado **Acordo fechado**;
- atendimentos já salvos podem ser editados ou excluídos;
- criador edita/exclui o próprio registro; supervisor/admin podem corrigir qualquer registro;
- exclusão é lógica (soft delete) e não apaga a trilha de auditoria;
- edição/exclusão de pagamento de cobrança recalcula o saldo local ainda pendente e os indicadores;
- tabela `interaction_audit` registra antes/depois, usuário e horário.

Atualização:
1. preserve `config/config.php`;
2. suba os arquivos V6.4;
3. ative temporariamente `installer.enabled`;
4. acesse `/upgrade-v6.4.php?token=SEU_TOKEN`;
5. desative o instalador;
6. abra **Visão de gestão**.


## V6.4.1 — correção do upgrade no MariaDB

Corrige o erro SQL 1064 causado por `SHOW COLUMNS ... LIKE ?`.
MariaDB não aceita placeholder preparado nessa forma de `SHOW`.

A verificação de colunas agora usa `information_schema.COLUMNS`, compatível com MariaDB/MySQL.

Para quem recebeu erro ao executar V6.4:
1. suba a V6.4.1;
2. mantenha `installer.enabled = true` temporariamente;
3. acesse `/upgrade-v6.4.1.php?token=SEU_TOKEN`;
4. confirme a conclusão;
5. volte `installer.enabled = false`.
