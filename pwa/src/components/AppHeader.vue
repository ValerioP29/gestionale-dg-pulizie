<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useUiStore } from '../stores/ui'

const uiStore = useUiStore()
const route = useRoute()

const pageTitle = computed(() => route.meta?.title || 'DG Pulizie')
</script>

<template>
  <header
    class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white/80 px-4 py-3 backdrop-blur"
  >
    <button
      type="button"
      class="flex h-10 w-10 flex-col items-center justify-center gap-1 rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-100"
      @click="uiStore.toggleSidebar"
      aria-label="Apri menu"
    >
      <span class="block h-0.5 w-5 bg-slate-700"></span>
      <span class="block h-0.5 w-5 bg-slate-700"></span>
      <span class="block h-0.5 w-5 bg-slate-700"></span>
    </button>

    <h1 class="text-lg font-semibold text-slate-900">{{ pageTitle }}</h1>
  </header>
</template>
