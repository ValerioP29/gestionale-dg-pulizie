<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgReportCacheResource\Pages;
use App\Jobs\GenerateReportsCache as GenerateReportsCacheJob;
use App\Models\DgReportCache;
use App\Models\DgSite;
use App\Models\User;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\CarbonImmutable;

class DgReportCacheResource extends Resource
{
    protected static ?string $model = DgReportCache::class;
    protected static ?string $navigationGroup = 'Gestione Cantieri';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Report Lavorativi';
    protected static ?string $pluralModelLabel = 'Report Lavorativi';
    protected static ?string $modelLabel = 'Report Lavorativo';
    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Dipendente')
                ->options(User::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\Select::make('site_id')
                ->label('Cantiere')
                ->options(DgSite::orderBy('name')->pluck('name', 'id'))
                ->searchable(),

            Forms\Components\DatePicker::make('period_start')->label('Da')->required(),
            Forms\Components\DatePicker::make('period_end')->label('A')->required(),

            Forms\Components\TextInput::make('worked_hours')->numeric()->label('Ore lavorate'),
            Forms\Components\TextInput::make('days_present')->numeric()->label('Presenze'),
            Forms\Components\TextInput::make('days_absent')->numeric()->label('Assenze'),
            Forms\Components\TextInput::make('overtime_minutes')->numeric()->label('Straordinario (min)'),
            Forms\Components\Toggle::make('is_final')->label('Finale'),

            Forms\Components\Textarea::make('anomaly_flags')->label('Anomalie')->rows(3)->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->sortable()->label('Dipendente'),
                Tables\Columns\TextColumn::make('resolvedSite.name')->sortable()->label('Cantiere'),
                Tables\Columns\TextColumn::make('period_start')->date()->label('Dal')->sortable(),
                Tables\Columns\TextColumn::make('period_end')->date()->label('Al')->sortable(),

                Tables\Columns\TextColumn::make('worked_hours')->label('Ore')->sortable(),
                Tables\Columns\TextColumn::make('days_present')->label('Presenze')->sortable(),
                Tables\Columns\TextColumn::make('days_absent')->label('Assenze')->sortable(),

                Tables\Columns\IconColumn::make('is_final')
                    ->boolean()
                    ->label('Finale')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock'),

                Tables\Columns\TextColumn::make('generated_at')
                    ->dateTime('d/m H:i')
                    ->label('Generato')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Dipendente')
                    ->options(User::orderBy('name')->pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('site_id')
                    ->label('Cantiere')
                    ->options(DgSite::orderBy('name')->pluck('name', 'id')),

                Tables\Filters\Filter::make('periodo')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('dal'),
                        Forms\Components\DatePicker::make('to')->label('al'),
                    ])
                    ->query(fn (Builder $q, array $data) =>
                        $q
                            ->when($data['from'], fn ($x) => $x->whereDate('period_start', '>=', $data['from']))
                            ->when($data['to'], fn ($x) => $x->whereDate('period_end', '<=', $data['to']))
                    ),
            ])
            ->defaultSort('period_start', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('rigenera_mese')
                    ->label('Rigenera mese')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\TextInput::make('year')
                            ->label('Anno')
                            ->numeric()
                            ->default(now()->year)
                            ->required(),
                        Forms\Components\Select::make('month')
                            ->label('Mese')
                            ->options([
                                '1'  => 'Gennaio',
                                '2'  => 'Febbraio',
                                '3'  => 'Marzo',
                                '4'  => 'Aprile',
                                '5'  => 'Maggio',
                                '6'  => 'Giugno',
                                '7'  => 'Luglio',
                                '8'  => 'Agosto',
                                '9'  => 'Settembre',
                                '10' => 'Ottobre',
                                '11' => 'Novembre',
                                '12' => 'Dicembre',
                            ])
                            ->default((string) now()->month)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $year = (int) $data['year'];
                        $month = (int) $data['month'];

                        $start = CarbonImmutable::createFromDate($year, $month, 1)->startOfMonth();
                        $end = $start->endOfMonth();

                        GenerateReportsCacheJob::dispatch(
                            $start->toDateString(),
                            $end->toDateString()
                        );

                        Notification::make()
                            ->title('Rigenerazione avviata')
                            ->body("Report del {$start->translatedFormat('F Y')} in elaborazione")
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin', 'supervisor'])),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn () => auth()->user()->hasAnyRole(['admin','supervisor'])),
                Tables\Actions\DeleteAction::make()->visible(fn () => auth()->user()->isRole('admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('rigenera')
                    ->label('Rigenera report')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $report) {
                            \App\Jobs\GenerateReportsCache::dispatch(
                                $report->period_start,
                                $report->period_end
                            );
                        }
                    })
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin','supervisor'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDgReportCaches::route('/'),
            'edit' => Pages\EditDgReportCache::route('/{record}/edit'),
        ];
    }
}
