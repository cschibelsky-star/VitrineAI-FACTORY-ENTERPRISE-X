<?php

declare(strict_types=1);

namespace App\Factory\Release\Services;

use Illuminate\Support\Facades\File;

class FactoryReleaseManager
{
    public function status(): array
    {
        return [
            'release' => config('factory_release.name'),
            'version' => config('factory_release.version'),
            'macro_pack' => config('factory_release.macro_pack'),
            'status' => config('factory_release.status'),
            'engines' => config('factory_release.engines', []),
            'checked_at' => now()->toISOString(),
        ];
    }

    public function registerRelease(): string
    {
        $dir = storage_path('app/factory/releases');
        File::ensureDirectoryExists($dir);

        $path = $dir . '/factory_v' . str_replace('.', '_', (string) config('factory_release.version')) . '.json';

        File::put($path, json_encode($this->status(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $path;
    }
}
