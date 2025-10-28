<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgUserJobTitleResource\Pages;
use App\Models\DgUserJobTitle;
use App\Models\User;
use App\Models\DgJobTitle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Carbon\Carbon;

class DgUserJobTitleResource extends Resource
{
    protected static ?string $model = DgUserJobTitle::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'HR';
    protected static ?string $modelLabel = 'Mansione dipendente';
    protected static ?string $pluralModelLabel = 'Storico mansioni';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Dipendente')
                ->options(User::orderBy('last_name')->pluck('last_name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\Select::make('job_title_id')
                ->label('Mansione')
                ->options(DgJobTitle::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\DatePicker::make('from_date')
                ->label('Dal')
                ->default(today())
                ->required(),

            Forms\Components\DatePicker::make('to_date')
                ->label('Al')
                ->nullable()
                ->helperText('Lascia vuoto per mansione attuale'),

            Forms\Components\Textarea::make('notes')
                ->label('Note')
                ->rows(2),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Dipendente')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('jobTitle.name')
                    ->label('Mansione')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('from_date')->label('Dal')->date(),
                Tables\Columns\TextColumn::make('to_date')->label('Al')->date(),
                Tables\Columns\TextColumn::make('notes')->label('Note')->limit(40),
            ])
            ->defaultSort('from_date', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDgUserJobTitles::route('/'),
            'create' => Pages\CreateDgUserJobTitle::route('/create'),
            'edit'   => Pages\EditDgUserJobTitle::route('/{record}/edit'),
        ];
    }
}
