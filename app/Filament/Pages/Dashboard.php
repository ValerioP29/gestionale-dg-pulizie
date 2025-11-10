<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets;

class Dashboard extends BaseDashboard
{
    public function getHeaderWidgets(): array
    {
        return [
            Widgets\StatsOverviewWidget::class,
            \App\Filament\Widgets\UsersStatsWidget::class,
            \App\Filament\Widgets\AnomaliesStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|string|array
    {
        return [
            'sm' => 1,
            'lg' => 2,
            'xl' => 3,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\AdvancedWorkedHoursChart::class,
            \App\Filament\Widgets\AnomaliesPieChart::class,
            \App\Filament\Widgets\TopLateEmployeesChart::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|string|array
    {
        return [
            'sm' => 1,
            'lg' => 2,
            'xl' => 3,
        ];
    }
}
