<?php

namespace App\Console\Commands;

use App\CommercialFactory\Services\CommercialFactoryStatusService;
use Illuminate\Console\Command;

class CommercialFactoryStatusCommand extends Command
{
    protected $signature = 'commercial:factory-status';
    protected $description = 'Lista pedidos comerciais enviados para a Factory.';

    public function handle(CommercialFactoryStatusService $service): int
    {
        $items = $service->list();

        if (! count($items)) {
            $this->warn('Nenhum intake comercial encontrado.');
            return self::SUCCESS;
        }

        $this->table(['Projeto', 'Cliente', 'Produto', 'Plano', 'Status'], array_map(fn ($i) => [
            $i['project'], $i['client'], $i['product'], $i['plan'], $i['status']
        ], $items));

        return self::SUCCESS;
    }
}
