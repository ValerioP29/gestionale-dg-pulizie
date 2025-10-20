<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class UsersStatsWidget extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Totale Utenti', User::count())
                ->description('Tutti gli utenti nel sistema')
                ->color('primary'),

            Card::make('Attivi', User::where('active', true)->count())
                ->description('Utenti attualmente attivi')
                ->color('success'),

            Card::make('Dipendenti', User::where('role', 'employee')->count())
                ->description('Utenti con ruolo Dipendente')
                ->color('info'),
        ];
    }
}
