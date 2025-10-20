<?php

namespace App\Filament\Resources\DgSiteResource\Pages;

use App\Filament\Resources\DgSiteResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDgSite extends CreateRecord
{
    protected static string $resource = DgSiteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
