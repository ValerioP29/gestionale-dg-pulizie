<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets;

class Dashboard extends BaseDashboard
{
    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\StatsOverviewWidget::class,
            \App\Filament\Widgets\UsersStatsWidget::class,
            \App\Filament\Widgets\AnomaliesStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\AdvancedWorkedHoursChart::class,
            \App\Filament\Widgets\AnomaliesPieChart::class,
            \App\Filament\Widgets\TopLateEmployeesChart::class,
        ];
    }
}
