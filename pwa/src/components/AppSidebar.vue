<script setup>
import { computed } from 'vue'
import { useUiStore } from '../stores/ui'
import { useAuthStore } from '../stores/auth'

const uiStore = useUiStore()
const authStore = useAuthStore()

const links = [
  { label: 'Home', to: '/home' },
  { label: 'Profilo', to: '/profile' },
  { label: 'Documenti / Buste paga', to: '/payroll' },
]

const isOpen = computed(() => uiStore.sidebarOpen)

const handleNavigate = () => {
  uiStore.closeSidebar()
}

const handleLogout = async () => {
  await authStore.logout()
  uiStore.closeSidebar()
}
</script>

<template>
  <div>
    <div
      v-if="isOpen"
      class="fixed inset-0 z-20 bg-slate-900/40 transition-opacity"
      @click="uiStore.closeSidebar"
    ></div>

    <aside
      class="fixed inset-y-0 left-0 z-30 w-72 transform border-r border-slate-200 bg-white shadow-xl transition-transform duration-200 ease-out"
      :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <h2 class="text-lg font-semibold text-slate-900">DG Pulizie</h2>
        <button
          type="button"
          class="text-sm text-slate-500 transition hover:text-slate-700"
          @click="uiStore.closeSidebar"
        >
          Chiudi
        </button>
      </div>

      <nav class="space-y-1 px-4 py-4">
        <RouterLink
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="flex items-center rounded-lg px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
          active-class="bg-slate-900 text-white hover:bg-slate-900"
          @click="handleNavigate"
        >
          {{ link.label }}
        </RouterLink>
      </nav>

      <div class="px-4 py-4">
        <button
          type="button"
          class="flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
          @click="handleLogout"
        >
          Logout
        </button>
      </div>
    </aside>
  </div>
</template>
