<script setup>
import { computed, ref } from 'vue'
import { useSessionStore } from '../../../stores/session'
import { getCurrentPosition } from '../../../utils/geo'

const sessionStore = useSessionStore()

const loading = ref(false)
const errorMessage = ref('')
const warningMessages = ref([])

const buttonLabel = computed(() => (sessionStore.activeSession ? 'ESCI' : 'ENTRA'))
const punchType = computed(() => (sessionStore.activeSession ? 'out' : 'in'))

function translateWarning(code) {
  if (code === 'outside_site') {
    return 'Fuori cantiere'
  }

  return code
}

async function handlePunch() {
  errorMessage.value = ''
  warningMessages.value = []
  loading.value = true

  try {
    const coords = await getCurrentPosition()
    const result = await sessionStore.punch(punchType.value, coords)

    if (!result.success) {
      errorMessage.value = 'Errore durante la timbratura.'
      return
    }

    warningMessages.value = (result.warnings || []).map(translateWarning)
    await sessionStore.loadCurrent()
  } catch (error) {
    if (error?.code === 1) {
      errorMessage.value = 'Permesso di geolocalizzazione negato.'
    } else {
      errorMessage.value = 'Impossibile ottenere la posizione.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="space-y-3">
    <button
      type="button"
      class="flex w-full items-center justify-center rounded-lg bg-blue-600 p-4 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:cursor-not-allowed disabled:opacity-70"
      :disabled="loading"
      @click="handlePunch"
    >
      <span v-if="loading">Attendere...</span>
      <span v-else>{{ buttonLabel }}</span>
    </button>

    <div v-if="errorMessage" class="rounded-lg border border-red-100 bg-red-50 p-3 text-sm text-red-700">
      {{ errorMessage }}
    </div>

    <div v-if="warningMessages.length" class="rounded-lg border border-amber-100 bg-amber-50 p-3 text-sm text-amber-700">
      <p class="font-semibold">Attenzione</p>
      <ul class="list-inside list-disc space-y-1">
        <li v-for="warning in warningMessages" :key="warning">{{ warning }}</li>
      </ul>
    </div>
  </div>
</template>
