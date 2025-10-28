<?php

namespace App\Filament\Resources\DgPayslipResource\Pages;

use App\Filament\Resources\DgPayslipResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDgPayslip extends CreateRecord
{
    protected static string $resource = DgPayslipResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (request()->hasFile('file')) {
            $file = request()->file('file');
            $path = $file->store('payslips', $data['storage_disk'] ?? 's3');
            $data['file_path'] = $path;
            $data['mime_type'] = $file->getMimeType();
            $data['file_size'] = $file->getSize();
            $data['checksum']  = sha1_file($file->getRealPath());
            $data['uploaded_by'] = auth()->id();
        }

        return $data;
    }
}
