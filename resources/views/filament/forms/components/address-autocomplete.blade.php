<div
    x-data="addressAutocomplete"
    x-init="init()"
    class="space-y-2"
>
    <label class="block text-sm font-medium">Indirizzo</label>

    <input
        type="text"
        x-ref="input"
        class="address-autocomplete-input"
        placeholder="Inserisci un indirizzo"
        @input="@this.set('data.address', $event.target.value)"
    />
</div>

@once
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places"></script>

<script>
    function addressAutocomplete() {
        return {
            init() {
                const input = this.$refs.input;

                let wait = setInterval(() => {
                    if (window.google && google.maps && google.maps.places) {
                        clearInterval(wait);
                        this.startAutocomplete(input);
                    }
                }, 200);
            },

            startAutocomplete(input) {
                const autocomplete = new google.maps.places.Autocomplete(input, { types: ['address'] });

                autocomplete.addListener('place_changed', () => {
                    const place = autocomplete.getPlace();
                    if (!place || !place.geometry) return;

                    const address = place.formatted_address;
                    const lat = place.geometry.location.lat();
                    const lng = place.geometry.location.lng();

                    @this.set('data.address', address);
                    @this.set('data.latitude', lat);
                    @this.set('data.longitude', lng);
                });
            }
        }
    }
</script>

@push('styles')
        <style>
            .address-autocomplete-input {
                background-color: var(--filament-input-bg, #ffffff) !important;
                color: var(--filament-input-color, #111827) !important;
                border-radius: 0.5rem;
                padding: 0.6rem 0.75rem;
                font-size: 0.95rem;
                border: 1px solid var(--filament-input-border-color, #d1d5db) !important;
            }

            .address-autocomplete-input::placeholder {
                color: var(--filament-input-placeholder-color, #6b7280) !important;
                opacity: 1;
            }

            .dark .address-autocomplete-input {
                background-color: #1f2937 !important;
                color: #f9fafb !important;
                border-color: #4b5563 !important;
            }

            .dark .address-autocomplete-input::placeholder {
                color: #9ca3af !important;
            }

            .pac-container {
                z-index: 999999 !important;
            }
        </style>
    @endpush
@endonce
