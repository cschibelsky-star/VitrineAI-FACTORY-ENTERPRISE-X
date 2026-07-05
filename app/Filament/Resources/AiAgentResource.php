<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiAgentResource\Pages;
use App\Models\AiAgent;
use App\Models\AiProvider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AiAgentResource extends Resource
{
    protected static ?string $model = AiAgent::class;
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Agentes IA';
    protected static ?string $navigationGroup = 'Centro de IA';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Agente IA';
    protected static ?string $pluralModelLabel = 'Agentes IA';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados do Agente')->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(150),
                Forms\Components\TextInput::make('slug')->label('Slug')->maxLength(150)->unique(ignoreRecord: true),
                Forms\Components\Select::make('ai_provider_id')->label('Provedor')->options(fn () => AiProvider::query()->pluck('name', 'id'))->searchable()->preload(),
                Forms\Components\Select::make('type')->label('Tipo')->options(['operacional'=>'Operacional','operacional premium'=>'Operacional Premium','especialista'=>'Especialista','especialista futuro'=>'Especialista Futuro','corporativo'=>'Corporativo','estrategico'=>'Estratégico'])->searchable(),
                Forms\Components\TextInput::make('product_scope')->label('Produto / Escopo')->maxLength(120),
                Forms\Components\TextInput::make('version')->label('Versão')->default('1.0')->maxLength(30),
                Forms\Components\TextInput::make('model_name')->label('Modelo')->maxLength(120),
                Forms\Components\Select::make('status')->label('Status')->options(['online'=>'Online','offline'=>'Offline','homologacao'=>'Homologação'])->default('online')->required(),
                Forms\Components\Toggle::make('is_internal')->label('Agente interno'),
                Forms\Components\Textarea::make('description')->label('Descrição')->rows(4)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Agente')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('provider.name')->label('Provedor')->badge()->sortable(),
            Tables\Columns\TextColumn::make('type')->label('Tipo')->badge()->sortable(),
            Tables\Columns\TextColumn::make('product_scope')->label('Produto / Escopo')->searchable()->toggleable(),
            Tables\Columns\TextColumn::make('version')->label('Versão')->badge(),
            Tables\Columns\IconColumn::make('is_internal')->label('Interno')->boolean(),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) { 'online'=>'success','homologacao'=>'warning','offline'=>'danger', default=>'gray' }),
            Tables\Columns\TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options(['online'=>'Online','offline'=>'Offline','homologacao'=>'Homologação']),
            Tables\Filters\SelectFilter::make('type')->options(['operacional'=>'Operacional','operacional premium'=>'Operacional Premium','especialista'=>'Especialista','corporativo'=>'Corporativo']),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
          ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListAiAgents::route('/'), 'create'=>Pages\CreateAiAgent::route('/create'), 'edit'=>Pages\EditAiAgent::route('/{record}/edit')];
    }
}
