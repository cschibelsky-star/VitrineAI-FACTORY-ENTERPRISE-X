<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiConsumptionResource\Pages;
use App\Models\AiAgent;
use App\Models\AiConsumption;
use App\Models\AiProvider;
use App\Models\Company;
use App\Models\License;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AiConsumptionResource extends Resource
{
    protected static ?string $model = AiConsumption::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Consumo';
    protected static ?string $navigationGroup = 'Centro de IA';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Registro de Consumo')->columns(2)->schema([
                Forms\Components\Select::make('company_id')->label('Cliente')->options(fn () => Company::query()->pluck('nome','id'))->searchable()->preload(),
                Forms\Components\Select::make('product_id')->label('Produto')->options(fn () => Product::query()->pluck('nome','id'))->searchable()->preload(),
                Forms\Components\Select::make('license_id')->label('Licença')->options(fn () => License::query()->get()->mapWithKeys(fn ($license) => [$license->id => (($license->chave ?? null) ?: 'Licença #' . $license->id)]))->searchable()->preload(),
                Forms\Components\Select::make('ai_agent_id')->label('Agente')->options(fn () => AiAgent::query()->pluck('name','id'))->searchable()->preload(),
                Forms\Components\Select::make('ai_provider_id')->label('Provedor')->options(fn () => AiProvider::query()->pluck('name','id'))->searchable()->preload(),
                Forms\Components\TextInput::make('resource_type')->label('Recurso')->default('execucao')->maxLength(100),
                Forms\Components\TextInput::make('quantity')->label('Quantidade')->numeric()->default(1),
                Forms\Components\TextInput::make('estimated_cost')->label('Custo estimado')->numeric()->prefix('R$')->default(0),
                Forms\Components\DatePicker::make('consumption_date')->label('Data')->default(now()),
                Forms\Components\Textarea::make('notes')->label('Observações')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('consumption_date')->label('Data')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('company.nome')->label('Cliente')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('product.nome')->label('Produto')->toggleable(),
            Tables\Columns\TextColumn::make('agent.name')->label('Agente')->searchable(),
            Tables\Columns\TextColumn::make('provider.name')->label('Provedor')->badge(),
            Tables\Columns\TextColumn::make('resource_type')->label('Recurso')->badge(),
            Tables\Columns\TextColumn::make('quantity')->label('Qtd.')->numeric(decimalPlaces: 2)->sortable(),
            Tables\Columns\TextColumn::make('estimated_cost')->label('Custo')->money('BRL')->sortable(),
        ])->defaultSort('consumption_date', 'desc')->filters([
            Tables\Filters\SelectFilter::make('resource_type')->options(['execucao'=>'Execução','texto'=>'Texto','imagem'=>'Imagem','video'=>'Vídeo','audio'=>'Áudio']),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
          ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListAiConsumptions::route('/'), 'create'=>Pages\CreateAiConsumption::route('/create'), 'edit'=>Pages\EditAiConsumption::route('/{record}/edit')];
    }
}
