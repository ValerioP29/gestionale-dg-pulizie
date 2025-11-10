<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\DgSite;
use App\Models\DgContractSchedule;
use App\Models\DgJobTitle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Anagrafica';
    protected static ?string $modelLabel = 'Utente';
    protected static ?string $pluralModelLabel = 'Utenti';
    protected static ?string $policy = \App\Policies\UserPolicy::class;

    public static function form(Form $form): Form
    {
        return $form->schema(self::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Nome')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('email')
                ->label('Email')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('phone')
                ->label('Telefono')
                ->toggleable()
                ->sortable(),

            Tables\Columns\TextColumn::make('payroll_code')
                ->label('Matricola')
                ->toggleable()
                ->sortable()
                ->searchable(),

            Tables\Columns\BadgeColumn::make('role')
                ->label('Ruolo')
                ->colors([
                    'success' => 'admin',
                    'warning' => 'supervisor',
                    'gray'    => 'viewer',
                    'danger'  => 'employee',
                ])
                ->sortable(),

            Tables\Columns\IconColumn::make('active')
                ->label('Attivo')
                ->boolean(),

            Tables\Columns\TextColumn::make('mainSite.name')
                ->label('Cantiere')
                ->default('—')
                ->toggleable()
                ->sortable(),

            Tables\Columns\TextColumn::make('contract_hours_monthly')
                ->label('Ore/Mese')
                ->default('—')
                ->sortable(),

            Tables\Columns\TextColumn::make('jobTitle.name')
                ->label('Mansione')
                ->default('—')
                ->sortable(),

            Tables\Columns\TextColumn::make('updated_at')
                ->label('Aggiornato')
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('role')
                ->label('Ruolo')
                ->options([
                    'admin' => 'Amministratore',
                    'supervisor' => 'Supervisore',
                    'viewer' => 'Visualizzatore',
                    'employee' => 'Dipendente',
                ]),

            Tables\Filters\TernaryFilter::make('active')
                ->label('Attivo'),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Dati Anagrafici')
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
                        ->unique(ignoreRecord: true) // niente required
                        ->nullable(), // permette vuoto

                    Forms\Components\TextInput::make('phone')
                        ->label('Telefono')
                        ->maxLength(30),
                ])
                ->columns(2),

            Forms\Components\Section::make('Ruolo e Accesso')
                ->schema([
                    Forms\Components\Select::make('role')
                        ->label('Ruolo')
                        ->native(false)
                        ->options([
                            'admin'      => 'Amministratore',
                            'supervisor' => 'Supervisore',
                            'viewer'     => 'Visualizzatore',
                            'employee'   => 'Dipendente',
                        ])
                        ->required(),

                    Forms\Components\Select::make('job_title_id')
                        ->label('Mansione')
                        ->relationship('jobTitle', 'name')
                        ->searchable()
                        ->preload(),   // questo carica tutti i valori e permette dropdown pieno

                    Forms\Components\Toggle::make('active')
                        ->label('Attivo')
                        ->default(true),

                    Forms\Components\Toggle::make('can_login')
                        ->label('Può accedere')
                        ->default(true),
                ])
                ->columns(3),

            Forms\Components\Section::make('Contratto')
                ->schema([
                    Forms\Components\DatePicker::make('hired_at')
                        ->label('Assunto il'),

                    Forms\Components\DatePicker::make('contract_end_at')
                        ->label('Fine contratto')
                        ->nullable()
                        ->rule('after_or_equal:hired_at'),

                    Forms\Components\TextInput::make('payroll_code')
                        ->label('Matricola')
                        ->maxLength(64),
                ])
                ->columns(2),

            Forms\Components\Section::make('Orario Settimanale')
                ->schema([
                    Forms\Components\TextInput::make('mon')->numeric()->default(0)->label('Lunedì'),
                    Forms\Components\TextInput::make('tue')->numeric()->default(0)->label('Martedì'),
                    Forms\Components\TextInput::make('wed')->numeric()->default(0)->label('Mercoledì'),
                    Forms\Components\TextInput::make('thu')->numeric()->default(0)->label('Giovedì'),
                    Forms\Components\TextInput::make('fri')->numeric()->default(0)->label('Venerdì'),
                    Forms\Components\TextInput::make('sat')->numeric()->default(0)->label('Sabato'),
                    Forms\Components\TextInput::make('sun')->numeric()->default(0)->label('Domenica'),

                ])
                ->columns(4),

            Forms\Components\Section::make('Cantiere principale')
                ->schema([
                   Forms\Components\Select::make('main_site_id')
                    ->label('Cantiere principale')
                    ->relationship('mainSite', 'name')
                    ->searchable()
                    ->preload(),
                ]),

            Forms\Components\Section::make('Sicurezza')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->rule('min:8')
                        ->required(fn ($operation) => $operation === 'create')
                        ->dehydrated(fn ($state) => filled($state))
                        ->helperText(fn ($operation) =>
                            $operation === 'create'
                                ? 'Richiesta solo in creazione.'
                                : 'Lascia vuoto per non cambiarla.'
                        ),
                ])
                ->collapsible(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\UserResource\RelationManagers\AssignmentsRelationManager::class,
        ];
    }
}
