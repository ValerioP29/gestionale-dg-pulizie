document.addEventListener('alpine:init', () => {
    Alpine.data('addressAutocomplete', () => ({
        geocoder: null,

        init() {
            console.log('%c✅ addressAutocomplete caricato (solo autocomplete)', 'color:#22c55e');

            const input = this.$refs.input
            const latField = document.querySelector('input[name="latitude"]')
            const lngField = document.querySelector('input[name="longitude"]')
            const addressField = document.querySelector('input[name="address"]')

            if (!input) return

            // Inizializza Google Places
            const start = () => this.initializeAutocomplete(input, latField, lngField, addressField)
            if (typeof google === 'undefined' || !google.maps) {
                const interval = setInterval(() => {
                    if (typeof google !== 'undefined' && google.maps) {
                        clearInterval(interval)
                        start()
                    }
                }, 300)
            } else start()
        },

        initializeAutocomplete(input, latField, lngField, addressField) {
            if (!google?.maps?.places) {
                console.error('Google Maps Places non è disponibile. Controlla libraries=places.')
                return
            }

            input.addEventListener('keydown', e => {
                if (e.key === 'Enter') e.preventDefault()
            })

            const autocomplete = new google.maps.places.Autocomplete(input, {
                types: ['geocode'],
                fields: ['geometry', 'formatted_address'],
            })

            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace()
                if (!place?.geometry) return

                const lat = place.geometry.location.lat()
                const lng = place.geometry.location.lng()
                const formatted = place.formatted_address || input.value || ''

                if (latField) latField.value = lat
                if (lngField) lngField.value = lng
                if (addressField) addressField.value = formatted

                // aggiorna anche Livewire (Filament)
                if (this.$wire?.set) {
                    this.$wire.set('data.latitude', lat, true)
                    this.$wire.set('data.longitude', lng, true)
                    this.$wire.set('data.address', formatted, true)
                }

                console.log('%c📍 Nuovo indirizzo selezionato:', 'color:#3b82f6', formatted)
            })
        },
    }))
})
