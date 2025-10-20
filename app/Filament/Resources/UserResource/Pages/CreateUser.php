<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Forza ruoli non-employee dal gestionale, se qualcuno prova a fare il furbo
        if (($data['role'] ?? null) === 'employee') {
            $data['role'] = 'viewer';
        }
        // can_login true per gli utenti del gestionale
        $data['can_login'] = true;
        return $data;
    }
}
