<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgSiteAssignmentResource\Pages;
use App\Models\DgSiteAssignment;
use App\Models\User;
use App\Models\DgSite;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class DgSiteAssignmentResource extends Resource
{
    protected static ?string $model = DgSiteAssignment::class;

    // Non deve più comparire nel menu laterale
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Assegnazione';
    protected static ?string $pluralModelLabel = 'Assegnazioni';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Dipendente')
                ->getSearchResultsUsing(function (string $search) {
                    $search = strtolower($search);
                    return User::query()
                        ->where('role', 'employee')
                        ->where(function ($q) use ($search) {
                            $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                              ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
                        })
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(fn ($u) => [$u->id => "{$u->name} ({$u->email})"])
                        ->toArray();
                })
                ->getOptionLabelUsing(fn ($value) => optional(User::find($value))->name ?? '—')
                ->required()
                ->searchable(),

            Forms\Components\Select::make('site_id')
                ->label('Cantiere')
                ->options(DgSite::query()->pluck('name', 'id')->toArray())
                ->required()
                ->searchable(),

            Forms\Components\DatePicker::make('assigned_from')
                ->label('Dal')
                ->required(),

            Forms\Components\DatePicker::make('assigned_to')
                ->label('Al')
                ->nullable()
                ->rule('after_or_equal:assigned_from'),

            Forms\Components\Textarea::make('notes')
                ->label('Note')
                ->rows(2),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dipendente')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->sortable(),

                Tables\Columns\TextColumn::make('site.name')
                    ->label('Cantiere')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('assigned_from')->label('Dal')->date(),
                Tables\Columns\TextColumn::make('assigned_to')->label('Al')->date(),

                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('Stato')
                    ->formatStateUsing(fn ($state) => $state ? 'Attivo' : 'Scaduto')
                    ->colors([
                        'success' => fn ($state) => (bool) $state === true,
                        'gray'    => fn ($state) => (bool) $state === false,
                    ]),

                Tables\Columns\TextColumn::make('notes')->label('Note')->limit(40),
                Tables\Columns\TextColumn::make('assignedBy.name')->label('Assegnato da')->default('—'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        self::validateAssignment($data);
                        $data['assigned_by'] = auth()->id();
                        return $data;
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        self::validateAssignment($data);
                        $data['assigned_by'] = auth()->id();
                        return $data;
                    })
                    ->failureNotificationTitle('Errore di validazione')
                    ->failureNotification(fn () => 'Date incoerenti o assegnazione duplicata.')
            ]);
    }

    /** Regola anti-sovrapposizione */
    protected static function validateAssignment(array $data): void
    {
        $userId = $data['user_id'];
        $from   = $data['assigned_from'];
        $to     = $data['assigned_to'] ?? now()->addYears(10)->format('Y-m-d');

        $overlap = DgSiteAssignment::where('user_id', $userId)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('assigned_from', [$from, $to])
                  ->orWhereBetween('assigned_to', [$from, $to])
                  ->orWhere(function ($sub) use ($from, $to) {
                      $sub->where('assigned_from', '<=', $from)
                          ->where(function ($inner) use ($to) {
                              $inner->whereNull('assigned_to')
                                    ->orWhere('assigned_to', '>=', $to);
                          });
                  });
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'site_id' => 'Dipendente già assegnato a un cantiere nello stesso periodo.',
            ]);
        }
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDgSiteAssignments::route('/'),
            'create' => Pages\CreateDgSiteAssignment::route('/create'),
            'edit'   => Pages\EditDgSiteAssignment::route('/{record}/edit'),
        ];
    }
}
