# Tecnodata LMS v0.2

Base funcional do novo LMS Tecnodata em PHP 8.3+, Bootstrap 5, JavaScript e DataTables, com arquitetura API-first, perfis por contexto e importador Moodle com validação antes da importação.

## URLs oficiais

Produção:
- https://lms.tecnodataeducacional.com.br
- API: https://lms.tecnodataeducacional.com.br/api/v1
- Administração: https://lms.tecnodataeducacional.com.br/admin
- Área do aluno: https://lms.tecnodataeducacional.com.br/student

Desenvolvimento recomendado:
- http://lms.local

O VirtualHost local e o subdomínio de produção devem apontar diretamente para a pasta `public/`.

## Banco

Produção e XAMPP usam o mesmo banco:
- database: `u695906402_lms_tecnodata`
- user: `u695906402_lms_tecnodata`
- produção: `DB_HOST=127.0.0.1`
- XAMPP: `DB_HOST=srv1530.hstgr.io`
- timezone da aplicação: `America/Sao_Paulo`
- sessão SQL: `-03:00`

A senha não é versionada. Preencha somente no arquivo `.env` de cada ambiente.

## O que já funciona

- instalação inicial e criação do primeiro Super Admin;
- autenticação por e-mail, usuário ou CPF;
- papéis e permissões por contexto, no modelo inspirado no Moodle;
- painel administrativo;
- cadastro e estrutura de cursos;
- seções, subseções lógicas e atividades;
- alunos e matrículas;
- área do aluno mobile-first;
- progresso e conclusão manual de atividades;
- vídeos externos, incluindo Video Front por URL;
- clientes de API com tokens e scopes;
- API v1 para alunos, matrículas, progresso e elegibilidade;
- idempotência em integrações;
- auditoria;
- importador Moodle .mbz com análise, bloqueio de tipos desconhecidos e mapeamento legado;
- modo seguro no XAMPP para evitar disparos externos no banco real;
- horário operacional do Brasil em toda a aplicação.

## Instalação em produção

1. Faça upload do projeto.
2. Aponte o Document Root de `lms.tecnodataeducacional.com.br` para `/public`.
3. Copie `.env.example` para `.env`.
4. Preencha `DB_PASSWORD`.
5. Garanta escrita em `storage/`.
6. Abra `https://lms.tecnodataeducacional.com.br/install.php`.
7. Crie o primeiro administrador.
8. Entre em `/login`.

## Instalação no XAMPP

1. Coloque o projeto, por exemplo, em `C:\xampp\htdocs\tecnodata-lms`.
2. Configure um VirtualHost `lms.local` apontando para `C:\xampp\htdocs\tecnodata-lms\public`.
3. Copie `.env.local.example` para `.env`.
4. Preencha a mesma senha do banco.
5. Autorize o IP da máquina no acesso remoto ao MySQL da hospedagem.
6. Não execute `install.php` novamente se o banco já foi instalado em produção.
7. O banner vermelho `AMBIENTE LOCAL · BANCO COMPARTILHADO` deve aparecer.

## Importação Moodle

Fluxo:
1. upload do `.mbz`;
2. inventário de curso, seções e atividades;
3. identificação de componentes suportados e desconhecidos;
4. importação bloqueada quando houver componente sem conversor;
5. criação do curso em rascunho;
6. preservação de IDs Moodle em `legacy_mappings`.

A v0.2 importa estrutura e metadados dos componentes já suportados. Migração de histórico de alunos/progresso será feita separadamente por API/banco, não dentro do .mbz.

## Segurança

Nunca versione `.env`. Como uma credencial de banco foi compartilhada durante a implantação, troque a senha antes da entrada oficial em produção e atualize apenas os arquivos `.env`.

Veja `docs/DEPLOY.md`, `docs/API.md` e `docs/ARCHITECTURE.md`.
