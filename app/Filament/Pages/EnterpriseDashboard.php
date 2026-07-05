<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class EnterpriseDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'Centro Operacional';
    protected static ?string $navigationLabel = 'Dashboard Executivo';
    protected static ?string $title = 'Dashboard Executivo';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.enterprise-dashboard';

    public function getStats(): array
    {
        return [
            ['label' => 'Clientes Ativos', 'value' => '—', 'trend' => 'Base SaaS', 'type' => 'Operação'],
            ['label' => 'Produtos', 'value' => '—', 'trend' => 'Ecossistema', 'type' => 'Catálogo'],
            ['label' => 'Licenças', 'value' => '—', 'trend' => 'Receita recorrente', 'type' => 'SaaS'],
            ['label' => 'Projetos Factory', 'value' => '—', 'trend' => 'Aplicações geradas', 'type' => 'Factory'],
        ];
    }

    public function getProducts(): array
    {
        return [
            ['name' => 'Consultor AI GOV360', 'status' => 'Estratégico', 'desc' => 'Assistente para pequenas empresas venderem para o governo.'],
            ['name' => 'Guia Digital da Cidade', 'status' => 'Produto SaaS', 'desc' => 'Plataforma replicável para turismo, comércio e cidade digital.'],
            ['name' => 'TV Digital Enterprise', 'status' => 'Produto SaaS', 'desc' => 'Portal TV com notícias, vídeos, RSS, ao vivo e IA editorial.'],
            ['name' => 'SISMED', 'status' => 'Roadmap', 'desc' => 'Sistema em desenvolvimento para gestão de saúde e atendimento.'],
        ];
    }

    public function getFactoryPipeline(): array
    {
        return [
            ['step' => 'Solicitação', 'status' => 'OK'],
            ['step' => 'Arquitetura', 'status' => 'OK'],
            ['step' => 'Blueprint', 'status' => 'OK'],
            ['step' => 'Build', 'status' => 'OK'],
            ['step' => 'QA', 'status' => 'OK'],
            ['step' => 'Publicação', 'status' => 'Controlada'],
        ];
    }
}
