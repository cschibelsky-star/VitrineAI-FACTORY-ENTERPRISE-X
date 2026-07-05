<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiExecutionResource\Pages;
use App\Models\AiAgent;
use App\Models\AiExecution;
use App\Models\AiProvider;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AiExecutionResource extends Resource
{
    protected static ?string $model = AiExecution::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $navigationLabel = 'Logs';
    protected static ?string $navigationGroup = 'Centro de IA';
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Log de Execução')->columns(2)->schema([
                Forms\Components\Select::make('ai_agent_id')->label('Agente')->options(fn () => AiAgent::query()->pluck('name','id'))->searchable()->preload(),
                Forms\Components\Select::make('ai_provider_id')->label('Provedor')->options(fn () => AiProvider::query()->pluck('name','id'))->searchable()->preload(),
                Forms\Components\Select::make('company_id')->label('Cliente')->options(fn () => Company::query()->pluck('nome','id'))->searchable()->preload(),
                Forms\Components\TextInput::make('execution_type')->label('Tipo')->maxLength(100),
                Forms\Components\Select::make('status')->label('Status')->options(['sucesso'=>'Sucesso','erro'=>'Erro','cancelado'=>'Cancelado'])->default('sucesso')->required(),
                Forms\Components\TextInput::make('duration_seconds')->label('Duração segundos')->numeric(),
                Forms\Components\TextInput::make('estimated_cost')->label('Custo estimado')->numeric()->prefix('R$')->default(0),
                Forms\Components\Textarea::make('error_message')->label('Mensagem de erro')->rows(3)->columnSpanFull(),
                Forms\Components\DateTimePicker::make('started_at')->label('Iniciado em'),
                Forms\Components\DateTimePicker::make('finished_at')->label('Finalizado em'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->label('Data')->dateTime('d/m/Y H:i')->sortable(),
            Tables\Columns\TextColumn::make('agent.name')->label('Agente')->searchable(),
            Tables\Columns\TextColumn::make('provider.name')->label('Provedor')->badge(),
            Tables\Columns\TextColumn::make('company.nome')->label('Cliente')->toggleable(),
            Tables\Columns\TextColumn::make('execution_type')->label('Tipo')->badge(),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) { 'sucesso'=>'success','erro'=>'danger','cancelado'=>'gray', default=>'gray' }),
            Tables\Columns\TextColumn::make('duration_seconds')->label('Tempo')->suffix('s')->sortable(),
            Tables\Columns\TextColumn::make('estimated_cost')->label('Custo')->money('BRL')->sortable(),
        ])->defaultSort('created_at','desc')->filters([
            Tables\Filters\SelectFilter::make('status')->options(['sucesso'=>'Sucesso','erro'=>'Erro','cancelado'=>'Cancelado']),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
          ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListAiExecutions::route('/'), 'create'=>Pages\CreateAiExecution::route('/create'), 'edit'=>Pages\EditAiExecution::route('/{record}/edit')];
    }
}
