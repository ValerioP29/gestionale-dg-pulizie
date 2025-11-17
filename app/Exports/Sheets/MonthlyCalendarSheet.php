<?php

namespace App\Exports\Sheets;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MonthlyCalendarSheet implements FromCollection, WithHeadings, WithTitle
{
    protected int $daysInMonth;

    public function __construct(protected array $dataset)
    {
        /** @var CarbonImmutable $start */
        $start = $dataset['start'];
        $this->daysInMonth = $start->daysInMonth;
    }

    public function collection(): Collection
    {
        $rows = $this->dataset['calendar'] ?? collect();
        $rows = $rows instanceof Collection ? $rows : collect($rows);

        return $rows->map(function (array $row) {
            $base = [
                $row['cliente_cod'] ?? '',
                $row['site_cod'] ?? '',
                $row['cliente_nome'] ?? '',
                $row['cantiere'] ?? '',
                $row['matricola'] ?? '',
                $row['utente'] ?? '',
                $row['hired_at'] ?? '',
                $row['end_at'] ?? '',
            ];

            $days = [];
            for ($d = 1; $d <= $this->daysInMonth; $d++) {
                $days[] = $row['giorni'][$d] ?? '';
            }

            return array_merge($base, $days, [
                number_format((float) ($row['total_hours'] ?? 0), 2, '.', ''),
                number_format((float) ($row['overtime_hours'] ?? 0), 2, '.', ''),
                $row['notes'] ?? '',
            ]);
        });
    }

    public function headings(): array
    {
        $headings = ['CLIENTE COD', 'SITE COD', 'CLIENTE', 'CANTIERE', 'MATRICOLA', 'DIPENDENTE', 'ASSUNZIONE', 'SCADENZA'];

        for ($d = 1; $d <= $this->daysInMonth; $d++) {
            $headings[] = (string) $d;
        }

        $headings[] = 'TOTALE';
        $headings[] = 'STRAORDINARI';
        $headings[] = 'NOTE';

        return $headings;
    }

    public function title(): string
    {
        return 'Calendario Dipendenti';
    }
}
