<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\DgAnomaly;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TopLateEmployeesChart extends ChartWidget
{
    protected static ?string $heading = 'Top 5 Ritardatari del Mese';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $month = Carbon::now()->month;

        $rows = DgAnomaly::query()
            ->selectRaw('user_id, COUNT(*) as count')
            ->where('type', 'late_entry')
            ->whereMonth('date', $month)
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $labels = $rows->map(fn ($r) => User::find($r->user_id)?->full_name ?? '—');
        $data = $rows->pluck('count');

        return [
            'datasets' => [
                [
                    'label' => 'Ritardi',
                    'data' => $data,
                    'backgroundColor' => 'rgba(234, 88, 12, 0.5)',
                    'borderColor' => '#ea580c',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
