<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgReportCacheResource\Pages;
use App\Jobs\GenerateReportsCache as GenerateReportsCacheJob;
use App\Models\DgReportCache;
use App\Models\DgSite;
use App\Models\User;
use App\Models\DgAnomaly;
use App\Models\DgWorkSession;
use App\Support\ReportsCacheStatus;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DgReportCacheResource extends Resource
{
    protected static ?string $model = DgReportCache::class;
    protected static ?string $navigationGroup = 'Gestione Cantieri';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Report Lavorativi';
    protected static ?string $pluralModelLabel = 'Report Lavorativi';
    protected static ?string $modelLabel = 'Report Lavorativo';
    protected static ?int $navigationSort = 40;

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
                ->searchable(),

            Forms\Components\DatePicker::make('period_start')->label('Da')->required(),
            Forms\Components\DatePicker::make('period_end')->label('A')->required(),

            Forms\Components\TextInput::make('worked_hours')->numeric()->label('Ore lavorate'),
            Forms\Components\TextInput::make('days_present')->numeric()->label('Presenze'),
            Forms\Components\TextInput::make('days_absent')->numeric()->label('Assenze'),
            Forms\Components\TextInput::make('overtime_minutes')->numeric()->label('Straordinario (min)'),
            Forms\Components\Toggle::make('is_final')->label('Finale'),

            Forms\Components\Textarea::make('anomaly_flags')->label('Anomalie')->rows(3)->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->sortable()->label('Dipendente'),
                Tables\Columns\TextColumn::make('resolvedSite.name')->sortable()->label('Cantiere'),
                Tables\Columns\TextColumn::make('period_start')->date()->label('Dal')->sortable(),
                Tables\Columns\TextColumn::make('period_end')->date()->label('Al')->sortable(),

                Tables\Columns\TextColumn::make('worked_hours')->label('Ore')->sortable(),
                Tables\Columns\TextColumn::make('days_present')->label('Presenze')->sortable(),
                Tables\Columns\TextColumn::make('days_absent')->label('Assenze')->sortable(),

                Tables\Columns\IconColumn::make('is_final')
                    ->boolean()
                    ->label('Finale')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock'),

                Tables\Columns\TextColumn::make('generated_at')
                    ->dateTime('d/m H:i')
                    ->label('Generato')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Dipendente')
                    ->options(User::orderBy('name')->pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('site_id')
                    ->label('Cantiere')
                    ->options(DgSite::orderBy('name')->pluck('name', 'id')),

                Tables\Filters\Filter::make('periodo')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('dal'),
                        Forms\Components\DatePicker::make('to')->label('al'),
                    ])
                    ->query(fn (Builder $q, array $data) =>
                        $q
                            ->when($data['from'], fn ($x) => $x->whereDate('period_start', '>=', $data['from']))
                            ->when($data['to'], fn ($x) => $x->whereDate('period_end', '<=', $data['to']))
                    ),
            ])
            ->defaultSort('period_start', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('Esporta CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form(self::getMonthFormSchema())
                    ->action(function (array $data) {
                        $year = (int) $data['year'];
                        $month = (int) $data['month'];

                        $start = CarbonImmutable::createFromDate($year, $month, 1)->startOfMonth();
                        $end = $start->endOfMonth();

                        $reports = DgReportCache::query()
                            ->with(['user', 'resolvedSite', 'site'])
                            ->whereDate('period_start', $start->toDateString())
                            ->whereDate('period_end', $end->toDateString())
                            ->orderBy('user_id')
                            ->orderBy('resolved_site_id')
                            ->get();

                        if ($reports->isEmpty()) {
                            Notification::make()
                                ->title('Nessun dato da esportare')
                                ->warning()
                                ->send();

                            return null;
                        }

                        $userIds = $reports->pluck('user_id')->unique()->all();

                        $notesByUser = self::collectAnomalyNotes($userIds, $start, $end);

                        $filename = sprintf(
                            'report_%s_%d.csv',
                            Str::slug($start->translatedFormat('F'), '_'),
                            $start->year
                        );

                        return response()->streamDownload(function () use ($reports, $notesByUser, $start) {
                            $handle = fopen('php://output', 'w');

                            fputcsv($handle, [
                                'user',
                                'site',
                                'ore_lavorate',
                                'assenze',
                                'straordinari',
                                'giorni_lavorati',
                                'mese',
                                'note_anomalie',
                            ]);

                            $monthLabel = $start->translatedFormat('F Y');

                            foreach ($reports as $report) {
                                $overtimeHours = round(($report->overtime_minutes ?? 0) / 60, 2);
                                $notes = $notesByUser[$report->user_id] ?? '';

                                fputcsv($handle, [
                                    $report->user?->name ?? '—',
                                    $report->resolvedSite?->name
                                        ?? $report->site?->name
                                        ?? '—',
                                    number_format((float) $report->worked_hours, 2, ',', '.'),
                                    (int) $report->days_absent,
                                    number_format($overtimeHours, 2, ',', '.'),
                                    (int) $report->days_present,
                                    $monthLabel,
                                    $notes,
                                ]);
                            }

                            fclose($handle);
                        }, $filename, [
                            'Content-Type' => 'text/csv; charset=UTF-8',
                        ]);
                    })
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin', 'supervisor'])),
                Tables\Actions\Action::make('rigenera_mese')
                    ->label('Rigenera mese')
                    ->icon('heroicon-o-arrow-path')
                    ->form(self::getMonthFormSchema())
                    ->action(function (array $data) {
                        $year = (int) $data['year'];
                        $month = (int) $data['month'];

                        $start = CarbonImmutable::createFromDate($year, $month, 1)->startOfMonth();
                        $end = $start->endOfMonth();

                        if (ReportsCacheStatus::isRunning()) {
                            Notification::make()
                                ->title('Rigenerazione già in corso')
                                ->body('Rigenerazione già in corso, attendi completamento')
                                ->warning()
                                ->send();

                            return null;
                        }

                        ReportsCacheStatus::markPending([
                            'period_start' => $start->toDateString(),
                            'period_end' => $end->toDateString(),
                            'source' => 'manual',
                        ]);

                        GenerateReportsCacheJob::dispatch(
                            $start->toDateString(),
                            $end->toDateString()
                        );

                        Notification::make()
                            ->title('Rigenerazione avviata')
                            ->body("Report del {$start->translatedFormat('F Y')} in elaborazione")
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin', 'supervisor'])),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn () => auth()->user()->hasAnyRole(['admin','supervisor'])),
                Tables\Actions\DeleteAction::make()->visible(fn () => auth()->user()->isRole('admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('rigenera')
                    ->label('Rigenera report')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $report) {
                            \App\Jobs\GenerateReportsCache::dispatch(
                                $report->period_start,
                                $report->period_end
                            );
                        }
                    })
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin','supervisor'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDgReportCaches::route('/'),
            'edit' => Pages\EditDgReportCache::route('/{record}/edit'),
        ];
    }

    private static function getMonthFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('year')
                ->label('Anno')
                ->numeric()
                ->default(now()->year)
                ->required(),
            Forms\Components\Select::make('month')
                ->label('Mese')
                ->options(self::getMonthsList())
                ->default((string) now()->month)
                ->required(),
        ];
    }

    private static function getMonthsList(): array
    {
        return [
            '1'  => 'Gennaio',
            '2'  => 'Febbraio',
            '3'  => 'Marzo',
            '4'  => 'Aprile',
            '5'  => 'Maggio',
            '6'  => 'Giugno',
            '7'  => 'Luglio',
            '8'  => 'Agosto',
            '9'  => 'Settembre',
            '10' => 'Ottobre',
            '11' => 'Novembre',
            '12' => 'Dicembre',
        ];
    }

    private static function collectAnomalyNotes(array $userIds, CarbonImmutable $start, CarbonImmutable $end): array
    {
        if (empty($userIds)) {
            return [];
        }

        $notesFromAnomalies = DgAnomaly::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'note'])
            ->groupBy('user_id')
            ->map(fn (Collection $items) => $items
                ->pluck('note')
                ->filter()
                ->map(fn ($note) => self::normalizeNote($note))
                ->filter()
                ->unique()
                ->values()
                ->all()
            );

        $notesFromFlags = DgWorkSession::query()
            ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'anomaly_flags'])
            ->groupBy('user_id')
            ->map(function (Collection $sessions) {
                $notes = [];

                foreach ($sessions as $session) {
                    $flags = $session->anomaly_flags ?? [];

                    if (!is_array($flags)) {
                        continue;
                    }

                    foreach ($flags as $flag) {
                        if (is_array($flag)) {
                            $candidate = $flag['note']
                                ?? $flag['message']
                                ?? null;

                            $normalized = self::normalizeNote($candidate);

                            if ($normalized) {
                                $notes[] = $normalized;
                            }
                        } elseif (is_string($flag)) {
                            $normalized = self::normalizeNote($flag);

                            if ($normalized) {
                                $notes[] = $normalized;
                            }
                        }
                    }
                }

                return collect($notes)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            });

        $merged = [];

        foreach ($userIds as $userId) {
            $notes = collect([
                $notesFromAnomalies->get($userId, []),
                $notesFromFlags->get($userId, []),
            ])
                ->flatten()
                ->filter()
                ->map(fn ($note) => self::normalizeNote($note))
                ->filter()
                ->unique()
                ->implode(' | ');

            if ($notes !== '') {
                $merged[$userId] = $notes;
            }
        }

        return $merged;
    }

    private static function normalizeNote($note): ?string
    {
        if ($note === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', (string) $note);
        $normalized = trim($normalized ?? '');

        return $normalized !== '' ? $normalized : null;
    }
}
