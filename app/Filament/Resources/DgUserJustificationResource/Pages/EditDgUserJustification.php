<?php

namespace App\Filament\Resources\DgUserJustificationResource\Pages;

use App\Filament\Resources\DgUserJustificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDgUserJustification extends EditRecord
{
    protected static string $resource = DgUserJustificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
