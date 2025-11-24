<?php

namespace App\Filament\Pages;

use App\Exports\ClientCustomReportExport;
use App\Models\DgClient;
use App\Services\Reports\WorkReportBuilder;
use Carbon\CarbonImmutable;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;

class ClientReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Report cliente';
    protected static ?string $navigationGroup = 'Reportistica avanzata';
    protected static ?int $navigationSort = 52;
    protected static string $view = 'filament.pages.client-report';

    public ?int $clientId = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public array $filters = [];

    /** @var array{client:?\App\Models\DgClient,summary:array,rows:Collection} */
    public array $report = [
        'client' => null,
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
            'client_id' => null,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ]);
    }

    protected function getFormStatePath(): string
    {
        return 'filters';
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('client_id')
                ->label('Cliente')
                ->options(DgClient::query()->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->reactive()
                ->afterStateUpdated(fn ($state) => $this->clientId = $state ? (int) $state : null),
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
                ->action(fn () => $this->refreshReport())
                ->disabled(fn () => ! $this->hasRequiredFilters()),
            Actions\Action::make('downloadXlsx')
                ->label('Esporta XLSX')
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn () => $this->downloadXlsx())
                ->disabled(fn () => ! $this->hasRequiredFilters()),
        ];
    }

    public function refreshReport(): void
    {
        $this->syncFormState();

        if (! $this->clientId) {
            $this->resetReport();
            Notification::make()->title('Seleziona un cliente')->warning()->send();

            return;
        }

        [$from, $to] = $this->resolvedRange();
        $builder = new WorkReportBuilder();
        $data = $builder->buildClientReport($this->clientId, $from, $to);

        $this->report = [
            'client' => $data['client'],
            'summary' => $data['summary'],
            'rows' => $data['rows'],
        ];
    }

    public function downloadXlsx()
    {
        $this->syncFormState();

        if (! $this->clientId) {
            Notification::make()->title('Seleziona un cliente')->warning()->send();

            return null;
        }

        [$from, $to] = $this->resolvedRange();

        return Excel::download(
            new ClientCustomReportExport($this->clientId, $from, $to),
            sprintf('report_cliente_%s.xlsx', now()->format('Ymd_His'))
        );
    }

    private function resetReport(): void
    {
        $this->report = [
            'client' => null,
            'summary' => [
                'total_hours' => 0.0,
                'overtime_hours' => 0.0,
                'days_worked' => 0,
                'anomalies' => 0,
            ],
            'rows' => collect(),
        ];
    }

    private function syncFormState(): void
    {
        $state = $this->form->getState();
        
        $value = $state['client_id'] ?? null;

        if ($value instanceof \Illuminate\Database\Eloquent\Collection) {
            $this->clientId = $value->first()?->id ?? null;
        } elseif ($value instanceof \App\Models\DgClient) {
            $this->clientId = $value->id;
        } else {
            $this->clientId = $value ? (int) $value : null;
        }

        $this->dateFrom = $state['date_from'] ?? $this->dateFrom;
        $this->dateTo = $state['date_to'] ?? $this->dateTo;

        $this->form->fill([
            'client_id' => $this->clientId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ]);
    }

    private function hasRequiredFilters(): bool
    {
        $this->syncFormState();

        return (bool) $this->clientId;
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
