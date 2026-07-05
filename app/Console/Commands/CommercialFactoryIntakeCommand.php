<?php

namespace App\Console\Commands;

use App\CommercialFactory\Services\CommercialFactoryIntakeService;
use Illuminate\Console\Command;
use Throwable;

class CommercialFactoryIntakeCommand extends Command
{
    protected $signature = 'commercial:factory-intake {product} {client} {--plan=start} {--email=} {--domain=} {--dry-run}';
    protected $description = 'Conecta pedido comercial à Factory.';

    public function handle(CommercialFactoryIntakeService $service): int
    {
        try {
            $report = $service->intake([
                'product' => (string) $this->argument('product'),
                'client' => (string) $this->argument('client'),
                'plan' => (string) $this->option('plan'),
                'email' => $this->option('email'),
                'domain' => $this->option('domain'),
            ], (bool) $this->option('dry-run'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Commercial → Factory Pipeline finalizado.');
        $this->line('Status: ' . $report['status']);
        $this->line('Projeto: ' . $report['project_slug']);
        $this->line('Status comercial: ' . $report['commercial_status']);
        $this->line('Relatório: ' . $report['path']);

        return $report['status'] === 'finished' ? self::SUCCESS : self::FAILURE;
    }
}
