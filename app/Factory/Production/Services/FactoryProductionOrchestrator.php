<?php

declare(strict_types=1);

namespace App\Factory\Production\Services;

use App\Factory\Decision\Services\DecisionEngine;
use App\Factory\Documentation\Services\DocumentationGenerator;
use App\Factory\History\Services\BuildHistoryService;
use App\Factory\Products\Services\ProductGenerator;
use App\Factory\QA\Services\SmartQa2Service;
use App\Factory\Release\Services\FactoryReleaseManager;
use App\Factory\Workflow\Services\WorkflowDesigner;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class FactoryProductionOrchestrator
{
    public function __construct(
        protected FactoryReleaseManager $releaseManager,
        protected DecisionEngine $decisionEngine,
        protected WorkflowDesigner $workflowDesigner,
        protected ProductGenerator $productGenerator,
        protected DocumentationGenerator $documentationGenerator,
        protected SmartQa2Service $qa,
        protected BuildHistoryService $history,
    ) {
    }

    public function produce(string $productKey): array
    {
        $catalog = config('factory_production.products', []);

        if (! isset($catalog[$productKey])) {
            throw new RuntimeException("Produto não encontrado: {$productKey}");
        }

        $product = $catalog[$productKey];
        $base = storage_path('app/factory/production/' . $productKey);
        $stepsDir = $base . '/steps';

        File::ensureDirectoryExists($stepsDir);

        $steps = [];
        $status = 'finished';

        try {
            $steps['release'] = $this->step($stepsDir, '01_release', $this->releaseManager->status());
            $steps['decision'] = $this->step($stepsDir, '02_decision', $this->decisionEngine->decide($product['prompt']));
            $steps['workflow'] = $this->step($stepsDir, '03_workflow', $this->workflowDesigner->design($product['domain']));
            $steps['product'] = $this->step($stepsDir, '04_product_manifest', $this->productGenerator->generate($productKey));
            $steps['documentation'] = $this->step($stepsDir, '05_documentation', $this->documentationGenerator->generate($productKey));

            $qaReport = $this->qa->inspect();
            $steps['qa'] = $this->step($stepsDir, '06_smart_qa2', $qaReport);

            if (($qaReport['status'] ?? 'failed') !== 'passed') {
                $status = 'failed';
            }

            $historyPath = $this->history->record('production_' . $productKey, [
                'product' => $productKey,
                'status' => $status,
            ]);

            $steps['history'] = $this->step($stepsDir, '07_history', ['path' => $historyPath]);
        } catch (Throwable $exception) {
            $status = 'failed';
            $steps['error'] = $this->step($stepsDir, '99_error', [
                'message' => $exception->getMessage(),
                'class' => $exception::class,
            ]);
        }

        $report = [
            'product_key' => $productKey,
            'product_name' => $product['name'],
            'status' => $status,
            'mode' => 'safe_technical_production',
            'steps' => $steps,
            'next_stage' => 'Conectar este motor ao Builder/Installer para gerar módulos físicos automaticamente.',
            'produced_at' => now()->toISOString(),
        ];

        $path = $base . '/production_report.json';
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $report['path'] = $path;

        return $report;
    }

    protected function step(string $dir, string $name, array $payload): string
    {
        $path = $dir . '/' . $name . '.json';
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $path;
    }
}
