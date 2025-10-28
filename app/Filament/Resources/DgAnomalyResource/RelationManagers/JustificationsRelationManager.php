<?php

namespace App\Filament\Resources\DgAnomalyResource\RelationManagers;

use App\Models\DgJustificationType;
use App\Models\DgUserJustification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class JustificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'justifications';
    protected static ?string $title = 'Giustificazioni';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type_id')
                    ->label('Tipo giustificazione')
                    ->options(DgJustificationType::orderBy('label')->pluck('label', 'id'))
                    ->reactive()
                    ->required(),

                Forms\Components\Textarea::make('note')
                    ->label('Note')
                    ->rows(2),

                Forms\Components\FileUpload::make('attachment_path')
                    ->label('Documento allegato')
                    ->directory('justifications')
                    ->acceptedFileTypes(['application/pdf','image/*'])
                    ->maxSize(4096)
                    ->visible(fn ($get) =>
                        optional(DgJustificationType::find($get('type_id')))->requires_doc
                    ),

                Forms\Components\Select::make('status')
                    ->label('Stato')
                    ->options([
                        'open' => 'Aperta',
                        'approved' => 'Approvata',
                        'rejected' => 'Respinta',
                    ])
                    ->default('open')
                    ->disabled(fn () => !auth()->user()->hasAnyRole(['admin','supervisor'])),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type.label')->label('Tipo'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Stato')
                    ->colors([
                        'warning' => 'open',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ]),
                Tables\Columns\IconColumn::make('attachment_path')
                    ->label('Doc')
                    ->boolean(fn ($record) => $record->attachment_path),
                Tables\Columns\TextColumn::make('author.name')->label('Creato da'),
                Tables\Columns\TextColumn::make('created_at')->label('Creato il')->date('d/m/Y'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function ($data) {
                        $data['created_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Action::make('download')
                    ->label('Scarica')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn ($record) => $record->attachment_path)
                    ->action(fn (DgUserJustification $record) =>
                        Storage::download($record->attachment_path)
                    ),

                Action::make('approva')
                    ->label('Approva')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin','supervisor']))
                    ->action(fn (DgUserJustification $record) => $record->update([
                        'status' => 'approved',
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => Carbon::now(),
                    ])),

                Action::make('respingi')
                    ->label('Respingi')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin','supervisor']))
                    ->action(fn (DgUserJustification $record) => $record->update([
                        'status' => 'rejected',
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => Carbon::now(),
                    ])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
