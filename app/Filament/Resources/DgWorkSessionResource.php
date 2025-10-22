<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgWorkSessionResource\Pages;
use App\Models\DgWorkSession;
use App\Models\User;
use App\Models\DgSite;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                ->options(User::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\Select::make('site_id')
                ->label('Cantiere')
                ->options(DgSite::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\DatePicker::make('session_date')
                ->label('Data')
                ->required(),

            Forms\Components\DateTimePicker::make('check_in')->label('Entrata'),
            Forms\Components\DateTimePicker::make('check_out')->label('Uscita'),

            Forms\Components\TextInput::make('worked_minutes')
                ->numeric()
                ->label('Minuti lavorati')
                ->disabled(),

            Forms\Components\Select::make('status')
                ->options([
                    'complete' => 'Completa',
                    'incomplete' => 'Incompleta',
                    'invalid' => 'Non valida',
                ])
                ->label('Stato'),

            Forms\Components\TextInput::make('source')
                ->label('Origine')
                ->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Dipendente')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('site.name')->label('Cantiere')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('session_date')->label('Data')->date()->sortable(),

                Tables\Columns\TextColumn::make('check_in')
                    ->label('Entrata')->dateTime('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out')
                    ->label('Uscita')->dateTime('H:i')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'complete',
                        'warning' => 'incomplete',
                        'danger'  => 'invalid',
                    ])
                    ->label('Stato')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_label')
                    ->label('Durata')
                    ->sortable(),

                Tables\Columns\TextColumn::make('source')
                    ->label('Origine')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Stato')
                    ->options([
                        'complete' => 'Complete',
                        'incomplete' => 'Incomplete',
                        'invalid' => 'Invalid',
                    ]),
               Tables\Filters\Filter::make('date_range')
                ->form([
                    Forms\Components\DatePicker::make('from')->label('Dal'),
                    Forms\Components\DatePicker::make('to')->label('Al'),
                ])
                ->query(fn ($query, array $data)
                    => $query
                        ->when($data['from'], fn ($q) => $q->whereDate('session_date', '>=', $data['from']))
                        ->when($data['to'], fn ($q) => $q->whereDate('session_date', '<=', $data['to']))
                ),
            ])
            ->defaultSort('session_date', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn () => auth()->user()->hasAnyRole(['admin','supervisor'])),
                Tables\Actions\DeleteAction::make()->visible(fn () => auth()->user()->isRole('admin')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->visible(fn () => auth()->user()->isRole('admin')),
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
