<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\DgSite;
use App\Models\DgSiteAssignment;
use Illuminate\Validation\ValidationException;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'siteAssignments';
    protected static ?string $title = 'Assegnazioni Cantieri';
    protected static ?string $recordTitleAttribute = 'site.name';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('site_id')
                ->label('Cantiere')
                ->options(DgSite::pluck('name','id'))
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

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site.name')->label('Cantiere')->sortable(),
                Tables\Columns\TextColumn::make('assigned_from')->label('Dal')->date(),
                Tables\Columns\TextColumn::make('assigned_to')->label('Al')->date(),
                Tables\Columns\TextColumn::make('notes')->label('Note')->limit(40),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function ($data) {
                        $data['user_id'] = $this->ownerRecord->id;
                        $data['assigned_by'] = auth()->id();
                        $this->validateAssignment($data);
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function ($data) {
                        $data['assigned_by'] = auth()->id();
                        $this->validateAssignment($data);
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    protected function validateAssignment(array $data): void
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
                'assigned_from' => 'Esiste già un’assegnazione attiva in quella data.',
            ]);
        }
    }
}
