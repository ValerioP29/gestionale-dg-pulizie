<?php

namespace App\Filament\Resources\DgWorkSessionResource\Pages;

use App\Filament\Resources\DgWorkSessionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget;

class ListDgWorkSessions extends ListRecords
{
    protected static string $resource = DgWorkSessionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverviewWidget::make()
                ->columns(3)
                ->stats([
                    Stat::make('Totali mese', now()->format('F Y'))
                        ->description('Sessioni totali')
                        ->value(\App\Models\DgWorkSession::whereMonth('session_date', now()->month)->count())
                        ->color('info'),

                    Stat::make('Anomalie', 'Sessioni con anomalie')
                        ->value(\App\Models\DgWorkSession::whereNotNull('anomaly_flags')->count())
                        ->color('danger'),

                    Stat::make('Straordinari', 'minuti totali')
                        ->value(\App\Models\DgWorkSession::sum('overtime_minutes'))
                        ->color('success'),
                ]),
        ];
    }
}
