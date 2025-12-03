<?php

namespace App\Filament\Resources\DgPayslipResource\Pages;

use App\Filament\Resources\DgPayslipResource;
use App\Models\DgPayslip;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListDgPayslips extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = DgPayslipResource::class;

    public array $fixFormData = [];

    protected ?int $currentErrorId = null;

    protected $rules = [
        'fixFormData.user_id' => ['required', 'exists:users,id'],
        'fixFormData.period_month' => ['required', 'integer', 'between:1,12'],
        'fixFormData.period_year' => ['required', 'integer', 'between:2000,2100'],
    ];

    public function mount(): void
    {
        parent::mount();

        $this->fillDefaultForm();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Dipendente')
                    ->options(\App\Models\User::orderBy('last_name')->pluck('last_name', 'id'))
                    ->searchable()
                    ->required(),

                Select::make('period_month')
                    ->label('Mese')
                    ->options([1 => 'Gen', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mag', 6 => 'Giu', 7 => 'Lug', 8 => 'Ago', 9 => 'Set', 10 => 'Ott', 11 => 'Nov', 12 => 'Dic'])
                    ->required(),

                TextInput::make('period_year')
                    ->label('Anno')
                    ->numeric()
                    ->required(),
            ])
            ->statePath('fixFormData');
    }

    public function prepareFixError(int $id): void
    {
        $this->currentErrorId = $id;
        $this->fillDefaultForm();

        $this->dispatch('open-modal', id: 'fix-error-' . $id);
    }

    public function fixError(): void
    {
        if (! $this->currentErrorId) {
            return;
        }

        $this->validate();

        $data = $this->fixFormData;

        $record = DgPayslip::findOrFail($this->currentErrorId);

        $disk = $record->storage_disk ?? config('filesystems.default');
        $filename = $record->file_name;

        $directory = sprintf(
            'payslips/%d/%s',
            $data['user_id'],
            sprintf('%d-%02d', $data['period_year'], $data['period_month'])
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
            'user_id' => $data['user_id'],
            'period_year' => $data['period_year'],
            'period_month' => $data['period_month'],
            'file_path' => $newPath,
            'status' => 'matched',
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
        ]);

        Notification::make()
            ->title('Busta paga corretta')
            ->success()
            ->send();

        $this->dispatch('close-modal', id: 'fix-error-' . $this->currentErrorId);

        $this->currentErrorId = null;

        $this->fillDefaultForm();
    }

    protected function fillDefaultForm(): void
    {
        $this->fixFormData = [
          'user_id' => null,
          'period_month' => now()->month,
          'period_year' => now()->year,
      ];
    }
}
