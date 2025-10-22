<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgPunchResource\Pages;
use App\Models\DgPunch;
use App\Models\User;
use App\Models\DgSite;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

class DgPunchResource extends Resource
{
    protected static ?string $model = DgPunch::class;
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
                ->options(User::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\Select::make('site_id')
                ->label('Cantiere')
                ->options(DgSite::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\Select::make('type')
                ->label('Tipo')
                ->options([
                    'check_in' => 'Entrata',
                    'check_out' => 'Uscita',
                ])
                ->required(),

            Forms\Components\TextInput::make('latitude')->numeric()->label('Latitudine')->disabled(),
            Forms\Components\TextInput::make('longitude')->numeric()->label('Longitudine')->disabled(),
            Forms\Components\TextInput::make('accuracy_m')->numeric()->label('Precisione (m)')->disabled(),

            Forms\Components\TextInput::make('device_id')->label('Dispositivo')->disabled(),
            Forms\Components\TextInput::make('device_battery')->label('Batteria (%)')->disabled(),
            Forms\Components\TextInput::make('network_type')->label('Connessione')->disabled(),

            Forms\Components\DateTimePicker::make('created_at')
                ->label('Ora timbratura')
                ->required(),

            Forms\Components\DateTimePicker::make('synced_at')
                ->label('Sincronizzato il')
                ->nullable()
                ->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Dipendente')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('site.name')->label('Cantiere')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'check_in',
                        'danger'  => 'check_out',
                    ])
                    ->formatStateUsing(fn (string $state) => $state === 'check_in' ? 'Entrata' : 'Uscita'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ora timbratura')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('latitude')
                    ->label('Latitudine')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('longitude')
                    ->label('Longitudine')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('network_type')
                    ->label('Rete')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('synced_at')
                    ->label('Sincronizzato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'check_in' => 'Entrata',
                        'check_out' => 'Uscita',
                    ]),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dal'),
                        Forms\Components\DatePicker::make('to')->label('Al'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['to'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn ($record) => auth()->user()->hasAnyRole(['admin', 'supervisor'])),
                Tables\Actions\DeleteAction::make()->visible(fn ($record) => auth()->user()->isRole('admin')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->visible(fn () => auth()->user()->isRole('admin')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->visible(fn () => auth()->user()->isRole('admin')),
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
