<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgUserJustificationResource\Pages;
use App\Models\DgUserJustification;
use App\Models\DgJustificationType;
use App\Models\DgAnomaly;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DgUserJustificationResource extends Resource
{
    protected static ?string $model = DgUserJustification::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Gestione Presenze';
    protected static ?string $modelLabel = 'Giustificazione';
    protected static ?string $pluralModelLabel = 'Giustificazioni';
    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('anomaly_id')
                ->label('Anomalia')
                ->options(
                    DgAnomaly::orderByDesc('date')
                        ->get()
                        ->mapWithKeys(fn ($a) => [$a->id => "{$a->date->format('d/m')} - {$a->type}"])
                )
                ->searchable()
                ->required(),

            Forms\Components\Select::make('type_id')
                ->label('Tipo giustificazione')
                ->options(DgJustificationType::orderBy('label')->pluck('label', 'id'))
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $type = DgJustificationType::find($state);
                    if ($type && !$type->requires_doc) {
                        $set('attachment_path', null);
                    }
                })
                ->required(),

            Forms\Components\Textarea::make('note')
                ->label('Note')
                ->rows(3),

            Forms\Components\FileUpload::make('attachment_path')
                ->label('Documento allegato')
                ->directory('justifications')
                ->acceptedFileTypes(['application/pdf','image/*'])
                ->maxSize(4096)
                ->downloadable()
                ->visible(fn ($get) => optional(DgJustificationType::find($get('type_id')))->requires_doc),

            Forms\Components\Select::make('status')
                ->label('Stato')
                ->options([
                    'open' => 'Aperta',
                    'approved' => 'Approvata',
                    'rejected' => 'Respinta',
                ])
                ->default('open')
                ->disabled(fn () => !auth()->user()->hasAnyRole(['admin','supervisor'])),

            Forms\Components\TextInput::make('reviewed_by')
                ->label('Revisionata da')
                ->disabled()
                ->dehydrated(false),

            Forms\Components\DateTimePicker::make('reviewed_at')
                ->label('Data revisione')
                ->disabled()
                ->dehydrated(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('anomaly.type')->label('Tipo anomalia')->sortable(),
                Tables\Columns\TextColumn::make('anomaly.date')->label('Data')->date()->sortable(),
                Tables\Columns\TextColumn::make('type.label')->label('Tipo giustificazione')->sortable(),
                Tables\Columns\TextColumn::make('author.name')->label('Inserita da')->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Stato')
                    ->colors([
                        'warning' => 'open',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ]),
                Tables\Columns\IconColumn::make('attachment_path')
                    ->label('Doc')
                    ->boolean(fn ($record) => !empty($record->attachment_path)),
                Tables\Columns\TextColumn::make('created_at')->label('Creata il')->date('d/m/Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Stato')
                    ->options([
                        'open' => 'Aperte',
                        'approved' => 'Approvate',
                        'rejected' => 'Respinte',
                    ]),
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
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDgUserJustifications::route('/'),
            'create' => Pages\CreateDgUserJustification::route('/create'),
            'edit'   => Pages\EditDgUserJustification::route('/{record}/edit'),
        ];
    }
}
