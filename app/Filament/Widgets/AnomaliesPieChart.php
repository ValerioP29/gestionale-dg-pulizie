<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\DgAnomaly;
use Carbon\Carbon;

class AnomaliesPieChart extends ChartWidget
{
    protected static ?string $heading = 'Distribuzione Anomalie nel Mese';
    protected int|string|array $columnSpan = '1/2';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $month = Carbon::now()->month;

        $rows = DgAnomaly::whereMonth('date', $month)
            ->selectRaw('type, COUNT(*) AS total')
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('type'),
            'datasets' => [
                [
                    'data' => $rows->pluck('total'),
                    'backgroundColor' => [
                        '#ef4444', // assenze
                        '#f97316', // ritardi
                        '#3b82f6', // straordinari
                        '#10b981', // early_exit
                        '#6366f1', // unplanned_day
                    ],
                ],
            ],
        ];
    }
}
