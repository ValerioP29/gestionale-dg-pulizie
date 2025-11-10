<?php

namespace App\Filament\Widgets;

use App\Models\DgReportCache;
use App\Models\DgSite;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class AdvancedWorkedHoursChart extends ChartWidget implements HasForms
{
    use InteractsWithForms;

    protected static ?string $heading = 'Ore lavorate — analisi';
    protected int|string|array $columnSpan = ['lg' => 2, 'xl' => 2];
    protected static string $view = 'filament.widgets.chart-with-filters';

    public ?int $userId = null;
    public ?int $siteId = null;
    public ?string $from = null;
    public ?string $to = null;
    public string $groupBy = 'month';

    protected function getFormSchema(): array
    {
        return [
            Select::make('userId')
                ->label('Dipendente')
                ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                ->placeholder('Tutti')
                ->searchable()
                ->reactive(),
            Select::make('siteId')
                ->label('Cantiere')
                ->options(fn () => DgSite::query()->orderBy('name')->pluck('name', 'id'))
                ->placeholder('Tutti')
                ->searchable()
                ->reactive(),
            DatePicker::make('from')
                ->label('Da')
                ->default(fn () => CarbonImmutable::now()->subMonths(5)->startOfMonth())
                ->maxDate(fn () => $this->to ? CarbonImmutable::parse($this->to) : null)
                ->reactive(),
            DatePicker::make('to')
                ->label('A')
                ->default(fn () => CarbonImmutable::now()->endOfMonth())
                ->minDate(fn () => $this->from ? CarbonImmutable::parse($this->from) : null)
                ->reactive(),
            Select::make('groupBy')
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
        $start = $this->from
            ? CarbonImmutable::parse($this->from)->startOfDay()
            : CarbonImmutable::now()->subMonths(5)->startOfMonth();

        $end = $this->to
            ? CarbonImmutable::parse($this->to)->endOfDay()
            : CarbonImmutable::now()->endOfMonth();

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $bucketExpression = match ($this->groupBy) {
            'day' => "DATE_TRUNC('day', period_start)",
            'week' => "DATE_TRUNC('week', period_start)",
            default => "DATE_TRUNC('month', period_start)",
        };

        $query = DgReportCache::query()
            ->selectRaw("{$bucketExpression} AS bucket, SUM(worked_hours) AS total_hours")
            ->whereBetween('period_start', [$start->toDateString(), $end->toDateString()])
            ->whereBetween('period_end', [$start->toDateString(), $end->toDateString()])
            ->when($this->userId, fn ($builder) => $builder->where('user_id', (int) $this->userId))
            ->when($this->siteId, fn ($builder) => $builder->where(function ($inner) {
                $siteId = (int) $this->siteId;

                $inner
                    ->where('resolved_site_id', $siteId)
                    ->orWhere('site_id', $siteId);
            }))
            ->groupByRaw('bucket')
            ->orderBy('bucket');

        $rows = $query->get();

        if ($rows->isEmpty()) {
            return [
                'datasets' => [[
                    'label' => 'Ore totali',
                    'data' => [],
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59,130,246,0.15)',
                    'borderWidth' => 2,
                    'tension' => 0.35,
                    'fill' => true,
                ]],
                'labels' => [],
            ];
        }

        $labels = $this->formatLabels($rows->pluck('bucket'));
        $values = $rows->pluck('total_hours')->map(fn ($value) => round((float) $value, 2));

        return [
            'datasets' => [[
                'label' => 'Ore totali',
                'data' => $values,
                'borderColor' => '#3b82f6',
                'backgroundColor' => 'rgba(59,130,246,0.15)',
                'borderWidth' => 2,
                'tension' => 0.35,
                'fill' => true,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
            ]],
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
                    'labels' => [
                        'color' => '#0f172a',
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'intersect' => false,
            ],
            'scales' => [
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Periodo',
                    ],
                ],
                'y' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Ore',
                    ],
                    'beginAtZero' => true,
                ],
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

    #[On('updateChart')]
    public function updateChart(): void
    {
        $this->dispatch('updateChartData', data: $this->getData());
    }

    protected function formatLabels(Collection $buckets): Collection
    {
        return $buckets->map(function ($value) {
            if ($value instanceof CarbonImmutable) {
                $bucket = $value;
            } else {
                $bucket = CarbonImmutable::parse($value);
            }

            return match ($this->groupBy) {
                'day' => $bucket->translatedFormat('d M Y'),
                'week' => sprintf('Settimana %s', $bucket->isoWeek()),
                default => $bucket->translatedFormat('M Y'),
            };
        });
    }
}
