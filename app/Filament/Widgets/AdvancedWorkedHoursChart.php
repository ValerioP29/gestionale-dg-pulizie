<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Forms;
use App\Models\DgReportCache;
use App\Models\User;
use App\Models\DgSite;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Forms\Contracts\HasForms;

class AdvancedWorkedHoursChart extends ChartWidget implements HasForms
{
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static ?string $heading = 'Ore Lavorate — Analisi Interattiva';
    protected int|string|array $columnSpan = 'full';

    protected bool $hasForm = true;
    protected static string $view = 'filament.widgets.chart-with-filters'; // <-- AGGIUNTO

    public ?int $userId = null;
    public ?int $siteId = null;
    public ?string $from = null;
    public ?string $to = null;
    public string $groupBy = 'month';

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('userId')
                ->label('Dipendente')
                ->options(User::orderBy('name')->pluck('name', 'id'))
                ->placeholder('Tutti')
                ->reactive()
                ->searchable(),

            Forms\Components\Select::make('siteId')
                ->label('Cantiere')
                ->options(DgSite::orderBy('name')->pluck('name', 'id'))
                ->placeholder('Tutti')
                ->reactive()
                ->searchable(),

            Forms\Components\DatePicker::make('from')
                ->label('Da')
                ->default(now()->subMonths(6)->startOfMonth())
                ->reactive(),

            Forms\Components\DatePicker::make('to')
                ->label('A')
                ->default(now()->endOfMonth())
                ->reactive(),

            Forms\Components\Select::make('groupBy')
                ->label('Raggruppa per')
                ->options([
                    'day' => 'Giorno',
                    'week' => 'Settimana',
                    'month' => 'Mese',
                ])
                ->default('month')
                ->reactive(),
        ];
    }

    protected function getData(): array
    {
        $groupExpr = match ($this->groupBy) {
            'day'   => "TO_CHAR(period_start, 'YYYY-MM-DD')",
            'week'  => "TO_CHAR(DATE_TRUNC('week', period_start), 'IYYY-IW')",
            default => "TO_CHAR(period_start, 'YYYY-MM')",
        };

        $data = DgReportCache::query()
            ->selectRaw("$groupExpr AS period, SUM(worked_hours) AS total")
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->when($this->siteId, fn($q) => $q->where('site_id', $this->siteId))
            ->when($this->from, fn($q) => $q->whereDate('period_start', '>=', $this->from))
            ->when($this->to, fn($q) => $q->whereDate('period_end', '<=', $this->to))
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $labels = $data->pluck('period')->map(function ($p) {
            try {
                if (str_contains($p, '-W')) return $p;
                $format = strlen($p) === 7 ? 'Y-m' : 'Y-m-d';
                return Carbon::createFromFormat($format, $p)->translatedFormat(strlen($p) === 7 ? 'M Y' : 'd M');
            } catch (\Exception $e) {
                return $p;
            }
        });

        return [
            'datasets' => [
                [
                    'label' => 'Ore totali',
                    'data' => $data->pluck('total'),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59,130,246,0.25)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointRadius' => 5,
                    'pointHoverRadius' => 7,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'labels' => ['color' => '#e2e8f0', 'font' => ['size' => 13]],
                ],
                'tooltip' => [
                    'backgroundColor' => '#1e293b',
                    'titleColor' => '#f1f5f9',
                    'bodyColor' => '#e2e8f0',
                    'displayColors' => false,
                ],
            ],
            'scales' => [
                'x' => ['grid' => ['color' => 'rgba(255,255,255,0.05)']],
                'y' => ['beginAtZero' => true],
            ],
        ];
    }

    public function getChartData(): array
{
    return [
        'type' => $this->getType(),
        'data' => $this->getData(),
        'options' => $this->getOptions(),
    ];
}

#[\Livewire\Attributes\On('updateChart')]
public function updateChart(): void
{
    $this->dispatch('updateChartData', data: $this->getData());
}

}
