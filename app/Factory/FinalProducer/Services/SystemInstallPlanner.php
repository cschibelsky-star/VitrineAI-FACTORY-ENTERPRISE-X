<?php

declare(strict_types=1);

namespace App\Factory\FinalProducer\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use RuntimeException;

class SystemInstallPlanner
{
    public function plan(string $product, bool $dryRun = true, bool $force = false): array
    {
        $reportPath = storage_path('app/factory/production-enterprise/' . $product . '/production_report.json');

        if (! File::exists($reportPath)) {
            throw new RuntimeException("Relatório de produção não encontrado. Rode factory:produce {$product} antes.");
        }

        $production = json_decode((string) File::get($reportPath), true);
        $modules = $production['modules'] ?? [];

        $base = storage_path('app/factory/final-producer/installations/' . date('Ymd_His') . '_' . $product);
        File::ensureDirectoryExists($base);

        $results = [];

        foreach ($modules as $module) {
            $args = ['module' => $module];

            if ($dryRun) $args['--dry-run'] = true;
            if ($force) $args['--force'] = true;

            $exitCode = Artisan::call('factory:install-module', $args);

            $results[] = [
                'module' => $module,
                'exit_code' => $exitCode,
                'status' => $exitCode === 0 ? 'passed' : 'failed',
                'output' => Artisan::output(),
            ];
        }

        $status = collect($results)->contains(fn ($item) => $item['status'] === 'failed') ? 'failed' : 'passed';

        $report = [
            'product' => $product,
            'mode' => $dryRun ? 'dry_run' : 'install',
            'force' => $force,
            'status' => $status,
            'modules' => $modules,
            'results' => $results,
            'created_at' => now()->toISOString(),
        ];

        $path = $base . '/install_system_report.json';
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $report['path'] = $path;

        return $report;
    }
}
