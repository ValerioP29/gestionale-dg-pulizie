<?php

namespace App\Filament\Resources\DgSiteResource\RelationManagers;

use App\Models\User;
use App\Models\DgSiteAssignment;
use App\Filament\Resources\DgSiteAssignmentResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';
    protected static ?string $recordTitleAttribute = 'user.name';
    protected static ?string $title = 'Assegnazioni';

    public function form(Form $form): Form
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
                ->searchable()
                ->required(),

            Forms\Components\DatePicker::make('assigned_from')
                ->label('Dal')
                ->required()
                ->helperText('Data di inizio'),

            Forms\Components\DatePicker::make('assigned_to')
                ->label('Al')
                ->nullable()
                ->rule('after_or_equal:assigned_from')
                ->helperText('Se presente, non può precedere la data di inizio.'),

            Forms\Components\Textarea::make('notes')
                ->label('Note')
                ->rows(2),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Dipendente')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('assigned_from')->label('Dal')->date(),
                Tables\Columns\TextColumn::make('assigned_to')->label('Al')->date(),
                Tables\Columns\TextColumn::make('notes')->label('Note')->limit(40),
                Tables\Columns\TextColumn::make('assignedBy.name')->label('Assegnato da')->default('—'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        DgSiteAssignmentResource::validateAssignment($data);
                        $data['assigned_by'] = auth()->id();
                        return $data;
                    })
                    ->failureNotificationTitle('Errore di validazione')
                    ->failureNotification(fn () => 'Date incoerenti o assegnazione duplicata.'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        DgSiteAssignmentResource::validateAssignment($data);
                        $data['assigned_by'] = auth()->id();
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
