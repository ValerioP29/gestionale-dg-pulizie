<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            {{-- Form dei filtri --}}
            <form wire:submit.prevent="updateChart" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{ $this->form }}
                <div class="col-span-full">
                    <x-filament::button type="submit" color="primary" class="mt-2">
                        Aggiorna grafico
                    </x-filament::button>
                </div>
            </form>


            {{-- Grafico --}}
            <div
                wire:ignore
                class="h-80"
                x-data="{
                    chart: null,
                    type: null,
                    options: null,
                    _updateTimer: null,

                    init() {
                        // Carica Chart.js se manca
                        if (typeof window.Chart === 'undefined') {
                            const script = document.createElement('script')
                            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'
                            script.onload = () => this.renderInitial()
                            document.head.appendChild(script)
                        } else {
                            this.renderInitial()
                        }

                        // Ascolta aggiornamenti dai filtri Livewire
                        Livewire.on('updateChartData', payload => {
                            // Alcune versioni inviano { data: {...} }, altre direttamente {...}
                            const base = payload?.data ?? payload
                            if (!base || !Array.isArray(base.datasets)) {
                                console.warn('⚠️ Dati grafico non validi:', payload)
                                return
                            }

                            // Deep clone per rimuovere qualsiasi Proxy/reattività
                            const clean = JSON.parse(JSON.stringify(base))

                            // Debounce: evita raffiche di ricostruzioni
                            clearTimeout(this._updateTimer)
                            this._updateTimer = setTimeout(() => {
                                this.rebuild(clean)
                            }, 60)
                        })
                    },

                    renderInitial() {
                        const cfg = @js($this->getChartData()) // { type, data, options }
                        this.type = cfg.type || 'line'
                        this.options = cfg.options || {}

                        const ctx = this.$refs.canvas.getContext('2d')

                        // Deep clone anche dell’iniziale per sicurezza
                        const initialData = JSON.parse(JSON.stringify(cfg.data || { labels: [], datasets: [] }))

                        this.chart = new window.Chart(ctx, {
                            type: this.type,
                            data: initialData,
                            options: this.options,
                        })
                    },

                    rebuild(newData) {
                        if (!window.Chart) return

                        // Distruggi e ricrea per evitare loop interni di Chart.js con proxies
                        try { this.chart?.destroy() } catch (_) {}

                        const ctx = this.$refs.canvas.getContext('2d')

                        this.chart = new window.Chart(ctx, {
                            type: this.type || 'line',
                            data: newData,                 // già deep-cloned
                            options: this.options || {},   // opzioni stabili
                        })
                    },
                }"
            >
                <canvas x-ref="canvas" class="w-full h-full"></canvas>
            </div>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>
