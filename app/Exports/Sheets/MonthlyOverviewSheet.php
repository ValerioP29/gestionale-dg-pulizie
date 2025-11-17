<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MonthlyOverviewSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(protected array $dataset)
    {
    }

    public function collection(): Collection
    {
        $rows = $this->dataset['overview'] ?? collect();
        $rows = $rows instanceof Collection ? $rows : collect($rows);

        return $rows->map(fn (array $row) => [
            $row['name'] ?? '',
            $row['site'] ?? '',
            $row['client'] ?? '',
            $row['hours'] ?? 0,
            $row['overtime'] ?? 0,
            $row['days'] ?? 0,
            $row['anomalies'] ?? 0,
            $row['notes'] ?? '',
        ]);
    }

    public function headings(): array
    {
        return ['Dipendente', 'Cantiere', 'Cliente', 'Ore', 'Straordinari', 'Giorni', 'Anomalie', 'Note'];
    }

    public function title(): string
    {
        return 'Riepilogo generale';
    }
}
