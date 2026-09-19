# Arquitetura

O LMS é API-first. A interface web e os sistemas integradores consomem o mesmo domínio acadêmico.

Camadas:
- Core: ambiente, banco, autenticação, sessão, CSRF, roteamento.
- Acadêmico: cursos, seções, atividades, questões e avaliações.
- Matrículas: aluno, matrícula, referências externas.
- Estudo: progresso, sessões e eventos.
- API: autenticação por cliente, escopos, idempotência.
- Migração: importador Moodle .mbz com mapeamento legado.

Timezone:
- PHP: America/Sao_Paulo.
- sessão MySQL: -03:00.
- DATETIME é persistido no horário operacional do Brasil.
- API retorna datas ISO-8601 com offset -03:00 via Clock.

Vídeos:
- não são armazenados no LMS;
- URL Video Front é persistida como conteúdo externo;
- evolução futura: player integrado e telemetria de progresso.

Princípio de migração:
nenhum componente desconhecido deve ser descartado silenciosamente. O importador marca o pacote como blocked até existir conversor.
