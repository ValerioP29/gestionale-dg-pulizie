<?php

namespace App\Filament\Resources\DgSiteResource\Pages;

use App\Filament\Resources\DgSiteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDgSite extends EditRecord
{
    protected static string $resource = DgSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

      protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
