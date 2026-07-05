<?php

namespace App\CommercialFactory\Services;

use Illuminate\Support\Facades\File;

class CommercialFactoryStatusService
{
    public function list(): array
    {
        $base = storage_path('app/factory/commercial-intake');

        if (! File::isDirectory($base)) {
            return [];
        }

        return collect(File::directories($base))
            ->map(function (string $dir): array {
                $reportPath = $dir . '/commercial_factory_report.json';
                $intakePath = $dir . '/commercial_intake.json';
                $report = File::exists($reportPath) ? json_decode((string) File::get($reportPath), true) : [];
                $intake = File::exists($intakePath) ? json_decode((string) File::get($intakePath), true) : [];

                return [
                    'project' => $report['project_slug'] ?? basename($dir),
                    'client' => $intake['client']['name'] ?? '-',
                    'product' => $intake['product']['name'] ?? '-',
                    'plan' => $intake['plan']['label'] ?? '-',
                    'status' => $report['commercial_status'] ?? '-',
                ];
            })
            ->values()
            ->all();
    }
}
