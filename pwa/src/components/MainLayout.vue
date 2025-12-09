<script setup>
import { watch } from 'vue'
import { useRoute } from 'vue-router'
import AppHeader from './AppHeader.vue'
import AppSidebar from './AppSidebar.vue'
import { useUiStore } from '../stores/ui'

const uiStore = useUiStore()
const route = useRoute()

watch(
  () => route.fullPath,
  () => {
    uiStore.closeSidebar()
  }
)
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <AppSidebar />

    <div class="flex min-h-screen flex-col">
      <AppHeader />

      <main class="safe-area-bottom mx-auto w-full max-w-3xl flex-1 px-4 py-6 sm:px-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>
