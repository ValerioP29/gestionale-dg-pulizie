<?php

namespace App\Filament\Resources\DgUserJobTitleResource\Pages;

use App\Filament\Resources\DgUserJobTitleResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\DgUserJobTitle;
use Carbon\Carbon;

class CreateDgUserJobTitle extends CreateRecord
{
    protected static string $resource = DgUserJobTitleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // chiudi mansione precedente
        DgUserJobTitle::where('user_id', $data['user_id'])
            ->whereNull('to_date')
            ->update([
                'to_date' => Carbon::parse($data['from_date'])->subDay(),
            ]);

        return $data;
    }
}
