<div x-data="addressAutocomplete()" class="space-y-2">
    <label for="autocomplete" class="address-autocomplete-label block text-sm font-medium">
        Indirizzo
    </label>

    <input
        id="autocomplete"
        type="text"
        class="address-autocomplete-input filament-forms-input block w-full shadow-sm focus:border-primary-500 focus:ring-primary-500"
        placeholder="Inserisci un indirizzo"
        x-ref="input"
    />
</div>

{{-- Carica script Google Maps --}}
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places">
</script>

@push('styles')
<style>
    .address-autocomplete-label {
        color: white !important;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .address-autocomplete-input {
        background-color: white !important;
        color: black !important;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.6rem 0.75rem;
        font-size: 0.95rem;
    }

    .pac-container {
        z-index: 999999 !important;
    }

    .filament-main, .fi-main, .fi-body, .fi-simple-layout {
        overflow: visible;
    }
</style>
@endpush
