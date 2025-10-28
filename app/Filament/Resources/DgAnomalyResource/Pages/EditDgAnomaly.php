<?php

namespace App\Filament\Resources\DgAnomalyResource\Pages;

use App\Filament\Resources\DgAnomalyResource;
use Filament\Resources\Pages\EditRecord;

class EditDgAnomaly extends EditRecord
{
    protected static string $resource = DgAnomalyResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Se un admin cambia lo stato manualmente da form
        if (isset($data['status']) && $data['status'] === 'approved') {
            $data['approved_at'] = now();
            $data['approved_by'] = auth()->id();
        }

        return $data;
    }
}
