<?php

namespace App\Filament\Pages;

use App\Models\DgReportCache;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\CarbonImmutable;

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

    public function mount(): void
    {
        $periods = $this->getAvailablePeriods();
        $this->period = $this->period ?? array_key_first($periods);

        if ($this->period === null) {
            $now = now()->startOfMonth();
            $this->period = $now->format('Y-m');
            $periods[$this->period] = $now->translatedFormat('F Y');
        }

        $this->form->fill([
            'period' => $this->period,
        ]);

        $this->refreshMetrics();
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

        return DgReportCache::query()
            ->with(['user', 'resolvedSite'])
            ->whereDate('period_start', $start->toDateString())
            ->whereDate('period_end', $end->toDateString())
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
            ->selectRaw('DATE_FORMAT(period_start, "%Y-%m-01") as period')
            ->distinct()
            ->orderByDesc('period')
            ->pluck('period')
            ->mapWithKeys(function ($period) {
                $date = CarbonImmutable::parse($period);

                return [$date->format('Y-m') => $date->translatedFormat('F Y')];
            })
            ->all();

        if (empty($periods)) {
            $now = now()->startOfMonth();
            $periods[$now->format('Y-m')] = $now->translatedFormat('F Y');
        }

        return $periods;
    }

    protected function refreshMetrics(): void
    {
        [$start, $end] = $this->currentPeriodRange();

        $query = DgReportCache::query()
            ->whereDate('period_start', $start->toDateString())
            ->whereDate('period_end', $end->toDateString());

        $this->summary = [
            'worked_hours' => round((clone $query)->sum('worked_hours'), 2),
            'absences' => (clone $query)->sum('days_absent'),
            'overtime_hours' => round(((clone $query)->sum('overtime_minutes')) / 60, 2),
        ];
    }

    protected function currentPeriodRange(): array
    {
        $period = $this->period ?? now()->format('Y-m');
        [$year, $month] = explode('-', $period);

        $start = CarbonImmutable::createFromDate((int) $year, (int) $month, 1)->startOfMonth();
        $end = $start->endOfMonth();

        return [$start, $end];
    }
}
