# API v1

Autenticação:
Authorization: Bearer SEU_TOKEN

Crie tokens no painel Administração > Integrações API.

## Aluno

POST /api/v1/students/upsert

JSON:
{
  "cpf":"00000000000",
  "name":"Nome do aluno",
  "email":"aluno@exemplo.com"
}

Se initial_password não for enviado, a versão inicial usa o CPF como senha para facilitar a migração legada. Recomenda-se migrar para uma política mais forte antes da abertura para outros públicos.

## Matrícula

POST /api/v1/enrollments/upsert

Header recomendado:
Idempotency-Key: origem-pedido-curso

JSON:
{
  "student":{"cpf":"00000000000"},
  "course":{"code":"RECICLAGEM-PR"},
  "source":{"system":"woocommerce","external_id":"24540"}
}

## Progresso

GET /api/v1/enrollments/{id}/progress

## Elegibilidade para certificação

GET /api/v1/certification/eligibility?cpf=00000000000&course=RECICLAGEM-PR

A v0.1 considera elegível matrícula concluída com progresso 100%. Regras regulatórias específicas serão implementadas no Compliance Engine sem acoplar o LMS ao DETRAN.
