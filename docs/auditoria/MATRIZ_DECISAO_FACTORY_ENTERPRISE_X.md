# MATRIZ DE DECISÃO — FACTORY ENTERPRISE X

## Componentes analisados
79 componentes únicos catalogados na Sprint Factory.

## Núcleo obrigatório da Release 1.0

### Engines
- DecisionEngine
- WorkflowDesigner
- DocumentationGenerator
- BuildHistoryService
- FactoryReleaseManager
- ProductGenerator
- SmartQa2Service
- FactoryProductionOrchestrator
- EnterpriseProductionEngine
- ProduceRequestPipeline
- RealCodeGenerator
- RealBuildInstaller
- FinishProjectService

### Comercial integrado
- CommercialFactoryIntakeService
- CommercialFactoryStatusService
- SiteCommercialOrderService
- SiteOrdersStatusCommand
- FactoryEnqueueSiteOrdersCommand
- FactoryProcessBuildQueueCommand
- FactoryBuildQueueService
- FactoryBlueprintCatalogService

### Interface
- EnterpriseDashboard
- FactoryStudioEnterprise
- FactoryStudio
- MarketplaceEnterprise
- GeneratedProjects
- AiCenterEnterprise
- ClientPortalEnterprise
- NextGenOperationDashboardController
- NextGenOperationMetricsService

## Duplicidades a resolver
- EnterpriseDashboard: 5 versões
- CommercialFactoryStatusService: 3 versões
- FactoryStudioEnterprise: 3 versões
- MarketplaceEnterprise: 3 versões
- GeneratedProjects: 3 versões
- EnterpriseProductionEngine: 2 versões
- SiteCommercialOrderService: 2 versões
- NextGenOperationDashboardController: 2 versões

## Decisão
Não desenvolver novos componentes equivalentes.

Selecionar a melhor implementação existente, consolidar e integrar ao repositório oficial.

## Regra
Nenhum ZIP será aplicado diretamente.
Toda integração será feita componente por componente.
