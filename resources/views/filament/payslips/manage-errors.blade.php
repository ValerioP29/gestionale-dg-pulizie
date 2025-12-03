<div class="space-y-4">

    @if($errors->isEmpty())
        <p class="text-gray-600">Nessun errore rilevato.</p>
    @else
        <table class="min-w-full text-sm border border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 border">Nome file</th>
                    <th class="p-2 border">Correggi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($errors as $e)
                    <tr>
                        <td class="p-2 border">{{ $e->file_name }}</td>
                        <td class="p-2 border text-center">
                            @php
                                $modalId = 'fix-error-'.$e->id;
                            @endphp

                            <button
                                wire:click="prepareFixError({{ $e->id }})"
                                class="px-2 py-1 bg-blue-600 text-white rounded">
                                Correggi
                            </button>

                            <x-filament::modal id="{{ $modalId }}" width="md">
                                <x-slot name="title">
                                    Correggi {{ $e->file_name }}
                                </x-slot>

                                <form wire:submit="fixError" class="space-y-3">

                                    {{ $this->form }}

                                    <div class="flex justify-end">
                                        <x-filament::button type="submit" color="success">
                                            Salva
                                        </x-filament::button>
                                    </div>

                                </form>

                            </x-filament::modal>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</div>
