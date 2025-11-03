<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use App\Models\DgWorkSession;
use App\Models\DgAnomaly;
use App\Models\DgJobTitle;
use Carbon\Carbon;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function form(Forms\Form $form): Forms\Form
    {
        $user = $this->record;

        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();

        $workedHours = round(
            DgWorkSession::where('user_id', $user->id)
                ->whereBetween('session_date', [$start, $end])
                ->sum('worked_minutes') / 60,
            2
        );

        $overtimeHours = round(
            DgAnomaly::where('user_id', $user->id)
                ->where('type', 'overtime')
                ->whereBetween('date', [$start, $end])
                ->sum('minutes') / 60,
            2
        );

        $anomaliesCount =
            DgAnomaly::where('user_id', $user->id)
                ->whereBetween('date', [$start, $end])
                ->count();

        return $form->schema([
            Forms\Components\Tabs::make('UserTabs')
                ->columnSpanFull()
                ->tabs([

                    /** DATI UTENTE */
                    Forms\Components\Tabs\Tab::make('Dati Utente')
                        ->schema(UserResource::getFormSchema()),

                    /** RIEPILOGO */
                    Forms\Components\Tabs\Tab::make('Riepilogo Mensile')
                        ->schema([
                            Forms\Components\Placeholder::make('worked_hours')
                                ->label('Ore lavorate mese')
                                ->content("$workedHours h"),

                            Forms\Components\Placeholder::make('overtime')
                                ->label('Straordinari mese')
                                ->content("$overtimeHours h"),

                            Forms\Components\Placeholder::make('anomalies')
                                ->label('Anomalie totali mese')
                                ->content($anomaliesCount),
                        ])
                        ->columns(2),

                    /** MANSIONE */
                    Forms\Components\Tabs\Tab::make('Mansione')
                        ->schema([
                            Forms\Components\Select::make('job_title_id')
                                ->label('Mansione')
                                ->options(
                                    DgJobTitle::where('active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                )
                                ->searchable()
                                ->placeholder('Seleziona mansione')
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')->required(),
                                    Forms\Components\TextInput::make('code')->label('Codice')->maxLength(50),
                                    Forms\Components\Textarea::make('notes')->label('Note'),
                                    Forms\Components\Toggle::make('active')->default(true),
                                ]),
                        ]),

                    /** CONTRATTO (direttamente sull’utente) */
                    Forms\Components\Tabs\Tab::make('Contratto Orario')
                        ->schema([
                            Forms\Components\Grid::make(7)->schema([
                                Forms\Components\TextInput::make('mon')->label('Lunedì')->numeric()->step(0.5),
                                Forms\Components\TextInput::make('tue')->label('Martedì')->numeric()->step(0.5),
                                Forms\Components\TextInput::make('wed')->label('Mercoledì')->numeric()->step(0.5),
                                Forms\Components\TextInput::make('thu')->label('Giovedì')->numeric()->step(0.5),
                                Forms\Components\TextInput::make('fri')->label('Venerdì')->numeric()->step(0.5),
                                Forms\Components\TextInput::make('sat')->label('Sabato')->numeric()->step(0.5),
                                Forms\Components\TextInput::make('sun')->label('Domenica')->numeric()->step(0.5),
                            ]),

                            Forms\Components\Placeholder::make('tot_month')
                                ->label('Totale mensile stimato')
                                ->content(fn ($record) =>
                                    $record && $record->contract_hours_monthly
                                        ? $record->contract_hours_monthly.' h'
                                        : '—'
                                ),

                            Forms\Components\KeyValue::make('rules')
                                ->label('Regole avanzate (opzionali)')
                                ->helperText('Esempio: {"start":"08:00","end":"12:00","break":0}')
                                ->nullable(),
                        ]),
                ]),
        ]);
    }
}
