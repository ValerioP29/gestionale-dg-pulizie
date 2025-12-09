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
    <Transition name="overlay-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-20 bg-slate-900/50"
        @click="uiStore.closeSidebar"
      ></div>
    </Transition>

    <Transition name="sidebar-slide">
      <aside
        v-if="isOpen"
        class="fixed inset-y-0 left-0 z-30 flex w-72 flex-col border-r border-slate-200 bg-white shadow-xl"
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
            class="flex items-center rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-200"
            active-class="bg-slate-900 text-white shadow-sm hover:bg-slate-900"
            @click="handleNavigate"
          >
            {{ link.label }}
          </RouterLink>
        </nav>

        <div class="mt-auto border-t border-slate-200 px-4 py-4">
          <button
            type="button"
            class="flex w-full items-center justify-center rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
            @click="handleLogout"
          >
            Logout
          </button>
        </div>
      </aside>
    </Transition>
  </div>
</template>

<style scoped>
.overlay-fade-enter-active,
.overlay-fade-leave-active {
  transition: opacity 0.2s ease;
}

.overlay-fade-enter-from,
.overlay-fade-leave-to {
  opacity: 0;
}

.sidebar-slide-enter-active,
.sidebar-slide-leave-active {
  transition: transform 0.25s ease;
}

.sidebar-slide-enter-from,
.sidebar-slide-leave-to {
  transform: translateX(-100%);
}
</style>
