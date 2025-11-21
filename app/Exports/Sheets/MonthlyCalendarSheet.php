<?php

namespace App\Exports\Sheets;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MonthlyCalendarSheet implements FromCollection, WithHeadings, WithTitle
{
    private const DAY_HEADINGS = [
        'MER 1',
        'GIOV 2',
        'VEN 3',
        'SAB 4',
        'DOM 5',
        'VEN-6',
        'SAB-7',
        'DOM-08',
        'LUN-9',
        'mar-10',
        'MERC-11',
        'GIO-12',
        'VEN-13',
        'SAB-14',
        'DOM-15',
        'LUN-16',
        'mar-17',
        'MER-18',
        'GIO-19',
        'VEN-20',
        'SAB-21',
        'DOM-22',
        'LUN-23',
        'MART-24',
        'MER-25',
        'GIO-26',
        'VEN-27',
        'SAB-28',
        'DOM-29',
        'LUN-30',
        '31',
    ];

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
            $contractWeek = $row['contract_week'] ?? [];

            $base = [
                $row['tipologia'] ?? '',
                $row['cliente_cod'] ?? '',
                $row['site_cod'] ?? '',
                $row['cliente_nome'] ?? '',
                $row['cantiere'] ?? '',
                $row['matricola'] ?? '',
                $row['last_name'] ?? '',
                $row['first_name'] ?? '',
                $row['hired_at'] ?? '',
                $row['end_at'] ?? '',
                $row['contract_hours_monthly'] ?? '',
                $contractWeek['mon'] ?? '',
                $contractWeek['tue'] ?? '',
                $contractWeek['wed'] ?? '',
                $contractWeek['thu'] ?? '',
                $contractWeek['fri'] ?? '',
                $contractWeek['sat'] ?? '',
                $contractWeek['sun'] ?? '',
                $row['contract_week_total'] ?? '',
            ];

            $days = [];
            for ($d = 1; $d <= 31; $d++) {
                $days[] = $row['giorni'][$d] ?? '';
            }

            return array_merge($base, $days, [
                $row['total_hours'] ?? '',
                $row['overtime_hours'] ?? '',
                $row['notes_detail'] ?? ($row['notes'] ?? ''),
            ]);
        });
    }

    public function headings(): array
    {
        return [
            'tipologia',
            'COD. RAGG. CLI.',
            'COD. CLI.',
            'cliente',
            'cantiere',
            'N. Matricola',
            'COGNOME',
            'NOME',
            'DATA ASSUNZIONE',
            'DATA SCADENZA',
            'ORE CONTRATTO (da user)',
            'LUNEDI',
            'MARTEDI',
            'MERCOLEDÌ',
            'GIOVEDÌ',
            'VENERDÌ',
            'SABATO',
            'DOMENICA',
            'TOTALE ORE CONTRATTO',
            ...self::DAY_HEADINGS,
            'totale',
            'straord.',
            'nota extra',
        ];
    }

    public function title(): string
    {
        return 'Calendario Dipendenti';
    }
}
