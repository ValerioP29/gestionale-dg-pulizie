<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MonthlySitesSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(protected array $dataset)
    {
    }

    public function collection(): Collection
    {
        $rows = $this->dataset['sites'] ?? collect();
        $rows = $rows instanceof Collection ? $rows : collect($rows);

        return $rows->map(fn (array $row) => [
            $row['site'] ?? '',
            $row['client'] ?? '',
            number_format((float) ($row['hours'] ?? 0), 2, '.', ''),
            number_format((float) ($row['overtime'] ?? 0), 2, '.', ''),
            $row['days'] ?? 0,
            $row['employees'] ?? 0,
            $row['anomalies'] ?? 0,
        ]);
    }

    public function headings(): array
    {
        return ['Cantiere', 'Cliente', 'Ore', 'Straordinari', 'Giorni', 'Dipendenti', 'Anomalie'];
    }

    public function title(): string
    {
        return 'Riepilogo Cantieri';
    }
}
