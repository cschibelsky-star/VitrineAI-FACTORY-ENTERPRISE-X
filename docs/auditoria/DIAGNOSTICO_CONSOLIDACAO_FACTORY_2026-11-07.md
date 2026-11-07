# Auditoria de consolidação — Factory Autônoma Vitrine IA Pro
Data: 2026-11-07
Status: diagnóstico por inspeção de código GitHub. Não é homologação de runtime ou produção.

## Objetivo
Transformar a Factory existente em fábrica autônoma supervisionada: departamentos, contratos, trabalho real, provas, handoffs e VIA como supervisor. Preservar arquitetura Laravel/PHP e demais stacks de cada projeto. Não recriar sistemas.

## Fontes auditadas
- Este repositório: `docs/auditoria/MATRIZ_DECISAO_FACTORY_ENTERPRISE_X.md`, `docs/auditoria/MATRIZ_COMPONENTES_FACTORY.csv`, `config/factory_release.php`, `app/Factory/Decision/Services/DecisionEngine.php`, `app/Factory/Workflow/Services/WorkflowDesigner.php`, `app/Factory/Production/Services/FactoryProductionOrchestrator.php`, `app/Factory/QA/Services/SmartQa2Service.php`, `app/Factory/Release/Services/FactoryReleaseManager.php`, `app/Models/AiAgent.php`, `AiQueue.php`, `AiExecution.php`.
- `cschibelsky-star/VitrineAI-Factory`: `factory-core/app/Models/FactoryAgent.php`, `FactoryMission.php`, `FactoryRuntimeEngine.php`, `factory-core/database/seeders/FactoryCoreSeeder.php`, `docs/constitution/README.md`, `docs/engineering-standards/README.md`. PR #20 (padrão de excelência, branch ainda sem merge).
- `cschibelsky-star/via`: `runtime/orchestrator.js`, `runtime/server.js`, novos módulos de handoff sob PR #37 (ainda sem merge).
- Referência conceitual fornecida pelo usuário: `Planta do Projeto Autônomo.pdf`, `Projeto Autônomo com IA.pdf`, e vídeo. Referências não constituem código implantado.

## Matriz de estado — não confundir código presente com serviço operacional
| Capability | Evidência localizada | Classificação | Próxima prova |
|---|---|---|---|
| Catálogo/constituição | Diretórios docs, models e seeders Factory Core | Existente, integração não comprovada | Verificar versão canônica e cadastro usado pelo runtime |
| Núcleo comercial e agentes | AiAgent, AiQueue, AiExecution e relacionamentos | Existente, integração não comprovada | Testar processamento de fila real em HML |
| Motor de decisão | DecisionEngine.php analisa palavras-chave e retorna módulos/componentes | Implementado parcialmente | Testes de acurácia e aderência a requisitos |
| Desenho de workflow | WorkflowDesigner.php retorna listas fixas e transições lineares por domínio | Implementado parcialmente | Fluxo por tarefa/agente com estado, retry, bloqueio e dependências |
| Orquestração de produção | FactoryProductionOrchestrator registra passos e relatório; declara Builder/Installer como etapa futura | Implementado parcialmente | Gerar artefato físico isolado com instalação em HML e CI |
| QA | SmartQa2Service verifica existência de configs/pastas e criação de storage | Insuficiente como garantia funcional | Exigir testes unitários, integração, build, regressão, segurança e evidências |
| Release | FactoryReleaseManager publica estado de configuração em JSON | Existente, mas não comprova release executável | Vincular commit, artefato, CI e homologação |
| VIA supervisor | Observer runtime; PR #37 verifica metadados e correlaciona evidências, canAdvance:false | Experimental, desconectado | Integrar somente depois de autenticação e autorização formal |
| Consolidação de interfaces | Matriz histórica aponta oito famílias duplicadas | Duplicidade documentada | Comparar implementação, rotas, uso real e migrar sem perda |
| End-to-end autônomo | Nenhuma prova conjunta observada na inspeção | Não comprovado | Piloto exigindo request -> branch -> contrato -> implementação -> QA -> VIA -> HML |

## Atenção ao indicador de 90%
A hipótese de que 90% dos componentes existe é plausível, mas não é um KPI validado; não foi medido o percentual de requisitos operacionais atendidos nem demonstrada uma missão ponta-a-ponta.

## Arquitetura alvo por responsabilidade (proposta, não migração aprovada)
- Factory Enterprise X: candidato a runtime operacional (orquestração, fila, builds, QA, observabilidade e painel), sujeito a comprovação.
- VitrineAI-Factory: candidato a fonte de padrões, catálogo e modelos fundacionais; verificar relação efetiva com Enterprise X antes de consolidação.
- VIA: supervisor de evidências com decisões auditáveis; começa fail-closed e não assume autoridade de deploy.
- GitHub: fonte canônica de código, PR, commits, workflows e handoffs versionados.
- Core/Cockpit: integrações e apresentação existentes devem ser inventariadas antes de criar novas telas.

Nenhum destes papéis deve ser promovido a fonte canônica sem mapa de rotas, deploy, dados e dependências.

## Primeira ordem de trabalho — organizar sem reescrever
1. Inventário executável: mapear classes, comandos, rotas, jobs, tabelas e telas, com URL de HML e owner para cada capability; classificar ativo/legado/duplicado/desconectado.
2. Matriz de autoridade: identificar quais classes, schemas e serviços realmente atendem as requisições de produção/HML; preservar as versões atuais até teste de equivalência.
3. Máquina de estados única por missão: `requested -> scoped -> planned -> implementing -> testing -> independent_review -> via_review -> hml -> ready_for_human`; estados de falha `blocked`, `needs_correction`, `escalated`; idempotência por tarefa.
4. Gates obrigatórios baseados em resultado real: contratos, testes, CI, revisão independente, segurança e permissões. Nunca avançar por score subjetivo.
5. Catálogo de agentes: especialidade, skills, ferramentas realmente conectadas, entrada/saída, custo, limites, fallback e critérios de pronto; sem fixar modelos por função.
6. Integrar PRs #20 e #37 só após revisão e comprovação; não executar merge automático.
7. Piloto em HML sem custos pagos não autorizados: demanda simples, branch isolada, geração real, QA independente, VIA apenas verifica, aprovação humana antes de release.
8. Após piloto, consolidar dashboards e duplicações por equivalência funcional, com rollback e rastreabilidade.

## Gates de aceite do piloto
- Arquivos efetivamente gerados e commit SHA verificado; contrato validado.
- Testes pertinentes e build executados de fato, artefatos/logs associados ao SHA.
- Revisão independente em GitHub e verificação de estado atual de reviews.
- Handoff estruturado e rastreabilidade de transições.
- Falhas simuladas são bloqueadas e registradas, sem avançar etapas.
- Autorização humana para deploy em HML, merge e produção conforme escopo.
- No mínimo um caso positivo e um negativo integrados antes de declarar operacional.

## Não autorizado nesta auditoria
Nenhum merge, deploy, alteração em banco real, exposição de credenciais, consumo pago de IA ou alteração de agentes em execução.

## Handoff
Próximo responsável: arquitetura/QA da Factory. Primeira implementação: inventário técnico com mapeamento de rotas e jobs efetivos. Prioridade alta: não criar engines, painéis ou agentes duplicados até definir uso real dos componentes.
