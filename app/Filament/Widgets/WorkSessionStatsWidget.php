<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\DgWorkSession;

class WorkSessionStatsWidget extends StatsOverviewWidget
{
    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Totali mese', now()->format('F Y'))
                ->description('Sessioni totali')
                ->value(DgWorkSession::whereMonth('session_date', now()->month)->count())
                ->color('info'),

            Stat::make('Anomalie', 'Sessioni con anomalie')
                ->value(DgWorkSession::whereNotNull('anomaly_flags')->count())
                ->color('danger'),

            Stat::make('Straordinari', 'minuti totali')
                ->value(DgWorkSession::sum('overtime_minutes'))
                ->color('success'),
        ];
    }
}
