<?php

namespace App\Http\Controllers\Reports;

use App\Exports\MonthlyHoursExport;
use Maatwebsite\Excel\Facades\Excel;

class HoursReportExcelController
{
    public function __invoke()
    {
        $year  = request('year');
        $month = request('month');

        if (!$year || !$month) {
            abort(400, 'Parametro mancante: year o month');
        }

        $file = "foglio_ore_{$month}_{$year}.xlsx";

        return Excel::download(new MonthlyHoursExport($year, $month), $file);
    }
}
