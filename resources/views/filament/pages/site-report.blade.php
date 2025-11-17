<x-filament::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="grid flex-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-filament::card class="p-4">
                        <div class="text-xs font-semibold uppercase text-gray-500">Ore totali</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ number_format($report['summary']['total_hours'] ?? 0, 2, ',', '.') }}
                        </div>
                    </x-filament::card>
                    <x-filament::card class="p-4">
                        <div class="text-xs font-semibold uppercase text-gray-500">Straordinari</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ number_format($report['summary']['overtime_hours'] ?? 0, 2, ',', '.') }}
                        </div>
                    </x-filament::card>
                    <x-filament::card class="p-4">
                        <div class="text-xs font-semibold uppercase text-gray-500">Giorni lavorati</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $report['summary']['days_worked'] ?? 0 }}
                        </div>
                    </x-filament::card>
                    <x-filament::card class="p-4">
                        <div class="text-xs font-semibold uppercase text-gray-500">Anomalie</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $report['summary']['anomalies'] ?? 0 }}
                        </div>
                    </x-filament::card>
                </div>
                <div class="w-full max-w-2xl">
                    {{ $this->form }}
                    @if($report['site'])
                        <dl class="mt-4 grid grid-cols-2 gap-2 text-sm text-gray-600 dark:text-gray-200">
                            <div>
                                <dt class="font-semibold">Cantiere</dt>
                                <dd>{{ $report['site']->name }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold">Cliente</dt>
                                <dd>{{ $report['site']->client->name ?? '—' }}</dd>
                            </div>
                        </dl>
                    @endif
                </div>
            </div>
        </x-filament::section>

        @php($rows = $report['rows'] instanceof \Illuminate\Support\Collection ? $report['rows'] : collect($report['rows']))

        @if($rows->isEmpty())
            <x-filament::section>
                <div class="rounded-md bg-gray-100 p-4 text-center text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-200">
                    Nessun dato disponibile per i filtri selezionati.
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold uppercase tracking-wide text-gray-600">Dipendente</th>
                                <th class="px-3 py-2 text-right font-semibold uppercase tracking-wide text-gray-600">Giorni</th>
                                <th class="px-3 py-2 text-right font-semibold uppercase tracking-wide text-gray-600">Ore</th>
                                <th class="px-3 py-2 text-right font-semibold uppercase tracking-wide text-gray-600">Straordinari</th>
                                <th class="px-3 py-2 text-right font-semibold uppercase tracking-wide text-gray-600">Anomalie</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($rows as $row)
                                <tr>
                                    <td class="px-3 py-2">{{ $row['user'] }}</td>
                                    <td class="px-3 py-2 text-right">{{ $row['days'] }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format((float)$row['hours'], 2, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format((float)$row['overtime'], 2, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right">{{ $row['anomalies'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament::page>
