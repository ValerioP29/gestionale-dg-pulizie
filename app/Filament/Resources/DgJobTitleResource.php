<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgJobTitleResource\Pages;
use App\Models\DgJobTitle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;

class DgJobTitleResource extends Resource
{
    protected static ?string $model = DgJobTitle::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Anagrafica';
    protected static ?string $modelLabel = 'Mansione';
    protected static ?string $pluralModelLabel = 'Mansioni';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')
                ->label('Codice')
                ->required()
                ->maxLength(32)
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(150)
                ->unique(ignoreRecord: true),

            Forms\Components\Textarea::make('notes')
                ->label('Note')
                ->rows(3),

            Forms\Components\Toggle::make('active')
                ->label('Attiva')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Codice')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Nome')->sortable()->searchable(),

                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->label('Attiva'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aggiornata')
                    ->dateTime('d/m/Y'),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Solo attive')
                    ->trueLabel('Attive')
                    ->falseLabel('Disattive')
                    ->placeholder('Tutte'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDgJobTitles::route('/'),
            'create' => Pages\CreateDgJobTitle::route('/create'),
            'edit'   => Pages\EditDgJobTitle::route('/{record}/edit'),
        ];
    }
}
