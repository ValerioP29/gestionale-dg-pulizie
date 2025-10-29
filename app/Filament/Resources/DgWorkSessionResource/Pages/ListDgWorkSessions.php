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
                \App\Filament\Widgets\WorkSessionStatsWidget::class,
            ];
        }

}
