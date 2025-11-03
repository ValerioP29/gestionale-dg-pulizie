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
                ->unique(
                    table: DgJobTitle::class,
                    column: 'code',
                    ignoreRecord: true
                )
                ->helperText('Un codice univoco per identificare la mansione (es: OP01, ADM02).'),

            Forms\Components\TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(150)
                ->unique(
                    table: DgJobTitle::class,
                    column: 'name',
                    ignoreRecord: true
                ),

            Forms\Components\Textarea::make('notes')
                ->label('Note')
                ->rows(3),

            Forms\Components\Toggle::make('active')
                ->label('Attiva')
                ->default(true),
        ])
        ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('code')
                    ->label('Codice')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->label('Attiva'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aggiornata')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Solo attive')
                    ->trueLabel('Attive')
                    ->falseLabel('Disattive')
                    ->placeholder('Tutte'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nuova Mansione')
                    ->modalHeading('Crea una nuova mansione')
                    ->slideOver()
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
