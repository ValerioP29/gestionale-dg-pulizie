<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\DgAnomaly;
use Carbon\Carbon;

class AnomaliesStatsWidget extends BaseWidget
{
    protected function getCards(): array
    {
        $today = Carbon::today();

        return [
            Card::make('Assenze (oggi)', DgAnomaly::where('type', 'absence')->whereDate('date', $today)->count())
                ->color('danger'),

            Card::make('Ritardi (oggi)', DgAnomaly::where('type', 'late_entry')->whereDate('date', $today)->count())
                ->color('warning'),

            Card::make('Straordinari (mese)',
                DgAnomaly::where('type', 'overtime')
                    ->whereMonth('date', $today->month)
                    ->sum('minutes') / 60
            )
                ->suffix('ore')
                ->color('info'),
        ];
    }
}
