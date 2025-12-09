<script setup>
import { computed } from 'vue'
import { useRoute, RouterView } from 'vue-router'

const route = useRoute()

const wrapperClasses = computed(() =>
  route.name === 'login' ? 'mx-auto max-w-md p-6' : ''
)
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <RouterView v-slot="{ Component }">
      <div :class="wrapperClasses">
        <component :is="Component" />
      </div>
    </RouterView>
  </div>
</template>
