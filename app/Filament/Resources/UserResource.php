<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $policy = \App\Policies\UserPolicy::class;
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Anagrafica';
    protected static ?string $modelLabel = 'Utente';
    protected static ?string $pluralModelLabel = 'Utenti';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dati utente')
                ->schema([
                    Forms\Components\TextInput::make('first_name')
                        ->label('Nome')
                        ->maxLength(100)
                        ->required(),

                    Forms\Components\TextInput::make('last_name')
                        ->label('Cognome')
                        ->maxLength(100)
                        ->required(),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true),

                    Forms\Components\TextInput::make('phone')
                        ->label('Telefono')
                        ->tel()
                        ->maxLength(20),

                    // Campo ruolo con logica per admin/supervisor
                    Forms\Components\Select::make('role')
                        ->label('Ruolo')
                        ->options(function () {
                            $user = auth()->user();
                            if ($user && $user->role === 'supervisor') {
                                return [
                                    'viewer' => 'Visualizzatore',
                                    'employee' => 'Dipendente',
                                ];
                            }

                            return [
                                'admin' => 'Amministratore',
                                'supervisor' => 'Supervisore',
                                'viewer' => 'Visualizzatore',
                                'employee' => 'Dipendente',
                            ];
                        })
                        ->required()
                        ->native(false),

                    Forms\Components\Toggle::make('active')
                        ->label('Attivo')
                        ->default(true),
                ])
                ->columns(2),

            Forms\Components\Section::make('Sicurezza')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->rule('min:8')
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(fn ($state) => filled($state))
                        // hashing gestito dal model setPasswordAttribute
                        ->helperText(fn (string $operation) => $operation === 'create'
                            ? 'Obbligatoria in creazione. Verrà salvata in modo sicuro.'
                            : 'Lascia vuoto per non cambiare la password.'),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nome')
                    ->searchable(['first_name', 'last_name', 'name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->label('Ruolo')
                    ->colors([
                        'success' => 'admin',
                        'warning' => 'supervisor',
                        'gray' => 'viewer',
                        'danger' => 'employee',
                    ])
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')
                    ->label('Attivo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aggiornato')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Amministratore',
                        'supervisor' => 'Supervisore',
                        'viewer' => 'Visualizzatore',
                        'employee' => 'Dipendente',
                    ]),
                Tables\Filters\TernaryFilter::make('active')->label('Attivo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
