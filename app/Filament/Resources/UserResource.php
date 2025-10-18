<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Anagrafica';

    /** Accesso al menu solo per Admin e HR */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin','HR']) ?? false;
    }

    /** Query globale: Admin e HR vedono tutto, altri niente */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (!$user) return parent::getEloquentQuery()->whereRaw('1=0');

        if ($user->hasAnyRole(['Admin','HR'])) {
            return parent::getEloquentQuery();
        }

        // Capocantiere e Dipendente non devono vedere utenti
        return parent::getEloquentQuery()->whereRaw('1=0');
    }

    /** FORM */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dati Personali')
                ->schema([
                    Forms\Components\TextInput::make('first_name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(100)
                        ->columnSpan(6),

                    Forms\Components\TextInput::make('last_name')
                        ->label('Cognome')
                        ->required()
                        ->maxLength(100)
                        ->columnSpan(6),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(190)
                        ->columnSpan(6),

                    Forms\Components\TextInput::make('phone')
                        ->label('Telefono')
                        ->tel()
                        ->maxLength(30)
                        ->columnSpan(6),

                    Forms\Components\Toggle::make('active')
                        ->label('Attivo')
                        ->default(true)
                        ->columnSpan(6),
                ])
                ->columns(12),

            Forms\Components\Section::make('Ruoli')
                ->schema([
                    Forms\Components\Select::make('roles')
                        ->label('Ruoli')
                        ->relationship('roles', 'name')
                        ->options(fn() => Role::pluck('name', 'name'))
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->visible(fn() => auth()->user()->hasAnyRole(['Admin','HR'])),
                ])
                ->visible(fn() => auth()->user()->hasAnyRole(['Admin','HR'])),
        ]);
    }

    /** TABELLA */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('Nome')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('last_name')
                    ->label('Cognome')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefono')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('active')
                    ->label('Attivo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Ruoli')
                    ->badge()
                    ->sortable()
                    ->visible(fn() => auth()->user()->hasAnyRole(['Admin','HR'])),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creato')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')->label('Attivo'),
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Ruolo')
                    ->relationship('roles', 'name')
                    ->visible(fn() => auth()->user()->hasAnyRole(['Admin','HR'])),
            ])
            ->defaultSort('first_name')
            ->paginated([25, 50, 100]);
    }

    /** CRUD pages */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    /** Disabilita delete per HR */
    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        if ($user->hasRole('Admin')) return true;
        if ($user->hasRole('HR')) return !$record->hasRole('Admin');

        return false;
    }
}
