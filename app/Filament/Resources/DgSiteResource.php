<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgSiteResource\Pages;
use App\Models\DgSite;
use App\Models\DgClient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;

class DgSiteResource extends Resource
{
    protected static ?string $model = DgSite::class;
    protected static ?string $policy = \App\Policies\DgSitePolicy::class;

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

                    Forms\Components\Select::make('client_id')
                        ->label('Cliente')
                        ->relationship('client', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Nome cliente')
                                ->required(),
                            Forms\Components\TextInput::make('payroll_client_code')
                                ->label('Codice cliente raggruppato')
                                ->maxLength(32),
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state && $client = DgClient::find($state)) {
                                $set('payroll_client_code_display', $client->payroll_client_code);
                            }
                        }),

                    Forms\Components\TextInput::make('payroll_site_code')
                        ->label('Codice cliente (cantiere)')
                        ->maxLength(32)
                        ->required(),

                    Forms\Components\TextInput::make('payroll_client_code_display')
                        ->label('Codice cliente raggruppato')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\Select::make('type')
                        ->label('Tipo cantiere')
                        ->options([
                            'pubblico' => 'Pubblico',
                            'privato'  => 'Privato',
                        ])
                        ->default('privato')
                        ->required(),

                    Forms\Components\View::make('filament.forms.components.address-autocomplete')
                        ->label('Indirizzo')
                        ->reactive(),

                    Forms\Components\Hidden::make('address')
                        ->required(),

                    Forms\Components\Hidden::make('latitude')
                        ->required()
                        ->rule('numeric'),

                    Forms\Components\Hidden::make('longitude')
                        ->required()
                        ->rule('numeric'),

                    Forms\Components\TextInput::make('radius_m')
                        ->label('Raggio operativo (m)')
                        ->numeric()
                        ->default(500)
                        ->minValue(50)
                        ->suffix('metri')
                        ->helperText('Controllo geofence su timbratura')
                        ->required(),

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
                    ->label('Nome cantiere')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.payroll_client_code')
                    ->label('Codice cliente raggruppato')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payroll_site_code')
                    ->label('Codice cantiere')
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
                        'gray'    => 'Disattivo',
                    ]),
            ])
            ->defaultSort('active', 'desc')
            ->filters([
                SelectFilter::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

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

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\DgSiteResource\RelationManagers\AssignmentsRelationManager::class,
        ];
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
