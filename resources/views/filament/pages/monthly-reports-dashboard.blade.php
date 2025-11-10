<x-filament::page>
    <div class="space-y-6">
        <div class="w-full max-w-md">
            {{ $this->form }}
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-filament::card>
                <div class="text-sm font-medium text-gray-500">Ore totali lavorate</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ number_format($summary['worked_hours'] ?? 0, 2, ',', '.') }}
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm font-medium text-gray-500">Assenze totali</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ $summary['absences'] ?? 0 }}
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm font-medium text-gray-500">Straordinari totali (h)</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ number_format($summary['overtime_hours'] ?? 0, 2, ',', '.') }}
                </div>
            </x-filament::card>
        </div>

        {{ $this->table }}
    </div>
</x-filament::page>
