<?php

namespace App\Http\Controllers\Reports;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HoursReportExcelController
{
    public function __invoke()
    {
        $year  = request('year');
        $month = request('month');

        $users = User::with(['workSessions' => function ($q) use ($year,$month) {
            $q->whereYear('session_date', $year)
              ->whereMonth('session_date', $month);
        }, 'mainSite.client'])
        ->where('active', true)
        ->orderBy('last_name')
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

        // Stile header
        $sheet->getStyle("A1:R1")->getFont()->setBold(true);
        $sheet->getStyle("A1:R1")->getFill()->setFillType('solid')->getStartColor()->setRGB('E1E1E1');
        $sheet->getStyle("A1:R1")->getAlignment()->setHorizontal('center');

        // Riga di partenza
        $row = 2;

        foreach ($users as $u) {
            $main   = $u->mainSite;
            $client = $main?->client;

            $daily = array_fill(1,7,0);
            $overtime = 0;

            foreach ($u->workSessions as $s) {
                $day = (int) date('N', strtotime($s->session_date)); // 1-7
                if ($day >= 1 && $day <= 7) {
                    $daily[$day] += $s->worked_minutes;
                }
                $overtime += $s->overtime_minutes;
            }

            $data = [
                $client?->payroll_client_code,
                $main?->payroll_site_code,
                $client?->name,
                $main?->name,
                $u->payroll_code,
                $u->last_name,
                $u->first_name,
                $u->hired_at,
                $u->contract_end_at,
                $u->contract_hours_monthly,
                round($daily[1]/60,2),
                round($daily[2]/60,2),
                round($daily[3]/60,2),
                round($daily[4]/60,2),
                round($daily[5]/60,2),
                round($daily[6]/60,2),
                round($daily[7]/60,2),
                round($overtime/60,2),
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

        // Tabelle bordate
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
