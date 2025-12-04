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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use ZipArchive;

class DgPayslipResource extends Resource
{
    protected static ?string $model = DgPayslip::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Documenti';
    protected static ?string $modelLabel = 'Busta paga';
    protected static ?string $pluralModelLabel = 'Buste paga';
    protected static ?int $navigationSort = 60;

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

                        $disk = Storage::disk($record->storage_disk);

                        $url = $disk->temporaryUrl(
                            $record->file_path,
                            now()->addMinutes(5),
                        );

                        return redirect()->away($url);
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
                        $uploadedFile = $data['file'];

                        $filename = $uploadedFile instanceof TemporaryUploadedFile
                            ? $uploadedFile->getClientOriginalName()
                            : basename($uploadedFile);

                        $directory = sprintf(
                            'payslips/%d/%s',
                            $data['user_id'],
                            sprintf('%d-%02d', $data['period_year'], $data['period_month'])
                        );

                        if ($uploadedFile instanceof TemporaryUploadedFile) {
                            $storedPath = $uploadedFile->storeAs($directory, $filename, $disk);
                        } else {
                            $storedPath = $directory . '/' . $filename;
                            Storage::disk($disk)->makeDirectory($directory);
                            Storage::disk($disk)->move($uploadedFile, $storedPath);
                        }

                        $payslip = DgPayslip::create([
                            'user_id' => $data['user_id'],
                            'file_name' => $filename,
                            'file_path' => $storedPath,
                            'storage_disk' => $disk,
                            'period_year' => $data['period_year'],
                            'period_month' => $data['period_month'],
                            'status' => 'matched',
                            'uploaded_by' => auth()->id(),
                            'uploaded_at' => now(),
                        ]);

                        activity('Buste paga')
                            ->causedBy(auth()->user())
                            ->performedOn($payslip)
                            ->withProperties([
                                'file' => $filename,
                                'user' => $payslip->user?->full_name,
                                'year' => $data['period_year'],
                                'month' => $data['period_month'],
                            ])
                            ->log('Upload singolo busta paga');

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
                        $diskConfig = config("filesystems.disks.{$disk}", []);
                        $filesystem = Storage::disk($disk);
                        $isLocalDisk = ($diskConfig['driver'] ?? null) === 'local';

                        $tempBase = storage_path('app/tmp/payslips');
                        File::ensureDirectoryExists($tempBase);

                        if ($isLocalDisk) {
                            $localZip = $filesystem->path($zipPath);
                            if (!is_file($localZip)) {
                                throw new \Exception('File ZIP non trovato su disco locale');
                            }
                        } else {
                            $localZip = tempnam($tempBase, 'zip_');
                            File::put($localZip, $filesystem->get($zipPath));
                        }

                        $zip = new ZipArchive;

                        if ($zip->open($localZip) !== true) {
                            throw new \Exception("File ZIP non leggibile");
                        }

                        $extractTo = $tempBase . '/extract_' . uniqid();
                        File::ensureDirectoryExists($extractTo);

                        $zip->extractTo($extractTo);
                        $zip->close();

                        $files = scandir($extractTo) ?: [];

                        foreach ($files as $pdf) {
                            if (!str_ends_with(strtolower($pdf), '.pdf')) {
                                continue;
                            }

                            $filename = $pdf;
                            $fullPath = $extractTo . '/' . $pdf;
                            $content = File::get($fullPath);

                            $matchedUser = self::matchUserFromFilename($filename);

                            if (!$matchedUser) {
                                $errorPath = "payslips/import/error/$filename";

                                Storage::disk($disk)->put($errorPath, $content);

                                DgPayslip::create([
                                    'file_name' => $filename,
                                    'file_path' => $errorPath,
                                    'storage_disk' => $disk,
                                    'status' => 'error',
                                ]);

                                continue;
                            }

                            $year = now()->year;
                            $month = now()->month;

                            $directory = sprintf(
                                'payslips/%d/%s',
                                $matchedUser->id,
                                sprintf('%d-%02d', $year, $month)
                            );

                            $dest = $directory . '/' . $filename;

                            Storage::disk($disk)->makeDirectory($directory);
                            Storage::disk($disk)->put($dest, $content);

                            $payslip = DgPayslip::create([
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

                            activity('Buste paga')
                                ->causedBy(auth()->user())
                                ->performedOn($payslip)
                                ->withProperties([
                                    'file' => $filename,
                                    'user' => $matchedUser->full_name ?? null,
                                    'year' => $year,
                                    'month' => $month,
                                ])
                                ->log('Import ZIP: busta paga associata');

                            @unlink($fullPath);
                        }

                        if ($isLocalDisk === false) {
                            @unlink($localZip);
                        }

                        File::deleteDirectory($extractTo);

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

    protected static function matchUserFromFilename(string $filename): ?User
    {
        $name = strtoupper(pathinfo($filename, PATHINFO_FILENAME));
        $name = preg_replace('/[^A-Z0-9]/', '', $name);

        if (!$name) {
            return null;
        }

        $candidates = collect([$name]);

        if (preg_match_all('/[A-Z0-9]{4,}/', $name, $matches)) {
            $candidates = $candidates->merge($matches[0]);
        }

        $candidates = $candidates->unique()->filter();

        foreach ($candidates as $candidate) {
            $user = User::whereRaw('UPPER(payroll_code) = ?', [$candidate])->first();
            if ($user) {
                return $user;
            }
        }

        return null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDgPayslips::route('/'),
        ];
    }
}
