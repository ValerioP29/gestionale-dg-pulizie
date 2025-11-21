<?php

namespace App\Exports;

use App\Services\Reports\WorkReportBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EmployeeCustomReportExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        protected int $userId,
        protected CarbonImmutable $from,
        protected CarbonImmutable $to,
    ) {
    }

    public function collection(): Collection
    {
        $builder = new WorkReportBuilder();
        $data = $builder->buildEmployeeReport($this->userId, $this->from, $this->to);
        $rows = $data['rows'] instanceof Collection ? $data['rows'] : collect($data['rows']);

        return $rows->map(function (array $row) {
            return [
                $row['date']->format('d/m/Y'),
                $row['site'],
                number_format((float) $row['hours'], 2, '.', ''),
                number_format((float) $row['overtime'], 2, '.', ''),
                ucfirst((string) $row['status']),
                implode(' | ', $row['anomalies'] ?? []),
            ];
        });
    }

    public function headings(): array
    {
        return ['Data', 'Cantiere', 'Ore lavorate', 'Straordinari', 'Stato', 'Anomalie'];
    }

    public function title(): string
    {
        return 'Report Dipendente';
    }
}
