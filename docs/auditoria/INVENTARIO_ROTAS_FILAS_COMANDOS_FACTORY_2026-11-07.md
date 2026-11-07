# Inventário de entradas, comandos, filas e vínculos — Factory Enterprise X

Data: 2026-11-07. Escopo: revisão de código disponível na branch principal dos repositórios. **Não prova registro efetivo de rotas, execução de jobs ou funcionamento no runtime.**

## 1. Entradas HTTP identificadas

| Local | Declaração | Destino | Situação |
|---|---|---|---|
| `routes/site_contratar.php` | GET `/contratar` | `SiteContratarController::create` | Declarada, montagem no roteador principal não verificada |
| `routes/site_contratar.php` | POST `/contratar` | `SiteContratarController::store` | Declarada, montagem não verificada |
| `routes/site_factory_api.php` | POST `/site/factory/intake` | `SiteFactoryIntakeController` | Declarada, montagem e autenticação no runtime não verificadas |
| `Modules/Cadastro/routes/web.php` | GET `/cadastro`, POST/PUT/DELETE de pessoas, impressão | `PersonController` / `PersonPrintController` | Declaradas sob auth/tenant/module/permission; provider carrega o arquivo |
| `Modules/Cadastro/routes/web.php` | `/cadastro-publico/{tenant:slug}` GET/POST | `PublicRegistrationController` | Declarada sob web; autorização e regras do controller exigem avaliação |
| `Modules/Cadastro/routes/api.php` | GET `/api/cadastro/health` | closure de status | Declarada sob api/auth/tenant/module |

Arquivos usuais `routes/web.php`, `routes/api.php`, `routes/console.php`, `app/Console/Kernel.php` e `bootstrap/app.php` não foram encontrados por fetch nos caminhos consultados. Isso **não prova** ausência da infraestrutura em ambiente implantado nem impossibilidade de registrar os trechos; exige `route:list` na aplicação HML correta e inventário do mecanismo de montagem.

## 2. Comandos Artisan identificados por arquivo e assinatura
| Comando | Arquivo | Ação declarada |
|---|---|---|
| `commercial:factory-intake` | `app/Console/Commands/CommercialFactoryIntakeCommand.php` | Chama `CommercialFactoryIntakeService::intake`; oferece `--dry-run` |
| `commercial:factory-status` | `app/Console/Commands/CommercialFactoryStatusCommand.php` | Lista registros do serviço de status |
| `site:factory-test` | `app/Console/Commands/SiteFactoryApiTestCommand.php` | Testa intake interno com `dryRun=true` |

O inventário histórico `MATRIZ_COMPONENTES_FACTORY.csv` lista outros comandos (`FactoryProcessBuildQueueCommand`, `FactoryRealBuildCommand`, `FactoryProduceEnterpriseCommand`, etc.). A busca de código **não localizou seus arquivos canônicos ativos** nos caminhos esperados. Logo, registros no inventário histórico não são equivalentes a executáveis atuais. Confirmar `artisan list` e providers em HML.

## 3. Descoberta crítica: intake comercial sempre invoca build em dry-run

Em `app/CommercialFactory/Services/CommercialFactoryIntakeService.php`, o método `intake(array $data, bool $dryRun=true)` registra o parâmetro no JSON, mas chama invariavelmente:
```php
Artisan::call('factory:build-and-install', [
  'request' => [$prompt],
  '--dry-run' => true,
]);
```
Portanto, a passagem de `dryRun=false` **não comprova uma construção real**, e um exit code 0 pode gerar o status `finished` e `ready_for_homologation` com somente prévia. Isso deve ser classificado como risco de **falso positivo operacional**, não como release funcional. Não mudar a flag nesta auditoria: remover `--dry-run` sem gates/autorização seria inseguro.

O controller `SiteFactoryIntakeController` transforma esse status em HTTP 201 e mensagem de envio à homologação. Antes de integrar pedidos reais: separar explicitamente `simulated`, `built`, `tested`, `hml_verified`, com evidências vinculadas ao commit e artefato.

## 4. Fila e registros de agentes

| Elemento | O que o código demonstra | O que não demonstra |
|---|---|---|
| `App\Models\AiAgent` | Relações com providers, queues, executions, consumptions | Agente executando tarefa real |
| `App\Models\AiQueue` | Payload, resultado, timestamps, agente/empresa/produto/licença | Worker consumindo itens |
| `App\Models\AiExecution` | Input, output, metadata, status, custo estimado | Log emitido por execução autenticada |
| `AiQueueResource` | Formulário CRUD com status pendente/processando/concluído/erro | Garantia de transições por gates |
| `AiExecutionResource` | Edição manual de logs e status | Evidência imutável de QA/CI |
| `App\Models\AiConsumption` | Campo `estimated_cost` e relacionamentos | Cobrança confirmada de provedor |

Busca de usos `AiQueue::` e `dispatch(` não encontrou consumidor/dispatch correspondente no escopo indexado. Resultado: **worker da fila não comprovado**, e ausência na busca não é prova definitiva de inexistência. Recomenda-se pesquisar bindings, jobs, supervisor e scheduler no HML antes de implementar outro.

## 5. Serviços da produção inspecionados
- `FactoryProductionOrchestrator::produce`: gera relatório em storage, chama releases/decision/workflow/manifest/docs/QA; indica explicitamente Builder/Installer como etapa futura.
- `DecisionEngine::decide`: roteia domínios por palavras-chave e retorna lista de módulos e componentes; não comprova compreensão semântica de requisitos.
- `WorkflowDesigner::design`: transições sequenciais estáticas por domínio; não é máquina de estados de agentes com retry/gates.
- `SmartQa2Service::inspect`: checa configurações/pastas e pode criar diretórios; um status `passed` **não comprova build, testes ou segurança do produto**.
- `FactoryReleaseManager::status`: lê versão/status do config; não é evidência de deployment.

## 6. Checklist operacional a executar na HML identificada
1. Confirmar commit/branch/container e serviço web efetivos; separar Core, Factory Enterprise X e Factory Core.
2. Executar `php artisan route:list` e `php artisan list` no serviço certo, somente leitura. Comparar com inventário acima.
3. Confirmar handlers de endpoints, permissões, middlewares e resolução do `SiteFactoryIntakeController`.
4. Inspecionar processos de worker, configuração de filas/scheduler e jobs registrados, sem executar pedidos reais.
5. Consultar logs de uma execução **já existente**, correlacionar `AiQueue` -> `AiExecution` -> commit/artefato/testes -> HML.
6. Confirmar ambiente com dados de teste e o que dry-run produz; nunca marcar `finished` como `built`.
7. Somente após essas provas, selecionar implementações canônicas e desenhar a integração Factory/VIA.

## 7. Prioridade de correção proposta (sem alterar runtime)
P0: eliminar falso positivo de conclusão de build; manter dry-run de forma segura, mas distinguir status de simulação.
P1: descobrir registro real de rotas/comandos e worker; rastrear fila ao executável.
P1: substituir checks de diretório por gates reais de QA, CI e evidência.
P2: consolidar interfaces duplicadas somente após uso real demonstrado.

## Handoff
Mudanças efetuadas nesta rodada: somente documentação nesta branch. Nenhuma mutation de produção/HML, API paga, merge ou migração. Resultado dos testes funcionais: **não executados**; foco em mapeamento estático.
