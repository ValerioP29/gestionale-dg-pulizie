<?php

namespace App\Exports\Sheets;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Events\AfterSheet;

class MonthlyCalendarSheet implements 
    FromCollection, 
    WithHeadings, 
    WithTitle, 
    ShouldAutoSize, 
    WithStyles, 
    WithEvents
{
    protected CarbonImmutable $start;
    protected int $daysInMonth;

    public function __construct(protected array $dataset)
    {
        $this->start = $dataset['start'];
        $this->daysInMonth = $this->start->daysInMonth;
    }

    public function collection(): Collection
    {
        $rows = collect($this->dataset['calendar'] ?? []);

        return $rows->map(function (array $row) {

            $contract = $row['contract_week'] ?? [];

            // base columns
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
                $contract['mon'] ?? '',
                $contract['tue'] ?? '',
                $contract['wed'] ?? '',
                $contract['thu'] ?? '',
                $contract['fri'] ?? '',
                $contract['sat'] ?? '',
                $contract['sun'] ?? '',
                $row['contract_week_total'] ?? '',
            ];

            // dynamic days
            $days = [];
            for ($d = 1; $d <= $this->daysInMonth; $d++) {
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
        $dayHeadings = [];

        for ($d = 1; $d <= $this->daysInMonth; $d++) {
            $date = $this->start->day($d)->locale('it');
            $dow = strtoupper(substr($date->dayName, 0, 3));   // LUN, MAR, MER
            $dayHeadings[] = "{$dow} {$d}";
        }

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
            'LUNEDI',
            'MARTEDI',
            'MERCOLEDÌ',
            'GIOVEDÌ',
            'VENERDÌ',
            'SABATO',
            'DOMENICA',
            'TOTALE ORE CONTRATTO',
            ...$dayHeadings,
            'totale',
            'straord.',
            'nota extra',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                // freeze the top row
                $sheet->freezePane('A2');

                // header background
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => '4A90E2'],
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ]
                ]);

                // borders for all cells
                $range = 'A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow();
                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'AAAAAA']
                        ]
                    ]
                ]);

                // center day columns
                $lastCol = $sheet->getHighestColumn();
                for ($col = 'T'; $col <= $lastCol; $col++) {
                    $sheet->getStyle($col)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
        ];
    }

    public function title(): string
    {
        return 'Calendario Dipendenti';
    }
}
