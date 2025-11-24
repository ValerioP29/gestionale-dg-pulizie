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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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
    public array $filters = [];

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

    protected function getFormStatePath(): string
    {
        return 'filters';
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
                ->afterStateUpdated(fn ($state) => $this->siteId = $this->resolveSiteId($state)),
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

        if (! $this->siteId) {
            $this->resetReport();
            Notification::make()->title('Seleziona un cantiere')->warning()->send();

            return;
        }

        [$from, $to] = $this->resolvedRange();
        $builder = new WorkReportBuilder();
        $data = $builder->buildSiteReport($this->siteId, $from, $to);

        $this->report = [
            'site' => $data['site'] ?? null,
            'summary' => $data['summary'] ?? [
                'total_hours' => 0.0,
                'overtime_hours' => 0.0,
                'days_worked' => 0,
                'anomalies' => 0,
            ],
            'rows' => ($data['rows'] ?? null) instanceof Collection
                ? $data['rows']
                : collect($data['rows'] ?? []),
        ];
    }

    public function downloadXlsx()
    {
        $this->syncFormState();

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

    private function syncFormState(): void
    {
        $state = $this->form->getState();

        $this->siteId = $this->resolveSiteId($state['site_id'] ?? null);

        $this->dateFrom = $state['date_from'] ?? $this->dateFrom;
        $this->dateTo = $state['date_to'] ?? $this->dateTo;

        $this->form->fill([
            'site_id' => $this->siteId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ]);
    }

    private function resolveSiteId(mixed $value): ?int
    {
        if ($value instanceof Collection) {
            $value = $value->first();
        }

        if ($value instanceof Model) {
            if (! $value->exists) {
                return null;
            }

            $value = $value->id ?? null;
        }

        if (is_numeric($value)) {
            $id = (int) $value;

            return DgSite::query()->whereKey($id)->exists() ? $id : null;
        }

        return null;
    }

    private function hasRequiredFilters(): bool
    {
        $this->syncFormState();

        return (bool) $this->siteId;
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
