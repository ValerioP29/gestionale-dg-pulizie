<?php

namespace App\Filament\Pages;

use App\Exports\SiteCustomReportExport;
use App\Models\DgSite;
use App\Services\Reports\WorkReportBuilder;
use Carbon\CarbonImmutable;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class SiteReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Report cantiere';
    protected static ?string $navigationGroup = 'Reportistica avanzata';
    protected static ?int $navigationSort = 51;
    protected static ?string $title = 'Report cantiere';
    protected static string $view = 'filament.pages.site-report';

    public ?int $siteId = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    /** @var array{site:?\App\Models\DgSite,summary:array,rows:Collection} */
    public array $report = [
        'site' => null,
        'summary' => [
            'total_hours' => 0.0,
            'overtime_hours' => 0.0,
            'days_worked' => 0,
            'anomalies' => 0,
        ],
        'rows' => null,
    ];

    public function mount(): void
    {
        $now = CarbonImmutable::now();
        $this->dateFrom = $now->startOfMonth()->toDateString();
        $this->dateTo = $now->endOfMonth()->toDateString();
        $this->report['rows'] = collect();
        $this->form->fill([
            'site_id' => null,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('site_id')
                ->label('Cantiere')
                ->options(
                    DgSite::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->searchable()
                ->reactive()
                ->afterStateUpdated(fn ($state) => $this->siteId = $state ? (int) $state : null),
            Forms\Components\DatePicker::make('date_from')
                ->label('Dal')
                ->default($this->dateFrom)
                ->reactive()
                ->afterStateUpdated(fn (?string $state) => $this->dateFrom = $state),
            Forms\Components\DatePicker::make('date_to')
                ->label('Al')
                ->default($this->dateTo)
                ->reactive()
                ->afterStateUpdated(fn (?string $state) => $this->dateTo = $state),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Aggiorna report')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->refreshReport()),
            Actions\Action::make('downloadCsv')
                ->label('Esporta CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->downloadCsv()),
            Actions\Action::make('downloadXlsx')
                ->label('Esporta XLSX')
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn () => $this->downloadXlsx()),
        ];
    }

    public function refreshReport(): void
    {
        if (! $this->siteId) {
            $this->resetReport();
            Notification::make()->title('Seleziona un cantiere')->warning()->send();

            return;
        }

        [$from, $to] = $this->resolvedRange();
        $builder = new WorkReportBuilder();
        $data = $builder->buildSiteReport($this->siteId, $from, $to);

        $this->report = [
            'site' => $data['site'],
            'summary' => $data['summary'],
            'rows' => $data['rows'],
        ];
    }

    public function downloadCsv()
    {
        if (! $this->siteId) {
            Notification::make()->title('Seleziona un cantiere')->warning()->send();

            return null;
        }

        if ($this->report['rows'] === null) {
            $this->report['rows'] = collect();
        }

        $rows = $this->report['rows'] instanceof Collection
            ? $this->report['rows']
            : collect($this->report['rows']);

        if ($rows->isEmpty()) {
            $this->refreshReport();
            $rows = $this->report['rows'];
        }

        $filename = sprintf('report_cantiere_%s_%s.csv', Str::slug(optional($this->report['site'])->name ?? 'cantiere', '_'), now()->format('Ymd_His'));

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Dipendente', 'Giorni', 'Ore', 'Straordinari', 'Anomalie']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['user'],
                    $row['days'],
                    number_format((float) $row['hours'], 2, ',', '.'),
                    number_format((float) $row['overtime'], 2, ',', '.'),
                    $row['anomalies'],
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function downloadXlsx()
    {
        if (! $this->siteId) {
            Notification::make()->title('Seleziona un cantiere')->warning()->send();

            return null;
        }

        [$from, $to] = $this->resolvedRange();

        return Excel::download(
            new SiteCustomReportExport($this->siteId, $from, $to),
            sprintf('report_cantiere_%s.xlsx', now()->format('Ymd_His'))
        );
    }

    private function resetReport(): void
    {
        $this->report = [
            'site' => null,
            'summary' => [
                'total_hours' => 0.0,
                'overtime_hours' => 0.0,
                'days_worked' => 0,
                'anomalies' => 0,
            ],
            'rows' => collect(),
        ];
    }

    private function resolvedRange(): array
    {
        try {
            $from = CarbonImmutable::parse($this->dateFrom ?? now()->toDateString())->startOfDay();
        } catch (\Throwable $exception) {
            $from = CarbonImmutable::now()->startOfMonth();
        }

        try {
            $to = CarbonImmutable::parse($this->dateTo ?? now()->toDateString())->endOfDay();
        } catch (\Throwable $exception) {
            $to = $from->endOfMonth();
        }

        if ($to->lt($from)) {
            $to = $from->endOfDay();
        }

        return [$from, $to];
    }
}
