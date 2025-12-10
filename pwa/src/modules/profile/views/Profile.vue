<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '../../../stores/auth'

const authStore = useAuthStore()
const loading = ref(true)
const error = ref('')

const profile = computed(() => authStore.user)

const fullName = computed(() => {
  const first = profile.value?.first_name
  const last = profile.value?.last_name

  if (first || last) {
    return [first, last].filter(Boolean).join(' ')
  }

  return 'Nessun dato disponibile'
})

const email = computed(() => profile.value?.email || 'Nessun dato disponibile')
const codiceFiscale = computed(() => profile.value?.cf || 'Nessun dato disponibile')
const mainSiteName = computed(() => profile.value?.main_site_name || 'Non assegnato')
const mainSiteAddress = computed(() => profile.value?.main_site_address || 'Non assegnato')

onMounted(async () => {
  if (profile.value) {
    loading.value = false
    return
  }

  try {
    await authStore.fetchUser()
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
          <dt class="font-semibold text-slate-900">Nome completo</dt>
          <dd class="col-span-2">{{ fullName }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-2">
          <dt class="font-semibold text-slate-900">Email</dt>
          <dd class="col-span-2">{{ email }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-2">
          <dt class="font-semibold text-slate-900">Codice fiscale</dt>
          <dd class="col-span-2">{{ codiceFiscale }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-2">
          <dt class="font-semibold text-slate-900">Cantiere principale</dt>
          <dd class="col-span-2">{{ mainSiteName }}</dd>
        </div>
        <div class="grid grid-cols-3 gap-2">
          <dt class="font-semibold text-slate-900">Indirizzo cantiere</dt>
          <dd class="col-span-2">{{ mainSiteAddress }}</dd>
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
