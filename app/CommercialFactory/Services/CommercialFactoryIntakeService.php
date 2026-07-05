<?php

namespace App\CommercialFactory\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CommercialFactoryIntakeService
{
    public function __construct(
        protected CommercialProductResolver $resolver,
    ) {}

    public function intake(array $data, bool $dryRun = true): array
    {
        $resolved = $this->resolver->resolve((string) $data['product']);
        $product = $resolved['config'];
        $planKey = (string) ($data['plan'] ?? 'start');
        $plan = $product['plans'][$planKey] ?? reset($product['plans']);
        $clientSlug = Str::slug((string) $data['client'], '_');
        $projectSlug = $resolved['key'] . '_' . $clientSlug;

        $base = storage_path('app/factory/commercial-intake/' . date('Ymd_His') . '_' . $projectSlug);
        File::ensureDirectoryExists($base);

        $prompt = trim(($product['factory_prompt'] ?? '') . "\n\nCliente: " . ($data['client'] ?? '') . "\nPlano: " . ($plan['label'] ?? $planKey) . "\nDomínio: " . ($data['domain'] ?? ''));

        $intake = [
            'client' => ['name' => $data['client'], 'email' => $data['email'] ?? null, 'domain' => $data['domain'] ?? null],
            'product' => ['key' => $resolved['key'], 'name' => $resolved['name']],
            'plan' => ['key' => $planKey, 'label' => $plan['label'] ?? $planKey, 'price' => $plan['price'] ?? null],
            'project_slug' => $projectSlug,
            'factory_prompt' => $prompt,
            'dry_run' => $dryRun,
            'created_at' => now()->toISOString(),
        ];

        File::put($base . '/commercial_intake.json', json_encode($intake, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $exitCode = Artisan::call('factory:build-and-install', [
            'request' => [$prompt],
            '--dry-run' => true,
        ]);

        $factory = [
            'command' => 'factory:build-and-install --dry-run',
            'exit_code' => $exitCode,
            'status' => $exitCode === 0 ? 'passed' : 'failed',
            'output' => Artisan::output(),
        ];

        File::put($base . '/factory_execution.json', json_encode($factory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $license = [
            'client_name' => $data['client'],
            'product_key' => $resolved['key'],
            'product_name' => $resolved['name'],
            'plan' => $planKey,
            'price' => $plan['price'] ?? null,
            'domain' => $data['domain'] ?? null,
            'status' => $factory['status'] === 'passed' ? 'ready_for_homologation' : 'factory_failed',
            'workspace' => 'Projetos > ' . $projectSlug,
            'created_at' => now()->toISOString(),
        ];

        File::put($base . '/license_preview.json', json_encode($license, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $report = [
            'status' => $factory['status'] === 'passed' ? 'finished' : 'failed',
            'project_slug' => $projectSlug,
            'commercial_status' => $license['status'],
            'path' => $base . '/commercial_factory_report.json',
            'created_at' => now()->toISOString(),
        ];

        File::put($report['path'], json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $report;
    }
}
