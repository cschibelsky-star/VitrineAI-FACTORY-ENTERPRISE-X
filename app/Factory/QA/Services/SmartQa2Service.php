<?php

declare(strict_types=1);

namespace App\Factory\QA\Services;

use Illuminate\Support\Facades\File;

class SmartQa2Service
{
    public function inspect(): array
    {
        $checks = [];

        $this->add($checks, 'release_config', File::exists(config_path('factory_release.php')), 'Configuração de release existe.');
        $this->add($checks, 'products_config', File::exists(config_path('factory_products.php')), 'Catálogo de produtos existe.');
        $this->add($checks, 'factory_bootstrap', File::exists(base_path('factory_release_bootstrap.py')), 'Bootstrap de release existe.');
        $this->add($checks, 'factory_core', File::isDirectory(app_path('Factory')), 'Factory está instalada.');
        $this->add($checks, 'storage_releases', $this->ensure(storage_path('app/factory/releases')), 'Storage de releases disponível.');
        $this->add($checks, 'storage_products', $this->ensure(storage_path('app/factory/products')), 'Storage de produtos disponível.');
        $this->add($checks, 'storage_docs', $this->ensure(storage_path('app/factory/docs')), 'Storage de documentação disponível.');
        $this->add($checks, 'storage_history', $this->ensure(storage_path('app/factory/history')), 'Storage de histórico disponível.');

        return [
            'status' => collect($checks)->contains(fn ($check) => $check['status'] === 'failed') ? 'failed' : 'passed',
            'checks' => $checks,
            'checked_at' => now()->toISOString(),
        ];
    }

    protected function ensure(string $path): bool
    {
        File::ensureDirectoryExists($path);
        return File::isDirectory($path);
    }

    protected function add(array &$checks, string $key, bool $passed, string $message): void
    {
        $checks[] = [
            'key' => $key,
            'status' => $passed ? 'passed' : 'failed',
            'message' => $message,
        ];
    }
}
