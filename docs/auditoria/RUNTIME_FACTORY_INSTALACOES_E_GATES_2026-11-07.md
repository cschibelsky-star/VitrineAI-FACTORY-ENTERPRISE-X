# Factory — diagnóstico de runtime e fluxo de execução (07/11/2026)

## Escopo e método

Consultas **somente leitura** via conector V5 Centro Operacional (`connector_health`, `project_context`, `project_status`, `project_git_status`, `project_docker_status`, `project_read_file`). Não foram executados `artisan`, jobs, migrations, testes E2E ou builds neste diagnóstico. Nenhum deploy, merge, reset ou limpeza foi realizado.

## Ambientes efetivamente identificados

| Identificador do registry | GitHub/branch indicada pelo manifesto | Git local observado | Docker observado | Interpretação |
|---|---|---|---|---|
| `factory` | `cschibelsky-star/VitrineAI-Factory`, manifesto `consolidation/factory-baseline-v1` | `fix/via-factory-gates-20261020` limpo | `vitrine_factory_hml` ativo e healthy, 8080 local | Runtime real presente; divergência entre branch declarada e checkout exige reconciliação **sem reset** |
| `vitrine-ai-pro-factory` | `cschibelsky-star/vitrine-ai-pro`, `feature/factory-enterprise-10-ui` | mesmo nome da branch, limpo | app PHP, MariaDB (healthy), Nginx 18083 ativos | Forte candidato a runtime Laravel da Factory; execução de worker/Artisan não comprovada |
| `vitrine-factory-enterprise-x` | `cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X`, main | main limpo | nenhum container próprio retornado | Repositório de componentes/documentação; **não confundir ausência de container próprio com ausência de código em outro runtime** |
| `vitrine-ai-pro-factory-e2e-school-hml` | `cschibelsky-star/vitrine-ai-pro`, `feature/factory-enterprise-10-ui` | behind 2 e árvore com muitas alterações staged/unstaged | não consultado | **Preservar** integralmente trabalho E2E existente, inclusive recursos, controllers, migrations e testes; sem limpar, resetar, atualizar ou migrar |

## Rastreamento de código presente na VPS

1. `vitrine-ai-pro-factory/app/Factory/Production/Services/FactoryProductionOrchestrator.php`: possui produção de relatório de etapas, mas registra explicitamente `next_stage` para conectar Builder/Installer. Verificação por leitura do arquivo do workspace atual, não do repositório Enterprise X.
2. `vitrine-ai-pro-factory/app/Factory/RealBuilder/Services/RealBuilderNameService.php`: há mapeamento concreto para entidades de escola (alunos, professores, matrículas, cursos, turmas etc.) — confirma parte da infraestrutura do gerador, **não a execução ponta-a-ponta**.
3. `vitrine-factory-enterprise-x/app/CommercialFactory/Services/CommercialFactoryIntakeService.php` no checkout `main` ainda contém `--dry-run => true` e mapeia exit 0 para `finished` / `ready_for_homologation`. Correção proposta no PR #16 **não está implantada**; não alterar main sem revisão.
4. `factory/AGENTS.md` não foi encontrado no checkout atual, apesar do arquivo existir na branch do PR #20. Isso reforça que PR pendente não é regra operacional ativa.

## Risco P0 — fontes e ambientes diferentes

Uma parte dos recursos da Factory está no repositório Enterprise X, outra no `vitrine-ai-pro` com serviço Laravel ativo e outra no `VitrineAI-Factory` com container próprio. Antes de conectar a VIA ou declarar um worker ausente, identificar com precisão:
- comando/entrypoint de cada container e registro de comandos Artisan no runtime Laravel;
- rotas instaladas (não apenas arquivos existentes);
- filas, scheduler, workers e consumidores de `AiQueue` e filas da Factory;
- dependências entre Factory Core, Enterprise X e runtime `vitrine-ai-pro`;
- origem de cada build real e diferença entre simulação, geração de arquivo, testes e homologação;
- status do piloto escola E2E e seus artefatos **sem tocar na árvore suja**.

## Critério de próximo passo

Em ambiente HML e **somente leitura**, consultar registros reais via mecanismos do conector permitidos (p. ex., `route:list`, `artisan list`, worker/processos, observabilidade e logs existentes), garantindo primeiro identificação do serviço correto. Não executar comandos que criem produto ou alterem dados. Catalogar mapa entrada → fila → executor → artefato → QA → revisão VIA, exigindo evidências por SHA.

## Status CI PR #16

Após correção do teste de regressão, consultas ao GitHub registraram no commit `fd4eb944e4b4f1a4dbca94e8b417e560854f2229`:
- `Factory Dry-run Classification Safety`: **success**;
- `Core Tests`: **success**.

**CI aprovado não implica deploy, integração Laravel completa ou compatibilidade externa.** PR #16 permanece separado e exige verificação de consumidores que interpretavam `finished` como build real.

## Handoff

Recomendação: não criar novo worker, engine, instalador ou painel antes de mapear e testar os serviços já ativos. Preservar árvores de trabalho, selecionar implementações canônicas por provas, integrar depois com contratos e gates fail-closed da VIA.
