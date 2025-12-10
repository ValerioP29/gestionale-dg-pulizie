<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../../stores/auth'

const email = ref('')
const password = ref('')
const errorMessage = ref('')
const isSubmitting = ref(false)

const router = useRouter()
const authStore = useAuthStore()

async function onSubmit() {
  errorMessage.value = ''
  isSubmitting.value = true
  const success = await authStore.login(email.value, password.value)
  isSubmitting.value = false

  if (success) {
    router.push('/home')
  } else {
    errorMessage.value = 'Credenziali non valide. Controlla email e password.'
  }
}
</script>

<template>
  <section class="space-y-6">
    <header class="space-y-2 text-center">
      <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">DG Pulizie</p>
      <h1 class="text-2xl font-bold text-slate-900">Accesso</h1>
      <p class="text-sm text-slate-500">Accedi con le tue credenziali</p>
    </header>

    <form class="space-y-4" @submit.prevent="onSubmit">
      <div v-if="errorMessage" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        {{ errorMessage }}
      </div>

      <div class="space-y-2">
        <label class="block text-sm font-medium text-slate-700" for="email">Email</label>
        <input
          id="email"
          v-model="email"
          type="email"
          class="w-full rounded-lg border border-slate-200 bg-white p-3 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
          placeholder="email@example.com"
          required
        />
      </div>

      <div class="space-y-2">
        <label class="block text-sm font-medium text-slate-700" for="password">Password</label>
        <input
          id="password"
          v-model="password"
          type="password"
          class="w-full rounded-lg border border-slate-200 bg-white p-3 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
          placeholder="••••••••"
          required
        />
      </div>

      <button
        type="submit"
        class="w-full rounded-lg bg-blue-600 p-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:cursor-not-allowed disabled:bg-blue-300"
        :disabled="isSubmitting"
      >
        {{ isSubmitting ? 'Accesso in corso...' : 'Accedi' }}
      </button>

      <p class="text-center text-sm text-slate-600">
        Non hai un account?
        <RouterLink to="/register" class="font-semibold text-blue-600 hover:underline">Registrati</RouterLink>
      </p>
    </form>
  </section>
</template>
