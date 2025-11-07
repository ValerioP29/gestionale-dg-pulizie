<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgPayslipResource\Pages;
use App\Models\DgPayslip;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use ZipArchive;

class DgPayslipResource extends Resource
{
    protected static ?string $model = DgPayslip::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Documenti';
    protected static ?string $modelLabel = 'Busta paga';
    protected static ?string $pluralModelLabel = 'Buste paga';
    protected static ?int $navigationSort = 60;

    // ✅ serve per la modale "fixError"
    public array $form = [];

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Dipendente')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('period_label')
                    ->label('Periodo')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Stato')
                    ->colors([
                        'success' => 'matched',
                        'danger'  => 'error',
                        'secondary' => null,
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'matched' => 'OK',
                        'error'   => 'Errore',
                        default   => 'Manuale',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Caricata il')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('download')
                    ->label('Scarica')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (DgPayslip $record) {
                        $record->increment('downloads_count');
                        $record->downloaded_at = now();
                        $record->save();

                        return Storage::disk($record->storage_disk)
                            ->download($record->file_path, $record->file_name);
                    }),
            ])
            ->defaultSort('created_at', 'desc')

            ->headerActions([

                // ✅ Upload singolo
                Action::make('carica_singola')
                    ->label('Carica busta paga')
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading('Carica busta paga')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('Dipendente')
                            ->options(User::orderBy('last_name')->pluck('last_name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('period_month')
                            ->label('Mese')
                            ->options(array_combine(range(1, 12), range(1, 12)))
                            ->default(today()->month)
                            ->required(),

                        Forms\Components\TextInput::make('period_year')
                            ->label('Anno')
                            ->numeric()
                            ->default(today()->year)
                            ->required(),

                        Forms\Components\FileUpload::make('file')
                            ->label('Documento PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory('payslips/tmp')
                            ->required(),
                    ])
                    ->action(function ($data) {
                        $disk = config('filesystems.default');
                        $file = $data['file'];

                        $filename = $file->getClientOriginalName();
                        $dest = "payslips/{$data['user_id']}/{$data['period_year']}-{$data['period_month']}/$filename";

                        // sposta file dalla tmp
                        Storage::disk($disk)->move(
                            $file->getRealPath(),
                            $dest
                        );

                        DgPayslip::create([
                            'user_id' => $data['user_id'],
                            'file_name' => $filename,
                            'file_path' => $dest,
                            'storage_disk' => $disk,
                            'period_year' => $data['period_year'],
                            'period_month' => $data['period_month'],
                            'status' => 'matched',
                            'uploaded_by' => auth()->id(),
                            'uploaded_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Busta paga caricata con successo')
                            ->success()
                            ->send();
                    }),

                // ✅ Import ZIP
                Action::make('import_zip')
                    ->label('Importa ZIP')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalHeading('Importa ZIP di buste paga')
                    ->form([
                        Forms\Components\FileUpload::make('zip')
                            ->label('Zip con PDF')
                            ->acceptedFileTypes(['application/zip'])
                            ->directory('payslips/import/tmp')
                            ->required(),
                    ])
                    ->action(function ($data) {

                        $zipPath = $data['zip'];
                        $disk = config('filesystems.default');

                        $localZip = Storage::disk($disk)->path($zipPath);
                        $zip = new ZipArchive;

                        if ($zip->open($localZip) !== true) {
                            throw new \Exception("File ZIP non leggibile");
                        }

                        $extractTo = Storage::disk($disk)->path('payslips/import/extracted');

                        if (!file_exists($extractTo)) {
                            mkdir($extractTo, 0777, true);
                        }

                        $zip->extractTo($extractTo);
                        $zip->close();

                        $files = scandir($extractTo);

                        foreach ($files as $pdf) {
                            if (!str_ends_with(strtolower($pdf), '.pdf')) continue;

                            $filename = $pdf;
                            $fullPath = $extractTo . '/' . $pdf;
                            $content = file_get_contents($fullPath);

                            // ✅ match tramite payroll_code nel nome file
                            $matchedUser = User::where('payroll_code', 'ILIKE', "%$filename%")->first();

                            if (!$matchedUser) {
                                // errore → salva record ma non visibile
                                DgPayslip::create([
                                    'file_name' => $filename,
                                    'file_path' => "payslips/import/error/$filename",
                                    'storage_disk' => $disk,
                                    'status' => 'error',
                                ]);

                                Storage::disk($disk)->put("payslips/import/error/$filename", $content);
                                continue;
                            }

                            $year = now()->year;
                            $month = now()->month;

                            $dest = "payslips/{$matchedUser->id}/{$year}-{$month}/$filename";
                            Storage::disk($disk)->put($dest, $content);

                            DgPayslip::create([
                                'user_id' => $matchedUser->id,
                                'file_name' => $filename,
                                'file_path' => $dest,
                                'storage_disk' => $disk,
                                'period_year' => $year,
                                'period_month' => $month,
                                'status' => 'matched',
                                'uploaded_by' => auth()->id(),
                                'uploaded_at' => now(),
                            ]);

                            // ✅ LOG DEL MATCH
                            activity('Buste paga')
                                ->causedBy(auth()->user())
                                ->performedOn($p)
                                ->withProperties([
                                    'file' => $filename,
                                    'user' => $matchedUser->full_name ?? null,
                                    'year' => $year,
                                    'month' => $month,
                                ])
                                ->log('Import ZIP: busta paga associata');
                        
                        }

                        Notification::make()
                            ->title('Importazione completata')
                            ->success()
                            ->send();
                    }),

                // ✅ Modale "Gestisci errori"
                Action::make('gestisci_errori')
                    ->label(fn () => 'Gestisci errori (' . DgPayslip::where('status', 'error')->count() . ')')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->visible(fn () => DgPayslip::where('status', 'error')->exists())
                    ->modalHeading('Buste paga non riconosciute')
                    ->modalContent(function () {
                        $errors = DgPayslip::where('status', 'error')->get();
                        return view('filament.payslips.manage-errors', compact('errors'));
                    }),
            ])

            ->actions([]) // tabella pulita
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    // ✅ Metodo chiamato dalla modale Blade
    public function fixError(int $id)
    {
        $data = $this->form;

        $record = DgPayslip::findOrFail($id);

        $disk = $record->storage_disk;
        $filename = $record->file_name;

        $oldPath = $record->file_path;
        $newPath = "payslips/{$data['user_id']}/{$data['period_year']}-{$data['period_month']}/$filename";

        Storage::disk($disk)->move($oldPath, $newPath);

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

        $this->dispatch('close-modal');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDgPayslips::route('/'),
        ];
    }
}
