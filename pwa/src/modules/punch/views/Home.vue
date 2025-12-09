<script setup>
import { computed, onMounted } from 'vue'
import { useSessionStore } from '../../../stores/session'

const sessionStore = useSessionStore()

onMounted(() => {
  sessionStore.loadCurrent()
})

const assignedSiteName = computed(
  () => sessionStore.assignedSite?.name || 'Nessun cantiere assegnato'
)

const sessionStatus = computed(() =>
  sessionStore.activeSession ? 'Sessione attiva' : 'Nessuna sessione attiva'
)
</script>

<template>
  <section class="space-y-6">
    <header class="space-y-2">
      <h1 class="text-2xl font-bold text-slate-900">Home</h1>
      <p class="text-slate-600">Benvenuto nella PWA di DG Pulizie.</p>
    </header>

    <div class="space-y-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div>
        <p class="text-sm text-slate-500">Cantiere assegnato</p>
        <p class="text-lg font-semibold text-slate-900">{{ assignedSiteName }}</p>
      </div>

      <div>
        <p class="text-sm text-slate-500">Stato sessione</p>
        <p class="text-lg font-semibold text-slate-900">{{ sessionStatus }}</p>
      </div>

      <button
        type="button"
        class="w-full rounded-lg bg-blue-600 p-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
      >
        Timbratura (disponibile nel prossimo step)
      </button>
    </div>
  </section>
</template>
