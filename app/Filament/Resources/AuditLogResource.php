<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Registro attività';
    protected static ?string $navigationGroup = 'Sicurezza';
    protected static ?int $navigationSort = 100;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('description')
                    ->label('Azione')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('log_name')
                    ->label('Sezione')
                    ->badge(),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Utente')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Modello')
                    ->formatStateUsing(fn ($state) => class_basename($state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])

            ->defaultSort('created_at', 'desc')

            ->filters([
                // ✅ FILTRO SEZIONE
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Sezione')
                    ->options(
                        Activity::query()
                            ->select('log_name')
                            ->distinct()
                            ->pluck('log_name','log_name')
                    ),

                // ✅ FILTRO UTENTE
                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('Utente')
                    ->options(User::pluck('name', 'id')),

                // ✅ FILTRO AZIONE (description)
                Tables\Filters\SelectFilter::make('description')
                    ->label('Azione')
                    ->options(
                        Activity::query()
                            ->select('description')
                            ->distinct()
                            ->pluck('description','description')
                    )
                    ->searchable(),

                // ✅ FILTRO DATA DA/A
                Tables\Filters\Filter::make('created_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Da'),
                        Forms\Components\DatePicker::make('to')->label('A'),
                    ])
                    ->query(function ($query, array $data) {
                        $query
                            ->when($data['from'] ?? null, fn ($q, $date) =>
                                $q->whereDate('created_at', '>=', $date)
                            )
                            ->when($data['to'] ?? null, fn ($q, $date) =>
                                $q->whereDate('created_at', '<=', $date)
                            );
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
        ];
    }
}
