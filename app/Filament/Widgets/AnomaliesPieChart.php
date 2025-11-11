<?php

namespace App\Filament\Widgets;

use App\Models\DgAnomaly;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class AnomaliesPieChart extends ChartWidget
{
    protected static ?string $heading = 'Tipologia anomalie (mese corrente)';
    protected int|string|array $columnSpan = [
        'sm' => 1,
        'md' => 1,
        'lg' => 1,
        'xl' => 1,
        '2xl' => 1,
    ];

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $now = CarbonImmutable::now();
        $start = $now->startOfMonth();
        $end = $now->endOfMonth();

        $rows = DgAnomaly::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('type, COUNT(*) AS total')
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();

        if ($rows->isEmpty()) {
            return [
                'labels' => ['Nessun dato'],
                'datasets' => [[
                    'data' => [1],
                    'backgroundColor' => ['#cbd5f5'],
                ]],
            ];
        }

        $labels = $rows->pluck('type')->map(fn ($type) => $this->humanizeType($type));
        $data = $rows->pluck('total')->map(fn ($value) => (int) $value);

        $palette = $this->colorPalette();
        $colors = $labels->map(fn ($label, $index) => $palette[$index % count($palette)]);

        return [
            'labels' => $labels,
            'datasets' => [[
                'data' => $data,
                'backgroundColor' => $colors,
            ]],
        ];
    }

    protected function humanizeType(?string $type): string
    {
        return match ($type) {
            'late_entry' => 'Ingresso in ritardo',
            'early_exit' => 'Uscita anticipata',
            'absence' => 'Assenza',
            'overtime' => 'Straordinario',
            'unplanned_day' => 'Giorno non pianificato',
            default => ucfirst(str_replace('_', ' ', $type ?? 'Altro')),
        };
    }

    protected function colorPalette(): array
    {
        return [
            '#ef4444',
            '#f97316',
            '#facc15',
            '#3b82f6',
            '#6366f1',
            '#10b981',
            '#0ea5e9',
        ];
    }
}
