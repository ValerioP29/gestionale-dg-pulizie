<x-filament::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="w-full max-w-md">
                    {{ $this->form }}
                </div>

                <div class="grid flex-1 gap-3 sm:grid-cols-3">
                    <x-filament::card class="p-4">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Ore totali lavorate</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ number_format($summary['worked_hours'] ?? 0, 2, ',', '.') }}
                        </div>
                    </x-filament::card>

                    <x-filament::card class="p-4">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Assenze totali</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ $summary['absences'] ?? 0 }}
                        </div>
                    </x-filament::card>

                    <x-filament::card class="p-4">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Straordinari totali (h)</div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ number_format($summary['overtime_hours'] ?? 0, 2, ',', '.') }}
                        </div>
                    </x-filament::card>
                </div>
            </div>
        </x-filament::section>

        @unless($hasData)
            <x-filament::section>
                <x-filament::alert color="warning">
                    Nessun dato disponibile per il periodo selezionato.
                </x-filament::alert>
            </x-filament::section>
        @endunless

        <x-filament::section>
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament::page>
