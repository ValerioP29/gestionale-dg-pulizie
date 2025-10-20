<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', \App\Models\User::class) ?? false;
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Forza la visibilità del pulsante "Nuovo"
        if (self::canCreate()) {
            $actions[] = Actions\CreateAction::make();
        }

        return $actions;
    }
}
