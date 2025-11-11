<?php

namespace App\Filament\Resources\DgPayslipResource\Pages;

use App\Filament\Resources\DgPayslipResource;
use App\Models\DgPayslip;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListDgPayslips extends ListRecords
{
    protected static string $resource = DgPayslipResource::class;

    public array $form = [
        'user_id' => null,
        'period_month' => null,
        'period_year' => null,
    ];

    protected $rules = [
        'form.user_id' => ['required', 'exists:users,id'],
        'form.period_month' => ['required', 'integer', 'between:1,12'],
        'form.period_year' => ['required', 'integer', 'between:2000,2100'],
    ];

    public function mount(): void
    {
        parent::mount();

        $this->form['period_month'] = now()->month;
        $this->form['period_year'] = now()->year;
    }

    public function fixError(int $id): void
    {
        $this->validate();

        $record = DgPayslip::findOrFail($id);

        $disk = $record->storage_disk ?? config('filesystems.default');
        $filename = $record->file_name;

        $directory = sprintf(
            'payslips/%d/%s',
            $this->form['user_id'],
            sprintf('%d-%02d', $this->form['period_year'], $this->form['period_month'])
        );

        $newPath = $directory . '/' . $filename;

        Storage::disk($disk)->makeDirectory($directory);

        if (!Storage::disk($disk)->exists($record->file_path)) {
            Notification::make()
                ->title('File originale non trovato')
                ->danger()
                ->send();

            return;
        }

        Storage::disk($disk)->move($record->file_path, $newPath);

        $record->update([
            'user_id' => $this->form['user_id'],
            'period_year' => $this->form['period_year'],
            'period_month' => $this->form['period_month'],
            'file_path' => $newPath,
            'status' => 'matched',
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
        ]);

        Notification::make()
            ->title('Busta paga corretta')
            ->success()
            ->send();

        $this->dispatch('close-modal', id: 'fix-error-' . $id);

        $this->reset('form');
        $this->form['period_month'] = now()->month;
        $this->form['period_year'] = now()->year;
    }
}
