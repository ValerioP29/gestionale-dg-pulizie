<?php

namespace App\Filament\Resources\DgPayslipResource\Pages;

use App\Filament\Resources\DgPayslipResource;
use Filament\Resources\Pages\EditRecord;

class EditDgPayslip extends EditRecord
{
    protected static string $resource = DgPayslipResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['file_path']) && $data['file_path'] !== $this->record->file_path) {
            $disk = $data['storage_disk'] ?? 'local';
            $data['mime_type'] = Storage::disk($disk)->mimeType($data['file_path']);
            $data['file_size'] = Storage::disk($disk)->size($data['file_path']);
            $data['checksum']  = sha1(Storage::disk($disk)->get($data['file_path']));
        }

        return $data;
    }
}
