<?php

declare(strict_types=1);

namespace App\Factory\ProductionEnterprise\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class EnterpriseProductionEngine
{
    public function produce(string $productKey): array
    {
        $products = config('factory_enterprise_products', []);

        if (! isset($products[$productKey])) {
            throw new RuntimeException("Produto não cadastrado: {$productKey}");
        }

        $product = $products[$productKey];
        $base = storage_path('app/factory/production-enterprise/' . $productKey);
        $stepsDir = $base . '/steps';

        File::ensureDirectoryExists($stepsDir);
        File::ensureDirectoryExists(storage_path('app/factory/blueprints'));

        $status = 'finished';
        $steps = [];

        try {
            $blueprint = [
                'name' => $product['name'],
                'slug' => $product['slug'],
                'description' => $product['description'],
                'modules' => $product['modules'],
                'generated_by' => 'FACTORY_FULL_RELEASE_1.0',
                'generated_at' => now()->toISOString(),
            ];

            $blueprintPath = storage_path('app/factory/blueprints/' . $blueprint['slug'] . '.json');
            File::put($blueprintPath, json_encode($blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $steps['01_blueprint'] = $this->writeStep($stepsDir, '01_blueprint', ['path' => $blueprintPath, 'blueprint' => $blueprint]);

            $steps['02_decision'] = $this->writeStep($stepsDir, '02_decision', $this->call('factory:decision', ['prompt' => [$product['decision_prompt']]]));

            $build = $this->call('factory:make-system', ['slug' => $blueprint['slug']]);
            $steps['03_make_system'] = $this->writeStep($stepsDir, '03_make_system', $build);

            if ($build['status'] !== 'passed') {
                $status = 'failed';
            }

            $dashboards = [];

            foreach ($blueprint['modules'] as $module) {
                $slug = $module['slug'];
                $dashboards[$slug] = [
                    'dashboard' => $this->call('factory:dashboard-module', ['slug' => $slug]),
                    'widgets' => $this->call('factory:widgets-module', ['slug' => $slug]),
                ];
            }

            $dashboards['executive'] = $this->call('factory:executive-dashboard', ['slug' => $blueprint['slug']]);
            $steps['04_dashboards_widgets'] = $this->writeStep($stepsDir, '04_dashboards_widgets', $dashboards);

            $steps['05_product_manifest'] = $this->writeStep($stepsDir, '05_product_manifest', $this->call('factory:product', ['key' => $productKey]));
            $steps['06_documentation'] = $this->writeStep($stepsDir, '06_documentation', $this->call('factory:docs', ['product_key' => $productKey]));

            $qa = [];

            foreach ($blueprint['modules'] as $module) {
                $qa[$module['slug']] = $this->call('factory:smart-qa', ['slug' => $module['slug']]);

                if ($qa[$module['slug']]['status'] !== 'passed') {
                    $status = 'failed';
                }
            }

            $qa['system'] = $this->call('factory:smart-qa2');

            if ($qa['system']['status'] !== 'passed') {
                $status = 'failed';
            }

            $steps['07_qa'] = $this->writeStep($stepsDir, '07_qa', $qa);
            $steps['08_history'] = $this->writeStep($stepsDir, '08_history', $this->call('factory:history', ['--record' => 'full_release_production_' . $productKey]));
        } catch (Throwable $exception) {
            $status = 'failed';
            $steps['99_error'] = $this->writeStep($stepsDir, '99_error', [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }

        $report = [
            'product_key' => $productKey,
            'product_name' => $product['name'],
            'status' => $status,
            'mode' => 'enterprise_safe_production',
            'release' => 'FACTORY_FULL_RELEASE_1.0',
            'modules' => array_map(fn (array $module) => $module['slug'], $product['modules']),
            'production_path' => $base,
            'builds_path' => storage_path('app/factory/builds'),
            'steps' => $steps,
            'install_note' => 'Produção em modo seguro. Instale módulos somente após validar o QA.',
            'produced_at' => now()->toISOString(),
        ];

        $reportPath = $base . '/production_report.json';
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $report['path'] = $reportPath;

        return $report;
    }

    protected function call(string $command, array $arguments = []): array
    {
        $exitCode = Artisan::call($command, $arguments);

        return [
            'command' => $command,
            'arguments' => $arguments,
            'exit_code' => $exitCode,
            'status' => $exitCode === 0 ? 'passed' : 'failed',
            'output' => Artisan::output(),
        ];
    }

    protected function writeStep(string $dir, string $name, array $payload): string
    {
        $path = $dir . '/' . $name . '.json';
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $path;
    }
}
