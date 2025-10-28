<?php

namespace App\Filament\Resources\DgPayslipResource\Pages;

use App\Filament\Resources\DgPayslipResource;
use Filament\Resources\Pages\EditRecord;

class EditDgPayslip extends EditRecord
{
    protected static string $resource = DgPayslipResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Se caricano nuovo file, sovrascrivi
        if (request()->hasFile('file')) {
            $file = request()->file('file');
            $path = $file->store('payslips', $this->record->storage_disk);
            $data['file_path'] = $path;
            $data['mime_type'] = $file->getMimeType();
            $data['file_size'] = $file->getSize();
            $data['checksum']  = sha1_file($file->getRealPath());
        }

        return $data;
    }
}
