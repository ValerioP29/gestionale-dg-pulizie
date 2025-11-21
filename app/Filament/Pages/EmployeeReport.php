<?php

namespace App\Filament\Pages;

use App\Models\User;
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
use App\Exports\EmployeeCustomReportExport;

class EmployeeReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Report dipendente';
    protected static ?string $navigationGroup = 'Reportistica avanzata';
    protected static ?int $navigationSort = 50;
    protected static ?string $title = 'Report dipendente';
    protected static string $view = 'filament.pages.employee-report';

    public ?int $userId = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    /** @var array{user:?\App\Models\User,summary:array,rows:Collection} */
    public array $report = [
        'user' => null,
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
            'user_id' => null,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('user_id')
                ->label('Dipendente')
                ->options(
                    User::query()
                        ->orderBy('last_name')
                        ->orderBy('first_name')
                        ->get()
                        ->mapWithKeys(fn ($u) => [
                            $u->id => $u->full_name
                        ])
                        ->toArray()
                )
                ->searchable()
                ->placeholder('Seleziona dipendente')
                ->reactive()
                ->afterStateUpdated(function ($state) {
                    $this->userId = $state ? (int) $state : null;
                }),
            Forms\Components\DatePicker::make('date_from')
                ->label('Dal')
                ->default($this->dateFrom)
                ->reactive()
                ->afterStateUpdated(function (?string $state) {
                    $this->dateFrom = $state;
                }),
            Forms\Components\DatePicker::make('date_to')
                ->label('Al')
                ->default($this->dateTo)
                ->reactive()
                ->afterStateUpdated(function (?string $state) {
                    $this->dateTo = $state;
                }),
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
                ->action(fn () => $this->downloadCsv())
                ->requiresConfirmation(false),
            Actions\Action::make('downloadXlsx')
                ->label('Esporta XLSX')
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn () => $this->downloadXlsx())
                ->requiresConfirmation(false),
        ];
    }

    public function refreshReport(): void
    {
        if (! $this->userId) {
            $this->resetReport();
            Notification::make()
                ->title('Seleziona un dipendente')
                ->warning()
                ->send();

            return;
        }

        [$from, $to] = $this->resolvedRange();

        $builder = new WorkReportBuilder();
        $data = $builder->buildEmployeeReport($this->userId, $from, $to);

        $this->report = [
            'user' => $data['user'],
            'summary' => $data['summary'],
            'rows' => $data['rows'],
        ];
    }

    public function downloadCsv()
    {
        if (! $this->userId) {
            Notification::make()
                ->title('Seleziona un dipendente')
                ->warning()
                ->send();

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

        $filename = sprintf(
            'report_dipendente_%s_%s.csv',
            Str::slug(optional($this->report['user'])->full_name ?? 'dipendente', '_'),
            now()->format('Ymd_His')
        );

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Data', 'Cantiere', 'Ore', 'Straordinari', 'Stato', 'Anomalie']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['date']->format('d/m/Y'),
                    $row['site'],
                    number_format((float) $row['hours'], 2, ',', '.'),
                    number_format((float) $row['overtime'], 2, ',', '.'),
                    ucfirst((string) $row['status']),
                    implode(' | ', $row['anomalies'] ?? []),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadXlsx()
    {
        if (! $this->userId) {
            Notification::make()
                ->title('Seleziona un dipendente')
                ->warning()
                ->send();

            return null;
        }

        [$from, $to] = $this->resolvedRange();

        return Excel::download(
            new EmployeeCustomReportExport($this->userId, $from, $to),
            sprintf('report_dipendente_%s.xlsx', now()->format('Ymd_His'))
        );
    }

    private function resetReport(): void
    {
        $this->report = [
            'user' => null,
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
