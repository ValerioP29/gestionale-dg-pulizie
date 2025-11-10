<?php

namespace App\Filament\Widgets;

use App\Models\DgAnomaly;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class TopLateEmployeesChart extends ChartWidget
{
    protected static ?string $heading = 'Top ritardi mese corrente';
    protected int|string|array $columnSpan = ['lg' => 2, 'xl' => 2];

    protected function getData(): array
    {
        $now = CarbonImmutable::now();
        $start = $now->startOfMonth();
        $end = $now->endOfMonth();

        $rows = DgAnomaly::query()
            ->where('type', 'late_entry')
            ->whereNotNull('user_id')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'datasets' => [[
                    'label' => 'Ritardi',
                    'data' => [],
                    'backgroundColor' => [],
                    'borderColor' => '#c2410c',
                ]],
                'labels' => [],
            ];
        }

        $userNames = User::query()
            ->whereIn('id', $rows->pluck('user_id')->all())
            ->get()
            ->mapWithKeys(fn (User $user) => [$user->id => $user->full_name]);

        $labels = $rows->map(fn ($row) => $userNames[$row->user_id] ?? '—');
        $data = $rows->pluck('total')->map(fn ($value) => (int) $value);

        return [
            'datasets' => [[
                'label' => 'Ritardi',
                'data' => $data,
                'backgroundColor' => 'rgba(249, 115, 22, 0.6)',
                'borderColor' => '#c2410c',
                'borderWidth' => 1,
                'borderRadius' => 6,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'ticks' => ['color' => '#0f172a'],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['stepSize' => 1],
                ],
            ],
        ];
    }
}
