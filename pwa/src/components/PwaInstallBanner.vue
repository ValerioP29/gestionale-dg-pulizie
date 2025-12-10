<script setup>
import { computed, onMounted } from 'vue'
import { usePwaInstallStore } from '../stores/pwaInstall'

const installStore = usePwaInstallStore()

onMounted(() => {
  installStore.initialize()
})

const shouldShow = computed(() => installStore.showInstallBanner)
</script>

<template>
  <div
    v-if="shouldShow"
    class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
  >
    <div class="space-y-1">
      <p class="text-sm font-semibold text-slate-900">Installa l'app sul tuo telefono</p>
      <p class="text-xs text-slate-600">Aggiungi la PWA alla schermata principale per un accesso più veloce.</p>
    </div>

    <div class="flex flex-wrap gap-2 sm:justify-end">
      <button
        type="button"
        class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
        @click="installStore.triggerInstall"
        :disabled="!installStore.canInstallPwa"
      >
        Installa
      </button>
      <button
        type="button"
        class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
        @click="installStore.dismissBanner"
      >
        Chiudi
      </button>
    </div>
  </div>
</template>
