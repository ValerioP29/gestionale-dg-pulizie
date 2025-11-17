<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgUserJustificationResource\Pages;
use App\Models\DgUserJustification;
use App\Models\DgWorkSession;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class DgUserJustificationResource extends Resource
{
    protected static ?string $model = DgUserJustification::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Gestione Cantieri';

    protected static ?string $modelLabel = 'Giustificazione';

    protected static ?string $pluralModelLabel = 'Giustificazioni';

    protected static ?int $navigationSort = 37;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Dipendente')
                ->relationship('user', 'full_name')
                ->searchable()
                ->required(),
            Forms\Components\Select::make('session_id')
                ->label('Sessione collegata')
                ->options(fn (callable $get) => self::sessionOptions($get('user_id')))
                ->searchable()
                ->nullable(),
            Forms\Components\DatePicker::make('date')
                ->label('Dal')
                ->required(),
            Forms\Components\DatePicker::make('date_end')
                ->label('Al')
                ->helperText('Lasciare vuoto per singola giornata')
                ->nullable(),
            Forms\Components\Select::make('category')
                ->label('Categoria')
                ->options(DgUserJustification::CATEGORIES)
                ->default('leave')
                ->required(),
            Forms\Components\Toggle::make('covers_full_day')
                ->label('Copre l\'intera giornata')
                ->default(true)
                ->reactive(),
            Forms\Components\TextInput::make('minutes')
                ->label('Minuti coperti')
                ->numeric()
                ->minValue(0)
                ->visible(fn (callable $get) => ! (bool) $get('covers_full_day'))
                ->default(0),
            Forms\Components\Textarea::make('note')
                ->label('Note')
                ->columnSpanFull(),
            Forms\Components\FileUpload::make('attachment_path')
                ->label('Allegato')
                ->directory('justifications')
                ->downloadable()
                ->preserveFilenames(),
            Forms\Components\Select::make('status')
                ->label('Stato')
                ->options([
                    'pending'  => 'In attesa',
                    'approved' => 'Approvata',
                    'rejected' => 'Respinta',
                ])
                ->default('pending'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Dal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_end')
                    ->label('Al')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Dipendente')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->formatStateUsing(fn ($state) => Arr::get(DgUserJustification::CATEGORIES, $state, $state))
                    ->badge(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Stato')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending'  => 'In attesa',
                        'approved' => 'Approvata',
                        'rejected' => 'Respinta',
                        default    => $state,
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('covers_full_day')
                    ->label('Giornata intera')
                    ->boolean(),
                Tables\Columns\TextColumn::make('minutes')
                    ->label('Minuti coperti')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options([
                        'pending'  => 'In attesa',
                        'approved' => 'Approvata',
                        'rejected' => 'Respinta',
                    ]),
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(DgUserJustification::CATEGORIES),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approva')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (DgUserJustification $record) => $record->status === 'pending')
                    ->action(fn (DgUserJustification $record) => $record->markApproved(auth()->id())),
                Tables\Actions\Action::make('reject')
                    ->label('Respingi')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (DgUserJustification $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motivazione (opzionale)')
                            ->maxLength(500),
                    ])
                    ->action(function (DgUserJustification $record, array $data) {
                        $record->markRejected($data['reason'] ?? null, auth()->id());
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approve')
                    ->label('Approva selezionate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            if ($record->status === 'pending') {
                                $record->markApproved(auth()->id());
                            }
                        }
                    }),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDgUserJustifications::route('/'),
            'create' => Pages\CreateDgUserJustification::route('/create'),
            'edit' => Pages\EditDgUserJustification::route('/{record}/edit'),
        ];
    }

    private static function sessionOptions(?int $userId): array
    {
        if (! $userId) {
            return [];
        }

        return DgWorkSession::query()
            ->with(['resolvedSite', 'site'])
            ->where('user_id', $userId)
            ->orderByDesc('session_date')
            ->limit(90)
            ->get()
            ->mapWithKeys(fn ($session) => [
                $session->getKey() => sprintf('%s - %s',
                    $session->session_date?->format('d/m/Y'),
                    $session->resolvedSite?->name ?? $session->site?->name ?? 'Sede non assegnata'
                ),
            ])
            ->all();
    }
}
