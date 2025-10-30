<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgAnomalyResource\Pages;
use App\Models\DgAnomaly;
use App\Models\User;
use App\Models\DgSite;
use App\Models\DgWorkSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DgAnomalyResource extends Resource
{
    protected static ?string $model = DgAnomaly::class;
    protected static ?string $navigationGroup = 'Gestione Cantieri';
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-circle';
    protected static ?string $modelLabel = 'Anomalia';
    protected static ?string $pluralModelLabel = 'Anomalie';
    protected static ?int $navigationSort = 36;

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                DgAnomaly::query()
                    ->with(['user', 'session.resolvedSite'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Data')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dipendente')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('session.resolvedSite.name')
                    ->label('Cantiere')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'danger' => ['missing_punch','absence','unplanned_day'],
                        'warning' => ['late_entry','early_exit'],
                        'info' => ['overtime'],
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'missing_punch' => 'Timbratura mancante',
                        'absence'       => 'Assenza',
                        'unplanned_day' => 'Giorno non previsto',
                        'late_entry'    => 'Entrata in ritardo',
                        'early_exit'    => 'Uscita anticipata',
                        'overtime'      => 'Straordinario',
                        default         => $state,
                    }),

                Tables\Columns\TextColumn::make('minutes')
                    ->label('Minuti')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Stato')
                    ->colors([
                        'warning' => 'open',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                        'gray'    => 'justified',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'open'       => 'Aperta',
                        'justified'  => 'Giustificata',
                        'approved'   => 'Approvata',
                        'rejected'   => 'Respinta',
                        default      => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->limit(25)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
               SelectFilter::make('user_id')
                    ->label('Dipendente')
                    ->relationship('user', 'name')
                    ->searchable(),

                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'missing_punch' => 'Timbratura mancante',
                        'absence'       => 'Assenza',
                        'unplanned_day' => 'Non previsto',
                        'late_entry'    => 'Ritardo',
                        'early_exit'    => 'Uscita anticipata',
                        'overtime'      => 'Straordinario',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'open'      => 'Aperta',
                        'approved'  => 'Approvata',
                        'rejected'  => 'Respinta',
                        'justified' => 'Giustificata',
                    ])
                    ->label('Stato'),

                // filtro per mese rapido
                Filter::make('mese')
                    ->form([
                        Forms\Components\Select::make('month')
                            ->label('Mese')
                            ->options([
                                '01' => 'Gennaio','02'=>'Febbraio','03'=>'Marzo','04'=>'Aprile',
                                '05' => 'Maggio','06'=>'Giugno','07'=>'Luglio','08'=>'Agosto',
                                '09' => 'Settembre','10'=>'Ottobre','11'=>'Novembre','12'=>'Dicembre',
                            ]),
                        Forms\Components\TextInput::make('year')
                            ->default(now()->year)
                            ->numeric()
                            ->label('Anno'),
                    ])
                    ->query(fn ($query, array $data) =>
                        $query
                            ->when($data['month'] ?? null, fn ($q) => $q->whereMonth('date', $data['month']))
                            ->when($data['year'] ?? null, fn ($q) => $q->whereYear('date', $data['year']))
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approva')
                    ->label('Approva')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'open' && auth()->user()->hasAnyRole(['admin','supervisor']))
                    ->action(fn ($record) => $record->update(['status' => 'approved'])),

                Tables\Actions\Action::make('rifiuta')
                    ->label('Respingi')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'open' && auth()->user()->hasAnyRole(['admin','supervisor']))
                    ->action(fn ($record) => $record->update(['status' => 'rejected'])),

                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin','supervisor'])),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->isRole('admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approva')
                    ->label('Approva selezionate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $r) {
                            $r->update(['status' => 'approved']);
                        }
                    })
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin','supervisor'])),

                Tables\Actions\BulkAction::make('rifiuta')
                    ->label('Respingi selezionate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $r) {
                            $r->update(['status' => 'rejected']);
                        }
                    })
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin','supervisor'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDgAnomalies::route('/'),
            'edit'  => Pages\EditDgAnomaly::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
{
    return [
        \App\Filament\Resources\DgAnomalyResource\RelationManagers\JustificationsRelationManager::class,
    ];
}
}
