<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../../../stores/auth'

const email = ref('')
const password = ref('')
const authStore = useAuthStore()

function onSubmit() {
  authStore.setToken('demo-token')
}
</script>

<template>
  <section class="space-y-6">
    <header class="text-center space-y-2">
      <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">DG Pulizie</p>
      <h1 class="text-2xl font-bold text-slate-900">Accesso</h1>
      <p class="text-sm text-slate-500">Placeholder form di login</p>
    </header>

    <form class="space-y-4" @submit.prevent="onSubmit">
      <div class="space-y-2">
        <label class="block text-sm font-medium text-slate-700" for="email">Email</label>
        <input
          id="email"
          v-model="email"
          type="email"
          class="w-full rounded-lg border border-slate-200 bg-white p-3 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
          placeholder="email@example.com"
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
        />
      </div>

      <button
        type="submit"
        class="w-full rounded-lg bg-blue-600 p-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
      >
        Accedi
      </button>
    </form>
  </section>
</template>