<script setup>
import { computed, onMounted } from 'vue'
import { useSessionStore } from '../../../stores/session'
import { useConnectivityStore } from '../../../stores/connectivity'
import PunchButton from '../components/PunchButton.vue'

const sessionStore = useSessionStore()
const connectivityStore = useConnectivityStore()

onMounted(() => {
  sessionStore.loadCurrent()
  sessionStore.setupOfflineSync()
  sessionStore.flushOfflinePunches()
})

const assignedSiteName = computed(
  () => sessionStore.assignedSite?.name || 'Nessun cantiere assegnato'
)

const sessionStatus = computed(() =>
  sessionStore.activeSession ? 'Sessione attiva' : 'Nessuna sessione attiva'
)

const isActiveSession = computed(() => Boolean(sessionStore.activeSession))

const statusLabel = computed(() => (isActiveSession.value ? 'In servizio' : 'Fuori servizio'))

const statusPillClasses = computed(() =>
  isActiveSession.value
    ? 'bg-green-50 text-green-700 ring-1 ring-green-100'
    : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'
)

const statusDotClasses = computed(() =>
  isActiveSession.value ? 'bg-green-500 shadow-[0_0_0_2px_rgba(74,222,128,0.3)]' : 'bg-slate-400'
)

const checkInTime = computed(() => {
  const checkIn = sessionStore.activeSession?.check_in

  if (!checkIn) return ''

  const parsed = new Date(checkIn)

  if (Number.isNaN(parsed.getTime())) return ''

  return parsed.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
})

const connectivityLabel = computed(() =>
  connectivityStore.isOnline
    ? 'Online'
    : 'Offline – le timbrature verranno sincronizzate'
)

const connectivityPillClasses = computed(() =>
  connectivityStore.isOnline
    ? 'bg-green-50 text-green-700 ring-1 ring-green-100'
    : 'bg-amber-50 text-amber-700 ring-1 ring-amber-100'
)
</script>

<template>
  <section class="space-y-6">
    <p class="text-sm text-slate-600">Benvenuto nella PWA di DG Pulizie.</p>

    <div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
      <span
        class="inline-flex items-center gap-2 rounded-full px-3 py-1"
        :class="connectivityPillClasses"
      >
        <span class="h-2.5 w-2.5 rounded-full" :class="{ 'bg-green-500': connectivityStore.isOnline, 'bg-amber-500': !connectivityStore.isOnline }"></span>
        {{ connectivityLabel }}
      </span>
    </div>

    <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="space-y-2">
        <p class="text-sm text-slate-500">Stato sessione</p>

        <div class="flex flex-wrap items-center gap-3">
          <span class="h-2.5 w-2.5 rounded-full" :class="statusDotClasses"></span>
          <span
            class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold"
            :class="statusPillClasses"
          >
            {{ statusLabel }}
          </span>
        </div>

        <p class="text-base font-semibold text-slate-900">{{ sessionStatus }}</p>
        <p v-if="checkInTime" class="text-xs text-slate-500">
          Entrato alle <span class="font-semibold text-slate-800">{{ checkInTime }}</span>
        </p>
      </div>

      <div class="rounded-xl bg-slate-50 px-4 py-3">
        <p class="text-sm text-slate-500">Cantiere assegnato</p>
        <p class="text-lg font-semibold text-slate-900">{{ assignedSiteName }}</p>
      </div>

      <div class="pt-2">
        <PunchButton />
      </div>
    </div>
  </section>
</template>
