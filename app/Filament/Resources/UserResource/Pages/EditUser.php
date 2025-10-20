<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Impedisci accidentalmente di trasformare un gestionale in employee
        if (($data['role'] ?? null) === 'employee') {
            $data['role'] = $this->record->role; // mantieni il ruolo precedente
        }
        // can_login deve restare coerente con l’uso gestionale
        if (in_array($data['role'] ?? '', ['admin', 'supervisor', 'viewer'], true)) {
            $data['can_login'] = $data['can_login'] ?? true;
        }
        return $data;
    }
}
