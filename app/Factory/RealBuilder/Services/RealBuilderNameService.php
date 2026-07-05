<?php

declare(strict_types=1);

namespace App\Factory\RealBuilder\Services;

use Illuminate\Support\Str;

class RealBuilderNameService
{
    protected array $singularMap = [
        'clientes' => 'Cliente',
        'animais' => 'Animal',
        'agendamentos' => 'Agendamento',
        'prontuarios' => 'Prontuario',
        'vacinas' => 'Vacina',
        'financeiro' => 'Financeiro',
        'diagnosticos' => 'Diagnostico',
        'documentos' => 'Documento',
        'planos' => 'Plano',
        'relatorios' => 'Relatorio',
        'categorias' => 'Categoria',
        'bens' => 'Bem',
        'locais' => 'Local',
        'movimentacoes' => 'Movimentacao',
        'registros' => 'Registro',
    ];

    public function modelName(string $slug): string
    {
        return $this->singularMap[$slug] ?? Str::studly(Str::singular($slug));
    }

    public function resourceName(string $slug): string
    {
        return $this->modelName($slug) . 'Resource';
    }

    public function pageListName(string $slug): string
    {
        return 'List' . Str::studly($slug);
    }

    public function migrationName(string $slug): string
    {
        return 'create_' . $slug . '_table';
    }

    public function relationName(string $field): string
    {
        return Str::camel(str_replace('_id', '', $field));
    }

    public function relatedTable(string $field): string
    {
        $base = str_replace('_id', '', $field);
        return Str::plural($base);
    }

    public function relatedModelFromField(string $field): string
    {
        return $this->modelName(Str::plural(str_replace('_id', '', $field)));
    }
}
