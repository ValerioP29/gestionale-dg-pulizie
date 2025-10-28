<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DgPayslipResource\Pages;
use App\Models\DgPayslip;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Actions\Action;
use Carbon\Carbon;

class DgPayslipResource extends Resource
{
    protected static ?string $model = DgPayslip::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Documenti';
    protected static ?string $modelLabel = 'Busta paga';
    protected static ?string $pluralModelLabel = 'Buste paga';
    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Dipendente')
                ->options(User::orderBy('last_name')->pluck('last_name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('period_year')
                ->label('Anno')
                ->numeric()
                ->default(today()->year)
                ->required(),

            Forms\Components\Select::make('period_month')
                ->label('Mese')
                ->options(array_combine(range(1,12), range(1,12)))
                ->default(today()->month)
                ->required(),

            Forms\Components\FileUpload::make('file')
                ->label('Documento PDF')
                ->required()
                ->acceptedFileTypes(['application/pdf'])
                ->directory('payslips')
                ->storeFileNamesIn('file_name')
                ->getUploadedFileNameForStorageUsing(fn ($file) => $file->getClientOriginalName())
                ->dehydrated(false)
                ->helperText('Carica PDF della busta paga'),

            Forms\Components\Toggle::make('visible_to_employee')
                ->label('Visibile al dipendente')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Dipendente')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('period_label')
                    ->label('Periodo')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Caricata il')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('visible_to_employee')
                    ->label('Visibile')
                    ->boolean(),

                Tables\Columns\TextColumn::make('downloads_count')
                    ->label('Download')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('period_year')
                    ->label('Anno')
                    ->options(
                        DgPayslip::query()
                            ->select('period_year')
                            ->distinct()
                            ->orderBy('period_year', 'desc')
                            ->pluck('period_year', 'period_year')
                    ),

                Tables\Filters\SelectFilter::make('period_month')
                    ->label('Mese')
                    ->options(array_combine(range(1,12), range(1,12))),
            ])
            ->actions([
                Action::make('download')
                    ->label('Scarica')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (DgPayslip $record) {
                        $record->increment('downloads_count');
                        $record->downloaded_at = Carbon::now();
                        $record->save();

                        return Storage::disk($record->storage_disk)->download($record->file_path, $record->file_name);
                    }),

                Tables\Actions\EditAction::make(),

                Action::make('toggle_visibility')
                    ->label(fn ($record) => $record->visible_to_employee ? 'Nascondi' : 'Rendi visibile')
                    ->icon('heroicon-o-eye-slash')
                    ->requiresConfirmation()
                    ->action(fn (DgPayslip $record) => $record->update([
                        'visible_to_employee' => !$record->visible_to_employee
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDgPayslips::route('/'),
            'create' => Pages\CreateDgPayslip::route('/create'),
            'edit' => Pages\EditDgPayslip::route('/{record}/edit'),
        ];
    }
}
