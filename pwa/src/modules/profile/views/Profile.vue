<script setup>
import { onMounted, ref } from 'vue'
import { useAuthStore } from '../../../stores/auth'
import { apiGet } from '../../../http'
import { ENDPOINTS } from '../../../endpoints'

const authStore = useAuthStore()
const loading = ref(true)
const profile = ref(null)
const error = ref('')

onMounted(async () => {
  try {
    const response = await apiGet(ENDPOINTS.me)

    if (!response.ok) {
      throw new Error('Impossibile caricare il profilo utente')
    }

    const json = await response.json()
    profile.value = json.data

  } catch (err) {
    error.value = err.message || 'Errore sconosciuto'
  } finally {
    loading.value = false
  }
})

const handleLogout = async () => {
  await authStore.logout()
}
</script>

<template>
  <section class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <h2 class="text-lg font-semibold text-slate-900">Il tuo profilo</h2>
      <p class="text-sm text-slate-600">Informazioni personali recuperate dal tuo account.</p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div v-if="loading" class="text-sm text-slate-600">Caricamento in corso...</div>
      <div v-else-if="error" class="text-sm text-red-600">{{ error }}</div>

      <dl v-else class="space-y-3 text-sm text-slate-700">
        <div class="grid grid-cols-3 gap-2">
          <dt class="font-semibold text-slate-900">Nome</dt>
          <dd class="col-span-2">{{ profile?.first_name || '-' }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-2">
          <dt class="font-semibold text-slate-900">Cognome</dt>
          <dd class="col-span-2">{{ profile?.last_name || '-' }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-2">
          <dt class="font-semibold text-slate-900">Email</dt>
          <dd class="col-span-2">{{ profile?.email || '-' }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-2">
          <dt class="font-semibold text-slate-900">Cantiere principale</dt>
          <dd class="col-span-2">{{ profile?.main_site_name || '-' }}</dd>
        </div>
      </dl>
    </div>

    <div class="flex justify-end">
      <button
        type="button"
        class="rounded-lg bg-red-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
        @click="handleLogout"
      >
        Logout
      </button>
    </div>
  </section>
</template>
