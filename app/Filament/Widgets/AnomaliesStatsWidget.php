<?php

namespace App\Filament\Widgets;

use App\Models\DgAnomaly;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class AnomaliesStatsWidget extends BaseWidget
{
    protected ?string $heading = 'Anomalie (ultimi 30 giorni)';
    protected int|string|array $columnSpan = ['lg' => 1, 'xl' => 1];

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getCards(): array
    {
        $rangeEnd = CarbonImmutable::now()->endOfDay();
        $rangeStart = $rangeEnd->subDays(29)->startOfDay();

        $baseQuery = DgAnomaly::query()
            ->whereBetween('date', [$rangeStart->toDateString(), $rangeEnd->toDateString()]);

        $total = (clone $baseQuery)->count();

        $open = (clone $baseQuery)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereIn('status', ['open', 'pending']);
            })
            ->count();

        $overtimeHours = round(((clone $baseQuery)->where('type', 'overtime')->sum('minutes')) / 60, 1);

        return [
            Card::make('Anomalie totali', number_format($total, 0, ',', '.'))
                ->description('Ultimi 30 giorni')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->extraAttributes(['class' => 'py-4']),

            Card::make('Da gestire', number_format($open, 0, ',', '.'))
                ->description('Aperte o in attesa')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning')
                ->extraAttributes(['class' => 'py-4']),

            Card::make('Straordinari (h)', number_format($overtimeHours, 1, ',', '.'))
                ->description('Ore extra registrate')
                ->descriptionIcon('heroicon-o-bolt')
                ->color('info')
                ->extraAttributes(['class' => 'py-4']),
        ];
    }
}
