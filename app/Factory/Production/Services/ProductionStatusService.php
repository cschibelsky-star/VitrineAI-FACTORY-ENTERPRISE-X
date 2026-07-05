<?php

declare(strict_types=1);

namespace App\Factory\Production\Services;

use Illuminate\Support\Facades\File;

class ProductionStatusService
{
    public function status(): array
    {
        File::ensureDirectoryExists(storage_path('app/factory/production'));

        return [
            'engine' => config('factory_production.name'),
            'version' => config('factory_production.version'),
            'status' => config('factory_production.status'),
            'products_available' => array_keys(config('factory_production.products', [])),
            'storage_ready' => File::isDirectory(storage_path('app/factory/production')),
            'checked_at' => now()->toISOString(),
        ];
    }
}
