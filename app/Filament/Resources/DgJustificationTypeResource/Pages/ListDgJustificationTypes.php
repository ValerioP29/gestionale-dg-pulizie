<?php

namespace App\Filament\Resources\DgJustificationTypeResource\Pages;

use App\Filament\Resources\DgJustificationTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDgJustificationTypes extends ListRecords
{
    protected static string $resource = DgJustificationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
