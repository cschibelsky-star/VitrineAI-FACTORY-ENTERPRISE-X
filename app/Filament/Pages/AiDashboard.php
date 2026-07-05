<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class AiDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Dashboard IA';
    protected static ?string $navigationGroup = 'Centro de IA';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Centro de IA';
    protected static ?string $slug = 'centro-ia';

    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 4,
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\AiStatsWidget::class,
            \App\Filament\Widgets\AiConsumptionWidget::class,
            \App\Filament\Widgets\AiQueuesWidget::class,
            \App\Filament\Widgets\AiAlertsWidget::class,
        ];
    }
}
