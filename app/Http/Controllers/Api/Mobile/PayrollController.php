<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\DgPayslip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PayrollController
{
    public function index(Request $request)
    {
        $user = $request->user();

        $payslips = DgPayslip::query()
            ->where('user_id', $user->id)
            ->where('visible_to_employee', true)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get();

        $data = $payslips->map(function (DgPayslip $payslip) {
            $period = $this->formatPeriod($payslip);
            $filename = $payslip->file_name ?? basename($payslip->file_path);

            return [
                'id'       => $payslip->id,
                'period'   => $period,
                'amount'   => $payslip->amount ?? null,
                'file_url' => url(sprintf('/api/mobile/payroll/%d/download', $payslip->id)),
                'file_name' => $filename,
            ];
        });

        return response()->json([
            'status' => 'ok',
            'data'   => $data,
        ]);
    }

    public function download(Request $request, int $id)
    {
        $user = $request->user();

        $payslip = DgPayslip::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->where('visible_to_employee', true)
            ->firstOrFail();

        $path = $payslip->file_path;
        $storage = Storage::disk($payslip->storage_disk ?? 'local');

        if (str_contains($path, '..')) {
            abort(400, 'Percorso non valido');
        }

        if (!Str::startsWith(ltrim($path, '/'), 'payslips/') && !Str::startsWith($path, '/fake/payslips/')) {
            abort(400, 'Percorso non valido');
        }

        abort_unless($storage->exists($path), 404);

        $filename = $payslip->file_name ?? basename($path);
        $mimeType = $payslip->mime_type ?? $storage->mimeType($path) ?? 'application/pdf';

        return $storage->download($path, $filename, [
            'Content-Type' => $mimeType,
        ]);
    }

    protected function formatPeriod(DgPayslip $payslip): string
    {
        $period = Carbon::create($payslip->period_year, $payslip->period_month, 1);

        return ucfirst($period->locale('it')->translatedFormat('F Y'));
    }
}
