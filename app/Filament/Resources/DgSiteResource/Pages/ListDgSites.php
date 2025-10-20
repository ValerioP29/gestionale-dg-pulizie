<?php

namespace App\Filament\Resources\DgSiteResource\Pages;

use App\Filament\Resources\DgSiteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDgSites extends ListRecords
{
    protected static string $resource = DgSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
