<?php

namespace App\Filament\Pages;

use App\Exports\MonthlyHoursExport;
use App\Jobs\GenerateReportsCache as GenerateReportsCacheJob;
use App\Models\DgReportCache;
use App\Support\ReportsCacheStatus;
use Carbon\CarbonImmutable;
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
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class MonthlyReportsDashboard extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationLabel = 'Dashboard report mensili';
    protected static ?string $navigationGroup = 'Gestione Cantieri';
    protected static ?int $navigationSort = 45;
    protected static ?string $title = 'Report Mensile';
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
            Actions\Action::make('downloadExcelSheet')
                ->label('Foglio ore Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => $this->excelUrl(), shouldOpenInNewTab: true),
            Actions\Action::make('refreshCurrentMonth')
                ->label('Rigenera report mese attuale')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->visible(function () {
                    $user = auth()->user();

                    return $user && ($user->can('rigenera_report') || $user->hasAnyRole(['admin', 'supervisor']));
                })
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
            Actions\Action::make('regenerateManualPeriod')
                ->label('Rigenera mese specifico')
                ->icon('heroicon-o-adjustments-vertical')
                ->form(self::monthSelectionForm())
                ->visible(fn () => auth()->user()?->hasAnyRole(['admin', 'supervisor']))
                ->action(function (array $data) {
                    $year = (int) $data['year'];
                    $month = (int) $data['month'];

                    $start = CarbonImmutable::createFromDate($year, $month, 1)->startOfMonth();
                    $end = $start->endOfMonth();

                    if (ReportsCacheStatus::isRunning()) {
                        Notification::make()
                            ->title('Rigenerazione già in corso')
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
                        ->success()
                        ->body("Report del {$start->translatedFormat('F Y')} in elaborazione")
                        ->send();
                }),
            Actions\Action::make('downloadCsv')
                ->label('Scarica Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    [$start] = $this->currentPeriodRange();

                    return Excel::download(
                        new MonthlyHoursExport($start->year, $start->month),
                        sprintf(
                            'report_%s_%d.xlsx',
                            Str::slug($start->translatedFormat('F'), '_'),
                            $start->year,
                        ),
                    );
                }),
        ];
    }

    private static function monthSelectionForm(): array
    {
        return [
            Forms\Components\TextInput::make('year')
                ->label('Anno')
                ->numeric()
                ->default(now()->year)
                ->required(),
            Forms\Components\Select::make('month')
                ->label('Mese')
                ->options([
                    '1' => 'Gennaio',
                    '2' => 'Febbraio',
                    '3' => 'Marzo',
                    '4' => 'Aprile',
                    '5' => 'Maggio',
                    '6' => 'Giugno',
                    '7' => 'Luglio',
                    '8' => 'Agosto',
                    '9' => 'Settembre',
                    '10' => 'Ottobre',
                    '11' => 'Novembre',
                    '12' => 'Dicembre',
                ])
                ->default((string) now()->month)
                ->required(),
        ];
    }

    private function excelUrl(): string
    {
        [$start] = $this->currentPeriodRange();

        return route('reports.foglio-ore-excel', [
            'year' => $start->year,
            'month' => $start->month,
        ]);
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
}
