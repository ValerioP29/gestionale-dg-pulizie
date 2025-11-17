<?php

namespace App\Filament\Resources\DgUserJustificationResource\Pages;

use App\Filament\Resources\DgUserJustificationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListDgUserJustifications extends ListRecords
{
    protected static string $resource = DgUserJustificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
