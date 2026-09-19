# Tecnodata LMS v0.1

Base funcional do novo LMS Tecnodata, construída em PHP 8.3+, Bootstrap 5, JavaScript e DataTables, com arquitetura API-first e importador Moodle integrado.

## Objetivo

- experiência moderna e mobile-first para aluno, professor e administração;
- cursos com seções, subseções e atividades;
- matrículas recebidas por múltiplos sistemas externos;
- REST API versionada em /api/v1;
- importação progressiva de backups Moodle .mbz;
- rastreabilidade por IDs legados;
- horário oficial da aplicação: America/Sao_Paulo.

## Requisitos

- PHP 8.3 ou superior
- MySQL/MariaDB 10.6+
- extensões PDO MySQL, mbstring, SimpleXML/XML, Phar, JSON
- Apache com mod_rewrite ou Nginx equivalente

## Instalação na Hostinger

1. Envie o conteúdo deste projeto para a pasta do domínio.
2. Copie .env.example para .env.
3. Configure DB_HOST=127.0.0.1, DB_DATABASE e DB_USERNAME conforme o banco de produção.
4. Preencha DB_PASSWORD diretamente no servidor. A senha não está embutida no projeto.
5. Garanta permissão de escrita em storage/.
6. Abra /public/install.php ou /install.php conforme o document root.
7. Crie o primeiro administrador.
8. Ao concluir, o instalador cria storage/installed.lock e deixa de aceitar nova instalação.

Banco de produção previsto:
- host: 127.0.0.1
- database: u695906402_lms_tecnodata
- user: u695906402_lms_tecnodata
- timezone: America/Sao_Paulo / sessão SQL -03:00

## Segurança

Nunca versione .env. Troque a senha do banco antes da entrada oficial em produção, porque ela foi compartilhada em conversa durante a implantação.

## Estrutura

app/
  Core/
  Controllers/
  Services/
config/
database/
docs/
public/
routes/
storage/
views/

## Estado desta versão

Esta é uma fundação funcional para desenvolvimento e homologação. Já contém autenticação, papéis básicos, painel, cursos, estrutura, alunos, matrículas, API, clientes de API, progresso, auditoria e importador Moodle estrutural para os componentes já mapeados. A migração 100% fiel de todos os plugins Moodle será ampliada por conversores, sempre bloqueando importações desconhecidas em vez de descartá-las silenciosamente.
