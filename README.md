# Tecnodata CRM

CRM comercial da Tecnodata Educacional.

## Ambientes

### Desenvolvimento — XAMPP

Clone o projeto em:

```
C:\\xampp\\htdocs\\tecnodata-crm
```

A URL do projeto é:

```
http://localhost/tecnodata-crm
```

Não use `/public` na URL.

O banco local padrão é MySQL/MariaDB no próprio computador:

- host: `127.0.0.1`
- porta: `3306`
- usuário padrão XAMPP: `root`

Copie `config/config.example.php` para `config/config.php` e informe o nome do seu banco local.

### Produção

URL oficial:

```
https://tecnodataeducacional.com.br/crm
```

O projeto deve ficar dentro do diretório público correspondente a `/crm`. O `.htaccess` da raiz envia apenas as rotas e assets públicos para `public/` e bloqueia acesso web direto a `app/`, `config/` e `database/`.

No `config/config.php`, mantenha:

```php
'production_url' => 'https://tecnodataeducacional.com.br/crm'
```

Quando PHP e MariaDB estiverem na mesma hospedagem, use banco com host `127.0.0.1` (ou `localhost`, caso o provedor exija).

Nunca versione `config/config.php`: ele está no `.gitignore`.

## Banco e segurança

- Todas as tabelas do CRM usam prefixo `tdcrm_`.
- Credenciais de produção ficam somente em `config/config.php` ou variáveis de ambiente.
- O código também aceita:
  - `TDCRM_APP_URL`
  - `TDCRM_DB_LOCAL_HOST`, `PORT`, `NAME`, `USER`, `PASS`
  - `TDCRM_DB_PROD_HOST`, `PORT`, `NAME`, `USER`, `PASS`
- Sessão usa cookie `HttpOnly`, `SameSite=Lax` e `Secure` em produção.
- O cookie é limitado ao caminho do CRM (`/crm/` em produção).

## Stack

- PHP 8.2+
- MySQL/MariaDB
- Bootstrap 5
- JavaScript nativo
- Omie como ERP
- Navegação operacional usando banco local
