<?php

namespace App\Http\Controllers\Reports;

use App\Exports\MonthlyHoursExport;
use App\Models\DgReportCache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class HoursReportExcelController
{
    use AuthorizesRequests;

    public function __invoke()
    {
        Gate::authorize('viewAny', DgReportCache::class);

        $year  = request('year');
        $month = request('month');

        if (!$year || !$month) {
            abort(400, 'Parametro mancante: year o month');
        }

        $file = "foglio_ore_{$month}_{$year}.xlsx";

        return Excel::download(new MonthlyHoursExport($year, $month), $file);
    }
}
