<?php

use App\Models\DgPayslip;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Controllers\Reports\HoursReportExcelController;

Route::view('/', 'app');

Route::middleware(['auth', 'throttle:payslip-downloads'])->get('/payslip/{payslip}/download', function (DgPayslip $payslip) {
    $user = auth()->user();

    abort_if(str_contains($payslip->file_path, '..'), 400, 'Percorso non valido');
    abort_unless(Str::startsWith($payslip->file_path, 'payslips/'), 400, 'Percorso non valido');

    if (!$user->hasAnyRole(['admin', 'supervisor'])) {
        abort_unless($payslip->visible_to_employee && $payslip->user_id === $user->id, 403);
    }

    $storage = Storage::disk($payslip->storage_disk);

    abort_unless($storage->exists($payslip->file_path), 404);

    $filename = $payslip->file_name ?? basename($payslip->file_path);
    $mimeType = $payslip->mime_type ?? $storage->mimeType($payslip->file_path) ?? 'application/octet-stream';

    return $storage->download($payslip->file_path, $filename, [
        'Content-Type' => $mimeType,
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->name('payslip.download');


Route::middleware(['auth'])->group(function () {
    Route::get('/reports/foglio-ore-excel', [HoursReportExcelController::class, '__invoke'])
        ->name('reports.foglio-ore-excel');
});

