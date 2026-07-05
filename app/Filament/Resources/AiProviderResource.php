<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiProviderResource\Pages;
use App\Models\AiProvider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AiProviderResource extends Resource
{
    protected static ?string $model = AiProvider::class;
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationLabel = 'Provedores';
    protected static ?string $navigationGroup = 'Centro de IA';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Provedor IA';
    protected static ?string $pluralModelLabel = 'Provedores IA';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados do Provedor')->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(120),
                Forms\Components\TextInput::make('slug')->label('Slug')->maxLength(120)->unique(ignoreRecord: true),
                Forms\Components\Select::make('provider_type')->label('Tipo')->options(['text'=>'Texto','agents'=>'Agentes','video'=>'Vídeo','image'=>'Imagem','audio'=>'Áudio'])->searchable(),
                Forms\Components\Select::make('status')->label('Status')->options(['ativo'=>'Ativo','inativo'=>'Inativo','homologacao'=>'Homologação'])->default('ativo')->required(),
                Forms\Components\TextInput::make('api_key')->label('API Key')->password()->revealable()->columnSpanFull(),
                Forms\Components\Textarea::make('notes')->label('Observações')->rows(4)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Provedor')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('provider_type')->label('Tipo')->badge()->sortable(),
            Tables\Columns\TextColumn::make('agents_count')->label('Agentes')->counts('agents'),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) { 'ativo'=>'success','homologacao'=>'warning','inativo'=>'danger', default=>'gray' }),
            Tables\Columns\TextColumn::make('updated_at')->label('Atualizado')->dateTime('d/m/Y H:i')->sortable(),
        ])->filters([Tables\Filters\SelectFilter::make('status')->options(['ativo'=>'Ativo','inativo'=>'Inativo','homologacao'=>'Homologação'])])
          ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
          ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListAiProviders::route('/'), 'create'=>Pages\CreateAiProvider::route('/create'), 'edit'=>Pages\EditAiProvider::route('/{record}/edit')];
    }
}
