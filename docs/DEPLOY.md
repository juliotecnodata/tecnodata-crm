# Deploy

## Produção

URL: `https://lms.tecnodataeducacional.com.br`

Document Root:
```
.../lms.tecnodataeducacional.com.br/public
```

Copie:
```
.env.example -> .env
```

Use:
```
APP_ENV=production
APP_URL=https://lms.tecnodataeducacional.com.br
DB_HOST=127.0.0.1
APP_TIMEZONE=America/Sao_Paulo
SESSION_SECURE=true
LOCAL_SAFE_MODE=false
```

## XAMPP

VirtualHost recomendado:
```apache
<VirtualHost *:80>
    ServerName lms.local
    DocumentRoot "C:/xampp/htdocs/tecnodata-lms/public"

    <Directory "C:/xampp/htdocs/tecnodata-lms/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Adicione ao arquivo hosts do Windows:
```
127.0.0.1 lms.local
```

Copie:
```
.env.local.example -> .env
```

O XAMPP aponta para o mesmo banco remoto. Não use `localhost` no DB_HOST local.

## Mesmo banco nos dois ambientes

Isso foi definido de propósito, mas exige disciplina:
- desenvolvimento local deve manter `LOCAL_SAFE_MODE=true`;
- envio de e-mails, webhooks e integrações externas ficam desabilitados no local;
- alterações de dados feitas no XAMPP são reais, porque o banco é compartilhado;
- testes destrutivos devem usar registros criados especificamente para teste.

## Timezone

PHP:
`America/Sao_Paulo`

MySQL/MariaDB por sessão:
`-03:00`

A API retorna ISO-8601 com offset `-03:00`.

## Permissões

A pasta `storage/` precisa ser gravável pelo PHP. Não exponha a raiz do projeto como Document Root. Somente `public/` deve estar acessível pela web.

## Depois da instalação

- remova ou mantenha bloqueado o instalador pelo `storage/installed.lock`;
- crie clientes de API em Administração > Integrações API;
- importe um curso Moodle de homologação;
- valide aluno, matrícula, estudo, progresso e consulta da API antes de liberar produção.
