# API v1

Base URL de produção:

```
https://lms.tecnodataeducacional.com.br/api/v1
```

## Health

```
GET /api/v1/health
```

Não exige token e retorna estado da aplicação, banco, timezone e horário atual.

## Autenticação

As demais rotas usam:

```
Authorization: Bearer SEU_TOKEN
```

Crie tokens em **Administração > Integrações API**. Cada sistema deve ter token próprio e escopos mínimos.

## Aluno

```
POST /api/v1/students/upsert
```

Scope: `students:write`

Exemplo:

```json
{
  "cpf":"00000000000",
  "name":"Nome do aluno",
  "email":"aluno@exemplo.com"
}
```

O CPF é armazenado sem máscara. Na compatibilidade inicial, a senha padrão pode ser o CPF quando nenhuma senha inicial for fornecida.

## Matrícula / aluno em uma única chamada

```
POST /api/v1/enrollments/upsert
```

Scope: `enrollments:write`

Header recomendado:

```
Idempotency-Key: origem-pedido-curso
```

Exemplo:

```json
{
  "student":{
    "cpf":"00000000000",
    "name":"Nome do aluno",
    "email":"aluno@exemplo.com"
  },
  "course":{"code":"RECICLAGEM-PR"},
  "source":{"system":"woocommerce","external_id":"24540"}
}
```

Se o aluno ainda não existir e `student.name` estiver presente, o endpoint cria o aluno e a matrícula na mesma transação.

## Progresso

```
GET /api/v1/enrollments/{id}/progress
```

Scope: `enrollments:read`

Retorna matrícula, aluno, curso e progresso por atividade.

## Elegibilidade acadêmica para certificação

```
GET /api/v1/certification/eligibility?cpf=00000000000&course=RECICLAGEM-PR
```

Scope: `certification:read`

O LMS informa somente elegibilidade acadêmica. A comunicação com DETRAN permanece no sistema certificador externo.

Nesta versão, a regra base exige matrícula `completed` e progresso de 100%. Regras específicas de cada produto serão adicionadas ao Compliance Engine.

## Horário

Todas as datas da API são retornadas em ISO-8601 usando `America/Sao_Paulo`, por exemplo:

```
2026-09-19T12:30:00-03:00
```

## Rastreamento

Respostas da API contêm `trace_id`. Requisições autenticadas também são registradas em `api_requests` para diagnóstico.
