<?php

declare(strict_types=1);

namespace App\Factory\History\Services;

use Illuminate\Support\Facades\File;

class BuildHistoryService
{
    public function record(string $event, array $data = []): string
    {
        $dir = storage_path('app/factory/history');
        File::ensureDirectoryExists($dir);

        $entry = [
            'event' => $event,
            'data' => $data,
            'recorded_at' => now()->toISOString(),
        ];

        $path = $dir . '/' . date('Ymd_His') . '_' . str($event)->slug('_') . '.json';
        File::put($path, json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    public function list(): array
    {
        $dir = storage_path('app/factory/history');
        File::ensureDirectoryExists($dir);

        return array_map(fn ($file) => $file->getFilename(), File::files($dir));
    }
}
