# Tecnodata LMS 1.0

LMS próprio da Tecnodata Educacional em PHP 8.3+, MariaDB, Bootstrap 5, JavaScript e DataTables.

Produção: `https://lms.tecnodataeducacional.com.br`

Local recomendado: `http://lms.local`

## Módulos incluídos

- autenticação por e-mail, usuário ou CPF;
- papéis e permissões;
- equipe interna;
- alunos;
- matrículas e histórico de status;
- cursos, seções, subseções e atividades;
- regras de conclusão, prazo e navegação;
- arquivos protegidos por matrícula;
- banco de questões por curso;
- múltipla escolha, verdadeiro/falso, resposta curta, numérica e associação;
- criação/configuração de quizzes;
- questões fixas ou sorteio por categoria;
- tentativas, correção automática, nota e aprovação;
- progresso e conclusão;
- área do aluno mobile-first;
- dashboard e relatórios;
- auditoria;
- clientes API com scopes;
- idempotência;
- API de aluno, matrícula, progresso, elegibilidade e política biométrica;
- importador Moodle .mbz;
- mapeamento de IDs Moodle;
- perfis biométricos por curso/papel/checkpoint;
- conexão para banco biométrico separado;
- atualizações de banco pelo próprio painel;
- modo seguro para XAMPP usando o mesmo banco da produção.

## Bancos

Acadêmico:
`u695906402_lms_tecnodata`

Produção conecta com:
`DB_HOST=127.0.0.1`

XAMPP conecta com:
`DB_HOST=srv1530.hstgr.io`

O banco biométrico é independente e só precisa ser criado quando a validação facial for ativada.

## Instalação de produção

1. Faça upload do projeto.
2. Aponte o Document Root do subdomínio para a pasta `public/`.
3. Copie `.env.example` para `.env`.
4. Preencha `DB_PASSWORD`.
5. Garanta escrita em `storage/`.
6. Abra `https://lms.tecnodataeducacional.com.br/install.php`.
7. Crie o Super Administrador.
8. Entre em `/login`.

## Atualizações

Não rode o instalador novamente. Use:

`Administração → Sistema → Aplicar atualizações`

As migrations ficam em `database/*.sql`.

## XAMPP

Configure um VirtualHost apontando diretamente para:

`C:\xampp\htdocs\tecnodata-lms\public`

Copie `.env.local.example` para `.env`.

O modo local exibe um banner porque as alterações feitas localmente atingem o mesmo banco de produção.

## Moodle

O importador nunca descarta silenciosamente um tipo desconhecido. O curso é criado como rascunho e precisa ser revisado antes de publicação.

## Biometria

O banco acadêmico guarda regras, não selfies. Evidências e tentativas biométricas ficam em banco separado. Capturas, quando realmente necessárias, devem ficar em storage privado e não como BLOB no MariaDB.

## Segurança

Não versione `.env`. Troque a senha do banco que foi compartilhada durante a implantação antes da abertura oficial ao público.


## Cron

O LMS possui manutenção própria em `cli/cron.php`. Execute a cada 5 minutos em produção, usando PHP 8.3:

```bash
/opt/alt/php83/usr/bin/php /CAMINHO/DO/LMS/cli/cron.php
```

O cron:
- aplica migrations pendentes;
- expira matrículas cujo prazo terminou;
- fecha sessões de estudo abandonadas;
- limpa chaves de idempotência antigas;
- reduz telemetria antiga da API;
- remove arquivos .mbz temporários após a retenção configurada.

Configure `IMPORT_FILE_RETENTION_DAYS=7` no `.env` se desejar outro prazo.

Para diagnóstico por terminal:

```bash
php cli/health.php
```
