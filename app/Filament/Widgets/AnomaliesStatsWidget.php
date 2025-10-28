<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\DgAnomaly;
use Carbon\Carbon;

class AnomaliesStatsWidget extends BaseWidget
{
    protected ?string $heading = 'Anomalie (Ultimo mese)';
    protected int|string|array $columnSpan = 'full';

    protected function getCards(): array
    {
        $today = Carbon::now();

        $overtimeHours = DgAnomaly::where('type', 'overtime')
            ->whereMonth('date', $today->month)
            ->sum('minutes') / 60;

        return [
            Card::make(
                'Anomalie Totali',
                DgAnomaly::whereBetween('date', [$today->copy()->subMonth(), $today])->count()
            )
                ->description('Ultimi 30 giorni')
                ->color('danger'),

            Card::make(
                'Anomalie Aperte',
                DgAnomaly::where('status', 'open')
                    ->whereBetween('date', [$today->copy()->subMonth(), $today])
                    ->count()
            )
                ->description('Da gestire')
                ->color('warning'),

            Card::make(
                'Straordinari (mese)',
                number_format($overtimeHours, 1) . ' h'
            )
                ->description('Ore extra calcolate')
                ->color('info'),
        ];
    }
}
