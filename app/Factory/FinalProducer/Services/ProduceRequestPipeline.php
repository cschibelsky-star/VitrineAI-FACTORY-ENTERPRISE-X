<?php

declare(strict_types=1);

namespace App\Factory\FinalProducer\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ProduceRequestPipeline
{
    public function __construct(
        protected ProductRequestResolver $resolver,
    ) {
    }

    public function run(string $request): array
    {
        $resolved = $this->resolver->resolve($request);
        $product = $resolved['resolved_product'];

        $base = storage_path('app/factory/final-producer/requests/' . date('Ymd_His') . '_' . $product);
        File::ensureDirectoryExists($base);

        $resolverPath = $base . '/01_resolver.json';
        File::put($resolverPath, json_encode($resolved, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $exitCode = Artisan::call('factory:produce', ['product' => $product]);

        $production = [
            'command' => 'factory:produce ' . $product,
            'exit_code' => $exitCode,
            'status' => $exitCode === 0 ? 'passed' : 'failed',
            'output' => Artisan::output(),
        ];

        $productionPath = $base . '/02_production.json';
        File::put($productionPath, json_encode($production, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $report = [
            'request' => $request,
            'resolved_product' => $product,
            'status' => $production['status'] === 'passed' ? 'finished' : 'failed',
            'resolver_path' => $resolverPath,
            'production_step_path' => $productionPath,
            'production_report_path' => storage_path('app/factory/production-enterprise/' . $product . '/production_report.json'),
            'next_command' => 'php artisan factory:install-system ' . $product . ' --dry-run',
            'created_at' => now()->toISOString(),
        ];

        $reportPath = $base . '/final_request_report.json';
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $report['path'] = $reportPath;

        return $report;
    }
}
