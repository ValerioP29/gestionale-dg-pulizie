<?php

namespace App\Exports;

use App\Services\Reports\WorkReportBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClientCustomReportExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        protected int $clientId,
        protected CarbonImmutable $from,
        protected CarbonImmutable $to,
    ) {
    }

    public function collection(): Collection
    {
        $builder = new WorkReportBuilder();
        $data = $builder->buildClientReport($this->clientId, $this->from, $this->to);
        $rows = $data['rows'] instanceof Collection ? $data['rows'] : collect($data['rows']);

        return $rows->map(fn (array $row) => [
            $row['site'],
            number_format((float) $row['hours'], 2, '.', ''),
            number_format((float) $row['overtime'], 2, '.', ''),
            $row['days'],
            $row['anomalies'],
        ]);
    }

    public function headings(): array
    {
        return ['Cantiere', 'Ore lavorate', 'Straordinari', 'Giorni lavorati', 'Anomalie'];
    }

    public function title(): string
    {
        return 'Report Cliente';
    }
}
