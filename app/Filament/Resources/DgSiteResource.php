<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgSiteResource\Pages;
use App\Models\DgSite;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;

class DgSiteResource extends Resource
{
    protected static ?string $model = DgSite::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Anagrafica';
    protected static ?string $modelLabel = 'Cantiere';
    protected static ?string $pluralModelLabel = 'Cantieri';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dati generali')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nome cantiere')
                        ->required()
                        ->maxLength(150)
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('type')
                        ->label('Tipo cantiere')
                        ->options([
                            'pubblico' => 'Pubblico',
                            'privato' => 'Privato',
                        ])
                        ->default('privato')
                        ->required(),

                    Forms\Components\View::make('filament.forms.address-autocomplete')
                        ->label('Indirizzo'),

                    Forms\Components\Hidden::make('address')
                        ->reactive()
                        ->dehydrated(true),

                    Forms\Components\Hidden::make('latitude')
                        ->reactive()
                        ->dehydrated(true),

                    Forms\Components\Hidden::make('longitude')
                        ->reactive()
                        ->dehydrated(true),

                    Forms\Components\TextInput::make('radius_m')
                        ->label('Raggio operativo (m)')
                        ->numeric()
                        ->default(250)
                        ->minValue(50)
                        ->suffix('metri')
                        ->helperText('Raggio usato per il controllo geofence'),

                    Forms\Components\Toggle::make('active')
                        ->label('Attivo')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'privato',
                        'warning' => 'pubblico',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Indirizzo')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->address),

                Tables\Columns\BadgeColumn::make('active')
                    ->label('Stato')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Attivo' : 'Disattivo')
                    ->colors([
                        'success' => 'Attivo',
                        'gray' => 'Disattivo',
                    ]),

                Tables\Columns\TextColumn::make('radius_m')
                    ->label('Raggio (m)')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aggiornato')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo cantiere')
                    ->options([
                        'pubblico' => 'Pubblico',
                        'privato'  => 'Privato',
                    ]),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Solo attivi')
                    ->trueLabel('Attivi')
                    ->falseLabel('Disattivi')
                    ->placeholder('Tutti'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDgSites::route('/'),
            'create' => Pages\CreateDgSite::route('/create'),
            'edit'   => Pages\EditDgSite::route('/{record}/edit'),
        ];
    }
}
