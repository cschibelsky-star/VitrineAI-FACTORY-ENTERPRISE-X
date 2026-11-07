# Factory — mapa executor, fila e segurança do build (2026-11-07)

**Natureza**: auditoria estática suplementada por consultas V5 dos ambientes, sem executar comando Artisan ou build. Status de rotas/worker em execução não verificado. Adicionar ao PR #15 sem merge.

## Evidência do runtime Laravel mais completo

Repositório `cschibelsky-star/vitrine-ai-pro` (código GitHub, não o Enterprise X):
- `bootstrap/providers.php` registra `App\\Factory\\Providers\\FactoryServiceProvider`.
- `FactoryServiceProvider::boot` registra `routes/factory.php`, comandos, observers, eventos e policies quando `factory.enabled`; `registerCommands` disponibiliza diversas classes, inclusive `FactoryBuildAndInstallCommand`, `FactoryRealBuildCommand`, `FactoryProduceCommand`, `FactoryEngineTestCommand`.
- `routes/factory.php` define apenas GET `factory/health` com middleware configurável; não expõe endpoint público para construir software.
- `FactoryStudioEnterprise` chama `factory:build-and-install --dry-run` e informa claramente simulação ao operador.
- `app/Commercial/Factory/Services/CommercialFactoryIntakeService.php` diferencia `FactoryStage::SimulationCompleted` de `FactoryStage::BuildCompleted`; diferentemente da implementação do repositório Enterprise X, repassa `dryRun=false` ao comando quando solicitado.
- `FactoryStage` define `intake_received`, `simulation_completed`, `build_queued`, `build_completed`, `provisioning`, `installed`, `health_check_passed`, `ready_for_homologation`, `homologated`, `failed`.

## Fluxo interno efetivamente codificado em FactoryFinalMasterService

`factory:build-and-install` delega a `FactoryFinalMasterService::buildAndInstall`:

1. `factory:finalize-request`: gera/seleciona solicitação e blueprint.
2. `detectLatestBlueprint()`: lê arquivo `finalization_report.json` com mtime mais recente em `storage/app/factory/finalization/productions`.
3. `factory:real-build`: chama `RealCodeGenerator::generate`, mesmo em dry-run.
4. `factory:enterprise-build`: chamado mesmo em dry-run.
5. `factory:real-install` e `factory:enterprise-install`: recebem `--dry-run` quando aplicável.
6. Apenas quando `!dryRun`: `composer dump-autoload`, `optimize:clear` e opcionalmente migration se `migrate=true`.
7. Emite JSON em storage e status `finished` se não detectou falha em etapas rastreadas.

**Correção de interpretação importante:** dry-run não implica 'nenhum arquivo gerado' — comandos de geração rodam antes das instalações simuladas. A afirmação precisa é que *o fluxo não efetua as etapas finais de instalação/autoload/migration nesse modo*; a extensão dos side effects de cada gerador só pode ser determinada lendo suas implementações e executando em ambiente isolado. `simulation_completed` deve ser interpretado como simulação da instalação, não garantia de sandbox sem escrita.

## Riscos P0 antes de operação multiagente

| Risco | Evidência no código | Impacto / gate proposto |
|---|---|---|
| Associação errada de blueprint por concorrência | `detectLatestBlueprint` seleciona mtime global | Duas missões simultâneas podem associar artefato de outra solicitação. Exigir `mission_id`, `blueprint_id`, lock/idempotência e vínculo explícito |
| Dry-run com geração potencialmente gravável | `factory:real-build` e `factory:enterprise-build` executam sempre | Não usar dry-run em workspace compartilhado para teste isolado sem verificar destino do gerador |
| Falha pode não interromper subsequentes | Após `finalize['status'] !== passed`, status muda para failed, mas método continua tentando detectar blueprint | Fail-fast e rastreamento por etapa antes de habilitar execução paralela |
| `finished` não significa QA funcional | Serviço registra saída de subprocessos e relatórios, sem CI/QA independente obrigatório | Separar estados `generated`, `installed`, `tested`, `via_verified`, `homologated` |
| Migração disponível via flag | `--migrate` chega a `migrate --force` no fluxo real | Exigir aprovação distinta; nunca acionar em piloto sem autorização |
| Contratos divergentes entre repos | Enterprise X intake força `--dry-run`; `vitrine-ai-pro` condiciona flag e usa enum de estágios | Escolher contrato canônico pela instância implantada após prova, sem substituir versão ativa indiscriminadamente |

## Fila de execução encontrada em código

`app/Factory/Engine/Jobs/ExecuteBlueprintJob.php`:
- Implementa `Illuminate\\Contracts\\Queue\\ShouldQueue`.
- Recebe `executionId`, busca `FactoryExecution` e chama `FactoryEngine::execute($execution)`.
- **Existe job concreto.** Entretanto, o código inspecionado não comprova um processo `queue:work` ativo, dispatch real desse job, ou sucesso de execução.

`App\\Models\\AiQueue` pertence ao catálogo de filas do Centro IA e **não deve ser confundido** com o job `ExecuteBlueprintJob` da Factory sem demonstrar vínculo.

## Provas restantes para homologação

1. Runtime: confirmar `php artisan list --raw` ou `artisan list` pela ferramenta controlada, apenas se suportar operação somente leitura.
2. Runtime: `php artisan route:list --path=factory`, registro de providers e middleware, sem alteração de dados.
3. Runtime: status de `queue:work`, supervisor e backend de fila; preferir status nativo de processos, sem ler secrets.
4. Analisar `FactoryEngine::execute`, lugares que fazem `ExecuteBlueprintJob::dispatch` e código de `RealCodeGenerator::generate`.
5. Piloto em workspace limpo e isolado, com artefato rastreado a missão e CI; somente após aprovação explícita da mutação em HML.

## Referências e disciplina

- Fonte de verdade: GitHub `cschibelsky-star/vitrine-ai-pro`, branch principal consultada.
- VPS V5: `vitrine-ai-pro-factory` confirmou aplicativo PHP, DB e proxy ativos; isso não certifica job consumido.
- PR #16 de Enterprise X passou CI, mas corrige **outra variante** do intake e não deve ser aplicado ao runtime Laravel sem revisão da divergência.
- Não foi modificado nenhum container, banco, agente, branch suja, deployment ou secret.

**Conclusão:** antes de adicionar novos motores, priorizar rastreabilidade de missão, isolamento do gerador e prova da fila existente.
