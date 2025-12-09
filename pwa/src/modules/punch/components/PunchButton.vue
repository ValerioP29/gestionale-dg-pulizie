<script setup>
import { computed, nextTick, ref } from 'vue'
import { useSessionStore } from '../../../stores/session'
import { getStablePosition } from '../../../utils/geo'
import { showError, showSuccess, showWarning } from '../../../utils/toast'

const sessionStore = useSessionStore()

const loadingStage = ref('')
const errorMessage = ref('')
const warningMessages = ref([])

const buttonLabel = computed(() => (sessionStore.activeSession ? 'ESCI' : 'ENTRA'))
const punchType = computed(() => (sessionStore.activeSession ? 'out' : 'in'))
const loading = computed(() => loadingStage.value !== '')
const buttonText = computed(() => (loading.value ? loadingStage.value : buttonLabel.value))

function translateWarning(code) {
  if (code === 'outside_site') {
    return 'Fuori cantiere'
  }

  return code
}

async function handlePunch() {
  errorMessage.value = ''
  warningMessages.value = []
  loadingStage.value = 'Attivazione GPS...'

  try {
    await nextTick()
    loadingStage.value = 'Localizzazione in corso...'
    const coords = await getStablePosition()

    if (coords.accuracy > 100) {
      showWarning('Segnale GPS debole, prova a spostarti vicino all’ingresso.')
    }

    loadingStage.value = 'Registrazione timbratura...'
    const result = await sessionStore.punch(punchType.value, coords)

    if (!result?.success) {
      const message = 'Errore durante la timbratura.'
      errorMessage.value = message
      showError(message)
      return
    }

    warningMessages.value = (result.warnings || []).map(translateWarning)
    await sessionStore.loadCurrent()

    const successMessage = punchType.value === 'in' ? 'Timbratura registrata' : 'Uscita registrata'
    showSuccess(successMessage)
  } catch (error) {
    if (error?.message === 'NO_POSITION') {
      errorMessage.value = 'Impossibile ottenere una posizione valida.'
      showError(errorMessage.value)
    } else if (error?.code === 1) {
      const isIOS = /iPad|iPhone|iPod/i.test(navigator.userAgent)
      const message = isIOS
        ? 'Vai su Impostazioni > Privacy > Localizzazione e abilita la posizione.'
        : 'Vai su Impostazioni > App > Autorizzazioni > Posizione.'
      errorMessage.value = message
      showError(message)
    } else {
      errorMessage.value = 'Impossibile ottenere la posizione.'
      showError(errorMessage.value)
    }
  } finally {
    loadingStage.value = ''
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
      <span>{{ buttonText }}</span>
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
