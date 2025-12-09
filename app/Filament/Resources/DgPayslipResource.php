<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgPayslipResource\Pages;
use App\Models\DgPayslip;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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
                        'success'   => 'matched',
                        'danger'    => 'error',
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
                // ✅ Upload SINGOLO
                Action::make('carica_singola')
                    ->label('Carica busta paga')
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading('Carica busta paga')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('Dipendente')
                            ->options(
                                User::orderBy('last_name')
                                    ->get()
                                    ->pluck('full_name', 'id') // meglio nome completo
                            )
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('period_month')
                            ->label('Mese')
                            ->options([
                                1  => 'Gennaio',
                                2  => 'Febbraio',
                                3  => 'Marzo',
                                4  => 'Aprile',
                                5  => 'Maggio',
                                6  => 'Giugno',
                                7  => 'Luglio',
                                8  => 'Agosto',
                                9  => 'Settembre',
                                10 => 'Ottobre',
                                11 => 'Novembre',
                                12 => 'Dicembre',
                            ])
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
                            ->disk('local') // <--- Upload locale
                            ->directory('tmp/payslips') // <--- temp directory
                            ->preserveFilenames() // <--- così il nome rimane quello del file originale
                            ->required(),
                         ])
                    ->action(function ($data) {
                        // Disco finale dove salvare definitivamente (S3)
                        $disk = config('filesystems.default'); 
                        $storage = Storage::disk($disk);

                        // File temporaneo arrivato da Livewire (sul disco locale!)
                        $uploadedFile = $data['file'];

                        $filename = $uploadedFile->getClientOriginalName();

                        // Cartella definitiva S3: /payslips/{user_id}/{YYYY-MM}
                        $directory = sprintf(
                            'payslips/%d/%s',
                            $data['user_id'],
                            sprintf('%d-%02d', $data['period_year'], $data['period_month'])
                        );

                        // Percorso completo definitivo
                        $finalPath = $directory . '/' . $filename;

                        // Contenuto del file locale (Livewire temp)
                        $fileContents = Storage::disk('local')->get($uploadedFile->getRealPath());

                        // Carichiamo su S3
                        $storage->put($finalPath, $fileContents);

                        // CREAZIONE DB
                        $payslip = DgPayslip::create([
                            'user_id'       => $data['user_id'],
                            'file_name'     => $filename,
                            'file_path'     => $finalPath,
                            'storage_disk'  => $disk,
                            'period_year'   => $data['period_year'],
                            'period_month'  => $data['period_month'],
                            'status'        => 'matched',
                            'uploaded_by'   => auth()->id(),
                            'uploaded_at'   => now(),
                        ]);

                        // Log attività
                        activity('Buste paga')
                            ->causedBy(auth()->user())
                            ->performedOn($payslip)
                            ->withProperties([
                                'file'  => $filename,
                                'user'  => $payslip->user?->full_name,
                                'year'  => $data['period_year'],
                                'month' => $data['period_month'],
                            ])
                            ->log('Upload singolo busta paga');

                        Notification::make()
                            ->title('Busta paga caricata con successo')
                            ->success()
                            ->send();
                    }),

                // ✅ Import ZIP MASSIVO
                Action::make('import_zip')
                    ->label('Importa ZIP')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalHeading('Importa ZIP di buste paga')
                    ->form([
                       Forms\Components\FileUpload::make('zip')
                        ->label('Zip con PDF')
                        ->acceptedFileTypes(['application/zip'])
                        ->disk('local')                     // ⬅️ upload sempre in locale
                        ->directory('tmp/payslips/import')  // ⬅️ cartella temporanea locale
                        ->preserveFilenames()
                        ->required(),
                    ])
                   ->action(function (array $data) {
                        // Disco finale (S3 in produzione)
                        $disk     = config('filesystems.default');
                        $storage  = Storage::disk($disk);

                        // File ZIP caricato da Filament (locale)
                        $zipFile  = $data['zip'];

                        // Base temporanea locale per lavorare sugli ZIP
                        $tempBase = storage_path('app/tmp/payslips');
                        File::ensureDirectoryExists($tempBase);

                        // Ricaviamo il path locale effettivo dello ZIP
                        if ($zipFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                            $localZip = $zipFile->getRealPath();
                        } else {
                            // $zipFile è una stringa tipo "tmp/payslips/import/xxx.zip" sul disco "local"
                            $localZip = Storage::disk('local')->path($zipFile);
                        }

                        if (! is_file($localZip)) {
                            throw new \Exception('File ZIP non trovato su disco locale.');
                        }

                        $zip = new \ZipArchive;

                        if ($zip->open($localZip) !== true) {
                            throw new \Exception('File ZIP non leggibile.');
                        }

                        $extractTo = $tempBase . '/extract_' . uniqid();
                        File::ensureDirectoryExists($extractTo);

                        $zip->extractTo($extractTo);
                        $zip->close();

                        $files = scandir($extractTo) ?: [];

                        foreach ($files as $pdf) {
                            if (! str_ends_with(strtolower($pdf), '.pdf')) {
                                continue;
                            }

                            $filename = $pdf;
                            $fullPath = $extractTo . '/' . $pdf;

                            if (! is_file($fullPath)) {
                                continue;
                            }

                            $content = File::get($fullPath);

                            // 🔍 parsing da nome file (matricola, mese, anno)
                            $parsed = self::parsePayslipFilename($filename);

                            // se non riesco ad estrarre dati minimi → errore
                            if (! $parsed['matricola'] || ! $parsed['mese'] || ! $parsed['anno']) {
                                $errorPath = "payslips/import/error/$filename";

                                $storage->put($errorPath, $content);

                                DgPayslip::create([
                                    'file_name'    => $filename,
                                    'file_path'    => $errorPath,
                                    'storage_disk' => $disk,
                                    'status'       => 'error',
                                ]);

                                @unlink($fullPath);
                                continue;
                            }

                            $matchedUser = self::matchUserFromMatricola($parsed['matricola']);

                            if (! $matchedUser) {
                                // nessun dipendente con quella matricola → errore
                                $errorPath = "payslips/import/error/$filename";

                                $storage->put($errorPath, $content);

                                DgPayslip::create([
                                    'file_name'    => $filename,
                                    'file_path'    => $errorPath,
                                    'storage_disk' => $disk,
                                    'status'       => 'error',
                                ]);

                                @unlink($fullPath);
                                continue;
                            }

                            $year  = $parsed['anno'];
                            $month = $parsed['mese'];

                            // Percorso definitivo sul disco finale (S3)
                            $directory = sprintf(
                                'payslips/%d/%s',
                                $matchedUser->id,
                                sprintf('%d-%02d', $year, $month)
                            );

                            $dest = $directory . '/' . $filename;

                            // Scriviamo il PDF sul disco finale (es. S3)
                            $storage->makeDirectory($directory);
                            $storage->put($dest, $content);

                            // Metadati dal disco finale
                            $mimeType = $storage->mimeType($dest);
                            $fileSize = $storage->size($dest);
                            $checksum = sha1($storage->get($dest));

                            // Creazione record busta paga
                            $payslip = DgPayslip::create([
                                'user_id'      => $matchedUser->id,
                                'file_name'    => $filename,
                                'file_path'    => $dest,
                                'storage_disk' => $disk,
                                'period_year'  => $year,
                                'period_month' => $month,
                                'status'       => 'matched',
                                'uploaded_by'  => auth()->id(),
                                'uploaded_at'  => now(),
                                'mime_type'    => $mimeType ?? null,
                                'file_size'    => $fileSize ?? null,
                                'checksum'     => $checksum ?? null,
                            ]);

                            activity('Buste paga')
                                ->causedBy(auth()->user())
                                ->performedOn($payslip)
                                ->withProperties([
                                    'file'  => $filename,
                                    'user'  => $matchedUser->full_name ?? null,
                                    'year'  => $year,
                                    'month' => $month,
                                ])
                                ->log('Import ZIP: busta paga associata');

                            @unlink($fullPath);
                        }

                        // pulizia: cartella di estrazione
                        File::deleteDirectory($extractTo);

                        // opzionale: rimuovere lo ZIP locale
                        if (is_file($localZip)) {
                            @unlink($localZip);
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

    /**
     * Parsing del nome file per estrarre:
     * - matricola (3–6 cifre)
     * - mese (1–12)
     * - anno (20xx o dedotto)
     */
    protected static function parsePayslipFilename(string $filename): array
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);

        // 1) matricola: prima sequenza di 3–6 cifre
        preg_match('/(\d{3,6})/', $base, $mat);
        $matricola = $mat[1] ?? null;

        // 2) mese: subito dopo matricola o separato da - _
        preg_match('/\d{3,6}[^\d]?(\d{1,2})/', $base, $mm);
        $mese = isset($mm[1]) ? intval($mm[1]) : null;

        if ($mese !== null && ($mese < 1 || $mese > 12)) {
            $mese = null;
        }

        // 3) anno: 20xx
        preg_match('/(20\d{2})/', $base, $yy);
        $anno = isset($yy[1]) ? intval($yy[1]) : null;

        // 4) se anno mancante ma mese presente → inferenza
        if (! $anno && $mese) {
            $currentYear  = now()->year;
            $currentMonth = now()->month;

            if ($mese > $currentMonth) {
                // esempio: siamo a gennaio e arriva 11/12 → anno precedente
                $anno = $currentYear - 1;
            } else {
                $anno = $currentYear;
            }
        }

        return [
            'matricola' => $matricola,
            'mese'      => $mese,
            'anno'      => $anno,
        ];
    }

    /**
     * Match utente a partire dalla matricola estratta.
     */
    protected static function matchUserFromMatricola(?string $matricola): ?User
    {
        if (! $matricola) {
            return null;
        }

        $matricola = strtoupper($matricola);
        $matricolaNoZero = ltrim($matricola, '0');

        return User::whereRaw('UPPER(payroll_code) = ?', [$matricola])
            ->orWhereRaw('UPPER(payroll_code) = ?', [$matricolaNoZero])
            ->first();
    }

    /**
     * Mantengo la firma compatibile se in futuro la richiami altrove.
     * Qui la usiamo solo per retrocompatibilità, ma dentro usa il parser.
     */
    protected static function matchUserFromFilename(string $filename): ?User
    {
        $parsed = self::parsePayslipFilename($filename);
        return self::matchUserFromMatricola($parsed['matricola'] ?? null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDgPayslips::route('/'),
        ];
    }
}
