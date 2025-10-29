<?php

use App\Models\DgPayslip;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reports\HoursReportExcelController;

Route::get('/payslip/{payslip}/download', function (DgPayslip $payslip) {
    abort_unless(auth()->check(), 403);
    abort_unless($payslip->visible_to_employee || auth()->user()->hasAnyRole(['admin','supervisor']), 403);

    return Storage::disk($payslip->storage_disk)
        ->download($payslip->file_path, $payslip->file_name);
})->name('payslip.download');


Route::get('/reports/foglio-ore-excel', [HoursReportExcelController::class, '__invoke'])
    ->name('reports.foglio-ore-excel');

