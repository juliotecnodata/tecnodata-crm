# Arquitetura do Tecnodata LMS

O LMS é **API-first**. A interface web e os sistemas integradores usam o mesmo domínio acadêmico.

## Camadas

- **Core**: ambiente, banco, autenticação, sessão, CSRF, roteamento e relógio.
- **Papéis**: roles + permissions + atribuições por contexto (sistema/curso).
- **Acadêmico**: cursos, seções, subseções, atividades, questões e avaliações.
- **Matrículas**: aluno, matrícula, referências externas e histórico de status.
- **Estudo**: progresso, sessões, eventos e conclusão.
- **API**: autenticação por cliente, scopes, idempotência, trace IDs e auditoria.
- **Migração**: importador Moodle .mbz com mapeamento legado.

## Ambientes

Produção:
`https://lms.tecnodataeducacional.com.br`

Local:
`http://lms.local`

Os dois ambientes usam o mesmo banco MariaDB por decisão operacional. O local deve manter `LOCAL_SAFE_MODE=true`.

## Timezone

- PHP: `America/Sao_Paulo`.
- sessão MySQL/MariaDB: `-03:00`.
- DATETIME é persistido no horário operacional do Brasil.
- API retorna datas ISO-8601 com offset brasileiro.

## Vídeos

- vídeos não são armazenados no LMS;
- Video Front é referenciada por URL;
- o LMS controla matrícula, permissão, estudo e progresso;
- telemetria detalhada de player será acrescentada no Study Engine.

## Moodle

O Moodle é fonte de migração, não modelo visual.

O importador:
1. extrai o .mbz em área temporária;
2. inventaria os componentes;
3. bloqueia tipos desconhecidos;
4. cria o curso como rascunho;
5. preserva IDs em `legacy_mappings`;
6. converte `subsection` em hierarquia de seções quando o backup fornece o vínculo.

Conteúdos que ainda exigem conversão profunda, como arquivos físicos, `@@PLUGINFILE@@` e question bank completo, são marcados como pendentes em vez de serem silenciosamente descartados.

## Fronteira DETRAN

O LMS não comunica diretamente com DETRAN. Ele é a fonte acadêmica: matrícula, estudo, progresso, avaliação e conclusão. O sistema certificador consulta a API e executa a integração regulatória.
