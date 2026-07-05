<?php

declare(strict_types=1);

namespace App\Factory\RealBuilder\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class FinishProjectService
{
    public function __construct(
        protected RealCodeGenerator $builder,
    ) {
    }

    public function finish(string $request): array
    {
        $exitCode = Artisan::call('factory:finalize-request', [
            'request' => [$request],
        ]);

        $finalizeOutput = Artisan::output();

        $architectPath = $this->latestFinalizationReport();

        if (! $architectPath) {
            throw new \RuntimeException('Relatório de finalização não encontrado.');
        }

        $report = json_decode((string) File::get($architectPath), true);
        $blueprintSlug = $report['blueprint_slug'];

        $realBuild = $this->builder->generate($blueprintSlug);

        return [
            'request' => $request,
            'finalize_exit_code' => $exitCode,
            'finalize_status' => $exitCode === 0 ? 'passed' : 'failed',
            'finalize_output' => $finalizeOutput,
            'finalization_report' => $architectPath,
            'blueprint' => $blueprintSlug,
            'real_build_report' => $realBuild['path'],
            'next_command' => 'php artisan factory:real-install ' . $blueprintSlug . ' --dry-run',
            'created_at' => now()->toISOString(),
        ];
    }

    protected function latestFinalizationReport(): ?string
    {
        $dir = storage_path('app/factory/finalization/productions');

        if (! File::isDirectory($dir)) {
            return null;
        }

        $files = collect(File::allFiles($dir))
            ->filter(fn ($file) => $file->getFilename() === 'finalization_report.json')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        return $files->first()?->getPathname();
    }
}
