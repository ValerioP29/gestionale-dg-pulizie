<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgJustificationTypeResource\Pages;
use App\Models\DgJustificationType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class DgJustificationTypeResource extends Resource
{
    protected static ?string $model = DgJustificationType::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Anagrafica';
    protected static ?string $modelLabel = 'Tipo giustificazione';
    protected static ?string $pluralModelLabel = 'Tipi giustificazione';
    protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')
                ->label('Codice')
                ->maxLength(16)
                ->unique(ignoreRecord: true)
                ->required(),

            Forms\Components\TextInput::make('label')
                ->label('Descrizione')
                ->maxLength(64)
                ->required(),

            Forms\Components\Toggle::make('requires_doc')
                ->label('Richiede documento')
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Codice')->sortable(),
                Tables\Columns\TextColumn::make('label')->label('Descrizione')->sortable()->searchable(),
                Tables\Columns\IconColumn::make('requires_doc')
                    ->label('Richiede doc')
                    ->boolean(),
            ])
            ->defaultSort('code')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDgJustificationTypes::route('/'),
            'create' => Pages\CreateDgJustificationType::route('/create'),
            'edit'   => Pages\EditDgJustificationType::route('/{record}/edit'),
        ];
    }
}
