<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgReportCacheResource\Pages;
use App\Models\DgReportCache;
use App\Models\User;
use App\Models\DgSite;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Jobs\GenerateReportsCache;
use Illuminate\Support\Facades\Bus;

class DgReportCacheResource extends Resource
{
    protected static ?string $model = DgReportCache::class;
    protected static ?string $navigationGroup = 'Report e Statistiche';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $modelLabel = 'Report';
    protected static ?string $pluralModelLabel = 'Report Ore Lavorate';
    protected static ?int $navigationSort = 40;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Dipendente')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('site.name')->label('Cantiere')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('worked_hours')->label('Ore Lavorate')->sortable(),
                Tables\Columns\TextColumn::make('days_present')->label('Giorni Presenza'),
                Tables\Columns\TextColumn::make('days_absent')->label('Assenze'),
                Tables\Columns\TextColumn::make('period_start')->label('Da')->date(),
                Tables\Columns\TextColumn::make('period_end')->label('A')->date(),
                Tables\Columns\TextColumn::make('generated_at')->label('Generato il')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                Tables\Filters\Filter::make('period')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dal'),
                        Forms\Components\DatePicker::make('to')->label('Al'),
                    ])
                    ->query(function ($query, array $data): void {
                        if (!empty($data['from'])) {
                            $query->whereDate('period_start', '>=', $data['from']);
                        }
                        if (!empty($data['to'])) {
                            $query->whereDate('period_end', '<=', $data['to']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()->visible(fn () => auth()->user()->isRole('admin')),
            ])
            ->headerActions([
                Tables\Actions\Action::make('rigeneraReport')
                    ->label('Rigenera Report')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Periodo da')->required(),
                        Forms\Components\DatePicker::make('to')->label('a')->required(),
                    ])
                    ->action(function (array $data): void {
                        Bus::dispatchSync(new GenerateReportsCache($data['from'], $data['to']));
                        Notification::make()
                            ->title('Report rigenerati')
                            ->body('I report sono stati aggiornati per il periodo selezionato.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin', 'supervisor'])),
            ])
            ->defaultSort('generated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDgReportCaches::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\AdvancedWorkedHoursChart::class,
        ];
    }

}
