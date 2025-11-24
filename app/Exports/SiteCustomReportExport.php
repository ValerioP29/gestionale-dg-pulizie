<?php

namespace App\Exports;

use App\Services\Reports\WorkReportBuilder;
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

class SiteCustomReportExport implements
    FromCollection,
    WithHeadings,
    WithTitle,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
    public function __construct(
        protected int $siteId,
        protected CarbonImmutable $from,
        protected CarbonImmutable $to,
    ) {
    }

    public function collection(): Collection
    {
        $builder = new WorkReportBuilder();
        $data    = $builder->buildSiteReport($this->siteId, $this->from, $this->to);
        $rows    = $data['rows'] instanceof Collection ? $data['rows'] : collect($data['rows']);

        return $rows->map(fn (array $row) => [
            $row['user'],
            $row['days'],
            number_format((float) $row['hours'], 2, '.', ''),
            number_format((float) $row['overtime'], 2, '.', ''),
            $row['anomalies'],
        ]);
    }

    public function headings(): array
    {
        return ['Dipendente', 'Giorni lavorati', 'Ore lavorate', 'Straordinari', 'Anomalie'];
    }

    public function title(): string
    {
        return 'Report Cantiere';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A2');

                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')
                    ->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'color'    => ['rgb' => '4A90E2'],
                        ],
                        'font' => [
                            'bold'  => true,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                        ],
                    ]);

                $range = 'A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow();
                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'AAAAAA'],
                        ],
                    ],
                ]);
            },
        ];
    }
}
