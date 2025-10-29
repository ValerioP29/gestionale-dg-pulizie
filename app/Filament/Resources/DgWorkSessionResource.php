<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgWorkSessionResource\Pages;
use App\Models\DgWorkSession;
use App\Models\User;
use App\Models\DgSite;
use App\Services\Anomalies\AnomalyEngine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DgWorkSessionResource extends Resource
{
    protected static ?string $model = DgWorkSession::class;
    protected static ?string $navigationGroup = 'Gestione Cantieri';
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $modelLabel = 'Sessione di lavoro';
    protected static ?string $pluralModelLabel = 'Sessioni di lavoro';
    protected static ?int $navigationSort = 35;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Dipendente')
                ->relationship('user', 'full_name')
                ->searchable()
                ->required(),

            Forms\Components\Select::make('site_id')
                ->label('Cantiere dichiarato')
                ->relationship('site', 'name')
                ->searchable(),

            Forms\Components\Select::make('resolved_site_id')
                ->label('Cantiere effettivo')
                ->relationship('resolvedSite', 'name')
                ->searchable()
                ->disabled(),

            Forms\Components\DatePicker::make('session_date')
                ->label('Data')
                ->required(),

            Forms\Components\DateTimePicker::make('check_in')->label('Entrata'),
            Forms\Components\DateTimePicker::make('check_out')->label('Uscita'),

            Forms\Components\TextInput::make('worked_minutes')
                ->numeric()
                ->label('Minuti lavorati')
                ->disabled(),

            Forms\Components\TextInput::make('overtime_minutes')
                ->numeric()
                ->label('Straordinari (min)')
                ->disabled(),

            Forms\Components\Select::make('status')
                ->options([
                    'complete' => 'Completa',
                    'incomplete' => 'Incompleta',
                    'invalid' => 'Non valida',
                ])
                ->label('Stato')
                ->disabled(),

            Forms\Components\Select::make('approval_status')
                ->options([
                    'pending'  => 'In attesa',
                    'approved' => 'Approvata',
                    'rejected' => 'Respinta',
                ])
                ->label('Approvazione')
                ->disabled(),

            Forms\Components\Textarea::make('extra_reason')
                ->label('Motivo straordinario')
                ->columnSpanFull()
                ->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                DgWorkSession::query()
                    ->with(['user', 'site', 'resolvedSite'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Dipendente')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('resolvedSite.name')
                    ->label('Cantiere')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('session_date')
                    ->label('Data')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('check_in')
                    ->label('Entrata')
                    ->dateTime('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('check_out')
                    ->label('Uscita')
                    ->dateTime('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('worked_minutes')
                    ->label('Minuti')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'complete',
                        'warning' => 'incomplete',
                        'danger'  => 'invalid',
                    ])
                    ->label('Stato'),

                Tables\Columns\BadgeColumn::make('approval_status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ])
                    ->label('Approvazione')
                    ->sortable(),

                Tables\Columns\IconColumn::make('anomaly_flags')
                    ->label('Anomalie')
                    ->tooltip(fn ($record) => $record->anomaly_summary)
                    ->icon(fn ($record) => $record->has_anomalies ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check')
                    ->color(fn ($record) => $record->has_anomalies ? 'danger' : 'success'),
            ])
            ->filters([
              SelectFilter::make('user_id')
                    ->label('Dipendente')
                    ->relationship('user', 'full_name')
                    ->searchable(),

                SelectFilter::make('resolved_site_id')
                    ->label('Cantiere')
                    ->options(DgSite::orderBy('name')->pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('status')
                    ->options([
                        'complete'   => 'Completa',
                        'incomplete' => 'Incompleta',
                        'invalid'    => 'Non valida',
                    ])
                    ->label('Stato'),

                SelectFilter::make('approval_status')
                    ->options([
                        'pending'  => 'In attesa',
                        'approved' => 'Approvata',
                        'rejected' => 'Respinta',
                    ])
                    ->label('Approvazione'),

                Filter::make('mese')
                    ->form([
                        Forms\Components\Select::make('month')
                            ->label('Mese')
                            ->options([
                                '01' => 'Gennaio', '02' => 'Febbraio', '03' => 'Marzo', '04' => 'Aprile',
                                '05' => 'Maggio',  '06' => 'Giugno',   '07' => 'Luglio', '08' => 'Agosto',
                                '09' => 'Settembre','10' => 'Ottobre','11' => 'Novembre','12' => 'Dicembre',
                            ]),
                        Forms\Components\TextInput::make('year')
                            ->default(now()->year)
                            ->numeric()
                            ->label('Anno'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['month'] ?? null, fn ($q) => $q->whereMonth('session_date', $data['month']))
                            ->when($data['year'] ?? null, fn ($q) => $q->whereYear('session_date', $data['year']));
                    }),
            ])
            ->defaultSort('session_date', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->visible(fn () => auth()->user()->hasAnyRole(['admin','supervisor'])),
                Tables\Actions\DeleteAction::make()->visible(fn () => auth()->user()->isRole('admin')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->visible(fn () => auth()->user()->isRole('admin')),
            Tables\Actions\Action::make('scarica_foglio_ore')
                 ->label('Scarica Foglio Ore Excel')
                    ->icon('heroicon-o-table-cells')
                    ->form([
                        Forms\Components\TextInput::make('year')
                            ->default(now()->year)
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('month')
                            ->options([
                                '1'=>'Gennaio','2'=>'Febbraio','3'=>'Marzo','4'=>'Aprile','5'=>'Maggio','6'=>'Giugno',
                                '7'=>'Luglio','8'=>'Agosto','9'=>'Settembre','10'=>'Ottobre','11'=>'Novembre','12'=>'Dicembre',
                            ])
                            ->default(now()->month)
                            ->required(),
                    ])
                    ->action(function ($data) {
                        return redirect()->route('reports.foglio-ore-excel', [
                            'year'  => $data['year'],
                            'month' => $data['month'],
                        ]);
                    })
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin','supervisor'])),
                            
                    ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approva')
                    ->label('Approva selezionate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $session) {
                            $session->approval_status = 'approved';
                            $session->approved_by = auth()->id();
                            $session->approved_at = now();
                            $session->saveQuietly();
                        }
                    })
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin','supervisor'])),

                Tables\Actions\BulkAction::make('recalc_anomalies')
                    ->label('Ricalcola anomalie')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $engine = new AnomalyEngine();
                        foreach ($records as $session) {
                            $engine->evaluateSession($session);
                        }
                    })
                    ->visible(fn () => auth()->user()->isRole('admin')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDgWorkSessions::route('/'),
            'create' => Pages\CreateDgWorkSession::route('/create'),
            'edit'   => Pages\EditDgWorkSession::route('/{record}/edit'),
        ];
    }
}
