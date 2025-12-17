<x-filament::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="grid gap-6 lg:grid-cols-[minmax(220px,260px)_1fr] lg:items-start">
                <div class="w-full max-w-xs">
                    {{ $this->form }}
                </div>

                <div class="grid flex-1 min-w-0 gap-3 sm:grid-cols-3">
                    <x-filament::card class="p-4">
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Ore totali lavorate</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ number_format($summary['worked_hours'] ?? 0, 2, ',', '.') }}
                        </div>
                    </x-filament::card>

                    <x-filament::card class="p-4">
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Assenze totali</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ $summary['absences'] ?? 0 }}
                        </div>
                    </x-filament::card>

                    <x-filament::card class="p-4">
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Straordinari totali</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ \App\Models\DgWorkSession::formatMinutesHuman($summary['overtime_minutes'] ?? 0) }}
                        </div>
                    </x-filament::card>
                </div>
            </div>
        </x-filament::section>

        @unless($hasData)
            <x-filament::section>
                <div class="p-3 w-full rounded-md bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200">
                    Nessun dato disponibile per il periodo selezionato.
                </div>
            </x-filament::section>
        @endunless

        <x-filament::section>
            <div class="overflow-x-auto">
                {{ $this->table }}
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
