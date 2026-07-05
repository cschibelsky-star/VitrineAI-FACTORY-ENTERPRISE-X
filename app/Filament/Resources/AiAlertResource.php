<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiAlertResource\Pages;
use App\Models\AiAgent;
use App\Models\AiAlert;
use App\Models\AiProvider;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AiAlertResource extends Resource
{
    protected static ?string $model = AiAlert::class;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Alertas';
    protected static ?string $navigationGroup = 'Centro de IA';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Alerta')->columns(2)->schema([
                Forms\Components\Select::make('company_id')->label('Cliente')->options(fn () => Company::query()->pluck('nome','id'))->searchable()->preload(),
                Forms\Components\Select::make('ai_agent_id')->label('Agente')->options(fn () => AiAgent::query()->pluck('name','id'))->searchable()->preload(),
                Forms\Components\Select::make('ai_provider_id')->label('Provedor')->options(fn () => AiProvider::query()->pluck('name','id'))->searchable()->preload(),
                Forms\Components\TextInput::make('type')->label('Tipo')->required()->maxLength(100),
                Forms\Components\Select::make('severity')->label('Severidade')->options(['baixa'=>'Baixa','media'=>'Média','alta'=>'Alta','critica'=>'Crítica'])->default('baixa')->required(),
                Forms\Components\Select::make('status')->label('Status')->options(['aberto'=>'Aberto','resolvido'=>'Resolvido'])->default('aberto')->required(),
                Forms\Components\TextInput::make('title')->label('Título')->required()->maxLength(180)->columnSpanFull(),
                Forms\Components\Textarea::make('message')->label('Mensagem')->rows(4)->columnSpanFull(),
                Forms\Components\DateTimePicker::make('resolved_at')->label('Resolvido em'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->label('Alerta')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('type')->label('Tipo')->badge(),
            Tables\Columns\TextColumn::make('severity')->label('Severidade')->badge()->color(fn (string $state): string => match ($state) { 'critica'=>'danger','alta'=>'warning','media'=>'info','baixa'=>'gray', default=>'gray' }),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => $state === 'aberto' ? 'danger' : 'success'),
            Tables\Columns\TextColumn::make('company.nome')->label('Cliente')->toggleable(),
            Tables\Columns\TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable(),
        ])->defaultSort('created_at','desc')->filters([
            Tables\Filters\SelectFilter::make('status')->options(['aberto'=>'Aberto','resolvido'=>'Resolvido']),
            Tables\Filters\SelectFilter::make('severity')->options(['baixa'=>'Baixa','media'=>'Média','alta'=>'Alta','critica'=>'Crítica']),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
          ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListAiAlerts::route('/'), 'create'=>Pages\CreateAiAlert::route('/create'), 'edit'=>Pages\EditAiAlert::route('/{record}/edit')];
    }
}
