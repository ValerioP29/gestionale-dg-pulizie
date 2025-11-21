<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgAnomalyResource\Pages;
use App\Models\DgAnomaly;
use App\Models\User;
use App\Models\DgSite;
use App\Models\DgWorkSession;
use App\Models\DgUserJustification;
use App\Services\Anomalies\AnomalyStatusService;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Tipo')
                    ->options([
                        'missing_punch' => 'Timbratura mancante',
                        'absence' => 'Assenza',
                        'unplanned_day' => 'Giorno non previsto',
                        'late_entry' => 'Entrata in ritardo',
                        'early_exit' => 'Uscita anticipata',
                        'overtime' => 'Straordinario',
                        'irregular_session' => 'Sessione irregolare',
                        'underwork' => 'Ore insufficienti',
                    ])
                    ->disabled(),
                Forms\Components\DatePicker::make('date')
                    ->label('Data')
                    ->disabled(),
                Forms\Components\TextInput::make('worked_minutes')
                    ->label('Minuti lavorati')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn (?DgAnomaly $record) => $record?->session?->worked_minutes ?? 0),
                Forms\Components\TextInput::make('expected_minutes')
                    ->label('Minuti previsti')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn (?DgAnomaly $record) => self::expectedMinutes($record)),
                Forms\Components\Textarea::make('note')
                    ->label('Note')
                    ->rows(3),
                Forms\Components\Textarea::make('justification')
                    ->label('Giustificazioni')
                    ->disabled()
                    ->dehydrated(false)
                    ->rows(3)
                    ->formatStateUsing(function (?DgAnomaly $record) {
                        return $record?->justifications?->map(function (DgUserJustification $justification) {
                            $category = DgUserJustification::CATEGORIES[$justification->category] ?? $justification->category;
                            $range = $justification->date?->format('d/m/Y');

                            if ($justification->date_end && $justification->date_end?->ne($justification->date)) {
                                $range .= ' - ' . $justification->date_end?->format('d/m/Y');
                            }

                            $note = trim((string) ($justification->note ?? ''));

                            return trim(implode(' ', array_filter([
                                $category,
                                $range ? "({$range})" : null,
                                $note,
                            ])));
                        })->implode("\n");
                    }),
                Forms\Components\Select::make('status')
                    ->label('Stato')
                    ->options([
                        'open' => 'Aperta',
                        'approved' => 'Approvata',
                        'rejected' => 'Respinta',
                        'justified' => 'Giustificata',
                    ]),
            ]);
    }

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
                        'warning' => ['late_entry','early_exit','irregular_session'],
                        'info' => ['overtime'],
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'missing_punch' => 'Timbratura mancante',
                        'absence'       => 'Assenza',
                        'unplanned_day' => 'Giorno non previsto',
                        'late_entry'    => 'Entrata in ritardo',
                        'early_exit'    => 'Uscita anticipata',
                        'overtime'      => 'Straordinario',
                        'irregular_session' => 'Sessione irregolare',
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
                        'irregular_session' => 'Sessione irregolare',
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
                Tables\Actions\Action::make('approva')
                    ->label('Approva')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('note_admin')
                            ->label('Motivazione approvazione (opzionale)')
                            ->maxLength(500),
                    ])
                    ->visible(fn ($record) => $record->status === 'open' && auth()->user()->hasAnyRole(['admin','supervisor']))
                    ->action(function ($record, $data) {
                        $actor = auth()->user();
                        if (! $actor) {
                            return;
                        }

                        $service = app(AnomalyStatusService::class);

                        if (!empty($data['note_admin'])) {
                            $record->note = $data['note_admin'];
                        }
                        $service->approve($record, $actor);
                    }),
                Tables\Actions\Action::make('rifiuta')
                    ->label('Respingi')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('note_admin')
                            ->label('Motivazione del rifiuto')
                            ->required(),
                    ])
                    ->visible(fn ($record) => $record->status === 'open' && auth()->user()->hasAnyRole(['admin','supervisor']))
                    ->action(function ($record, $data) {
                        $actor = auth()->user();
                        if (! $actor) {
                            return;
                        }

                        $service = app(AnomalyStatusService::class);

                        $record->note = $data['note_admin'];   // salva motivazione
                        $service->reject($record, $actor);     // gestisce status, user, timestamp, session update
                    }),
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
                        $actor = auth()->user();
                        if (! $actor) {
                            return;
                        }

                        $service = app(AnomalyStatusService::class);

                        foreach ($records as $r) {
                            $service->approve($r, $actor);   // ✅ USA IL METODO NUOVO
                        }
                    })
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin','supervisor'])),

                Tables\Actions\BulkAction::make('rifiuta')
                    ->label('Respingi selezionate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $actor = auth()->user();
                        if (! $actor) {
                            return;
                        }

                        $service = app(AnomalyStatusService::class);

                        foreach ($records as $r) {
                            $service->reject($r, $actor);   // ✅ USA IL METODO NUOVO
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

    private static function expectedMinutes(?DgAnomaly $record): ?int
    {
        if (! $record) {
            return null;
        }

        $worked = (int) ($record->session?->worked_minutes ?? 0);
        $delta = (int) ($record->minutes ?? 0);

        return match ($record->type) {
            'overtime' => max(0, $worked - $delta),
            default => $worked + $delta,
        };
    }
}
