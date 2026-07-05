<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiQueueResource\Pages;
use App\Models\AiAgent;
use App\Models\AiQueue;
use App\Models\Company;
use App\Models\License;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AiQueueResource extends Resource
{
    protected static ?string $model = AiQueue::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Filas';
    protected static ?string $navigationGroup = 'Centro de IA';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Fila de Execução')->columns(2)->schema([
                Forms\Components\Select::make('ai_agent_id')->label('Agente')->options(fn () => AiAgent::query()->pluck('name','id'))->searchable()->preload(),
                Forms\Components\Select::make('company_id')->label('Cliente')->options(fn () => Company::query()->pluck('nome','id'))->searchable()->preload(),
                Forms\Components\Select::make('product_id')->label('Produto')->options(fn () => Product::query()->pluck('nome','id'))->searchable()->preload(),
                Forms\Components\Select::make('license_id')->label('Licença')->options(fn () => License::query()->get()->mapWithKeys(fn ($license) => [$license->id => (($license->chave ?? null) ?: 'Licença #' . $license->id)]))->searchable()->preload(),
                Forms\Components\TextInput::make('queue_type')->label('Tipo')->maxLength(100),
                Forms\Components\TextInput::make('reference')->label('Referência')->maxLength(150),
                Forms\Components\Select::make('status')->label('Status')->options(['pendente'=>'Pendente','processando'=>'Processando','concluido'=>'Concluído','erro'=>'Erro'])->default('pendente')->required(),
                Forms\Components\TextInput::make('priority')->label('Prioridade')->numeric()->default(5),
                Forms\Components\Textarea::make('error_message')->label('Erro')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
            Tables\Columns\TextColumn::make('agent.name')->label('Agente')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('company.nome')->label('Cliente')->searchable()->toggleable(),
            Tables\Columns\TextColumn::make('queue_type')->label('Tipo')->badge(),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) { 'pendente'=>'warning','processando'=>'info','concluido'=>'success','erro'=>'danger', default=>'gray' }),
            Tables\Columns\TextColumn::make('priority')->label('Prioridade')->sortable(),
            Tables\Columns\TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
        ])->defaultSort('created_at', 'desc')->filters([
            Tables\Filters\SelectFilter::make('status')->options(['pendente'=>'Pendente','processando'=>'Processando','concluido'=>'Concluído','erro'=>'Erro']),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
          ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListAiQueues::route('/'), 'create'=>Pages\CreateAiQueue::route('/create'), 'edit'=>Pages\EditAiQueue::route('/{record}/edit')];
    }
}
