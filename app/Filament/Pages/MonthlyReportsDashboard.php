<?php

namespace App\Filament\Pages;

use App\Jobs\GenerateReportsCache as GenerateReportsCacheJob;
use App\Models\DgAnomaly;
use App\Models\DgReportCache;
use App\Support\ReportsCacheStatus;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MonthlyReportsDashboard extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationLabel = 'Dashboard report mensili';
    protected static ?string $navigationGroup = 'Gestione Cantieri';
    protected static ?int $navigationSort = 45;
    protected static string $view = 'filament.pages.monthly-reports-dashboard';

    public ?string $period = null;

    public array $summary = [
        'worked_hours' => 0.0,
        'absences' => 0,
        'overtime_hours' => 0.0,
    ];

    public bool $hasData = false;

    public function mount(): void
    {
        $periods = $this->getAvailablePeriods();
        $this->period = $this->period ?? array_key_first($periods);

        if ($this->period === null) {
            $now = CarbonImmutable::now()->startOfMonth();
            $this->period = $now->format('Y-m');
            $periods[$this->period] = $now->translatedFormat('F Y');
        }

        $this->form->fill([
            'period' => $this->period,
        ]);

        $this->refreshMetrics();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refreshCurrentMonth')
                ->label('Rigenera report mese attuale')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action(function () {
                    [$start, $end] = $this->currentPeriodRange(CarbonImmutable::now()->format('Y-m'));

                    if (ReportsCacheStatus::isRunning()) {
                        Notification::make()
                            ->title('Rigenerazione già in corso')
                            ->body('Attendi il completamento della generazione dei report in corso.')
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

                    return null;
                }),
            Actions\Action::make('downloadCsv')
                ->label('Scarica CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    [$start, $end] = $this->currentPeriodRange();

                    $reports = $this->buildMonthlyQuery($start, $end)
                        ->with(['user', 'resolvedSite', 'site'])
                        ->orderBy('user_id')
                        ->orderBy('resolved_site_id')
                        ->get();

                    if ($reports->isEmpty()) {
                        Notification::make()
                            ->title('Nessun dato disponibile')
                            ->warning()
                            ->body('Non ci sono report da esportare per il periodo selezionato.')
                            ->send();

                        return null;
                    }

                    $userIds = $reports->pluck('user_id')->filter()->unique()->values()->all();
                    $notesByUser = $this->collectAnomalyNotes($userIds, $start, $end);

                    $fileName = sprintf(
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
                            $siteName = $report->resolvedSite?->name
                                ?? $report->site?->name
                                ?? '—';

                            $overtimeHours = round(($report->overtime_minutes ?? 0) / 60, 2);
                            $notes = $notesByUser[$report->user_id] ?? '';

                            fputcsv($handle, [
                                $report->user?->name ?? $report->user?->full_name ?? '—',
                                $siteName,
                                number_format((float) $report->worked_hours, 2, ',', '.'),
                                (int) $report->days_absent,
                                number_format($overtimeHours, 2, ',', '.'),
                                (int) $report->days_present,
                                $monthLabel,
                                $notes,
                            ]);
                        }

                        fclose($handle);
                    }, $fileName, [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]);
                }),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('period')
                ->label('Mese')
                ->options($this->getAvailablePeriods())
                ->searchable()
                ->reactive()
                ->afterStateUpdated(function (?string $state) {
                    if ($state) {
                        $this->period = $state;
                        $this->refreshMetrics();
                    }
                })
                ->required(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        [$start, $end] = $this->currentPeriodRange();

        return $this->buildMonthlyQuery($start, $end)
            ->with(['user', 'resolvedSite', 'site'])
            ->orderBy('user_id')
            ->orderBy('resolved_site_id');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('user.name')
                ->label('Dipendente')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('resolvedSite.name')
                ->label('Cantiere')
                ->toggleable()
                ->sortable(),
            Tables\Columns\TextColumn::make('worked_hours')
                ->label('Ore lavorate')
                ->sortable()
                ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.')),
            Tables\Columns\TextColumn::make('days_present')
                ->label('Giorni lavorati')
                ->sortable(),
            Tables\Columns\TextColumn::make('days_absent')
                ->label('Assenze')
                ->sortable(),
            Tables\Columns\TextColumn::make('overtime_minutes')
                ->label('Straordinari (h)')
                ->sortable()
                ->formatStateUsing(fn ($state) => number_format(((int) $state) / 60, 2, ',', '.')),
        ];
    }

    protected function getAvailablePeriods(): array
    {
        $periods = DgReportCache::query()
            ->selectRaw("DATE_TRUNC('month', period_start) AS month_start")
            ->distinct()
            ->orderByDesc('month_start')
            ->pluck('month_start')
            ->map(function ($value) {
                return $value instanceof CarbonImmutable
                    ? $value
                    : CarbonImmutable::parse($value);
            })
            ->mapWithKeys(fn (CarbonImmutable $date) => [
                $date->format('Y-m') => $date->translatedFormat('F Y'),
            ])
            ->all();

        if ($periods === []) {
            $now = CarbonImmutable::now()->startOfMonth();

            return [
                $now->format('Y-m') => $now->translatedFormat('F Y'),
            ];
        }

        return $periods;
    }

    protected function refreshMetrics(): void
    {
        [$start, $end] = $this->currentPeriodRange();

        if (!$start || !$end) {
            $this->hasData = false;
            $this->summary = [
                'worked_hours' => 0.0,
                'absences' => 0,
                'overtime_hours' => 0.0,
            ];

            return;
        }

        $query = $this->buildMonthlyQuery($start, $end);

        $this->hasData = (clone $query)->exists();

        $workedHours = (float) (clone $query)->sum('worked_hours');
        $absences = (int) (clone $query)->sum('days_absent');
        $overtimeMinutes = (int) (clone $query)->sum('overtime_minutes');

        $this->summary = [
            'worked_hours' => round($workedHours, 2),
            'absences' => $absences,
            'overtime_hours' => round($overtimeMinutes / 60, 2),
        ];
    }

    protected function currentPeriodRange(?string $period = null): array
    {
        $period = $period ?? $this->period ?? CarbonImmutable::now()->format('Y-m');

        if (!preg_match('/^\d{4}-\d{2}$/', (string) $period)) {
            $period = CarbonImmutable::now()->format('Y-m');
        }

        [$year, $month] = array_map('intval', explode('-', $period));

        try {
            $start = CarbonImmutable::create($year, $month, 1, 0, 0, 0)->startOfMonth();
        } catch (\Throwable $exception) {
            $start = CarbonImmutable::now()->startOfMonth();
        }

        $end = $start->endOfMonth();

        return [$start, $end];
    }

    protected function buildMonthlyQuery(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return DgReportCache::query()
            ->whereBetween('period_start', [$start->toDateString(), $end->toDateString()])
            ->whereBetween('period_end', [$start->toDateString(), $end->toDateString()]);
    }

    protected function collectAnomalyNotes(array $userIds, CarbonImmutable $start, CarbonImmutable $end): array
    {
        if ($userIds === []) {
            return [];
        }

        $notesFromAnomalies = DgAnomaly::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['user_id', 'note'])
            ->groupBy('user_id')
            ->map(fn (Collection $items) => $items
                ->pluck('note')
                ->filter()
                ->map(fn ($note) => $this->normalizeNote($note))
                ->filter()
                ->unique()
                ->values()
                ->all()
            );

        $notesFromResolvedSites = DgReportCache::query()
            ->whereBetween('period_start', [$start->toDateString(), $end->toDateString()])
            ->whereBetween('period_end', [$start->toDateString(), $end->toDateString()])
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'anomaly_flags'])
            ->groupBy('user_id')
            ->map(function (Collection $items) {
                return $items
                    ->flatMap(fn ($item) => Arr::wrap($item->anomaly_flags))
                    ->map(function ($flag) {
                        if (is_array($flag)) {
                            return $this->normalizeNote($flag['note'] ?? $flag['message'] ?? null);
                        }

                        return $this->normalizeNote($flag);
                    })
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            });

        $merged = [];

        foreach ($userIds as $userId) {
            $notes = collect([
                $notesFromAnomalies->get($userId, []),
                $notesFromResolvedSites->get($userId, []),
            ])
                ->flatten()
                ->filter()
                ->unique()
                ->implode(' | ');

            if ($notes !== '') {
                $merged[$userId] = $notes;
            }
        }

        return $merged;
    }

    protected function normalizeNote($note): ?string
    {
        if ($note === null) {
            return null;
        }

        $normalized = trim(preg_replace('/\s+/u', ' ', (string) $note) ?? '');

        return $normalized !== '' ? $normalized : null;
    }
}
