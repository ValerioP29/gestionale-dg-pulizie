<?php

namespace App\Filament\Resources\DgPunchResource\Pages;

use App\Filament\Resources\DgPunchResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDgPunch extends CreateRecord
{
    protected static string $resource = DgPunchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Se l’utente ha messo una data custom, riscrive created_at
        if (filled($this->data['punch_time'] ?? null)) {
            $data['created_at'] = $this->data['punch_time'];
        }

        return $data;
    }
}
