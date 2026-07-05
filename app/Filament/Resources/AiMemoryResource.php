<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiMemoryResource\Pages;
use App\Models\AiMemory;
use App\Models\Company;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AiMemoryResource extends Resource
{
    protected static ?string $model = AiMemory::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Memória';
    protected static ?string $navigationGroup = 'Centro de IA';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Conhecimento')->columns(2)->schema([
                Forms\Components\Select::make('company_id')->label('Cliente')->options(fn () => Company::query()->pluck('nome','id'))->searchable()->preload(),
                Forms\Components\Select::make('product_id')->label('Produto')->options(fn () => Product::query()->pluck('nome','id'))->searchable()->preload(),
                Forms\Components\Select::make('category')->label('Categoria')->options(['Comercial'=>'Comercial','Editorial'=>'Editorial','Jurídica'=>'Jurídica','Turismo'=>'Turismo','Governo'=>'Governo','Saúde'=>'Saúde','Operacional'=>'Operacional'])->searchable()->required(),
                Forms\Components\TextInput::make('version')->label('Versão')->default('1.0')->maxLength(30),
                Forms\Components\Select::make('status')->label('Status')->options(['rascunho'=>'Rascunho','homologado'=>'Homologado','arquivado'=>'Arquivado'])->default('rascunho')->required(),
                Forms\Components\TextInput::make('title')->label('Título')->required()->maxLength(255)->columnSpanFull(),
                Forms\Components\Textarea::make('content')->label('Conteúdo')->rows(10)->columnSpanFull(),
                Forms\Components\TagsInput::make('tags')->label('Tags')->columnSpanFull(),
                Forms\Components\DateTimePicker::make('approved_at')->label('Homologado em'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->label('Título')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('category')->label('Categoria')->badge()->sortable(),
            Tables\Columns\TextColumn::make('company.nome')->label('Cliente')->toggleable(),
            Tables\Columns\TextColumn::make('product.nome')->label('Produto')->toggleable(),
            Tables\Columns\TextColumn::make('version')->label('Versão')->badge(),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) { 'homologado'=>'success','rascunho'=>'warning','arquivado'=>'gray', default=>'gray' }),
            Tables\Columns\TextColumn::make('updated_at')->label('Atualizado')->dateTime('d/m/Y H:i')->sortable(),
        ])->defaultSort('updated_at','desc')->filters([
            Tables\Filters\SelectFilter::make('status')->options(['rascunho'=>'Rascunho','homologado'=>'Homologado','arquivado'=>'Arquivado']),
            Tables\Filters\SelectFilter::make('category')->options(['Comercial'=>'Comercial','Editorial'=>'Editorial','Jurídica'=>'Jurídica','Turismo'=>'Turismo','Governo'=>'Governo','Saúde'=>'Saúde','Operacional'=>'Operacional']),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
          ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListAiMemories::route('/'), 'create'=>Pages\CreateAiMemory::route('/create'), 'edit'=>Pages\EditAiMemory::route('/{record}/edit')];
    }
}
