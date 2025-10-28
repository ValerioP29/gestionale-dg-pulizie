<?php

namespace App\Filament\Resources\DgJustificationTypeResource\Pages;

use App\Filament\Resources\DgJustificationTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDgJustificationType extends EditRecord
{
    protected static string $resource = DgJustificationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
