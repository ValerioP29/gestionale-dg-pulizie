<?php

namespace App\Http\Controllers\Reports;

use App\Models\DgReportCache;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HoursReportExcelController
{
    public function __invoke()
    {
        $year  = request('year');
        $month = request('month');

        if (!$year || !$month) {
            abort(400, 'Parametro mancante: year o month');
        }

        // Prende SOLO la cache del mese
        $reports = DgReportCache::query()
            ->with(['user', 'resolvedSite.client'])
            ->whereYear('period_start', $year)
            ->whereMonth('period_start', $month)
            ->orderBy('user_id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Foglio ore $month-$year");

        // Header
        $headers = [
            "COD. RAGG. CLI.","COD. CLI.","CLIENTE","CANTIERE","MATRICOLA",
            "COGNOME","NOME","ASSUNZIONE","SCADENZA","ORE CONTR.",
            "LUN","MAR","MER","GIO","VEN","SAB","DOM","STRAORD."
        ];

        // Scrive header
        $col = 1;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($col, 1, $h);
            $col++;
        }

        // Riga di partenza
        $row = 2;

        foreach ($reports as $r) {
            $usr   = $r->user;
            $site  = $r->resolvedSite;
            $cli   = $site?->client;

            // Non servono loop di sessioni: i daily si leggono dalla cache
            $daily = [
                $r->anomaly_flags['lun'] ?? '',
                $r->anomaly_flags['mar'] ?? '',
                $r->anomaly_flags['mer'] ?? '',
                $r->anomaly_flags['gio'] ?? '',
                $r->anomaly_flags['ven'] ?? '',
                $r->anomaly_flags['sab'] ?? '',
                $r->anomaly_flags['dom'] ?? '',
            ];

            $data = [
                $cli?->payroll_client_code,
                $site?->payroll_site_code,
                $cli?->name,
                $site?->name,
                $usr?->payroll_code,
                $usr?->last_name,
                $usr?->first_name,
                optional($usr?->hired_at)->format('d/m/Y'),
                optional($usr?->contract_end_at)->format('d/m/Y'),
                $usr?->contract_hours_monthly,
                number_format(($daily[0] ?? 0)/60, 2, ',', ''),
                number_format(($daily[1] ?? 0)/60, 2, ',', ''),
                number_format(($daily[2] ?? 0)/60, 2, ',', ''),
                number_format(($daily[3] ?? 0)/60, 2, ',', ''),
                number_format(($daily[4] ?? 0)/60, 2, ',', ''),
                number_format(($daily[5] ?? 0)/60, 2, ',', ''),
                number_format(($daily[6] ?? 0)/60, 2, ',', ''),
                number_format(($r->overtime_minutes ?? 0)/60, 2, ',', ''),
            ];

            $col = 1;
            foreach ($data as $value) {
                $sheet->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }

            $row++;
        }

        // Auto larghezza colonne
        foreach (range('A','R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Stile header
        $sheet->getStyle("A1:R1")->getFont()->setBold(true);
        $sheet->getStyle("A1:R1")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("A1:R1")->getFill()->setFillType('solid')->getStartColor()->setRGB('E1E1E1');

        // Bordo tabella
        $range = "A1:R" . ($row-1);
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle('thin');

        // Streaming del file
        $filename = "foglio_ore_{$month}_{$year}.xls";

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xls($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            "Content-Type" => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=\"$filename\""
        ]);
    }
}
