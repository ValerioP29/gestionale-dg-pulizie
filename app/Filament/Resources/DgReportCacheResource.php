<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgReportCacheResource\Pages;
use App\Models\DgReportCache;
use App\Models\User;
use App\Models\DgSite;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

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
