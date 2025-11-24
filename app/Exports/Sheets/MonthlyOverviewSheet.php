<?php

namespace App\Exports\Sheets;

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

class MonthlyOverviewSheet implements 
    FromCollection, 
    WithHeadings, 
    WithTitle, 
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
    public function __construct(protected array $dataset)
    {
    }

    public function collection(): Collection
    {
        $rows = collect($this->dataset['overview'] ?? []);

        return $rows->map(fn (array $row) => [
            $row['name'] ?? '',
            $row['site'] ?? '',
            $row['client'] ?? '',
            number_format((float) ($row['hours'] ?? 0), 2, '.', ''),
            number_format((float) ($row['overtime'] ?? 0), 2, '.', ''),
            $row['days'] ?? 0,
            $row['anomalies'] ?? 0,
            $row['notes'] ?? '',
        ]);
    }

    public function headings(): array
    {
        return [
            'Dipendente',
            'Cantiere',
            'Cliente',
            'Ore',
            'Straordinari',
            'Giorni',
            'Anomalie',
            'Note',
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

                // freeze header
                $sheet->freezePane('A2');

                // header style
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
                    ],
                ]);

                // borders
                $range = 'A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow();
                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'AAAAAA'],
                        ]
                    ]
                ]);
            }
        ];
    }

    public function title(): string
    {
        return 'Riepilogo generale';
    }
}
