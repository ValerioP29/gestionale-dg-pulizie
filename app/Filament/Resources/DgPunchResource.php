<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgPunchResource\Pages;
use App\Models\DgPunch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class DgPunchResource extends Resource
{
    protected static ?string $model = DgPunch::class;
    protected static ?string $policy = \App\Policies\DgPunchPolicy::class;
    protected static ?string $navigationGroup = 'Gestione Cantieri';
    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    protected static ?string $modelLabel = 'Timbratura';
    protected static ?string $pluralModelLabel = 'Timbrature';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Select::make('user_id')
                ->label('Dipendente')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Select::make('site_id')
                ->label('Cantiere')
                ->relationship('site', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Select::make('type')
                ->label('Tipo')
                ->options([
                    'check_in' => 'Entrata',
                    'check_out' => 'Uscita',
                ])
                ->required(),

            // ✅ punch_time al posto di created_at per evitare loop
            Forms\Components\DateTimePicker::make('punch_time')
                ->label('Data e ora timbratura')
                ->default(now())
                ->required()
                ->withoutSeconds()
                ->dehydrated(true),   // <<–– questa riga salva la vita

            // ✅ Campi solo visuali
            Forms\Components\TextInput::make('latitude')
                ->label('Latitudine')
                ->disabled()
                ->dehydrated(false),

            Forms\Components\TextInput::make('longitude')
                ->label('Longitudine')
                ->disabled()
                ->dehydrated(false),

            Forms\Components\TextInput::make('accuracy_m')
                ->label('Precisione (m)')
                ->disabled()
                ->dehydrated(false),

            Forms\Components\TextInput::make('device_id')
                ->label('Dispositivo')
                ->disabled()
                ->dehydrated(false),

            Forms\Components\TextInput::make('device_battery')
                ->label('Batteria (%)')
                ->disabled()
                ->dehydrated(false),

            Forms\Components\TextInput::make('network_type')
                ->label('Connessione')
                ->disabled()
                ->dehydrated(false),

        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dipendente')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('site.name')
                    ->label('Cantiere')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'check_in',
                        'danger'  => 'check_out',
                    ])
                    ->formatStateUsing(fn ($state) =>
                        $state === 'check_in' ? 'Entrata' : 'Uscita'
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ora timbratura')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('network_type')
                    ->label('Rete')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('synced_at')
                    ->label('Sincronizzato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin','supervisor'])),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->isRole('admin')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => auth()->user()->isRole('admin')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->isRole('admin')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDgPunches::route('/'),
            'create' => Pages\CreateDgPunch::route('/create'),
            'edit'   => Pages\EditDgPunch::route('/{record}/edit'),
        ];
    }
}
