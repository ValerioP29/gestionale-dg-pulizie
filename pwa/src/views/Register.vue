<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  codice_fiscale: '',
  matricola: '',
  privacy_accepted: false,
  location_consent: false,
})

const errorMessage = ref('')
const isSubmitting = ref(false)

const router = useRouter()
const authStore = useAuthStore()

function isValidEmail(value) {
  return /.+@.+\..+/.test(value)
}

function isValidCodiceFiscale(value) {
  const cf = value?.trim().toUpperCase()
  return cf && cf.length === 16 && /^[A-Z0-9]{16}$/.test(cf)
}

function validateForm() {
  if (!form.first_name.trim()) {
    return 'Il nome è obbligatorio.'
  }
  if (!form.last_name.trim()) {
    return 'Il cognome è obbligatorio.'
  }
  if (!form.email.trim() || !isValidEmail(form.email)) {
    return "Inserisci un'email valida."
  }
  if (!isValidCodiceFiscale(form.codice_fiscale)) {
    return 'Inserisci un codice fiscale valido (16 caratteri alfanumerici).'
  }
  if (!form.privacy_accepted) {
    return 'Devi accettare la privacy per procedere.'
  }

  return ''
}

async function onSubmit() {
  errorMessage.value = ''

  const validationError = validateForm()
  if (validationError) {
    errorMessage.value = validationError
    return
  }

  isSubmitting.value = true
  try {
    await authStore.registerEmployee({
      ...form,
      codice_fiscale: form.codice_fiscale.trim().toUpperCase(),
    })
    router.push('/home')
  } catch (error) {
    errorMessage.value = error?.message || 'Registrazione non riuscita. Riprova.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section class="space-y-6">
    <header class="space-y-2 text-center">
      <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">DG Pulizie</p>
      <h1 class="text-2xl font-bold text-slate-900">Registrazione</h1>
      <p class="text-sm text-slate-500">Crea il tuo account dipendente</p>
    </header>

    <form class="space-y-4" @submit.prevent="onSubmit">
      <div v-if="errorMessage" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        {{ errorMessage }}
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="space-y-2">
          <label class="block text-sm font-medium text-slate-700" for="first_name">Nome</label>
          <input
            id="first_name"
            v-model="form.first_name"
            type="text"
            class="w-full rounded-lg border border-slate-200 bg-white p-3 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            placeholder="Mario"
            required
          />
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-slate-700" for="last_name">Cognome</label>
          <input
            id="last_name"
            v-model="form.last_name"
            type="text"
            class="w-full rounded-lg border border-slate-200 bg-white p-3 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            placeholder="Rossi"
            required
          />
        </div>
      </div>

      <div class="space-y-2">
        <label class="block text-sm font-medium text-slate-700" for="email">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          class="w-full rounded-lg border border-slate-200 bg-white p-3 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
          placeholder="email@example.com"
          required
        />
      </div>

      <div class="space-y-2">
        <label class="block text-sm font-medium text-slate-700" for="codice_fiscale">Codice fiscale</label>
        <input
          id="codice_fiscale"
          v-model="form.codice_fiscale"
          type="text"
          class="w-full rounded-lg border border-slate-200 bg-white p-3 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
          placeholder="RSSMRA80A01H501U"
          maxlength="16"
          required
        />
        <p class="text-xs text-slate-500">Inserisci 16 caratteri alfanumerici.</p>
      </div>

      <div class="space-y-2">
        <label class="block text-sm font-medium text-slate-700" for="matricola">Matricola (opzionale)</label>
        <input
          id="matricola"
          v-model="form.matricola"
          type="text"
          class="w-full rounded-lg border border-slate-200 bg-white p-3 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
          placeholder="12345"
        />
      </div>

      <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
        <label class="flex items-start gap-3 text-sm text-slate-700">
          <input v-model="form.privacy_accepted" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" required />
          <span>Ho letto e accetto l'informativa privacy obbligatoria.</span>
        </label>
        <label class="flex items-start gap-3 text-sm text-slate-700">
          <input
            v-model="form.location_consent"
            type="checkbox"
            class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
          />
          <span>Acconsento all'utilizzo della posizione per la timbratura (opzionale).</span>
        </label>
      </div>

      <button
        type="submit"
        class="w-full rounded-lg bg-blue-600 p-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:cursor-not-allowed disabled:bg-blue-300"
        :disabled="isSubmitting"
      >
        {{ isSubmitting ? 'Registrazione in corso...' : 'Registrati' }}
      </button>

      <p class="text-center text-sm text-slate-600">
        Hai già un account?
        <RouterLink to="/login" class="font-semibold text-blue-600 hover:underline">Accedi</RouterLink>
      </p>
    </form>
  </section>
</template>
