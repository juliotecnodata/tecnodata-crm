# Tecnodata CRM

Repositório principal do CRM Tecnodata.

## Segurança

O arquivo `config/config.php` não é versionado. Copie `config/config.example.php` para `config/config.php` e preencha as credenciais localmente.

Nunca envie para o Git:
- credenciais do banco;
- app_key/app_secret da Omie;
- senha do administrador;
- tokens do instalador.

## Fluxo

Atualizar a máquina local:

```bash
git pull origin main
```

As alterações do projeto passam a ser versionadas neste repositório.
