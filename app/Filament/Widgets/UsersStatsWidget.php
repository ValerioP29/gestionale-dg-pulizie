<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class UsersStatsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = [
        'lg' => 2,
        'xl' => 2,
    ];

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getCards(): array
    {
        $baseQuery = User::query();

        $totalUsers = (clone $baseQuery)->count();
        $activeUsers = (clone $baseQuery)->where('active', true)->count();
        $employees = (clone $baseQuery)->where('role', 'employee')->count();

        $format = fn (int $value): string => number_format($value, 0, ',', '.');

        return [
            Card::make('Utenti totali', $format($totalUsers))
                ->description('Tutti gli utenti registrati')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary')
                ->extraAttributes(['class' => 'py-4']),

            Card::make('Utenti attivi', $format($activeUsers))
                ->description('Abilitati all’accesso')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->extraAttributes(['class' => 'py-4']),

            Card::make('Dipendenti', $format($employees))
                ->description('Utenti con ruolo Dipendente')
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('info')
                ->extraAttributes(['class' => 'py-4']),
        ];
    }
}
