<?php

namespace App\Filament\Resources\DgPayslipResource\Pages;

use App\Filament\Resources\DgPayslipResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDgPayslip extends CreateRecord
{
    protected static string $resource = DgPayslipResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = auth()->id();
        $data['uploaded_at'] = now();

        if (isset($data['file_path'])) {
            $disk = $data['storage_disk'] ?? 'local';
            $data['mime_type'] = Storage::disk($disk)->mimeType($data['file_path']);
            $data['file_size'] = Storage::disk($disk)->size($data['file_path']);
            $data['checksum']  = sha1(Storage::disk($disk)->get($data['file_path']));
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $payslip = $this->record;

        activity('buste_paga')
            ->causedBy(auth()->user())
            ->performedOn($payslip)
            ->withProperties([
                'file'  => $payslip->file_name,
                'month' => $payslip->period_month,
                'year'  => $payslip->period_year,
                'user'  => $payslip->user->full_name,
            ])
            ->log('Busta paga caricata');
    }

}
