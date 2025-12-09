<script setup>
import { computed, onMounted } from 'vue'
import { useSessionStore } from '../../../stores/session'
import PunchButton from '../components/PunchButton.vue'

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

      <PunchButton />
    </div>
  </section>
</template>
